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
        $baseurl = "http://www.stopforumspam.com/api?";
        $args=array();
        // Valeurs encodées : un nom avec une espace ou en cyrillique partait tel quel et
        // l'API répondait 400 ; le « + » d'une adresse e-mail y devenait une espace.
        if(!empty($PHORUM['mod_stopforumspam']['check_ip'])) {
            $args[]="ip=".rawurlencode($_SERVER['REMOTE_ADDR']);
        }
        if(!empty($PHORUM['mod_stopforumspam']['check_username'])) {
            $args[]="username=".rawurlencode($userdata['username']);
        }
        if(!empty($PHORUM['mod_stopforumspam']['check_email'])) {
            $args[]="email=".rawurlencode($userdata['email']);
        }
        if(count($args)) {
            include_once("./include/api/http_get.php");
            $error = array();
            // request serialized data
            $args[]="f=serial";
            $url = $baseurl.implode("&",$args);
            #_phorum_mod_stopforumspam_log("SFS query is $url", "", EVENTLOG_LVL_DEBUG);
            $res = phorum_api_http_get($url);
            // En cas d'échec (code HTTP autre que 200, hôte injoignable),
            // phorum_api_http_get() renvoie FALSE, pas NULL : l'ancien test « !== null »
            // le laissait passer, unserialize(FALSE) rendait FALSE, le score restait à 0
            // et l'inscription était déclarée sûre. Une réponse sans « success » finissait
            // de même. Les 10 comptes frauduleux du 2026-10-21 sont entrés ainsi.
            // Désormais, un contrôle impossible met le compte en modération, et
            // l'avertissement remonte au contrôle nocturne des erreurs PHP : le forum
            // ne prévient personne qu'un compte attend d'être approuvé.
            $resdata = is_string($res) ? @unserialize($res, array('allowed_classes' => false)) : false;
            if(!is_array($resdata) || empty($resdata['success'])) {
                $raison = is_string($res) ? 'réponse inexploitable' : phorum_api_strerror();
                _phorum_mod_stopforumspam_pending($userdata);
                trigger_error("stopforumspam : vérification impossible ($raison), inscription mise en modération", E_USER_WARNING);
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
// Compte à approuver par un modérateur ; la vérification d'adresse e-mail, si elle est
// demandée, reste due (register.php passe PENDING_BOTH à PENDING_MOD au clic).
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