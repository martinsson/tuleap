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
import localVue from "../../../helpers/local-vue.js";

import QuickLookDeleteButton from "./QuickLookDeleteButton.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest.js";
import { TYPE_LINK, TYPE_FILE } from "../../../constants.js";
import EventBus from "../../../helpers/event-bus.js";

describe("QuickLookDeleteButton", () => {
    let delete_button_factory, store;
    beforeEach(() => {
        const state = {
            project_id: 101
        };

        const store_options = {
            state
        };

        store = createStoreMock(store_options);

        delete_button_factory = (user_can_write, item_type) => {
            return shallowMount(QuickLookDeleteButton, {
                localVue,
                propsData: {
                    item: {
                        id: 1,
                        user_can_write: user_can_write,
                        type: item_type
                    }
                },
                mocks: { $store: store }
            });
        };
    });

    it(`Displays the delete button because the user can write and has the right to delete items`, () => {
        store.state.is_deletion_allowed = true;
        const wrapper = delete_button_factory(true, TYPE_LINK);
        expect(wrapper.find("[data-test=quick-look-delete-button]").exists()).toBeTruthy();
    });
    it(`Does not display the delete button if the user can't write but has the right to delete items`, () => {
        store.state.is_deletion_allowed = true;
        const wrapper = delete_button_factory(false, TYPE_LINK);
        expect(wrapper.find("[data-test=quick-look-delete-button]").exists()).toBeFalsy();
    });
    it(`Does not display the delete button if the user can write but cannot to delete items`, () => {
        store.state.is_deletion_allowed = false;
        const wrapper = delete_button_factory(true, TYPE_LINK);
        expect(wrapper.find("[data-test=quick-look-delete-button]").exists()).toBeFalsy();
    });

    it(`When the user clicks the button, then it should trigger an event to open the confirmation modal`, () => {
        store.state.is_deletion_allowed = true;
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = delete_button_factory(true, TYPE_FILE);
        wrapper.find("[data-test=quick-look-delete-button]").trigger("click");

        expect(event_bus_emit).toHaveBeenCalledWith(
            "show-confirm-item-deletion-modal",
            expect.any(Object)
        );
    });
});
