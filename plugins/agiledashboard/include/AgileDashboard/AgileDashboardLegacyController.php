<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard;

use AgileDashboardRouterBuilder;
use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class AgileDashboardLegacyController implements DispatchableWithRequest
{
    /**
     * @var AgileDashboardRouterBuilder
     */
    private $router_builder;

    public function __construct(AgileDashboardRouterBuilder $router_builder)
    {
        $this->router_builder = $router_builder;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout  $layout
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $request->getProject();

        if ($project->isDeleted()) {
            $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('include_exit', 'project_status_' . $project->getStatus()));
            $layout->redirect('/');
        }

        $router = $this->router_builder->build($request);

        $router->route($request);
    }
}
