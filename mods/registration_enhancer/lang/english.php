<?php
if (!defined("PHORUM")) return;

// Loaded automatically by common.php for the active language, and used as the
// fallback for any language this module has not been translated into.
// The %tokens% are substituted by the module.
$PHORUM['DATA']['LANG']['mod_registration_enhancer'] = array(

    // --- Address checks at registration time ---------------------------- //
    'InvalidEmailDomain' =>
        "The email address you supplied appears to be invalid (no mail server ".
        "was found for that domain).",
    'DisposableEmail' =>
        "Disposable email addresses are not accepted on this forum.",

    // --- Notification sent to moderators -------------------------------- //
    'ModNotifySubject' =>
        "New registration (pending): %username%",
    'ModNotifyBody' =>
        "A new user has just registered on the forum.\n\n".
        "Username: %username%\n".
        "Email: %email%\n\n".
        "--- Connection details ---\n".
        "IP address: %ip%\n".
        "Detected country: %country%\n\n".
        "You can approve or reject this account using the link below:\n".
        "%url%\n",

    // --- Acknowledgement sent to the applicant -------------------------- //
    'PendingSubject' =>
        "Your account is under review",
    'PendingBody' =>
        "Hello %username%,\n\n".
        "Your account on %title% has been created.\n".
        "It is currently being reviewed by our moderation team.\n".
        "You will receive another email as soon as it has been approved.\n\n".
        "Thank you for your patience!",

    // Shown when the geolocation service is silent or unreachable.
    'UnknownCountry' => "Unknown",
);

?>
