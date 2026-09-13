<?php
if (!defined("PHORUM")) return;

// Loaded automatically by common.php for the active forum language, falling
// back to PHORUM_DEFAULT_LANGUAGE. The %tokens% are substituted by the module.
$PHORUM['DATA']['LANG']['mod_registration_enhancer'] = array(

    // --- Address checks at registration time ---------------------------- //
    'InvalidEmailDomain' =>
        "L'adresse e-mail fournie semble invalide (aucun serveur de réception ".
        "trouvé pour ce domaine).",
    'DisposableEmail' =>
        "Les adresses e-mail jetables ne sont pas autorisées sur ce forum.",

    // --- Notification sent to moderators -------------------------------- //
    'ModNotifySubject' =>
        "Nouvelle inscription (en attente) : %username%",
    'ModNotifyBody' =>
        "Un nouvel utilisateur vient de s'inscrire sur le forum.\n\n".
        "Nom d'utilisateur : %username%\n".
        "E-mail : %email%\n\n".
        "--- Informations de connexion ---\n".
        "Adresse IP : %ip%\n".
        "Pays détecté : %country%\n\n".
        "Vous pouvez valider ou refuser ce compte en cliquant sur le lien ".
        "ci-dessous :\n%url%\n",

    // --- Acknowledgement sent to the applicant -------------------------- //
    'PendingSubject' =>
        "Votre compte est en cours d'examen",
    'PendingBody' =>
        "Bonjour %username%,\n\n".
        "Votre compte a bien été créé sur %title%.\n".
        "Il est actuellement en cours d'examen par notre équipe de ".
        "modération.\n".
        "Vous recevrez un nouvel e-mail dès qu'il aura été approuvé.\n\n".
        "Merci de votre patience !",

    // Shown when the geolocation service is silent or unreachable.
    'UnknownCountry' => "Inconnu",
);

?>
