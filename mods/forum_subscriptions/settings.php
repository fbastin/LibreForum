<?php

// Make sure that this script is loaded from the admin interface.
if(!defined("PHORUM_ADMIN")) return;

Global $PHORUM;

// pull in the mail queue database functions
require_once ("./mods/forum_subscriptions/db_functions.php");

// update the database tables for this module, if necessary
if (empty($PHORUM["phorum_mod_forum_subscriptions"]["db_version"])
    || $PHORUM["phorum_mod_forum_subscriptions"]["db_version"] < PHORUM_MOD_FORUM_SUB_DB_VERSION)
    phorum_mod_forum_subscriptions_db_upgrade_db();

// Save settings in case this script is run after posting
// the settings form.
if(count($_POST)) {
  
    /* development test code
    //die(print "<pre>".htmlspecialchars(print_r($_POST,true))."</pre>");
    unset($PHORUM["phorum_mod_forum_subscriptions"]);
    $PHORUM["phorum_mod_forum_subscriptions"]["mass_messages"] = 1;
    //*/
    
    // Create the settings array for this module.
    $PHORUM["phorum_mod_forum_subscriptions"]["allow_user_unsubscribe_self"]  = empty($_POST["allow_user_unsubscribe_self"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["allow_attachments"]            = empty($_POST["allow_attachments"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["allow_daily_digests"]          = empty($_POST["allow_daily_digests"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["allow_weekly_digests"]         = empty($_POST["allow_weekly_digests"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["default_frequency"]            = empty($_POST["default_frequency"]) ? PHORUM_MOD_FORUM_SUB_FREQUENCY_IMMEDIATE : (int)$_POST["default_frequency"];
    $PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"]             = empty($_POST["enable_debugging"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["enable_mail_queue"]            = empty($_POST["enable_mail_queue"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["ignore_selected_forums"]       = empty($_POST["ignore_selected_forums"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["forums_to_ignore"]             = empty($_POST["forums_to_ignore"]) ? array() : $_POST["forums_to_ignore"];
    $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"]        = empty($_POST["mail_queue_time_limit"]) ? 60 : (int)$_POST["mail_queue_time_limit"];
    $PHORUM["phorum_mod_forum_subscriptions"]["send_only_new_threads"]        = empty($_POST["send_only_new_threads"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["show_signatures"]              = empty($_POST["show_signatures"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["show_only_subscriptions"]      = empty($_POST["show_only_subscriptions"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["show_vroot_subscriptions"]     = empty($_POST["show_vroot_subscriptions"]) ? 0 : 1;
    $PHORUM["phorum_mod_forum_subscriptions"]["weekly_digest_day"]            = empty($_POST["weekly_digest_day"]) ? 0 : (int)$_POST["weekly_digest_day"];
    
    /* more development code
    if (empty($PHORUM["phorum_mod_forum_subscriptions"]["mass_messages"])) {
        $user_ids = array(1,5,10,11);
        $users = phorum_api_user_get($user_ids,TRUE);
        $ip = "192.168.1.1";
        $forum_ids = array(1,2,4,4,2,1,4); //array(1,2,2,1,1,2,2);
        $thread_ids = array (2 => array(0,57,0,58,0,61), 1=> array(0,63,0,64,0,65), 4 => array(0,0,0,0,0,0));
        $moderator_post = 0;
        $sort = 2;
        $status = 2;
        $closed = 0;
        $user_loop = 0;
        $forum_loop = 0;
        $thread_loop = 0;
        for ($i=1;$i<= 4000;$i++) {
            $message = array (
                "forum_id"        => $forum_ids[$forum_loop],
                "thread"          => $thread_ids[$forum_ids[$forum_loop]][$thread_loop],
                "parent_id"       => $thread_ids[$forum_ids[$forum_loop]][$thread_loop],
                "author"          => $users[$user_ids[$user_loop]]["display_name"],
                "subject"         => "Test $i - " . microtime(),
                "email"           => "",
                "ip"              => $ip,
                "user_id"         => $user_ids[$user_loop],
                "moderator_post"  => $moderator_post,
                "status"          => ($i == 20 || $i == 40 || $i == 100 || $i == 200 || $i == 400) ? 0 : $status,
                "sort"            => $sort,
                "msgid"           => md5("Test $i - " . microtime()),
                "body"            => "Test Body $i - " . microtime(),
                "closed"          => $closed
                );
            phorum_db_post_message($message);
            $user_loop ++;
            $forum_loop ++;
            $thread_loop ++;
            if($user_loop > 3) $user_loop = 0;
            if($forum_loop > 6) $forum_loop = 0;
            if($thread_loop > 5) $thread_loop = 0;
        
        }
        $PHORUM["phorum_mod_forum_subscriptions"]["mass_messages"] = 1;
    }
    //*/
    
    phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
    phorum_admin_okmsg("Settings Updated");    
}

// Apply default values for the settings.
if (!isset($PHORUM["phorum_mod_forum_subscriptions"]["allow_user_unsubscribe_self"]) || $PHORUM["phorum_mod_forum_subscriptions"]["allow_user_unsubscribe_self"] == "") {
    $PHORUM["phorum_mod_forum_subscriptions"]["allow_user_unsubscribe_self"] = 0;
    $set_default = 1;
}
if (!isset($PHORUM["phorum_mod_forum_subscriptions"]["allow_attachments"]) || $PHORUM["phorum_mod_forum_subscriptions"]["allow_attachments"] == "") {
    $PHORUM["phorum_mod_forum_subscriptions"]["allow_attachments"] = 0;
    $set_default = 1;
}
if (!isset($PHORUM["phorum_mod_forum_subscriptions"]["default_frequency"])) {
    $PHORUM["phorum_mod_forum_subscriptions"]["default_frequency"] = PHORUM_MOD_FORUM_SUB_FREQUENCY_IMMEDIATE;
    $set_default = 1;
}
if (!isset($PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"])) {
    $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"] = 60;
    $set_default = 1;
}
if (!isset($PHORUM["phorum_mod_forum_subscriptions"]["weekly_digest_day"])) {
    $PHORUM["phorum_mod_forum_subscriptions"]["weekly_digest_day"] = 0;
    $set_default = 1;
}
if (!empty($set_default)) {
	phorum_db_update_settings(array("phorum_mod_forum_subscriptions"=>$PHORUM["phorum_mod_forum_subscriptions"]));
}
foreach ($PHORUM["PROFILE_FIELDS"] as $key => $cstm_field) {
	if ($cstm_field["name"] == "phorum_mod_forum_subscriptions_user_unsubscribe_setting_self") {
		if (isset($cstm_field["deleted"]) && $cstm_field["deleted"] == TRUE) {
			$user_unsubscribe_self = 2;
		} else {
			$user_unsubscribe_self = 1;
		}
	}
}
if (!isset($user_unsubscribe_self)) {
	include_once("./include/api/base.php");
	include_once("./include/api/custom_profile_fields.php");
    phorum_api_custom_profile_field_configure(array (
    	'id'            => NULL,
    	'name'          => 'phorum_mod_forum_subscriptions_user_unsubscribe_setting_self',
    	'length'        => 3,
    	'html_disabled' => TRUE,
    	'show_in_admin' => TRUE,
	));
    $user_unsubscribe_self = 1;
}

// We build the settings form by using the PhorumInputForm object. When
// creating your own settings screen, you'll only have to change the
// "mod" hidden parameter to the name of your own module.
include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", "Save");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "forum_subscriptions"); 

// Here we display an error in case one was set by saving 
// the settings before.
if (!empty($error)) {
    echo "$error<br />";
}

// This adds a break line to your form, with a description on it.
// You can use this to separate your form into multiple sections.
$frm->addbreak("Edit settings for the Forum Subscriptions module");
$frm->addsubbreak("Please refer to the <a target=\"_blank\" href=\"" . $PHORUM["http_path"] . "/mods/forum_subscriptions/README\">README</a> for information on customizing the emails sent by this module.");
$row = $frm->addrow("Use a cronjob mail queue (Please read the help popup or the <a target=\"_blank\" href=\"" . $PHORUM["http_path"] . "/mods/forum_subscriptions/README\">README</a><br>before using this feature): ", $frm->checkbox("enable_mail_queue", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["enable_mail_queue"]));
$frm->addhelp($row, "Using a Cronjob Mail Queue", "If you would like to use the Cronjob Mail Queue option you will need to complete two steps:<br>
<br>
First, you must add a cronjob to call the LibreForum \"scheduled\" hook from the LibreForum script.php file.  I would recommend that this cronjob run every minute. The module will handle any time-outs or errors which otherwise could cause the cronjob to run two instances of this module.  This is one example cronjob:<br>
<br>
* * * * * cd your/phorum/root && /usr/bin/php ./script.php --scheduled<br>
<br>
There should be no output from this script unless there is an error.  If possible I would also advise that you have the output from this script either logged or emailed.<br>
<br>
Second, you must enable the Mail Queue option from this module's settings page.<br>
<br>
Your Forum Subscriptions emails should now be sent through the mail queue system.  I would advise enabling the \"Debug email\" setting for this module until you are sure the mail queue system is working properly.");
if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["enable_mail_queue"])) {
    $time_limits = array (
        60  => "1 Minute",
        120 => "2 Minutes",
        180 => "3 Minutes",
        240 => "4 Minutes",
        300 => "5 Minutes"
        );
    $row = $frm->addrow("&nbsp;&nbsp;&nbsp;Maximum time limit for the mail queue PHP script to run<br>&nbsp;&nbsp;&nbsp;(This should only be changed if absolutely necessary): ", $frm->select_tag("mail_queue_time_limit", $time_limits, $PHORUM["phorum_mod_forum_subscriptions"]["mail_queue_time_limit"]));
    $frm->addhelp($row, "Mail Queue Time Limit", "This sets a rough time limit to keep a mail queue cronjob from exceeding a server's time limit");
    $frm->addrow("&nbsp;&nbsp;&nbsp;Allow users to subscribe to daily post digests: ", $frm->checkbox("allow_daily_digests", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["allow_daily_digests"]));
    $frm->addrow("&nbsp;&nbsp;&nbsp;Allow users to subscribe to weekly post digests: ", $frm->checkbox("allow_weekly_digests", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["allow_weekly_digests"]));
    if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["allow_weekly_digests"])) {
        $week_days = array (
            0 => "Sunday",
            1 => "Monday",
            2 => "Tuesday",
            3 => "Wednesday",
            4 => "Thursday",
            5 => "Friday",
            6 => "Saturday"
            );
        $frm->addrow("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Send weekly digests at 12:01 AM on which day: ", $frm->select_tag("weekly_digest_day", $week_days, $PHORUM["phorum_mod_forum_subscriptions"]["weekly_digest_day"]));
    }
    $frequencies = array (
        PHORUM_MOD_FORUM_SUB_FREQUENCY_IMMEDIATE => "After Each Post",
        PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY     => "Once Per Day in a Single Email",
        PHORUM_MOD_FORUM_SUB_FREQUENCY_WEEKLY    => "Once Per Week in a Single Email"
        );
    $row = $frm->addrow("&nbsp;&nbsp;&nbsp;Default frequency for Index/List page subscriptions links: ", $frm->select_tag("default_frequency", $frequencies, $PHORUM["phorum_mod_forum_subscriptions"]["default_frequency"]));
    $frm->addhelp($row, "Default Subscription Freqency", "If you edit your template's index and/or list pages to add a subscription link (see the <a target=\"_blank\" href=\"" . $PHORUM["http_path"] . "/mods/forum_subscriptions/README\">README</a> for more on template customization) this setting determines to which frequency the subscription will default. The user will be able to change the frequency after clicking on the subscription link.");
}    
$frm->addrow("Send post attachments with emails<br>(Does not apply to daily or weekly post digests): ", $frm->checkbox("allow_attachments", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["allow_attachments"]));
$frm->addrow("Add signatures to the body of the post if available: ", $frm->checkbox("show_signatures", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["show_signatures"]));
$row = $frm->addrow("Only send emails of new threads, not replies: ", $frm->checkbox("send_only_new_threads", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["send_only_new_threads"]));
$frm->addhelp($row, "Only Send Emails of New Threads", "If enabled, this setting will restrict users to subscribing to New Threads only.  Otherwise, users will be able to choose whether they want emails of all messages or only new threads.");
if ($user_unsubscribe_self == 2) {
	$frm->addmessage("Please add the deleted custom profile field named \"phorum_mod_forum_subscriptions_user_unsubscribe_setting_self\" if you would like to allow users to choose to stop receiving subscription emails of their own posts.");
} else {
	$frm->addrow("Allow users to choose to stop receiving subscription emails of their own posts<br>(set in the Control Center): ", $frm->checkbox("allow_user_unsubscribe_self", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["allow_user_unsubscribe_self"]));
}
$frm->addrow("Only show forums to which a user has subscribed in the Control Center: ", $frm->checkbox("show_only_subscriptions", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["show_only_subscriptions"]));
$frm->addrow("Show forums from other vroots to which a user has subscribed in the Control Center: ", $frm->checkbox("show_vroot_subscriptions", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["show_vroot_subscriptions"]));
$frm->addrow("Do not send any emails from this module from selected forums<br>(Shown after enabling and saving this setting): ", $frm->checkbox("ignore_selected_forums", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["ignore_selected_forums"]));
if (!empty($PHORUM["phorum_mod_forum_subscriptions"]["ignore_selected_forums"])) {
    
    $frm->addsubbreak("Select forums which will not send emails:");
    
    //get a list of folders and forums
    include_once("./mods/forum_subscriptions/settings_forum_functions.php");
    $forums = phorum_mod_forum_subscriptions_get_forums();
    
    if (count($forums)) {
        foreach($forums as $forum_id => $forum){
            if (!empty($forum["folder_flag"])) {
                if ($forum["vroot"] == $forum_id) {
                  $frm->addrow($forum["indent_spaces"]."Virtual root: ".$forum["name"]);
                } else {
                    $frm->addrow($forum["indent_spaces"].$forum["name"]." (Folder)");
                }
            } else {
                $curr_val = empty($PHORUM["phorum_mod_forum_subscriptions"]["forums_to_ignore"][$forum_id]) ? "" : 1;
                $frm->addrow($forum["indent_spaces"].$forum["name"], $frm->checkbox("forums_to_ignore[$forum_id]", "1", "", $curr_val));
            }
        }
    } else {
        $frm->addrow("No forums to select");
    }
}
$frm->addsubbreak("Debugging options (only enable if you have problems sending emails)");
$frm->addrow("Debug email: ", $frm->checkbox("enable_debugging", "1", "", $PHORUM["phorum_mod_forum_subscriptions"]["enable_debugging"]));
  
// We are done building the settings screen.
// By calling show(), the screen will be displayed.
$frm->show();

?>