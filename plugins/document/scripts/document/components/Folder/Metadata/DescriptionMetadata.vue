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
  -
  -->

<template>
    <div class="tlp-form-element">
        <label
            class="tlp-label"
            for="document-new-item-description"
            v-translate
        >
            Description
        </label>
        <textarea
            class="tlp-textarea"
            id="document-new-item-description"
            name="description"
            v-bind:placeholder="placeholder"
            v-bind:value="value"
            v-on:input="$emit('input', $event.target.value)"
        ></textarea>
    </div>
</template>
<script>
export default {
    props: {
        value: String
    },
    computed: {
        placeholder() {
            return this.$gettext("My useful document description");
        }
    }
};
</script>
