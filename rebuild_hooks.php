<?php
define('PHORUM_ADMIN', 1);
define('PHORUM', 1);
// Fake server env to bypass mysql autodetect issues if any
$_SERVER['SERVER_SOFTWARE'] = 'Apache';
$_SERVER['HTTP_HOST'] = 'localhost';
include './common.php';
include './include/api/modules.php';
phorum_api_modules_save();
echo "Hooks rebuilt successfully.";
