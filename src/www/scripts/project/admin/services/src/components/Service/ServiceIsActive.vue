<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

<template functional>
    <div class="tlp-form-element">
        <label
            class="tlp-label"
            v-bind:for="props.id"
            v-translate
        >Available (also available for new projects)</label>
        <div class="tlp-switch">
            <input
                class="tlp-switch-checkbox"
                v-bind:id="props.id"
                type="checkbox"
                name="is_active"
                value="1"
                v-bind:checked="props.value"
            >
            <label
                class="tlp-switch-button"
                v-bind:for="props.id"
                aria-hidden
            ></label>
        </div>
    </div>
</template>
