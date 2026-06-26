<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
////////////////////////////////////////////////////////////////////////////////

    // Phorum 5 Admin

    define("PHORUM_ADMIN", 1);

    // set a sane error level for our admin.
    // this will make the coding time faster and
    // the code run faster.
    error_reporting(E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR );

    include_once "./common.php";
    include_once "./include/admin_functions.php";

    // initialized as empty
    $PHORUM['admin_token']="";
    if(!empty($_GET['phorum_admin_token'])) {
        $PHORUM['admin_token']=$_GET['phorum_admin_token'];
    } elseif(!empty($_POST['phorum_admin_token'])) {
        $PHORUM['admin_token']=$_POST['phorum_admin_token'];
    }

    // determine absolute URI for the admin
    $PHORUM["admin_http_path"] = phorum_get_current_url(false);

    // determine http_path (at install time; after that it's in the settings)
    if(!isset($PHORUM["http_path"])){
        $PHORUM["http_path"] = dirname($_SERVER["PHP_SELF"]);
    }

    // if we are installing or upgrading, we don't need to check for a session
    // 2005081000 was the internal version that introduced the installed flag
    if(!isset($PHORUM['internal_version']) || (!isset($PHORUM['installed']) && $PHORUM['internal_version']>='2005081000')) {

        // this is an install
        $module="install";

    } elseif ( (isset($_REQUEST["module"]) && $_REQUEST["module"]=="upgrade") ||
               $PHORUM['internal_version'] < PHORUM_SCHEMA_VERSION ||
               !isset($PHORUM['internal_patchlevel']) ||
               $PHORUM['internal_patchlevel'] < PHORUM_SCHEMA_PATCHLEVEL ) {

        // this is an upgrade
        $module="upgrade";

    } else {

        // Try to restore an admin session.
        $res_admin = phorum_api_user_session_restore(PHORUM_ADMIN_SESSION);

        // If the sso param is set, or if we successfully restored the admin session, clear the logged_out cookie.
        if (isset($_GET['sso']) || $res_admin) {
            if (isset($_COOKIE['phorum_admin_logged_out'])) {
                setcookie(
                    'phorum_admin_logged_out',
                    '',
                    time() - 86400,
                    $PHORUM['session_path'] ?? '/',
                    $PHORUM['session_domain'] ?? '',
                    FALSE,
                    TRUE
                );
                unset($_COOKIE['phorum_admin_logged_out']);
            }
        }

        // If no admin session exists, but a user session exists and the user is an admin,
        // we can automatically establish the admin session!
        if(!isset($PHORUM["user"]) || !$PHORUM["user"]["admin"]){
            if (empty($_COOKIE['phorum_admin_logged_out']) || isset($_GET['sso'])) {
                $res_user = phorum_api_user_session_restore(PHORUM_FORUM_SESSION);
            if(isset($PHORUM["user"]) && $PHORUM["user"]["admin"]){
                if(phorum_api_user_set_active_user(PHORUM_ADMIN_SESSION, $PHORUM["user"]["user_id"]) &&
                   phorum_api_user_session_create(PHORUM_ADMIN_SESSION)){
                    
                    // Generate a fresh admin token
                    $PHORUM["user"]['settings_data']['admin_token_time'] = time();
                    $sig_data = $PHORUM["user"]['user_id'].time().$PHORUM["user"]['username'];
                    $PHORUM["user"]['settings_data']['admin_token'] = phorum_generate_data_signature($sig_data);
                    $PHORUM['admin_token'] = $PHORUM["user"]['settings_data']['admin_token'];
                    
                    $tmp_user = array(
                        'user_id'=>$PHORUM["user"]['user_id'],
                        'settings_data'=>$PHORUM["user"]['settings_data']
                    );
                    phorum_api_user_save($tmp_user);

                    // Sync the IP lock in admin_security_suite module if enabled to prevent session theft false positives
                    if (isset($PHORUM["phorum_mod_admin_security_suite"])) {
                        $mod_cfg = $PHORUM["phorum_mod_admin_security_suite"];
                        if (isset($mod_cfg["enable_admin_IP_session_lock"]) && $mod_cfg["enable_admin_IP_session_lock"] == "1") {
                            $mod_cfg["admin_IP_session_locks"][$PHORUM["user"]["user_id"]] = $_SERVER["REMOTE_ADDR"];
                            $PHORUM["phorum_mod_admin_security_suite"] = $mod_cfg;
                            phorum_db_update_settings(array("phorum_mod_admin_security_suite" => $mod_cfg));
                        }
                    }
                    
                    // Redirect to the admin panel with the newly generated token!
                    phorum_redirect_by_url(phorum_admin_build_url('', TRUE));
                    exit();
                }
            }
            }
        }

        if(!isset($GLOBALS["PHORUM"]["user"]) || !$GLOBALS["PHORUM"]["user"]["admin"]){
            // if not an admin
            unset($GLOBALS["PHORUM"]["user"]);
            $module="login";
        } else {
            // load the default module if none is specified
            $module = "";
            if(isset($_POST["module"]) && is_scalar($_POST["module"])){
                $module = @basename($_POST["module"]);
            } elseif(isset($_GET["module"]) && is_scalar($_GET["module"])){
                $module = @basename($_GET["module"]);
            }
            if(empty($module) || !file_exists("./include/admin/$module.php")){
                $module = "default";
            }
            // check the admin token
            if(!empty($GLOBALS["PHORUM"]["user"]['settings_data']['admin_token']) &&
               $PHORUM['admin_token'] != $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token'] ||
               $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token_time'] <= (time()-PHORUM_ADMIN_TOKEN_TIMEOUT)) {
                // 900 = timeout after 15 minutes of inactivity
               // echo "invalid token or timeout ...";
               // var_dump($PHORUM['admin_token'],$GLOBALS["PHORUM"]["user"]['settings_data']['admin_token'],$GLOBALS["PHORUM"]["user"]['settings_data']['admin_token_time'],(time()-PHORUM_ADMIN_TOKEN_TIMEOUT));
                $PHORUM['admin_token']="";
            }

            if(empty($PHORUM['admin_token'])) {
                $module = "tokenmissing";
            } else {
                // update the token time
                phorum_api_user_save_settings(array(
                    'admin_token_time' => time()
                ));
            }
        }

    }

    $module = phorum_hook( "admin_pre", $module );
    ob_start();
    if($module!="help") include_once "./include/admin/header.php";
    include_once "./include/admin/$module.php";
    if($module!="help") include_once "./include/admin/footer.php";
    ob_end_flush();




?>
