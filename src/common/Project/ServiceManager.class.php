<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Service\ServiceCannotBeUpdatedException;
use Tuleap\Project\Service\ServiceNotFoundException;

class ServiceManager
{

    public const CUSTOM_SERVICE_SHORTNAME = '';

    /** @var ServiceDao */
    private $dao;

    /** @var string[] */
    private $list_of_core_services = array(
        self::CUSTOM_SERVICE_SHORTNAME,
        Service::SUMMARY,
        Service::ADMIN,
        Service::HOMEPAGE,
        Service::FORUM,
        Service::ML,
        Service::NEWS,
        Service::CVS,
        Service::FILE,
        Service::SVN,
        Service::WIKI,
        Service::TRACKERV3,
    );

    private $list_of_services_per_project = array();

    /** @var ServiceManager */
    private static $instance;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    private function __construct(ServiceDao $dao, ProjectManager $project_manager)
    {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
    }

    /**
     * ServiceManager is a singleton
     * @return ServiceManager
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            $c = self::class;
            self::$instance = new $c(new ServiceDao(), ProjectManager::instance());
        }
        return self::$instance;
    }

    /**
     * Only for testing purpose
     *
     * @param ServiceManager $service_manager
     */
    public function setInstance(ServiceManager $service_manager)
    {
        self::$instance = $service_manager;
    }

    /**
     * Only for testing purpose
     */
    public function clearInstance()
    {
        self::$instance = null;
    }

    /**
     * @return Service[]
     */
    public function getListOfAllowedServicesForProject(Project $project)
    {
        if (! isset($this->list_of_services_per_project[$project->getID()])) {
            $this->list_of_services_per_project[$project->getID()] = array();
            $allowed_services_dar = $this->dao->searchByProjectIdAndShortNames(
                $project->getID(),
                array_merge(
                    $this->list_of_core_services,
                    $this->getListOfPluginBasedServices($project)
                )
            );

            foreach ($allowed_services_dar as $row) {
                try {
                    $service = $this->instantiateFromRow($project, $row);
                    $this->list_of_services_per_project[$project->getID()][$row['service_id']] = $service;
                } catch (ServiceNotAllowedForProjectException $e) {
                    //don't display the row for this servce
                }
            }
        }

        return $this->list_of_services_per_project[$project->getID()];
    }

    private function getListOfPluginBasedServices(Project $project)
    {
        $services = array();
        EventManager::instance()->processEvent(Event::SERVICES_ALLOWED_FOR_PROJECT, array('project' => $project, 'services' => &$services));
        return $services;
    }

    public function isServiceAllowedForProject(Project $project, $service_id)
    {
        $list_of_allowed_services = $this->getListOfAllowedServicesForProject($project);

        return isset($list_of_allowed_services[$service_id]);
    }

    public function isServiceAvailableAtSiteLevelByShortName($name)
    {
        return $this->dao->isServiceAvailableAtSiteLevelByShortName($name);
    }

    private function isServiceActiveInProject($project, $name)
    {
        $project_id = $project->getId();
        return $this->dao->isServiceActiveInProjectByShortName($project_id, $name);
    }

    public function toggleServiceUsage(Project $project, $short_name, $is_used)
    {
        if ($this->isServiceAvailableAtSiteLevelByShortName($short_name) || $this->isServiceActiveInProject($project, $short_name)) {
            if ($this->doesServiceUsageChange($project, $short_name, $is_used)) {
                $this->updateServiceUsage($project, $short_name, $is_used);
            }
        }
    }

    private function updateServiceUsage(Project $project, $short_name, $is_used)
    {
        $this->dao->updateServiceUsageByShortName($project->getID(), $short_name, $is_used);
        ProjectManager::instance()->clearProjectFromCache($project->getID());

        $reference_manager = ReferenceManager::instance();
        $reference_manager->updateReferenceForService($project->getID(), $short_name, ($is_used ? "1" : "0"));

        $event_manager = EventManager::instance();
        $event_manager->processEvent(
            Event::SERVICE_IS_USED,
            array(
                'shortname' => $short_name,
                'is_used'   => $is_used ? true:false,
                'group_id'  => $project->getID(),
            )
        );
    }

    /**
     * @throws ServiceCannotBeUpdatedException
     */
    public function checkServiceCanBeUpdated(Project $project, $short_name, $is_used)
    {
        if ($short_name === 'admin' && ! $is_used) {
            throw new ServiceCannotBeUpdatedException(_('Admin service cannot be disabled.'));
        }

        if (! $this->doesServiceUsageChange($project, $short_name, $is_used) && $is_used) {
            return;
        }

        $event = new ProjectServiceBeforeActivation($project, $short_name);
        EventManager::instance()->processEvent($event);

        if ($event->doesPluginSetAValue() && ! $event->canServiceBeActivated()) {
            throw new ServiceCannotBeUpdatedException($event->getWarningMessage());
        }
    }

    /**
     * @return bool
     */
    private function doesServiceUsageChange(Project $project, $short_name, $is_used)
    {
        $previous_is_used = $project->getService($short_name);

        return $previous_is_used != $is_used;
    }

    /**
     * @return Service
     * @throws ServiceNotAllowedForProjectException
     */
    public function getService(int $id)
    {
        $row = $this->dao->searchById($id)->getRow();
        if (! $row) {
            throw new ServiceNotFoundException();
        }

        $project = $this->project_manager->getProject($row['group_id']);
        if ($project->isError()) {
            throw new ServiceNotFoundException();
        }

        return $this->instantiateFromRow($project, $row);
    }

    /**
     * @return Service
     */
    private function instantiateFromRow(Project $project, array $row)
    {
        $classname = $project->getServiceClassName($row['short_name']);

        return new $classname($project, $row);
    }
}
