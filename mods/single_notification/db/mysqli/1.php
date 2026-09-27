<?php
if (!defined("PHORUM")) return;

// Same character set as the core tables (see phorum_db_create_tables()).
$charset = empty($GLOBALS['PHORUM']['DBCONFIG']['charset'])
         ? ''
         : "DEFAULT CHARACTER SET {$GLOBALS['PHORUM']['DBCONFIG']['charset']}";

// email is 191 characters: in utf8mb4, 255 would exceed the key length
// limit of MyISAM (1000 bytes) and of older InnoDB row formats (767).
$sqlqueries[]= "
  CREATE TABLE {$GLOBALS['PHORUM']['single_notify_table']} (
	 `email` VARCHAR( 191 ) NOT NULL ,
	`forum_id` INT UNSIGNED NOT NULL ,
	`thread_id` INT UNSIGNED NOT NULL ,
	PRIMARY KEY ( `email` , `forum_id` , `thread_id` ) 
  ) $charset
";

?>
