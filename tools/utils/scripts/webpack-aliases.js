/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

const path = require("path");

const path_to_tuleap_root = path.resolve(__dirname, "../../../");
const path_to_tlp = path.join(path_to_tuleap_root, "src/www/themes/common/tlp/");
const path_to_tuleap_core = path.join(path_to_tuleap_root, "src/www/scripts/");

const tlp_fetch_alias = {
    "tlp-fetch": path.join(path_to_tlp, "src/js/fetch-wrapper.js")
};

const tuleap_core_alias = {
    "tuleap-core": path_to_tuleap_core
};

const angular_tlp_alias = {
    "angular-tlp": path.join(path_to_tlp, "angular-tlp/index.js")
};

const tlp_mocks_alias = {
    "tlp-mocks": path.join(path_to_tlp, "mocks/index.js")
};

const jquery_mocks_alias = {
    "jquery-mocks": path.join(path_to_tlp, "mocks/jQuery.js")
};

const ckeditor_mocks_alias = {
    "ckeditor-mocks": path.join(path_to_tuleap_core, "tuleap/ckeditor/mocks/index.js")
};

const angular_artifact_modal_alias = {
    "angular-artifact-modal": path.join(
        path_to_tuleap_root,
        "plugins/tracker/www/scripts/angular-artifact-modal/index.js"
    )
};

const vue_components_alias = {
    "@tuleap-vue-components": path.join(path_to_tuleap_core, "vue-components")
};

const skeletons_alias = {
    skeletons: path.join(path_to_tuleap_core, "vue-components/skeletons/")
};

const easygettext_loader_alias = {
    "easygettext-loader": path.resolve(__dirname, "./easygettext-loader.js")
};

const node_streams_alias = {
    "readable-stream": "readable-stream/readable-browser.js"
};

const angular_artifact_modal_aliases = Object.assign(
    {},
    angular_artifact_modal_alias,
    angular_tlp_alias,
    ckeditor_mocks_alias,
    node_streams_alias,
    tlp_fetch_alias,
    tlp_mocks_alias,
    tuleap_core_alias
);

function extendAliases(...aliases) {
    return Object.assign({}, ...aliases);
}

module.exports = {
    extendAliases,
    angular_artifact_modal_aliases,
    angular_tlp_alias,
    ckeditor_mocks_alias,
    easygettext_loader_alias,
    jquery_mocks_alias,
    skeletons_alias,
    tlp_fetch_alias,
    tlp_mocks_alias,
    tuleap_core_alias,
    vue_components_alias
};
