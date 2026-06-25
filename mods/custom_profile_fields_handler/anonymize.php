<?php
/**
 * anonymize.php — Anonymisation RGPD à la suppression de compte (Tireur.org).
 * Bibliothèque partagée par le hook Phorum `user_delete` et le script CLI rétroactif.
 *
 * Comble les lacunes du nettoyage natif de Phorum :
 *   - Phorum met déjà user_id=0, email='', author='Utilisateur anonyme' sur les
 *     messages publics ; il NE purge PAS la colonne `ip` -> on s'en charge.
 *   - DokuWiki (authphorum) garde pseudo + IP dans les fichiers .changes -> on les
 *     réécrit (pseudo -> "Utilisateur anonyme", IP -> "0.0.0.0").
 * Toutes les fonctions supportent un mode dry-run (aucune écriture).
 */
if (!defined('PHORUM')) return;     // chargé uniquement en contexte Phorum

define('TIREUR_ANON_USER', 'Utilisateur anonyme');
define('TIREUR_ANON_IP',   '0.0.0.0');
define('TIREUR_WIKI_DATA',  dirname(__DIR__, 3) . '/wiki/data');
define('TIREUR_ANON_LOG',   dirname(__DIR__, 3) . '/admin-data/cleanup.log');

function tireur_anon_log($msg) {
    @file_put_contents(TIREUR_ANON_LOG, sprintf("[%s] (rgpd) %s\n", date('Y-m-d H:i:s'), $msg), FILE_APPEND);
}

/** IP des messages d'un utilisateur ENCORE identifié par user_id (cas hook, avant
 *  que Phorum ne mette user_id=0). Renvoie le nombre de messages concernés. */
function tireur_anon_phorum_ip_by_uid($user_id, $dry_run = false) {
    global $PHORUM; $user_id = (int)$user_id; $t = $PHORUM['message_table'];
    $n = (int)phorum_db_interact(DB_RETURN_VALUE,
        "SELECT count(*) FROM $t WHERE user_id=$user_id AND ip NOT IN ('', '" . TIREUR_ANON_IP . "')");
    if (!$dry_run && $n) phorum_db_interact(DB_RETURN_RES,
        "UPDATE $t SET ip='" . TIREUR_ANON_IP . "' WHERE user_id=$user_id");
    return $n;
}

/** Rétroactif : purge l'IP résiduelle des messages DÉJÀ anonymisés (comptes
 *  supprimés avant ce correctif : user_id=0 ET author='Utilisateur anonyme').
 *  N'affecte PAS les messages d'invités (user_id=0 mais autre auteur). */
function tireur_anon_phorum_ip_retro($dry_run = false) {
    global $PHORUM; $t = $PHORUM['message_table'];
    $where = "user_id=0 AND author='" . TIREUR_ANON_USER . "' AND ip NOT IN ('', '" . TIREUR_ANON_IP . "')";
    $n = (int)phorum_db_interact(DB_RETURN_VALUE, "SELECT count(*) FROM $t WHERE $where");
    if (!$dry_run && $n) {
        // Sauvegarde restaurable (message_id\tip) AVANT l'effacement.
        $bk = dirname(__DIR__, 3) . '/admin-data/rgpd_ip_backup_' . date('Ymd-His') . '.tsv';
        $rows = phorum_db_interact(DB_RETURN_ROWS, "SELECT message_id, ip FROM $t WHERE $where");
        $buf = "# restore: UPDATE {$t} SET ip=<col2> WHERE message_id=<col1>\n";
        foreach ((array)$rows as $r) $buf .= $r[0] . "\t" . $r[1] . "\n";
        @file_put_contents($bk, $buf);
        tireur_anon_log("sauvegarde IP avant purge : $bk ($n lignes)");
        phorum_db_interact(DB_RETURN_RES, "UPDATE $t SET ip='" . TIREUR_ANON_IP . "' WHERE $where");
    }
    return $n;
}

/** Anonymise les journaux DokuWiki (.changes) d'un pseudo : remplace, dans chaque
 *  ligne où la colonne 5 (user) == $username, la col5 par "Utilisateur anonyme" et
 *  la col2 (IP) par "0.0.0.0". Écriture atomique (tmp + rename), perms préservées.
 *  Renvoie ['files'=>n fichiers modifiés, 'rows'=>n lignes]. */
function tireur_anon_dokuwiki($username, $dry_run = false) {
    $res = ['files' => 0, 'rows' => 0];
    if ($username === '' || $username === TIREUR_ANON_USER) return $res;
    foreach (['meta', 'media'] as $sub) {
        $dir = TIREUR_WIKI_DATA . '/' . $sub;
        if (!is_dir($dir)) continue;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO));
        foreach ($it as $f) {
            if (!$f->isFile() || substr($f->getFilename(), -8) !== '.changes') continue;
            $path = $f->getPathname();
            $lines = @file($path, FILE_IGNORE_NEW_LINES);
            if ($lines === false) continue;
            $changed = 0;
            foreach ($lines as $i => $line) {
                if ($line === '') continue;
                $c = explode("\t", $line);
                if (count($c) >= 5 && $c[4] === $username) {
                    $c[4] = TIREUR_ANON_USER;
                    if (isset($c[1])) $c[1] = TIREUR_ANON_IP;
                    $lines[$i] = implode("\t", $c);
                    $changed++;
                }
            }
            if ($changed) {
                $res['files']++; $res['rows'] += $changed;
                if (!$dry_run) {
                    $perms = @fileperms($path) & 0777;
                    $tmp = $path . '.anontmp';
                    if (@file_put_contents($tmp, implode("\n", $lines) . "\n", LOCK_EX) !== false) {
                        if ($perms) @chmod($tmp, $perms);
                        @rename($tmp, $path);
                    }
                }
            }
        }
    }
    return $res;
}

/** Orchestration complète pour un compte (hook ou CLI). */
function tireur_anonymize_user($user_id, $username, $dry_run = false) {
    $ip = ($user_id > 0) ? tireur_anon_phorum_ip_by_uid($user_id, $dry_run) : 0;
    $wiki = tireur_anon_dokuwiki((string)$username, $dry_run);
    $tag = $dry_run ? 'SIMULATION' : 'anonymisé';
    tireur_anon_log(sprintf('%s user="%s" (#%d) : messages IP=%d, wiki fichiers=%d lignes=%d',
        $tag, $username, (int)$user_id, $ip, $wiki['files'], $wiki['rows']));
    return ['ip_messages' => $ip, 'wiki' => $wiki];
}
