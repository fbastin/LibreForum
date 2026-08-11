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
$existing = phorum_api_custom_profile_field_byname('mod_tos');

// We have, but it is a deleted field.
// In this case restore the field and its data.
if (!empty($existing['deleted'])) {
    phorum_api_custom_profile_field_restore($existing['id']);
    $id = $existing['id'];
}
// Existing field.
elseif (!empty($existing)) {
    $id = $existing['id'];
}
// New field.
else {
    $id = NULL;
}

// Configure the field.
phorum_api_custom_profile_field_configure
        ( array
          ( 'id'            => $id,
                'name'          => 'mod_tos',
                'length'        => 8,
                'html_disabled' => 1,
                'show_in_admin' => 0 ) );

// Store module default settings.
$PHORUM['mod_tos'] = array
    ( 'date_last_change' => date('Ymd'),
      'default_accept' => 0,
      'file_name' => 'terms-of-service' );

// Force the default_accept to be an integer value.
settype($PHORUM['mod_tos']['default_accept'], 'int');

// Keep track of the module's install state.
phorum_db_update_settings
         ( array
           ( 'mod_tos' => $PHORUM['mod_tos'],
             'mod_tos_installed' => 1 ) )

?>
