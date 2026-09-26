<?php

if(!defined("PHORUM")) return;
define('SWIFT_DIRECTORY','./mods/smtp_mail/swiftmailer');

/*
$data = 
	sender		('key','name','email')
	recipients
		key 	=>	1=users, 2=groups
		groups	=>	array of group ids
		users	=>	array of user ids
	message 	('body','subject','attachments')
*/
function phorum_mod_admin_mass_email_send ($data, $preview = false) 
{
	GLOBAL $PHORUM;
	GLOBAL $user_fields;
	if ($PHORUM["DBCONFIG"]["type"] == "mysqli" &&
		!file_exists("./include/db/mysqli.php")) {
		$PHORUM["DBCONFIG"]["type"] = "mysql";
	}

	// Load the database layer.
	include_once( "./include/db/{$PHORUM['DBCONFIG']['type']}.php" );
	include_once("./include/format_functions.php");
	include_once("./include/api/base.php");
	include_once("./include/api/file_storage.php");
	$mail_users = phorum_api_user_get($data["recipients"]["users_batch"],TRUE);

	if (count($mail_users)) {
		
		$mail_data = array(
			"sitename"	=> $PHORUM['title'],
			"mailmessage"	=> $data["message"]["body"],
			"mailsubject"	=> $data["message"]["subject"]
			);
		
		$host = "";
		if (isset($_SERVER["HTTP_HOST"])) {
			$host = $_SERVER["HTTP_HOST"];
		} else if (function_exists("posix_uname")) {
			$sysinfo = @posix_uname();
			if (!empty($sysinfo["nodename"])) {
				$host .= $sysinfo["nodename"];
			}
			if (!empty($sysinfo["domainname"])) {
				$host .= $sysinfo["domainname"];
			}
		} else if (function_exists("php_uname")) {
			$host = @php_uname("n");
		} else if (($envhost = getenv("HOSTNAME")) !== false) {
			$host = $envhost;
		}
		if (empty($host)) {
			$host = "webserver";
		}

		if ($data["sender"]["key"] == 0) {
			$sender_name = $PHORUM['system_email_from_name'];
			$sender_address = $PHORUM['system_email_from_address'];
		} else if  ($data["sender"]["key"] == 1) {
			$sender_name = $data["sender"]["name"];
			$sender_address = $data["sender"]["email"];
		}		

		// set to 1 for debugging messages in the event logging module
		$debug_i = (!empty($PHORUM["phorum_mod_admin_mass_email"]["enable_debugging"])) ? 1 : 0;
		$repeat_debug = (!empty($PHORUM["phorum_mod_admin_mass_email"]["repeated_debugging"])) ? 1 : 0;

		$subject_conditions = phorum_mod_admin_mass_email_process_conditions($mail_data["mailsubject"]);
		$body_conditions = phorum_mod_admin_mass_email_process_conditions($mail_data["mailmessage"]);
		
		foreach($mail_users as $user_id => $user_info) 
		{

			if ($debug_i == 1) {
				if (function_exists('event_logging_writelog')) {
					$testuser = implode(",",$user_info);
					event_logging_writelog(array(
						"message"	=> "user:\n\n".$user_id." = ".$testuser
					));
				}
			}

			if ((empty($user_info["phorum_mod_admin_mass_email_user_unsubscribe_setting"]) || (!empty($user_info["phorum_mod_admin_mass_email_user_unsubscribe_setting"]) && $user_info["phorum_mod_admin_mass_email_user_unsubscribe_setting"] != "off") || $PHORUM["phorum_mod_admin_mass_email"]["allow_user_unsubscribe"] != 1)) {

				$messageid = time();
				$temp_mail_data["mailmessage"] = $mail_data["mailmessage"];
				$temp_mail_data["mailsubject"] = $mail_data["mailsubject"];
				
				if (!empty($body_conditions)) {
					foreach ($body_conditions[0] as $num_if => $condition) {
						eval($condition);
						$temp_mail_data["mailmessage"] = str_replace($body_conditions[1][$num_if],$post_conditions[$num_if],$temp_mail_data["mailmessage"]);
					}
				}
				
				$post_conditions = array();

				if (!empty($subject_conditions)) {
					foreach ($subject_conditions[0] as $num_if => $condition) {
						eval($condition);
						$temp_mail_data["mailsubject"] = str_replace($subject_conditions[1][$num_if],$post_conditions[$num_if],$temp_mail_data["mailsubject"]);
					}
				}
				
				$post_conditions = array();
				foreach ($user_fields as $key) {
					if (empty($user_info[$key])) continue;
					if ($key == "date_added" || $key == "date_last_active") {
						$user_info[$key] = date(str_replace("%","",$PHORUM["short_date"]), $user_info[$key]);
					}
					$temp_mail_data["mailmessage"] = str_replace("%$key%", $user_info[$key], $temp_mail_data["mailmessage"]);
					$temp_mail_data["mailsubject"] = str_replace("%$key%", $user_info[$key], $temp_mail_data["mailsubject"]);
				}

				if (!empty($PHORUM["hooks"]["send_mail"]) && in_array("smtp_mail",$PHORUM["hooks"]["send_mail"]["mods"]) && !$preview) {
					$email_data = array(
						'sender_address'=> $sender_address,
						'sender_name'	=> $sender_name,
						'address'	=> $user_info["email"],
						'subject'	=> $temp_mail_data["mailsubject"],
						'body'		=> $temp_mail_data["mailmessage"],
						'messageid'	=> $messageid
					);
					if (isset($data['message']['attachments'])) {
						$email_data["attachments"] = $data['message']['attachments'];
					}
            if (empty($PHORUM["phorum_mod_admin_mass_email"]["disable_actual_emails"])) {
                $smtp_error = phorum_smtp_send_messages_azumod_ame($email_data);
            } else {
                $smtp_error = "";
            }

					if ($debug_i == 1) {
						if (function_exists('event_logging_writelog')) {
							if (!empty($smtp_error)) {
								$log_message = $smtp_error;
							} else {
                if (empty($PHORUM["phorum_mod_admin_mass_email"]["disable_actual_emails"])) {
                    $log_message = "Message sent through outside email module";
                } else {
                    $log_message = "Debugging, no actual email sent.";
                }
							}
							event_logging_writelog(array(
								"message"	=> $log_message
							));
						}
					}
					if (!empty($smtp_error)) {
						$status_data = $smtp_error;
					}
					continue;
				}
				
				$Textmsg = $temp_mail_data["mailmessage"];
				$headers = "From: $sender_name <$sender_address>\r\n";
				$headers .= "Message-ID: <".$messageid.">\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$bndp = "ame_bndp_".$messageid;
				if ($debug_i == 1) {
					if (function_exists('event_logging_writelog')) {
						event_logging_writelog(array(
							"message" => "Boundary:\n\n".$bndp
						));
					}
				}
				$headers .= "Content-Type: multipart/mixed; \r\n boundary=\"".$bndp."\"\r\n\n";
				if ($debug_i == 1) {
					if (function_exists('event_logging_writelog')) {
						event_logging_writelog(array(
							"message"	=> "Headers:\n\n".$headers
						));
					}
				}
				$msg = "This is a multi-part message in MIME format.\n\n";
				$msg .= "--".$bndp."\n";
				$msg .= "Content-Type: text/plain; charset=UTF-8\n";
				$msg .= "Content-Transfer-Encoding: 8bit\n\n";
				$msg .= $Textmsg."\n";
				if (isset($data['message']['attachments'])) {
					foreach ($data['message']['attachments'] as $attachment => $attachment_data) {
						$msg .= "--".$bndp."\n";
						$msg .= "Content-Type: $attachment_data[mime_type]; name=\"".$attachment."\"\n";
						$msg .= "Content-Transfer-Encoding: base64\n";
						$msg .= "Content-Disposition: attachment;\n";
						$msg .= "		filename=\"".$attachment."\"\n\n";
						$msg .= $attachment_data["f_contents"]."\n";
					}
				}
				$msg .= "--".$bndp."--\n";
				if ($debug_i == 1) {
					if (function_exists('event_logging_writelog')) {
						event_logging_writelog(array(
							"message"	=> "Email message:\n\n".$msg
						));
					}
				}
				
					
				}
				if ($preview) {
					$preview_data = "Preview message for ".$user_info["username"]."&nbsp;&nbsp;<input type='button' value='Return to Email' onclick='window.close()' /></td></tr><tr><td align='left' class='input-form-td' colspan='2'><pre>$headers"."Subject:$temp_mail_data[mailsubject]\n\n$msg</pre>";
					return $preview_data;
				}

				if (empty($PHORUM["phorum_mod_admin_mass_email"]["disable_actual_emails"])) {
            $msg_accepted = mail($user_info["email"], $temp_mail_data["mailsubject"], $msg, $headers);
        } else {
            $msg_accepted = "Debugging, no actual email sent.";
        }

				if ($debug_i == 1) {
					if (function_exists('event_logging_writelog')) {
						event_logging_writelog(array(
							"message"	=> "Email accepted by sending server:\n\n".$msg_accepted
						));
            if (!empty($PHORUM["phorum_mod_admin_mass_email"]["disable_actual_emails"])) $msg_accepted = 1;
					}
					$debug_i = $repeat_debug;
				}
        $status_data = "";
        
				if (empty($msg_accepted)) {
					$status_data = "$user_info[username] = Undefined error.";
				} elseif ($msg_accepted != 1) {
					$status_data = "$user_info[username] = ".$msg_accepted;
				} else {
          $staus_data = "$user_info[username] = Message accepted by email server.";
        }
			}
		}

	return $status_data;
}

function phorum_mod_admin_mass_email_process_conditions($data) {
	preg_match_all("/\{[\"\'\!\/A-Za-z0-9].+?\}/s", $data, $matches);
	$conditions = array();
	$replaces = array();
	$num_if = 0;
	$operators = " ( = | < | > | <= | >= | contains ) ";
	$neg_operators = " (% NOT [=<>] |% NOT <= |% NOT >= |% NOT contains) ";
	$date_operators = " ( [=<>] DATE \\\"| <= DATE \\\"| >= DATE \\\"| contains DATE \\\") ";
	if (!empty($matches)) {
		foreach ($matches[0] as $key => $match) {
			if (substr($match,0,4) == "{IF ") {
				$replaces[$num_if] = $match;
				preg_match($neg_operators,$match,$neg_operator);
				$not = (empty($neg_operator)) ? "" : "!";
				preg_match($operators,$match,$pre_operator);
				$operator = substr($pre_operator[0],1,(strlen($pre_operator[0])-2));
				if ($operator == "=" && empty($not)) $operator = "==";
				preg_match($date_operators,$match,$date_operator);
				$isdate = (!empty($date_operator));
				preg_match("/%.+%/",$match,$pre_haystack);
				$haystack = substr($pre_haystack[0],1,(strlen($pre_haystack[0])-2));
				preg_match("/\".+\"/",$match,$pre_needle);
				$needle = substr($pre_needle[0],1,(strlen($pre_needle[0])-2));
				if ($isdate) $needle = strtotime($needle);
				if ($operator == "contains") {
					$conditions[$num_if] = "if (".$not."strstr(\$user_info[\"$haystack\"],\"$needle\")";
				} else {
					$conditions[$num_if] = "if (\$user_info[\"$haystack\"] ".$not."$operator ";
					if (is_numeric($needle)) { 
						$conditions[$num_if] .= $needle;
					} else {
						$conditions[$num_if] .= "\"$needle\"";
					}
				}
			} elseif ($match == "{/IF}") {
				$replaces[$num_if] .= $match;
				$num_if++;
			} elseif (substr($match,0,5) == "{AND ") {
				$replaces[$num_if] .= $match;
				preg_match($neg_operators,$match,$neg_operator);
				$not = (empty($neg_operator)) ? "" : "!";
				preg_match($operators,$match,$pre_operator);
				$operator = substr($pre_operator[0],1,(strlen($pre_operator[0])-2));
				if ($operator == "=" && empty($not)) $operator = "==";
				preg_match($date_operators,$match,$date_operator);
				$isdate = (!empty($date_operator));
				preg_match("/%.+%/",$match,$pre_haystack);
				$haystack = substr($pre_haystack[0],1,(strlen($pre_haystack[0])-2));
				preg_match("/\".+\"/",$match,$pre_needle);
				$needle = substr($pre_needle[0],1,(strlen($pre_needle[0])-2));
				if ($isdate) $needle = strtotime($needle);
				if ($operator == "contains") {
					$conditions[$num_if] = " && ".$not."strstr(\$user_info[\"$haystack\"],\"$needle\")";
				} else {
					$conditions[$num_if] .= " && \$user_info[\"$haystack\"] ".$not."$operator ";
					if (is_numeric($needle)) { 
						$conditions[$num_if] .= $needle;
					} else {
						$conditions[$num_if] .= "\"$needle\"";
					}
				}
			} elseif (substr($match,0,4) == "{OR ") {
				$replaces[$num_if] .= $match;
				preg_match($neg_operators,$match,$neg_operator);
				$not = (empty($neg_operator)) ? "" : "!";
				preg_match($operators,$match,$pre_operator);
				$operator = substr($pre_operator[0],1,(strlen($pre_operator[0])-2));
				if ($operator == "=" && empty($not)) $operator = "==";
				preg_match($date_operators,$match,$date_operator);
				$isdate = (!empty($date_operator));
				preg_match("/%.+%/",$match,$pre_haystack);
				$haystack = substr($pre_haystack[0],1,(strlen($pre_haystack[0])-2));
				preg_match("/\".+\"/",$match,$pre_needle);
				$needle = substr($pre_needle[0],1,(strlen($pre_needle[0])-2));
				if ($operator == "contains") {
					$conditions[$num_if] = " || ".$not."strstr(\$user_info[\"$haystack\"],\"$needle\")";
				} else {
					$conditions[$num_if] .= " || \$user_info[\"$haystack\"] ".$not."$operator ";
					if (is_numeric($needle)) { 
						$conditions[$num_if] .= $needle;
					} else {
						$conditions[$num_if] .= "\"$needle\"";
					}
				}
			} elseif (substr($match,0,8) == "{ELSEIF ") {
				$replaces[$num_if] .= $match;
				preg_match($neg_operators,$match,$neg_operator);
				$not = (empty($neg_operator)) ? "" : "!";
				preg_match($operators,$match,$pre_operator);
				$operator = substr($pre_operator[0],1,(strlen($pre_operator[0])-2));
				if ($operator == "=" && empty($not)) $operator = "==";
				preg_match($date_operators,$match,$date_operator);
				$isdate = (!empty($date_operator));
				preg_match("/%.+%/",$match,$pre_haystack);
				$haystack = substr($pre_haystack[0],1,(strlen($pre_haystack[0])-2));
				preg_match("/\".+\"/",$match,$pre_needle);
				$needle = substr($pre_needle[0],1,(strlen($pre_needle[0])-2));
				if ($operator == "contains") {
					$conditions[$num_if] = " elseif (".$not."strstr(\$user_info[\"$haystack\"],\"$needle\")";
				} else {
					$conditions[$num_if] .= " elseif (\$user_info[\"$haystack\"] ".$not."$operator ";
					if (is_numeric($needle)) { 
						$conditions[$num_if] .= $needle;
					} else {
						$conditions[$num_if] .= "\"$needle\"";
					}
				}
			} elseif (substr($match,0,6) == "{THEN ") {
				$replaces[$num_if] .= $match;
				$match = str_replace("\n","\\n",$match);
				preg_match("/\".+\"/",$match,$pre_then);
				$then = substr($pre_then[0],1,(strlen($pre_then[0])-2));
				$conditions[$num_if] .= ") { \$post_conditions[$num_if] = \"$then\"; }";
			} elseif (substr($match,0,6) == "{ELSE ") {
				$replaces[$num_if] .= $match;
				$match = str_replace("\n","\\n",$match);
				preg_match("/\".+\"/",$match,$pre_else);
				$else = substr($pre_else[0],1,(strlen($pre_else[0])-2));
				$conditions[$num_if] .= " else { \$post_conditions[$num_if] = \"$else\"; }";
			}
		}
	}
	$processed_data = array($conditions, $replaces);
	return $processed_data;
}

function phorum_mod_admin_mass_email_tpl_cc_usersettings($profile)
{
	global $PHORUM;
	if (!empty($profile["MAILSETTINGS"])) {
		if ($PHORUM["phorum_mod_admin_mass_email"]["allow_user_unsubscribe"] == 1) {
			foreach ($PHORUM["PROFILE_FIELDS"] as $key => $cstm_field) {
				if ($key === 'num_fields' || !is_array($cstm_field)) continue;   // compteur entier rangé parmi les champs (cœur : même garde)
				if ($cstm_field["name"] == "phorum_mod_admin_mass_email_user_unsubscribe_setting") {
					if (!empty($cstm_field["deleted"]) && $cstm_field["deleted"] == TRUE) {
						$user_unsubscribe = 2;
					} else {
						$user_unsubscribe = 1;
					}
				}
			}
			if ($user_unsubscribe == 1) {
				$currval = isset($profile["phorum_mod_admin_mass_email_user_unsubscribe_setting"]);
				print "<dt>".$PHORUM["DATA"]["LANG"]["admin_mass_email"]["Unsubscribe"];
				?></dt>
					<dd><select name="phorum_mod_admin_mass_email_user_unsubscribe_setting">
						<option value="on"<?php
						if ($PHORUM["DATA"]["PROFILE"]["phorum_mod_admin_mass_email_user_unsubscribe_setting"] == "on") {
							?> selected="selected"<?php
						}
				print ">".$PHORUM["DATA"]["LANG"]["admin_mass_email"]["UnsubscribeOn"];
				?></option>
				<option value="off"<?php
				if ($PHORUM["DATA"]["PROFILE"]["phorum_mod_admin_mass_email_user_unsubscribe_setting"] == "off") {
					?> selected="selected"<?php
				}
				print ">".$PHORUM["DATA"]["LANG"]["admin_mass_email"]["UnsubscribeOff"]; 
				?></option></select></dd>
				<?php
			}
		}
	}

	return $profile;
	
}

function phorum_smtp_send_messages_azumod_ame ($data)
{
	$PHORUM = $GLOBALS["PHORUM"];

	if(!empty($data["address"])){
    
		if (file_exists(SWIFT_DIRECTORY."/Swift.php")) {
      $sender_address = $data['sender_address'];
      $sender_name = $data['sender_name'];
      $address = $data['address'];
      $subject = $data['subject'];
      $message = $data['body'];
    
      $settings  = $PHORUM['smtp_mail'];
      $settings['auth'] = empty($settings['auth'])?false:true;

      try {
        /*
         * SMTP-Mail-Module v0.8
         * made by Thomas Seifert
         * email: thomas (at) phorum.org
         *
         * modified for this module with the ability to send attachments by Azumandias
         */
  
        // include the swiftmailer-class
  
        require_once SWIFT_DIRECTORY."/Swift.php";
        require_once SWIFT_DIRECTORY."/Swift/Connection/SMTP.php";
  
        // set the connection type
        if($settings['conn'] == 'plain') {
          $conn_type = Swift_Connection_SMTP::ENC_OFF;
        } elseif($settings['conn'] == 'ssl') {
          $conn_type = Swift_Connection_SMTP::ENC_SSL;
        } elseif($settings['conn'] == 'tls') {
          $conn_type = Swift_Connection_SMTP::ENC_TLS;
        } else {
          $conn_type = Swift_Connection_SMTP::AUTO_DETECT;
        }
  
        if(!isset($settings['host']) || empty($settings['host'])) {
          $settings['host'] = 'localhost';
        }
  
        if(!isset($settings['port']) || empty($settings['port'])) {
          $settings['port'] = '25';
        }
  
        // setup the connection with hostname and port
        $smtp = new Swift_Connection_SMTP($settings['host'], $settings['port'],$conn_type);
  
        // smtp-authentication
        if($settings['auth'] && !empty($settings['username'])) {
          $smtp->setUsername($settings['username']);
          $smtp->setpassword($settings['password']);
        }
  
        // construct the swift-mailer
        $swift = new Swift($smtp);
  
        // construct the message
        $message = new Swift_Message($subject, $message, $type="text/plain", $PHORUM["DATA"]["MAILENCODING"], $PHORUM["DATA"]["CHARSET"]);
      
      if (!empty($data["attachments"])) {
        foreach ($data["attachments"] as $attachment => $attachment_info) {
          $message->attach(new Swift_Message_Attachment(new Swift_File($attachment_info["f_contents"]), $attachment, $attachment_info["mime_type"]));
        }
      }
      
        $recipients = new Swift_RecipientList();
  
        $recipients->addTo($address);
  
        $swift->batchSend($message,$recipients,new Swift_Address($sender_address, $sender_name));
  
      } catch (Swift_Connection_Exception $e) {
        $swift_error = "There was a problem communicating with SMTP: " . $e->getMessage();
        return $swift_error;
      } catch (Swift_Message_MimeException $e) {
        $swift_error = "There was an unexpected problem building the email:" . $e->getMessage();
        return $swift_error;
      }
        unset($recipients);
        unset($message);
        unset($swift);
        unset($smtp);
    } elseif (file_exists("./mods/smtp_mail/phpmailer/class.phpmailer.php")) {
      $phorum_system_email_from_address = $PHORUM["system_email_from_address"];
      $phorum_system_email_from_name = $PHORUM["system_email_from_name"];
      $PHORUM["system_email_from_address"] = $data["sender_address"];
      $PHORUM["system_email_from_name"] = $data["sender_name"];
      $email_data = array (
        "addresses" => array(0 => $data["address"]),
        "subject" => $data["subject"],
        "body" => $data["body"],
        "messageid"	=> $data["messageid"],
        );
      if (!empty($data["attachments"])) {
        $email_data["attachments"] = array();
        foreach($data["attachments"] as $filename => $filedata) {
          $email_data["attachments"][] = array(
            "filename" => $filename,
            "filedata" => $filedata["f_contents"],
            "mimetype" => $filedata["mime_type"],
            );
        }
      }
      phorum_hook("send_mail", $email_data);
      $PHORUM["system_email_from_address"] = $phorum_system_email_from_address;
      $PHORUM["system_email_from_name"] = $phorum_system_email_from_name;
    }
	}



	// make sure that the internal mail-facility doesn't kick in
	return null;
}
?>