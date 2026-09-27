<?php

if (!defined("PHORUM")) return;

// Same character set as the core tables (see phorum_db_create_tables()).
$charset = empty($PHORUM['DBCONFIG']['charset'])
         ? ''
         : "DEFAULT CHARACTER SET {$PHORUM['DBCONFIG']['charset']}";

$sqlqueries[]= "
    CREATE TABLE {$PHORUM["event_logging_table"]} (

        log_id     INT UNSIGNED PRIMARY KEY auto_increment,

        source     VARCHAR(32)  NOT NULL default 'unknown',
        category   TINYINT(4)   NOT NULL default 0,
        loglevel   TINYINT(4)   NOT NULL default 0,
        message    VARCHAR(255) NOT NULL default '<no error message specified>',
        details    TEXT             NULL,

        ip         VARCHAR(15)      NULL,
        hostname   VARCHAR(255)     NULL,
        user_id    INT UNSIGNED     NULL,
        datestamp  INT UNSIGNED NOT NULL default 0,

        vroot      INT UNSIGNED     NULL,
        forum_id   INT UNSIGNED     NULL,
        thread_id  INT UNSIGNED     NULL,
        message_id INT UNSIGNED     NULL,

        KEY source     (source),
        KEY category   (category),
        KEY loglevel   (loglevel),
        KEY datestamp  (datestamp),
        KEY user_id    (user_id),
        KEY forum      (vroot, forum_id, thread_id, message_id)
    ) $charset
";

?>
