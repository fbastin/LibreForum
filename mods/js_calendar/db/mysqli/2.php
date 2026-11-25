<?php
if (!defined("PHORUM")) return;

// Dated announcements: an event created from a forum thread keeps that
// thread's id, one event per thread at most. Skipped when the column is
// already there (a table created by hand from a forum that had it).
$cols = phorum_db_interact(DB_RETURN_ROWS,
    "SHOW COLUMNS FROM {$GLOBALS['PHORUM']['js_calendar_table']} LIKE 'thread'");
if (empty($cols)) {
    $sqlqueries[] = "
      ALTER TABLE {$GLOBALS['PHORUM']['js_calendar_table']}
        ADD COLUMN thread INT UNSIGNED NULL DEFAULT NULL AFTER user_id,
        ADD UNIQUE KEY thread (thread)
    ";
}

?>
