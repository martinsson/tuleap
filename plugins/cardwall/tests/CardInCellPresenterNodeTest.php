<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__) .'/bootstrap.php';
require_once dirname(__FILE__).'/../../../tests/simpletest/common/TreeNode/NodeDuplicatorContractTest.class.php';


class Cardwall_CardInCellPresenterNodeTest extends TuleapTestCase
{

    public function itHoldsTheGivenPresenter()
    {
        $presenter      = mock('Cardwall_CardInCellPresenter');
        $presenter_node = new Cardwall_CardInCellPresenterNode($presenter);
        $this->assertEqual($presenter, $presenter_node->getCardInCellPresenter());
    }

    public function itHasAnArtifact()
    {
        $artifact       = mock('Tracker_Artifact');
        $presenter      = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact);
        $presenter_node = new Cardwall_CardInCellPresenterNode($presenter);
        $this->assertEqual($artifact, $presenter_node->getArtifact());
    }
}
