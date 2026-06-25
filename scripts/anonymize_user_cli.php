<?php
/**
 * anonymize_user_cli.php — anonymisation RGPD en ligne de commande (rétroactif).
 *
 *   php8.3 scripts/anonymize_user_cli.php --check
 *       Diagnostic : messages déjà anonymisés dont l'IP traîne encore.
 *   php8.3 scripts/anonymize_user_cli.php --user "<pseudo>" [--apply]
 *       Anonymise un membre (journaux DokuWiki + IP de ses messages s'il existe encore).
 *   php8.3 scripts/anonymize_user_cli.php --purge-ips [--apply]
 *       Purge l'IP résiduelle de TOUS les comptes déjà supprimés (user_id=0, auteur anonyme).
 *
 * Sans --apply : DRY-RUN (aucune écriture). Lancer avec php8.3 (mysqli).
 * Pour les écritures DokuWiki, exécuter de préférence en www-data
 * (sudo -u www-data php8.3 ...) pour les permissions sur wiki/data/.
 */
define('PHORUM', 1); define('PHORUM_ADMIN', 1);
chdir(dirname(__DIR__));                 // racine du forum
require_once('./common.php');
require_once('./mods/custom_profile_fields_handler/anonymize.php');

$apply = in_array('--apply', $argv, true);
$tag   = $apply ? 'APPLIQUÉ' : 'DRY-RUN (aucune écriture)';

function arg_val($name) {
    global $argv; $i = array_search($name, $argv, true);
    return ($i !== false && isset($argv[$i + 1])) ? $argv[$i + 1] : null;
}

if (in_array('--check', $argv, true)) {
    global $PHORUM; $t = $PHORUM['message_table'];
    $lingering = (int)phorum_db_interact(DB_RETURN_VALUE,
        "SELECT count(*) FROM $t WHERE user_id=0 AND author='" . TIREUR_ANON_USER . "' AND ip NOT IN ('','" . TIREUR_ANON_IP . "')");
    $guests = (int)phorum_db_interact(DB_RETURN_VALUE,
        "SELECT count(*) FROM $t WHERE user_id=0 AND author<>'" . TIREUR_ANON_USER . "' AND author<>''");
    echo "== Diagnostic anonymisation ==\n";
    echo "Messages de comptes supprimés avec IP résiduelle : $lingering  (=> --purge-ips)\n";
    echo "Messages user_id=0 d'invités (auteur non anonyme, informatif) : $guests\n";
    exit;
}

if (($u = arg_val('--user')) !== null) {
    $uid = 0;
    if (function_exists('phorum_api_user_search')) {
        $found = phorum_api_user_search('username', $u, '=');
        if ($found) $uid = (int)(is_array($found) ? reset($found) : $found);
    }
    echo "== Anonymisation de « $u » ($tag) ==\n";
    echo $uid ? "Compte Phorum existant (#$uid) : IP des messages + journaux DokuWiki.\n"
              : "Compte introuvable (déjà supprimé) : journaux DokuWiki uniquement.\n";
    $r = tireur_anonymize_user($uid, $u, !$apply);
    echo "Messages IP concernés : {$r['ip_messages']}\n";
    echo "DokuWiki : {$r['wiki']['files']} fichier(s), {$r['wiki']['rows']} ligne(s).\n";
    exit;
}

if (in_array('--purge-ips', $argv, true)) {
    echo "== Purge rétroactive des IP des comptes supprimés ($tag) ==\n";
    $n = tireur_anon_phorum_ip_retro(!$apply);
    echo "Messages avec IP " . ($apply ? "purgée" : "à purger") . " : $n\n";
    exit;
}

fwrite(STDERR, "usage : --check | --user \"<pseudo>\" [--apply] | --purge-ips [--apply]\n");
exit(1);
