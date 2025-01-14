<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;

$USER_RES=array();


//Deprecated. Use User->isLoggedIn() instead
function user_isloggedin()
{
    return UserManager::instance()->getCurrentUser()->isLoggedIn();
}

//Deprecated. Use User->isRestricted() instead
function user_isrestricted()
{
    return UserManager::instance()->getCurrentUser()->isRestricted();
}

//Deprecated. Use User->isSuperUser() instead
function user_is_super_user()
{
    return UserManager::instance()->getCurrentUser()->isSuperUser();
}

//Deprecated. Use User->isMember() instead
function user_ismember($group_id, $type = 0)
{
    return UserManager::instance()->getCurrentUser()->isMember($group_id, $type);
}

//Deprecated. Use User->getName() instead
function user_getname($user_id = 0)
{
    global $USER_NAMES,$Language;
    // use current user if one is not passed in
    if (!$user_id) {
        return UserManager::instance()->getCurrentUser()->getUserName();
    }
    // else must lookup name
    else {
        if (isset($USER_NAMES["user_$user_id"]) && $USER_NAMES["user_$user_id"]) {
         //user name was fetched previously
            return $USER_NAMES["user_$user_id"];
        } else {
         //fetch the user name and store it for future reference
            $result = db_query("SELECT user_id,user_name FROM user WHERE user_id='".db_es($user_id)."'");
            if ($result && db_numrows($result) > 0) {
                //valid user - store and return
                $USER_NAMES["user_$user_id"]=db_result($result, 0, "user_name");
                return $USER_NAMES["user_$user_id"];
            } else {
                //invalid user - store and return
                $USER_NAMES["user_$user_id"]="<B>".$Language->getText('include_user', 'invalid_u_id')."</B>";
                return $USER_NAMES["user_$user_id"];
            }
        }
    }
}

//quick hack - this entire library needs a rewrite similar to groups library
//Deprecated. Use User->getRealName() instead
function user_getrealname($user_id)
{
    global $Language;
        $result = user_get_result_set($user_id);
    if ($result && db_numrows($result) > 0) {
        return db_result($result, 0, "realname");
    } else {
        return $Language->getText('include_user', 'not_found');
    }
}

// LJ - Added here because we use the real e-mail addresse
// on Codendi - No e-mail aliases like on SF
//Deprecated. Use User->getEmail() instead
function user_getemail($user_id)
{
    global $Language;
        $result = user_get_result_set($user_id);
    if ($result && db_numrows($result) > 0) {
        return db_result($result, 0, "email");
    } else {
        return $Language->getText('include_user', 'email_not_found');
    }
}

function user_getid_from_email($email)
{
    global $Language;
    $result = db_query("SELECT user_id FROM user WHERE email='".db_es($email)."'");
    if ($result && db_numrows($result) > 0) {
        return db_result($result, 0, "user_id");
    } else {
        return $Language->getText('include_user', 'not_found');
    }
}

//Deprectaed. Use User->getEmail() and UserManager->getUserByUserName() instead
function user_getemail_from_unix($user_name)
{
    global $Language;
        $result = user_get_result_set_from_unix($user_name);
    if ($result && db_numrows($result) > 0) {
        return db_result($result, 0, "email");
    } else {
        return $Language->getText('include_user', 'email_not_found');
    }
}

//Deprecated. Use UserManager->getUserById() instead and don't use db_result in all code
function user_get_result_set($user_id)
{
    //create a common set of user result sets,
    //so it doesn't have to be fetched each time

    global $USER_RES;
    if (!isset($USER_RES["_".$user_id."_"]) || !$USER_RES["_".$user_id."_"]) {
        $USER_RES["_".$user_id."_"]=db_query("SELECT * FROM user WHERE user_id='".db_es($user_id)."'");
        return $USER_RES["_".$user_id."_"];
    } else {
        return $USER_RES["_".$user_id."_"];
    }
}

//Deprecated. Use UserManager->getUserByUserName() instead and don't use db_result in all code
function user_get_result_set_from_unix($user_name)
{
    //create a common set of user result sets,
    //so it doesn't have to be fetched each time

    global $USER_RES;
    $res = db_query("SELECT * FROM user WHERE user_name='".db_es($user_name)."'");
    $user_id = db_result($res, 0, 'user_id');
    $USER_RES["_".$user_id."_"] = $res;
    return $USER_RES["_".$user_id."_"];
}
function user_get_result_set_from_email($email)
{
    //create a common set of user result sets,
    //so it doesn't have to be fetched each time

    global $USER_RES;
    $sql = "SELECT * FROM user WHERE (user_name='".db_es($email)."' or email='".db_es($email)."')";
    $res = db_query($sql);
    $user_id = db_result($res, 0, 'user_id');
    $USER_RES["_".$user_id."_"] = $res;
    return $USER_RES["_".$user_id."_"];
}

//Deprecated. Use user->getTimezone() instead
function user_get_timezone()
{
    $current_user = UserManager::instance()->getCurrentUser();
    if ($current_user->isLoggedIn()) {
        return $current_user->getTimezone();
    } else {
        return '';
    }
}

//Deprecated. Use User->setPreference() instead.
function user_set_preference($preference_name, $value)
{
    global $user_pref;
    if (user_isloggedin()) {
        $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
        $preference_name=strtolower(trim($preference_name));
        $result=db_query("UPDATE user_preferences SET preference_value='".db_es($value)."' ".
        "WHERE user_id='".$db_escaped_user_id."' AND preference_name='".db_es($preference_name)."'");
        if (db_affected_rows($result) < 1) {
            echo db_error();
            $result=db_query("INSERT INTO user_preferences (user_id,preference_name,preference_value) ".
             "VALUES ('".$db_escaped_user_id."','".db_es($preference_name)."','".db_es($value)."')");
        }

     // Update the Preference cache if it was setup by a user_get_preference
        if (isset($user_pref)) {
            $user_pref[$preference_name] = $value;
        }

        return true;
    } else {
        return false;
    }
}

//Deprecated. Use User->getPreference() instead.
function user_get_preference($preference_name)
{
    global $user_pref;
    if (user_isloggedin()) {
        $preference_name=strtolower(trim($preference_name));
     /*
      First check to see if we have already fetched the preferences
     */
        if ($user_pref) {
            if (isset($user_pref["$preference_name"]) && $user_pref["$preference_name"]) {
                //we have fetched prefs - return part of array
                return $user_pref["$preference_name"];
            } else {
                //we have fetched prefs, but this pref hasn't been set
                return false;
            }
        } else {
            $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
         //we haven't returned prefs - go to the db
            $result=db_query("SELECT preference_name,preference_value FROM user_preferences ".
            "WHERE user_id='".$db_escaped_user_id."'");

            if (db_numrows($result) < 1) {
                return false;
            } else {
                //iterate and put the results into an array
                while ($row = db_fetch_array($result)) {
                    $user_pref[$row['preference_name']]=$row['preference_value'];
                }
                if (isset($user_pref["$preference_name"])) {
                 //we have fetched prefs - return part of array
                         return $user_pref["$preference_name"];
                } else {
             //we have fetched prefs, but this pref hasn't been set
                    return false;
                }
            }
        }
    } else {
        return false;
    }
}

//Deprecated. Use User->delPreference() instead.
function user_del_preference($preference_name)
{
    global $user_pref;
    if (user_isloggedin()) {
        if ($user_pref && array_key_exists($preference_name, $user_pref)) {
            unset($user_pref[$preference_name]);
        }
        $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
        $sql = 'DELETE FROM user_preferences'
            .' WHERE preference_name="'.db_es($preference_name).'"'
            .' AND user_id='.$db_escaped_user_id;
        $res = db_query($sql);
        if (db_affected_rows($res) != 1) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}

function user_display_choose_password($page, $user_id = false)
{
    $purifier = Codendi_HTMLPurifier::instance();
    ?>
    <table><tr valign='top'><td>
    <?php

    if ($page == 'admin_creation') {
        echo $purifier->purify($GLOBALS['Language']->getText('account_change_pw', 'new_password'));
        ?>:
     <br><div class="input-append"><input type="text" value="" id="form_pw" name="form_pw"></div>
     <script type="text/javascript" src="/scripts/user.js"></script>


    <?php } else {
        echo $purifier->purify($GLOBALS['Language']->getText('account_change_pw', 'new_password')); ?>:
    <br><input type="password" value="" id="form_pw" name="form_pw" autocomplete="new-password">
    <p><?php echo $purifier->purify($GLOBALS['Language']->getText('account_change_pw', 'new_password2')); ?>:
    <br><input type="password" value="" name="form_pw2" autocomplete="new-password">
    <?php } ?>
    </td><td>
    <div class="password_strategy">
        <p class="robustness"><?php echo $purifier->purify($GLOBALS['Language']->getText('account_check_pw', 'password_robustness'))?>
            <span class="password_strategy_good"><?php echo $purifier->purify($GLOBALS['Language']->getText('account_check_pw', 'good')); ?></span>
            <span class="password_strategy_bad"><?php echo $purifier->purify($GLOBALS['Language']->getText('account_check_pw', 'bad')); ?></span>
            <img class="password_validators_loading" src="/themes/common/images/ic/spinner-16.gif">
        </p>
        <?php
        $password_configuration_retriever = new PasswordConfigurationRetriever(new PasswordConfigurationDAO());
        $password_configuration           = $password_configuration_retriever->getPasswordConfiguration();
        $password_strategy                = new PasswordStrategy($password_configuration);
        include($GLOBALS['Language']->getContent('account/password_strategy'));
        foreach ($password_strategy->validators as $key => $v) {
            echo '<p class="password_validator_msg_'. $purifier->purify($key) .'"><i class="fa fa-times password_strategy_bad"></i> '. $purifier->purify($v->description()) .'</p>';
        }
        ?>
    </blockquote>
    </td></tr></table>
    <script type="text/javascript">
    <?php
    $password_validators_js = array();
    $validator_keys = array_keys($password_strategy->validators);
    foreach ($validator_keys as $validator_key) {
        $password_validators_js[] = "'" . $purifier->purify($validator_key, CODENDI_PURIFIER_JS_QUOTE) . "'";
    }
    ?>
    var password_validators = [<?php echo implode(', ', $password_validators_js) ?>];
    </script>
    <?php
    if ($user_id) {
        echo '<input type="hidden" name="user_id" value="'. $purifier->purify($user_id) .'" />';
    }
}

?>
