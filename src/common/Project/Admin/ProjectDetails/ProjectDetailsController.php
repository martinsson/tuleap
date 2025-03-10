<?php
/**
 * Copyright Enalean (c) 2017 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\ProjectDetails;

require_once __DIR__ . '/../../../../www/project/admin/project_admin_utils.php';

use Codendi_HTMLPurifier;
use CSRFSynchronizerToken;
use DataAccessException;
use EventManager;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use PFUser;
use Project;
use Project_HierarchyManagerAlreadyAncestorException;
use Project_HierarchyManagerAncestorIsSelfException;
use ProjectHistoryDao;
use ProjectManager;
use Rule_ProjectFullName;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\HierarchyDisplayer;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\ProjectVisibilityPresenterBuilder;
use Tuleap\Project\Admin\ProjectVisibilityUserConfigurationPermissions;
use Tuleap\Project\Admin\ServicesUsingTruncatedMailRetriever;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\TroveCat\TroveCatLinkDao;
use UGroupBinding;

class ProjectDetailsController
{
    /**
     * @var DescriptionFieldsFactory
     */
    private $description_fields_factory;

    /**
     * @var Project
     */
    private $current_project;

    /**
     * @var ProjectDetailsDAO
     */
    private $project_details_dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var TroveCatLinkDao
     */
    private $trove_cat_link_dao;

    /**
     * @var ProjectVisibilityUserConfigurationPermissions
     */
    private $project_visibility_configuration;

    /**
     * @var ServicesUsingTruncatedMailRetriever
     */
    private $service_truncated_mails_retriever;

    /**
     * @var UGroupBinding
     */
    private $ugroup_binding;

    /**
     * @var ProjectVisibilityPresenterBuilder
     */
    private $project_visibility_presenter_builder;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        DescriptionFieldsFactory $description_fields_factory,
        Project $current_project,
        ProjectDetailsDAO $project_details_dao,
        ProjectManager $project_manager,
        EventManager $event_manager,
        ProjectHistoryDao $project_history_dao,
        ProjectVisibilityPresenterBuilder $project_visibility_presenter_builder,
        ProjectVisibilityUserConfigurationPermissions $project_visibility_configuration,
        ServicesUsingTruncatedMailRetriever $service_truncated_mails_retriever,
        UGroupBinding $ugroup_binding,
        TroveCatLinkDao $trove_cat_link_dao,
        CSRFSynchronizerToken $csrf_token
    ) {
        $this->description_fields_factory           = $description_fields_factory;
        $this->current_project                      = $current_project;
        $this->project_details_dao                  = $project_details_dao;
        $this->project_manager                      = $project_manager;
        $this->event_manager                        = $event_manager;
        $this->project_history_dao                  = $project_history_dao;
        $this->project_visibility_configuration     = $project_visibility_configuration;
        $this->service_truncated_mails_retriever    = $service_truncated_mails_retriever;
        $this->ugroup_binding                       = $ugroup_binding;
        $this->project_visibility_presenter_builder = $project_visibility_presenter_builder;
        $this->trove_cat_link_dao                   = $trove_cat_link_dao;
        $this->csrf_token                           = $csrf_token;
    }

    public function display(HTTPRequest $request)
    {
        $title        = _('Details');
        $project      = $request->getProject();
        $current_user = $request->getCurrentUser();

        $this->displayHeader($title, $project);

        $template_path = ForgeConfig::get('tuleap_dir') . '/src/templates/project';

        $project_id                        = $project->getID();
        $group_info                        = $this->project_details_dao->searchGroupInfo($project_id);
        $description_field_representations = $this->getDescriptionFieldsRepresentation();
        $parent_project_info               = $this->getParentProjectInfo($current_user, $project);
        $purified_project_children         = $this->buildProjectChildren($current_user, $project);

        $project_trove_categories  = array();
        $are_trove_categories_used = ($GLOBALS['sys_use_trove'] != 0);
        if ($are_trove_categories_used) {
            $project_trove_categories = $this->buildProjectTroveCategories($project);
        }

        $project_can_use_gerrit = new HierarchyDisplayer($project);
        $this->event_manager->processEvent($project_can_use_gerrit);
        $is_hierarchy_shown  = $project_can_use_gerrit->canProjectDisplayHierarchy();

        $hierarchy_presenter = new ProjectHierarchyPresenter(
            $parent_project_info,
            $purified_project_children,
            $is_hierarchy_shown
        );

        $global_visibility_presenter = $this->project_visibility_presenter_builder->build($request);

        $template_project = $this->project_manager->getProject($project->getTemplate());

        $renderer = \TemplateRendererFactory::build()->getRenderer($template_path);
        $renderer->renderToPage(
            'project-details',
            new ProjectDetailsPresenter(
                $project,
                $template_project,
                $group_info,
                $description_field_representations,
                $hierarchy_presenter,
                $global_visibility_presenter,
                $are_trove_categories_used,
                $project_trove_categories,
                $this->getProjectsCreatedFromTemplate($project),
                $this->csrf_token
            )
        );
    }

    public function update(HTTPRequest $request)
    {
        $this->csrf_token->check();
        if (! $this->validateFormData($request)) {
            return;
        }

        try {
            $this->updateCustomProjectFields($request);
            $this->updateParentProject($request);
            $this->updateGroup($request);
            $this->validateChanges($request);
            $this->updateProjectVisibility($request->getCurrentUser(), $request->getProject(), $request);
            $this->updateTruncatedMails($request->getCurrentUser(), $request->getProject(), $request);
        } catch (DataAccessException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _("Update failed"));
        } catch (CannotUpdateProjectHierarchyException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _("Update failed"));
        } catch (Project_HierarchyManagerAncestorIsSelfException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _("A project cannot be its own parent."));
        } catch (Project_HierarchyManagerAlreadyAncestorException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _("These projects are already related."));
        }
    }

    private function displayHeader($title, Project $project)
    {
        $assets_path    = ForgeConfig::get('tuleap_dir') . '/src/www/assets';
        $include_assets = new IncludeAssets($assets_path, '/assets');

        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('project-admin.js'));

        $header_displayer = new HeaderNavigationDisplayer();
        $header_displayer->displayBurningParrotNavigation($title, $project, 'details');
    }

    private function validateFormData(HTTPRequest $request)
    {
        $form_group_name = trim($request->get('form_group_name'));
        $form_shortdesc  = $request->get('form_shortdesc');

        if (! $form_group_name || ! $form_shortdesc) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Missing Information. PLEASE fill in all required information.'));

            return false;
        }

        $rule = new Rule_ProjectFullName();

        if (!$rule->isValid($form_group_name)) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $rule->getErrorMessage());

            return false;
        }

        $description_fields = $this->description_fields_factory->getAllDescriptionFields();

        for ($i = 0; $i < sizeof($description_fields); $i++) {
            $current_form = trim($request->get("form_".$description_fields[$i]["group_desc_id"]));

            if (($description_fields[$i]['desc_required'] == 1) && (! $current_form)) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Missing Information. PLEASE fill in all required information.'));

                return false;
            }
        }

        return true;
    }

    private function updateCustomProjectFields(HTTPRequest $request)
    {
        $group_id                  = $request->get('group_id');
        $description_fields_values = $this->reindexRowsByDescriptionId(
            $this->current_project->getProjectsDescFieldsValue()
        );

        $description_fields = $this->reindexRowsByDescriptionId(
            $this->description_fields_factory->getAllDescriptionFields()
        );

        $previous_values = array();

        foreach ($description_fields as $description_field_id => $description_field) {
            $current_form = trim($request->get("form_" . $description_field_id));

            if (array_key_exists($description_field_id, $description_fields_values)) {
                $previous_values[$description_field_id] = $description_fields_values[$description_field_id]['value'];
            }

            if ($current_form != '') {
                if (isset($previous_values[$description_field_id]) && ($previous_values[$description_field_id] != $current_form)) {
                    $this->project_details_dao->updateGroupDescription($group_id, $description_field_id, $current_form);
                } elseif (! isset($previous_values[$description_field_id])) {
                    $this->project_details_dao->createGroupDescription($group_id, $description_field_id, $current_form);
                }
            } elseif (isset($previous_values[$description_field_id])) {
                $this->project_details_dao->deleteDescriptionForGroup($group_id, $description_field_id);
            }
        }
    }

    /**
     * @throws CannotUpdateProjectHierarchyException
     */
    private function updateParentProject(HTTPRequest $request)
    {
        $current_user = $request->getCurrentUser();
        $group_id     = $request->get('group_id');

        if ($request->existAndNonEmpty('parent_project')) {
            $parent_project = $this->project_manager->getProjectFromAutocompleter($request->get('parent_project'));
            if ($parent_project && $parent_project->isActive() && $current_user->isMember($parent_project->getId(), 'A')) {
                $result = $this->project_manager->setParentProject($group_id, $parent_project->getID());
                if (! $result) {
                    throw new CannotUpdateProjectHierarchyException();
                }
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, _("The given parent project does not exist or you are not its administrator."));
                throw new CannotUpdateProjectHierarchyException();
            }
        }
        if ($request->existAndNonEmpty('remove_parent_project')) {
            $result = $this->project_manager->removeParentProject($group_id);
            if (! $result) {
                throw new CannotUpdateProjectHierarchyException();
            }
        }
    }

    private function validateChanges(HTTPRequest $request)
    {
        $group_id = $request->get('group_id');

        $this->project_history_dao->groupAddHistory(
            'changed_public_info',
            '',
            $group_id
        );

        // Raise an event
        $this->event_manager->processEvent('project_admin_edition', array(
            'group_id' => $group_id
        ));

        $GLOBALS['Response']->addFeedback(Feedback::INFO, _('Update successful'));
    }

    private function getDescriptionFieldsRepresentation()
    {
        $description_fields_representations = array();
        $description_fields                 = $this->reindexRowsByDescriptionId(
            $this->description_fields_factory->getAllDescriptionFields()
        );

        $description_fields_values = $this->reindexRowsByDescriptionId(
            $this->current_project->getProjectsDescFieldsValue()
        );

        foreach ($description_fields as $description_field_id => $description_field) {
            $field_value = '';

            if (array_key_exists($description_field_id, $description_fields_values)) {
                $field_value = $description_fields_values[$description_field_id]['value'];
            }

            $translated_field_description = $this->translateFieldProperty($description_field["desc_description"]);
            $purified_field_description   = Codendi_HTMLPurifier::instance()->purify($translated_field_description, CODENDI_PURIFIER_LIGHT);

            $description_fields_representations[] = array(
                'field_name'                 => "form_" . $description_field["group_desc_id"],
                'field_value'                => $field_value,
                'field_label'                => $this->translateFieldProperty($description_field["desc_name"]),
                'field_description_required' => $description_field["desc_required"],
                'is_field_line_typed'        => $description_field["desc_type"] === 'line',
                'is_field_text_typed'        => $description_field["desc_type"] === 'text',
                'purified_field_description' => $purified_field_description
            );
        }

        return $description_fields_representations;
    }

    private function translateFieldProperty($field_property)
    {
        if (preg_match('/(.*):(.*)/', $field_property, $matches)
            && $GLOBALS['Language']->hasText($matches[1], $matches[2])) {
            return $GLOBALS['Language']->getText($matches[1], $matches[2]);
        }

        return $field_property;
    }

    private function updateGroup(HTTPRequest $request)
    {
        $form_group_name = trim($request->get('form_group_name'));
        $form_shortdesc  = $request->get('form_shortdesc');
        $group_id        = $request->get('group_id');

        // in the database, these all default to '1',
        // so we have to explicity set 0
        $this->project_details_dao->updateGroupNameAndDescription(
            $form_group_name,
            $form_shortdesc,
            $group_id
        );
    }

    private function getParentProjectInfo(PFUser $current_user, Project $project)
    {
        $parent = $this->project_manager->getParentProject($project->getID());

        $parent_project_info = array();

        if (! $parent) {
            return $parent_project_info;
        }

        $parent_project_info['parent_name'] = $parent->getUnconvertedPublicName();

        if ($current_user->isMember($parent->getId(), 'A')) {
            $url = '?group_id=' . urlencode($parent->getID());
        } else {
            $url = '/projects/' . urlencode($parent->getUnixName());
        }

        $parent_project_info['url']    = $url;

        $parent_project_info['is_active']    = $parent->isActive();
        if (!  $parent->isActive()) {
            switch ($parent->getStatus()) {
                case Project::STATUS_SUSPENDED:
                    $parent_project_info['status_label'] = $GLOBALS['Language']->getText('admin_projectlist', 'suspended');
                    $parent_project_info['status_class'] = 'tlp-badge-secondary';
                    break;
                case Project::STATUS_DELETED:
                    $parent_project_info['status_label'] = $GLOBALS['Language']->getText('admin_projectlist', 'deleted');
                    $parent_project_info['status_class'] = 'tlp-badge-danger tlp-badge-outline';
                    break;
            }
        }

        return $parent_project_info;
    }

    private function buildProjectChildren(PFUser $current_user, Project $project)
    {
        $children = $this->project_manager->getChildProjects($project->getID());

        $purifier          = Codendi_HTMLPurifier::instance();
        $children_projects = array();

        foreach ($children as $child) {
            if ($current_user->isMember($child->getId(), 'A')) {
                $url = '?group_id=' . urlencode($child->getID());
            } else {
                $url = '/projects/' . urlencode($child->getUnixName());
            }

            $purified_url  = $purifier->purify($url);
            $purified_name = $purifier->purify($child->getUnconvertedPublicName());
            $children_projects[] = '<a href="' . $purified_url . '">' . $purified_name . '</a>';
        }

        return implode(', ', $children_projects);
    }

    private function reindexRowsByDescriptionId($rows)
    {
        $result = array();
        foreach ($rows as $row) {
            $result[$row['group_desc_id']] = $row;
        }

        return $result;
    }

    private function updateTruncatedMails(PFUser $user, Project $project, HTTPRequest $request)
    {
        if ($this->project_visibility_configuration->canUserConfigureTruncatedMail($user)) {
            $usage = (int) $request->exist('truncated_emails');
            if ($project->getTruncatedEmailsUsage() != $usage) {
                $this->project_manager->setTruncatedEmailsUsage($project, $usage);
            }
        }
    }

    private function updateProjectVisibility(PFUser $user, Project $project, HTTPRequest $request)
    {
        if ($this->project_visibility_configuration->canUserConfigureProjectVisibility($user, $project)) {
            if ($project->getAccess() !== $request->get('project_visibility')) {
                if ($request->get('term_of_service')) {
                    $this->project_manager->setAccess($project, $request->get('project_visibility'));
                    $this->project_manager->clear($project->getID());
                    $this->ugroup_binding->reloadUgroupBindingInProject($project);
                } else {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, _("Please accept term of service"));
                }
            }
        }
    }

    private function buildProjectTroveCategories(Project $project)
    {
        $project_trove_categories = array();
        foreach ($this->trove_cat_link_dao->searchTroveCatForProject($project->getID()) as $row_trovecat) {
            $project_trove_categories[] = array('trove_category_full_path' => $row_trovecat['fullpath']);
        }

        return $project_trove_categories;
    }

    private function getProjectsCreatedFromTemplate(Project $project)
    {
        $projects = array();
        foreach ($project->getProjectsCreatedFrom() as $subproject) {
            $projects[] = array(
                'unix_group_name' => $subproject['unix_group_name'],
                'group_name'      => util_unconvert_htmlspecialchars($subproject['group_name'])
            );
        }
        return $projects;
    }
}
