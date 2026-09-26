<?php

if (!defined("PHORUM")) return;

function phorum_mod_pm_alerts_pm_sent($pm_message, $recipients) {

	global $PHORUM;
	
	$timestamp = time();
	
	// set/update the timestamp for each recipient's last pm
	foreach ($recipients as $user_id) {
		$PHORUM["phorum_mod_pm_alerts"]["last_pm"][$user_id]["timestamp"] = $timestamp;
	}
	phorum_db_update_settings(array("phorum_mod_pm_alerts" => $PHORUM["phorum_mod_pm_alerts"]));
	
	return $pm_message;
}

function phorum_mod_pm_alerts_before_footer() {
	
	global $PHORUM;
	
	// if the user is not logged in there is no need to continue	
	if (empty($PHORUM["user"])) return;

	// if the admin doesn't want to show alerts on the pm pages there is no need to continue
	if (!empty($PHORUM["phorum_mod_pm_alerts"]["no_alert_on_page_pm"]) && phorum_page == "pm") return;
	
	// if users are allowed to disable alerts and this one has then there is no need to continue
	if (!empty($PHORUM["phorum_mod_pm_alerts"]["allow_users_to_disable_alerts"]) && !empty($PHORUM["user"]["phorum_mod_pm_alerts_no_alert"])) return;

	$user_id = $PHORUM["user"]["user_id"];
	
	// if there is no new message to alert there is no need to continue
	if (empty($PHORUM["phorum_mod_pm_alerts"]["last_pm"][$user_id]["timestamp"])
		|| (!empty($PHORUM["phorum_mod_pm_alerts"]["last_alert"][$user_id]["timestamp"])
			&& $PHORUM["phorum_mod_pm_alerts"]["last_pm"][$user_id]["timestamp"] 
				< $PHORUM["phorum_mod_pm_alerts"]["last_alert"][$user_id]["timestamp"]))
		return;
	
	// save the last alert data so that the user is not constantly bothered.
	$PHORUM["phorum_mod_pm_alerts"]["last_alert"][$user_id]["timestamp"] = time();
	phorum_db_update_settings(array("phorum_mod_pm_alerts" => $PHORUM["phorum_mod_pm_alerts"]));
	
	$pm_path = $PHORUM["DATA"]["URL"]["PM"];
	$alert = $PHORUM["DATA"]["LANG"]["phorum_mod_pm_alerts"]["Alert"];
	
	// add the javascript for alerting the user to the page
	print "<script>\n".
		"goto_pm = confirm(\"$alert\");\n".
		"if (goto_pm === true) window.location=\"$pm_path\";\n".
		"</script>";
	return;
}

function phorum_mod_pm_alerts_tpl_cc_usersettings($profile)
{
	global $PHORUM;
	
	//show the option to disable alerts on the forum settings page
	if (!empty($profile["PANEL"]) && $profile["PANEL"] == "forum") {
		//only if the option to disable alerts has been enabled
		if (!empty($PHORUM["phorum_mod_pm_alerts"]["allow_users_to_disable_alerts"])) {
			foreach ($PHORUM["PROFILE_FIELDS"] as $key => $cstm_field) {
				if ($key === 'num_fields' || !is_array($cstm_field)) continue;   // compteur entier rangé parmi les champs (cœur : même garde)
				if ($cstm_field["name"] == "phorum_mod_pm_alerts_no_alert") {
					if (!empty($cstm_field["deleted"]) && $cstm_field["deleted"] == TRUE) {
						$user_disable_alert = 2;
					} else {
						$user_disable_alert = 1;
					}
				}
			}
			//only if the custom profile field has been installed and not deleted
			if ($user_disable_alert == 1) {
				$currval = isset($profile["phorum_mod_pm_alerts_no_alert"]);
				print "<dt>".$PHORUM["DATA"]["LANG"]["phorum_mod_pm_alerts"]["DisableAlerts"];
				?></dt>
					<dd><select name="phorum_mod_pm_alerts_no_alert">
						<option value="1"<?php
						if ($PHORUM["DATA"]["PROFILE"]["phorum_mod_pm_alerts_no_alert"] == 1) {
							?> selected="selected"<?php
						}
				print ">".$PHORUM["DATA"]["LANG"]["phorum_mod_pm_alerts"]["DisableAlertsYes"];
				?></option>
				<option value="0"<?php
				if ($PHORUM["DATA"]["PROFILE"]["phorum_mod_pm_alerts_no_alert"] == 0) {
					?> selected="selected"<?php
				}
				print ">".$PHORUM["DATA"]["LANG"]["phorum_mod_pm_alerts"]["DisableAlertsNo"];
				?></option></select></dd>
				<?php
			}
		}
	}

	return $profile;
	
}

?>
