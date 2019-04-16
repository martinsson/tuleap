<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types = 1);

namespace Tuleap\Docman\rest\v1;

use REST_TestDataBuilder;
use Tuleap\Docman\rest\DocmanDataBuilder;
use Tuleap\Docman\rest\DocmanWithMetadataActivatedBase;

require_once __DIR__ . '/../bootstrap.php';

class DocmanHardcodedMetadataTest extends DocmanWithMetadataActivatedBase
{
    public function testGetRootId(): int
    {
        $project_response = $this->getResponse($this->client->get('projects/' . $this->project_id));

        $this->assertSame(200, $project_response->getStatusCode());

        $json_projects = $project_response->json();
        return $json_projects['additional_informations']['docman']['root_item']['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForAdmin(int $root_id): array
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folders   = $response->json();

        $this->assertEquals(count($folders), 1);
        $this->assertEquals($folders[0]['user_can_write'], true);

        return $folders;
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostFolderStatus(array $items): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $query = json_encode(
            [
                'title'       => 'Faboulous Folder With Status',
                'description' => 'A description',
                'status'      => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . "/folders", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }


    /**
     * Find first item in given array of items which has given title.
     *
     * @return array|null Found item. null otherwise.
     */
    private function findItemByTitle(array $items, $title)
    {
        $index = array_search($title, array_column($items, 'title'));
        if ($index === false) {
            return null;
        }
        return $items[$index];
    }
}