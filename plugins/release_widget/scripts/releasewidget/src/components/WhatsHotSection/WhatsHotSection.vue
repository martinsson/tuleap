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
    <div class="project-release-timeframe">
        <span class="project-release-label" v-translate>What's hot</span>
        <release-displayer
            v-for="milestone in current_milestones"
            v-bind:key="milestone.id"
            v-bind:release-data="milestone"
            v-bind:data-test="`current-milestones-test-${milestone.label}`"
        />
    </div>
</template>

<script lang="ts">
import ReleaseDisplayer from "./ReleaseDisplayer.vue";
import Vue from "vue";
import { State } from "vuex-class";
import { MilestoneData } from "../../type";
import { Component } from "vue-property-decorator";

@Component({
    components: { ReleaseDisplayer }
})
export default class WhatsHotSection extends Vue {
    @State
    readonly current_milestones!: MilestoneData[];
}
</script>
