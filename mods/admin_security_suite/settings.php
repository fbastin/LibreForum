<?php
    
	if(!defined("PHORUM_ADMIN")) return;

	global $PHORUM;
	
  if (empty($PHORUM["admin_token"])) $PHORUM["admin_token"] = "";
  
	$ass_menu = "<style>\n.menuon {\nfont-family: Lucida Sans Unicode, Lucida Grand, Verdana, Arial, Helvetica;\nfont-size: 12px;\nfont-weight: bold;\ncolor: White;\nbackground-color: Navy;\ncursor: pointer;\n}";
	$ass_menu .= "\n.menuoff {\nfont-family: Lucida Sans Unicode, Lucida Grand, Verdana, Arial, Helvetica;\nfont-size: 12px;\nfont-weight: bold;\n}\n</style>";
	$ass_menu .= "<script>function menuon(id) {\nif(id) {\nid.className='menuon'\n} else {\n}\n}\nfunction menuoff(id) {\nif(id) {\nid.className='menuoff'\n} else {\n}\n}\n</script>";
	$ass_menu .= "<table cellspacing='0px' cellpadding='3px' class='input-form-table' width='100%'>";
	$ass_menu .= "\n<tr><td class='menuoff' id='ass_menu_settings' width='90px' onmouseover=\"menuon(this)\" onmouseout=\"menuoff(this)\" onclick=\"window.open('./admin.php?module=modsettings&mod=admin_security_suite&phorum_admin_token=".$PHORUM["admin_token"]."', '_parent')\">Edit Settings</td>";
	$ass_menu .= "\n<td class='menuoff' width='10px'>|</td>";
	$ass_menu .= "\n<td class='menuoff' id='ass_menu_search_settings' width='240px' onmouseover=\"menuon(this)\" onmouseout=\"menuoff(this)\" onclick=\"window.open('./admin.php?module=modsettings&mod=admin_security_suite&misc=search_admin_settings&phorum_admin_token=".$PHORUM["admin_token"]."', '_parent')\">Search Saved Settings For Bad Code</td>";
	$ass_menu .= "\n<td>&nbsp;</td></tr></table>";

	//View logs - Removed as of Phorum 5.2 - same funcionality available from "Event Logging" module.

	//Search Admin Settings for Bad Code
	if (isset($_REQUEST["misc"]) && $_REQUEST["misc"] == "search_admin_settings") {
		if (isset($_POST["search_terms"])) {
			$search_terms = $_POST["search_terms"];
		}
		echo $ass_menu;
	    include_once "./include/admin/PhorumInputForm.php";
	    $frm = new PhorumInputForm ("", "post", "Search");
	    $frm->hidden("module", "modsettings");
	    $frm->hidden("mod", "admin_security_suite");
		$frm->hidden("misc", "search_admin_settings");
		$frm->addbreak("Search for bad code in the settings saved from the admin area.");
		$frm->addrow("Search terms (such as \"href=\" for links or \"iframe\" for a hacker favorite):",$frm->text_box("search_terms",$search_terms));
		$frm->show();
		
		if (isset($search_terms)) {
		    echo "<br />";
		    $frm = new PhorumInputForm ("", "post", "Clear Results");
		    $frm->hidden("module", "modsettings");
		    $frm->hidden("mod", "admin_security_suite");
			$frm->hidden("misc", "search_admin_settings");
			$frm->addbreak("Results for \"$search_terms\":");
			$search_terms = explode(" ",strtolower($search_terms));		
			foreach ($PHORUM as $phorum_set => $phorum_values) {
				if (is_array($phorum_values)) {
					foreach ($phorum_values as $sub_set => $sub_values) {
						if (is_array($sub_values)) {
							foreach ($sub_values as $sub_sub_set => $sub_sub_values) {
								if (is_array($sub_sub_values)) {
									foreach ($sub_sub_values as $sub_sub_sub_set => $sub_sub_sub_values) {
										if (is_array($sub_sub_sub_values)) {
											foreach ($sub_sub_sub_values as $sub_sub_sub_sub_set => $sub_sub_sub_sub_values) {
												$search_check = str_replace($search_terms,"ass_found",strtolower($sub_sub_sub_sub_values));
												if ($search_check != strtolower($sub_sub_sub_sub_values)) {
													$frm->addrow("\$PHORUM[\"$phorum_set\"][\"$sub_set\"][\"$sub_sub_set\"][\"$sub_sub_sub_set\"][\"$sub_sub_sub_sub_set\"] = <textarea readonly>".str_replace("<","&lt;",$sub_sub_sub_sub_values)."</textarea>");
												}
											}
										} else {
											$search_check = str_replace($search_terms,"ass_found",strtolower($sub_sub_sub_values));
											if ($search_check != strtolower($sub_sub_sub_values)) {
												$frm->addrow("\$PHORUM[\"$phorum_set\"][\"$sub_set\"][\"$sub_sub_set\"][\"$sub_sub_sub_set\"] = <textarea readonly>".str_replace("<","&lt;",$sub_sub_sub_values)."</textarea>");
											}
										}
									}
								} else {
									$search_check = str_replace($search_terms,"ass_found",strtolower($sub_sub_values));
									if ($search_check != strtolower($sub_sub_values)) {
										$frm->addrow("\$PHORUM[\"$phorum_set\"][\"$sub_set\"][\"$sub_sub_set\"] = <textarea readonly>".str_replace("<","&lt;",$sub_sub_values)."</textarea>");
									}
								}
							}
						} else {
							$search_check = str_replace($search_terms,"ass_found",strtolower($sub_values));
							if ($search_check != strtolower($sub_values)) {
								$frm->addrow("\$PHORUM[\"$phorum_set\"][\"$sub_set\"] = <textarea readonly>".str_replace("<","&lt;",$sub_values)."</textarea>");
							}
						}
					}
				} else {
					$search_check = str_replace($search_terms,"ass_found",strtolower($phorum_values));
					if ($search_check != strtolower($phorum_values)) {
						$frm->addrow("\$PHORUM[\"$phorum_set\"] = <textarea readonly>".str_replace("<","&lt;",$phorum_values)."</textarea>");
					}
				} 
			}		
			$frm->show();
		}
		return; 
	}

	// Manage Users with Admin Access - Removed as of Phorum 5.2 - same funcionality available from "Edit Users"
	
    // save settings
    if(count($_POST)){
		$PHORUM["phorum_mod_admin_security_suite"]["check_title"] = !empty($_POST["check_title"]) ? "1" : "0";		
		$PHORUM["phorum_mod_admin_security_suite"]["true_title"] = $_POST["true_title"];
		$PHORUM["phorum_mod_admin_security_suite"]["warning_sent"] = !empty($_POST["warning_sent"]) ? "1" : "0";
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_captcha"] = !empty($_POST["enable_admin_captcha"]) ? "1" : "0";
		$PHORUM["phorum_mod_admin_security_suite"]["enable_skip_first_admin_captcha"] = !empty($_POST["enable_skip_first_admin_captcha"]) ? "1" : "0";
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_lockout"] = !empty($_POST["enable_admin_lockout"]) ? "1" : "0";		
		$PHORUM["phorum_mod_admin_security_suite"]["max_admin_login_attempts"] = intval($_POST["max_admin_login_attempts"]);
		$PHORUM["phorum_mod_admin_security_suite"]["admin_login_attempt_time"] = intval($_POST["admin_login_attempt_time"])*60;
		$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_interval"] = intval($_POST["admin_lockout_interval"])*60;
		$PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override"] = !empty($_POST["allow_lockout_override"]) ? "1" : "0";		
		$PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override_for_user"] = !empty($_POST["allow_lockout_override_for_user"]) ? "1" : "0";
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_IP_session_lock"] = !empty($_POST["enable_admin_IP_session_lock"]) ? "1" : "0";		
		$PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction"] = !empty($_POST["enable_IP_restriction"]) ? "1" : "0";
		if (isset($_POST["allowed_admin_IPs"])) {
			$temp_allowed_IPs = str_replace(" ","",$_POST["allowed_admin_IPs"]);
			$PHORUM["phorum_mod_admin_security_suite"]["allowed_admin_IPs"] = explode(",",$temp_allowed_IPs);
		}
		$PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override"] = !empty($_POST["enable_IP_restriction_override"]) ? "1" : "0";				
		$PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override_for_user"] = !empty($_POST["enable_IP_restriction_override_for_user"]) ? "1" : "0";				
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_lock"] = !empty($_POST["enable_admin_schedule_lock"]) ? "1" : "0";		
		if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_lock"] != "1") unset($PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_override_list"]);
		$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_start"] = intval($_POST["admin_schedule_start"]);		
		$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_stop"] = intval($_POST["admin_schedule_stop"]);		
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override"] = !empty($_POST["enable_admin_schedule_override"]) ? "1" : "0";		
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override_for_user"] = !empty($_POST["enable_admin_schedule_override_for_user"]) ? "1" : "0";		
		$PHORUM["phorum_mod_admin_security_suite"]["run_once"]="1";

		if(!phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]))){
			$error="Database error while updating settings.";
		} else {
			echo "Settings Updated<br />";
		}
	}	
	
	//check for insecure database configuration file
	if (!empty($_REQUEST["ignore_db_files"])) {
		$PHORUM["phorum_mod_admin_security_suite"]["check_db_files"] = 1;
		phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
	}
	if (empty($PHORUM["phorum_mod_admin_security_suite"]["check_db_files"])) {
		$db_file = "./include/db/config.php";
		$db_sample = "./include/db/config.php.sample";
		$db_warning = "";
		if (file_exists($db_sample)) $db_warning .= "<div style='color: #FF0000;'>The ./include/db/config.php.sample file still exists.  Please delete this file or ensure that does not contain actual password information.</div>";
		if (substr(sprintf('%o', fileperms($db_file)), -4) != "0600") $db_warning .= "<div style='color: #FF0000;'>Your database configuration file is not set to the safest permissions.  ".
			"If you are in a shared hosting environment you should try to set the permissions for the \"./include/db/config.php\" file to 0600 or rw-r--r--.  ".
			"You should immediately check that your forum is functioning properly.  If not, reset the permissions on the file.  ".
			"Otherwise it is best to restrict the access to that file.</div>";
		if (!empty($db_warning)) {
			echo $db_warning."<a href='./admin.php?module=modsettings&mod=admin_security_suite&ignore_db_files=1&phorum_admin_token=".$PHORUM["admin_token"]."'>Ignore database configuration file warnings.</a><br />";
		}
	}
	//Show Settings Form
	echo $ass_menu;
	
	include_once "./include/admin/PhorumInputForm.php";
	$frm = new PhorumInputForm ("", "post", "Save");
	$frm->hidden("module", "modsettings");
	$frm->hidden("mod", "admin_security_suite"); // this is the directory name that the Settings file lives in
	
	if (!isset($PHORUM["phorum_mod_admin_security_suite"]["run_once"])) {
		$frm->hidden("warning_sent", "0");
		$PHORUM["phorum_mod_admin_security_suite"]["check_title"]="1";
		$PHORUM["phorum_mod_admin_security_suite"]["true_title"] = $PHORUM["title"];
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_lockout"] = "1";
		$PHORUM["phorum_mod_admin_security_suite"]["max_admin_login_attempts"] = 5;
		$PHORUM["phorum_mod_admin_security_suite"]["admin_login_attempt_time"] = 300;
		$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_interval"] = 600;
		$PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override"] = "1";
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_captcha"] = "1";
		$PHORUM["phorum_mod_admin_security_suite"]["enable_skip_first_admin_captcha"] = "0";
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_IP_session_lock"] = "1";
		$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_start"] = 9;		
		$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_stop"] = 17;		
		$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override"] = "1";

		phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
	}
		
    if (!empty($error)){
        echo "$error<br />";
    }
    $frm->addbreak("Admin Security Suite Settings");
    $frm->addsubbreak("Enable and Configure Title Monitoring");
    $frm->addrow("Check the forum title against a backup title<br />(This will help prevent hackers from inserting code in your title): ", $frm->checkbox("check_title", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["check_title"],"onclick=\"ass_change_status(this)\""));
    $frm->addrow("Please enter the true title for your forum as a backup:", $frm->text_box("true_title", $PHORUM["phorum_mod_admin_security_suite"]["true_title"],"","","","id=\"true_title\""));
	if ($PHORUM["phorum_mod_admin_security_suite"]["warning_sent"] == "1") {
	    $frm->addrow("<font color=red>A warning has been sent to the admin via e-mail and no more will be sent until this box is unchecked: </font>", $frm->checkbox("warning_sent", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["warning_sent"]));
	}
	$frm->addsubbreak("Admin Login Captcha");
	$row=$frm->addrow("Enable Admin Login Captcha: ", $frm->checkbox("enable_admin_captcha","1","",$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_captcha"]));
	$frm->addhelp($row,"Admin Login Captcha", "Enable this option to require the entry of a random code obscured in a picture in order to login to the admin area.");
	$row=$frm->addrow("Enable Skipping the First Admin Login Captcha: ", $frm->checkbox("enable_skip_first_admin_captcha","1","",$PHORUM["phorum_mod_admin_security_suite"]["enable_skip_first_admin_captcha"]));
	$frm->addhelp($row,"Skipping the First Admin Login Captcha", "Enable this option to allow leaving the captcha field blank on the first attempt at logging in to the admin section.");
   	$frm->addsubbreak("Enable and Configure Admin Lockout");
   	$row=$frm->addrow("Enable admin lockout if a wrong password is given too many times: ", $frm->checkbox("enable_admin_lockout", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["enable_admin_lockout"],"onclick=\"ass_change_status(this)\""));
   	$frm->addhelp($row,"Admin Lockout","Enable this option to restrict the number of failed attempts a specific computer can make when logging in to the Admin area.");
	$frm->addrow("Maximum number of failed logins before lockout:", $frm->text_box("max_admin_login_attempts", $PHORUM["phorum_mod_admin_security_suite"]["max_admin_login_attempts"],"","","","id=\"max_admin_login_attempts\""));
	$frm->addrow("Minutes before failed login count is reset:", $frm->text_box("admin_login_attempt_time", $PHORUM["phorum_mod_admin_security_suite"]["admin_login_attempt_time"]/60,"","","","id=\"admin_login_attempt_time\""));
	$frm->addrow("Minutes to lockout a user after they have hit the maximum number of failed logins<br />(Note this will only lockout an individual IP address.):", $frm->text_box("admin_lockout_interval", $PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_interval"]/60,"","","","id=\"admin_lockout_interval\""));
	$row=$frm->addrow("Allow lockout override code to be sent to the system email address (set in General Settings): ", $frm->checkbox("allow_lockout_override", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override"],"id=\"allow_lockout_override\""));
	$frm->addhelp($row,"Lockout Override for System Admin","This option will allow an override code to be entered if a computer has been locked out of the Admin area.  The override code will then allow that computer X more login attempts.  The override code will be sent to the system email address which is set in the \"General Settings\" section.");
	$row=$frm->addrow("Allow lockout override code to be sent to the email address of the locked-out user: ", $frm->checkbox("allow_lockout_override_for_user", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override_for_user"],"id=\"allow_lockout_override_for_user\""));
	$frm->addhelp($row,"Lockout Override for User with Admin Access","This option will allow an override code to be entered if a computer has been locked out of the Admin area.  The override code will then allow that computer X more login attempts.  The override code will be sent to the user's email address if that user has Admin access.");
	$frm->addsubbreak("Admin IP Address Session Lock");
	$row=$frm->addrow("Enable Admin IP Address Session Lock: ", $frm->checkbox("enable_admin_IP_session_lock", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["enable_admin_IP_session_lock"]));
	$frm->addhelp($row,"Admin IP Address Session Lock","Enable this option to require admin users to relogin if their IP address changes.  This will prevent a hacker from taking a valid cookie and using it to access the Admin area without loggin in.");
	$frm->addsubbreak("Enable and Configure Admin IP Address Restricion");
	$row=$frm->addrow("Enable Admin IP Address Restriction: ", $frm->checkbox("enable_IP_restriction", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction"],"onclick=\"ass_change_status(this)\""));
	$frm->addhelp($row,"Admin IP Address Restriction","Enable this option to restrict all access to the admin area by IP address.  If you don't add an IP adress to the list of allowed IP's, this option will still be disabled.  An IP address not on the list will not even get a login screen.");
	if (isset($PHORUM["phorum_mod_admin_security_suite"]["allowed_admin_IPs"])) $allowed_admin_IPs = implode(",", $PHORUM["phorum_mod_admin_security_suite"]["allowed_admin_IPs"]);
	$frm->addrow("Admin may login from these IP addresses (separated by commas):", $frm->text_box("allowed_admin_IPs", $allowed_admin_IPs,"","","","id=\"allowed_admin_IPs\""));
	$row=$frm->addrow("Enable option to send override code to system email address: ", $frm->checkbox("enable_IP_restriction_override", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override"],"id=\"enable_IP_restriction_override\""));
	$frm->addhelp($row,"IP Address Restriction Override","Enable this option to allow a user with a blocked IP address to request an override, code which is sent to the system email address. When entered, the override code will add the blocked IP to the list of allowed addresses.");
	$row=$frm->addrow("Enable option to send override code to a user with admin access: ", $frm->checkbox("enable_IP_restriction_override_for_user", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override_for_user"],"id=\"enable_IP_restriction_override_for_user\""));
	$frm->addhelp($row,"IP Address Restriction Override For User with Admin Access","Enable this option to allow a user with a blocked IP address to request an override, code which is sent to the users email address if that user has Admin access. When entered, the override code will add the blocked IP to the list of allowed addresses.");
	$frm->addsubbreak("Enable and Configure Scheduled Admin Login Hours - Current Server Time is ".date("g:i A",strtotime($PHORUM["tz_offset"]." hours")));
	$row=$frm->addrow("Enable Scheduled Admin Login Hours: ", $frm->checkbox("enable_admin_schedule_lock","1","",$PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_lock"],"onclick=\"ass_change_status(this)\""));
	$frm->addhelp($row,"Scheduled Admin Login Hours","Enable this option to restrict admin logins to a set time period such as from 9 AM to 5 PM.  During these hours the login screen will appear as normal.  However, outside the set hours, no admin will be able to login. Please note, the time used will be the server's time offset by the time zone set in the \"General Settings\" not the user's computer's time.");
	$hours[(0-$PHORUM["tz_offset"])]="12 AM";
	$hour_min = 1-$PHORUM["tz_offset"];
	$hour_max = 11-$PHORUM["tz_offset"];
	for ($hour=$hour_min; $hour<=$hour_max; $hour++) {
		$hours[$hour] = ($hour+$PHORUM["tz_offset"])." AM";
	}
	$hours[(12-$PHORUM["tz_offset"])]="12 PM";
	$hour_min = 13-$PHORUM["tz_offset"];
	$hour_max = 23-$PHORUM["tz_offset"];
	for ($hour=$hour_min; $hour<=$hour_max; $hour++) {
		$hours[$hour] = ($hour-12+$PHORUM["tz_offset"])." PM";
	}
	$row=$frm->addrow("Scheduled Admin Login Start Time: ",$frm->select_tag("admin_schedule_start",$hours,$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_start"],"id=\"admin_schedule_start\""));
	$frm->addhelp($row,"Scheduled Admin Login Start Time","This is the starting time after which admin users will be able to login to the admin area.");
	$row=$frm->addrow("Scheduled Admin Login Stop Time: ",$frm->select_tag("admin_schedule_stop",$hours,$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_stop"],"id=\"admin_schedule_stop\""));
	$frm->addhelp($row,"Scheduled Admin Login Stop Time","This is the stopping time after which admin users will not be able to login to the admin area.");
	$row=$frm->addrow("Allow schedule override code to be sent to the system email address (set in General Settings): ", $frm->checkbox("enable_admin_schedule_override", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override"],"id=\"enable_admin_schedule_override\""));
	$frm->addhelp($row,"Schedule Override for System Admin","This option will allow an override code to be entered outside of the scheduled admin login hours.  The override code will then allow the system admin to login for one day.  The override code will be sent to the system email address which is set in the \"General Settings\" section.");
	$row=$frm->addrow("Allow schedule override code to be sent to the email address of users with admin access: ", $frm->checkbox("enable_admin_schedule_override_for_user", "1", "", $PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override_for_user"],"id=\"enable_admin_schedule_override_for_user\""));
	$frm->addhelp($row,"Schedule Override for User with Admin Access","This option will allow an override code to be entered outside of the scheduled admin login hours.  The override code will then allow the requesting user to login for one day.  The override code will be sent to the user's email address if that user has Admin access.");
	$frm->show();
	echo "<script>function ass_disable() {";
	if ($PHORUM["phorum_mod_admin_security_suite"]["check_title"] != "1") {
		echo "\ndocument.getElementById(\"true_title\").disabled=true;";
	}
	if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_lockout"] != "1") {
		echo "\ndocument.getElementById(\"max_admin_login_attempts\").disabled=true;";
		echo "\ndocument.getElementById(\"admin_login_attempt_time\").disabled=true;";
		echo "\ndocument.getElementById(\"admin_lockout_interval\").disabled=true;";
		echo "\ndocument.getElementById(\"allow_lockout_override\").disabled=true;";
		echo "\ndocument.getElementById(\"allow_lockout_override_for_user\").disabled=true;";
	}
	if ($PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction"] != "1") {
		echo "\ndocument.getElementById(\"allowed_admin_IPs\").disabled=true;";
		echo "\ndocument.getElementById(\"enable_IP_restriction_override\").disabled=true;";
		echo "\ndocument.getElementById(\"enable_IP_restriction_override_for_user\").disabled=true;";
	}
	if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_lock"] != "1") {
		echo "\ndocument.getElementById(\"admin_schedule_start\").disabled=true;";
		echo "\ndocument.getElementById(\"admin_schedule_stop\").disabled=true;";
		echo "\ndocument.getElementById(\"enable_admin_schedule_override\").disabled=true;";
		echo "\ndocument.getElementById(\"enable_admin_schedule_override_for_user\").disabled=true;";
	}
	echo "\n}";
	echo "\nfunction ass_change_status(id) {";
	echo "\nif (id.name == \"check_title\") {";
	echo "\ndocument.getElementById(\"true_title\").disabled=!id.checked;";
	echo "\n}";	
	echo "\nif (id.name == \"enable_admin_lockout\") {";
	echo "\ndocument.getElementById(\"max_admin_login_attempts\").disabled=!id.checked;";
	echo "\ndocument.getElementById(\"admin_login_attempt_time\").disabled=!id.checked;";
	echo "\ndocument.getElementById(\"admin_lockout_interval\").disabled=!id.checked;";
	echo "\ndocument.getElementById(\"allow_lockout_override\").disabled=!id.checked;";
	echo "\ndocument.getElementById(\"allow_lockout_override_for_user\").disabled=!id.checked;";
	echo "\n}";
	echo "\nif (id.name == \"enable_IP_restriction\") {";
	echo "\ndocument.getElementById(\"allowed_admin_IPs\").disabled=!id.checked;";
	echo "\ndocument.getElementById(\"enable_IP_restriction_override\").disabled=!id.checked;";
	echo "\ndocument.getElementById(\"enable_IP_restriction_override_for_user\").disabled=!id.checked;";
	echo "\n}";
	echo "\nif (id.name == \"enable_admin_schedule_lock\") {";
	echo "\ndocument.getElementById(\"admin_schedule_start\").disabled=!id.checked;";
	echo "\ndocument.getElementById(\"admin_schedule_stop\").disabled=!id.checked;";
	echo "\ndocument.getElementById(\"enable_admin_schedule_override\").disabled=!id.checked;";
	echo "\ndocument.getElementById(\"enable_admin_schedule_override_for_user\").disabled=!id.checked;";
	echo "\n}";
	echo "\n}\nwindow.onload=ass_disable()\n</script>";
?>