<?php
    //
    // Displays the Terms of Service
    //

    define('phorum_page','terms-of-service');

    include_once('./common.php');
    include_once('./include/api/user.php');
    include_once('./include/format_functions.php');

    // set all our URL's
    phorum_build_common_urls();

    // If users TOS was outdated he had the possibility to accept the actual
    // TOS on this page.
    if (!$PHORUM['user']['mod_tos_current']) {
        // Check the agreement
        if (!empty($_POST['tos_accept'])) {
            // Store date of accept for registered users
            if ($PHORUM['DATA']['LOGGEDIN']) {
                $userdata=array();
                $userdata['user_id']=$PHORUM['user']['user_id'];
                $userdata['mod_tos'] = date('Ymd');
                if (phorum_api_user_save($userdata)) {
                    $PHORUM['user']['mod_tos_current'] = 1;
                    $PHORUM['user']['mod_tos'] = $userdata['mod_tos'];

                    // Copy data from the updated user back into the user template data.
                    $formatted = phorum_api_user_format(array($PHORUM['user']));
                    foreach ($formatted[0] as $key => $val) {
                        $PHORUM['DATA']['USER'][$key] = $val;
                    }
                }
            }
        }
    }

    // Formatting date of version
    $PHORUM['DATA']['LANG']['TOS']['Version']
        = $PHORUM['DATA']['LANG']['TOS']['Version']
              .':&nbsp;'
              .phorum_date
                  ( $PHORUM['short_date'],
                    mktime
                        ( 12, 0, 0,
                          substr($PHORUM['mod_tos']['date_last_change'], 4, 2),
                          substr($PHORUM['mod_tos']['date_last_change'], 6, 2),
                          substr($PHORUM['mod_tos']['date_last_change'], 0, 4) ) );
    if (    isset($PHORUM['user']['mod_tos'])
         && $PHORUM['user']['mod_tos'] ) {
        $PHORUM['DATA']['LANG']['TOS']['LastAgree']
            = $PHORUM['DATA']['LANG']['TOS']['LastAgree']
                  .':&nbsp;'
                  .phorum_date
                      ( $PHORUM['long_date'],
                        mktime
                            ( 0, 0, 0,
                              substr($PHORUM['user']['mod_tos'], 4, 2),
                              substr($PHORUM['user']['mod_tos'], 6, 2),
                              substr($PHORUM['user']['mod_tos'], 0, 4) ) );
    }

    // include the correct template
    phorum_output('terms_of_service::' . 'terms_of_service');
?>
