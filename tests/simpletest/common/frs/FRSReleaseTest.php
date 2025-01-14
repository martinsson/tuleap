<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('FRSPackageFactory');
Mock::generatePartial('FRSRelease', 'FRSReleaseTestVersion', array('_getFRSPackageFactory', '_getProjectManager'));

class FRSReleaseTest extends TuleapTestCase
{

    function testIsActive()
    {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;

        $r = new FRSRelease();
        $r->setStatusId($active_value);
        $this->assertTrue($r->isActive());

        $r->setStatusId($hidden_value);
        $this->assertFalse($r->isActive());

        $r->setStatusId($deleted_value);
        $this->assertFalse($r->isActive());
    }

    function testIsHidden()
    {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;

        $r = new FRSRelease();
        $r->setStatusId($hidden_value);
        $this->assertTrue($r->isHidden());

        $r->setStatusId($active_value);
        $this->assertFalse($r->isHidden());

        $r->setStatusId($deleted_value);
        $this->assertFalse($r->isHidden());
    }

    function testIsDeleted()
    {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;

        $r = new FRSRelease();
        $r->setStatusId($deleted_value);
        $this->assertTrue($r->isDeleted());

        $r->setStatusId($hidden_value);
        $this->assertFalse($r->isDeleted());

        $r->setStatusId($active_value);
        $this->assertFalse($r->isDeleted());
    }

    function testGetProjectWithProjectSet()
    {
        $r = new FRSRelease();

        $p = new MockProject($this);
        $r->setProject($p);

        $this->assertIdentical($p, $r->getProject());
    }

    function testGetProjectWithGroupIdSet()
    {
        $r = new FRSReleaseTestVersion($this);
        $r->setGroupID(123);

        $p = new MockProject($this);

        $pm = new MockProjectManager($this);
        $pm->expectOnce('getProject', array(123));
        $pm->setReturnValue('getProject', $p);

        $r->setReturnValue('_getProjectManager', $pm);

        $this->assertIdentical($p, $r->getProject());
    }

    function testGetProjectWithNeitherProjectNorGroupID()
    {
        $r = new FRSReleaseTestVersion($this);
        $r->setPackageId(696);

        $pkg = new FRSPackage(array('group_id' => 123));

        $pf = new MockFRSPackageFactory($this);
        $pf->expectOnce('getFRSPackageFromDb', array(696, null, FRSPackageDao::INCLUDE_DELETED));
        $pf->setReturnValue('getFRSPackageFromDb', $pkg);
        $r->setReturnValue('_getFRSPackageFactory', $pf);

        $p = new MockProject($this);
        $pm = new MockProjectManager($this);
        $pm->expectOnce('getProject', array(123));
        $pm->setReturnValue('getProject', $p);
        $r->setReturnValue('_getProjectManager', $pm);

        $this->assertIdentical($p, $r->getProject());
    }

    function testGetGroupIdWithoutProjectSet()
    {
        $r = new FRSReleaseTestVersion($this);
        $r->setPackageId(696);

        $pkg = new FRSPackage(array('group_id' => 123));

        $pf = new MockFRSPackageFactory($this);
        $pf->expectOnce('getFRSPackageFromDb', array(696, null, FRSPackageDao::INCLUDE_DELETED));
        $pf->setReturnValue('getFRSPackageFromDb', $pkg);
        $r->setReturnValue('_getFRSPackageFactory', $pf);

        $this->assertEqual($r->getGroupID(), 123);
    }

    function testGetGroupIdWithProjectSet()
    {
        $r = new FRSRelease();

        $p = new MockProject($this);
        $p->setReturnValue('getID', 123);
        $r->setProject($p);

        $this->assertEqual($r->getGroupID(), 123);
    }
}
