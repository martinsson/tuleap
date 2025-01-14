<?php
/**
 * Copyright (c) Enalean SAS 2014 - Present. All Rights Reserved.
 * Copyright 2000-2011, Fusionforge Team
 * Copyright 2012, Franck Villaume - TrivialDev
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Mediawiki\Events\SystemEvent_MEDIAWIKI_TO_CENTRAL_DB;
use Tuleap\Mediawiki\ForgeUserGroupPermission\MediawikiAdminAllProjects;
use Tuleap\Mediawiki\Maintenance\CleanUnused;
use Tuleap\Mediawiki\Maintenance\CleanUnusedDao;
use Tuleap\Mediawiki\Migration\MoveToCentralDbDao;
use Tuleap\Mediawiki\PermissionsPerGroup\PermissionPerGroupPaneBuilder;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\ProjectUGroup\UserAndProjectUGroupRelationshipEvent;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesForumAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesNewsAdministrator;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesNewsWriter;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesWikiAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerForumAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerNewsAdministrator;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerNewsWriter;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerWikiAdmin;
use Tuleap\MediaWiki\MediawikiMaintenanceWrapper;
use Tuleap\MediaWiki\XMLMediaWikiExporter;
use Tuleap\Project\DelegatedUserAccessForProject;
use Tuleap\Request\RestrictedUsersAreHandledByPluginEvent;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

class MediaWikiPlugin extends Plugin
{

    public const SERVICE_SHORTNAME = 'plugin_mediawiki';

    public function __construct($id = 0)
    {
        parent::__construct($id);
        $this->setName("mediawiki");
        $this->text = "Mediawiki" ; // To show in the tabs, use...
        $this->addHook('cssfile');
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);

        $this->addHook('permission_get_name');
        $this->addHook(Event::SERVICE_IS_USED);
        $this->addHook(Event::REGISTER_PROJECT_CREATION);

        $this->addHook(Event::SERVICE_REPLACE_TEMPLATE_NAME_IN_LINK);
        $this->addHook(Event::RENAME_PROJECT, 'rename_project');
        $this->addHook('project_is_deleted');

        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass');
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);

        //User permissions
        $this->addHook('project_admin_remove_user');
        $this->addHook('project_admin_change_user_permissions');
        $this->addHook('SystemEvent_USER_RENAME', 'systemevent_user_rename');
        $this->addHook('project_admin_ugroup_remove_user');
        $this->addHook('project_admin_remove_user_from_project_ugroups');
        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook(DelegatedUserAccessForProject::NAME);
        $this->addHook(RestrictedUsersAreHandledByPluginEvent::NAME);
        $this->addHook(Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED);

        // Search
        $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
        $this->addHook(Event::SEARCH_TYPES_PRESENTERS);
        $this->addHook(Event::SEARCH_TYPE);

        $this->addHook('plugin_statistics_service_usage');

        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::GET_PROJECTID_FROM_URL);

        // Stats plugin
        $this->addHook('plugin_statistics_disk_usage_collect_project');
        $this->addHook('plugin_statistics_disk_usage_service_label');
        $this->addHook('plugin_statistics_color');

        // Site admin link
        $this->addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);

        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);

        $this->addHook(Event::IMPORT_XML_PROJECT, 'importXmlProject', false);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION);
        $this->addHook(NavigationDropdownQuickLinksCollector::NAME);
        $this->addHook(UserBecomesProjectAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerProjectAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserBecomesWikiAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerWikiAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserBecomesForumAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerForumAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserBecomesNewsWriter::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerNewsWriter::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserBecomesNewsAdministrator::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerNewsAdministrator::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(Event::EXPORT_XML_PROJECT);

        $this->addHook(PermissionPerGroupPaneCollector::NAME);

        /**
         * HACK
         */
        require_once MEDIAWIKI_BASE_DIR . '/../fusionforge/compat/load_compatibilities_method.php';

        bindtextdomain('tuleap-mediawiki', __DIR__ . '/../site-content');
    }

    public function getServiceShortname()
    {
        return self::SERVICE_SHORTNAME;
    }

    public function burning_parrot_get_stylesheets($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/mediawiki') === 0) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    public function burning_parrot_get_javascript_files($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/mediawiki') === 0) {
            $params['javascript_files'][] = '/scripts/tuleap/manage-allowed-projects-on-resource.js';
        }
    }

    private function getMediaWikiDataDir()
    {
        return forge_get_config('mwdata_path', 'mediawiki');
    }

    public function export_xml_project($params)
    {
        if (! isset($params['options']['all']) || $params['options']['all'] === false) {
            return;
        }

        $this->getMediaWikiExporter($params['project']->getID())->exportToXml(
            $params['into_xml'],
            $params['archive'],
            'export_mw_' . $params['project']->getID() . time() . '.xml',
            $params['temporary_dump_path_on_filesystem'],
            $this->getMediaWikiDataDir()
        );
    }

    private function getMediaWikiExporter($group_id)
    {
        $sys_command = new System_Command();
        return new XMLMediaWikiExporter(
            $sys_command,
            ProjectManager::instance()->getProject($group_id),
            new MediawikiManager(new MediawikiDao()),
            new UGroupManager(),
            new ProjectXMLExporterLogger(),
            new MediawikiMaintenanceWrapper($sys_command),
            new MediawikiLanguageManager(new MediawikiLanguageDao())
        );
    }

    public function layout_search_entry($params)
    {
        $project = $this->getProjectFromRequest();
        if ($this->isSearchEntryAvailable($project)) {
            $params['search_entries'][] = array(
                'value'    => $this->getName(),
                'label'    => $this->text,
                'selected' => $this->isSearchEntrySelected($params['type_of_search']),
            );
            $params['hidden_fields'][] = array(
                'name'  => 'group_id',
                'value' => $project->getID()
            );
        }
    }

        /**
         * @see Event::SEARCH_TYPE
         */
    public function search_type($params)
    {
        $query   = $params['query'];
        $project = $query->getProject();

        if ($query->getTypeOfSearch() == $this->getName() && $this->isSearchEntryAvailable($project)) {
            if (! $project->isError()) {
                util_return_to($this->getMediawikiSearchURI($project, $query->getWords()));
            }
        }
    }

        /**
         * @see Event::SEARCH_TYPES_PRESENTERS
         */
    public function search_types_presenters($params)
    {
        if ($this->isSearchEntryAvailable($params['project'])) {
            $params['project_presenters'][] = new Search_SearchTypePresenter(
                $this->getName(),
                $this->text,
                array(),
                $this->getMediawikiSearchURI($params['project'], $params['words'])
            );
        }
    }

    /**
     * @see Event::PROCCESS_SYSTEM_CHECK
     */
    public function proccess_system_check($params)
    {
        $this->getMediawikiMLEBExtensionManager()->activateMLEBForCompatibleProjects($params['logger']);
    }

    private function getMediawikiSearchURI(Project $project, $words)
    {
        return $this->getPluginPath().'/wiki/'. $project->getUnixName() .'/index.php?title=Special%3ASearch&search=' . urlencode($words) . '&go=Go';
    }

    private function isSearchEntryAvailable(?Project $project = null)
    {
        if ($project && ! $project->isError()) {
            return $project->usesService(self::SERVICE_SHORTNAME);
        }
        return false;
    }

    private function isSearchEntrySelected($type_of_search)
    {
        return ($type_of_search == $this->getName()) || $this->isMediawikiUrl();
    }

    private function isMediawikiUrl()
    {
        return preg_match('%'.$this->getPluginPath().'/wiki/.*%', $_SERVER['REQUEST_URI']);
    }

        /**
         *
         * @return Project | null
         */
    private function getProjectFromRequest()
    {
        $matches = array();
        preg_match('%'.$this->getPluginPath().'/wiki/([^/]+)/.*%', $_SERVER['REQUEST_URI'], $matches);
        if (isset($matches[1])) {
            $project = ProjectManager::instance()->getProjectByUnixName($matches[1]);

            if ($project->isError()) {
                $project = ProjectManager::instance()->getProject($matches[1]);
            }

            if (! $project->isError()) {
                return $project;
            }
        }
        return null;
    }

    public function cssFile($params)
    {
        // Only show the stylesheet if we're actually in the Mediawiki pages.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="/plugins/mediawiki/themes/default/css/style.css" />';
        }
    }

    public function showImage(Codendi_Request $request)
    {
        $project = $this->getProjectFromRequest();
        $user    = $request->getCurrentUser();

        if (! $project) {
            exit;
        }

        if ((! $project->isPublic() || $user->isRestricted())
            && ! $project->userIsMember()
            && ! $user->isSuperUser()
            && ! $this->doesUserHavePermission($user)
            && ! $this->getMediawikiManager()->userCanRead($user, $project)
        ) {
            exit;
        }

        preg_match('%'.$this->getPluginPath().'/wiki/[^/]+/images(.*)%', $_SERVER['REQUEST_URI'], $matches);
        $file_location = $matches[1];

        $folder_location = '';
        if (is_dir('/var/lib/tuleap/mediawiki/projects/' . $project->getUnixName())) {
            $folder_location = '/var/lib/tuleap/mediawiki/projects/' . $project->getUnixName().'/images';
        } elseif (is_dir('/var/lib/tuleap/mediawiki/projects/' . $project->getId())) {
            $folder_location = '/var/lib/tuleap/mediawiki/projects/' . $project->getId().'/images';
        } else {
            exit;
        }

        $file = $folder_location.$file_location;
        if (! file_exists($file)) {
            exit;
        }

        $size = getimagesize($file);
        $fp   = fopen($file, 'r');

        if ($size and $fp) {
            header('Content-Type: '.$size['mime']);
            header('Content-Length: '.filesize($file));

            readfile($file);
            exit;
        }
    }

    function process()
    {
        echo '<h1>Mediawiki</h1>';
        echo $this->getPluginInfo()->getpropVal('answer');
    }

    function &getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'MediaWikiPluginInfo')) {
            $this->pluginInfo = new MediaWikiPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function service_replace_template_name_in_link($params)
    {
        $params['link'] = preg_replace(
            '#/plugins/mediawiki/wiki/'.preg_quote($params['template']['name'], '#').'(/|$)#',
            '/plugins/mediawiki/wiki/'. $params['project']->getUnixName().'$1',
            $params['link']
        );
    }


    public function register_project_creation($params)
    {
        if ($this->serviceIsUsedInTemplate($params['template_id'])) {
            $mediawiki_instantiater = $this->getInstantiater($params['group_id']);
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiateFromTemplate($params['ugroupsMapping']);
            }
        } elseif ($this->serviceIsUsedInTemplate($params['group_id'])) {
            $mediawiki_instantiater = $this->getInstantiater($params['group_id']);
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiate();
            }
        }
    }

    public function has_user_been_delegated_access(DelegatedUserAccessForProject $event) : void
    {
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 &&
                $this->doesUserHavePermission($event->getUser())) {
            $event->enableAccessToProjectToTheUser();
        }
    }

    private function doesUserHavePermission(PFUser $user)
    {
        $forge_user_manager = $this->getForgeUserGroupPermissionsManager();

        return $forge_user_manager->doesUserHavePermission(
            $user,
            new MediawikiAdminAllProjects()
        );
    }

    /**
     * @return User_ForgeUserGroupPermissionsManager
     */
    private function getForgeUserGroupPermissionsManager()
    {
        return new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    public function restrictedUsersAreHandledByPluginEvent(RestrictedUsersAreHandledByPluginEvent $event)
    {
        if (strpos($event->getUri(), $this->getPluginPath()) === 0) {
            $event->setPluginHandleRestricted();
        }
    }

    /**
     * @see Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED
     */
    public function get_services_allowed_for_restricted($params)
    {
        $params['allowed_services'][] = $this->getServiceShortname();
    }

    private function serviceIsUsedInTemplate($project_id)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($project_id);

        return $project->usesService(self::SERVICE_SHORTNAME);
    }

    public function service_is_used($params)
    {
        if ($params['shortname'] == 'plugin_mediawiki' && $params['is_used']) {
            $mediawiki_instantiater = $this->getInstantiater($params['group_id']);
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiate();
            }
        }
    }

    private function getInstantiater($group_id)
    {
        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProject($group_id);

        if (! $project instanceof Project || $project->isError()) {
            return;
        }

        return new MediaWikiInstantiater(
            $project,
            $this->getMediawikiManager(),
            $this->getMediawikiLanguageManager(),
            $this->getMediawikiVersionManager(),
            $this->getMediawikiMLEBExtensionManager()
        );
    }

    private function getMediawikiLanguageManager()
    {
        return new MediawikiLanguageManager(new MediawikiLanguageDao());
    }

    public function plugin_statistics_service_usage($params)
    {
        $dao             = $this->getDao();
        $project_manager = ProjectManager::instance();
        $start_date      = $params['start_date'];
        $end_date        = $params['end_date'];

        $number_of_page                   = array();
        $number_of_page_between_two_dates = array();
        $number_of_page_since_a_date      = array();
        foreach ($project_manager->getProjectsByStatus(Project::STATUS_ACTIVE) as $project) {
            if ($project->usesService('plugin_mediawiki') && $dao->hasDatabase($project)) {
                $number_of_page[] = $dao->getMediawikiPagesNumberOfAProject($project);
                $number_of_page_between_two_dates[] = $dao->getModifiedMediawikiPagesNumberOfAProjectBetweenStartDateAndEndDate($project, $start_date, $end_date);
                $number_of_page_since_a_date[] = $dao->getCreatedPagesNumberSinceStartDate($project, $start_date);
            }
        }

        $params['csv_exporter']->buildDatas($number_of_page, "Mediawiki Pages");
        $params['csv_exporter']->buildDatas($number_of_page_between_two_dates, "Modified Mediawiki pages");
        $params['csv_exporter']->buildDatas($number_of_page_since_a_date, "Number of created Mediawiki pages since start date");
    }

    public function project_admin_ugroup_deletion($params)
    {
        $project = $this->getProjectFromParams($params);
        $dao     = $this->getDao();

        if ($project->usesService(MediaWikiPlugin::SERVICE_SHORTNAME)) {
            $dao->deleteUserGroup($project->getID(), $params['ugroup_id']);
            $dao->resetUserGroups($project);
        }
    }

    public function project_admin_remove_user($params)
    {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_ugroup_remove_user($params)
    {
        $this->updateUserGroupMapping($params);
    }

    public function updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent(UserAndProjectUGroupRelationshipEvent $event)
    {
        $this->updateUserGroupMapping(
            array(
                'user_id'  => $event->getUser()->getId(),
                'group_id' => $event->getProject()->getID(),
            )
        );
    }

    public function project_admin_change_user_permissions($params)
    {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_remove_user_from_project_ugroups($params)
    {
        $this->updateUserGroupMapping($params);
    }

    private function updateUserGroupMapping($params)
    {
        $user    = $this->getUserFromParams($params);
        $project = $this->getProjectFromParams($params);
        $dao     = $this->getDao();

        if ($project->usesService(MediaWikiPlugin::SERVICE_SHORTNAME)) {
            $dao->resetUserGroupsForUser($user, $project);
        }
    }

    public function systemevent_user_rename($params)
    {
        $user            = $params['user'];
        $projects        = ProjectManager::instance()->getAllProjectsButDeleted();
        foreach ($projects as $project) {
            if ($project->usesService(MediaWikiPlugin::SERVICE_SHORTNAME)) {
                $this->getDao()->renameUser($project, $params['old_user_name'], $user->getUnixName());
            }
        }
    }

    private function getUserFromParams($params)
    {
        $user_id  = $params['user_id'];

        return UserManager::instance()->getUserById($user_id);
    }

    private function getProjectFromParams($params)
    {
        $group_id = $params['group_id'];

        return ProjectManager::instance()->getProject($group_id);
    }

    private function getDao()
    {
        return new MediawikiDao($this->getCentralDatabaseNameProperty());
    }

    private function getCentralDatabaseNameProperty()
    {
        return trim($this->getPluginInfo()->getPropVal('central_database'));
    }

    /**
     * @return MediawikiManager
     */
    public function getMediawikiManager()
    {
        return new MediawikiManager($this->getDao());
    }

    public function service_classnames(array &$params)
    {
        $params['classnames'][$this->getServiceShortname()] = ServiceMediawiki::class;
    }

    public function rename_project($params)
    {
        $project         = $params['project'];
        $project_manager = ProjectManager::instance();
        $new_link        = '/plugins/mediawiki/wiki/'. $params['new_name'];

        if (! $project_manager->renameProjectPluginServiceLink($project->getID(), self::SERVICE_SHORTNAME, $new_link)) {
            $params['success'] = false;
            return;
        }

        $this->updateMediawikiDirectory($project);
        $this->clearMediawikiCache($project);
    }

    private function updateMediawikiDirectory(Project $project)
    {
        $logger         = new BackendLogger();
        $project_id_dir = forge_get_config('projects_path', 'mediawiki') . "/". $project->getID() ;

        if (is_dir($project_id_dir)) {
            return true;
        }

        $project_name_dir = forge_get_config('projects_path', 'mediawiki') . "/" . $project->getUnixName();
        if (is_dir($project_name_dir)) {
            exec("mv $project_name_dir $project_id_dir");
            return true;
        }

        $logger->error('Project Rename: Can\'t find mediawiki directory for project: '.$project->getID());
        return false;
    }

    private function clearMediawikiCache(Project $project)
    {
        $logger = $this->getBackendLogger();

        $delete = $this->getDao()->clearPageCacheForProject($project);
        if (! $delete) {
            $logger->error('Project Clear cache: Can\'t delete mediawiki cache for schema: '.$project->getID());
        }
    }

    public function get_projectid_from_url($params)
    {
        $url = $params['url'];

        if (strpos($url, '/plugins/mediawiki/wiki/') === 0) {
            $pieces       = explode("/", $url);
            $project_name = $pieces[4];

            $dao          = $params['project_dao'];
            $dao_results  = $dao->searchByUnixGroupName($project_name);
            if ($dao_results->rowCount() < 1) {
                // project does not exist
                return false;
            }

            $project_data         = $dao_results->getRow();
            $params['project_id'] = $project_data['group_id'];
        }
    }

    public function plugin_statistics_disk_usage_collect_project($params)
    {
        $start   = microtime(true);
        $row     = $params['project_row'];
        $project = $params['project'];

        $project_for_parth = $this->getMediawikiManager()->instanceUsesProjectID($project) ?
            $row['group_id'] : $row['unix_group_name'];

        $path = $GLOBALS['sys_data_dir']. '/mediawiki/projects/'. $project_for_parth;

        $size = $params['DiskUsageManager']->getDirSize($path);

        $params['DiskUsageManager']->_getDao()->addGroup(
            $row['group_id'],
            self::SERVICE_SHORTNAME,
            $size,
            $_SERVER['REQUEST_TIME']
        );

        $end  = microtime(true);
        $time = $end - $start;

        if (! isset($params['time_to_collect'][self::SERVICE_SHORTNAME])) {
            $params['time_to_collect'][self::SERVICE_SHORTNAME] = 0;
        }

        $params['time_to_collect'][self::SERVICE_SHORTNAME] += $time;
    }

    public function plugin_statistics_disk_usage_service_label($params)
    {
        $params['services'][self::SERVICE_SHORTNAME] = 'Mediawiki';
    }

    public function plugin_statistics_color($params)
    {
        if ($params['service'] == self::SERVICE_SHORTNAME) {
            $params['color'] = 'lightsalmon';
        }
    }

    public function site_admin_option_hook($params)
    {
        $params['plugins'][] = array(
            'label' => 'Mediawiki',
            'href'  => $this->getPluginPath() . '/forge_admin.php?action=site_index'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath().'/forge_admin.php?action=site_index') === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function system_event_get_types_for_default_queue(array &$params)
    {
        $params['types'] = array_merge($params['types'], array(
            SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME,
            SystemEvent_MEDIAWIKI_TO_CENTRAL_DB::NAME
        ));
    }

    public function getSystemEventClass($params)
    {
        switch ($params['type']) {
            case SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME:
                $params['class'] = 'SystemEvent_MEDIAWIKI_SWITCH_TO_123';
                $params['dependencies'] = array(
                    $this->getMediawikiMigrator(),
                    $this->getProjectManager(),
                    $this->getMediawikiVersionManager(),
                    $this->getMediawikiMLEBExtensionManager(),
                    new MediawikiSiteAdminResourceRestrictor(
                        new MediawikiSiteAdminResourceRestrictorDao(),
                        $this->getProjectManager()
                    )
                );
                break;
            case SystemEvent_MEDIAWIKI_TO_CENTRAL_DB::NAME:
                $params['class'] = 'Tuleap\Mediawiki\Events\SystemEvent_MEDIAWIKI_TO_CENTRAL_DB';
                $params['dependencies'] = array(
                    new MoveToCentralDbDao($this->getCentralDatabaseNameProperty()),
                );
                break;

            default:
                break;
        }
    }

    private function getMediawikiMigrator()
    {
        return new Mediawiki_Migration_MediawikiMigrator();
    }

    private function getProjectManager()
    {
        return ProjectManager::instance();
    }

    public function permission_get_name($params)
    {
        if (!$params['name']) {
            switch ($params['permission_type']) {
                case MediawikiManager::READ_ACCESS:
                    $params['name'] = 'Read';
                    break;
                case MediawikiManager::WRITE_ACCESS:
                    $params['name'] = 'Write';
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @see Event::PROJECT_ACCESS_CHANGE
     */
    public function projectAccessChange($params): void
    {
        $project = ProjectManager::instance()->getProject($params['project_id']);

        $this->getMediawikiManager()->updateAccessControlInProjectChangeContext(
            $project,
            $params['old_access'],
            $params['access']
        );
    }

    /**
     * @see Event::SITE_ACCESS_CHANGE
     */
    public function site_access_change($params)
    {
        $this->getMediawikiManager()->updateSiteAccess($params['old_value']);
    }

    private function getMediawikiVersionManager()
    {
        return new MediawikiVersionManager(new MediawikiVersionDao());
    }

    /**
     * @return MediawikiMLEBExtensionManager
     */
    private function getMediawikiMLEBExtensionManager()
    {
        return new MediawikiMLEBExtensionManager(
            $this->getMediawikiMigrator(),
            new \Tuleap\Mediawiki\MediawikiExtensionDAO(),
            $this->getProjectManager(),
            $this->getMediawikiVersionManager(),
            $this->getMediawikiLanguageManager()
        );
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function importXmlProject($params)
    {
        $importer = new MediaWikiXMLImporter(
            $params['logger'],
            $this->getMediawikiManager(),
            $this->getMediawikiLanguageManager(),
            new UGroupManager(),
            EventManager::instance()
        );
        $importer->import($params['configuration'], $params['project'], UserManager::instance()->getCurrentUser(), $params['xml_content'], $params['extraction_path']);
    }

    public function get_permission_delegation($params)
    {
        $permission = new MediawikiAdminAllProjects();

        $params['plugins_permission'][MediawikiAdminAllProjects::ID] = $permission;
    }

    public function getCleanUnused(Logger $logger)
    {
        return new CleanUnused(
            $logger,
            new CleanUnusedDao(
                $logger,
                $this->getCentralDatabaseNameProperty()
            ),
            ProjectManager::instance(),
            Backend::instance('System'),
            $this->getDao()
        );
    }

    public function project_is_deleted(array $params)
    {
        if (! empty($params['group_id'])) {
            $clean_unused = $this->getCleanUnused($this->getBackendLogger());
            $clean_unused->purgeProject($params['group_id']);
        }
    }

    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(NavigationDropdownQuickLinksCollector $quick_links_collector)
    {
        $project = $quick_links_collector->getProject();

        if (! $project->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                $GLOBALS['Language']->getText('plugin_mediawiki', 'service_lbl_key'),
                $this->getPluginPath() . '/forge_admin.php?' . http_build_query(
                    array(
                        'group_id' => $project->getID(),
                        'pane'     => 'permissions'
                    )
                )
            )
        );
    }

    /**
     * @param PermissionPerGroupPaneCollector $event
     */
    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        if (! $event->getProject()->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $ugroup_manager = new UGroupManager();

        $builder   = new PermissionPerGroupPaneBuilder(
            $this->getMediawikiManager(),
            $ugroup_manager,
            new PermissionPerGroupUGroupFormatter($ugroup_manager),
            new MediawikiUserGroupsMapper($this->getDao(), new User_ForgeUserGroupPermissionsDao())
        );
        $presenter = $builder->buildPresenter($event);

        $templates_dir = ForgeConfig::get('tuleap_dir') . '/plugins/mediawiki/templates/';
        $content       = TemplateRendererFactory::build()
            ->getRenderer($templates_dir)
            ->renderToString('project-admin-permission-per-group', $presenter);

        $project = $event->getProject();
        $service = $project->getService($this->getServiceShortname());
        if ($service !== null) {
            $rank_in_project = $service->getRank();
            $event->addPane($content, $rank_in_project);
        }
    }
}
