<?php
$start = microtime(true);
define('phorum_page', 'read');
$_SERVER['HTTP_HOST'] = 'www.tireur.org';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_GET[0] = "46";
$_GET[1] = "226571";
$GLOBALS['PHORUM_CUSTOM_ARGS'] = "46,226571";
require_once('./common.php');
echo "Common loaded: " . (microtime(true) - $start) . "s\n";
// We can't easily profile read.php directly without modifying it.
