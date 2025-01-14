<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentationFactory;

class AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder
{

    /** @var BacklogItemRepresentationFactory */
    private $backlog_item_representation_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogFactory */
    private $backlog_factory;


    public function __construct(
        BacklogItemRepresentationFactory $backlog_item_representation_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $backlog_item_collection_factory,
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory
    ) {
        $this->backlog_item_representation_factory = $backlog_item_representation_factory;
        $this->backlog_item_collection_factory     = $backlog_item_collection_factory;
        $this->backlog_factory                     = $backlog_factory;
    }

    /**
     * @return AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations;
     */
    public function getPaginatedBacklogItemsRepresentationsForMilestone(PFUser $user, Planning_Milestone $milestone, $limit, $offset)
    {
        $backlog = $this->backlog_factory->getBacklog($milestone, $limit, $offset);

        return $this->getBacklogItemsRepresentations($user, $milestone, $backlog);
    }

    /**
     * @return AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations;
     */
    public function getPaginatedBacklogItemsRepresentationsForTopMilestone(PFUser $user, Planning_Milestone $top_milestone, $limit, $offset)
    {
        $backlog = $this->backlog_factory->getSelfBacklog($top_milestone, $limit, $offset);

        return $this->getBacklogItemsRepresentations($user, $top_milestone, $backlog);
    }

    private function getBacklogItemsRepresentations(PFUser $user, Planning_Milestone $milestone, $backlog)
    {
        $backlog_items                 = $this->getMilestoneBacklogItems($user, $milestone, $backlog);
        $backlog_items_representations = array();

        foreach ($backlog_items as $backlog_item) {
            $backlog_items_representations[] = $this->backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        return new AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations($backlog_items_representations, $backlog_items->getTotalAvaialableSize());
    }

    private function getMilestoneBacklogItems(PFUser $user, $milestone, $backlog)
    {
        return $this->backlog_item_collection_factory->getUnplannedOpenCollection($user, $milestone, $backlog, false);
    }
}
