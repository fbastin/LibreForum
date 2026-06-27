<?php
$res = phorum_db_interact(DB_RETURN_ROW, "SHOW TABLE STATUS LIKE '{$PHORUM['search_table']}'");
if (!empty($res) && strtolower($res[1]) === 'myisam') {
    $upgrade_queries[] = "ALTER TABLE {$PHORUM['search_table']} ENGINE=InnoDB";
}
?>
