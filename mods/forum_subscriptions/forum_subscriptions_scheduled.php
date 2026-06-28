<?php
        
if(!defined("PHORUM")) return;

function phorum_mod_forum_subscriptions_functions_scheduled() {
    global $PHORUM;
    
    if (empty($PHORUM["phorum_mod_forum_subscriptions_temp"]["scheduled_start"]))
        $PHORUM["phorum_mod_forum_subscriptions_temp"]["scheduled_start"] = time();
    
    $max_time_limit = (empty($PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"])) 
        ? 60 : $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"];
    $current_time_limit = ini_get('max_execution_time');
    if ($max_time_limit > $current_time_limit
        && $current_time_limit != 0
        && function_exists("set_time_limit")) {
        set_time_limit($max_time_limit);
        $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"] = ini_get('max_execution_time');
    }
    
    $tz_offset = ($PHORUM["tz_offset"] != -99) ? $PHORUM["tz_offset"] : 0;
    $localtime = localtime(time() + ($tz_offset * 60 * 60));
    
    // set ahead 1 day for testing - [3],[6]
    $current_day = mktime(0,0,0,$localtime[4]+1,$localtime[3], $localtime[5]+1900) - ($tz_offset * 60 * 60);
    $week_day = $localtime[6];
    $localtime = mktime($localtime[2],$localtime[1],$localtime[0],$localtime[4]+1,$localtime[3], $localtime[5]+1900) - ($tz_offset * 60 * 60);
    
    /* development test code
    //return;
    //unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
    //unset($PHORUM["phorum_mod_forum_subscriptions"]["last_weekly_digest"]);
    //event_logging_writelog(array("message"   => "forum_subscriptions settings:\n\n".print_r($PHORUM["phorum_mod_forum_subscriptions"],true)));
    //event_logging_writelog(array("message"   => "times:\n\n".print_r(array($current_day, $week_day, $localtime),true)));
    //*/
    
    // we are done if:
    //   the mail queue is not enabled
    //   or there is a timestamp for another instance of this script
    //     and the other script was started less than 10 minutes ago
    //   or 
    //     a mail queue has not been inserted into the mail queue table
    //       or there are no queues in the table which are delayed due to errors
    //         and the last queue was inserted before the last time the table was empty
    //     and daily digests are not allowed
    //       or at least one daily digest has been run
    //         and the current day is the same as that of the last digest
    //     and weekly digests are not allowed
    //       or at least one weekly digest has been run
    //         and the current day is less then that of the last digest day + 7 days
    //       or the week day is not the day the admin selected to send weekly digests
    if (empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_mail_queue"]) 
        || (!empty($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"])
            && $PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"] + ($PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"] * 2) > time())
        || (
            ((empty($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_insert"])
                || (empty($PHORUM["phorum_mod_forum_subscriptions"]["queue_error"]) && !empty($PHORUM["phorum_mod_forum_subscriptions"]["last_empty_queue"])
                    && $PHORUM["phorum_mod_forum_subscriptions"]["last_queue_insert"] < $PHORUM["phorum_mod_forum_subscriptions"]["last_empty_queue"]))) 
            && (empty($PHORUM["phorum_mod_forum_subscriptions"]["allow_daily_digests"])
                || (!empty($PHORUM["phorum_mod_forum_subscriptions"]["last_daily_digest"])
                    && $current_day <= $PHORUM["phorum_mod_forum_subscriptions"]["last_daily_digest"]))
            && (empty($PHORUM["phorum_mod_forum_subscriptions"]["allow_weekly_digests"])
                || (!empty($PHORUM["phorum_mod_forum_subscriptions"]["last_weekly_digest"])
                    && $current_day < ($PHORUM["phorum_mod_forum_subscriptions"]["last_weekly_digest"] + (60*60*24*7)))
                || $week_day != $PHORUM["phorum_mod_forum_subscriptions"]["weekly_digest_day"])
            )
        ) return;
    
    // pull in the mail queue database functions
    require_once ("./mods/forum_subscriptions/db_functions.php");
    
    // process a daily digest if:
    //  daily digests are allowed
    //    and no daily digests have been run
    //      or the current day is greater than the last daily digest
    if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["allow_daily_digests"])
        && (empty($PHORUM["phorum_mod_forum_subscriptions"]["last_daily_digest"])
            || $current_day >= $PHORUM["phorum_mod_forum_subscriptions"]["last_daily_digest"])) {
        $PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"] = time();
        phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
        require_once ("./mods/forum_subscriptions/digest_functions.php");
        $digest_complete = phorum_mod_forum_subscriptions_prepare_digest(PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY, $current_day);
        if ($digest_complete) {
            $PHORUM["phorum_mod_forum_subscriptions"]["last_daily_digest"] = $localtime;
            unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
            unset($PHORUM["phorum_mod_forum_subscriptions"]["pre_run_forums_by_digest"][PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY]);
            phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
            if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"])) {
                if (function_exists('event_logging_writelog')) {
                    $log_message = "Daily digest prepared. Microtime: " . microtime();
                    event_logging_writelog(array(
                        "message"    => $log_message
                    ));
                }
            }
        }
        if ($PHORUM["phorum_mod_forum_subscriptions_temp"]["scheduled_start"] + $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"] - time() > 29) {
            unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
            phorum_mod_forum_subscriptions_functions_scheduled();
        }
        return;
    }
    
    // process a weekly digest if:
    //  weekly digests are allowed
    //    and no weekly digests have been run
    //      or the current day is greater then that of the last digest day + 7 days
    //    and this week day is the day the admin selected to send weekly digests 
    if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["allow_weekly_digests"])
        && (empty($PHORUM["phorum_mod_forum_subscriptions"]["last_weekly_digest"])
            || $current_day >= $PHORUM["phorum_mod_forum_subscriptions"]["last_weekly_digest"] + (60*60*24*7))
        && $week_day == $PHORUM["phorum_mod_forum_subscriptions"]["weekly_digest_day"]) {
        $PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"] = time();
        phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
        require_once ("./mods/forum_subscriptions/digest_functions.php");
        $digest_complete = phorum_mod_forum_subscriptions_prepare_digest(PHORUM_MOD_FORUM_SUB_FREQUENCY_WEEKLY, $current_day);
        if ($digest_complete) {
            $PHORUM["phorum_mod_forum_subscriptions"]["last_weekly_digest"] = $current_day;
            unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
            unset($PHORUM["phorum_mod_forum_subscriptions"]["pre_run_forums_by_digest"][PHORUM_MOD_FORUM_SUB_FREQUENCY_WEEKLY]);
            phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
            if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"])) {
                if (function_exists('event_logging_writelog')) {
                    $log_message = "Weekly digest prepared. Microtime: " . microtime();
                    event_logging_writelog(array(
                        "message"    => $log_message
                    ));
                }
            }
        }
        if ($PHORUM["phorum_mod_forum_subscriptions_temp"]["scheduled_start"] + $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"] - time() > 29) {
            unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
            phorum_mod_forum_subscriptions_functions_scheduled();
        }
        return;
    }
    
    // grab the first mail queue from the mail queue table
    $queue_data = phorum_mod_forum_subscriptions_db_mailqueue_get();
    
    // if there are no mail queues, set a timestamp reporting this fact
    if (empty($queue_data)) {
        if (empty($PHORUM["phorum_mod_forum_subscriptions"]["queue_error"])) {
            $PHORUM["phorum_mod_forum_subscriptions"]["last_empty_queue"] = time();
            phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
        }
        
        // if debugging is enabled, log the fact that all mail queues have been
        // processed
        if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"])) {
            if (function_exists('event_logging_writelog')) {
                $log_message = (empty($PHORUM["phorum_mod_forum_subscriptions"]["queue_error"]))
                    ? "All Forum Subscriptions mail queues have been processed."
                    : "No current mail queue, waiting to retry one or more queues with errors.";
                event_logging_writelog(array(
                    "message"    => $log_message
                ));
            }
        }
        return;
    }
    
    // if this is a digest, assign a message_id based on the digest forum_id
    if (!empty($queue_data["mail_data"]["digest_id"]))
        $queue_data["mail_data"]["message_id"] = $queue_data["mail_data"]["digest_id"];
    
    // if all of the recipients in the queue have received errors or if this 
    // particular queue has timed out due to a PHP error, then increment the 
    // error count for this queue, set the queue to run 30 minutes later per 
    // error count (ie. 30 minutes, 60 minutes, or 90 minutes later), and clear
    // the error data.
    if (count($queue_data["recipient_ids"]) == (is_array($queue_data["error_data"]) ? count($queue_data["error_data"]) : 0)
        || (!empty($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"])
            && $PHORUM["phorum_mod_forum_subscriptions"]["last_queue_message_id"] == $queue_data["mail_data"]["message_id"])) {
        $queue_data["error_count"] += 1;
        $log_message = "Forum Subscriptions mail queue for message_id " . $queue_data["mail_data"]["message_id"];
        if ($queue_data["error_count"] == 4) {
            unset($PHORUM["phorum_mod_forum_subscriptions"]["queue_error"][$queue_data["mail_data"]["message_id"]]);
            $queue_data["dead_queue"] = 1;
            $log_message .= " reached the error limit and will no longer be processed.";
        } else if ($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_message_id"] == $queue_data["mail_data"]["message_id"]) {
            $PHORUM["phorum_mod_forum_subscriptions"]["queue_error"][$queue_data["mail_data"]["message_id"]] = 1;
            $queue_data["error_delay_start"] = time() + ($queue_data["error_count"] * 300);
            $log_message .= " has dumped a PHP error " . $queue_data["error_count"] . " time(s) and will be processed again in " . ($queue_data["error_count"] * 5) . " minutes.";
        } else {
            $PHORUM["phorum_mod_forum_subscriptions"]["queue_error"][$queue_data["mail_data"]["message_id"]] = 1;
            $queue_data["error_delay_start"] = time() + ($queue_data["error_count"] * 1800);
            $log_message .= " has errored out " . $queue_data["error_count"] . " time(s) and will be processed again in " . ($queue_data["error_count"] * 30) . " minutes."; 
        }
        unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_message_id"]);
        unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
        phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
        phorum_mod_forum_subscriptions_db_mailqueue_update_data($queue_data);
        
        if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"])) {
            if (function_exists('event_logging_writelog')) {
                event_logging_writelog(array(
                    "message"    => $log_message
                ));
            }
        }
        if ($PHORUM["phorum_mod_forum_subscriptions_temp"]["scheduled_start"] + $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"] - time() > 29) {
            phorum_mod_forum_subscriptions_functions_scheduled();
        }
        return;
    }
    
    // save the start time for this instance.  this will be used to keep other
    // instances of this script from running and to avoid this script timing out
    $PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"] = time();
    $PHORUM["phorum_mod_forum_subscriptions"]["last_queue_message_id"] = $queue_data["mail_data"]["message_id"];
    phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
    
    // process the current mail queue
    // if logging is enabled, log the fact that the current queue has been run
    if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"])) {
        if (function_exists('event_logging_writelog')) {
            $log_message = "Starting mail queue for message_id " . $queue_data["mail_data"]["message_id"] . ". Microtime: " . microtime();
            event_logging_writelog(array(
                "message"    => $log_message
            ));
        }
    }
    phorum_mod_forum_subscriptions_after_post(NULL, $queue_data);
    
    if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["queue_error"][$queue_data["mail_data"]["message_id"]])) {
        unset($PHORUM["phorum_mod_forum_subscriptions"]["queue_error"][$queue_data["mail_data"]["message_id"]]);
        phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
    }
    
    // if logging is enabled, log the fact that the current queue has been run
    if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"])) {
        if (function_exists('event_logging_writelog')) {
            $log_message = "Finished mail queue for message_id " . $queue_data["mail_data"]["message_id"] . ". Microtime: " . microtime();
            event_logging_writelog(array(
                "message"    => $log_message
            ));
        }
    }
    if ($PHORUM["phorum_mod_forum_subscriptions_temp"]["scheduled_start"] + $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"] - time() > 29) {
        unset($PHORUM["phorum_mod_forum_subscriptions"]["last_queue_start"]);
        phorum_mod_forum_subscriptions_functions_scheduled();
    }
    return;
}
?>
