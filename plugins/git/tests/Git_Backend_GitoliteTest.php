<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;

require_once 'bootstrap.php';

Mock::generate('Backend');
Mock::generate('Git_GitoliteDriver');
Mock::generatePartial('Git_Backend_Gitolite', 'Git_Backend_GitoliteTestVersion', array('getDao', 'loadRepositoryFromId'));
Mock::generate('GitRepository');
Mock::generate('DataAccessResult');
Mock::generate('Project');
Mock::generate('PermissionsManager');

abstract class Git_Backend_GitoliteCommonTest extends TuleapTestCase
{

    protected function _GivenAGitRepoWithNameAndNamespace($name, $namespace)
    {
        $repository = new GitRepository();
        $repository->setName($name);
        $repository->setNamespace($namespace);

        $project = new MockProject();
        $project->setReturnValue('getUnixName', 'gpig');
        $project->setReturnValue('getId', 123);
        $repository->setProject($project);

        return $repository;
    }

    protected function _GivenABackendGitolite()
    {
        $driver             = mock('Git_GitoliteDriver');
        $dao                = \Mockery::spy(GitDao::class);
        $permissionsManager = mock('PermissionsManager');
        $gitPlugin          = \Mockery::mock(GitPlugin::class);
        $backend = new Git_Backend_Gitolite($driver, mock(GitoliteAccessURLGenerator::class), mock('Logger'));
        $backend->setDao($dao);
        $backend->setPermissionsManager($permissionsManager);
        $backend->setGitPlugin($gitPlugin);
        return $backend;
    }
}

class Git_Backend_GitoliteTest extends Git_Backend_GitoliteCommonTest
{

    protected $fixturesPath;

    private $forkPermissions;

    public function setUp()
    {
        parent::setUp();
        $this->fixtureRenamePath = $this->getTmpDir() . '/rename';

        mkdir($this->fixtureRenamePath .'/legacy', 0770, true);

        $this->forkPermissions = array();
    }

    function getPartialMock($className, $methods)
    {
        $partialName = $className.'Partial'.uniqid();
        Mock::generatePartial($className, $partialName, $methods);
        return new $partialName($this);
    }

    public function testRenameProjectOk()
    {
        $project = $this->getPartialMock('Project', array('getUnixName'));
        $project->setReturnValue('getUnixName', 'legacy');

        $backend = $this->getPartialMock('Git_Backend_Gitolite', array('glRenameProject', 'getBackend'));

        $driver = new MockGit_GitoliteDriver();
        $driver->setReturnValue('getRepositoriesPath', $this->fixtureRenamePath);
        $backend->setDriver($driver);

        $bck = new MockBackend();
        $bck->expectNever('log');
        $backend->setReturnValue('getBackend', $bck);

        $this->assertTrue(is_dir($this->fixtureRenamePath .'/legacy'));
        $this->assertFalse(is_dir($this->fixtureRenamePath .'/newone'));

        $backend->expectOnce('glRenameProject', array('legacy', 'newone'));
        $this->assertTrue($backend->renameProject($project, 'newone'));

        clearstatcache(true, $this->fixtureRenamePath .'/legacy');
        $this->assertFalse(is_dir($this->fixtureRenamePath .'/legacy'));
        $this->assertTrue(is_dir($this->fixtureRenamePath .'/newone'));
    }

    public function itSavesForkInfoIntoDB()
    {
        $name  = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $driver = mock('Git_GitoliteDriver');

        $project = mock('Project');

        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);

        $backend = partial_mock('Git_Backend_Gitolite', array('clonePermissions'), array($driver, mock(GitoliteAccessURLGenerator::class), mock('Logger')));
        $dao = \Mockery::spy(GitDao::class);
        $backend->setDao($dao);

        $backend->expectOnce('clonePermissions', array($old_repo, $new_repo));
        $dao->shouldReceive('save')->with($new_repo)->once()->andReturns(667);
        $dao->shouldReceive('isRepositoryExisting')->with(\Mockery::any(), $new_repo_path)->andReturns(false);

        $this->assertEqual(667, $backend->fork($old_repo, $new_repo, $this->forkPermissions));
    }

    public function testFork_clonesRepository()
    {
        $name  = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $driver     = new MockGit_GitoliteDriver();
        $driver->setReturnValue('fork', true);
        $project    = new MockProject();

        $project->setReturnValue('getUnixName', 'gpig');

        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);

        $backend = TestHelper::getPartialMock('Git_Backend_Gitolite', array('clonePermissions'));
        $backend->__construct($driver, mock(GitoliteAccessURLGenerator::class), mock('Logger'));

        $driver->expectOnce('fork', array($name, 'gpig/'. $old_namespace, 'gpig/'. $new_namespace));
        $driver->expectOnce('dumpProjectRepoConf', array($project));
        $driver->expectNever('push');

        $backend->forkOnFilesystem($old_repo, $new_repo);
    }

    public function testFork_clonesRepositoryFromOneProjectToAnotherSucceed()
    {
        $repo_name        = 'tuleap';
        $old_project_name = 'garden';
        $new_project_name = 'gpig';
        $namespace        = '';
        $new_repo_path    = "$new_project_name/$namespace/$repo_name.git";

        $driver     = new MockGit_GitoliteDriver();
        $driver->setReturnValue('fork', true);

        $new_project    = new MockProject();
        $new_project->setReturnValue('getUnixName', $new_project_name);

        $old_project    = new MockProject();
        $old_project->setReturnValue('getUnixName', 'garden');

        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($repo_name, $namespace);
        $new_repo->setProject($new_project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($repo_name, $namespace);
        $old_repo->setProject($old_project);

        $backend = TestHelper::getPartialMock('Git_Backend_Gitolite', array('clonePermissions'));
        $backend->__construct($driver, mock(GitoliteAccessURLGenerator::class), mock('Logger'));

        $driver->expectOnce('fork', array($repo_name, $old_project_name.'/'. $namespace, $new_project_name.'/'. $namespace));
        $driver->expectOnce('dumpProjectRepoConf', array($new_project));
        $driver->expectNever('push');

        $backend->forkOnFilesystem($old_repo, $new_repo, $this->forkPermissions);
    }

    public function testForkWithTargetPathAlreadyExistingShouldNotFork()
    {
        $name  = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $driver     = new MockGit_GitoliteDriver();
        $dao        = \Mockery::spy(GitDao::class);

        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setPath($new_repo_path);
        $project_id = $new_repo->getProject()->getId();

        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $old_namespace);

        $backend = TestHelper::getPartialMock('Git_Backend_Gitolite', array('clonePermissions'));
        $backend->__construct($driver, mock(GitoliteAccessURLGenerator::class), mock('Logger'));
        $backend->setDao($dao);

        $this->expectException('GitRepositoryAlreadyExistsException');

        $backend->expectNever('clonePermissions');
        $dao->shouldNotReceive('save');
        $dao->shouldReceive('isRepositoryExisting')->with($project_id, $new_repo_path)->andReturns(true);
        $driver->expectNever('fork');
        $driver->expectNever('dumpProjectRepoConf');
        $driver->expectNever('push');

        $backend->fork($old_repo, $new_repo, $this->forkPermissions);
    }

    public function testClonePermsWithPersonalFork()
    {
        $old_repo_id = 110;
        $new_repo_id = 220;

        $project = new MockProject();

        $old = new MockGitRepository();
        $old->setReturnValue('getId', $old_repo_id);
        $old->setReturnValue('getProject', $project);

        $new = new MockGitRepository();
        $new->setReturnValue('getId', $new_repo_id);
        $new->setReturnValue('getProject', $project);

        $backend  = $this->_GivenABackendGitolite();

        $permissionsManager = $backend->getPermissionsManager();
        $permissionsManager->expectOnce('duplicateWithStatic', array($old_repo_id, $new_repo_id, Git::allPermissionTypes()));

        $backend->clonePermissions($old, $new);
    }

    public function testClonePermsCrossProjectFork()
    {
        $old_repo_id = 110;
        $old_project = new MockProject();
        $old_project->setReturnValue('getId', 1);

        $new_repo_id = 220;
        $new_project = new MockProject();
        $new_project->setReturnValue('getId', 2);

        $old = new MockGitRepository();
        $old->setReturnValue('getId', $old_repo_id);
        $old->setReturnValue('getProject', $old_project);

        $new = new MockGitRepository();
        $new->setReturnValue('getId', $new_repo_id);
        $new->setReturnValue('getProject', $new_project);

        $backend  = $this->_GivenABackendGitolite();

        $permissionsManager = $backend->getPermissionsManager();
        $permissionsManager->expectOnce('duplicateWithoutStatic', array($old_repo_id, $new_repo_id, Git::allPermissionTypes()));

        $backend->clonePermissions($old, $new);
    }
}

class Git_Backend_Gitolite_disconnectFromGerrit extends TuleapTestCase
{

    private $repo_id = 123;

    public function setUp()
    {
        parent::setUp();
        $this->repository = aGitRepository()->withId($this->repo_id)->build();
        $this->dao        = \Mockery::spy(GitDao::class);
        $this->backend    = partial_mock('Git_Backend_Gitolite', array('updateRepoConf'));
        $this->backend->setDao($this->dao);
    }

    public function itAsksToDAOToDisconnectFromGerrit()
    {
        $this->dao->shouldReceive('disconnectFromGerrit')->with($this->repo_id)->once();

        $this->backend->disconnectFromGerrit($this->repository);
    }
}

class Git_Backend_Gitolite_UserAccessRightsTest extends Git_Backend_GitoliteCommonTest
{

    /**
     * @var Git_Backend_Gitolite
     */
    private $backend;

    public function setUp()
    {
        parent::setUp();
        $driver        = mock('Git_GitoliteDriver');
        $this->backend = new Git_Backend_Gitolite($driver, mock(GitoliteAccessURLGenerator::class), mock('Logger'));

        $this->user       = mock('PFUser');
        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns(1);
        stub($this->repository)->getProjectId()->returns(101);
    }

    public function itReturnsTrueIfUserIsProjectAdmin()
    {
        stub($this->user)->isMember(101, 'A')->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsTrueIfUserHasReadAccess()
    {
        stub($this->user)->hasPermission(Git::PERM_READ, 1, 101)->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsTrueIfUserHasReadAccessAndRepositoryIsMigratedToGerrit()
    {
        stub($this->user)->hasPermission(Git::PERM_READ, 1, 101)->returns(true);
        stub($this->repository)->isMigratedToGerrit()->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsTrueIfUserHasWriteAccess()
    {
        stub($this->user)->hasPermission(Git::PERM_WRITE, 1, 101)->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsFalseIfUserHasWriteAccessAndRepositoryIsMigratedToGerrit()
    {
        stub($this->user)->hasPermission(Git::PERM_WRITE, 1, 101)->returns(true);
        stub($this->repository)->isMigratedToGerrit()->returns(true);

        $this->assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsTrueIfUserHasRewindAccess()
    {
        stub($this->user)->hasPermission(Git::PERM_WPLUS, 1, 101)->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsFalseIfUserHasRewindAccessAndRepositoryIsMigratedToGerrit()
    {
        stub($this->user)->hasPermission(Git::PERM_WPLUS, 1, 101)->returns(true);
        stub($this->repository)->isMigratedToGerrit()->returns(true);

        $this->assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsFalseIfUserHasNoPermissions()
    {
        $this->assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }
}
