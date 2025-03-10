<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="project-release-infos-badges">
        <a class="project-release-info-badge tlp-badge-primary toggle-sprints" v-if="get_top_planning_link !== null" v-bind:href="get_top_planning_link" data-test="planning-link">
            <i class="fa fa-map-signs tlp-badge-icon"></i>
            <translate v-bind:translate-n="releaseData.total_sprint" translate-plural="%{ releaseData.total_sprint } sprints">
                %{ releaseData.total_sprint } sprint
            </translate>
        </a>
        <div class="project-release-info-badge tlp-badge-primary tlp-badge-outline">
            <translate v-if="capacity_exists" v-bind:translate-params="{capacity: releaseData.capacity}" data-test="capacity-not-empty">
                Capacity: %{capacity}
            </translate>
            <translate v-else data-test="capacity-empty">
                Capacity: N/A
            </translate>
        </div>
        <div class="project-release-info-badge tlp-badge-warning tlp-badge-outline">
            <translate v-if="initial_effort_exist" v-bind:translate-params="{initialEffort: releaseData.initial_effort}" data-test="initial-effort-not-empty">
                Initial effort: %{initialEffort}
            </translate>
            <translate v-else data-test="initial-effort-empty">
                Initial effort: N/A
            </translate>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../type";
import { State } from "vuex-class";

@Component
export default class ReleaseBadges extends Vue {
    @Prop()
    readonly releaseData!: MilestoneData;
    @State
    readonly project_id!: number;

    get get_top_planning_link(): string | null {
        if (!this.releaseData.planning) {
            return null;
        }
        return (
            "/plugins/agiledashboard/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&planning_id=" +
            encodeURIComponent(this.releaseData.planning.id) +
            "&action=show&aid=" +
            encodeURIComponent(this.releaseData.id) +
            "&pane=planning-v2"
        );
    }

    get capacity_exists(): boolean {
        if (!this.releaseData.capacity) {
            return false;
        }
        return this.releaseData.capacity > 0;
    }

    get initial_effort_exist(): boolean {
        if (!this.releaseData.initial_effort) {
            return false;
        }
        return this.releaseData.initial_effort > 0;
    }
}
</script>
