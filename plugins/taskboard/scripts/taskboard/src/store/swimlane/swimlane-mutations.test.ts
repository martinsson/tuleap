/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { Card } from "../../type";
import * as mutations from "./swimlane-mutations";
import { SwimlaneState } from "./type";

describe("addSwimlanes", () => {
    it("add swimlanes to existing ones", () => {
        const state: SwimlaneState = {
            swimlanes: [{ card: { id: 42 } }]
        } as SwimlaneState;
        mutations.addSwimlanes(state, [{ card: { id: 43 } as Card }, { card: { id: 44 } as Card }]);
        expect(state.swimlanes).toStrictEqual([
            { card: { id: 42 } },
            { card: { id: 43 } },
            { card: { id: 44 } }
        ]);
    });
});

describe("setIsLoadingSwimlanes", () => {
    it("set swimlane to loading state", () => {
        const state: SwimlaneState = {
            is_loading_swimlanes: false
        } as SwimlaneState;
        mutations.setIsLoadingSwimlanes(state, true);
        expect(state.is_loading_swimlanes).toStrictEqual(true);
    });
});
