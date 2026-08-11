<?php
        
if(!defined("PHORUM")) return;

function phorum_mod_forum_subscriptions_page_index() {
    
    global $PHORUM;
    
    if (!empty($PHORUM["forum_id"]) && $PHORUM["vroot"] != $PHORUM["forum_id"]) return;
    
    $forum_id = ($PHORUM["forum_id"] == $PHORUM["vroot"]) ? $PHORUM["forum_id"] : 0;
    
    $PHORUM["DATA"]["URL"]["FORUM_SUBSCRIPTION"] 
        = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=forum_subscriptions", "fsub_forum=$forum_id");
    $PHORUM["DATA"]["FORUM_SUBSCRIPTION"]["SubscribeURLText"] = $PHORUM["DATA"]["LANG"]["forum_subscriptions"]["SubscribeToAllForums"];
}

function phorum_mod_forum_subscriptions_page_list() {
    
    global $PHORUM;
    
    $PHORUM["DATA"]["URL"]["FORUM_SUBSCRIPTION"] 
        = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=forum_subscriptions", "fsub_forum=" . $PHORUM["forum_id"]);
        $PHORUM["DATA"]["FORUM_SUBSCRIPTION"]["SubscribeURLText"] = $PHORUM["DATA"]["LANG"]["forum_subscriptions"]["SubscribeToForum"];
}

function phorum_mod_forum_subscriptions_scheduled() {
    
    require_once("./mods/forum_subscriptions/forum_subscriptions_scheduled.php");
    phorum_mod_forum_subscriptions_functions_scheduled();
    
}

// please note that LibreForum's after_post hook only provides the $data, the 
// $queue_data is provided by the scheduled hook of this module if enabled  
function phorum_mod_forum_subscriptions_after_post ($data = NULL, $queue_data = NULL) {
    // if the message is not approved and this is not a mail queue, we are done
    if (!is_null($data) && is_null($queue_data) && $data["status"] <= 0 ) return $data;
    
    require_once("./mods/forum_subscriptions/forum_subscriptions_after_post.php");
    $data = phorum_mod_forum_subscriptions_functions_after_post ($data, $queue_data);
    
    return $data;
}

function phorum_mod_forum_subscriptions_after_approve($data) {

    if (PHORUM_APPROVE_MESSAGE || PHORUM_APPROVE_MESSAGE_TREE) {

        $data[0]["status"] = 2;
        
        phorum_mod_forum_subscriptions_after_post($data[0]);

    }
    
    return $data;

}

// Add the forum subscriptions panel to the control center.
function phorum_mod_forum_subscriptions_cc_panel($data) {
    global $PHORUM;

    if ($data["panel"] == "forum_subscriptions") {
        
        require_once("./mods/forum_subscriptions/forum_subscriptions_cc_panel.php");
        $data = phorum_mod_forum_subscriptions_functions_cc_panel($data);
    }
    
    return $data;
}

// Add the forum subscriptions option to the control center menu.
function phorum_mod_forum_subscriptions_tpl_cc_menu_options_hook() {
    global $PHORUM;
    
    // Generate the require template data for the control panel menu button.
    if ($PHORUM["DATA"]["PROFILE"]["PANEL"] == 'forum_subscriptions')
        $PHORUM["DATA"]["FORUM_SUBSCRIPTIONS_PANEL_ACTIVE"] = TRUE;
    $PHORUM["DATA"]["URL"]["CC_FORUM_SUBSCRIPTIONS"] =
        phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=forum_subscriptions");

    // Show the menu button.
    include(phorum_get_template('forum_subscriptions::cc_menu_item'));
}

?>