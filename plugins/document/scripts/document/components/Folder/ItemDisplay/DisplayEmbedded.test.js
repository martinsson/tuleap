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

import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest.js";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue.js";
import DisplayEmbedded from "./DisplayEmbedded.vue";
import VueRouter from "vue-router";

describe("DisplayEmbedded", () => {
    let router, component_options, store;

    beforeEach(() => {
        router = new VueRouter({
            routes: [
                {
                    path: "/folder/3/42",
                    name: "item"
                }
            ]
        });

        component_options = {
            localVue,
            router
        };
    });

    it(`Given user display an embedded file content
        When backend throw an error
        Then no spinner is displayed and component is not rendered`, async () => {
        const store_options = {
            state: {
                error: {
                    has_document_permission_error: true,
                    has_document_loading_error: false
                }
            },
            getters: {
                "error/does_document_have_any_error": true
            }
        };
        store = createStoreMock(store_options);

        const wrapper = shallowMount(DisplayEmbedded, { store, ...component_options });

        await wrapper.vm.$nextTick().then(() => {});

        expect(wrapper.find("[data-test=embedded_content]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=embedded_spinner]").exists()).toBeFalsy();
    });

    it(`Given user display an embedded file content
        When component is rendered
        Backend load the embedded file content`, async () => {
        const store_options = {
            state: {
                error: {}
            },
            getters: {
                "error/does_document_have_any_error": false
            }
        };

        store = createStoreMock(store_options);

        const wrapper = shallowMount(DisplayEmbedded, { store, ...component_options });

        await wrapper.vm.$nextTick().then(() => {});

        jest.spyOn(wrapper.vm, "loadDocumentWithAscendentHierarchy").mockReturnValue({
            id: 10,
            embedded_properties: {
                content: "<p>my custom content </p>"
            }
        });

        expect(wrapper.find("[data-test=embedded_content]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=embedded_spinner]").exists()).toBeFalsy();
    });

    it(`Reset currently displayed item form stored
        When compontent is destroyed`, () => {
        const store_options = {
            state: {
                error: {}
            },
            getters: {
                "error/does_document_have_any_error": false
            }
        };

        store = createStoreMock(store_options);

        const wrapper = shallowMount(DisplayEmbedded, { store, ...component_options });

        wrapper.destroy();
        expect(store.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", null);
    });
});
