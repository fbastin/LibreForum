<?php
/**
 * Script d'envoi automatique des courriels d'anniversaire pour Tireur.org
 * Doit être exécuté une fois par jour via Cron (crontab de l'utilisateur, 8 h).
 *
 *   php8.4 forum/scripts/send_birthday_emails.php                         # envoi réel
 *   php8.4 forum/scripts/send_birthday_emails.php --dry-run [--jour=MM-JJ] # compte, n'envoie rien
 *
 * Un membre peut refuser ce courriel : case « Pas de courriel d'anniversaire » de
 * « Mon profil » (champ user_birthday_no_email, 2026-11-08). La case « Garder
 * privée » ne concerne que le calendrier : le courriel ne va qu'au membre lui-même.
 */

// Définition de l'environnement Phorum
define('PHORUM', 1);
define('PHORUM_ADMIN', 1); // Pour avoir accès à certaines fonctions si besoin
chdir(__DIR__ . '/..'); // Se placer à la racine du forum
require_once('./common.php');
require_once('./include/email_functions.php');
require_once('./include/api/mail.php');

// Paramètres de l'email (utilisent les réglages système de Phorum désormais configurés pour Gmail)
$from_email = $PHORUM['system_email_from_address'];
$from_name  = $PHORUM['system_email_from_name'];
$subject    = '🎂 Joyeux anniversaire de la part de Tireur.org !';

// Fuseau du serveur, et non UTC (fuseau du PHP en ligne de commande ici) : lancé à la
// main après 20 h, le script servait déjà les anniversaires du LENDEMAIN, et la
// protection ci-dessous bloquait ensuite l'envoi du jour même.
date_default_timezone_set('America/Toronto');

// --dry-run : compte les destinataires sans rien envoyer ni enregistrer ; --jour=MM-JJ
// (avec --dry-run seulement) simule un autre jour.
$dry_run = in_array('--dry-run', $argv ?? array(), true);
$jour_force = '';
foreach ($argv ?? array() as $arg) {
    if (preg_match('/^--jour=(\d{2}-\d{2})$/', $arg, $m)) $jour_force = $m[1];
}

// Date du jour
$today_md = ($dry_run && $jour_force !== '') ? $jour_force : date('m-d');
$current_year = date('Y');

// 1. Protection contre les envois multiples le même jour
$last_run = isset($PHORUM['mod_birthday_email_last_run']) ? $PHORUM['mod_birthday_email_last_run'] : '';
if (!$dry_run && $last_run == date('Y-m-d')) {
    echo "Script déjà exécuté aujourd'hui (" . date('Y-m-d') . "). Arrêt.\n";
    exit;
}

// 2. Recherche des membres fêtant leur anniversaire aujourd'hui
// On utilise le champ personnalisé type 22 (user_birthday)
$prefix = $PHORUM['DBCONFIG']['table_prefix'];
// Membres qui ont refusé le courriel (champ créé par custom_profile_fields_handler ;
// tant qu'il n'existe pas, personne ne l'a coché).
require_once('./include/api/custom_profile_fields.php');
$refus = phorum_api_custom_profile_field_byname('user_birthday_no_email');
$join_refus  = $refus ? "LEFT JOIN {$prefix}_user_custom_fields r ON u.user_id = r.user_id AND r.type = " . (int)$refus['id'] : '';
$where_refus = $refus ? "AND (r.data IS NULL OR r.data <> '1')" : '';
$sql = "SELECT u.user_id, u.username, u.email, b.data as birthday
        FROM {$prefix}_users u
        JOIN {$prefix}_user_custom_fields b ON u.user_id = b.user_id AND b.type = 22
        $join_refus
        WHERE b.data LIKE '%-$today_md'
        AND u.active = 1
        $where_refus";

$res = phorum_db_interact(DB_RETURN_RES, $sql);

if (!$res) {
    echo "Aucun anniversaire aujourd'hui ($today_md).\n";
} else {
    $count = 0;
    while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
        $username = $row['username'];
        $email    = $row['email'];
        $bday     = $row['birthday'];
        
        // Calcul de l'âge
        $age = "";
        if (preg_match('/^(\d{4})/', $bday, $m)) {
            $year = (int)$m[1];
            $age = $current_year - $year;
        }

        if ($dry_run) {
            echo "Envoi simulé au membre n° " . (int)$row['user_id'] . ".\n";
            $count++;
            continue;
        }
        echo "Envoi à $username ($email)... ";

        // Préparation du message
        $message = "Bonjour $username,\n\n";
        $message .= "Toute l'équipe de Tireur.org est ravie de vous souhaiter un très joyeux anniversaire";
        if ($age) {
            $message .= " pour vos $age ans";
        }
        $message .= " !\n\n";
        $message .= "Nous vous remercions de votre fidélité et de votre participation à notre communauté francophone de tir sportif.\n\n";
        $message .= "À très bientôt sur le forum,\n\n";
        $message .= "L'équipe Tireur.org\nhttps://www.tireur.org\n\n";
        $message .= "Pour ne plus recevoir ce courriel : « Mon profil », case « Pas de courriel d'anniversaire »,\n";
        $message .= "https://www.tireur.org/forum/control.php?0,panel=user";

        // Envoi via l'API Phorum
        $mail_data = array(
            'from_address' => phorum_api_mail_encode_header($from_name) . " <$from_email>",
            'mailsubject'  => $subject,
            'mailmessage'  => $message
        );

        phorum_email_user(array($email), $mail_data);
        echo "OK.\n";
        $count++;
    }
    echo "$count email(s) " . ($dry_run ? "à envoyer (simulation, rien n'est parti)" : "envoyé(s)") . ".\n";
}

// 3. Mise à jour de la date de dernière exécution (pas en simulation)
if (!$dry_run) {
    phorum_db_update_settings(array('mod_birthday_email_last_run' => date('Y-m-d')));
}

?>
