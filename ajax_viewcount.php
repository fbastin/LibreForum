<?php
define('phorum_page', 'ajax_viewcount');
include_once("./common.php");

$message_id = isset($_GET['message_id']) ? (int)$_GET['message_id'] : 0;
$thread_id  = isset($_GET['thread_id'])  ? (int)$_GET['thread_id']  : NULL;
if (empty($thread_id)) $thread_id = NULL;

if ($message_id > 0) {
    if ($PHORUM['count_views'] && (!isset($PHORUM['status']) || $PHORUM["status"]!=PHORUM_MASTER_STATUS_READ_ONLY)) {
        if (empty($PHORUM['count_views_per_thread'])) {
            $thread_id = NULL;
        }
        phorum_db_increment_viewcount($message_id, $thread_id);
    }
}

// Return a 1x1 transparent GIF
header("Content-Type: image/gif");
echo base64_decode("R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7");
?>
