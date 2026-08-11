<?php

if (!defined('PHORUM')) return;

//
// Add the Terms of Service agreement to the posting page
//
function mod_tos_tpl_editor_before_textarea() {

    if (phorum_page != "post" && phorum_page != "read") return;

    global $PHORUM;

    // Only run the checks when writing a new message.
    // We do not need the checks for editing existing messages.
    if (isset($PHORUM['DATA']['POSTING']['message_id'])) {
        if (!empty($PHORUM['DATA']['POSTING']['message_id'])) return;
    } else die( "mod_tos_tpl_editor_before_textarea(): "
                    ."Can't determine whether we're editing a new message." );

    // user accepted actual TOS?
    if (isset($PHORUM['user']['mod_tos_current']) && $PHORUM['user']['mod_tos_current']) {
      // Yes already accepted.
    } else {
        echo '<div><small>';
        // Additional text when reforcing accept
        if ($PHORUM['DATA']['LOGGEDIN']) {
            echo $PHORUM['DATA']['LANG']['TOS']['Reforce'].'<br /><br />';
        }
        // Option checked?
        if (    $PHORUM['mod_tos']['default_accept'] == 1
             || (isset($_POST['tos_accept']) && $_POST['tos_accept']) ) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }
        echo '<input type="checkbox" name="tos_accept" value="1"'.$checked.'" />';
        $pos = strpos
                   ( strtolower($PHORUM['DATA']['LANG']['TOS']['Agree']),
                     strtolower($PHORUM['DATA']['LANG']['TOS']['Header']) );
        if ($pos) {
            // Format only the word "agreement" as a link
            $len = strlen($PHORUM['DATA']['LANG']['TOS']['Header']);
            echo substr($PHORUM['DATA']['LANG']['TOS']['Agree'], 0, $pos)
                     .'<a href="'
                     .phorum_get_url
                         ( PHORUM_CUSTOM_URL,
                           $PHORUM['mod_tos']['file_name'], true )
                     .'">'
                     .substr($PHORUM['DATA']['LANG']['TOS']['Agree'], $pos, $len)
                     .'</a>'
                     .substr($PHORUM['DATA']['LANG']['TOS']['Agree'], $pos + $len);
        } else {
            // Format the complete agreement text as a link
            echo '<a href="'
                     .phorum_get_url
                         ( PHORUM_CUSTOM_URL,
                           $PHORUM['mod_tos']['file_name'], true )
                     .'" target="_blank">'
                     .$PHORUM['DATA']['LANG']['TOS']['Agree']
                     .'</a>';
        }
        echo '</small><br /><br /></div>';
    }
}

//
// Add the Terms of Service agreement to the register page
//
function mod_tos_tpl_register_form() {
    global $PHORUM;

    echo '<div><br /><small>'
             .$PHORUM['DATA']['LANG']['TOS']['Content']
             .'<br />'
             .'<input type="checkbox" name="tos_accept" value="1" />'
             .$PHORUM['DATA']['LANG']['TOS']['Agree']
             .'</small></div>';
}

//
// Check the agreement on the register page
//
function mod_tos_before_register($userdata) {
    global $PHORUM;

    // Any other module found an error before?
    if (isset($userdata['error']) && !empty($userdata['error'])) return $userdata;

    if (empty($_POST['tos_accept'])) {
        $userdata['error'] = $PHORUM['DATA']['LANG']['TOS']['ErrorRegister'];
    } else {
        $userdata['mod_tos'] = date('Ymd');
    }

    return $userdata;
}

//
// Check the agreement on the posting page
//
function mod_tos_check_post($postdata) {
    global $PHORUM;

    list($post, $error) = $postdata;

    // No other module found an error before?
    if (empty($error)) {
        // Check the agreement
        if (    empty($_POST['tos_accept'])         // TOS not accepted
             && !$PHORUM['user']['mod_tos_current'] // user accepted actual TOS?
           ) {
            return array($post, $PHORUM['DATA']['LANG']['TOS']['ErrorPost']);
        }
        // Store date of accept for registered users
        if (    $PHORUM['DATA']['LOGGEDIN']
             && isset($_POST['tos_accept'])
             && $_POST['tos_accept'] ) {
            $userdata=array();
            $userdata['user_id'] = $PHORUM['user']['user_id'];
            $userdata['mod_tos'] = date('Ymd');
            if (!phorum_api_user_save($userdata)) {
                return array($post, $PHORUM['DATA']['LANG']['ErrUserAddUpdate']);
            }
        }
    }

    return $postdata;
}

//
// Install once custom profiles field and build URL for the page showing the TOS
//
function mod_tos_common() {
    global $PHORUM;

    // Install once custom profiles field
    if (    !isset($PHORUM['mod_tos_installed'])
         || !$PHORUM['mod_tos_installed'] ) {
        include('./mods/terms_of_service/install.php');
    }

    $PHORUM['user']['mod_tos_current']
        = (    !$PHORUM['DATA']['LOGGEDIN']        // for guests always
            || !isset($PHORUM['user']['mod_tos'])  // reforce accept for registered user
            || !$PHORUM['user']['mod_tos']
            || (    $PHORUM['user']['mod_tos'] < $PHORUM['mod_tos']['date_last_change']
                 && $PHORUM['mod_tos']['date_last_change'] <= date('Ymd') )
          )?false:true; // True if it's current, false if it's not

    // Build URL for the page showing the TOS
    $PHORUM['DATA']['URL']['TOS']
        = phorum_get_url(PHORUM_CUSTOM_URL, $PHORUM['mod_tos']['file_name'], true);

    // Formatting terms of service
    $PHORUM['DATA']['LANG']['TOS']['Content']
        = nl2br($PHORUM['DATA']['LANG']['TOS']['Content']);
}

//
// Add sanity checks
//
function mod_tos_sanity_checks($sanity_checks) {
    if (    isset($sanity_checks)
         && is_array($sanity_checks) ) {
        $sanity_checks[] = array(
            'function'    => 'mod_tos_do_sanity_checks',
            'description' => 'Terms of Service Module'
        );
    }
    return $sanity_checks;
}

//
// Do sanity checks
//
function mod_tos_do_sanity_checks() {
    global $PHORUM;

    // Check if module settings exists.
    if (    !isset($PHORUM['mod_tos']['date_last_change'])
         || !$PHORUM['mod_tos']['date_last_change']
         || !isset($PHORUM['mod_tos']['default_accept'])
         || !isset($PHORUM['mod_tos']['file_name'])
         || !$PHORUM['mod_tos']['file_name'] ) {
          return array(
                     PHORUM_SANITY_CRIT,
                     'The default settings for the module are missing.',
                     "Login as administrator in LibreForum's administrative "
                         .'interface and go to the "Modules" section. Open '
                         .'the module settings for the Terms of Service '
                         .'Module and save the default values.'
                 );
    }

    // Check if template file exists
    if ( !file_exists
             ("./templates/{$PHORUM['default_forum_options']['template']}/terms_of_service.tpl")
       ) {
        return array(
                   PHORUM_SANITY_CRIT,
                   'The template for the Terms of Service page is missing.',
                   'Copy terms_of_service.tpl from the module directory '
                       .'to your template directory /templates/'
                       .htmlspecialchars($PHORUM['default_forum_options']['template']).'/.'
               );
    }

    // Check if terms-of-service file exists in phorum-directory even if the
    // admin uses a localized file name
    if (!file_exists("./{$PHORUM['mod_tos']['file_name']}.php")) {
        $tmpmessage = '';
        if ($PHORUM['mod_tos']['file_name']!='terms-of-service') {
            $tmpmessage
                = ' and rename the file to '
                      .htmlspecialchars($PHORUM['mod_tos']['file_name'])
                      .'.php';
        }
        return array(
                   PHORUM_SANITY_CRIT,
                   'The Terms of Service page in the phorum root directory is '
                       .'missing.',
                   'Copy terms-of-service.php from the module '
                       .'directory to phorum root directory directory'
                       .$tmpmessage.'.'
               );
    }

    // Check custom profile field
    // Get the current custom profile fields.
    $fields = $PHORUM['PROFILE_FIELDS'];
    // If this is not an array, we don't trust it.
    if (!is_array($fields)) {
        return array(
                   PHORUM_SANITY_CRIT,
                   "\$PHORUM['PROFILE_FIELDS'] is not an array."
               );
    } else {
        // Check if the field is available.
        $field_exists = false;
        foreach ($fields as $id => $fieldinfo) {
            if ($fieldinfo['name'] == 'mod_tos') {
                $field_exists = true;
                break;
            }
        }
        // The field does not exist.
        if (!$field_exists) {
            return array(
                       PHORUM_SANITY_CRIT,
                       'The custom profile field for the Terms of Service '
                       ."Module doesn't exist."
                   );
        }
    }

    // Check if custom language file exists
    $checked = array();
    // Check for the default language file.
    if ( !file_exists
             ("./mods/terms_of_service/lang/{$PHORUM['language']}.php")
       ) {
        return array(
            PHORUM_SANITY_WARN,
            'Your default language is set to "'
                .htmlspecialchars($PHORUM['language'])
                .'", but the language file "mods/terms_of_service/lang/'
                .htmlspecialchars($PHORUM['language'])
                .'.php" is not available on your system.',
            'Install the specified language file to make this default '
                .'language work or change the Default Language setting under '
                .'General Settings.'
        );
    }
    $checked[$PHORUM['language']] = true;

    // Check for the forum specific language file(s).
    $forums = phorum_db_get_forums();
    foreach ($forums as $id => $forum) {
        if (    !empty($forum['language'])
             && !$checked[$forum['language']]
             && !file_exists("./mods/terms_of_service/lang/{$forum['language']}.php")
           ) {
            return array(
                PHORUM_SANITY_WARN,
                'The language for forum "'
                    .htmlspecialchars($forum['name'])
                    .'" is set to "'
                    .htmlspecialchars($forum['language'])
                    .'", but the language file "mods/terms_of_service/lang/'
                    .htmlspecialchars($forum['language'])
                    .'.php" is not available on your system.',
                'Install the specified language file to make this default '
                    .'language work or change the language setting for the '
                    .'forum.'
            );
        }
        $checked[$forum['language']] = true;
    }

    // Check if custom language file contains same array key as the english file
    $PHORUM['DATA']['LANG'] = array();
    include('./mods/terms_of_service/lang/english.php');
    $orig_data = $PHORUM['DATA']['LANG'];
    $orig_keys = array_keys($PHORUM['DATA']['LANG']['TOS']);
    // Check all files in the module language directory
    $tmphandle = opendir('./mods/terms_of_service/lang/');
    if ($tmphandle) {
        while ($file = readdir($tmphandle)) {
            if ($file == '.' || $file == '..' || $file == 'english.php')
                continue;
            else
                $PHORUM['DATA']['LANG'] = array();
                include("./mods/terms_of_service/lang/{$file}");
                $new_keys = array_keys($PHORUM['DATA']['LANG']['TOS']);

                $missing_keys = array();

                foreach ($orig_keys as $id => $key) {
                    if (!in_array($key,$new_keys)) {
                        $missing_keys[$key] = $orig_data[$key];
                    }
                }

                if (count($missing_keys)) {
                    $tmpmessage
                        = 'The following keys are missing in your custom language file '.$file.':';
                    foreach ($missing_keys as $key => $val) {
                        $tmpmessage .= '<br />'.$key;
                    }
                    return array(
                               PHORUM_SANITY_CRIT,
                               $tmpmessage,
                               'Please add these keys to this language file!'
                           );
                }
        }
        closedir($tmphandle);
    } else {
        return array(
                   PHORUM_SANITY_CRIT,
                   'Error getting file list of module language files.',
                   'Check if the mods/terms_of_service/lang/ directory exists.'
               );
    }

    // Check if header.tpl {URL->TOS} (only warning)
    $tmpfile = file_get_contents("./templates/{$PHORUM['default_forum_options']['template']}/header.tpl");
    if (!preg_match('/{URL->TOS}/', $tmpfile)) {
          return array(
                     PHORUM_SANITY_WARN,
                     'Your template file "header.tpl" misses link to '
                         .'the terms of service page.',
                     "It's recommended to show a link to the TOS on the index "
                         .'page. See README in the module directory.'
                 );
    }

    return array(PHORUM_SANITY_OK, NULL, NULL);
}

?>