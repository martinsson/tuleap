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

/**
 * A pane to be displayed in git repo management
 */
abstract class GitViews_RepoManagement_Pane {

    /**
     * @var GitRepository
     */
    protected $repository;

    public function __construct(GitRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * @return string eg: 'perms'
     */
    public abstract function getIdentifier();

    /**
     * @return string eg: 'Accesss Control'
     */
    public abstract function getTitle();

    /**
     * @return string eg: '<form>...</form>'
     */
    public abstract function getContent();
}
?>
