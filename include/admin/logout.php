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

    if(!defined("PHORUM_ADMIN")) return;

    include_once("./include/api/base.php");
    include_once("./include/api/user.php");

    global $PHORUM;

    // Check if the user has a valid front-end admin session
    $has_front_admin = false;
    $res_user = phorum_api_user_session_restore(PHORUM_FORUM_SESSION);
    if (isset($PHORUM["user"]) && $PHORUM["user"]["admin"]) {
        $has_front_admin = true;
    }

    // Check referrer
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $from_admin_tools = (strpos($referer, 'admin-tools.php') !== false);

    phorum_api_user_session_destroy(PHORUM_ADMIN_SESSION);
    
    // Set a cookie to indicate the admin has explicitly logged out, preventing auto-SSO.
    setcookie(
        'phorum_admin_logged_out',
        '1',
        time() + 3600,
        $PHORUM['session_path'] ?? '/',
        $PHORUM['session_domain'] ?? '',
        FALSE,
        TRUE
    );
    
    if ($has_front_admin || $from_admin_tools) {
        $redir_url = '/admin-tools.php';
    } else {
        $redir_url = '/';
    }

    phorum_redirect_by_url($redir_url);
    exit();

?>
