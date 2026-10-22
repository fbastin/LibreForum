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
            $raison = '';
            $res = _phorum_mod_stopforumspam_query($args, $raison);
            // Avant le 2026-10-22, un échec (FALSE de phorum_api_http_get()) passait le
            // test « !== null » : unserialize(FALSE) rendait FALSE, le score restait à 0
            // et l'inscription était déclarée sûre. Une réponse sans « success » finissait
            // de même. Les 10 comptes frauduleux du 2026-10-21 sont entrés ainsi.
            // Désormais, un contrôle impossible met le compte en modération, et
            // l'avertissement remonte au contrôle nocturne des erreurs PHP : le forum
            // ne prévient personne qu'un compte attend d'être approuvé.
            $resdata = is_string($res) ? @unserialize($res, array('allowed_classes' => false)) : false;
            if(!is_array($resdata) || empty($resdata['success'])) {
                if(is_string($res)) $raison = 'réponse inexploitable';
                _phorum_mod_stopforumspam_pending($userdata);
                trigger_error("stopforumspam : vérification impossible ($raison), inscription mise en modération", E_USER_WARNING);
                _phorum_mod_stopforumspam_alerte($userdata, $raison);
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
// Requête à l'API : POST, HTTPS avec vérification du certificat, serveur européen.
// Selon stopforumspam (pages GDPR et Usage, lues le 2026-10-22), le contenu d'une requête
// GET (l'URL, donc l'adresse IP et l'e-mail de l'inscrit) est écrit dans ses journaux,
// gardés 5 ou 7 jours selon la page, et des sauvegardes hors site peuvent en contenir ;
// celui d'une requête POST ne l'est pas, et europe.stopforumspam.org garde la requête
// dans l'Union européenne. phorum_api_http_get() ne fait que du GET, sans délai maximal
// et sans vérifier le certificat. Renvoie le corps de la réponse, ou FALSE avec la
// raison de l'échec dans $raison.
function _phorum_mod_stopforumspam_query($args, &$raison) {
    if(!function_exists('curl_init')) {
        $raison = 'extension curl absente';
        return false;
    }
    $ch = curl_init('https://europe.stopforumspam.org/api');
    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        // http_build_query encode les valeurs : envoyées telles quelles dans l'URL, un
        // nom avec une espace faisait répondre 400, et le « + » d'un e-mail y devenait une espace.
        CURLOPT_POSTFIELDS     => http_build_query($args),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
    ));
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if($res === false) {
        $raison = 'curl : ' . curl_error($ch);
    } elseif($code != 200) {
        $raison = "HTTP $code";
        $res = false;
    }
    return $res;
}

// Mail immédiat à l'adresse d'alerte du module (réglage « alert_email », vide = aucun
// mail) quand une inscription part en modération faute de vérification : le forum ne
// prévient personne par mail, et le contrôle nocturne des erreurs PHP ne relève
// l'avertissement que la nuit suivante. Un envoi raté ne bloque pas l'inscription.
function _phorum_mod_stopforumspam_alerte($userdata, $raison) {
    global $PHORUM;
    $adresse = isset($PHORUM['mod_stopforumspam']['alert_email']) ? trim($PHORUM['mod_stopforumspam']['alert_email']) : '';
    if($adresse === '') return;
    try {
        include_once("./include/email_functions.php");
        $url = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERS);
        // phorum_email_user() renvoie le nombre d'adresses, pas un succès : rien à tester.
        phorum_email_user(array($adresse), array(
            'mailsubject' => "Inscription au forum à valider : {$userdata['username']}",
            'mailmessage' =>
                "L'inscription de « {$userdata['username']} » ({$userdata['email']}, adresse IP "
                . "{$_SERVER['REMOTE_ADDR']}) n'a pas pu être vérifiée auprès de StopForumSpam : $raison.\n\n"
                . "Le compte est créé en attente de validation. Si une confirmation de l'adresse "
                . "e-mail est demandée, l'inscrit doit encore la faire.\n\n"
                . "Valider ou refuser le compte :\n$url\n\n"
                . "Ce mail part à chaque échec de la vérification. L'échec est aussi écrit dans "
                . "le journal d'Apache, que le contrôle nocturne des erreurs PHP relève.\n",
        ));
    } catch(Throwable $e) {
        trigger_error("stopforumspam : mail d'alerte non envoyé — " . $e->getMessage(), E_USER_WARNING);
    }
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