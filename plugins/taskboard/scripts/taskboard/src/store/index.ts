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

import { RootState } from "../type";
import { Store, StoreOptions } from "vuex";
import error from "./error";
import swimlane from "./swimlane";
import { UserState } from "./user/type";
import { createUserModule } from "./user";

export function createStore(
    initial_root_state: RootState,
    initial_user_state: UserState
): Store<RootState> {
    const user = createUserModule(initial_user_state);

    return new Store({
        state: initial_root_state,
        modules: {
            error,
            swimlane,
            user
        }
    } as StoreOptions<RootState>);
}
