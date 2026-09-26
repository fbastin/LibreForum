<?php

// Make sure that this script is loaded from the admin interface.
if(!defined("PHORUM_ADMIN")) return;

Global $PHORUM;

// Save settings in case this script is run after posting
// the settings form.
if(count($_POST)) 
{
    // Create the settings array for this module.
    $PHORUM["phorum_mod_pm_alerts"] = array(
	"no_alert_on_page_pm"  => empty($_POST["no_alert_on_page_pm"]) ? 0 : 1,
	"allow_users_to_disable_alerts"  => empty($_POST["allow_users_to_disable_alerts"]) ? 0 : 1,
	);

    if(! phorum_db_update_settings(array("phorum_mod_pm_alerts"=>$PHORUM["phorum_mod_pm_alerts"]))) {
        $error="Database error while updating settings.";
    } else {
        phorum_admin_okmsg("Settings Updated");
    }
}

//check if the necessary custom profile fields have been created
foreach ($PHORUM["PROFILE_FIELDS"] as $key => $cstm_field) {
    if ($key === 'num_fields' || !is_array($cstm_field)) continue;   // compteur entier rangé parmi les champs (cœur : même garde)
	if ($cstm_field["name"] == "phorum_mod_pm_alerts_no_alert") {
		if (isset($cstm_field["deleted"]) && $cstm_field["deleted"] == TRUE) {
			$user_disable_alert = 2;
		} else {
			$user_disable_alert = 1;
		}
	}
}
if (!isset($user_disable_alert)) {
	include_once("./include/api/base.php");
	include_once("./include/api/custom_profile_fields.php");
    phorum_api_custom_profile_field_configure(array (
    	'id'            => NULL,
    	'name'          => 'phorum_mod_pm_alerts_no_alert',
    	'length'        => 3,
    	'html_disabled' => TRUE,
    	'show_in_admin' => TRUE,
	));
    $user_disable_alert = 1;
}

// We build the settings form by using the PhorumInputForm object. When
// creating your own settings screen, you'll only have to change the
// "mod" hidden parameter to the name of your own module.
include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", "Save");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "pm_alerts"); 

// Here we display an error in case one was set by saving 
// the settings before.
if (!empty($error)) {
    echo "$error<br />";
}

// This adds a break line to your form, with a description on it.
// You can use this to separate your form into multiple sections.
$frm->addbreak("Edit settings for the Private Message Alerts module");
$frm->addrow("Disable showing alerts on the PM pages: ", $frm->checkbox("no_alert_on_page_pm", "1", "", $PHORUM["phorum_mod_pm_alerts"]["no_alert_on_page_pm"]));
if ($user_disable_alert == 2) {
	$frm->addmessage("Please add the deleted custom profile field named \"phorum_mod_pm_alerts_no_alert\" if you would like to allow users to disable showing alerts for new private messages.");
} else {
	$frm->addrow("Allow users to disable showing alerts for new private messages: ", $frm->checkbox("allow_users_to_disable_alerts", "1", "", $PHORUM["phorum_mod_pm_alerts"]["allow_users_to_disable_alerts"]));
}
// We are done building the settings screen.
// By calling show(), the screen will be displayed.
$frm->show();
?>