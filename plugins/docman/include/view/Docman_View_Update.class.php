<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_Update
*/

require_once('Docman_View_Details.class.php');

require_once('Docman_View_ItemDetailsSectionUpdate.class.php');

class Docman_View_Update extends Docman_View_Details {
    
    
    /* protected */ function _getTitle($params) {
        $hp = Codendi_HTMLPurifier::instance();
        return $GLOBALS['Language']->getText('plugin_docman', 'details_update_title',  $hp->purify($params['item']->getTitle(), CODEX_PURIFIER_CONVERT_HTML) );
    }
    
    /* protected */ function _content($params) {
        $force = isset($params['force_item']) ? $params['force_item'] : null;
        $token = isset($params['token']) ? $params['token'] : null;
        parent::_content($params, new Docman_View_ItemDetailsSectionUpdate($params['item'], $params['default_url'], $this->_controller, $force, $token), 'actions');
    }
}

?>
