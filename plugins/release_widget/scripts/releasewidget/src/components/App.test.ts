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

import { shallowMount, Wrapper } from "@vue/test-utils";
import App from "./App.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import Vue from "vue";
import { StoreOptions } from "../type";
import { initVueGettext } from "../../../../../../src/www/scripts/tuleap/gettext/vue-gettext-init";

const project_id = 102;
async function getPersonalWidgetInstance(store_options: StoreOptions): Promise<Wrapper<App>> {
    const store = createStoreMock(store_options);
    const component_options = {
        propsData: {
            project_id
        },
        mocks: { $store: store }
    };

    await initVueGettext(Vue, () => {
        throw new Error("Fallback to default");
    });

    return shallowMount(App, component_options);
}

describe("Given a release widget", () => {
    let store_options: StoreOptions & Required<Pick<StoreOptions, "getters">>;
    beforeEach(() => {
        store_options = {
            state: {
                is_loading: false
            },
            getters: {
                has_rest_error: false
            }
        };
    });

    it("When there are no errors, then the widget content will be displayed", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=widget-content]")).toBeTruthy();
        expect(wrapper.contains("[data-test=show-error-message]")).toBeFalsy();
        expect(wrapper.contains("[data-test=is-loading]")).toBeFalsy();
    });

    it("When there is an error, then the widget content will not be displayed", async () => {
        store_options.getters.has_rest_error = true;
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=show-error-message]")).toBeTruthy();
        expect(wrapper.contains("[data-test=widget-content]")).toBeFalsy();
        expect(wrapper.contains("[data-test=is-loading]")).toBeFalsy();
    });

    it("When it is loading rest data, then a loader will be displayed", async () => {
        store_options.state.is_loading = true;
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=is-loading]")).toBeTruthy();
        expect(wrapper.contains("[data-test=widget-content]")).toBeFalsy();
        expect(wrapper.contains("[data-test=show-error-message]")).toBeFalsy();
    });

    it("When there is a rest error and it is empty, Then another message is displayed", async () => {
        store_options.state.error_message = "";
        store_options.getters.has_rest_error = true;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.find("[data-test=show-error-message]").text()).toEqual(
            "Oops, an error occurred!"
        );
    });
});
