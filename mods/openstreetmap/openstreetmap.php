<?php

if (!defined("PHORUM")) return;

require_once './mods/openstreetmap/api.php';
require_once './mods/openstreetmap/defaults.php';

// Hook: common
function phorum_mod_openstreetmap_common()
{
    global $PHORUM;

    // Generate addon URL.
    $PHORUM["DATA"]["URL"]["MOD_OPENSTREETMAP_USERMAP"] =
        phorum_get_url(PHORUM_ADDON_URL, "module=openstreetmap", "addon=usermap");

    // Handle module installation:
    // Load the module installation code if this was not yet done.
    // The installation code will take care of automatically adding
    // the custom profile field that is needed for this module.
    if (! isset($PHORUM["mod_openstreetmap_installed"]) ||
        ! $PHORUM["mod_openstreetmap_installed"]) {
        include("./mods/openstreetmap/install.php");
    }
}

// Hook: cc_menu_options_hook 
// This hook will add a "Location" link to the control center menu.
// This link will lead to a page where the user can configure his location.
function phorum_mod_openstreetmap_cc_menu_options_hook()
{
    global $PHORUM;

    // Generate the required template data for the control panel menu button.
    if ($PHORUM["DATA"]["PROFILE"]["PANEL"] == 'location')
        $PHORUM["DATA"]["LOCATION_PANEL_ACTIVE"] = TRUE;
    $PHORUM["DATA"]["URL"]["CC_LOCATION"] =
        phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=location");

    // Show the menu button.
    include phorum_get_template('openstreetmap::cc_menu_item');
}

// Hook: cc_panel
// This hook will setup the {MOD_OPENSTREETMAP} template variable
// that can be used to display the map editor in the control center.
function phorum_mod_openstreetmap_cc_panel($data)
{
    global $PHORUM;

    // Check if we are on our custom "location" panel.
    if ($data['panel'] != 'location') return $data;

    // Check if map data was posted.
    // If yes, then store the maptool's state in the user data.
    if (isset($_POST['map_latitude']))
    {
        $PHORUM['user']['mod_openstreetmap'] =
            mod_openstreetmap_filter_state_data($_POST);

        phorum_api_user_save(array(
            'user_id'         => $PHORUM['user']['user_id'],
            'mod_openstreetmap' => $PHORUM['user']['mod_openstreetmap']
        ));

        $data['okmsg'] = $PHORUM["DATA"]["LANG"]["ProfileUpdatedOk"];
    }

    // Retrieve the data for the active Phorum user.
    $mapstate = empty($PHORUM["user"]["mod_openstreetmap"])
              ? array() : $PHORUM["user"]["mod_openstreetmap"];

    // Upgrade the user data if it looks like version 1 data.
    if (isset($mapstate['marker'])) {
        $mapstate = mod_openstreetmap_upgrade_userdata($mapstate);
    }

    // Build the HTML code for the map editor.
    $PHORUM['DATA']['MOD_OPENSTREETMAP'] =
        mod_openstreetmap_build_maptool('location-editor', $mapstate);

    $PHORUM["DATA"]["URL"]["LOCATION_CONFIGURE"] = phorum_get_url(
        PHORUM_CONTROLCENTER_URL, "panel=location"
    );

    $data['handled'] = TRUE;
    $data['template'] = 'openstreetmap::cc_panel';

    return $data;
}

// Hook: profile
// Setup the OpenStreetMap code for the user profile.
function phorum_mod_openstreetmap_profile($profile)
{
    global $PHORUM;

    $PHORUM['DATA']['MOD_OPENSTREETMAP'] = '';

    // Retrieve the data for the active Phorum user.
    $mapstate = empty($profile['mod_openstreetmap'])
              ? array() : $profile['mod_openstreetmap'];

    // Upgrade the user data if it looks like version 1 data.
    if (isset($mapstate['marker'])) {
        $mapstate = mod_openstreetmap_upgrade_userdata($mapstate);
    }

    // Do not show a map if neither a marker, nor a streetview are available.
    if (!isset($mapstate['marker_latitude']) &&
        !isset($mapstate['streetview_latitude'])) return $profile;

    // If a position is set in streetview, then copy that position to
    // the marker position, so the marker and streetview will match
    // when viewing the map.
    if (isset($mapstate['streetview_latitude']) &&
        isset($mapstate['streetview_longitude'])) {
        $mapstate['marker_latitude'] = $mapstate['streetview_latitude'];
        $mapstate['marker_longitude'] = $mapstate['streetview_longitude'];
    }

    // Build the HTML code for the map viewer.
    $PHORUM['DATA']['MOD_OPENSTREETMAP'] =
        mod_openstreetmap_build_maptool('viewer', $mapstate);

    // Format country and city for the profile page.
    if (!empty($profile['mod_openstreetmap']))
    {
        $m = $profile['mod_openstreetmap'];
        if (!empty($m['geoloc_country'])) {
            $profile['mod_openstreetmap']['country'] = htmlspecialchars(
                $m['geoloc_country'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        }
        if (!empty($m['geoloc_city'])) {
            $profile['mod_openstreetmap']['city'] = htmlspecialchars(
                $m['geoloc_city'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        }
    }

    return $profile;
}

// Hook: before_footer_profile
// This hook is used to display the map in the user profile,
// unless the admin configured the module setting to not display
// the map automatically in the profile.
function phorum_mod_openstreetmap_before_footer_profile()
{
    global $PHORUM;

    if (isset($PHORUM["DATA"]["MOD_OPENSTREETMAP"]) &&
        $PHORUM["mod_openstreetmap"]["profile_auto_show"]) {
        include phorum_get_template("openstreetmap::profile");
    }
}

// Hook: read
// Setup the author's city and country for the message data.
function phorum_mod_openstreetmap_read($messages)
{
    global $PHORUM;

    foreach ($messages as $id => $message)
    {
        if (!empty($messages[$id]['user']) &&
            !empty($messages[$id]['user']['mod_openstreetmap'])) {
            $m = $messages[$id]['user']['mod_openstreetmap'];
            if (!empty($m['geoloc_country'])) {
                $messages[$id]['user']['country'] = htmlspecialchars(
                    $m['geoloc_country'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            }
            if (!empty($m['geoloc_city'])) {
                $messages[$id]['user']['city'] = htmlspecialchars(
                    $m['geoloc_city'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            }
        }
    }

    return $messages;
}

// Hook: addon
function phorum_mod_openstreetmap_addon()
{
    global $PHORUM;

    if (! isset($PHORUM["args"]["addon"]))
        die("missing \"addon\" parameter for the openstreetmap module");

    // Load addon script.
    $addon = basename($PHORUM["args"]["addon"]);
    if (file_exists("./mods/openstreetmap/addon/{$addon}.php")) {
        include("./mods/openstreetmap/addon/{$addon}.php");
    } else {
        // Unknown addon.
        die("Unknown openstreetmap module addon script: " .
            htmlspecialchars($addon));
    }
}

?>
