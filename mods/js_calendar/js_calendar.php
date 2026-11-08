<?php

if (!defined('PHORUM')) return;

/**
 * Addon hook to display the js_calendar page.
 */
function phorum_mod_js_calendar_addon() {
    $PHORUM = $GLOBALS['PHORUM'];

    // Determine action from URL args
    $action = 'display';
    if (isset($PHORUM['args']['action'])) {
        $action = $PHORUM['args']['action'];
    } elseif (isset($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
    }

    // Handle AJAX actions
    if ($action !== 'display') {
        // Clean output buffer to ensure pure JSON
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        
        $data = array();
        try {
            if ($action === 'list') {
                $data = phorum_mod_js_calendar_list_events();
            } elseif ($action === 'list_all_dates') {
                $data = phorum_mod_js_calendar_list_all_dates();
            } elseif ($action === 'save') {
                $data = phorum_mod_js_calendar_save_event();
            } elseif ($action === 'delete') {
                $data = phorum_mod_js_calendar_delete_event();
            } elseif ($action === 'export') {
                phorum_mod_js_calendar_export_ical();
                exit;
            }
        } catch (Exception $e) {
            $data = array('error' => $e->getMessage());
        }
        
        echo json_encode($data);
        exit;
    }

    // Standard display logic
    phorum_build_common_urls();

    $is_logged_in = !empty($PHORUM['user']['user_id']);

    // Jour à ouvrir (lien « Prochains événements » de la colonne latérale) : AAAA-MM-JJ.
    $date_initiale = isset($PHORUM['args']['date']) ? (string)$PHORUM['args']['date'] : '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_initiale)) {
        $date_initiale = '';
    }

    $GLOBALS['PHORUM']['DATA']['CALENDAR'] = array(
        'is_logged_in' => $is_logged_in,
        'user_id' => $is_logged_in ? (int)$PHORUM['user']['user_id'] : 0,
        'is_admin' => !empty($PHORUM['user']['admin']) ? 1 : 0,
        'ajax_url' => phorum_get_url(PHORUM_ADDON_URL, 'module=js_calendar'),
        'jeton' => phorum_mod_js_calendar_jeton(),
        'date_initiale' => $date_initiale
    );

    phorum_output('js_calendar::cal');
}

/**
 * Register CSS files
 */
function phorum_mod_js_calendar_css_register($data) {
    if ($GLOBALS['PHORUM']['page'] !== 'addon' || !isset($_REQUEST['module']) || $_REQUEST['module'] !== 'js_calendar') {
        return $data;
    }
    
    $data['register'][] = array(
        'module' => 'js_calendar',
        'where'  => 'after',
        'source' => 'file(mods/js_calendar/css/calendar.css)'
    );
    
    return $data;
}

/**
 * Register JavaScript files
 */
function phorum_mod_js_calendar_javascript_register($data) {
    if ($GLOBALS['PHORUM']['page'] !== 'addon' || !isset($_REQUEST['module']) || $_REQUEST['module'] !== 'js_calendar') {
        return $data;
    }

    $data[] = array(
        'module' => 'js_calendar',
        'source' => 'file(mods/js_calendar/js/calendar.js)'
    );
    
    return $data;
}

function phorum_mod_js_calendar_list_events() {
    $PHORUM = $GLOBALS['PHORUM'];
    $date = isset($_REQUEST['date']) ? $_REQUEST['date'] : (isset($PHORUM['args']['date']) ? $PHORUM['args']['date'] : '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return array('error' => 'Date invalide');

    $prefix = $PHORUM['DBCONFIG']['table_prefix'];
    $quoted_date = phorum_db_interact(DB_RETURN_QUOTED, $date);

    // 1. Fetch standard events
    // Un événement lié à une annonce (colonne thread) ne paraît que si le fil est approuvé :
    // un fil masqué ou supprimé emporte son événement.
    $sql = "SELECT e.*, COALESCE(u.username, m.author) AS username, m.forum_id AS thread_forum_id
            FROM phorum_calendar_events e
            LEFT JOIN {$prefix}_users u ON e.user_id = u.user_id
            LEFT JOIN {$prefix}_messages m ON m.message_id = e.thread
            WHERE e.event_date = '$quoted_date'
              AND (e.thread IS NULL OR m.status = " . PHORUM_STATUS_APPROVED . ")
            ORDER BY e.created_at ASC";
    $res = phorum_db_interact(DB_RETURN_RES, $sql);

    $results = array();
    if ($res) {
        while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
            $row['type'] = 'event';
            if (!empty($row['thread'])) {
                // FOREIGN : la page du calendrier est hors forum (?0,…) ; READ_URL y mettrait 0.
                $row['thread_url'] = phorum_get_url(PHORUM_FOREIGN_READ_URL, (int)$row['thread_forum_id'], (int)$row['thread']);
            }
            $results[] = $row;
        }
    }

    // 2. Anniversaires du jour : aux membres connectés seulement, et sans l'année.
    //    Jusqu'au 2026-11-08, n'importe quel visiteur lisait ici « Date de naissance :
    //    AAAA-MM-JJ » de chaque membre (52 dates complètes), et l'export iCal les donnait
    //    toutes d'un coup. La date de naissance n'est pas lue : seul le jour du mois l'est.
    if (!phorum_mod_js_calendar_membre()) {
        return $results;
    }
    $day_month = substr($date, 5); // MM-DD, issu d'une date déjà validée
    $sql_bday = "SELECT u.user_id, u.username
                 FROM {$prefix}_users u
                 JOIN {$prefix}_user_custom_fields b ON u.user_id = b.user_id AND b.type = 22
                 LEFT JOIN {$prefix}_user_custom_fields p ON u.user_id = p.user_id AND p.type = 23
                 WHERE b.data LIKE '%-$day_month'
                 AND (p.data IS NULL OR p.data = '0')
                 AND u.active = 1";
    $res_bday = phorum_db_interact(DB_RETURN_RES, $sql_bday);
    if ($res_bday) {
        while ($row = phorum_db_fetch_row($res_bday, DB_RETURN_ASSOC)) {
            $results[] = array(
                'event_id'    => 'bday_' . $row['user_id'],
                'user_id'     => $row['user_id'],
                'username'    => $row['username'],
                'title'       => "Anniversaire de " . $row['username'],
                'event_date'  => $date,
                'type'        => 'birthday'
            );
        }
    }

    return $results;
}

/**
 * Le lecteur est-il un membre connecté ? Les anniversaires ne sont montrés qu'à eux.
 */
function phorum_mod_js_calendar_membre() {
    return !empty($GLOBALS['PHORUM']['user']['user_id']);
}

function phorum_mod_js_calendar_list_all_dates() {
    $PHORUM = $GLOBALS['PHORUM'];
    $prefix = $PHORUM['DBCONFIG']['table_prefix'];
    
    // Standard events dates
    $res = phorum_db_interact(DB_RETURN_RES, "SELECT DISTINCT e.event_date FROM phorum_calendar_events e
        LEFT JOIN {$prefix}_messages m ON m.message_id = e.thread
        WHERE e.thread IS NULL OR m.status = " . PHORUM_STATUS_APPROVED);
    $results = array();
    if ($res) {
        while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
            $results[] = $row['event_date'];
        }
    }

    // Jours d'anniversaire à surligner (années précédente, courante et suivante) : pour
    // les membres connectés seulement, comme la liste du jour.
    if (!phorum_mod_js_calendar_membre()) {
        return array_values(array_unique($results));
    }
    $year = date('Y');
    $sql_bday = "SELECT DISTINCT SUBSTR(b.data, 6) as md
                 FROM {$prefix}_user_custom_fields b
                 JOIN {$prefix}_users u ON u.user_id = b.user_id AND u.active = 1
                 LEFT JOIN {$prefix}_user_custom_fields p ON b.user_id = p.user_id AND p.type = 23
                 WHERE b.type = 22 AND (p.data IS NULL OR p.data = '0')";
    $res_bday = phorum_db_interact(DB_RETURN_RES, $sql_bday);
    if ($res_bday) {
        while ($row = phorum_db_fetch_row($res_bday, DB_RETURN_ASSOC)) {
            $results[] = $year . '-' . $row['md'];
            $results[] = ($year+1) . '-' . $row['md'];
            $results[] = ($year-1) . '-' . $row['md'];
        }
    }

    // array_values : array_unique laisse des trous dans les clés, json_encode rend alors un
    // objet, et le gabarit (Array.isArray) ne surlignait plus aucun jour.
    return array_values(array_unique($results));
}

/**
 * Jeton anti-CSRF des écritures (ajout, modification, suppression), propre au membre.
 * Jusqu'au 2026-11-08, la suppression passait par une simple requête GET sans jeton :
 * un lien ou une image piégés effaçaient les événements du membre qui les chargeait.
 * Signé par la clé privée du forum : un autre site ne peut pas le calculer.
 */
function phorum_mod_js_calendar_jeton() {
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['user']['user_id']) || empty($PHORUM['private_key'])) return '';
    return hash_hmac('sha256', 'js_calendar|' . (int)$PHORUM['user']['user_id'] . '|' .
                     (isset($PHORUM['user']['sessid_lt']) ? $PHORUM['user']['sessid_lt'] : ''),
                     $PHORUM['private_key']);
}

/** Une écriture n'est acceptée qu'en POST, avec le jeton du membre. */
function phorum_mod_js_calendar_ecriture_permise() {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') return false;
    $attendu = phorum_mod_js_calendar_jeton();
    $recu = isset($_POST['jeton']) && is_string($_POST['jeton']) ? $_POST['jeton'] : '';
    return $attendu !== '' && hash_equals($attendu, $recu);
}

function phorum_mod_js_calendar_save_event() {
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['user']['user_id'])) return array('success' => false, 'error' => 'Non connecté');
    if (!phorum_mod_js_calendar_ecriture_permise()) return array('success' => false, 'error' => 'Jeton invalide : rechargez la page.');

    $title = isset($_POST['title']) && is_string($_POST['title']) ? trim($_POST['title']) : '';
    $desc = isset($_POST['description']) && is_string($_POST['description']) ? trim($_POST['description']) : '';
    $date = isset($_POST['date']) && is_string($_POST['date']) ? $_POST['date'] : '';
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

    if (empty($title) || empty($date)) return array('success' => false, 'error' => 'Champs requis manquants');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return array('success' => false, 'error' => 'Date invalide');

    $quoted_title = phorum_db_interact(DB_RETURN_QUOTED, $title);
    $quoted_desc = phorum_db_interact(DB_RETURN_QUOTED, $desc);
    $quoted_date = phorum_db_interact(DB_RETURN_QUOTED, $date);
    $user_id = (int)$PHORUM['user']['user_id'];

    if ($event_id > 0) {
        $res = phorum_db_interact(DB_RETURN_RES, "SELECT user_id, thread FROM phorum_calendar_events WHERE event_id = $event_id");
        $row = $res ? phorum_db_fetch_row($res, DB_RETURN_ASSOC) : null;
        if (!$row) return array('success' => false, 'error' => 'Événement introuvable');
        if ($row['user_id'] != $user_id && empty($PHORUM['user']['admin'])) {
            return array('success' => false, 'error' => 'Accès interdit');
        }
        if (!empty($row['thread']) && empty($PHORUM['user']['admin'])) {
            return array('success' => false, 'error' => "Cet événement vient d'une annonce du forum : modifiez la date dans l'annonce.");
        }
        $sql = "UPDATE phorum_calendar_events SET title = '$quoted_title', description = '$quoted_desc', event_date = '$quoted_date' WHERE event_id = $event_id";
    } else {
        $sql = "INSERT INTO phorum_calendar_events (user_id, title, description, event_date) VALUES ($user_id, '$quoted_title', '$quoted_desc', '$quoted_date')";
    }

    phorum_db_interact(DB_RETURN_RES, $sql);
    return array('success' => true);
}

function phorum_mod_js_calendar_delete_event() {
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['user']['user_id'])) return array('success' => false, 'error' => 'Non connecté');
    if (!phorum_mod_js_calendar_ecriture_permise()) return array('success' => false, 'error' => 'Jeton invalide : rechargez la page.');

    // En POST seulement (voir phorum_mod_js_calendar_jeton).
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    
    $res = phorum_db_interact(DB_RETURN_RES, "SELECT user_id, thread FROM phorum_calendar_events WHERE event_id = $event_id");
    if (!$res) return array('success' => false, 'error' => 'Event introuvable');
    $row = phorum_db_fetch_row($res, DB_RETURN_ASSOC);

    if (!$row || ($row['user_id'] != $PHORUM['user']['user_id'] && empty($PHORUM['user']['admin']))) {
        return array('success' => false, 'error' => 'Accès interdit');
    }
    if (!empty($row['thread']) && empty($PHORUM['user']['admin'])) {
        return array('success' => false, 'error' => "Cet événement vient d'une annonce du forum : effacez la date dans l'annonce.");
    }

    phorum_db_interact(DB_RETURN_RES, "DELETE FROM phorum_calendar_events WHERE event_id = $event_id");
    return array('success' => true);
}

function phorum_mod_js_calendar_export_ical() {
    $PHORUM = $GLOBALS['PHORUM'];
    $prefix = $PHORUM['DBCONFIG']['table_prefix'];

    // 1. Fetch events
    $sql = "SELECT e.*, COALESCE(u.username, m.author) AS username, m.forum_id AS thread_forum_id
            FROM phorum_calendar_events e
            LEFT JOIN {$prefix}_users u ON e.user_id = u.user_id
            LEFT JOIN {$prefix}_messages m ON m.message_id = e.thread
            WHERE e.thread IS NULL OR m.status = " . PHORUM_STATUS_APPROVED . "
            ORDER BY e.event_date ASC";
    $res = phorum_db_interact(DB_RETURN_RES, $sql);

    // 2. Prepare iCal content
    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//Tireur.org//jsCalendar//FR\r\n";
    $ical .= "CALSCALE:GREGORIAN\r\n";
    $ical .= "METHOD:PUBLISH\r\n";
    $ical .= "X-WR-CALNAME:Calendrier Tireur.org\r\n";

    if ($res) {
        while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
            $dt = str_replace('-', '', $row['event_date']);
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:event_" . $row['event_id'] . "@tireur.org\r\n";
            $ical .= "DTSTAMP:" . date('Ymd\THis\Z', strtotime($row['created_at'])) . "\r\n";
            $ical .= "DTSTART;VALUE=DATE:" . $dt . "\r\n";
            $ical .= "SUMMARY:" . phorum_mod_js_calendar_ical_escape($row['title']) . "\r\n";
            if (!empty($row['thread'])) {
                $url_fil = phorum_get_url(PHORUM_FOREIGN_READ_URL, (int)$row['thread_forum_id'], (int)$row['thread']);
                if (strpos($url_fil, 'http') !== 0) $url_fil = 'https://www.tireur.org' . (substr($url_fil, 0, 1) === '/' ? '' : '/forum/') . $url_fil;
                $ical .= "URL:" . $url_fil . "\r\n";
            }
            if (!empty($row['description'])) {
                $ical .= "DESCRIPTION:" . phorum_mod_js_calendar_ical_escape($row['description'] . " (Par " . $row['username'] . ")") . "\r\n";
            } else {
                $ical .= "DESCRIPTION:Par " . phorum_mod_js_calendar_ical_escape($row['username']) . "\r\n";
            }
            $ical .= "END:VEVENT\r\n";
        }
    }

    // 3. Pas d'anniversaires dans l'export. Jusqu'au 2026-11-08, il les donnait sans
    //    connexion, chacun avec la date de naissance complète en DTSTART (52 membres). Un
    //    agenda abonné télécharge cette adresse sans les cookies du membre : on ne peut pas
    //    y réserver les anniversaires aux membres, on ne les y met donc pas.

    $ical .= "END:VCALENDAR\r\n";

    // 4. Send file
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="tireur_org_calendar.ics"');
    echo $ical;
}

function phorum_mod_js_calendar_ical_escape($text) {
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace(',', '\\,', $text);
    $text = str_replace(';', '\\;', $text);
    $text = str_replace("\n", '\\n', $text);
    return $text;
}

/* ---------------------------------------------------------------------------------------
 * Annonces du forum ↔ calendrier (2026-11-08).
 *
 * Dans les forums d'annonces, le formulaire d'un NOUVEAU fil (et la modification de son
 * premier message) porte un champ facultatif « Date de l'événement ». La date est gardée
 * dans les métadonnées du message (meta.mod_js_calendar_date) et reportée dans
 * phorum_calendar_events, colonne thread (unique) : l'événement ouvre son fil, le fil
 * affiche sa date en tête. Un événement lié à une annonce se modifie depuis l'annonce,
 * pas depuis le calendrier (save/delete le refusent, sauf administrateur). Un fil masqué
 * ou supprimé fait disparaître son événement : toutes les lectures joignent le message
 * et exigent le statut approuvé.
 * ------------------------------------------------------------------------------------- */

/** Forums dont les nouveaux fils peuvent porter une date d'événement. */
function phorum_mod_js_calendar_forums_annonces() {
    return array(30); // « Annonces de concours »
}

function phorum_mod_js_calendar_date_valide($d) {
    if (!is_string($d) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) return false;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

function phorum_mod_js_calendar_date_fr($d) {
    $mois = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août',
                  'septembre', 'octobre', 'novembre', 'décembre');
    list($a, $m, $j) = explode('-', $d);
    return (int)$j . ' ' . $mois[(int)$m - 1] . ' ' . $a;
}

/** Premier message d'un fil, dans un forum d'annonces ? */
function phorum_mod_js_calendar_annonce($message) {
    return is_array($message)
        && isset($message['forum_id'], $message['parent_id'])
        && in_array((int)$message['forum_id'], phorum_mod_js_calendar_forums_annonces(), true)
        && (int)$message['parent_id'] === 0;
}

/** Hook tpl_editor_before_textarea : le champ de date, au-dessus du texte du message. */
function phorum_mod_js_calendar_tpl_editor_before_textarea() {
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['DATA']['POSTING']) || !is_array($PHORUM['DATA']['POSTING'])) return;
    $m = $PHORUM['DATA']['POSTING'];
    if (!isset($m['forum_id'])) $m['forum_id'] = isset($PHORUM['forum_id']) ? $PHORUM['forum_id'] : 0;
    if (!isset($m['parent_id'])) $m['parent_id'] = 0;
    if (!phorum_mod_js_calendar_annonce($m)) return;

    // Valeur : celle qui vient d'être saisie (aperçu, erreur), sinon celle du message.
    if (isset($_POST['mod_js_calendar_date']) && is_string($_POST['mod_js_calendar_date'])) {
        $val = trim($_POST['mod_js_calendar_date']);
    } else {
        $val = isset($m['meta']['mod_js_calendar_date']) ? $m['meta']['mod_js_calendar_date'] : '';
    }
    if (!phorum_mod_js_calendar_date_valide($val)) $val = '';
    $cal = phorum_get_url(PHORUM_ADDON_URL, 'module=js_calendar');

    echo '<div class="mod-js-calendar-date" style="margin: 0.75rem 0;">'
       . '<label for="mod-js-calendar-date"><strong>Date de l\'événement</strong> (facultatif)</label><br />'
       . '<input type="date" id="mod-js-calendar-date" name="mod_js_calendar_date" value="' . htmlspecialchars($val) . '" /> '
       . '<br /><small>Concours, portes ouvertes, bourse aux armes&nbsp;: la date paraîtra dans le '
       . '<a href="' . htmlspecialchars($cal) . '">calendrier des événements</a> et dans l\'encadré '
       . '« Prochains événements », avec un lien vers cette annonce. Laissez vide si l\'annonce n\'a pas de date.</small>'
       . '</div>';
}

/** Hook check_post : une date saisie doit être valide. */
function phorum_mod_js_calendar_check_post($args) {
    list($message, $error) = $args;
    if (!$error && isset($_POST['mod_js_calendar_date']) && is_string($_POST['mod_js_calendar_date'])) {
        $d = trim($_POST['mod_js_calendar_date']);
        if ($d !== '' && !phorum_mod_js_calendar_date_valide($d)) {
            $error = "La date de l'événement n'est pas une date valide (AAAA-MM-JJ).";
        }
    }
    return array($message, $error);
}

/** Reporte le champ du formulaire dans les métadonnées ; champ absent = rien ne change. */
function phorum_mod_js_calendar_meta($message) {
    if (!isset($_POST['mod_js_calendar_date']) || !is_string($_POST['mod_js_calendar_date'])) return $message;
    $d = trim($_POST['mod_js_calendar_date']);
    if (!isset($message['meta']) || !is_array($message['meta'])) $message['meta'] = array();
    if (phorum_mod_js_calendar_date_valide($d)) {
        $message['meta']['mod_js_calendar_date'] = $d;
    } else {
        unset($message['meta']['mod_js_calendar_date']);
    }
    return $message;
}

/** Hook before_post : nouveau fil d'annonce. */
function phorum_mod_js_calendar_before_post($message) {
    return phorum_mod_js_calendar_annonce($message) ? phorum_mod_js_calendar_meta($message) : $message;
}

/** Hook before_edit : modification du premier message d'une annonce ($orig porte les ids). */
function phorum_mod_js_calendar_before_edit($dbmessage, $orig = null) {
    return phorum_mod_js_calendar_annonce($orig) ? phorum_mod_js_calendar_meta($dbmessage) : $dbmessage;
}

/** Hook after_post : l'annonce publiée, son événement est créé. */
function phorum_mod_js_calendar_after_post($message) {
    if (phorum_mod_js_calendar_annonce($message) && !empty($message['message_id'])) {
        phorum_mod_js_calendar_synchroniser((int)$message['message_id'], $message);
    }
    return $message;
}

/** Hook after_edit : date ajoutée, changée ou effacée dans l'annonce. */
function phorum_mod_js_calendar_after_edit($dbmessage, $orig = null) {
    if (phorum_mod_js_calendar_annonce($orig) && !empty($orig['message_id']) && isset($_POST['mod_js_calendar_date'])) {
        phorum_mod_js_calendar_synchroniser((int)$orig['message_id'], $dbmessage + $orig);
    }
    return $dbmessage;
}

/** Crée, met à jour ou supprime l'événement d'un fil (clé : thread, unique). */
function phorum_mod_js_calendar_synchroniser($thread, $message) {
    $d = isset($message['meta']['mod_js_calendar_date']) ? $message['meta']['mod_js_calendar_date'] : '';
    if (!phorum_mod_js_calendar_date_valide($d)) {
        phorum_db_interact(DB_RETURN_RES, "DELETE FROM phorum_calendar_events WHERE thread = " . (int)$thread);
        return;
    }
    $titre = phorum_db_interact(DB_RETURN_QUOTED, mb_substr((string)$message['subject'], 0, 255));
    $qd = phorum_db_interact(DB_RETURN_QUOTED, $d);
    $user = isset($message['user_id']) ? (int)$message['user_id'] : 0;
    phorum_db_interact(DB_RETURN_RES,
        "INSERT INTO phorum_calendar_events (user_id, thread, title, description, event_date)
         VALUES ($user, " . (int)$thread . ", '$titre', '', '$qd')
         ON DUPLICATE KEY UPDATE title = VALUES(title), event_date = VALUES(event_date)");
}

/** Hook format_fixup : la date en tête du premier message d'une annonce datée. */
function phorum_mod_js_calendar_format_fixup($messages) {
    if (!is_array($messages)) return $messages;
    foreach ($messages as $id => $m) {
        if (!is_array($m) || !isset($m['body'], $m['parent_id']) || (int)$m['parent_id'] !== 0) continue;
        $d = isset($m['meta']['mod_js_calendar_date']) ? $m['meta']['mod_js_calendar_date'] : '';
        if (!phorum_mod_js_calendar_date_valide($d)) continue;
        $url = phorum_get_url(PHORUM_ADDON_URL, 'module=js_calendar', 'date=' . $d);
        $messages[$id]['body'] = '<p class="mod-js-calendar-annonce"><strong>Événement le '
            . phorum_mod_js_calendar_date_fr($d) . '</strong> — <a href="' . htmlspecialchars($url)
            . '">voir le calendrier</a></p>' . $m['body'];
    }
    return $messages;
}
