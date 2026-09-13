<?php
if(!defined("PHORUM")) return;

function phorum_mod_registration_enhancer_before($userdata) {
    // 1. Check MX record and disposable domains for the email
    if (!empty($userdata['email'])) {
        $parts = explode('@', $userdata['email']);
        if (count($parts) == 2) {
            $domain = $parts[1];
            
            // Check for valid MX records
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                $userdata['error'] = "L'adresse e-mail fournie semble invalide (aucun serveur de réception trouvé pour ce domaine).";
                return $userdata;
            }
            
            // Basic blacklist for disposable emails
            $blacklist = array('yopmail.com', 'yopmail.fr', '10minutemail.com', 'trashmail.com', 'mailinator.com', 'guerrillamail.com', 'temp-mail.org');
            if (in_array(strtolower($domain), $blacklist)) {
                $userdata['error'] = "Les adresses e-mail jetables ne sont pas autorisées sur ce forum.";
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
    $country = "Inconnu";
    
    // Simple fetch from ip-api.com (timeout 2s to not block registration)
    if ($ip && $ip != '127.0.0.1' && $ip != '::1') {
        // HTTPS : l'adresse IP d'un inscrit est une donnée personnelle, elle
        // ne doit pas transiter en clair vers un tiers. ip-api.com ne sert le
        // HTTPS qu'aux comptes payants (403 en gratuit, vérifié le
        // 2026-09-13) ; ipwho.is le fait gratuitement et renvoie exactement le
        // même {"country":"..."} , d'où la substitution directe.
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
        $mail_data = array(
            "mailsubject" => "Nouvelle inscription (en attente) : " . $userdata["username"],
            "mailmessage" => "Un nouvel utilisateur vient de s'inscrire sur le forum.\n\n" .
                             "Nom d'utilisateur : " . $userdata["username"] . "\n" .
                             "E-mail : " . $userdata["email"] . "\n\n" .
                             "--- Informations de connexion ---\n" .
                             "Adresse IP : " . $ip . "\n" .
                             "Pays détecté : " . $country . "\n\n" .
                             "Vous pouvez valider ou refuser ce compte en cliquant sur le lien ci-dessous :\n" .
                             $admin_url . "\n"
        );
        phorum_email_user($mail_users, $mail_data);
    }

    // 4. Send email to user (if waiting for moderation and not doing VERIFY_BOTH which sends its own email)
    // Actually, if VERIFY_BOTH is active, they will receive the confirmation link first. We should tell them about the manual moderation after they click.
    // That happens in register.php when approve is passed. It might be better to send the "pending" email directly here if it's VERIFY_MODERATOR only.
    if ($PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_MODERATOR) {
        $mail_data = array(
            "mailsubject" => "Votre compte est en cours d'examen",
            "mailmessage" => "Bonjour " . $userdata["username"] . ",\n\n" .
                             "Votre compte a bien été créé sur " . $PHORUM["title"] . ".\n" .
                             "Il est actuellement en cours d'examen par notre équipe de modération.\n" .
                             "Vous recevrez un nouvel e-mail dès qu'il aura été approuvé.\n\n" .
                             "Merci de votre patience !"
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
        // Ce module N'ENVOIE PLUS de message d'approbation : Phorum le fait
        // déjà. include/controlcenter/users.php envoie RegApprovedSubject /
        // RegApprovedEmailBody juste avant phorum_api_user_save(), qui
        // déclenche ce hook — l'utilisateur recevait donc DEUX courriels à la
        // même minute (« Votre compte a été approuvé. » puis « Votre compte
        // est approuvé ! »), constaté le 2026-09-13.
        //
        // C'est l'envoi du module qui est retiré, pas celui de Phorum : le
        // message natif porte déjà le lien de connexion, et toucher au cœur
        // priverait de ce courriel les installations de LibreForum qui n'ont
        // pas ce module. Pour personnaliser le texte, modifier les chaînes
        // RegApprovedSubject / RegApprovedEmailBody dans include/lang/.
        //
        // La fonction et sa déclaration `hook: user_save` sont CONSERVÉES à
        // dessein : le setting `hooks` en base cite cette fonction, et c'est
        // lui qui pilote l'exécution. La supprimer du fichier sans
        // reconstruire `hooks` par Admin → Modules ferait appeler une
        // fonction inexistante à chaque sauvegarde d'utilisateur.
        //
        // Le module garde par ailleurs ses deux autres rôles : filtrage des
        // adresses à l'inscription (before_register) et alerte aux
        // modérateurs (after_register).
    }
    return $user;
}
?>
