<?php
if (!defined("PHORUM")) return;

// Same character set as the core tables (see phorum_db_create_tables()).
$charset = empty($GLOBALS['PHORUM']['DBCONFIG']['charset'])
         ? ''
         : "DEFAULT CHARACTER SET {$GLOBALS['PHORUM']['DBCONFIG']['charset']}";

// IF NOT EXISTS: forums that ran this module before it created its own table
// had to create it by hand, under this same name and with these columns.
$sqlqueries[] = "
  CREATE TABLE IF NOT EXISTS {$GLOBALS['PHORUM']['js_calendar_table']} (
    event_id    INT NOT NULL AUTO_INCREMENT,
    user_id     INT NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    event_date  DATE NOT NULL,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id),
    KEY event_date (event_date)
  ) $charset
";

?>
