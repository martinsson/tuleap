<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\FRS\FRSPermissionDao;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\FRSPermissionManager;

class ServiceFile extends Service
{
    public function getIconName(): string
    {
        return 'fa-files-o';
    }

    /**
     * getPublicArea
     *
     * Return the link which will be displayed in public area in summary page
     * @return string
     */
    public function getPublicArea(): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $html    = '';
        $html   .= '<p><a href="/file/showfiles.php?group_id='.urlencode((string) $this->getGroupId()).'">';
        $html   .= '<i class="dashboard-widget-content-projectpublicareas '.$purifier->purify($this->getIcon()).'"></i>';
        $html   .= $GLOBALS['Language']->getText('include_project_home', 'file_releases').'</a>';
        $user_id = UserManager::instance()->getCurrentUser()->getId();
        $html   .= ' ( '.$GLOBALS['Language']->getText('include_project_home', 'packages', count($this->_getPackagesForUser($user_id))).' )';
        $html   .= '</p>';
        return $html;
    }
    /**
    * getSummaryPageContent
    *
    * Return the text to display on the summary page
    * @return arr[title], arr[content]
    */
    public function getSummaryPageContent()
    {
        $hp   = Codendi_HTMLPurifier::instance();
        $user = UserManager::instance()->getCurrentUser();
        $ret  = array(
            'title' => $GLOBALS['Language']->getText('include_project_home', 'latest_file_releases'),
            'content' => ''
        );

        $packages = $this->_getPackagesForUser($user->getId());
        if (count($packages)) {
            $ret['content'] .= '
                <table cellspacing="1" cellpadding="5" width="100%" border="0" class="tlp-table">
                    <thead>
                        <tr class="boxitem">
                            <th>
                                '.$GLOBALS['Language']->getText('include_project_home', 'package').'
                            </th>
                            <th>
                                '.$GLOBALS['Language']->getText('include_project_home', 'version').'
                            </th>
                            <th>
                                '.$GLOBALS['Language']->getText('include_project_home', 'download').'
                            </th>
                        </tr>
                    </thead>
                    <tbody>
            ';
            require_once('FileModuleMonitorFactory.class.php');
            $fmmf = new FileModuleMonitorFactory();
            foreach ($packages as $package) {
                // the icon is different whether the package is monitored or not
                if ($fmmf->isMonitoring($package['package_id'], $user, false)) {
                    $monitor_img = '<i class="fa fa-bell-slash"></i>';
                } else {
                    $monitor_img = '<i class="fa fa-bell"></i>';
                }

                $ret['content'] .= '
                  <tr class="boxitem">
                  <td>
                    <b>' .  $hp->purify(util_unconvert_htmlspecialchars($package['package_name']), CODENDI_PURIFIER_CONVERT_HTML)  . '</b>&nbsp;
                    <a HREF="/file/filemodule_monitor.php?filemodule_id=' . $package['package_id'] . '&group_id='.$this->getGroupId().'">'.
                        $monitor_img . '     
                    </a>
                  </td>';
                // Releases to display
                $ret['content'] .= '<td>'.  $hp->purify($package['release_name'], CODENDI_PURIFIER_CONVERT_HTML)  .'&nbsp;<A href="/file/shownotes.php?group_id=' . $this->getGroupId() . '&release_id=' . $package['release_id'] . '">
                    <i class="fa fa-fw fa-files-o"></i>
                  </td>
                  <td><a href="/file/showfiles.php?group_id=' . $this->getGroupId() . '&release_id=' . $package['release_id'] . '">'.$GLOBALS['Language']->getText('include_project_home', 'download').'</a></td></tr>';
            }
            $ret['content'] .= '</tbody></table>';
        } else {
            $ret['content'] .= '<b>'. $GLOBALS['Language']->getText('include_project_home', 'no_files_released') .'</b>';
        }
        $ret['content'] .= '
            <div align="center">
                <a href="/file/showfiles.php?group_id='.$this->getGroupId().'">['.$GLOBALS['Language']->getText('include_project_home', 'view_all_files').']</A>
            </div>
        ';
        return $ret;
    }

    private function getFRSPackageFactory()
    {
        require_once('FRSPackageFactory.class.php');
        return new FRSPackageFactory();
    }

    /**
    * _getPackagesForUser
    *
    * return the packages the user can see
    *
    * @param  user_id
    */
    function _getPackagesForUser($user_id)
    {
        $frspf = $this->getFRSPackageFactory();
        $packages = array();
        $sql="SELECT frs_package.package_id,frs_package.name AS package_name,frs_release.name AS release_name,frs_release.release_id AS release_id,frs_release.release_date AS release_date ".
        "FROM frs_package,frs_release ".
        "WHERE frs_package.package_id=frs_release.package_id ".
        "AND frs_package.group_id='". db_ei($this->getGroupId()) ."' ".
        "AND frs_release.status_id=' ".db_ei($frspf->STATUS_ACTIVE)."' ".
        "ORDER BY frs_package.rank,frs_package.package_id,frs_release.release_date DESC, frs_release.release_id DESC";
        $res_files = db_query($sql);
        $rows_files = db_numrows($res_files);
        if ($res_files && $rows_files >= 1) {
            for ($f=0; $f<$rows_files; $f++) {
                $package_id=db_result($res_files, $f, 'package_id');
                $release_id=db_result($res_files, $f, 'release_id');
                if ($frspf->userCanRead($this->getGroupId(), $package_id, $user_id)) {
                    if (isset($package_displayed[$package_id]) && $package_displayed[$package_id]) {
                        //if ($package_id==db_result($res_files,($f-1),'package_id')) {
                        //same package as last iteration - don't show this release
                    } else {
                        $authorized=false;
                        // check access.
                        if (permission_exist('RELEASE_READ', $release_id)) {
                            $authorized=permission_is_authorized('RELEASE_READ', $release_id, $user_id, $this->getGroupId());
                        } else {
                            $authorized=permission_is_authorized('PACKAGE_READ', $package_id, $user_id, $this->getGroupId());
                        }
                        if ($authorized) {
                            $packages[] = array(
                                'package_name' => db_result($res_files, $f, 'package_name'),
                                'release_name' => db_result($res_files, $f, 'release_name'),
                                'release_id'   => $release_id,
                                'package_id'   => $package_id,
                            );
                            $package_displayed[$package_id] = true;
                        }
                    }
                }
            }
        }
        return $packages;
    }

    public function displayFRSHeader(Project $project, $title)
    {
        $GLOBALS['HTML']->includeJavascriptSnippet(
            file_get_contents($GLOBALS['Language']->getContent('script_locale', null, 'svn', '.js'))
        );

        $user = UserManager::instance()->getCurrentUser();
        if ($this->getFrsPermissionManager()->isAdmin($project, $user)) {
            $toolbar[]   = array(
                'title' => $GLOBALS['Language']->getText('file_file_utils', 'toolbar_admin'),
                'url'   => '/file/admin/?'. http_build_query(array(
                        'group_id' => $project->getID(),
                        'action'   => 'edit-permissions'
                    ))
            );
        }

        $toolbar[] = array(
            'title' => $GLOBALS['Language']->getText('file_file_utils', 'toolbar_help'),
            'url'   => "javascript:help_window('/doc/".$user->getShortLocale()."/user-guide/frs.html')"
        );

        $breadcrumbs = array();

        $this->displayHeader($title, $breadcrumbs, $toolbar);
    }

    private function getFrsPermissionManager()
    {
        return FRSPermissionManager::build();
    }
}
