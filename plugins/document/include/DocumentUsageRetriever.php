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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Tuleap\Document;

use ForgeConfig;
use PFUser;
use Project;

class DocumentUsageRetriever
{
    public function shouldUseDocument(?PFUser $user, Project $project): bool
    {
        if (! $user) {
            return false;
        }

        $user_new_ui_preference = $user->getPreference("plugin_docman_display_new_ui_" . $project->getID());
        if ($user_new_ui_preference === '1') {
            return true;
        }

        if ($user_new_ui_preference === '0') {
            return false;
        }

        return $this->canProjectUseNewUI($project);
    }

    public function canProjectUseNewUI(Project $project): bool
    {
        if (ForgeConfig::get('disable_new_document_ui_by_default')) {
            return false;
        }

        $blacklist_projects_string = ForgeConfig::get('sys_project_blacklist_which_uses_legacy_ui_by_default');
        if ($blacklist_projects_string) {
            $blacklist_projects = array_map('trim', explode(',', $blacklist_projects_string));
            if ($blacklist_projects && in_array($project->getID(), $blacklist_projects)) {
                return false;
            }
        }

        return true;
    }
}
