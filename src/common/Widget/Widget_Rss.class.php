<?php
/**
 * Copyright (c) Enalean, 2011 - 2019. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi 2001-2009.
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

use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\RSS\FeedHTTPClient;
use Zend\Feed\Reader\Reader as FeedReader;

/**
* Widget_Rss
*
* Rss reader
*/
abstract class Widget_Rss extends Widget
{
    var $rss_title;
    var $rss_url;

    public function __construct($id, $owner_id, $owner_type)
    {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
    }

    function getTitle()
    {
        return $this->rss_title ?: 'RSS Reader';
    }
    function getContent()
    {
        if (! $this->rss_url) {
            return '';
        }
        $hp      = Codendi_HTMLPurifier::instance();
        $content = '<table class="tlp-table">';
        try {
            $feed = $this->retrieveFeed($this->rss_url);
        } catch (Zend\Feed\Exception\RuntimeException $ex) {
            return '<div class="tlp-alert-warning">' . _('An issue occurred while retrieving the RSS feed') . '</div>' .  $content . '</table>';
        }

        $sliced_feed = new LimitIterator($feed, 0, 10);
        foreach ($sliced_feed as $entry) {
            $content .= '<tr><td>';
            $content .= '<a href="'. $hp->purify($entry->getLink()) .'">'. $hp->purify($entry->getTitle(), CODENDI_PURIFIER_STRIP_HTML) .'</a>';
            $date     = $entry->getDateCreated();
            if ($date !== null) {
                $content .= '<span style="color:#999;" title="'. format_date($GLOBALS['Language']->getText('system', 'datefmt'), $date->getTimestamp()) .'"> - '. DateHelper::timeAgoInWords($date->getTimestamp()) .'</span>';
            }
            $content .= '</td></tr>';
        }

        return $content . '</table>';
    }
    function isAjax()
    {
        return true;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences($widget_id)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="title-'. (int)$widget_id .'">'. $purifier->purify(_('Title')) .'</label>
                <input type="text"
                       class="tlp-input"
                       id="title-'. (int)$widget_id .'"
                       name="rss[title]"
                       value="'. $purifier->purify($this->getTitle()) .'"
                       placeholder="RSS">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="url-'. (int)$widget_id .'">
                    URL <i class="fa fa-asterisk"></i>
                </label>
                <input type="text"
                       class="tlp-input"
                       id="url-'. (int)$widget_id .'"
                       name="rss[url]"
                       value="'. $purifier->purify($this->rss_url) .'"
                       pattern="https?://.*"
                       title="'. $purifier->purify(_('Please, enter a http:// or https:// link')) .'"
                       required
                       placeholder="https://example.com/rss.xml">
            </div>
            ';
    }

    public function getInstallPreferences()
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-rss-title">'. $purifier->purify(_('Title')) .'</label>
                <input type="text"
                       class="tlp-input"
                       id="widget-rss-title"
                       name="rss[title]"
                       value="'. $purifier->purify($this->getTitle()) .'"
                       placeholder="RSS">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-rss-url">
                    URL <i class="fa fa-asterisk"></i>
                </label>
                <input type="text"
                       class="tlp-input"
                       id="widget-rss-url"
                       name="rss[url]"
                       pattern="https?://.*"
                       title="'. $purifier->purify(_('Please, enter a http:// or https:// link')) .'"
                       required
                       placeholder="https://example.com/rss.xml">
            </div>
            ';
    }

    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type
    ) {
        $sql = "INSERT INTO widget_rss (owner_id, owner_type, title, url) 
        SELECT  ". $owner_id .", '". $owner_type ."', title, url
        FROM widget_rss
        WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' ";
        $res = db_query($sql);
        return db_insertid($res);
    }
    function loadContent($id)
    {
        $sql = "SELECT * FROM widget_rss WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->rss_title = $data['title'];
            $this->rss_url   = $data['url'];
            $this->content_id = $id;
        }
    }

    function create(Codendi_Request $request)
    {
        $content_id = false;
        $vUrl = new Valid_String('url');
        $vUrl->setErrorMessage("Can't add empty rss url");
        $vUrl->required();
        if ($request->validInArray('rss', $vUrl)) {
            $rss = $request->get('rss');
            $vTitle = new Valid_String('title');
            $vTitle->required();
            if (!$request->validInArray('rss', $vTitle)) {
                try {
                    $feed         = $this->retrieveFeed($rss['url']);
                    $rss['title'] = $feed->getTitle();
                } catch (\Zend\Feed\Exception\RuntimeException $ex) {
                    $rss['title'] = $request->get('title');
                }
            }
            $sql = 'INSERT INTO widget_rss (owner_id, owner_type, title, url) VALUES ('. $this->owner_id .", '". $this->owner_type ."', '". db_escape_string($rss['title']) ."', '". db_escape_string($rss['url']) ."')";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    function updatePreferences(Codendi_Request $request)
    {
        $done = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($rss = $request->get('rss')) && $request->valid($vContentId)) {
            $vUrl = new Valid_String('url');
            if ($request->validInArray('rss', $vUrl)) {
                $url = " url   = '". db_escape_string($rss['url']) ."' ";
            } else {
                $url = '';
            }

            $vTitle = new Valid_String('title');
            if ($request->validInArray('rss', $vTitle)) {
                $title = " title = '". db_escape_string($rss['title']) ."' ";
            } else {
                $title = '';
            }

            if ($url || $title) {
                $sql = "UPDATE widget_rss SET ". $title .", ". $url ." WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". (int)$request->get('content_id');
                $res = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }
    function destroy($id)
    {
        $sql = 'DELETE FROM widget_rss WHERE id = '. $id .' AND owner_id = '. $this->owner_id ." AND owner_type = '". $this->owner_type ."'";
        db_query($sql);
    }
    function isUnique()
    {
        return false;
    }

    /**
     * @@throws \Zend\Feed\Exception\RuntimeException
     */
    private function retrieveFeed(string $url) : \Zend\Feed\Reader\Feed\FeedInterface
    {
        $http_client = new FeedHTTPClient(HttpClientFactory::createClient(), HTTPFactoryBuilder::requestFactory());
        FeedReader::setHttpClient($http_client);
        $cache_dir = ForgeConfig::get('codendi_cache_dir') . '/rss';
        if (! is_dir($cache_dir) && ! mkdir($cache_dir) && ! is_dir($cache_dir)) {
            throw new \RuntimeException(sprintf('RSS cache directory "%s" was not created', $cache_dir));
        }
        $cache     = Zend\Cache\StorageFactory::factory(array(
            'adapter' => [
                'name'    => 'filesystem',
                'options' => ['cache_dir' => $cache_dir],
            ],
        ));
        FeedReader::setCache($cache);
        FeedReader::useHttpConditionalGet();

        return FeedReader::import($url);
    }
}
