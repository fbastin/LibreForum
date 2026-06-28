<?php
if (!defined("PHORUM_ADMIN")) return;

require_once './mods/openstreetmap/defaults.php';
require_once './mods/openstreetmap/api.php';

// Convert OpenStreetMap module v1 settings.
$converted = FALSE;
if (!empty($PHORUM['mod_openstreetmap']['center'])) {
    $s = $PHORUM['mod_openstreetmap'];
    if (preg_match(
        '/^\((-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\)$/',
        $s['center'], $m
    )) {
        $PHORUM['mod_openstreetmap']['latitude']  = $m[1];
        $PHORUM['mod_openstreetmap']['longitude'] = $m[2];
        unset($PHORUM['mod_openstreetmap']['center']);
        $converted = TRUE;
    }
}
if (!empty($PHORUM['mod_openstreetmap']['type']) &&
    $PHORUM['mod_openstreetmap']['type'] === 'normal') {
    $PHORUM['mod_openstreetmap']['type'] = 'roadmap';
    $converted = TRUE;
}
if (!empty($PHORUM['mod_openstreetmap']['api_key'])) {
    unset($PHORUM['mod_openstreetmap']['api_key']);
    $converted = TRUE;
}
if ($converted) {
    phorum_db_update_settings(array(
        "mod_openstreetmap" => $PHORUM['mod_openstreetmap']
    ));
}

// save settings
if(count($_POST))
{
  $settings = array(
      "latitude"          => $_POST["map_latitude"],
      "longitude"         => $_POST["map_longitude"],
      "zoom"              => $_POST["map_zoom"],
      "type"              => $_POST["map_type"],
      "profile_auto_show" => isset($_POST["profile_auto_show"]) ? 1 : 0
  );

  if (!phorum_db_update_settings(array("mod_openstreetmap" => $settings))) {
    phorum_admin_error("Sorry, the setting were not updated (database error)");
  } else {
    phorum_admin_okmsg("The settings were successfully updated");
  }

  $PHORUM["mod_openstreetmap"] = $settings;
}

require_once './include/admin/PhorumInputForm.php';
$frm = new PhorumInputForm ("", "post", "Save");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "openstreetmap"); 

$frm->addbreak("Edit settings for the OpenStreetMap module");

$row = $frm->addrow(
    "Display automatically in the user's profile",
    $frm->checkbox(
        "profile_auto_show", "1", "",
        $PHORUM["mod_openstreetmap"]["profile_auto_show"]
    )
);
$frm->addhelp($row,
    "Display automatically in the user's profile",
    "This option can be used to configure automatic displaying of the map for
     the user in the user's profile. If you disable this option, you can
     place the map anywhere in the profile.tpl template by hand. Place the
     code {MOD_OPENSTREETMAP} in your template at the place where you want the
     map to appear. See also the README for this module for an example."
);

$frm->addmessage("");
$frm->addbreak("Configure the starting position for the map");

// Show the map editor.
$frm->addmessage(
    "Using the map below, you can configure the default state for the map.
     This state is shown in case a user did not yet set a position or if
     he/she unsets the position. The map's center and zoom level 
     are all stored for defining the default state."
);

// Construct the code for the map editor.
$map = mod_openstreetmap_build_maptool('map-editor', array(
    'map_latitude'    => $PHORUM['mod_openstreetmap']['latitude'],
    'map_longitude'   => $PHORUM['mod_openstreetmap']['longitude'],
    'map_zoom'        => $PHORUM['mod_openstreetmap']['zoom'],
    'map_type'        => 'roadmap',

    // We need to specify these here explicitly, because otherwise
    // the currently active map center, zoom and type would be used
    // as the reset state by the maptool builder code.
    'reset_latitude'  => 40,
    'reset_longitude' => -20,
    'reset_zoom'      => 1,
    'reset_type'      => 'roadmap'
));

$frm->addmessage(
    "<div style=\"width:100%; height: 350px; margin-bottom: 1em\">$map</div>"
);

$frm->show();
?>
