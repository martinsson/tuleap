<?php
/*
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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

use Tuleap\PHPWiki\WikiPage;

function exit_wiki_empty()
{
    global $HTML;
    global $group_id;

    $pm = ProjectManager::instance();
    $go = $pm->getProject($group_id);
    $uname = $go->getUnixName();

    $HTML->header(array('title'=>$GLOBALS['Language']->getText('wiki_views_wikiviews', 'title_error')));

    print $GLOBALS['Language']->getText('wiki_views_wikiviews', 'not_activate', array($uname));

    $HTML->footer(array());
    exit;
}

/**
 * Generate url to Expend/Collapse a part of a page
 * @see my_hide_url
 */
function hide_url($svc, $db_item_id, $defaultHide = false, $hide = null)
{
    $pref_name = 'hide_'.$svc.$db_item_id;

    if (empty($hide)) {
        $hide=$_REQUEST['hide_'.$svc];
    }

    $noPref = false;
    $old_hide = user_get_preference($pref_name);

  // Make sure they are both 0 if never set before
    if ($old_hide == false) {
        $noPref = true;
        $old_hide = 0;
    }

  // If no given value for hide, keep the old one
    if (!isset($hide)) {
        $hide = $old_hide;
    }

  // Update pref value if needed
    if ($old_hide != $hide) {
        user_set_preference($pref_name, $hide);
    }

    if ($hide == 2 || ($noPref && $defaultHide)) {
        $hide_url = 'hide_'.$svc.'=1&hide_item_id='.$db_item_id;
        $hide_img = '<img src="'.util_get_image_theme("pointer_right.png").'" align="middle" border="0" alt="Expand">';
        $hide_now = true;
    } else {
        $hide_url = 'hide_'.$svc.'=2&hide_item_id='.$db_item_id;
        $hide_img = '<img src="'.util_get_image_theme("pointer_down.png").'" align="middle" border="0" alt="Collapse">';
        $hide_now = false;
    }

    return array($hide_now, $hide_url, $hide_img);
}

function wiki_display_header()
{
    $GLOBALS['wiki_view']->header();
}

function wiki_display_footer()
{
    $GLOBALS['wiki_view']->footer();
}

/**
 * Common functions for WikiServiceViews and WikiServiceAdminViews
 *
 */
class WikiViews extends Views
{
  /* protected int    */ var $gid;
  /* protected string */ var $wikiname;
  /* protected string */ var $wikiLink;
  /* protected string */ var $wikiAdminLink;

  /**
   * WikiView - Constructor
   */
    function WikiView(&$controler, $id = 0, $view = null)
    {
        parent::view($controler, $view);

        $this->gid = (int) $id;

      // Parameters for HTML rendering
        $this->html_params['group']  = $this->gid;
        $this->html_params['toptab'] = 'wiki';

      // Wikize project name
        $pm = ProjectManager::instance();
        $go = $pm->getProject($this->gid);
        $this->wikiname = ucfirst($go->getUnixName()).'Wiki';

      // Build convenients URL
        $this->wikiLink      = '/wiki/index.php?group_id='.$this->gid;
        $this->wikiAdminLink = '/wiki/admin/index.php?group_id='.$this->gid;
    }

  /**
   * displayMenu - Public pure virtual
   */
    function displayMenu()
    {
    }

  /**
   * header - public
   *
   * Display Wiki Service header
   */
    function header()
    {
        $this->html_params['stylesheet'][] = '/wiki/themes/Codendi/phpwiki-codendi.css';
        $this->html_params['service_name'] = 'wiki';
        $this->html_params['project_id']   = $this->gid;
        parent::header();
        $this->displayMenu();
    }

    /**
    * pagePerms - public View
    */
    function _pagePerms($postUrl = '')
    {
        $wp = new WikiPage($_REQUEST['id']);
        $pagename = $wp->getPagename();

        $eM = EventManager::instance();
        $referenced = false;
        $eM->processEvent('isWikiPageReferenced', array(
                          'referenced' => &$referenced,
                          'wiki_page'  => $pagename,
                          'group_id' => $this->gid
                        ));
        if ($referenced) {
            $label = '';
            $eM->processEvent('getPermsLabelForWiki', array(
                              'label'  => &$label
                            ));
            print '<p align="center"><br><b>'.$label.'</b></p>';
        } else {
            print $GLOBALS['Language']->getText('wiki_views_wikiviews', 'set_perm_title');
            if (empty($pagename)) {
                print $GLOBALS['Language']->getText('wiki_views_wikiviews', 'empty_page');
            } else {
                $purifier = Codendi_HTMLPurifier::instance();
                print $GLOBALS['Language']->getText('wiki_views_wikiviews', 'not_empty_page', array($purifier->purify($pagename)));
                permission_display_selection_form("WIKIPAGE_READ", $wp->getId(), $this->gid, $postUrl);
            }
        }
    }
}
