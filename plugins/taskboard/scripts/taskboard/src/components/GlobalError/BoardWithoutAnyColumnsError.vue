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
    <board-without-any-columns-error-for-admin v-if="user_is_admin"/>
    <board-without-any-columns-error-for-users v-else/>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import BoardWithoutAnyColumnsErrorForAdmin from "./BoardWithoutAnyColumnsErrorForAdmin.vue";
import BoardWithoutAnyColumnsErrorForUsers from "./BoardWithoutAnyColumnsErrorForUsers.vue";
import { namespace } from "vuex-class";

const user = namespace("user");

@Component({
    components: { BoardWithoutAnyColumnsErrorForUsers, BoardWithoutAnyColumnsErrorForAdmin }
})
export default class BoardWithoutAnyColumnsError extends Vue {
    @user.State
    readonly user_is_admin!: boolean;
}
</script>
