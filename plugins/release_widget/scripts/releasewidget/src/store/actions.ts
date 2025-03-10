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

import {
    getCurrentMilestones as getAllCurrentMilestones,
    getMilestonesContent as getContent,
    getNbOfBacklogItems as getBacklogs,
    getNbOfSprints as getSprints,
    getNbOfUpcomingReleases as getReleases,
    getTrackersProject as getTrackers
} from "../api/rest-querier";

import {
    Context,
    MilestoneContent,
    MilestoneData,
    TrackerNumberArtifacts,
    TrackerProject
} from "../type";
import { FetchWrapperError } from "tlp";

async function getNumberOfBacklogItems(context: Context): Promise<void> {
    context.commit("resetErrorMessage");
    let total = 0;
    const project_id = context.state.project_id;
    if (project_id !== null) {
        total = await getBacklogs({
            project_id,
            limit: context.state.limit,
            offset: context.state.offset
        });
    }
    return context.commit("setNbBacklogItem", total);
}

async function getNumberOfUpcomingReleases(context: Context): Promise<void> {
    context.commit("resetErrorMessage");
    let total = 0;
    const project_id = context.state.project_id;
    if (project_id !== null) {
        total = await getReleases({
            project_id,
            limit: context.state.limit,
            offset: context.state.offset
        });
    }
    return context.commit("setNbUpcomingReleases", total);
}

async function getCurrentMilestones(context: Context): Promise<void> {
    context.commit("resetErrorMessage");
    let milestones: MilestoneData[] = [];
    const project_id = context.state.project_id;
    if (project_id !== null) {
        milestones = await getAllCurrentMilestones({
            project_id,
            limit: context.state.limit,
            offset: context.state.offset
        });
    }

    const promises = milestones.map(
        async (milestone: MilestoneData): Promise<MilestoneData> => {
            await getInitialEffortAndNumberArtifactsInTrackers(context, milestone);
            return {
                ...milestone,
                total_sprint: await getNumberOfSprints(context, milestone)
            };
        }
    );

    const enriched_milestones = await Promise.all<MilestoneData>(promises);

    return context.commit("setCurrentMilestones", enriched_milestones);
}

export async function getMilestones(context: Context): Promise<void> {
    try {
        context.commit("setIsLoading", true);

        await getTrackersProject(context);
        await getNumberOfUpcomingReleases(context);
        await getNumberOfBacklogItems(context);
        await getCurrentMilestones(context);
    } catch (error) {
        await handleErrorMessage(context, error);
    } finally {
        context.commit("setIsLoading", false);
    }
}

async function getTrackersProject(context: Context): Promise<void> {
    context.commit("resetErrorMessage");
    let trackers: TrackerProject[] = [];
    const project_id = context.state.project_id;
    if (project_id !== null) {
        trackers = await getTrackers({
            project_id,
            limit: context.state.limit,
            offset: context.state.offset
        });
    }
    return context.commit("setTrackers", trackers);
}

function getNumberOfSprints(context: Context, milestone: MilestoneData): Promise<number> {
    context.commit("resetErrorMessage");
    return getSprints(milestone.id, context.state);
}

async function getInitialEffortAndNumberArtifactsInTrackers(
    context: Context,
    milestone: MilestoneData
): Promise<void> {
    const milestone_contents = await getContent(milestone.id, context.state);
    getInitialEffortOfRelease(context, milestone, milestone_contents);
    getNumberArtifactsInTrackerOfAgileDashboard(context, milestone, milestone_contents);
}

function getInitialEffortOfRelease(
    context: Context,
    milestone: MilestoneData,
    milestone_contents: MilestoneContent[]
): void {
    context.commit("resetErrorMessage");

    milestone.initial_effort = milestone_contents.reduce(
        (nb_users_stories: number, milestone_content: MilestoneContent) => {
            if (milestone_content.initial_effort !== null) {
                return nb_users_stories + milestone_content.initial_effort;
            }
            return nb_users_stories;
        },
        0
    );
}

function getNumberArtifactsInTrackerOfAgileDashboard(
    context: Context,
    milestone: MilestoneData,
    milestone_contents: MilestoneContent[]
): void {
    if (!milestone.resources) {
        return;
    }

    const trackers_with_number_artifacts: TrackerNumberArtifacts[] = [];

    milestone.resources.content.accept.trackers.forEach(agiledashboard_tracker => {
        const tracker_with_color = context.state.trackers.find(
            tracker => tracker.id === agiledashboard_tracker.id
        );

        let color = null;
        if (tracker_with_color) {
            color = tracker_with_color.color_name;
        }
        trackers_with_number_artifacts.push({
            id: agiledashboard_tracker.id,
            label: agiledashboard_tracker.label,
            total_artifact: 0,
            color_name: color
        });
    });

    milestone_contents.forEach(content => {
        const tracker_with_number_artifacts = trackers_with_number_artifacts.find(
            tracker => tracker.id === content.artifact.tracker.id
        );
        if (tracker_with_number_artifacts) {
            tracker_with_number_artifacts.total_artifact++;
        }
    });

    milestone.number_of_artifact_by_trackers = [...trackers_with_number_artifacts];
}

export async function handleErrorMessage(
    context: Context,
    rest_error: FetchWrapperError
): Promise<void> {
    try {
        const { error } = await rest_error.response.json();
        context.commit("setErrorMessage", error.code + " " + error.message);
    } catch (error) {
        context.commit("setErrorMessage", "");
    }
}
