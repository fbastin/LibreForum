<?php

if (!defined('PHORUM')) return;

# The database scheme version, used to install and upgrade the module's table.
define('JS_CALENDAR_DB_VERSION', 2);

# The events table, under the configured table prefix like the core tables.
$GLOBALS['PHORUM']['js_calendar_table'] =
    $GLOBALS['PHORUM']['DBCONFIG']['table_prefix'] . '_calendar_events';

/**
 * Creates or upgrades the events table when needed. The module used to read
 * and write "phorum_calendar_events" without ever creating it: on a new
 * forum, or one with another table prefix, every query failed.
 * Returns TRUE when the table is ready, or an error message.
 */
function phorum_mod_js_calendar_db_init() {
    $PHORUM = $GLOBALS['PHORUM'];
    $version = isset($PHORUM['mod_js_calendar_installed'])
        ? (int)$PHORUM['mod_js_calendar_installed'] : 0;
    $layerpath = './mods/js_calendar/db/' . basename($PHORUM['DBCONFIG']['type']);

    while ($version < JS_CALENDAR_DB_VERSION) {
        $version++;
        $sqlfile = "$layerpath/$version.php";
        if (!file_exists($sqlfile)) {
            return "no database scheme script for this database layer ($sqlfile)";
        }
        $sqlqueries = array();
        include($sqlfile);
        // A script may have nothing to do (a column that is already there).
        $err = count($sqlqueries) ? phorum_db_run_queries($sqlqueries) : NULL;
        if ($err) {
            return "running $sqlfile failed: $err";
        }
        if (!phorum_db_update_settings(array('mod_js_calendar_installed' => $version))) {
            return "saving the mod_js_calendar_installed setting failed";
        }
        $GLOBALS['PHORUM']['mod_js_calendar_installed'] = $version;
    }
    return TRUE;
}

/**
 * Loads the module's language file into $PHORUM['DATA']['LANG']['mod_js_calendar'],
 * english.php for a language that has no file. Done here rather than through a
 * "lang" hook, which would need every forum to save its module settings again.
 */
function phorum_mod_js_calendar_load_language() {
    global $PHORUM;
    if (isset($PHORUM['DATA']['LANG']['mod_js_calendar'])) return;
    $language = empty($PHORUM['language']) ? 'english' : basename($PHORUM['language']);
    $file = "./mods/js_calendar/lang/$language.php";
    if (!file_exists($file)) $file = './mods/js_calendar/lang/english.php';
    include $file;
}

/** A string of the module's language file, "%name%" replaced when given. */
function phorum_mod_js_calendar_t($key, $name = NULL) {
    phorum_mod_js_calendar_load_language();
    $lang = $GLOBALS['PHORUM']['DATA']['LANG']['mod_js_calendar'];
    $text = isset($lang[$key]) ? $lang[$key] : $key;
    return $name === NULL ? $text : str_replace('%name%', $name, $text);
}

/**
 * Addon hook to display the js_calendar page.
 */
function phorum_mod_js_calendar_addon() {
    $PHORUM = $GLOBALS['PHORUM'];

    $db_ready = phorum_mod_js_calendar_db_init();
    if ($db_ready !== TRUE) {
        trigger_error("js_calendar: $db_ready", E_USER_WARNING);
    }
    phorum_mod_js_calendar_load_language();

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

    $GLOBALS['PHORUM']['DATA']['CALENDAR'] = phorum_mod_js_calendar_page_data();

    phorum_output('js_calendar::cal');
}

/**
 * Template data of the calendar page: who reads it, where its requests go, and
 * the strings and jsCalendar language of the forum's language.
 */
function phorum_mod_js_calendar_page_data() {
    $PHORUM = $GLOBALS['PHORUM'];
    phorum_mod_js_calendar_load_language();

    $is_logged_in = !empty($PHORUM['user']['user_id']);

    $data = array(
        'is_logged_in' => $is_logged_in,
        'user_id' => $is_logged_in ? (int)$PHORUM['user']['user_id'] : 0,
        'is_admin' => !empty($PHORUM['user']['admin']) ? 1 : 0,
        'ajax_url' => phorum_get_url(PHORUM_ADDON_URL, 'module=js_calendar'),
        'csrf_token' => phorum_mod_js_calendar_csrf_token(),
        // jsCalendar's own language file, when the forum's language has one.
        'jscal_language' => '',
        'jscal_lang_script' => '',
        // Strings for the page's script, as a JSON object safe inside <script>.
        'strings_json' => '',
        // Day to open, from the link at the top of a dated announcement (YYYY-MM-DD).
        'initial_date' => ''
    );
    if (isset($PHORUM['args']['date']) && phorum_mod_js_calendar_date_valid((string)$PHORUM['args']['date'])) {
        $data['initial_date'] = (string)$PHORUM['args']['date'];
    }
    $lang = $GLOBALS['PHORUM']['DATA']['LANG']['mod_js_calendar'];
    $code = preg_replace('/[^a-z]/', '', strtolower($lang['jscalendar_language']));
    $data['jscal_language'] = $code ? $code : 'en';
    if ($code && $code !== 'en' && file_exists("./mods/js_calendar/js/jsCalendar.lang.$code.js")) {
        $data['jscal_lang_script'] =
            $PHORUM['http_path'] . "/mods/js_calendar/js/jsCalendar.lang.$code.js?v=1";
    } else {
        $data['jscal_language'] = 'en';
    }
    $js = array();
    foreach (array('months', 'loading', 'no_events', 'load_error', 'confirm_delete', 'error',
                   'unknown', 'network_error', 'by', 'announcement_by', 'add_event', 'edit_event', 'delete') as $key) {
        $js[$key] = $lang[$key];
    }
    $data['strings_json'] = json_encode($js,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    return $data;
}

function phorum_mod_js_calendar_list_events() {
    $PHORUM = $GLOBALS['PHORUM'];
    $date = isset($_REQUEST['date']) ? $_REQUEST['date'] : (isset($PHORUM['args']['date']) ? $PHORUM['args']['date'] : '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return array('error' => phorum_mod_js_calendar_t('invalid_date'));

    $prefix = $PHORUM['DBCONFIG']['table_prefix'];
    $quoted_date = phorum_db_interact(DB_RETURN_QUOTED, $date);

    // 1. Events. One created from a forum announcement (thread column) only
    //    shows while its thread is approved: a hidden or deleted thread takes
    //    its event with it.
    $sql = "SELECT e.*, COALESCE(u.username, m.author) AS username, m.forum_id AS thread_forum_id
            FROM {$PHORUM['js_calendar_table']} e
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
                $row['thread_url'] = phorum_mod_js_calendar_thread_url($row['thread_forum_id'], $row['thread']);
            }
            $results[] = $row;
        }
    }

    // 2. Birthdays of the day: for logged-in members only, and without the year.
    //    Any visitor used to read "Date de naissance : YYYY-MM-DD" of every member
    //    here, and the iCal export gave them all at once. The birth date itself is
    //    not read any more: only the day of the month is.
    list($bday_field, $privacy_field) = phorum_mod_js_calendar_birthday_fields();
    if (!phorum_mod_js_calendar_member() || !$bday_field) {
        return $results;
    }
    $day_month = substr($date, 5); // MM-DD, from a date already validated
    $sql_bday = "SELECT u.user_id, u.username
                 FROM {$prefix}_users u
                 JOIN {$prefix}_user_custom_fields b ON u.user_id = b.user_id AND b.type = $bday_field
                 LEFT JOIN {$prefix}_user_custom_fields p ON u.user_id = p.user_id AND p.type = $privacy_field
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
                'title'       => phorum_mod_js_calendar_t('birthday_of', $row['username']),
                'event_date'  => $date,
                'type'        => 'birthday'
            );
        }
    }

    return $results;
}

/**
 * Is the reader a logged-in member? Birthdays are only shown to members.
 */
function phorum_mod_js_calendar_member() {
    return !empty($GLOBALS['PHORUM']['user']['user_id']);
}

/**
 * Ids of the birthday profile fields, "user_birthday" (YYYY-MM-DD) and
 * "user_birthday_privacy" (1 = keep private), looked up by name: their ids
 * depend on the order in which a forum created its profile fields. They used
 * to be hard-coded as 22 and 23, the ids of one particular forum; elsewhere the
 * queries read whatever fields 22 and 23 were. Returns array(birthday, privacy);
 * 0 for a field that does not exist (no birthday field: no birthdays).
 */
function phorum_mod_js_calendar_birthday_fields() {
    require_once './include/api/custom_profile_fields.php';
    $ids = array();
    foreach (array('user_birthday', 'user_birthday_privacy') as $name) {
        $field = phorum_api_custom_profile_field_byname($name);
        $ids[] = ($field && empty($field['deleted'])) ? (int)$field['id'] : 0;
    }
    return $ids;
}

function phorum_mod_js_calendar_list_all_dates() {
    $PHORUM = $GLOBALS['PHORUM'];
    $prefix = $PHORUM['DBCONFIG']['table_prefix'];
    
    // Standard events dates
    $res = phorum_db_interact(DB_RETURN_RES,
        "SELECT DISTINCT e.event_date FROM {$PHORUM['js_calendar_table']} e
         LEFT JOIN {$prefix}_messages m ON m.message_id = e.thread
         WHERE e.thread IS NULL OR m.status = " . PHORUM_STATUS_APPROVED);
    $results = array();
    if ($res) {
        while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
            $results[] = $row['event_date'];
        }
    }

    // Birthday days to highlight (previous, current and next year): for logged-in
    // members only, like the list of the day.
    list($bday_field, $privacy_field) = phorum_mod_js_calendar_birthday_fields();
    if (!phorum_mod_js_calendar_member() || !$bday_field) {
        return array_values(array_unique($results));
    }
    $year = date('Y');
    $sql_bday = "SELECT DISTINCT SUBSTR(b.data, 6) as md
                 FROM {$prefix}_user_custom_fields b
                 JOIN {$prefix}_users u ON u.user_id = b.user_id AND u.active = 1
                 LEFT JOIN {$prefix}_user_custom_fields p ON b.user_id = p.user_id AND p.type = $privacy_field
                 WHERE b.type = $bday_field AND (p.data IS NULL OR p.data = '0')";
    $res_bday = phorum_db_interact(DB_RETURN_RES, $sql_bday);
    if ($res_bday) {
        while ($row = phorum_db_fetch_row($res_bday, DB_RETURN_ASSOC)) {
            $results[] = $year . '-' . $row['md'];
            $results[] = ($year+1) . '-' . $row['md'];
            $results[] = ($year-1) . '-' . $row['md'];
        }
    }

    // array_values(): array_unique() leaves holes in the keys, json_encode() then gives
    // an object, and the template (Array.isArray) highlighted no day at all.
    return array_values(array_unique($results));
}

/**
 * Anti-CSRF token for writes (add, edit, delete), specific to the member.
 * Deleting used to be a plain GET request without a token: a booby-trapped
 * link or image deleted the events of the member who loaded it. Signed with
 * the forum's private key: another site cannot compute it.
 */
function phorum_mod_js_calendar_csrf_token() {
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['user']['user_id']) || empty($PHORUM['private_key'])) return '';
    return hash_hmac('sha256', 'js_calendar|' . (int)$PHORUM['user']['user_id'] . '|' .
                     (isset($PHORUM['user']['sessid_lt']) ? $PHORUM['user']['sessid_lt'] : ''),
                     $PHORUM['private_key']);
}

/** A write is only accepted by POST, with the member's token. */
function phorum_mod_js_calendar_write_allowed() {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') return false;
    $expected = phorum_mod_js_calendar_csrf_token();
    $received = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    return $expected !== '' && hash_equals($expected, $received);
}

function phorum_mod_js_calendar_save_event() {
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['user']['user_id'])) return array('success' => false, 'error' => phorum_mod_js_calendar_t('not_logged_in'));
    if (!phorum_mod_js_calendar_write_allowed()) return array('success' => false, 'error' => phorum_mod_js_calendar_t('invalid_token'));

    $title = isset($_POST['title']) && is_string($_POST['title']) ? trim($_POST['title']) : '';
    $desc = isset($_POST['description']) && is_string($_POST['description']) ? trim($_POST['description']) : '';
    $date = isset($_POST['date']) && is_string($_POST['date']) ? $_POST['date'] : '';
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

    if (empty($title) || empty($date)) return array('success' => false, 'error' => phorum_mod_js_calendar_t('missing_fields'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return array('success' => false, 'error' => phorum_mod_js_calendar_t('invalid_date'));

    $quoted_title = phorum_db_interact(DB_RETURN_QUOTED, $title);
    $quoted_desc = phorum_db_interact(DB_RETURN_QUOTED, $desc);
    $quoted_date = phorum_db_interact(DB_RETURN_QUOTED, $date);
    $user_id = (int)$PHORUM['user']['user_id'];

    if ($event_id > 0) {
        $res = phorum_db_interact(DB_RETURN_RES, "SELECT user_id, thread FROM {$PHORUM['js_calendar_table']} WHERE event_id = $event_id");
        $row = $res ? phorum_db_fetch_row($res, DB_RETURN_ASSOC) : null;
        if (!$row) return array('success' => false, 'error' => phorum_mod_js_calendar_t('not_found'));
        if ($row['user_id'] != $user_id && empty($PHORUM['user']['admin'])) {
            return array('success' => false, 'error' => phorum_mod_js_calendar_t('forbidden'));
        }
        // An announcement's event is changed in the announcement (except by an admin).
        if (!empty($row['thread']) && empty($PHORUM['user']['admin'])) {
            return array('success' => false, 'error' => phorum_mod_js_calendar_t('edit_in_announcement'));
        }
        $sql = "UPDATE {$PHORUM['js_calendar_table']} SET title = '$quoted_title', description = '$quoted_desc', event_date = '$quoted_date' WHERE event_id = $event_id";
    } else {
        $sql = "INSERT INTO {$PHORUM['js_calendar_table']} (user_id, title, description, event_date) VALUES ($user_id, '$quoted_title', '$quoted_desc', '$quoted_date')";
    }

    phorum_db_interact(DB_RETURN_RES, $sql);
    return array('success' => true);
}

function phorum_mod_js_calendar_delete_event() {
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['user']['user_id'])) return array('success' => false, 'error' => phorum_mod_js_calendar_t('not_logged_in'));
    if (!phorum_mod_js_calendar_write_allowed()) return array('success' => false, 'error' => phorum_mod_js_calendar_t('invalid_token'));

    // By POST only (see phorum_mod_js_calendar_csrf_token()).
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    
    $res = phorum_db_interact(DB_RETURN_RES, "SELECT user_id, thread FROM {$PHORUM['js_calendar_table']} WHERE event_id = $event_id");
    if (!$res) return array('success' => false, 'error' => phorum_mod_js_calendar_t('not_found'));
    $row = phorum_db_fetch_row($res, DB_RETURN_ASSOC);

    if (!$row || ($row['user_id'] != $PHORUM['user']['user_id'] && empty($PHORUM['user']['admin']))) {
        return array('success' => false, 'error' => phorum_mod_js_calendar_t('forbidden'));
    }
    if (!empty($row['thread']) && empty($PHORUM['user']['admin'])) {
        return array('success' => false, 'error' => phorum_mod_js_calendar_t('delete_in_announcement'));
    }

    phorum_db_interact(DB_RETURN_RES, "DELETE FROM {$PHORUM['js_calendar_table']} WHERE event_id = $event_id");
    return array('success' => true);
}

function phorum_mod_js_calendar_export_ical() {
    $PHORUM = $GLOBALS['PHORUM'];
    $prefix = $PHORUM['DBCONFIG']['table_prefix'];

    // 1. Fetch events
    $sql = "SELECT e.*, COALESCE(u.username, m.author) AS username, m.forum_id AS thread_forum_id
            FROM {$PHORUM['js_calendar_table']} e
            LEFT JOIN {$prefix}_users u ON e.user_id = u.user_id
            LEFT JOIN {$prefix}_messages m ON m.message_id = e.thread
            WHERE e.thread IS NULL OR m.status = " . PHORUM_STATUS_APPROVED . "
            ORDER BY e.event_date ASC";
    $res = phorum_db_interact(DB_RETURN_RES, $sql);

    // 2. Prepare iCal content. The calendar is named after the forum, and the
    //    event UIDs use the forum's own host: they used to name one particular
    //    site, whatever forum served them.
    $host = parse_url($PHORUM['http_path'], PHP_URL_HOST);
    if (!$host && isset($_SERVER['HTTP_HOST'])) $host = $_SERVER['HTTP_HOST'];
    $host = preg_replace('/[^A-Za-z0-9.-]/', '', (string)$host);
    if ($host === '') $host = 'localhost';
    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//LibreForum//js_calendar//EN\r\n";
    $ical .= "CALSCALE:GREGORIAN\r\n";
    $ical .= "METHOD:PUBLISH\r\n";
    $ical .= "X-WR-CALNAME:" . phorum_mod_js_calendar_ical_escape(strip_tags($PHORUM['title'])) . "\r\n";

    if ($res) {
        while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
            $dt = str_replace('-', '', $row['event_date']);
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:event_" . $row['event_id'] . "@" . $host . "\r\n";
            $ical .= "DTSTAMP:" . date('Ymd\THis\Z', strtotime($row['created_at'])) . "\r\n";
            $ical .= "DTSTART;VALUE=DATE:" . $dt . "\r\n";
            $ical .= "SUMMARY:" . phorum_mod_js_calendar_ical_escape($row['title']) . "\r\n";
            if (!empty($row['description'])) {
                $ical .= "DESCRIPTION:" . phorum_mod_js_calendar_ical_escape($row['description'] . " (" . phorum_mod_js_calendar_t('by', $row['username']) . ")") . "\r\n";
            } else {
                $ical .= "DESCRIPTION:" . phorum_mod_js_calendar_ical_escape(phorum_mod_js_calendar_t('by', $row['username'])) . "\r\n";
            }
            if (!empty($row['thread'])) {
                $ical .= "URL:" . phorum_mod_js_calendar_thread_url($row['thread_forum_id'], $row['thread']) . "\r\n";
            }
            $ical .= "END:VEVENT\r\n";
        }
    }

    // 3. No birthdays in the export. It used to give them without logging in, each
    //    with the full birth date as DTSTART. A subscribed calendar downloads this
    //    address without the member's cookies: birthdays cannot be restricted to
    //    members there, so they are left out.

    $ical .= "END:VCALENDAR\r\n";

    // 4. Send file
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="calendar.ics"');
    echo $ical;
}

function phorum_mod_js_calendar_ical_escape($text) {
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace(',', '\\,', $text);
    $text = str_replace(';', '\\;', $text);
    $text = str_replace("\n", '\\n', $text);
    return $text;
}

/* ---------------------------------------------------------------------------
 * Dated forum announcements.
 *
 * In the forums chosen in the module settings, the form of a NEW thread (and
 * of the edit of its first message) carries an optional "Event date" field.
 * The date is kept in the message's metadata (meta.mod_js_calendar_date) and
 * copied into the events table, column thread (unique): the event links to
 * its thread, and the thread shows its date at the top. An announcement's
 * event is changed from the announcement, not from the calendar (save and
 * delete refuse it, except for an administrator). A hidden or deleted thread
 * takes its event with it: every read joins the message and requires the
 * approved status.
 * ------------------------------------------------------------------------- */

/** Forums whose new threads may carry an event date (module settings). */
function phorum_mod_js_calendar_announcement_forums() {
    $ids = isset($GLOBALS['PHORUM']['mod_js_calendar']['announcement_forums'])
         ? $GLOBALS['PHORUM']['mod_js_calendar']['announcement_forums'] : array();
    return array_map('intval', is_array($ids) ? $ids : array());
}

/** Absolute address of a thread, from the calendar page (outside any forum). */
function phorum_mod_js_calendar_thread_url($forum_id, $thread) {
    // FOREIGN: the calendar page is outside the forums (?0,...); READ_URL would put 0 there.
    $url = phorum_get_url(PHORUM_FOREIGN_READ_URL, (int)$forum_id, (int)$thread);
    if (!preg_match('!^https?://!i', $url)) {
        $url = rtrim($GLOBALS['PHORUM']['http_path'], '/') . '/' . ltrim($url, '/');
    }
    return $url;
}

function phorum_mod_js_calendar_date_valid($d) {
    if (!is_string($d) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) return false;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

/** "25 November 2026", with the month names of the forum's language. */
function phorum_mod_js_calendar_date_long($d) {
    $months = phorum_mod_js_calendar_t('months');
    list($y, $m, $day) = explode('-', $d);
    return (int)$day . ' ' . $months[(int)$m - 1] . ' ' . $y;
}

/** First message of a thread, in an announcement forum? */
function phorum_mod_js_calendar_is_announcement($message) {
    return is_array($message)
        && isset($message['forum_id'], $message['parent_id'])
        && in_array((int)$message['forum_id'], phorum_mod_js_calendar_announcement_forums(), true)
        && (int)$message['parent_id'] === 0;
}

/** tpl_editor_before_textarea hook: the date field, above the message text. */
function phorum_mod_js_calendar_tpl_editor_before_textarea() {
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['DATA']['POSTING']) || !is_array($PHORUM['DATA']['POSTING'])) return;
    $m = $PHORUM['DATA']['POSTING'];
    if (!isset($m['forum_id'])) $m['forum_id'] = isset($PHORUM['forum_id']) ? $PHORUM['forum_id'] : 0;
    if (!isset($m['parent_id'])) $m['parent_id'] = 0;
    if (!phorum_mod_js_calendar_is_announcement($m)) return;

    // Value: the one just typed (preview, error), else the message's own.
    if (isset($_POST['mod_js_calendar_date']) && is_string($_POST['mod_js_calendar_date'])) {
        $val = trim($_POST['mod_js_calendar_date']);
    } else {
        $val = isset($m['meta']['mod_js_calendar_date']) ? $m['meta']['mod_js_calendar_date'] : '';
    }
    if (!phorum_mod_js_calendar_date_valid($val)) $val = '';
    $cal = phorum_get_url(PHORUM_ADDON_URL, 'module=js_calendar');
    $help = str_replace('%calendar%',
        '<a href="' . htmlspecialchars($cal) . '">' . htmlspecialchars(phorum_mod_js_calendar_t('page_title')) . '</a>',
        htmlspecialchars(phorum_mod_js_calendar_t('announcement_date_help')));

    echo '<div class="mod-js-calendar-date" style="margin: 0.75rem 0;">'
       . '<label for="mod-js-calendar-date"><strong>' . htmlspecialchars(phorum_mod_js_calendar_t('announcement_date')) . '</strong> '
       . htmlspecialchars(phorum_mod_js_calendar_t('optional')) . '</label><br />'
       . '<input type="date" id="mod-js-calendar-date" name="mod_js_calendar_date" value="' . htmlspecialchars($val) . '" />'
       . '<br /><small>' . $help . '</small>'
       . '</div>';
}

/** check_post hook: a date that was typed must be valid. */
function phorum_mod_js_calendar_check_post($args) {
    list($message, $error) = $args;
    if (!$error && isset($_POST['mod_js_calendar_date']) && is_string($_POST['mod_js_calendar_date'])) {
        $d = trim($_POST['mod_js_calendar_date']);
        if ($d !== '' && !phorum_mod_js_calendar_date_valid($d)) {
            $error = phorum_mod_js_calendar_t('announcement_date_invalid');
        }
    }
    return array($message, $error);
}

/** Copies the form field into the metadata; no field = nothing changes. */
function phorum_mod_js_calendar_meta($message) {
    if (!isset($_POST['mod_js_calendar_date']) || !is_string($_POST['mod_js_calendar_date'])) return $message;
    $d = trim($_POST['mod_js_calendar_date']);
    if (!isset($message['meta']) || !is_array($message['meta'])) $message['meta'] = array();
    if (phorum_mod_js_calendar_date_valid($d)) {
        $message['meta']['mod_js_calendar_date'] = $d;
    } else {
        unset($message['meta']['mod_js_calendar_date']);
    }
    return $message;
}

/** before_post hook: a new announcement thread. */
function phorum_mod_js_calendar_before_post($message) {
    return phorum_mod_js_calendar_is_announcement($message) ? phorum_mod_js_calendar_meta($message) : $message;
}

/** before_edit hook: edit of an announcement's first message ($orig carries the ids). */
function phorum_mod_js_calendar_before_edit($dbmessage, $orig = null) {
    return phorum_mod_js_calendar_is_announcement($orig) ? phorum_mod_js_calendar_meta($dbmessage) : $dbmessage;
}

/** after_post hook: the announcement is published, its event is created. */
function phorum_mod_js_calendar_after_post($message) {
    if (phorum_mod_js_calendar_is_announcement($message) && !empty($message['message_id'])) {
        phorum_mod_js_calendar_sync((int)$message['message_id'], $message);
    }
    return $message;
}

/** after_edit hook: a date added, changed or cleared in the announcement. */
function phorum_mod_js_calendar_after_edit($dbmessage, $orig = null) {
    if (phorum_mod_js_calendar_is_announcement($orig) && !empty($orig['message_id']) && isset($_POST['mod_js_calendar_date'])) {
        phorum_mod_js_calendar_sync((int)$orig['message_id'], $dbmessage + $orig);
    }
    return $dbmessage;
}

/** Creates, updates or deletes the event of a thread (key: thread, unique). */
function phorum_mod_js_calendar_sync($thread, $message) {
    if (phorum_mod_js_calendar_db_init() !== TRUE) return;
    $table = $GLOBALS['PHORUM']['js_calendar_table'];
    $d = isset($message['meta']['mod_js_calendar_date']) ? $message['meta']['mod_js_calendar_date'] : '';
    if (!phorum_mod_js_calendar_date_valid($d)) {
        phorum_db_interact(DB_RETURN_RES, "DELETE FROM $table WHERE thread = " . (int)$thread);
        return;
    }
    $title = phorum_db_interact(DB_RETURN_QUOTED, mb_substr((string)$message['subject'], 0, 255));
    $qd = phorum_db_interact(DB_RETURN_QUOTED, $d);
    $user = isset($message['user_id']) ? (int)$message['user_id'] : 0;
    phorum_db_interact(DB_RETURN_RES,
        "INSERT INTO $table (user_id, thread, title, description, event_date)
         VALUES ($user, " . (int)$thread . ", '$title', '', '$qd')
         ON DUPLICATE KEY UPDATE title = VALUES(title), event_date = VALUES(event_date)");
}

/** format_fixup hook: the date at the top of a dated announcement's first message. */
function phorum_mod_js_calendar_format_fixup($messages) {
    if (!is_array($messages)) return $messages;
    foreach ($messages as $id => $m) {
        if (!is_array($m) || !isset($m['body'], $m['parent_id']) || (int)$m['parent_id'] !== 0) continue;
        $d = isset($m['meta']['mod_js_calendar_date']) ? $m['meta']['mod_js_calendar_date'] : '';
        if (!phorum_mod_js_calendar_date_valid($d)) continue;
        $url = phorum_get_url(PHORUM_ADDON_URL, 'module=js_calendar', 'date=' . $d);
        $messages[$id]['body'] = '<p class="mod-js-calendar-announcement"><strong>'
            . htmlspecialchars(str_replace('%date%', phorum_mod_js_calendar_date_long($d), phorum_mod_js_calendar_t('event_on')))
            . '</strong> — <a href="' . htmlspecialchars($url) . '">'
            . htmlspecialchars(phorum_mod_js_calendar_t('see_calendar')) . '</a></p>' . $m['body'];
    }
    return $messages;
}
