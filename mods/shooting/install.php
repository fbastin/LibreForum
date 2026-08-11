<?php

//
// This install file will be included by the module automatically at the first
// time that it is run. This file will take care of adding the custom user
// field "mod_tos" to LibreForum and store the default settings for this module.
// This way, the administrator won't have to create the custom field manually
// nor to call the settings page.
//

if (!defined('PHORUM')) return;

include_once('./include/api/custom_profile_fields.php');

// Store custom profile field

// Get the current custom profile field if exists.
//$field = phorum_api_custom_profile_field_byname('mod_shooting');

// If the field does not exist. Add it.
//if ($field_exists===NULL) {
//    phorum_api_custom_profile_field_configure
//        ( array
//              ( 'id'            => NULL,
//                'name'          => 'mod_tos',
//                'length'        => 8,
//                'html_disabled' => 1,
//                'show_in_admin' => 0 ) );
//}

// Store module default setting.
//$PHORUM['mod_shooting'] = array
//    ( 'date_last_change' => date('Ymd'),
//      'default_accept' => 0,
//      'file_name' => 'terms-of-service' );

//if ( !phorum_db_update_settings
//         ( array
//               ( 'mod_tos'=>$PHORUM['mod_tos'],
//                 'mod_tos_installed'=>$PHORUM['mod_tos_installed'] ) ) ) {
//    $error = 'Database error while updating settings.';
//}

?>
