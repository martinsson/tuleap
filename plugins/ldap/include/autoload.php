<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoload0eaa0f2b516ff01719305981e1c74a5e($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'ldap' => '/LDAP.class.php',
            'ldap_administrationpresenter' => '/LDAP_AdministrationPresenter.class.php',
            'ldap_authenticationfailedexception' => '/LDAP_AuthenticationFailedException.class.php',
            'ldap_backendsvn' => '/LDAP_BackendSVN.class.php',
            'ldap_cleanupmanager' => '/LDAP_CleanUpManager.class.php',
            'ldap_directorycleanupdao' => '/LDAP_DirectoryCleanUpDao.class.php',
            'ldap_directorysynchronization' => '/LDAP_DirectorySynchronization.class.php',
            'ldap_exception_addexception' => '/exception/AddException.class.php',
            'ldap_exception_bindexception' => '/exception/BindException.class.php',
            'ldap_exception_connexionexception' => '/exception/ConnexionException.class.php',
            'ldap_exception_deleteexception' => '/exception/DeleteException.class.php',
            'ldap_exception_nowriteexception' => '/exception/NoWriteException.class.php',
            'ldap_exception_renameexception' => '/exception/RenameException.class.php',
            'ldap_exception_updateexception' => '/exception/UpdateException.class.php',
            'ldap_groupmanager' => '/LDAP_GroupManager.class.php',
            'ldap_loginpresenter' => '/LoginPresenter.class.php',
            'ldap_projectdao' => '/LDAP_ProjectDao.class.php',
            'ldap_projectgroupdao' => '/LDAP_ProjectGroupDao.class.php',
            'ldap_projectgroupmanager' => '/LDAP_ProjectGroupManager.class.php',
            'ldap_projectmanager' => '/LDAP_ProjectManager.class.php',
            'ldap_searchpeople' => '/LDAP_SearchPeople.class.php',
            'ldap_searchpeopleresultpresenter' => '/LDAP_SearchPeopleResultPresenter.php',
            'ldap_svn_apache_modperl' => '/LDAP_SVN_Apache_ModPerl.class.php',
            'ldap_syncmail' => '/LDAP_SyncMail.class.php',
            'ldap_syncnotificationmanager' => '/LDAP_SyncNotificationManager.class.php',
            'ldap_syncremindernotificationmanager' => '/LDAP_SyncReminderNotificationManager.class.php',
            'ldap_user' => '/LDAP_User.class.php',
            'ldap_userdao' => '/LDAP_UserDao.class.php',
            'ldap_usergroupdao' => '/LDAP_UserGroupDao.class.php',
            'ldap_usergroupmanager' => '/LDAP_UserGroupManager.class.php',
            'ldap_usermanager' => '/LDAP_UserManager.class.php',
            'ldap_usernotfoundexception' => '/LDAP_UserNotFoundException.class.php',
            'ldap_usersync' => '/LDAP_UserSync.class.php',
            'ldap_usersync_orange' => '/LDAP_UserSync_Orange.class.php',
            'ldap_userwrite' => '/LDAP_UserWrite.class.php',
            'ldapplugin' => '/ldapPlugin.class.php',
            'ldapplugindescriptor' => '/LdapPluginDescriptor.class.php',
            'ldapplugininfo' => '/LdapPluginInfo.class.php',
            'ldapqueryescaper' => '/QueryEscaper.class.php',
            'ldapresult' => '/LDAPResult.class.php',
            'ldapresultiterator' => '/LDAPResult.class.php',
            'systemevent_plugin_ldap_update_login' => '/system_event/SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN.class.php',
            'tuleap\\ldap\\exception\\identifiertypenotfoundexception' => '/exception/IdentifierTypeNotFoundException.php',
            'tuleap\\ldap\\exception\\identifiertypenotrecognizedexception' => '/exception/IdentifierTypeNotRecognizedException.php',
            'tuleap\\ldap\\groupsyncadminemailnotificationsmanager' => '/GroupSyncAdminEmailNotificationsManager.class.php',
            'tuleap\\ldap\\groupsyncemailpresenter' => '/GroupSyncEmailPresenter.class.php',
            'tuleap\\ldap\\groupsyncnotificationsmanager' => '/GroupSyncNotificationsManager.php',
            'tuleap\\ldap\\groupsyncsilentnotificationsmanager' => '/GroupSyncSilentNotificationsManager.class.php',
            'tuleap\\ldap\\ldaplogger' => '/LdapLogger.php',
            'tuleap\\ldap\\linkmodalcontentpresenter' => '/LinkModalContentPresenter.php',
            'tuleap\\ldap\\nonuniqueuidretriever' => '/NonUniqueUidRetriever.php',
            'tuleap\\ldap\\project\\ugroup\\binding\\additionalmodalpresenterbuilder' => '/LDAP/Project/UGroup/Binding/AdditionalModalPresenterBuilder.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoload0eaa0f2b516ff01719305981e1c74a5e');
// @codeCoverageIgnoreEnd
