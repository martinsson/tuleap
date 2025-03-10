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

import { shallowMount } from "@vue/test-utils";
import CardXrefLabel from "./CardXrefLabel.vue";

describe("CardXrefLabel", () => {
    it("displays the xref and the label of a card", () => {
        const wrapper = shallowMount(CardXrefLabel, {
            propsData: {
                card: {
                    id: 43,
                    label: "Story 2",
                    xref: "story #43",
                    color: "lake-placid-blue",
                    artifact_html_uri: "/path/to/43"
                }
            }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
});
