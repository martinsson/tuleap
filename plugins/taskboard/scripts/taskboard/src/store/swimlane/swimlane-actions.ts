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

import { Card, RootState, Swimlane } from "../../type";
import { recursiveGet } from "tlp";
import { ActionContext } from "vuex";
import { SwimlaneState } from "./type";

export async function loadSwimlanes(
    context: ActionContext<SwimlaneState, RootState>
): Promise<void> {
    context.commit("setIsLoadingSwimlanes", true);
    try {
        await recursiveGet(`/api/v1/taskboard/${context.rootState.milestone_id}/cards`, {
            params: {
                limit: 100,
                offset: 0
            },
            getCollectionCallback: (collection: Card[]): Swimlane[] => {
                const swimlanes = collection.map(card => {
                    return { card };
                });
                context.commit("addSwimlanes", swimlanes);

                return swimlanes;
            }
        });
    } catch (error) {
        await context.dispatch("error/handleErrorMessage", error, { root: true });
    } finally {
        context.commit("setIsLoadingSwimlanes", false);
    }
}
