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
  -
  -->
<template>
    <th class="taskboard-header" v-bind:class="classes">
        <div class="taskboard-header-content">
            <span class="taskboard-header-label">{{ column.label }}</span>
            <wrong-color-popover v-if="should_popover_be_displayed" v-bind:color="this.column.color"/>
        </div>
    </th>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { ColumnDefinition } from "../../type";
import WrongColorPopover from "./WrongColorPopover.vue";
import { State } from "vuex-class";

const DEFAULT_COLOR = "#F8F8F8";

@Component({
    components: { WrongColorPopover }
})
export default class TaskBoardHeaderCell extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @State
    readonly user_is_admin!: boolean;

    get classes(): string {
        if (this.is_rgb_color) {
            return "";
        }

        return this.column.color ? "taskboard-header-" + this.column.color : "";
    }

    get is_rgb_color(): boolean {
        return this.column.color.charAt(0) === "#";
    }

    get is_default_color(): boolean {
        return this.column.color === DEFAULT_COLOR;
    }

    get should_popover_be_displayed(): boolean {
        return this.user_is_admin && this.is_rgb_color && !this.is_default_color;
    }
}
</script>
