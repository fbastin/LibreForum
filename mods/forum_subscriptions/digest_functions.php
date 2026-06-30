<?php
        
if(!defined("PHORUM")) return;

function phorum_mod_forum_subscriptions_prepare_digest($frequency, $current_day) {
  
    global $PHORUM;
    
    // set to 1 for debugging messages in the event logging module
    $debug_i = (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"])) ? 1 : 0;
    
    if ($debug_i == 1) {
        if (function_exists('event_logging_writelog')) {
            $prefix = (empty($PHORUM["phorum_mod_forum_subscriptions"]["pre_run_forums_by_digest"][$frequency]))
                ? "Preparing " : "Continuing ";
            $frequency_name = ($frequency == PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY) ? "daily" : "weekly";
            event_logging_writelog(array(
                "message"   => $prefix . $frequency_name . " digest subscriptions. Microtime: " . microtime()
            ));
        }
    }
    
    $forum_subscriptions = phorum_mod_forum_subscriptions_db_subscriptions_get_subscribers_by_frequency($frequency);
    
    if (count($forum_subscriptions)) {
        
        require_once("./include/api/forums.php");
        require_once("./include/format_functions.php");
        $forum_messages = array();
        $digest_id = time();
        
        $end_time = $current_day - 1;
        
        if ($frequency == PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY) {
            $start_time = $current_day - (60 * 60 * 24);
        } elseif ($frequency == PHORUM_MOD_FORUM_SUB_FREQUENCY_WEEKLY) {
            $start_time = $current_day - (60 * 60 * 24 * 7);
        } else {
            return true;
        }
        // get all available forums
        $forums = phorum_api_forums_get();
        
        $pre_run_forums = 
            (!empty($PHORUM["phorum_mod_forum_subscriptions"]["pre_run_forums_by_digest"][$frequency]))
            ? $PHORUM["phorum_mod_forum_subscriptions"]["pre_run_forums_by_digest"][$frequency]
            : array();
        
        foreach ($forum_subscriptions as $forum_id => $subscribers) {
            if (isset($pre_run_forums[$forum_id])) continue;
            if (($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"] + $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"]) < time()) {
                unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
                phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
                if ($debug_i == 1) {
                    if (function_exists('event_logging_writelog')) {
                        $frequency_name = ($frequency == PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY) ? "Daily" : "Weekly";
                        event_logging_writelog(array(
                            "message"   => $frequency_name . " digest exceeded time limit.  To be continued at next cronjob. Microtime: " . microtime()
                        ));
                    }
                }
                return false;
            }
            if ($forum_id == 0
                || $forums[$forum_id]["vroot"] == $forum_id) {
                $forum_messages[$forum_id]["all_posts"] = array();
                $forum_messages[$forum_id]["new_threads"] = array();
                // get all daily messages
                foreach ($forums as $forum) {
                    // skip folders or forums not in the current vroot
                    if ($forum['folder_flag']
                        || $forum["vroot"] != $forum_id) continue;
                    $forum_messages[$forum["forum_id"]]["all_posts"] = array();
                    $forum_messages[$forum["forum_id"]]["new_threads"] = array();
                    $messages = phorum_mod_forum_subscriptions_db_messages_get_messages_by_frequency($forum["forum_id"],$frequency, $start_time, $end_time);
                    
                    if (count($messages)) {
                        foreach ($messages as $message_id => $message) {
                            if ($message["status"] != PHORUM_STATUS_APPROVED) continue;
                            if(empty($PHORUM["phorum_mod_forum_subscriptions"]["send_only_new_threads"])
                                || empty($message["parent_id"])) {
                                $forum_messages[$forum["forum_id"]]["all_posts"][] = $message_id;
                                $forum_messages[$forum_id]["all_posts"][] = $message_id;      
                            }
                            if (empty($message["parent_id"])) {
                                $forum_messages[$forum["forum_id"]]["new_threads"][] = $message_id;
                                $forum_messages[$forum_id]["new_threads"][] = $message_id;      
                            }
                        }
                    }
                }
            } elseif (empty($forum_messages[$forum_id])) {
                $forum_messages[$forum_id]["all_posts"] = array();
                $forum_messages[$forum_id]["new_threads"] = array();
                $messages = phorum_mod_forum_subscriptions_db_messages_get_messages_by_frequency($forum_id,$frequency, $start_time, $end_time);
                
                if (count($messages)) {
                    foreach ($messages as $message_id => $message) {
                        // skip unapproved messages
                        if ($message["status"] != PHORUM_STATUS_APPROVED) continue;
                        if(empty($PHORUM["phorum_mod_forum_subscriptions"]["send_only_new_threads"])
                            || empty($message["parent_id"])) {
                            $forum_messages[$forum_id]["all_posts"][] = $message_id;
                        }
                        if (empty($message["parent_id"])) {
                            $forum_messages[$forum_id]["new_threads"][] = $message_id;
                        }
                    }
                }
            }
            $all_posts_subscribers = array();
            $new_threads_subscribers = array();
            foreach($subscribers as $subscriber) {
                if ($subscriber["sub_type"] == PHORUM_MOD_FORUM_SUB_SUBSCRIBE_ALL) {
                    $all_posts_subscribers[$subscriber["user_id"]] = $subscriber["user_id"];
                } elseif ($subscriber["sub_type"] == PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NEW_THREAD) {
                    $new_threads_subscribers[$subscriber["user_id"]] = $subscriber["user_id"];
                }
            }
            if (count($all_posts_subscribers) && !empty($forum_messages[$forum_id]["all_posts"])) {
                if (empty($forums[$forum_id]["vroot"])) {
                    $sitename = $PHORUM['title'];
                } else {
                    $sitename = strip_tags($forums[$forums[$forum_id]["vroot"]]["name"]);
                }
                $mail_data = array(
                    "sitename"    => $sitename,
                    "forumname"   => ($forum_id == 0 || $forums[$forum_id]["vroot"] == $forum_id) ? $sitename : strip_tags($forums[$forum_id]["name"]),
                    "forum_id"    => $forum_id,
                    "vroot"       => ($forum_id == 0) ? 0 : $forums[$forum_id]["vroot"],
                    "user_id"     => NULL,
                    "messages"    => $forum_messages[$forum_id]["all_posts"],
                    "frequency"   => $frequency,
                    "digest_id"   => $digest_id . "_" . $forum_id . "_" . PHORUM_MOD_FORUM_SUB_SUBSCRIBE_ALL,
                    "digest_start"  => phorum_date($PHORUM['short_date'], $start_time),
                    "forum_subscriptions_url" => phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=forum_subscriptions"),
                );
                phorum_mod_forum_subscriptions_db_mailqueue_add_digest($all_posts_subscribers, $mail_data);
                if ($debug_i == 1) {
                    if (function_exists('event_logging_writelog')) {
                        $frequency_name = ($frequency == PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY) ? "Daily" : "Weekly";
                        event_logging_writelog(array(
                            "message"   => $frequency_name . " Digest All Posts mail_data:\n\n".print_r($mail_data,true)
                        ));
                    }
                }
            }
            if (count($new_threads_subscribers) && !empty($forum_messages[$forum_id]["new_threads"])) {
                if (empty($forums[$forum_id]["vroot"])) {
                    $sitename = $PHORUM['title'];
                } else {
                    $sitename = strip_tags($forums[$forums[$forum_id]["vroot"]]["name"]);
                }
                $mail_data = array(
                    "sitename"    => $sitename,
                    "forumname"   => ($forum_id == 0 || $forums[$forum_id]["vroot"] == $forum_id) ? $sitename : strip_tags($forums[$forum_id]["name"]),
                    "forum_id"    => $forum_id,
                    "vroot"       => ($forum_id == 0) ? 0 : $forums[$forum_id]["vroot"],
                    "user_id"     => NULL,
                    "messages"    => $forum_messages[$forum_id]["new_threads"],
                    "frequency"   => $frequency,
                    "digest_id"   => $digest_id . "_" . $forum_id . "_" . PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NEW_THREAD,
                    "digest_start"  => phorum_date($PHORUM['short_date'], $start_time),
                    "forum_subscriptions_url" => phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=forum_subscriptions"),
                );
                phorum_mod_forum_subscriptions_db_mailqueue_add_digest($new_threads_subscribers, $mail_data);
                if ($debug_i == 1) {
                    if (function_exists('event_logging_writelog')) {
                        $frequency_name = ($frequency == PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY) ? "Daily" : "Weekly";
                        event_logging_writelog(array(
                            "message"   => $frequency_name . " Digest New Threads mail_data:\n\n".print_r($mail_data,true)
                        ));
                    }
                }
            }
            $pre_run_forums[$forum_id] = $forum_id;
            $PHORUM["phorum_mod_forum_subscriptions"]["pre_run_forums_by_digest"][$frequency] = $pre_run_forums;
            phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
        }
    } else {
        if ($debug_i == 1) {
            if (function_exists('event_logging_writelog')) {
                $frequency_name = ($frequency == PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY) ? "daily" : "weekly";
                event_logging_writelog(array(
                    "message"   => "No " . $frequency_name . " digest subscriptions."
                ));
            }
        }
    }
    
    return true;
}
?>
