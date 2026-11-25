<?php
// The search table moves from MyISAM to InnoDB. MyISAM's FULLTEXT index ignores
// words shorter than ft_min_word_len (4 by default), so a search for a
// three-letter term (a calibre, an acronym) found nothing; InnoDB's
// innodb_ft_min_token_size is 3 by default. New installs create the table
// in InnoDB directly (phorum_db_create_tables()).
$res = phorum_db_interact(DB_RETURN_ROW, "SHOW TABLE STATUS LIKE '{$PHORUM['search_table']}'");
if (!empty($res) && strtolower($res[1]) === 'myisam') {
    $upgrade_queries[] = "ALTER TABLE {$PHORUM['search_table']} ENGINE=InnoDB";
}
?>
