<?php

// Load event_logging constants (required for PHP 8).
if (file_exists('./mods/event_logging/constants.php')) {
    require_once './mods/event_logging/constants.php';
}

function phorum_mod_stopforumspam_before_register($userdata) {
    global $PHORUM;

    $apikey = $PHORUM['mod_stopforumspam']['apikey'];
    $last_key = false;
    if(!empty($apikey)) {
        $args=array();
        if(!empty($PHORUM['mod_stopforumspam']['check_ip'])) {
            $args['ip'] = $_SERVER['REMOTE_ADDR'];
        }
        if(!empty($PHORUM['mod_stopforumspam']['check_username'])) {
            $args['username'] = $userdata['username'];
        }
        if(!empty($PHORUM['mod_stopforumspam']['check_email'])) {
            $args['email'] = $userdata['email'];
        }
        if(count($args)) {
            $error = array();
            // request serialized data
            $args['f'] = 'serial';
            $reason = '';
            $res = _phorum_mod_stopforumspam_query($args, $reason);
            // A failed query used to pass the "!== null" test (phorum_api_http_get()
            // returns FALSE, not NULL): unserialize(FALSE) gave FALSE, the score stayed
            // at 0 and the registration was declared safe. An answer without "success"
            // ended the same way. On one installation, ten fraudulent accounts got in
            // like this in a single day. A check that cannot be made now puts the
            // account in moderation, and the warning goes to the PHP error log: the
            // forum itself tells nobody that an account awaits approval.
            $resdata = is_string($res) ? @unserialize($res, array('allowed_classes' => false)) : false;
            if(!is_array($resdata) || empty($resdata['success'])) {
                if(is_string($res)) $reason = 'unreadable answer';
                _phorum_mod_stopforumspam_pending($userdata);
                trigger_error("stopforumspam: check impossible ($reason), registration put in moderation", E_USER_WARNING);
                _phorum_mod_stopforumspam_alert($userdata, $reason);
                _phorum_mod_stopforumspam_log('ERROR Check failed',"Data returned from Stopforumspam was:\n".print_r($res,true));
            } else {
                $score = 0;
                foreach($resdata as $key => $var) {
                    if($key == 'success') {
                        continue;
                    } elseif(!empty($var['appears'])) {
                        $last_key = $key;
                    }
                    if(!empty($var['frequency'])) {
                        $score += $var['frequency'];
                    }
                }
                #_phorum_mod_stopforumspam_log("score $score >= {$PHORUM["mod_stopforumspam"]["freq_min"]} ?", "", EVENTLOG_LVL_DEBUG);
                if(empty($PHORUM["mod_stopforumspam"]["freq_min"]) || $PHORUM["mod_stopforumspam"]["freq_min"] < 1)
                    $PHORUM["mod_stopforumspam"]["freq_min"] = 1; # hard min
                if($score >= $PHORUM["mod_stopforumspam"]["freq_min"]) {
                    if($PHORUM['mod_stopforumspam']['block_action'] == 'blockerror') {
                        // block user
                        if($PHORUM['mod_stopforumspam']['force_generic'] || !$last_key)
                            $error[]=$PHORUM['DATA']['LANG']['mod_stopforumspam']['error_generic'];
                        else
                            $error[]=$PHORUM['DATA']['LANG']['mod_stopforumspam']['error_'.$last_key];
                    } else {
                        _phorum_mod_stopforumspam_pending($userdata);
                    }
                }
                if(count($error)) {
                    if($PHORUM['mod_stopforumspam']['block_action'] == 'blockerror') {
                        _phorum_mod_stopforumspam_log("User {$userdata['username']} registration blocked! (score $score)","A user registration based on data from stopforumspam.com was blocked!\nUserdata was:\nUsername: {$userdata['username']}\nIP: {$_SERVER['REMOTE_ADDR']}\nEmail: {$userdata['email']}\n\nAnd data returned from Stopforumspam was:\n".print_r($resdata,true));
                    } else {
                        _phorum_mod_stopforumspam_log("User registration made unapproved! (score $score)","A user registration based on data from stopforumspam.com was made unapproved!\nUserdata was:\nUsername: {$userdata['username']}\nIP: {$_SERVER['REMOTE_ADDR']}\nEmail: {$userdata['email']}\n\nAnd data returned from Stopforumspam was:\n".print_r($resdata,true));
                    }
                    $userdata['error']=implode("<br />",$error);
                    $error = array();
                } else {
                    _phorum_mod_stopforumspam_log("User registration was safe (score $score)","A user registration was safe based on data from stopforumspam.com.\nUserdata was:\nUsername: {$userdata['username']}\nIP: {$_SERVER['REMOTE_ADDR']}\nEmail: {$userdata['email']}\n\nAnd data returned from Stopforumspam was:\n".print_r($resdata,true));

                }
            }
        }
    }
    return $userdata;
}
// Query to the API: POST, HTTPS with certificate verification, European server.
// According to stopforumspam (GDPR and Usage pages, read 2026-10-22), the content of a
// GET request (the URL, hence the registrant's IP address and email) is written to its
// logs, kept 5 or 7 days depending on the page, and off-site backups may hold it; the
// body of a POST request is not, and europe.stopforumspam.org keeps the request within
// the European Union. The module used to send a GET in clear HTTP.
// phorum_api_http_get() only does GET, with no timeout and no certificate check.
// Returns the response body, or FALSE with the reason of the failure in $reason.
function _phorum_mod_stopforumspam_query($args, &$reason) {
    if(!function_exists('curl_init')) {
        $reason = 'curl extension missing';
        return false;
    }
    $ch = curl_init('https://europe.stopforumspam.org/api');
    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        // http_build_query() encodes the values: sent as-is in the URL, a username with
        // a space got a 400 answer, and the "+" of an email address became a space.
        CURLOPT_POSTFIELDS     => http_build_query($args),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
    ));
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if($res === false) {
        $reason = 'curl: ' . curl_error($ch);
    } elseif($code != 200) {
        $reason = "HTTP $code";
        $res = false;
    }
    return $res;
}

// Immediate email to the module's alert address (setting "alert_email", empty = no
// email) when a registration is put in moderation because the check was impossible:
// the forum tells nobody by email, and the PHP error log is only read later. A failed
// sending never blocks the registration.
function _phorum_mod_stopforumspam_alert($userdata, $reason) {
    global $PHORUM;
    $address = isset($PHORUM['mod_stopforumspam']['alert_email']) ? trim($PHORUM['mod_stopforumspam']['alert_email']) : '';
    if($address === '') return;
    try {
        include_once("./include/email_functions.php");
        $url = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERS);
        // phorum_email_user() returns the number of addresses, not a success: nothing to test.
        phorum_email_user(array($address), array(
            'mailsubject' => "Forum registration to approve: {$userdata['username']}",
            'mailmessage' =>
                "The registration of \"{$userdata['username']}\" ({$userdata['email']}, IP address "
                . "{$_SERVER['REMOTE_ADDR']}) could not be checked against StopForumSpam: $reason.\n\n"
                . "The account was created pending approval. If an email confirmation is "
                . "required, the registrant still has to do it.\n\n"
                . "Approve or refuse the account:\n$url\n\n"
                . "This email is sent on every failed check. The failure is also written to "
                . "the PHP error log.\n",
        ));
    } catch(Throwable $e) {
        trigger_error("stopforumspam: alert email not sent: " . $e->getMessage(), E_USER_WARNING);
    }
}

// Account to be approved by a moderator; the email confirmation, if required, is still
// due (register.php turns PENDING_BOTH into PENDING_MOD when the link is clicked).
function _phorum_mod_stopforumspam_pending(&$userdata) {
    if($userdata["active"] == PHORUM_USER_ACTIVE) {
        $userdata['active'] = PHORUM_USER_PENDING_MOD;
    } elseif($userdata["active"] == PHORUM_USER_PENDING_EMAIL) {
        $userdata['active'] = PHORUM_USER_PENDING_BOTH;
    }
}
function _phorum_mod_stopforumspam_log($shortmsg,$message,$level=EVENTLOG_LVL_INFO) {
    global $PHORUM;
    if (!empty($PHORUM["mod_stopforumspam"]["log_events"]) &&
        function_exists('event_logging_writelog')) {
        event_logging_writelog(array(
            'message'   => $shortmsg,
            'details'   => $message,
            'loglevel'  => $level
        ));
    }
}