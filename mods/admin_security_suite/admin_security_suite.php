<?php

if(!defined("PHORUM")) return;

// Load event_logging constants (required for PHP 8).
if (file_exists('./mods/event_logging/constants.php')) {
    require_once './mods/event_logging/constants.php';
}

//log an event if possible
function phorum_mod_admin_security_suite_log_event ($data) {
	//$data should be in an array of message, detail (can be null), loglevel (null for default "alert"), category (null for default "security")
	$data["source"] = "Admin Security Suite";
	if (empty($data["loglevel"])) $data["loglevel"] = EVENTLOG_LVL_ALERT;
	if (empty($data["category"])) $data["category"] = EVENTLOG_CAT_SECURITY;
	if (function_exists('event_logging_writelog')) {
		event_logging_writelog($data);
	}
}

function phorum_mod_admin_security_suite_common_pre () {

	global $PHORUM;

	if ($PHORUM["phorum_mod_admin_security_suite"]["check_title"] == "1") {
		if ( !defined( "PHORUM_ADMIN" ) ) {
			$current_title = $PHORUM["title"];
			$true_title = $PHORUM["phorum_mod_admin_security_suite"]["true_title"];
			$admin_email = $PHORUM["system_email_from_address"];
			if ($current_title != $true_title) {
				$PHORUM["title"] = $true_title;
				if ($PHORUM["phorum_mod_admin_security_suite"]["warning_sent"] == "0") {
		
					$subject = "$true_title has been changed.";
					$body = "The title of your forum, \"$true_title\", has been changed.  This may be a hacker or you may have simply forgotten to change the title in the Admin Update module.";
					$body .= " If you did not change the title of your forum, please make sure you are using the latest version of Phorum and change the title in the General Settings page of the Admin area.";
					$phorum_major_version = substr(PHORUM, 0, strpos(PHORUM, '.'));
					$mailer = "Phorum" . $phorum_major_version;
					$mailheader = "Content-Type: text/plain; ";
					if (!empty($PHORUM["DATA"]["CHARSET"])) $mailheader .= " charset={$PHORUM["DATA"]["CHARSET"]}";
					if (!empty($PHORUM["DATA"]["MAILENCODING"])) $mailheader .= "\nContent-Transfer-Encoding: {$PHORUM["DATA"]["MAILENCODING"]}";
					$mailheader .= "\nX-Mailer: $mailer\n\n";
					
					mail($admin_email, $subject, $body, $mailheader."From: $admin_email");
					phorum_mod_admin_security_suite_log_event(array("message" => $subject, "details" => $body));
					$PHORUM["phorum_mod_admin_security_suite"]["warning_sent"] = "1";
					phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
				}
			}
		}
	}
}

function phorum_mod_admin_security_suite_admin_pre ($module) {
	
	//do not run security checks for installs or upgrades
	if ($module == "install" || $module == "upgrade") return $module;
	
	global $PHORUM;

	include_once("./include/api/base.php");
	include_once("./include/api/user.php");

	if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_lock"] == "1") {
		if ($_POST["send_admin_schedule_override_code"] == 1) {
			srand((double)microtime()*1000000);  
			$randnum = rand(0,100);
			$set_time = time();
			$generated_code["override_time"] = $set_time;
			$generated_code["override_code"] = $set_time.$randnum;
			
			$admin_email = $PHORUM["system_email_from_address"];
			$subject = "Admin schedule override for ".$_SERVER["REMOTE_ADDR"];
			$body = "The admin schedule override code for ".$_SERVER["REMOTE_ADDR"]." is: ".$generated_code["override_code"].".";
			$body .= "\n\nEntering this override code will allow the above IP address to login for one hour.";
			$phorum_major_version = substr(PHORUM, 0, strpos(PHORUM, '.'));
			$mailer = "Phorum" . $phorum_major_version;
			$mailheader ="Content-Type: text/plain; charset={$PHORUM["DATA"]["CHARSET"]}\nContent-Transfer-Encoding: {$PHORUM["DATA"]["MAILENCODING"]}\nX-Mailer: $mailer$msgid\n";

			if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override"] == "1") {
				mail($admin_email, $subject, $body, $mailheader."From: $admin_email");
				phorum_mod_admin_security_suite_log_event(array("message" => $subject." has been emailed", "loglevel" => EVENTLOG_LVL_WARNING));
			}
			
			if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override_for_user"] == "1") {
				if (isset($_POST["username"])) {
					$user_id = phorum_api_user_search("username",$_POST["username"]);
					$user_info = phorum_api_user_get($user_id,TRUE);
					if (strtolower($user_info["email"]) != strtolower($admin_email) || $PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override"]!="1") {
						if ($user_info["admin"]) {
							mail($user_info["email"], $subject, $body, $mailheader."From: $admin_email");
							phorum_mod_admin_security_suite_log_event(array("message" => $subject." has been emailed to the user ".$_POST["username"], "loglevel" => EVENTLOG_LVL_WARNING));
						}
					}
				}			
			}
			$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_override_list"][$_SERVER["REMOTE_ADDR"]] = $generated_code;
			phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
		}

		if (date(G) < $PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_start"] || date(G) >= $PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_stop"]) {
			if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override"] == "1" || $PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override_for_user"] = "1") {
				if (isset($_POST["override_code"]) && $_POST["override_code"] == $PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_override_list"][$_SERVER["REMOTE_ADDR"]]["override_code"]) {
					$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_override_list"][$_SERVER["REMOTE_ADDR"]]["override_allowed"] = "1";
					phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
				} else if (isset($_POST["override_code"]) && $_POST["override_code"] =! $PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_override_list"][$_SERVER["REMOTE_ADDR"]]["override_code"]) {
					phorum_mod_admin_security_suite_log_event(array("message" => "Incorrect admin schedule override code entered from ".$_SERVER["REMOTE_ADDR"]));
				}
				if ($PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_override_list"][$_SERVER["REMOTE_ADDR"]]["override_allowed"] == "1" 
					&& time() < strtotime("+1 hour",$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_override_list"][$_SERVER["REMOTE_ADDR"]]["override_time"])) {
					$override_success = 1;
					phorum_mod_admin_security_suite_log_event(array("message" => "Admin schedule override code accepted for ".$_SERVER["REMOTE_ADDR"], "loglevel" => EVENTLOG_LVL_WARNING));
				}
			}
			if (!$override_success) {
				ob_start();
				echo "<b><font color=red>Admin login is only availble during certain hours.</font></b>";
				if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override"] == "1") {
					echo "\n<form action='".$PHORUM["http_path"]."/admin.php' method='post'>";
					echo "\n<input type='hidden' name='send_admin_schedule_override_code' value='1'>";
					echo "\n<input type='hidden' name='target' value='".$PHORUM["http_path"]."/admin.php'>";
					if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override_for_user"] == "1") {
						echo "\n<input type='text' name='username' size='30' value=''>&nbsp;";
					}
					echo "\n<input type='submit' value='Send Override Code";
					if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_schedule_override_for_user"] != "1") {
						echo " to Admin Email";
					}
					echo "'></form>";
					echo "\n<br /><form action='".$PHORUM["http_path"]."/admin.php' method='post'>";
					echo "\n<input type='hidden' name='target' value='".$PHORUM["http_path"]."/admin.php'>";
					echo "\n<input type='text' name='override_code' size='30' value=''>&nbsp;";
					echo "<input type='submit' value='Override'></form>";
				}
				phorum_mod_admin_security_suite_log_event(array("message" => "Admin login screen opened during restricted hours from ".$_SERVER["REMOTE_ADDR"], "loglevel" => EVENTLOG_LVL_INFO));
				ob_end_flush();
				exit();
			} else {
				$checktime = strtotime("+1 hour",$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_override_list"][$_SERVER["REMOTE_ADDR"]]["override_time"]);
				if (time() >= ($checktime - (60 * 15)) && time() <= $checktime) {
					ob_start();
					if ($PHORUM["phorum__mod_admin_security_suite"]["15_minute_warning_sent"] != "1") {
						echo "<script>function timed_warning(time_remaining) {\nif (time_remaining > 0) {\ndocument.write('<div id=\"timed_warning_div\" style=\"position:absolute; left:180px; top:9px; \"><font color=\"red\"><b>Less than '+time_remaining+' minutes left until access to the admin area is closed.</b></font></div>');";
						echo "\nt=setTimeout(\"warning_countdown(\"+(time_remaining-1)+\")\",60000);\n} else {\ndocument.write('<div id=\"timed_warning_div\" style=\"position:absolute; left:180px; top:9px; \"><font color=\"red\"><b>The admin area is now closed.  Changes you make on this screen will not be saved.</b></font></div>');";
						echo "\n}\n}\nfunction warning_countdown(time_remaining) {\nif (time_remaining > 0) {\ndocument.getElementById(\"timed_warning_div\").innerHTML='<font color=\"red\"><b>Less than '+time_remaining+' minutes left until access to the admin area is closed.</b></font>';";
						echo "\nt=setTimeout(\"warning_countdown(\"+(time_remaining-1)+\")\",60000);\n} else {\ndocument.getElementById(\"timed_warning_div\").innerHTML='<font color=\"red\"><b>The admin area is now closed.  Changes you make on this screen will not be saved.</b></font>';";
						echo "\n}\n}\nwindow.onload=timed_warning(".(date("i",($checktime - time()))-0).")\n</script>";
					}
				}
			}
		} else {
			$checktime = strtotime(date("m/d/Y")." ".$PHORUM["phorum_mod_admin_security_suite"]["admin_schedule_stop"].":00");//." +16 hours +4 minutes");
			if (time() >= ($checktime - (60 * 15)) && time() <= $checktime) {
				ob_start();
				if ($PHORUM["phorum__mod_admin_security_suite"]["15_minute_warning_sent"] != "1") {
					echo "<script>function timed_warning(time_remaining) {\nif (time_remaining > 0) {\ndocument.write('<div id=\"timed_warning_div\" style=\"position:absolute; left:180px; top:9px; \"><font color=\"red\"><b>Less than '+time_remaining+' minutes left until access to the admin area is closed.</b></font></div>');";
					echo "\nt=setTimeout(\"warning_countdown(\"+(time_remaining-1)+\")\",60000);\n} else {\ndocument.write('<div id=\"timed_warning_div\" style=\"position:absolute; left:180px; top:9px; \"><font color=\"red\"><b>The admin area is now closed.  Changes you make on this screen will not be saved.</b></font></div>');";
					echo "\n}\n}\nfunction warning_countdown(time_remaining) {\nif (time_remaining > 0) {\ndocument.getElementById(\"timed_warning_div\").innerHTML='<font color=\"red\"><b>Less than '+time_remaining+' minutes left until access to the admin area is closed.</b></font>';";
					echo "\nt=setTimeout(\"warning_countdown(\"+(time_remaining-1)+\")\",60000);\n} else {\ndocument.getElementById(\"timed_warning_div\").innerHTML='<font color=\"red\"><b>The admin area is now closed.  Changes you make on this screen will not be saved.</b></font>';";
					echo "\n}\n}\nwindow.onload=timed_warning(".(date("i",($checktime - time()))-0).")\n</script>";
				}
			}
		}
	}
	
	if (isset($PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction"]) && $PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction"] == "1") {
		if (isset($_POST["IP_restriction_override_check"]) && $_POST["IP_restriction_override_check"] == 1) {
			if (isset($_POST["override_code"]) && $_POST["override_code"] == $PHORUM["phorum_mod_admin_security_suite"]["IP_restriction_override_code"][$_SERVER["REMOTE_ADDR"]]) {
				array_push($PHORUM["phorum_mod_admin_security_suite"]["allowed_admin_IPs"],$_SERVER["REMOTE_ADDR"]);
				unset($PHORUM["phorum_mod_admin_security_suite"]["IP_restriction_override_code"][$_SERVER["REMOTE_ADDR"]]);
				phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
				phorum_mod_admin_security_suite_log_event(array("message" => "IP restriction override code accepted for ".$_SERVER["REMOTE_ADDR"], "loglevel" => EVENTLOG_LVL_WARNING));
			} else if (isset($_POST["override_code"]) && $_POST["override_code"] != $PHORUM["phorum_mod_admin_security_suite"]["IP_restriction_override_code"][$_SERVER["REMOTE_ADDR"]]) {
				phorum_mod_admin_security_suite_log_event(array("message" => "Incorrect IP restriction override code entered from ".$_SERVER["REMOTE_ADDR"]));
			}
		} elseif (isset($_POST["IP_restriction_override_check"]) && $_POST["IP_restriction_override_check"] == 2) {
			srand((double)microtime()*1000000);  
			$randnum = rand(0,100);
			$generated_code = time().$randnum;
			
			$admin_email = $PHORUM["system_email_from_address"];
			$subject = "IP restriction override for ".$_SERVER["REMOTE_ADDR"];
			$body = "The IP restriction override code for ".$_SERVER["REMOTE_ADDR"]." is: $generated_code";
			$body .= "\n\nEntering this override code will add the above IP address to your list of allowed IP addresses.";
			$phorum_major_version = substr(PHORUM, 0, strpos(PHORUM, '.'));
			$mailer = "Phorum" . $phorum_major_version;
			$mailheader ="Content-Type: text/plain; charset={$PHORUM["DATA"]["CHARSET"]}\nContent-Transfer-Encoding: {$PHORUM["DATA"]["MAILENCODING"]}\nX-Mailer: $mailer$msgid\n";
			
			if ($PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override_for_user"] == "1") {		
				mail($admin_email, $subject, $body, $mailheader."From: $admin_email");
				phorum_mod_admin_security_suite_log_event(array("message" => $subject." has been emailed", "loglevel" => EVENTLOG_LVL_WARNING));
			}
			
			if ($PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override_for_user"] == "1") {
				if (isset($_POST["username"])) {
					$user_id = phorum_api_user_search("username",$_POST["username"]);
					$user_info = phorum_api_user_get($user_id,TRUE);
					if (strtolower($user_info["email"]) != strtolower($admin_email) || $PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override"]!="1") {
						if ($user_info["admin"]) {
							mail($user_info["email"], $subject, $body, $mailheader."From: $admin_email");
							phorum_mod_admin_security_suite_log_event(array("message" => $subject." has been emailed to the user ".$_POST["username"], "loglevel" => EVENTLOG_LVL_WARNING));
						}
					}
				}			
			}
			
			$PHORUM["phorum_mod_admin_security_suite"]["IP_restriction_override_code"][$_SERVER["REMOTE_ADDR"]] = $generated_code;
			phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
		}
			
		if (!isset($PHORUM["phorum_mod_admin_security_suite"]["allowed_admin_IPs"])) {
			$enable_login = 1;
		} else {
			foreach ($PHORUM["phorum_mod_admin_security_suite"]["allowed_admin_IPs"] as $allowed_IP) {
				if ($_SERVER["REMOTE_ADDR"] == $allowed_IP) {
					$enable_login = 1;
				}
			}
		}
		if ($enable_login != 1) {
			ob_start();
			echo "<b><font color=red>Admin login is only availble for allowed IP addresses. ".$_SERVER["REMOTE_ADDR"]." is not allowed.</font></b>";
			if ($PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override"] == "1") {
				echo "\n<form action='".$PHORUM["http_path"]."/admin.php' method='post'>";
				echo "\n<input type='hidden' name='IP_restriction_override_check' value='2'>";
				echo "\n<input type='hidden' name='target' value='".$PHORUM["http_path"]."/admin.php'>";
				if ($PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override_for_user"] == "1") {
					echo "\n<input type='text' name='username' size='30' value=''>&nbsp;";
				}
				echo "\n<input type='submit' value='Send Override Code";
				if ($PHORUM["phorum_mod_admin_security_suite"]["enable_IP_restriction_override_for_user"] != "1") {
					echo " to Admin Email";
				}
				echo "'></form>";
				echo "\n<br /><form action='".$PHORUM["http_path"]."/admin.php' method='post'>";
				echo "\n<input type='hidden' name='IP_restriction_override_check' value='1'>";
				echo "\n<input type='hidden' name='target' value='".$PHORUM["http_path"]."/admin.php'>";
				echo "\n<input type='text' name='override_code' size='30' value=''>&nbsp;";
				echo "<input type='submit' value='Override'></form>";
				phorum_mod_admin_security_suite_log_event(array("message" => "Admin login screen opened from restricted IP address (".$_SERVER["REMOTE_ADDR"].")", "loglevel" => EVENTLOG_LVL_INFO));
			}		
			ob_end_flush();
			exit();
		}
	}
	
	if (isset($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_lockout"]) && $PHORUM["phorum_mod_admin_security_suite"]["enable_admin_lockout"] == "1") {

		if (isset($_POST["username"]) && isset($_POST["password"])) {
			$check_login = "";
			$username = $_POST["username"];
			if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_captcha"] == "1" && (!isset($PHORUM["phorum_mod_admin_security_suite"]["enable_skip_first_admin_captcha"]) || (isset($PHORUM["phorum_mod_admin_security_suite"]["enable_skip_first_admin_captcha"]) && ($PHORUM["phorum_mod_admin_security_suite"]["enable_skip_first_admin_captcha"] != "1"
					|| !empty($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"])))) && intval($_POST["submitted_captcha"]) != $PHORUM["phorum_mod_admin_security_suite"]["admin_captcha_list"][$_SERVER["REMOTE_ADDR"]]["captcha_code"]) {
				$check_login = "Failure";
			} 
			$user_id = phorum_api_user_authenticate(PHORUM_ADMIN_SESSION,$_POST["username"], $_POST["password"]);
			if (!$user_id) {
				$check_login = "Failure";
			} else {
				$user_info = phorum_api_user_get($user_id, TRUE);
				if (!$user_info["admin"]) {
					$check_login = "Failure";
				}
			}
			if ($check_login == "Failure") {
				phorum_mod_admin_security_suite_log_event(array("message" => "Failed login attempt from ".$_SERVER["REMOTE_ADDR"]." with user ".$_POST["username"]));
				$max_attempts = $PHORUM["phorum_mod_admin_security_suite"]["max_admin_login_attempts"];
				$attempt_time = $PHORUM["phorum_mod_admin_security_suite"]["admin_login_attempt_time"];
				if (empty($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"])) {
					$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"] = 1;
					$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_time"] = time();
				} else {
					if (($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_time"] + $attempt_time) > time()) {
						$lockout_count = $PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"];
						$lockout_count = $lockout_count + 1;
						if ($lockout_count >= $max_attempts) {
							$lockout_time = time();
							$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list"][$_SERVER['REMOTE_ADDR']] = $lockout_time;
							srand((double)microtime()*1000000);  
							$randnum = rand(0,100);
							$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list_override"][$_SERVER['REMOTE_ADDR']] = $lockout_time.$randnum;
							$admin_email = $PHORUM["system_email_from_address"];
							$subject = "User \"$username\" locked out of admin section.";
							$body = "User \"".$_POST["username"]."\" has failed to login to the admin section after $max_attempts attempts and is now locked out.";
							phorum_mod_admin_security_suite_log_event(array("message" => $subject, "details" => $body));
							$body .= "\nThe override code for that user to login is ".$lockout_time.$randnum.".";
							$phorum_major_version = substr(PHORUM, 0, strpos(PHORUM, '.'));
							$mailer = "Phorum" . $phorum_major_version;
							$mailheader ="Content-Type: text/plain; charset={$PHORUM["DATA"]["CHARSET"]}\nContent-Transfer-Encoding: {$PHORUM["DATA"]["MAILENCODING"]}\nX-Mailer: $mailer$msgid\n";

							if ($PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override"] == "1") {
								mail($admin_email, $subject, $body, $mailheader."From: $admin_email");
								phorum_mod_admin_security_suite_log_event(array("message" => $subject." has been emailed", "loglevel" => EVENTLOG_LVL_WARNING));
							}
							if ($PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override_for_user"] == "1") {
								$user_id = phorum_api_user_search("username",$_POST["username"]);
								$user_info = phorum_api_user_get($user_id, TRUE);
								if (strtolower($user_info["email"]) != strtolower($admin_email) || $PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override"]!="1") {
									if ($user_info["admin"]) {
										mail($user_info["email"], $subject, $body, $mailheader."From: $admin_email");
										phorum_mod_admin_security_suite_log_event(array("message" => $subject." has been emailed to the user ".$_POST["username"], "loglevel" => EVENTLOG_LVL_WARNING));
									}
								}
							}
							$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"] = $lockout_count;	
							phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
							ob_start();
							echo "<b><font color=red>Admin login has been disabled due to a number of failed login attempts</font></b>";
							if ($PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override"] == "1" || $PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override_for_user"] == "1") {
								echo "\n<form action='".$PHORUM["http_path"]."/admin.php' method='post'>";
								echo "\n<input type='hidden' name='target' value='".$PHORUM["http_path"]."/admin.php'>";
								echo "\n<input type='text' name='override_code' size='30' value=''>&nbsp;";
								echo "<input type='submit' value='Override'>";
							}
							ob_end_flush();
							exit();
						} else {
							$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"] = $lockout_count;	
						}
					} else {
						$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"] = 1;
						$PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_time"] = time();
					}
				}
				unset($_POST["username"]);
			} else {
				unset($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list"][$_SERVER['REMOTE_ADDR']]);
				unset($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"]);
				unset($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_time"]);
			}	
		phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
		}
		
		$lockout_interval = $PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_interval"];

		if (isset($_POST["override_code"])) {
			$override_code = $_POST["override_code"];
			if (!empty($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list"][$_SERVER['REMOTE_ADDR']]) && $override_code == $PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list_override"][$_SERVER['REMOTE_ADDR']]) {
				unset($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list"][$_SERVER['REMOTE_ADDR']]);
				unset($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"]);
				unset($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_time"]);
				phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
				phorum_mod_admin_security_suite_log_event(array("message" => "Admin lockout override code accepted for ".$_SERVER["REMOTE_ADDR"], "loglevel" => EVENTLOG_LVL_WARNING));
			} else if (!empty($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list"][$_SERVER['REMOTE_ADDR']]) && $override_code != $PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list_override"][$_SERVER['REMOTE_ADDR']]) {
				phorum_mod_admin_security_suite_log_event(array("message" => "Incorrect admin lockout override code entered from ".$_SERVER["REMOTE_ADDR"]));
			}
		}

		if (!empty($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list"][$_SERVER['REMOTE_ADDR']])) {
			if (($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list"][$_SERVER['REMOTE_ADDR']] + $lockout_interval) > time()) {
			    ob_start();
				echo "<b><font color=red>Admin login has been disabled due to a number of failed login attempts</font></b>";
				if ($PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override"] == "1" || $PHORUM["phorum_mod_admin_security_suite"]["allow_lockout_override_for_user"] == "1") {
					echo "\n<form action='".$PHORUM["http_path"]."/admin.php' method='post'>";
					echo "\n<input type='hidden' name='target' value='".$PHORUM["http_path"]."/admin.php'>";
					echo "\n<input type='text' name='override_code' size='30' value=''>&nbsp;";
					echo "<input type='submit' value='Override'>";
				}		
				ob_end_flush();
				exit();
			} else {
				unset($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_list"][$_SERVER['REMOTE_ADDR']]);
				unset($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"]);
				unset($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_time"]);
				phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
			}
		}
	}

	if (isset($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_captcha"]) && $PHORUM["phorum_mod_admin_security_suite"]["enable_admin_captcha"] == "1") {
		if ($module == "login") {
			if(isset($_POST["username"]) && isset($_POST["password"])){
	        	$user_id=phorum_api_user_authenticate(PHORUM_ADMIN_SESSION,$_POST["username"], $_POST["password"]);
	        	if (intval($_POST["submitted_captcha"]) == $PHORUM["phorum_mod_admin_security_suite"]["admin_captcha_list"][$_SERVER["REMOTE_ADDR"]]["captcha_code"]
				|| (isset($PHORUM["phorum_mod_admin_security_suite"]["enable_skip_first_admin_captcha"]) && $PHORUM["phorum_mod_admin_security_suite"]["enable_skip_first_admin_captcha"] == "1"
					&& empty($PHORUM["phorum_mod_admin_security_suite"]["admin_lockout_log"][$_SERVER['REMOTE_ADDR']]["lockout_count"]))) {
		           if ($user_id &&
			            phorum_api_user_set_active_user(PHORUM_ADMIN_SESSION, $user_id) &&
				        phorum_api_user_session_create(PHORUM_ADMIN_SESSION)) {
						
						if ($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_IP_session_lock"] == "1") {
							$PHORUM["phorum_mod_admin_security_suite"]["admin_IP_session_locks"][$user_id] = $_SERVER["REMOTE_ADDR"];
						}
		                unset($PHORUM["phorum_mod_admin_security_suite"]["admin_captcha_list"][$_SERVER["REMOTE_ADDR"]]);
		        		phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
		        		$captcha_dir = opendir("./mods/admin_security_suite/tmp_captchas");
		        		while ($captcha_file = readdir($captcha_dir)) {
		        			if (strstr($captcha_file,".png")) unlink("./mods/admin_security_suite/tmp_captchas/".$captcha_file);
		        		}
		        		closedir($captcha_dir);

		                if(!empty($_POST["target"])){
		                    phorum_redirect_by_url($_POST['target']);
		                } else {
		                    phorum_redirect_by_url($PHORUM["admin_http_path"]);
		                }
		        		exit();
		        	} else {
						phorum_hook("failed_login", array(
			                "username" => $_POST["username"],
			                "password" => $_POST["password"],
			                "location" => "admin"
			            ));
					phorum_mod_admin_security_suite_log_event(array("message" => "Failed admin login attempt from ".$_SERVER["REMOTE_ADDR"]." by the user ".$_POST["username"]));
		        	}
	        	} else {
	        	   	unset($GLOBALS["PHORUM"]["user"]);
	        	   	if (is_file("./mods/admin_security_suite/tmp_captchas/".$PHORUM["phorum_mod_admin_security_suite"]["admin_captcha_list"][$_SERVER["REMOTE_ADDR"]]["captcha_img"])) {
						unlink("./mods/admin_security_suite/tmp_captchas/".$PHORUM["phorum_mod_admin_security_suite"]["admin_captcha_list"][$_SERVER["REMOTE_ADDR"]]["captcha_img"]);
		        	}
				phorum_mod_admin_security_suite_log_event(array("message" => "Incorrect admin captcha entered from ".$_SERVER["REMOTE_ADDR"]." by the user ".$_POST["username"], "loglevel" => EVENTLOG_LVL_WARNING));
			}	    
		    }

			srand((double)microtime()*1000000);  
			$randnum = rand(100,2000);
			$captcha_img = "";

			if (function_exists('imagecreate')) {
				$captcha_image = imagecreate(120,40);
				$bg_color = imagecolorallocate($captcha_image, 245, 245, 245);
				$text_color = imagecolorallocate($captcha_image, 20, 60, 20);
				for ($x=0; $x<15; $x++) {
					$rand_color = imagecolorallocate($captcha_image, rand(180,230), rand(180,230), rand(180,230));
					imageline ($captcha_image, rand(0,120), rand(0,40), rand(0,120), rand(0,40), $rand_color);
				}
				// Font 5 is 9px wide, 15px high. A 4-digit number is 36px wide.
				// Centering: (120 - 36) / 2 = 42 for X. (40 - 15) / 2 = 12 for Y.
				imagestring($captcha_image,5,42,12,$randnum,$text_color);
				$captcha_img = time().".png";
				imagepng($captcha_image,"./mods/admin_security_suite/tmp_captchas/".$captcha_img);
				imagedestroy($captcha_image);
			}
			$captcha_info["captcha_code"]=$randnum;
			$captcha_info["captcha_img"]=$captcha_img;
			$PHORUM["phorum_mod_admin_security_suite"]["admin_captcha_list"][$_SERVER["REMOTE_ADDR"]] = $captcha_info;
			phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
			ob_start();
			include_once "./include/admin/header.php";
		    include_once "./include/admin/PhorumInputForm.php";
			$_REQUEST["module"]="modsettings";
		    $frm = new PhorumInputForm ("", "post");
	    
		    if(count($_REQUEST)){

		        $frm->hidden("target", $PHORUM["admin_http_path"]."?".$_SERVER["QUERY_STRING"]);
		    }

		    $frm->addrow("Username", $frm->text_box("username", "", 30));
	
		    $frm->addrow("Password", $frm->text_box("password", "", 30, 0, true));
		    
		    $captcha_display = !empty($captcha_img)
				? "<img width='120' height='40' style='vertical-align: middle; border: 1px solid #ccc; border-radius: 4px;' src='./mods/admin_security_suite/tmp_captchas/".$captcha_img."' />"
				: "<b style='font-size: 1.2em; letter-spacing: 2px;'>$randnum</b>";

		    $frm->addrow("Enter this code: $captcha_display", $frm->text_box("submitted_captcha", "", 30));

		

		    $frm->show();
		    include_once "./include/admin/footer.php";
			ob_end_flush();
			exit();
		}
	}
	
	if (isset($PHORUM["phorum_mod_admin_security_suite"]["enable_admin_IP_session_lock"]) && $PHORUM["phorum_mod_admin_security_suite"]["enable_admin_IP_session_lock"] == "1") {
		if ($module == "login") {
			if(isset($_POST["username"]) && isset($_POST["password"])){
	        	$user_id = phorum_api_user_authenticate(PHORUM_ADMIN_SESSION,$_POST["username"], $_POST["password"]);
		        if ($user_id &&
        			phorum_api_user_set_active_user(PHORUM_ADMIN_SESSION, $user_id) &&
        			phorum_api_user_session_create(PHORUM_ADMIN_SESSION)) {	        	 
		
					$PHORUM["phorum_mod_admin_security_suite"]["admin_IP_session_locks"][$user_id] = $_SERVER["REMOTE_ADDR"];
					phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
	       	        if(!empty($_POST["target"])){
	       	            phorum_redirect_by_url($_POST['target']);
	       	        } else {
	       	            phorum_redirect_by_url($PHORUM["admin_http_path"]);
	       	        }
	       	        exit();
	       		} else {
	       			unset($GLOBALS["PHORUM"]["user"]);
	       		}
	   	    } else {
	   	    	unset($GLOBALS["PHORUM"]["user"]);
	  	    }
	    } else {
	    	if ($_SERVER["REMOTE_ADDR"] != $PHORUM["phorum_mod_admin_security_suite"]["admin_IP_session_locks"][$GLOBALS["PHORUM"]["user"]["user_id"]]) {
    			phorum_mod_admin_security_suite_log_event(array("message" => "Possible admin session theft", "details" => "Possible admin session theft as ".$GLOBALS["PHORUM"]["user"]["username"]." attempted to access the admin area from a new IP address (".$_SERVER["REMOTE_ADDR"].")"));
    			unset($PHORUM["phorum_mod_admin_security_suite"]["admin_IP_session_locks"][$user_id]);
			phorum_db_update_settings(array("phorum_mod_admin_security_suite"=>$PHORUM["phorum_mod_admin_security_suite"]));
			phorum_api_user_session_destroy(PHORUM_ADMIN_SESSION);
				
			unset($GLOBALS["PHORUM"]["user"]);
    			phorum_redirect_by_url($PHORUM["admin_http_path"]);
    			exit();
    		}
    	}
	} 

	return $module;
}
?>