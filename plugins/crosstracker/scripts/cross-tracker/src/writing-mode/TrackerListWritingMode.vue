<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="cross-tracker-form-trackers-selected">
        <span
            class="tlp-badge-primary tlp-badge-outline cross-tracker-selected-tracker"
            v-for="tracker of trackers"
            v-bind:key="tracker.id"
        >
            <span>
                <i class="fa fa-times tlp-badge-icon cross-tracker-remove-tracker"
                   v-on:click="removeTracker(tracker)"
                ></i> {{ tracker.tracker_label }}
            </span>
            <span>
                <i class="fa fa-archive cross-tracker-report-archive-icon"></i>{{ tracker.project_label }}
            </span>
        </span>
    </div>
</template>
<script>
export default {
    name: "TrackerListWritingMode",
    props: {
        trackers: Array
    },
    methods: {
        removeTracker(tracker) {
            this.$emit("trackerRemoved", tracker);
        }
    }
};
</script>
