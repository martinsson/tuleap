/*
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

import { TrackerProject, MilestoneData, State } from "../type";

export default {
    setProjectId(state: State, project_id: number): void {
        state.project_id = project_id;
    },

    setIsLoading(state: State, loading: boolean): void {
        state.is_loading = loading;
    },

    setNbBacklogItem(state: State, total: number): void {
        state.nb_backlog_items = total;
    },

    setNbUpcomingReleases(state: State, total: number): void {
        state.nb_upcoming_releases = total;
    },

    setErrorMessage(state: State, error_message: string): void {
        state.error_message = error_message;
    },

    resetErrorMessage(state: State): void {
        state.error_message = null;
    },

    setCurrentMilestones(state: State, milestones: MilestoneData[]): void {
        state.current_milestones = milestones;
    },

    setTrackers(state: State, trackers: TrackerProject[]): void {
        state.trackers = trackers;
    }
};
