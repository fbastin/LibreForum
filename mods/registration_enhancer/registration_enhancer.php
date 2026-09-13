<?php
if(!defined("PHORUM")) return;

/**
 * Returns one of this module's strings, with %tokens% substituted.
 *
 * The texts live in mods/registration_enhancer/lang/, which common.php loads
 * for the active forum language (falling back to PHORUM_DEFAULT_LANGUAGE).
 * Falling back to the key itself prevents sending an empty email should the
 * language file ever go missing.
 */
function phorum_mod_registration_enhancer_lang($key, $tokens = array())
{
    global $PHORUM;

    // common.php only loads a module's language file when that module
    // declares "hook: lang|" AND the `hooks` setting in the database has been
    // rebuilt since (Admin -> Modules). On an installation where that has not
    // happened yet, we load it here: without this fallback, emails would go
    // out with key names in place of the actual texts.
    if (!isset($PHORUM['DATA']['LANG']['mod_registration_enhancer'])) {
        $langue = isset($PHORUM['language'])
                ? basename($PHORUM['language']) : PHORUM_DEFAULT_LANGUAGE;
        foreach (array($langue, PHORUM_DEFAULT_LANGUAGE) as $essai) {
            $fichier = "./mods/registration_enhancer/lang/$essai.php";
            if (file_exists($fichier)) { include_once $fichier; break; }
        }
    }

    $lang = isset($PHORUM['DATA']['LANG']['mod_registration_enhancer'])
          ? $PHORUM['DATA']['LANG']['mod_registration_enhancer'] : array();
    $text = isset($lang[$key]) ? $lang[$key] : $key;
    foreach ($tokens as $token => $value) {
        $text = str_replace("%$token%", $value, $text);
    }
    return $text;
}

function phorum_mod_registration_enhancer_before($userdata) {
    global $PHORUM;
    // 1. Check MX record and disposable domains for the email
    if (!empty($userdata['email'])) {
        $parts = explode('@', $userdata['email']);
        if (count($parts) == 2) {
            $domain = $parts[1];
            
            // Check for valid MX records
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                $userdata['error'] = phorum_mod_registration_enhancer_lang('InvalidEmailDomain');
                return $userdata;
            }
            
            // Basic blacklist for disposable emails
            $blacklist = array('yopmail.com', 'yopmail.fr', '10minutemail.com', 'trashmail.com', 'mailinator.com', 'guerrillamail.com', 'temp-mail.org');
            if (in_array(strtolower($domain), $blacklist)) {
                $userdata['error'] = phorum_mod_registration_enhancer_lang('DisposableEmail');
                return $userdata;
            }
        }
    }
    return $userdata;
}

function phorum_mod_registration_enhancer_after($userdata) {
    global $PHORUM;
    include_once("./include/email_functions.php");

    // 2. Get IP and Country
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    $country = phorum_mod_registration_enhancer_lang('UnknownCountry');
    
    // Country lookup, capped at 2s so it never holds up a registration.
    if ($ip && $ip != '127.0.0.1' && $ip != '::1') {
        // HTTPS: a registrant's IP address is personal data and must not
        // travel to a third party in the clear. ip-api.com only serves HTTPS
        // to paying accounts (403 on the free tier, checked 2026-09-13);
        // ipwho.is does it for free and returns the very same
        // {"country":"..."}, hence the drop-in replacement.
        $ctx = stream_context_create(array('http' => array('timeout' => 2)));
        $json = @file_get_contents("https://ipwho.is/{$ip}?fields=country", false, $ctx);
        if ($json) {
            $data = json_decode($json, true);
            if (!empty($data['country'])) {
                $country = $data['country'];
            }
        }
    }

    // 3. Send email to admin (with IP/Country)
    if($PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_MODERATOR ||
       $PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_BOTH) {
           
        // Link to the frontend moderation control center (panel=users)
        if (!function_exists('phorum_get_url')) {
            include_once("./common.php");
        }
        $admin_url = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=users");
        
        $mail_users = phorum_api_user_list_moderators($PHORUM['forum_id'], false, true);
        $tokens = array(
            'username' => $userdata["username"],
            'email'    => $userdata["email"],
            'ip'       => $ip,
            'country'  => $country,
            'url'      => $admin_url,
        );
        $mail_data = array(
            "mailsubject" => phorum_mod_registration_enhancer_lang('ModNotifySubject', $tokens),
            "mailmessage" => phorum_mod_registration_enhancer_lang('ModNotifyBody', $tokens)
        );
        phorum_email_user($mail_users, $mail_data);
    }

    // 4. Send email to user (if waiting for moderation and not doing VERIFY_BOTH which sends its own email)
    // Actually, if VERIFY_BOTH is active, they will receive the confirmation link first. We should tell them about the manual moderation after they click.
    // That happens in register.php when approve is passed. It might be better to send the "pending" email directly here if it's VERIFY_MODERATOR only.
    if ($PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_MODERATOR) {
        $tokens = array(
            'username' => $userdata["username"],
            'title'    => $PHORUM["title"],
        );
        $mail_data = array(
            "mailsubject" => phorum_mod_registration_enhancer_lang('PendingSubject', $tokens),
            "mailmessage" => phorum_mod_registration_enhancer_lang('PendingBody', $tokens)
        );
        phorum_email_user(array($userdata["email"]), $mail_data);
    }
    
    return $userdata;
}

function phorum_mod_registration_enhancer_user_save($user) {
    global $PHORUM;
    include_once("./include/email_functions.php");
    
    // Check if the user is being approved by transitioning to PHORUM_USER_ACTIVE
    if (isset($user['user_id']) && isset($user['active']) && $user['active'] == PHORUM_USER_ACTIVE) {
        // This module deliberately sends NO approval message: Phorum already
        // does. include/controlcenter/users.php sends RegApprovedSubject /
        // RegApprovedEmailBody right before phorum_api_user_save(), which in
        // turn fires this hook -- so the user received TWO emails within the
        // same minute ("Your account has been approved." followed by the
        // module's own wording). Observed 2026-09-13.
        //
        // It is the module's email that was dropped, not Phorum's: the native
        // message already carries the login link, and touching the core would
        // deprive installations without this module of that email. To reword
        // it, edit RegApprovedSubject / RegApprovedEmailBody in include/lang/.
        //
        // The function and its "hook: user_save" declaration are kept on
        // purpose, emptied: the `hooks` setting in the database names this
        // function, and that setting is what drives execution. Removing it
        // from the file without rebuilding `hooks` from Admin -> Modules
        // would call a non-existent function on every user save.
        //
        // The module keeps its two other roles: address filtering at
        // registration time (before_register) and the moderator alert
        // (after_register).
    }
    return $user;
}
?>
