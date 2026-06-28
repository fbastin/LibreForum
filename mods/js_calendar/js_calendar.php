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

    $GLOBALS['PHORUM']['DATA']['CALENDAR'] = array(
        'is_logged_in' => $is_logged_in,
        'user_id' => $is_logged_in ? (int)$PHORUM['user']['user_id'] : 0,
        'is_admin' => !empty($PHORUM['user']['admin']) ? 1 : 0,
        'ajax_url' => phorum_get_url(PHORUM_ADDON_URL, 'module=js_calendar')
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
    $sql = "SELECT e.*, u.username FROM phorum_calendar_events e JOIN {$prefix}_users u ON e.user_id = u.user_id WHERE e.event_date = '$quoted_date' ORDER BY e.created_at ASC";
    $res = phorum_db_interact(DB_RETURN_RES, $sql);

    $results = array();
    if ($res) {
        while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
            $row['type'] = 'event';
            $results[] = $row;
        }
    }

    // 2. Fetch birthdays for this day/month (ignoring year)
    $day_month = substr($date, 5); // MM-DD
    $sql_bday = "SELECT u.user_id, u.username, b.data as birthday 
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
                'description' => "Date de naissance : " . $row['birthday'],
                'event_date'  => $date,
                'type'        => 'birthday'
            );
        }
    }

    return $results;
}

function phorum_mod_js_calendar_list_all_dates() {
    $PHORUM = $GLOBALS['PHORUM'];
    $prefix = $PHORUM['DBCONFIG']['table_prefix'];
    
    // Standard events dates
    $res = phorum_db_interact(DB_RETURN_RES, "SELECT DISTINCT event_date FROM phorum_calendar_events");
    $results = array();
    if ($res) {
        while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
            $results[] = $row['event_date'];
        }
    }

    // Birthdays dates (we only care about the current view typically, 
    // but for simplicity we can return birthdays that match users)
    // To avoid returning thousands of dates, we could just return those in a range,
    // but js_calendar list_all_dates is used for highlighting.
    // Since birthdays repeat every year, this logic is tricky for "list_all_dates".
    // For now, let's keep it to standard events and we'll handle birthday highlighting in JS if needed,
    // or just return birthdays for the current year.
    $year = date('Y');
    $sql_bday = "SELECT DISTINCT SUBSTR(b.data, 6) as md
                 FROM {$prefix}_user_custom_fields b
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

    return array_unique($results);
}

function phorum_mod_js_calendar_save_event() {
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['user']['user_id'])) return array('success' => false, 'error' => 'Non connecté');

    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

    if (empty($title) || empty($date)) return array('success' => false, 'error' => 'Champs requis manquants');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return array('success' => false, 'error' => 'Date invalide');

    $quoted_title = phorum_db_interact(DB_RETURN_QUOTED, $title);
    $quoted_desc = phorum_db_interact(DB_RETURN_QUOTED, $desc);
    $quoted_date = phorum_db_interact(DB_RETURN_QUOTED, $date);
    $user_id = (int)$PHORUM['user']['user_id'];

    if ($event_id > 0) {
        $res = phorum_db_interact(DB_RETURN_RES, "SELECT user_id FROM phorum_calendar_events WHERE event_id = $event_id");
        $row = $res ? phorum_db_fetch_row($res, DB_RETURN_ASSOC) : null;
        if (!$row) return array('success' => false, 'error' => 'Événement introuvable');
        if ($row['user_id'] != $user_id && empty($PHORUM['user']['admin'])) {
            return array('success' => false, 'error' => 'Accès interdit');
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

    $event_id = isset($_REQUEST['event_id']) ? (int)$_REQUEST['event_id'] : (isset($PHORUM['args']['event_id']) ? (int)$PHORUM['args']['event_id'] : 0);
    
    $res = phorum_db_interact(DB_RETURN_RES, "SELECT user_id FROM phorum_calendar_events WHERE event_id = $event_id");
    if (!$res) return array('success' => false, 'error' => 'Event introuvable');
    $row = phorum_db_fetch_row($res, DB_RETURN_ASSOC);

    if (!$row || ($row['user_id'] != $PHORUM['user']['user_id'] && empty($PHORUM['user']['admin']))) {
        return array('success' => false, 'error' => 'Accès interdit');
    }

    phorum_db_interact(DB_RETURN_RES, "DELETE FROM phorum_calendar_events WHERE event_id = $event_id");
    return array('success' => true);
}

function phorum_mod_js_calendar_export_ical() {
    $PHORUM = $GLOBALS['PHORUM'];
    $prefix = $PHORUM['DBCONFIG']['table_prefix'];

    // 1. Fetch events
    $sql = "SELECT e.*, u.username FROM phorum_calendar_events e JOIN {$prefix}_users u ON e.user_id = u.user_id ORDER BY e.event_date ASC";
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
            if (!empty($row['description'])) {
                $ical .= "DESCRIPTION:" . phorum_mod_js_calendar_ical_escape($row['description'] . " (Par " . $row['username'] . ")") . "\r\n";
            } else {
                $ical .= "DESCRIPTION:Par " . phorum_mod_js_calendar_ical_escape($row['username']) . "\r\n";
            }
            $ical .= "END:VEVENT\r\n";
        }
    }

    // 3. Fetch birthdays (as recurring events)
    $sql_bday = "SELECT u.user_id, u.username, b.data as birthday 
                 FROM {$prefix}_users u 
                 JOIN {$prefix}_user_custom_fields b ON u.user_id = b.user_id AND b.type = 22
                 LEFT JOIN {$prefix}_user_custom_fields p ON u.user_id = p.user_id AND p.type = 23
                 WHERE (p.data IS NULL OR p.data = '0')
                 AND u.active = 1";
    $res_bday = phorum_db_interact(DB_RETURN_RES, $sql_bday);
    if ($res_bday) {
        while ($row = phorum_db_fetch_row($res_bday, DB_RETURN_ASSOC)) {
            // Birthday is usually YYYY-MM-DD in the custom field
            // We want it to start on that day and repeat yearly
            $bdt = str_replace('-', '', $row['birthday']);
            if (strlen($bdt) === 8) {
                $ical .= "BEGIN:VEVENT\r\n";
                $ical .= "UID:bday_" . $row['user_id'] . "@tireur.org\r\n";
                $ical .= "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
                $ical .= "DTSTART;VALUE=DATE:" . $bdt . "\r\n";
                $ical .= "RRULE:FREQ=YEARLY\r\n";
                $ical .= "SUMMARY:Anniversaire de " . phorum_mod_js_calendar_ical_escape($row['username']) . "\r\n";
                $ical .= "END:VEVENT\r\n";
            }
        }
    }

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
