<?php

if (!defined('PHORUM')) return;

# The database scheme version, used to install and upgrade the module's table.
define('JS_CALENDAR_DB_VERSION', 1);

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
        if (count($sqlqueries) == 0) {
            return "no SQL query in $sqlfile";
        }
        $err = phorum_db_run_queries($sqlqueries);
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
        'strings_json' => ''
    );
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
                   'unknown', 'network_error', 'by', 'add_event', 'edit_event', 'delete') as $key) {
        $js[$key] = $lang[$key];
    }
    $data['strings_json'] = json_encode($js,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    return $data;
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
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return array('error' => phorum_mod_js_calendar_t('invalid_date'));

    $prefix = $PHORUM['DBCONFIG']['table_prefix'];
    $quoted_date = phorum_db_interact(DB_RETURN_QUOTED, $date);

    // 1. Fetch standard events
    $sql = "SELECT e.*, u.username FROM {$PHORUM['js_calendar_table']} e JOIN {$prefix}_users u ON e.user_id = u.user_id WHERE e.event_date = '$quoted_date' ORDER BY e.created_at ASC";
    $res = phorum_db_interact(DB_RETURN_RES, $sql);

    $results = array();
    if ($res) {
        while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
            $row['type'] = 'event';
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
    $res = phorum_db_interact(DB_RETURN_RES, "SELECT DISTINCT event_date FROM {$PHORUM['js_calendar_table']}");
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
        $res = phorum_db_interact(DB_RETURN_RES, "SELECT user_id FROM {$PHORUM['js_calendar_table']} WHERE event_id = $event_id");
        $row = $res ? phorum_db_fetch_row($res, DB_RETURN_ASSOC) : null;
        if (!$row) return array('success' => false, 'error' => phorum_mod_js_calendar_t('not_found'));
        if ($row['user_id'] != $user_id && empty($PHORUM['user']['admin'])) {
            return array('success' => false, 'error' => phorum_mod_js_calendar_t('forbidden'));
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
    
    $res = phorum_db_interact(DB_RETURN_RES, "SELECT user_id FROM {$PHORUM['js_calendar_table']} WHERE event_id = $event_id");
    if (!$res) return array('success' => false, 'error' => phorum_mod_js_calendar_t('not_found'));
    $row = phorum_db_fetch_row($res, DB_RETURN_ASSOC);

    if (!$row || ($row['user_id'] != $PHORUM['user']['user_id'] && empty($PHORUM['user']['admin']))) {
        return array('success' => false, 'error' => phorum_mod_js_calendar_t('forbidden'));
    }

    phorum_db_interact(DB_RETURN_RES, "DELETE FROM {$PHORUM['js_calendar_table']} WHERE event_id = $event_id");
    return array('success' => true);
}

function phorum_mod_js_calendar_export_ical() {
    $PHORUM = $GLOBALS['PHORUM'];
    $prefix = $PHORUM['DBCONFIG']['table_prefix'];

    // 1. Fetch events
    $sql = "SELECT e.*, u.username FROM {$PHORUM['js_calendar_table']} e JOIN {$prefix}_users u ON e.user_id = u.user_id ORDER BY e.event_date ASC";
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
