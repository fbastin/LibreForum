<?php
if (!defined("PHORUM")) return;

// Chargé automatiquement par common.php selon la langue du forum, avec repli
// sur PHORUM_DEFAULT_LANGUAGE. Les %jetons% sont remplacés par le module.
$PHORUM['DATA']['LANG']['mod_registration_enhancer'] = array(

    // --- Contrôle de l'adresse à l'inscription -------------------------- //
    'InvalidEmailDomain' =>
        "L'adresse e-mail fournie semble invalide (aucun serveur de réception ".
        "trouvé pour ce domaine).",
    'DisposableEmail' =>
        "Les adresses e-mail jetables ne sont pas autorisées sur ce forum.",

    // --- Alerte aux modérateurs ----------------------------------------- //
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

    // --- Accusé de réception à l'inscrit -------------------------------- //
    'PendingSubject' =>
        "Votre compte est en cours d'examen",
    'PendingBody' =>
        "Bonjour %username%,\n\n".
        "Votre compte a bien été créé sur %title%.\n".
        "Il est actuellement en cours d'examen par notre équipe de ".
        "modération.\n".
        "Vous recevrez un nouvel e-mail dès qu'il aura été approuvé.\n\n".
        "Merci de votre patience !",

    // Pays non déterminé (service de géolocalisation muet ou injoignable).
    'UnknownCountry' => "Inconnu",
);

?>
