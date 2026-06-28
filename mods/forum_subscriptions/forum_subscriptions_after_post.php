<?php
        
if(!defined("PHORUM")) return;

function phorum_mod_forum_subscriptions_functions_after_post($data = NULL, $queue_data = NULL) {

    GLOBAL $PHORUM;
    
    // pull in the mail queue database functions
    require_once ("./mods/forum_subscriptions/db_functions.php");
    
    // if this is not a mail queue then get the list of users for this message
    if (is_null($queue_data)) {
        // if this is a reply and the admin only wants to send new threads
        // of if this is an ignored forum
        // then we are done
        if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["send_only_new_threads"]) && !empty($data["parent_id"])
            || !empty($PHORUM["phorum_mod_forum_subscriptions"]["ignore_selected_forums"]) && empty($PHORUM["phorum_mod_forum_subscriptions"]["forums_to_ignore"][$data["forum_id"]])
            ) return $data;
        
        // Get the list of all subscribers requesting immediate frequency
        $subscribers = phorum_mod_forum_subscriptions_db_subscriptions_get_subscribers_by_forum($data["forum_id"], PHORUM_MOD_FORUM_SUB_FREQUENCY_IMMEDIATE, PHORUM_MOD_FORUM_SUB_FORUM_GLOBAL);
        
        // if there are no subscribers then we are done
        if (empty($subscribers)) return $data;
        
        $user_ids = array();
        foreach ($subscribers as $subscriber) {
            // if the user does not have read access to this forum 
            // or the user only wants new threads and this is a reply
            // then we are done
            if(!phorum_api_user_check_access(PHORUM_USER_ALLOW_READ, $data["forum_id"], $subscriber["user_id"])
                || (!empty($data["parent_id"]) && $subscriber["sub_type"] == PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NEW_THREAD)) continue;
            
            $user_ids[$subscriber["user_id"]] = $subscriber["user_id"];
        }
        
        // if there are no valid subscribers then we are done
        if (empty($user_ids)) return $data;
        
        // get the full info for each subscriber if we are not building a
        // mail queue
        $mail_users = (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_mail_queue"])) ? $user_ids : phorum_api_user_get($user_ids,TRUE);
        
    } else {
        $user_ids = $queue_data["recipient_ids"];
        // get the full info for each recipient in the mail queue
        $mail_users = phorum_api_user_get($user_ids,TRUE);
    }
    
    if (count($mail_users)) {
      
        // set to 1 for debugging messages in the event logging module
        $debug_i = (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"])) ? 1 : 0;
        
        if ($debug_i == 1) {
            if (function_exists('event_logging_writelog')) {
                $log_message = "User_id's for mailing:\n\n".print_r($user_ids,true);
                event_logging_writelog(array(
                    "message"    => $log_message
                ));
            }
        }
        // if this is not a mail queue then process the message
        if (is_null($queue_data)) {
            
            require_once("./include/format_functions.php");
            
            // add the user's signature if enabled and elected by the author
            if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["show_signatures"]) && !empty($data["meta"]["show_signature"])) {
                
                $author_info = phorum_api_user_get($data["user_id"]);
                // add the signature if the author has a signature
                if (!empty($author_info["signature"])) {
                    $author_signature = trim($author_info["signature"]);
                    $data["body"].="\n\n$author_signature";
                }
                
                unset($author_info);
            }
            
            $mail_data = array(
                "sitename"    => $PHORUM['title'],
                "forumname"   => strip_tags($PHORUM["DATA"]["NAME"]),
                "forum_id"    => $data['forum_id'],
                "message_id"  => $data['message_id'],
                "user_id"     => $data['user_id'],
                "vroot"       => $PHORUM["vroot"],
                "author"      => $data['author'],
                "subject"     => $data['subject'],
                "full_body"   => $data['body'],
                "plain_body"  => phorum_strip_body($data['body']),
                "read_url"    => phorum_get_url(PHORUM_READ_URL, $data['thread'], $data['message_id']),
                "remove_url"  => phorum_get_url(PHORUM_FOLLOW_URL, $data['thread'], "remove=1"),
                "noemail_url" => phorum_get_url(PHORUM_FOLLOW_URL, $data['thread'], "noemail=1"),
                "followed_threads_url" => phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUBSCRIPTION_THREADS),
                "msgid"       => $data["msgid"]
            );
            
            if (isset($_POST[PHORUM_SESSION_LONG_TERM])) {
               // strip any auth info from the read url
                $mail_data["read_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["read_url"]);
                $mail_data["remove_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["remove_url"]);
                $mail_data["noemail_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["noemail_url"]);
                $mail_data["followed_threads_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["followed_threads_url"]);
            }
            // if the admin has opted to use mail queues, insert this message
            // and its recipients as a new mail queue, then we are done
            if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_mail_queue"])) {
                $attachment_data = (isset($data["meta"]["attachments"])) ? $data["meta"]["attachments"] : NULL;
                phorum_mod_forum_subscriptions_db_mailqueue_add($user_ids, $mail_data, $attachment_data);
                return $data;
            }
        } else {
            require_once("./include/api/forums.php");
            require_once("./include/format_functions.php");
            
            // separate out the mail data from the mail queue
            $mail_data = $queue_data["mail_data"];

            // get all available forums
            $forums = phorum_api_forums_by_vroot($mail_data["vroot"]);
            
            $PHORUM["vroot"] = $mail_data["vroot"];
            
            if (!empty($mail_data["digest_id"])) {
                
                if (!function_exists("phorum_mod_forum_subscriptions_message_datesort")) {
                    function phorum_mod_forum_subscriptions_message_datesort($a, $b) {
                        if ($a["datestamp"] == $b["datestamp"]) {
                            return ($a["message_id"] < $b["message_id"]) ? -1 : 1;
                        }
                        return ($a["datestamp"] < $b["datestamp"]) ? -1 : 1;
                    }
                }
                
                $messages = phorum_db_get_message($mail_data["messages"],"message_id",true);
                unset($mail_data["messages"]);
                
                uasort($messages, "phorum_mod_forum_subscriptions_message_datesort");
                
                foreach($messages as $message_id => $message) {
                    if(isset($messages[$message_id]["meta"]["attachments"])) 
                        unset($messages[$message_id]["meta"]["attachments"]);
                    $messages[$message_id]["raw_datestamp"] = $message["datestamp"];
                    $messages[$message_id]['datestamp'] = phorum_date($PHORUM['short_date_time'], $message['datestamp']);
                    // add the author's signature if enabled and elected by the author
                    if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["show_signatures"]) && !empty($message["meta"]["show_signature"])) {
                        
                        $author_info = phorum_api_user_get($message["user_id"]);
                        // add the signature if the author has a signature
                        if (!empty($author_info["signature"])) {
                            $author_signature = trim($author_info["signature"]);
                            $messages[$message_id]["body"].="\n\n$author_signature";
                        }
                        
                        unset($author_info);
                    }
                    $messages[$message_id]["plain_body"] = phorum_strip_body($messages[$message_id]['body']);
                    $messages[$message_id]["forum_name"] = strip_tags($forums[$message["forum_id"]]["name"]);
                    $current_forum_id = $PHORUM["forum_id"];
                    $PHORUM["forum_id"] = $message["forum_id"];
                    $messages[$message_id]["read_url"] = phorum_get_url(PHORUM_READ_URL, $message['thread'], $message['message_id']);
                    $PHORUM["forum_id"] = $current_forum_id;
                    if (file_exists("./mods/forum_subscriptions/lang/{$PHORUM['language']}.php")) {
                        include("./mods/forum_subscriptions/lang/{$PHORUM['language']}.php");
                    } elseif (file_exists("./mods/forum_subscriptions/lang/english.php")) {
                        include("./mods/forum_subscriptions/lang/english.php");
                    }
                    $digest = ($mail_data["forum_id"] == $mail_data["vroot"])
                        ? $PHORUM["DATA"]["LANG"]["forum_subscriptions"]["AllForumsDigestFormat"]
                        : $PHORUM["DATA"]["LANG"]["forum_subscriptions"]["SingleForumDigestFormat"];
                    // substitute sections of the mail message and subject from the 
                    // forum message's data
                    foreach(array_keys($messages[$message_id]) as $key) {
                        if ($messages[$message_id][$key] === NULL || is_array($messages[$message_id][$key])) continue;
                        $digest = str_replace("%$key%", $messages[$message_id][$key], $digest);
                    }
                    $messages[$message_id]["digest"] = $digest;
                }
            }
            
            // set the limit for the number of emails to send each time the
            // scheduled hook is called (this may become a setting variable 
            // in the future)
            $queue_limit = !is_null($queue_data["attachment_data"]) ? 20 : 50;
            // start counting the number of messages
            $loop_count = 1;
        }
        
        $sender_name = $PHORUM['system_email_from_name'];
        $sender_address = $PHORUM['system_email_from_address'];
        
        //process attachments if need be
        if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["allow_attachments"]) && $PHORUM["phorum_mod_forum_subscriptions"]["allow_attachments"] == 1) {
            if (!is_null($queue_data) && !is_null($queue_data["attachment_data"]))
                $data["meta"]["attachments"] = $queue_data["attachment_data"];
                include_once("./include/api/base.php");
                include_once("./include/api/file_storage.php");
            if (isset($data["meta"]["attachments"])) {
                $attachments = array();
                foreach ($data["meta"]["attachments"] as $attachment) {
                    $dbfile = phorum_db_file_get($attachment["file_id"], TRUE);
                    $mime_type = phorum_api_file_get_mimetype($attachment["name"]);
                    
                    // format the file contents as needed
                    if (!empty($PHORUM["hooks"]["send_mail"]) && in_array("smtp_mail",$PHORUM["hooks"]["send_mail"]["mods"])) {
                        // if using SMTP Mail module version 0.8 or lower
                        if (file_exists ("./mods/smtp_mail/swiftmailer/Swift.php")) {
                            $filedata = $dbfile["file_data"];
                        // if using the new SMTP Mail with PHP Mailer
                        } else {
                            $filedata = base64_decode($dbfile["file_data"]);
                        }
                    // if using the internal mailer
                    } else {
                        $filedata = chunk_split($dbfile["file_data"]);
                    }
                    $attachments[$attachment["name"]] = array (
                        "filename" => $attachment["name"],
                        "mimetype" => $mime_type,
                        "filedata" => $filedata,
                        );
                }
            }
        }
        
        // go through the user-languages and send mail with their set lang
        foreach($mail_users as $user_id => $user_info) {
            if (!is_null($queue_data)) {
                // if we have sent the maximum number of emails per instance of 
                // the scheduled hook or if this instance has been running for 
                // more than two minutes, then we are done
                if ($loop_count > $queue_limit || ($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"] + $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"]) < time()) {
                    unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
                    phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
                    return;
                } else {
                    $loop_count ++;
                }
            }

            // continue sending to this user only if
            //   the admin does not allow the user to unsubscibe from their 
            //   own messages
            //     or the user has not unsubscribed from their own messages
            //     or this is not the user's message
            //   and the user is an admin
            //       or this user does have read permission
            if ((empty($user_info["phorum_mod_forum_subscriptions_user_unsubscribe_setting_self"]) 
                    || ((!empty($user_info["phorum_mod_forum_subscriptions_user_unsubscribe_setting_self"]) 
                        && $user_info["phorum_mod_forum_subscriptions_user_unsubscribe_setting_self"] == "yes")
                        || $user_id != $mail_data["user_id"])
                    || $PHORUM["phorum_mod_forum_subscriptions"]["allow_user_unsubscribe_self"] != 1)
                && ((!empty($mail_data["digest_id"]) && $mail_data["forum_id"] == $mail_data["vroot"])
                    || phorum_api_user_check_access(PHORUM_USER_ALLOW_READ, $mail_data["forum_id"], $user_id))) {

                // single_notification enforcement for immediate thread replies
                if (empty($mail_data["digest_id"]) && !empty($mail_data["thread_id"]) && !empty($PHORUM["mods"]["single_notification"])) {
                    require_once("./mods/single_notification/db.php");
                    if (mod_single_notification_db_init()) {
                        $checks = mod_single_notification_db_check(array($user_info["email"]), $mail_data["forum_id"], $mail_data["thread_id"]);
                        if (!empty($checks)) {
                            if (!is_null($queue_data)) {
                                unset($queue_data["recipient_ids"][$user_id]);
                            }
                            continue;
                        } else {
                            mod_single_notification_db_add(array($user_info["email"]), $mail_data["forum_id"], $mail_data["thread_id"]);
                        }
                    }
                }
            
                if ($debug_i == 1) {
                    if (function_exists('event_logging_writelog')) {
                        $testuser = $user_info["username"] . "(" . $user_info["email"] . ")";
                        event_logging_writelog(array(
                            "message"   => "First user:\n\n".$user_id." = ".$testuser
                        ));
                    }
                }
                
                $messageid = time().$user_id;
                
                if ( file_exists( "./mods/forum_subscriptions/lang/{$user_info['user_language']}.php" )) {
                    include( "./mods/forum_subscriptions/lang/{$user_info['user_language']}.php" );
                } elseif (file_exists("./mods/forum_subscriptions/lang/{$PHORUM['language']}.php")) {
                    include("./mods/forum_subscriptions/lang/{$PHORUM['language']}.php");
                } elseif (file_exists("./mods/forum_subscriptions/lang/english.php")) {
                    include("./mods/forum_subscriptions/lang/english.php");
                }
                
                if (empty($mail_data["digest_id"])) {
                    // prepare the mail message and subject for the user's language
                    $mail_data["mailmessage"] = $PHORUM["DATA"]["LANG"]["forum_subscriptions"]["NewMessage"];
                    $mail_data["mailsubject"] = $PHORUM["DATA"]["LANG"]["forum_subscriptions"]["NewMessageSubject"];
                    
                    // substitute sections of the mail message and subject from the 
                    // forum message's data
                    foreach(array_keys($mail_data) as $key) {
                        if ($mail_data[$key] === NULL || is_array($mail_data[$key])) continue;
                        $mail_data["mailmessage"] = str_replace("%$key%", $mail_data[$key], $mail_data["mailmessage"]);
                        $mail_data["mailsubject"] = str_replace("%$key%", $mail_data[$key], $mail_data["mailsubject"]);
                    }
                } else {
                    $frequency = $mail_data["frequency"];
                    $lang_frequency = ($frequency == PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY)
                        ? "Daily" : "Weekly";
                    $mail_data["mailmessage"] = $PHORUM["DATA"]["LANG"]["forum_subscriptions"][$lang_frequency . "DigestBody"];
                    $mail_data["mailsubject"] = $PHORUM["DATA"]["LANG"]["forum_subscriptions"][$lang_frequency . "DigestSubject"];
                    
                    $first_message = true;
                    $mail_data["digest"] = "";
                    
                    if ($mail_data["forum_id"] == $mail_data["vroot"]) {
                        $user_forums = phorum_api_user_check_access(PHORUM_USER_ALLOW_READ, PHORUM_ACCESS_LIST, $user_id);
                        if (empty($user_forums)) continue;
                    } else {
                        $user_forums[$mail_data["forum_id"]] = $mail_data["forum_id"];
                    }
                    
                    foreach ($messages as $message) {
                        if (empty($user_forums[$message["forum_id"]])) continue;
                        if (!empty($user_info["phorum_mod_forum_subscriptions_user_unsubscribe_setting_self"]) 
                            && $user_info["phorum_mod_forum_subscriptions_user_unsubscribe_setting_self"] == "no" 
                            && $user_id == $message["user_id"] 
                            && $PHORUM["phorum_mod_forum_subscriptions"]["allow_user_unsubscribe_self"] == 1) continue;
                        if ($first_message) {
                            $first_message = false;
                            $digest_separator = "";
                        } else {
                            $digest_separator = $PHORUM["DATA"]["LANG"]["forum_subscriptions"]["DigestSeparator"];
                        }
                        $mail_data["digest"] .= $digest_separator.$message["digest"];
                    }
                        
                    // if this recipient is not valid, remove them from the mail
                    // queue and update it if there are more recipients
                    if (empty($mail_data["digest"])) {
                        unset($queue_data["recipient_ids"][$user_id]);
                        if (!empty($queue_data["recipient_ids"]))
                            phorum_mod_forum_subscriptions_db_mailqueue_update_data($queue_data);
                        continue;
                    }
                    
                    foreach(array_keys($PHORUM["DATA"]["LANG"]["forum_subscriptions"]["digest_variables"]) as $key) {
                        $mail_data["digest"] = str_replace("%$key%", $PHORUM["DATA"]["LANG"]["forum_subscriptions"]["digest_variables"][$key], $mail_data["digest"]);
                    }
                    foreach(array_keys($mail_data) as $key) {
                        if ($mail_data[$key] === NULL || is_array($mail_data[$key])) continue;
                        $mail_data["mailmessage"] = str_replace("%$key%", $mail_data[$key], $mail_data["mailmessage"]);
                        $mail_data["mailsubject"] = str_replace("%$key%", $mail_data[$key], $mail_data["mailsubject"]);
                    }
                }
                
                // if the SMTP Mail module has been enabled, prepare the message
                // for that module and send it through that module
                if (!empty($PHORUM["hooks"]["send_mail"]) && in_array("smtp_mail",$PHORUM["hooks"]["send_mail"]["mods"])) {
                    $email_data = array(
                        'sender_address'=> $sender_address,
                        'sender_name'   => $sender_name,
                        'address'       => $user_info["email"],
                        'addresses'     => array($user_info["email"]),
                        'subject'       => $mail_data["mailsubject"],
                        'body'          => $mail_data["mailmessage"],
                        'messageid'     => $messageid
                    );
                    if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["allow_attachments"]) 
                        && $PHORUM["phorum_mod_forum_subscriptions"]["allow_attachments"] == 1
                        && !empty($attachments)) {
                        $email_data["attachments"] = $attachments;
                    }
                    // if the SMTP Mail module is version 0.8 or lower
                    if (file_exists ("./mods/smtp_mail/swiftmailer/Swift.php")) {
                        $smtp_error = phorum_smtp_send_messages_mod_forum_sub_swift($email_data);
                    // otherwise use the new SMTP Module with support for attachments
                    } else {
                        $smtp_error = phorum_hook("send_mail", $email_data);
                    }
                    if ($debug_i == 1) {
                        if (function_exists('event_logging_writelog')) {
                            if (!empty($smtp_error)) {
                                $log_message = $smtp_error;
                            } else {
                                $log_message = "Message sent through SMTP Mail module";
                            }
                            event_logging_writelog(array(
                                "message"    => $log_message
                            ));
                        }
                    }
                    
                    if (!empty($smtp_error)) {
                        $status_data[] = $smtp_error;
                        if (!is_null($queue_data)) {
                            // if the SMTP Mail module returned an error for 
                            // this recipient, save the error to the mail queue
                            $queue_data["error_data"][$user_id] = $smtp_error;
                        }
                    } else if (!is_null($queue_data)) {
                        // if the mail was sent successfully, remove this
                        // recipient from the mail queue
                        unset($queue_data["recipient_ids"][$user_id]);
                    }
                    
                    // if we are in a mail queue and have not sent the message
                    // to every recipient in the queue, update the mail queue
                    if (!is_null($queue_data) && !empty($queue_data["recipient_ids"])) 
                        phorum_mod_forum_subscriptions_db_mailqueue_update_data($queue_data);
                    
                    // continue to the next recipient
                    continue;
                }
                
                // prepare the headers for the email
                $Textmsg = $mail_data["mailmessage"];
                $headers      = "From: $sender_name <$sender_address>\r\n";
                $headers      .= "Message-ID: <".$messageid.">\r\n";
                $headers      .= "MIME-Version: 1.0\r\n";
                // set a unique email boundary based on the Phorum message_id
                $bndp          = "fsub_bndp_".$mail_data['message_id'];
                if ($debug_i == 1) {
                    if (function_exists('event_logging_writelog')) {
                        event_logging_writelog(array(
                            "message"   => "Boundary:\n\n".$bndp
                        ));
                    }
                }
                $headers      .= "Content-Type: multipart/mixed; \r\n       boundary=\"".$bndp."\"\r\n\n";
                if ($debug_i == 1) {
                    if (function_exists('event_logging_writelog')) {
                        event_logging_writelog(array(
                            "message"   => "Headers:\n\n".$headers
                        ));
                    }
                }
                $msg           = "This is a multi-part message in MIME format.\n\n";
                $msg          .= "--".$bndp."\n";
                $msg          .= "Content-Type: text/plain; charset=UTF-8\n";
                $msg          .= "Content-Transfer-Encoding: 8bit\n\n";
                
                // add the message body to the email
                $msg          .= $Textmsg."\n";
                
                // if we have attachments, add them to the email
                if (isset($attachments)) {
                    foreach ($attachments as $attachment => $attachment_data) {
                        $msg .= "--".$bndp."\n";
                        $msg .= "Content-Type: $attachment_data[mimetype]; name=\"".$attachment."\"\n";
                        $msg .= "Content-Transfer-Encoding: base64\n";
                        $msg .= "Content-Disposition: attachment;\n";
                        $msg .= "        filename=\"".$attachment."\"\n\n";
                        $msg .= $attachment_data["filedata"]."\n";
                    }
                }
                $msg          .= "--".$bndp."--\n";
                if ($debug_i == 1) {
                    if (function_exists('event_logging_writelog')) {
                        event_logging_writelog(array(
                            "message"   => "Email message:\n\n".substr($msg,0,9999)
                        ));
                    }
                }
                
                $msg_accepted = mail($user_info["email"], $mail_data["mailsubject"], $msg, $headers);
                if ($debug_i == 1) {
                    if (function_exists('event_logging_writelog')) {
                        event_logging_writelog(array(
                            "message"   => "Email accepted by sending server:\n\n".$msg_accepted
                        ));
                    }
                    $debug_i = 0;
                }
                
                if (!is_null($queue_data)) {
                    if ($msg_accepted != 1) {
                        // if the mail server returned an error for this
                        // recipient, save the error to the mail queue
                        $queue_data["error_data"][$user_id] = "Server return: " . $msg_accepted;
                    } else {
                        // if the mail was sent successfully, remove this
                        // recipient from the mail queue
                        unset($queue_data["recipient_ids"][$user_id]);
                    }
                    // if we are in a mail queue and have not sent the message
                    // to every recipient in the queue, update the mail queue
                    if (!empty($queue_data["recipient_ids"]))
                        phorum_mod_forum_subscriptions_db_mailqueue_update_data($queue_data);
                }
            } else {
                // if this recipient is not valid, remove them from the mail
                // queue and update it if there are more recipients
                unset($queue_data["recipient_ids"][$user_id]);
                if (!empty($queue_data["recipient_ids"]))
                    phorum_mod_forum_subscriptions_db_mailqueue_update_data($queue_data);
            }
        }
    }
    if (!is_null($queue_data) && empty($queue_data["recipient_ids"])) {
        // if this is a mail queue and there are no more recipients, delete
        // this mail queue from the mail queue table and clear the last start
        // timestamp
        phorum_mod_forum_subscriptions_db_mailqueue_delete($queue_data["queue_id"]);
        unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
        phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
    }
      
    return $data;
}


/*
* SMTP-Mail-Module v0.8
* made by Thomas Seifert
* email: thomas (at) phorum.org
*
* modified for this module with the ability to send attachments by Joe Curia
*/

function phorum_smtp_send_messages_mod_forum_sub_swift ($data) {
    
    define('SWIFT_DIRECTORY','./mods/smtp_mail/swiftmailer');

    $PHORUM = $GLOBALS["PHORUM"];

    $sender_address = $data['sender_address'];
    $sender_name = $data['sender_name'];
    $address = $data['address'];
    $subject = $data['subject'];
    $message = $data['body'];

    $settings  = $PHORUM['smtp_mail'];
    $settings['auth'] = empty($settings['auth'])?false:true;

    if(!empty($address)){

        try {

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
            foreach ($data["attachments"] as $filename => $attachment) {
                $message->attach(new Swift_Message_Attachment(new Swift_File($attachment["filedata"]), $filename, $attachment["mimetype"]));
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
    }

    unset($recipients);
    unset($message);
    unset($swift);
    unset($smtp);

    // make sure that the internal mail-facility doesn't kick in
    return null;
}
?>
