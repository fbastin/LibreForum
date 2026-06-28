<?php

if (!defined("PHORUM")) return;

define ("PHORUM_MOD_FORUM_SUB_DB_VERSION", 1);

define ("PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NONE", -1);
define ("PHORUM_MOD_FORUM_SUB_SUBSCRIBE_ALL", 0);
define ("PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NEW_THREAD", 1);

define ("PHORUM_MOD_FORUM_SUB_FREQUENCY_NEVER", -1);
define ("PHORUM_MOD_FORUM_SUB_FREQUENCY_IMMEDIATE", 0);
define ("PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY", 1);
define ("PHORUM_MOD_FORUM_SUB_FREQUENCY_WEEKLY", 2);

define ("PHORUM_MOD_FORUM_SUB_FORUM_GLOBAL", 0);
define ("PHORUM_MOD_FORUM_SUB_FORUM_ONLY", 1);

// Check the existence of the mail queue table. If the table does not exist, 
// create it.
// input: base table name
// output: full table name
function phorum_mod_forum_subscriptions_db_check_table ($table) {

    global $PHORUM;
    
    if (empty($PHORUM["phorum_mod_forum_subscriptions"]["db_version"])
        || $PHORUM["phorum_mod_forum_subscriptions"]["db_version"] < PHORUM_MOD_FORUM_SUB_DB_VERSION)
        phorum_mod_forum_subscriptions_db_upgrade_db();
        
    // formulate the full table name
    $full_table = $PHORUM["DBCONFIG"]["table_prefix"]."_mod_forum_sub_" . $table;
    
    // make sure the full table exists
    $sql = "SELECT * FROM $full_table LIMIT 1";
    $error = phorum_db_interact(DB_RETURN_ERROR, $sql, NULL, DB_MASTERQUERY);
    
    if ($error !== NULL) {
        // create the full table
        phorum_mod_forum_subscriptions_db_create_table ($full_table);
    }
    
    return $full_table;
}

// create the mail queue table.
// input: mail queue table name
// output: none
function phorum_mod_forum_subscriptions_db_create_table ($full_table) {
    
    global $PHORUM;
    
    switch ($full_table) {
        
        case $PHORUM["DBCONFIG"]["table_prefix"]."_mod_forum_sub_mailqueue":
            $sql = "CREATE TABLE $full_table (
                queue_id          int unsigned      NOT NULL auto_increment,
                recipient_ids     mediumtext        NOT NULL,
                mail_data         mediumtext        NOT NULL,
                forumname         varchar(50)       NOT NULL default '',
                sitename          varchar(255)      NOT NULL default '',
                author            varchar(255)      NOT NULL default '',
                subject           varchar(255)      NOT NULL default '',
                full_body         mediumtext        NOT NULL default '',
                plain_body        mediumtext        NOT NULL default '',
                attachment_data   mediumtext            NULL,
                error_data        mediumtext            NULL,
                error_count       int unsigned      NOT NULL default '0',
                error_delay_start int unsigned      NOT NULL default '0',
                dead_queue        int unsigned      NOT NULL default '0',
                insert_timestamp  int unsigned      NOT NULL default '0',
                
                PRIMARY KEY (queue_id),
                INDEX (insert_timestamp)
              )";
            break;
        case $PHORUM["DBCONFIG"]["table_prefix"]."_mod_forum_sub_subscriptions":
            $sql = "CREATE TABLE $full_table (
                user_id           int unsigned      NOT NULL,
                forum_id          int unsigned      NOT NULL,
                sub_type          int unsigned      NOT NULL default '0',
                frequency         int unsigned      NOT NULL default '0',
                
                INDEX (user_id),
                INDEX (forum_id),
                INDEX (user_id, forum_id),
                INDEX (forum_id, frequency)
              )";
            break;    
    }
    
    $error = phorum_db_interact(DB_RETURN_ERROR, $sql, NULL, DB_MASTERQUERY);
    
    if ($error !== NULL) {
        //log the error if enabled
        if (function_exists('event_logging_writelog')) {
            event_logging_writelog(array(
                "message"	=> "Error creating $full_table table",
                "details"   => "SQL:\n$sql\n\nError:\n$error",
            ));
        }
    }
}

// add a mail queue to the mail queue table
// input: recipient_ids, mail_data, attachment_data (optional)
// output: none
function phorum_mod_forum_subscriptions_db_mailqueue_add ($recipient_ids, $mail_data, $attachment_data = NULL) {
  
    global $PHORUM;
    
    // make sure the mail queue table has been created
    $mailqueue_table = phorum_mod_forum_subscriptions_db_check_table("mailqueue");
    
    // sanitize the mail data for storage in the database
    foreach($mail_data as $key => $data) {
        if (!is_numeric($data))
            phorum_db_sanitize_mixed($mail_data[$key], "string");
    }
    
    // strip out certain portions of the mail data which will not serialize well
    $forumname = $mail_data["forumname"];
    $sitename = $mail_data["sitename"];
    $author = $mail_data["author"];
    $subject = $mail_data["subject"];
    $full_body = $mail_data["full_body"];
    $plain_body = $mail_data["plain_body"];
    unset($mail_data["forumname"]);
    unset($mail_data["sitename"]);
    unset($mail_data["author"]);
    unset($mail_data["subject"]);
    unset($mail_data["full_body"]);
    unset($mail_data["plain_body"]);
    
    // if we have attachments, prepare them for insertion into the database
    if (is_null($attachment_data)) {
        $attachment_data_field = "";
        $attachment_data = "";
    } else {
        foreach($attachment_data as $key => $data) {
            if (!is_numeric($data))
                phorum_db_sanitize_mixed($attachment_data[$key], "string");
        }
        $attachment_data_field = ", attachment_data";
        $attachment_data = ", '" . serialize($attachment_data) . "'";
    }
    
    $sql = "INSERT INTO $mailqueue_table
                   (recipient_ids, 
                    mail_data, 
                    forumname, 
                    sitename, 
                    author, 
                    subject, 
                    full_body, 
                    plain_body,
                    insert_timestamp
                    " . $attachment_data_field . "
                    )
            VALUES ('" . serialize($recipient_ids) . "',
                    '" . serialize($mail_data) . "',
                    '$forumname',
                    '$sitename',
                    '$author',
                    '$subject',
                    '$full_body',
                    '$plain_body',
                    " . time() . $attachment_data . "
                    )";
    
    // insert the mail queue into the mail queue table
    $queue_id = phorum_db_interact(
        DB_RETURN_NEWID,
        $sql);
    
    // if the mail queue was inserted properly, update the "last_queue_insert"
    // timestamp
    if (!empty($queue_id)) {
        $PHORUM["phorum_mod_forum_subscriptions"]["last_queue_insert"] = time();
        phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
    } else if (function_exists('event_logging_writelog')) {
        $log_message = "Unknown error while adding message_id " . $mail_data["message_id"] . " to the mail queue table.";
        event_logging_writelog(array(
            "message"    => $log_message
        ));
    }
}

// add a digest mail queue to the mail queue table
// input: recipient_ids, mail_data
// output: none
function phorum_mod_forum_subscriptions_db_mailqueue_add_digest ($recipient_ids, $mail_data) {
  
    global $PHORUM;
    
    // make sure the mail queue table has been created
    $mailqueue_table = phorum_mod_forum_subscriptions_db_check_table("mailqueue");

    // sanitize the mail data for storage in the database
    foreach($mail_data as $key => $data) {
        if (!is_numeric($data))
            phorum_db_sanitize_mixed($mail_data[$key], "string");
    }
    
    // strip out certain portions of the mail data which will not serialize well
    $forumname = $mail_data["forumname"];
    $sitename = $mail_data["sitename"];
    unset($mail_data["forumname"]);
    unset($mail_data["sitename"]);
    
    $sql = "INSERT INTO $mailqueue_table
                   (recipient_ids, 
                    mail_data, 
                    forumname, 
                    sitename, 
                    insert_timestamp
                    )
            VALUES ('" . serialize($recipient_ids) . "',
                    '" . serialize($mail_data) . "',
                    '$forumname',
                    '$sitename',
                    " . time() . "
                    )";
    
    // insert the mail queue into the mail queue table
    $queue_id = phorum_db_interact(
        DB_RETURN_NEWID,
        $sql);
    
    // if the mail queue was inserted properly, insert the messages in the 
    // digests table and update the "last_queue_insert" timestamp
    if (!empty($queue_id)) {
        $PHORUM["phorum_mod_forum_subscriptions"]["last_queue_insert"] = time();
        phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
    } else if (function_exists('event_logging_writelog')) {
        $log_message = "Unknown error while adding a digest to the mail queue table.";
        event_logging_writelog(array(
            "message"    => $log_message
        ));
    }

}

// Delete a mail queue
// input: queue_id
// output: none
function phorum_mod_forum_subscriptions_db_mailqueue_delete ($queue_id) {
    
    global $PHORUM;
    
    // make sure the mail queue table has been created
    $mailqueue_table = phorum_mod_forum_subscriptions_db_check_table("mailqueue");
    
    $sql = "DELETE FROM $mailqueue_table
            WHERE queue_id = '$queue_id'";
    
    // delete the queue
    $error = phorum_db_interact(DB_RETURN_ERROR, $sql, NULL);
    
    if ($error !== NULL) {
        //log the error if enabled
        if (function_exists('event_logging_writelog')) {
            event_logging_writelog(array(
                "message"	=> "Error deleting queue from the mail queue table",
                "details"   => "SQL:\n$sql\n\nError:\n$error",
            ));
        }
    }
    
}

// Get the first available queue, sorted by insert timestamp
//   filter out queues with more than 3 errors (dead_queue's) and queues which
//   have been delayed due to past errors
// input: none
// output: the queue_data array:
//   queue_id, 
//   recipient_ids, 
//   mail_data, 
//   forumname,
//   sitename,
//   author,
//   subject,
//   full_body,
//   plain_body,
//   attachment_data,
//   error_data
function phorum_mod_forum_subscriptions_db_mailqueue_get () {
    
    global $PHORUM;
    
    // make sure the mail queue table has been created
    $mailqueue_table = phorum_mod_forum_subscriptions_db_check_table("mailqueue");
    
    $sql = "SELECT queue_id,
                   recipient_ids, 
                   mail_data, 
                   forumname,
                   sitename,
                   author,
                   subject,
                   full_body,
                   plain_body,
                   attachment_data,
                   error_data,
                   error_count,
                   error_delay_start,
                   dead_queue,
                   insert_timestamp
            FROM $mailqueue_table
            WHERE dead_queue != 1
            AND error_delay_start <= " . time() . "
            ORDER BY insert_timestamp ASC
            LIMIT 1";
    
    // retrieve a mail queue (if any)
    $queue_data = phorum_db_interact(DB_RETURN_ASSOC, $sql);
    
    // if we have an actual queue, process the various fields
    if (!empty($queue_data["recipient_ids"])) {
        $queue_data["recipient_ids"] = unserialize($queue_data["recipient_ids"]);
        $queue_data["mail_data"] = unserialize($queue_data["mail_data"]);
        $queue_data["mail_data"]["forumname"] = $queue_data["forumname"];
        $queue_data["mail_data"]["sitename"] = $queue_data["sitename"];
        $queue_data["mail_data"]["author"] = $queue_data["author"];
        $queue_data["mail_data"]["subject"] = $queue_data["subject"];
        $queue_data["mail_data"]["full_body"] = $queue_data["full_body"];
        $queue_data["mail_data"]["plain_body"] = $queue_data["plain_body"];
        unset($queue_data["forumname"]);
        unset($queue_data["sitename"]);
        unset($queue_data["author"]);
        unset($queue_data["subject"]);
        unset($queue_data["full_body"]);
        unset($queue_data["plain_body"]);
    }

    if (!empty($queue_data["attachment_data"])) {
        $queue_data["attachment_data"] = unserialize($queue_data["attachment_data"]);
    }
    if (!empty($queue_data["error_data"])) {
        $queue_data["error_data"] = unserialize($queue_data["error_data"]);
    }
    return $queue_data;
    
}

// Update a mail queue (either to remove a recipient or add error_data)
// input: the queue_data array
// output: none
function phorum_mod_forum_subscriptions_db_mailqueue_update_data ($queue_data) {
    
    global $PHORUM;
    
    // make sure the mail queue table has been created
    $mailqueue_table = phorum_mod_forum_subscriptions_db_check_table("mailqueue");
    
    $sql = "UPDATE $mailqueue_table
            SET recipient_ids = '" . serialize($queue_data["recipient_ids"]) . "' ";
    if (!is_null($queue_data["error_data"])) {
        foreach($queue_data["error_data"] as $key => $data) {
            if (!is_numeric($data))
                phorum_db_sanitize_mixed($queue_data["error_data"][$key], "string");
        }
        $sql .= ", error_data = '" . serialize($queue_data["error_data"]) . "' ";
    }
    $sql .= ", error_count = '" . $queue_data["error_count"] . "'
             , error_delay_start = '" . $queue_data["error_delay_start"] . "'
             , dead_queue = '" . $queue_data["dead_queue"] . "' ";
             
    $sql .= "WHERE queue_id = " . (int)$queue_data["queue_id"] . ";";
    
    // update the mail queue
    $error = phorum_db_interact(DB_RETURN_ERROR, $sql);
    
    if ($error !== NULL) {
        //log the error if enabled
        if (function_exists('event_logging_writelog')) {
            event_logging_writelog(array(
                "message"	=> "Error updating queue in the mail queue table",
                "details"   => "SQL:\n$sql\n\nError:\n$error",
            ));
        }
    }
    
}

// Get messages for a single forum in a set frequency
// input: forum_id, frequency, start_time, end_time
// output: an array of messages indexed by message_id
function phorum_mod_forum_subscriptions_db_messages_get_messages_by_frequency($forum_id, $frequency, $start_time, $end_time) {
    
    global $PHORUM;
    
    $return = array();
    
    // we are done if the admin has opted to ignore selected forums and this is 
    // one of those forums
    if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["ignore_selected_forums"])
        && !empty($PHORUM["phorum_mod_forum_subscriptions"]["forums_to_ignore"][$forum_id]))
        return $return;
        
    
    $messages = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM   {$PHORUM['message_table']}
         WHERE  forum_id = $forum_id
         AND (datestamp >= $start_time 
              AND datestamp <= $end_time)
         ORDER BY datestamp",
        NULL,
        DB_MASTERQUERY
    );

    foreach ($messages as $message)
    {
        $message['meta'] = empty($message['meta'])
                         ? array()
                         : unserialize($message['meta']);

        $return[$message['message_id']] = $message;
    }

    return $return;
}

// Delete a subscriber
// input: user_id, forum_id (optional)
// output: none
function phorum_mod_forum_subscriptions_db_subscriptions_delete ($user_id, $forum_id = NULL) {
    
    global $PHORUM;
    
    // make sure the subscriptions table has been created
    $subscriptions_table = phorum_mod_forum_subscriptions_db_check_table("subscriptions");
    
    $sql = "DELETE FROM $subscriptions_table
            WHERE user_id = $user_id";

    // if a forum_id was given, delete only the subscription for the given forum_id            
    if ($forum_id !== NULL) {
        $sql .= " 
             AND forum_id = $forum_id
             LIMIT 1";
     }
    
    // delete the subscriber
    $error = phorum_db_interact(DB_RETURN_ERROR, $sql, NULL);
    
    if ($error !== NULL) {
        //log the error if enabled
        if (function_exists('event_logging_writelog')) {
            event_logging_writelog(array(
                "message"	=> "Error deleting user_id $user_id from the subscriptions table for forum_id $forum_id.",
                "details"   => "SQL:\n$sql\n\nError:\n$error",
            ));
        }
    }
    
}

// Get a subscriber from the subscriptions table
// input: user_id, forum_id
// output: array containing user_id, forum_id, sub_type, frequency
function phorum_mod_forum_subscriptions_db_subscriptions_get_subscriber ($user_id, $forum_id = NULL) {
    
    global $PHORUM;
    
    // make sure the subscriptions table has been created
    $subscriptions_table = phorum_mod_forum_subscriptions_db_check_table("subscriptions");
    
    $sql =  "SELECT user_id, forum_id, sub_type, frequency 
             FROM $subscriptions_table
             WHERE user_id = $user_id";
    
    // if a forum_id was given, get only the subscription for the given forum_id
    if ($forum_id !== NULL) {
        $sql .= " 
             AND forum_id = $forum_id
             LIMIT 1";
        // if no forum_id was given return an array of all rows for the given
        // user_id
        $return_val = DB_RETURN_ASSOC;
    } else {
        $return_val = DB_RETURN_ASSOCS;
    }

    // retrieve a subscriber (if any)
    $subscriber = phorum_db_interact($return_val, $sql);

    // When a forum_id is given, DB_RETURN_ASSOC returns a single row (or NULL).
    // Normalize it to an array of rows so the loop below works and to avoid a
    // PHP 8 fatal (count(null) / accessing a string offset on a scalar row).
    if ($forum_id !== NULL) {
        $subscriber = empty($subscriber) ? array() : array($subscriber);
    }

    $forum_subscriptions = array();

    if (count($subscriber)) {
        $forums = array();
        
        foreach ($subscriber as $key => $data) {
            $forums[$data["forum_id"]] = $data["forum_id"];
            $forum_subscriptions[$data["forum_id"]] = $data;
        }
        
        $forums = phorum_db_get_forums($forums);
        
        foreach ($forums as $forum_id => $forum) {
            if (isset($forum_subscriptions[$forum["vroot"]]) && $forum["vroot"] != $forum_id)
                unset($forum_subscriptions[$forum_id]);
        }
    }
    
    return $forum_subscriptions;
    
}

// Get subscribers for a particular frequency from the subscriptions table
// input: frequency
// output: array containing rows of user_id, forum_id, sub_type, frequency - 
// indexed by forum_id
function phorum_mod_forum_subscriptions_db_subscriptions_get_subscribers_by_frequency ($frequency) {
    
    global $PHORUM;
    
    // make sure the subscriptions table has been created
    $subscriptions_table = phorum_mod_forum_subscriptions_db_check_table("subscriptions");
    
    $sql = "SELECT user_id, forum_id, sub_type, frequency
            FROM $subscriptions_table
            WHERE frequency = $frequency
            ORDER BY forum_id";
    
    // retrieve a subscriber_id (if any)
    $subscribers = phorum_db_interact(DB_RETURN_ASSOCS, $sql);
    
    $forum_subscriptions = array();
    // if we have subscribers, add them to the forums array indexed by forum_id
    if (count($subscribers)) {
        $forums = array();
        $forum_0 = false;
        
        foreach ($subscribers as $subscriber) {
            if ($subscriber["forum_id"] == 0 ) {
                $forum_0 = true;
            } else {
                $forums[$subscriber["forum_id"]] = $subscriber["forum_id"];
            }
            $forum_subscriptions[$subscriber["forum_id"]][$subscriber["user_id"]] = $subscriber;
        }
        
        $forums = phorum_db_get_forums($forums);
        if ($forum_0) {
            $forums[0]["vroot"] = 0;
        }
        
        foreach ($forum_subscriptions as $forum_id => $subscriber) {
            if (isset($forum_subscriptions[$forums[$forum_id]["vroot"]]) && $forums[$forum_id]["vroot"] != $forum_id) {
                foreach($subscriber as $user_id => $data) {
                    if (isset($forum_subscriptions[$forums[$forum_id]["vroot"]][$user_id]))
                        unset($forum_subscriptions[$forums[$forum_id]["vroot"]][$user_id]);
                }
            }
        }
        
    }
    
    return $forum_subscriptions;
    
}

// Get subscribers for a particular forum from the subscriptions table
// input: forum_id, frequency (optional), forum_only (optional)
// output: array containing rows of user_id, forum_id, sub_type, frequency
function phorum_mod_forum_subscriptions_db_subscriptions_get_subscribers_by_forum ($forum_id, $frequency = NULL, $forum_only = PHORUM_MOD_FORUM_SUB_FORUM_GLOBAL) {
    
    global $PHORUM;
    
    // make sure the subscriptions table has been created
    $subscriptions_table = phorum_mod_forum_subscriptions_db_check_table("subscriptions");
    
    $sql = "SELECT user_id, forum_id, sub_type, frequency
            FROM $subscriptions_table
            WHERE (forum_id = $forum_id";
    
    if ($forum_only == PHORUM_MOD_FORUM_SUB_FORUM_GLOBAL) { 
        $forum = phorum_db_get_forums($forum_id);
        $sql .= " OR forum_id = " . $forum[$forum_id]["vroot"] . ")";
    } else { 
      $sql .= ")";
    }
    
    // if a frequency was given, only select subscribers with that specific 
    // frequency
    if ($frequency !== NULL)
        $sql .= " AND frequency = $frequency";
    
    // retrieve a subscriber_id (if any)
    $subscribers = phorum_db_interact(DB_RETURN_ASSOCS, $sql);
    
    // if we have subscribers, add them to the forums array indexed by forum_id
    if (count($subscribers)) {
        $forum_subscriptions = array();
        $forums = array();
        $forum_0 = false;
        
        foreach ($subscribers as $subscriber) {
            if ($subscriber["forum_id"] == 0 ) {
                $forum_0 = true;
            } else {
                $forums[$subscriber["forum_id"]] = $subscriber["forum_id"];
            }
            $forum_subscriptions[$subscriber["forum_id"]][$subscriber["user_id"]] = $subscriber;
        }
        
        $subscribers = array();
        
        $forums = phorum_db_get_forums($forums);
        if ($forum_0) {
            $forums[0]["vroot"] = 0;
        }
        
        foreach ($forum_subscriptions as $forum_id => $subscriber) {
            if (isset($forum_subscriptions[$forums[$forum_id]["vroot"]]) && $forums[$forum_id]["vroot"] != $forum_id) {
                foreach($subscriber as $user_id => $data) {
                    if (isset($forum_subscriptions[$forums[$forum_id]["vroot"]][$user_id]))
                        unset($forum_subscriptions[$forums[$forum_id]["vroot"]][$user_id]);
                }
            }
        }
        
        foreach ($forum_subscriptions as $forum_id => $subscriber) {
            foreach($subscriber as $user_id => $data) {
                $subscribers[$user_id] = $data;
            }
        }
    }
    
    return $subscribers;
    
}

// add or update a subscriber in the subscription table
// input: user_id, forum_id, sub_type, frequency
// output: none
function phorum_mod_forum_subscriptions_db_subscriptions_save ($user_id, $forum_id, $sub_type, $frequency) {
  
    global $PHORUM;
    
    // make sure the subscriptions table has been created
    $subscriptions_table = phorum_mod_forum_subscriptions_db_check_table("subscriptions");
    
    // make sure we have a valid subscription type
    if ($sub_type != PHORUM_MOD_FORUM_SUB_SUBSCRIBE_ALL
        && $sub_type != PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NEW_THREAD)
        $sub_type = PHORUM_MOD_FORUM_SUB_SUBSCRIBE_ALL;
        
    // make sure we have a valid frequency
    if ($frequency != PHORUM_MOD_FORUM_SUB_FREQUENCY_IMMEDIATE
        && $frequency != PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY
        && $frequency != PHORUM_MOD_FORUM_SUB_FREQUENCY_WEEKLY)
        $frequency = PHORUM_MOD_FORUM_SUB_FREQUENCY_IMMEDIATE;
    
    // check if we are updating a subscriber or creating a new one
    $subscriber = phorum_mod_forum_subscriptions_db_subscriptions_get_subscriber($user_id, $forum_id);
    if (!empty($subscriber)) {
        // update the existing subscriber
        $sql = "UPDATE $subscriptions_table
                SET sub_type  = $sub_type,
                    frequency = $frequency
                WHERE user_id = $user_id
                AND forum_id = $forum_id";
    } else {
        // add the new subscriber
        $sql = "INSERT INTO $subscriptions_table
                       (user_id, 
                        forum_id, 
                        sub_type, 
                        frequency 
                        )
                VALUES ('" . $user_id . "',
                        '" . $forum_id . "',
                        '" . $sub_type . "',
                        '" . $frequency . "'
                        )";
    }
    
    // save the subscriber in the subscriptions table
    $error = phorum_db_interact(DB_RETURN_ERROR, $sql, NULL);
    
    if ($error !== NULL && function_exists('event_logging_writelog')) {
        $log_message = "Unknown error while saving subscription information for user_id $user_id and forum_id $forum_id in the subscriptions table.";
        event_logging_writelog(array(
            "message"    => $log_message
        ));
    }
}

function phorum_mod_forum_subscriptions_db_upgrade_db () {

    global $PHORUM;
    
    if (empty($PHORUM["phorum_mod_forum_subscriptions"]["db_version"]))
        $PHORUM["phorum_mod_forum_subscriptions"]["db_version"] = 0;
      
    $first_upgrade = $PHORUM["phorum_mod_forum_subscriptions"]["db_version"]+1;
    
    for ($i=$first_upgrade;$i<= PHORUM_MOD_FORUM_SUB_DB_VERSION;$i++) {
        require_once("./mods/forum_subscriptions/db/upgrade_$i.php");
    }
}
?>
