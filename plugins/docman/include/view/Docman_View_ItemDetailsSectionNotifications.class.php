<?php
/**
 * Copyright (c) Enalean, 2013-201*. All rights reserved
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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

use Tuleap\Docman\Notifications\CollectionOfUgroupMonitoredItemsBuilder;
use Tuleap\Docman\Notifications\NotificationListPresenter;

require_once('Docman_View_ItemDetailsSection.class.php');

class Docman_View_ItemDetailsSectionNotifications extends Docman_View_ItemDetailsSection
{
    /**
     * @var Docman_NotificationsManager
     */
    var $notificationsManager;
    var $token;

    /**
     * @var Tuleap\Docman\Notifications\CollectionOfUgroupMonitoredItemsBuilder
     */
    private $ugroups_to_be_notified_builder;

    function __construct(
        $item,
        $url,
        $notificationsManager,
        $token,
        CollectionOfUgroupMonitoredItemsBuilder $ugroups_to_be_notified_builder
    ) {
        parent::__construct(
            $item,
            $url,
            'notifications',
            $GLOBALS['Language']->getText('plugin_docman', 'details_notifications')
        );
        $this->notificationsManager           = $notificationsManager;
        $this->token                          = $token;
        $this->ugroups_to_be_notified_builder = $ugroups_to_be_notified_builder;
    }
    function getContent($params = [])
    {
        $content = '<dl><fieldset><legend>'. $GLOBALS['Language']->getText('plugin_docman', 'details_notifications') .'</legend>';
        $content .= '<dd>';
        $content .= '<form action="" method="POST">';
        $content .= '<p>';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="'. $this->token .'" />';
        }
        $content .= '<input type="hidden" name="action" value="monitor" />';
        $content .= '<input type="hidden" name="id" value="'. $this->item->getId() .'" />';
        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        $checked  = !$user->isAnonymous() && $this->notificationsManager->userExists($user->getId(), $this->item->getId()) ? 'checked="checked"' : '';
        $disabled = $user->isAnonymous() ? 'disabled="disabled"' : '';
        $content .= '<input type="hidden" name="monitor" value="0" />';
        $content .= '<label class="checkbox" for="plugin_docman_monitor_item">';
        $content .= '<input type="checkbox" name="monitor" value="1" id="plugin_docman_monitor_item" '. $checked .' '. $disabled .' />'. $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_sendemail');
        $content .= '</label></p>';
        $content .= $this->item->accept($this, array('user' => &$user));
        $content .= '<p><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></p>';
        $content .= '</form>';
        $content .= '</dd></fieldset></dl>';
        $content .= '<dl>'.$this->displayListeningUsers($this->item->getId()).'</dl>';
        return $content;
    }

    /**
     * Show list of people monitoring the document directly or indirectly by monitoring one of the parents and its subitems
     *
     * @param int $itemId Id of the document
     *
     * @return String
     */
    private function displayListeningUsers($itemId)
    {
        $dpm        = Docman_PermissionsManager::instance($this->item->getGroupId());
        $um         = UserManager::instance();
        $purifier   = Codendi_HTMLPurifier::instance();
        $content    = '';
        if ($dpm->userCanManage($um->getCurrentUser(), $itemId)) {
            $users   = $this->notificationsManager->getListeningUsers($this->item);
            $ugroups = $this->ugroups_to_be_notified_builder->getCollectionOfUgroupMonitoredItems($this->item);

            $content .= '<fieldset><legend>'. $purifier->purify($GLOBALS['Language']->getText('plugin_docman', 'details_listeners')) .'</legend>';

            $renderer = TemplateRendererFactory::build()->getRenderer(
                dirname(PLUGIN_DOCMAN_BASE_DIR) . '/templates'
            );
            $content .= $renderer->renderToString(
                'item-details-notifications',
                new NotificationListPresenter($users, $ugroups, $this->item)
            );

            $content .= '</fieldset>';
            $GLOBALS['Response']->includeFooterJavascriptFile('/scripts/tuleap/user-and-ugroup-autocompleter.js');
        }
        return $content;
    }

    function visitEmpty(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }
    function visitWiki(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }
    function visitEmbeddedFile(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }
    function visitDocument(&$item, $params)
    {
        return '';
    }
    function visitFolder(&$item, $params)
    {
        $content = '<blockquote>';
        $checked  = !$params['user']->isAnonymous() && $this->notificationsManager->userExists($params['user']->getId(), $this->item->getId(), PLUGIN_DOCMAN_NOTIFICATION_CASCADE) ? 'checked="checked"' : '';
        $disabled = $params['user']->isAnonymous() ? 'disabled="disabled"' : '';
        $content .= '<input type="hidden" name="cascade" value="0" />';
        $content .= '<label for="plugin_docman_monitor_cascade_item" class="checkbox">';
        $content .= '<input type="checkbox" name="cascade" value="1" id="plugin_docman_monitor_cascade_item" '. $checked .' '. $disabled .' />';
        $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_cascade_sendemail') .'</label>';
        $content .= '</blockquote>';
        return $content;
    }
}
