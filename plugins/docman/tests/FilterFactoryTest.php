<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'bootstrap.php';

class Docman_FilterFactoryTest extends TuleapTestCase
{
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testCloneFilter()
    {
        $mdFactory =  \Mockery::spy(Docman_MetadataFactory::class);
        $mdFactory->allows(['isRealMetadata' => false]);

        $md = new Docman_ListMetadata();
        $md->setLabel('item_type');

        $srcFilter     = \Mockery::mock(Docman_FilterItemType::class);
        $srcFilter->md = $md;
        $dstReport     = new Docman_Report();
        $dstReport->setGroupId(123);

        $filterFactory = \Mockery::mock(Docman_FilterFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $gsMd = new Docman_Metadata();
        $filterFactory->allows(['getGlobalSearchMetadata' => $gsMd]);
        $gsMd->setLabel('global_txt');
        $itMd = new Docman_ListMetadata();
        $filterFactory->allows(['getItemTypeSearchMetadata' => $itMd]);
        $itMd->setLabel('item_type');

        $itMd->setUseIt(PLUGIN_DOCMAN_METADATA_USED);
        $metadataMapping = array('md' => array(), 'love' => array());
        $dstFilterFactory = \Mockery::mock(Docman_FilterFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $filterFactory->shouldReceive('getFilterFactory')->andReturns($dstFilterFactory);
        $filterFactory->shouldReceive('cloneFilterValues')->once();

        $dstFilterFactory->shouldReceive('createFromMetadata')->once();
        $dstFilterFactory->shouldReceive('createFilter')->once();

        $filterFactory->cloneFilter($srcFilter, $dstReport, $metadataMapping);
    }
}
