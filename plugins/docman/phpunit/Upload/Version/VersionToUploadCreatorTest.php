<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\Upload\Version;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;

class VersionToUploadCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;
    private $mockery_matcher_callback_wrapped_operations;

    public function setUp() : void
    {
        \ForgeConfig::store();

        $this->dao = \Mockery::mock(DocumentOnGoingVersionToUploadDAO::class);

        $this->mockery_matcher_callback_wrapped_operations = \Mockery::on(
            function (callable $operations) {
                $operations($this->dao);
                return true;
            }
        );
    }

    public function tearDown()  : void
    {
        \ForgeConfig::restore();
    }

    public function testCreation()
    {
        $this->dao->shouldReceive('wrapAtomicOperations')->with($this->mockery_matcher_callback_wrapped_operations);
        $creator = new VersionToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '999999');
        $item = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->andReturns([]);
        $this->dao->shouldReceive('saveDocumentVersionOngoingUpload')->once()->andReturns(12);

        $document_to_upload = $creator->create(
            $item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            123456
        );

        $this->assertSame(12, $document_to_upload->getVersionId());
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile()
    {
        $this->dao->shouldReceive('wrapAtomicOperations')->with($this->mockery_matcher_callback_wrapped_operations);
        $creator = new VersionToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->andReturns(
            [
                ['id' => 12, 'user_id' => 102, 'filename' => 'filename', 'filesize' => 123456]
            ]
        );

        $document_to_upload = $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            123456
        );

        $this->assertSame(12, $document_to_upload->getVersionId());
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument()
    {
        $this->dao->shouldReceive('wrapAtomicOperations')->with($this->mockery_matcher_callback_wrapped_operations);
        $creator = new VersionToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->andReturns(
            [
                ['user_id' => 103]
            ]
        );

        $this->expectException(UploadCreationConflictException::class);

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            123456
        );
    }

    public function testCreationIsRejectedWhenTheUserIsAlreadyCreatingTheDocumentWithAnotherFile()
    {
        $this->dao->shouldReceive('wrapAtomicOperations')->with($this->mockery_matcher_callback_wrapped_operations);
        $creator = new VersionToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->andReturns(
            [
                ['user_id' => 102, 'filename' => 'filename1', 'filesize' => 123456]
            ]
        );

        $this->expectException(UploadCreationFileMismatchException::class);

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename2',
            789
        );
    }

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit()
    {
        $creator = new VersionToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '1');
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $current_time = new \DateTimeImmutable();

        $this->expectException(UploadMaxSizeExceededException::class);

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            2
        );
    }
}