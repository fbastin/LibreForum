<?php
if (!defined("PHORUM_ADMIN")) return;

// Forums whose new threads may carry an event date ("dated announcements").
if (count($_POST)) {
    $ids = array();
    if (isset($_POST['announcement_forums']) && is_array($_POST['announcement_forums'])) {
        foreach ($_POST['announcement_forums'] as $id => $on) {
            if ((int)$id > 0) $ids[] = (int)$id;
        }
    }
    $PHORUM['mod_js_calendar'] = isset($PHORUM['mod_js_calendar']) && is_array($PHORUM['mod_js_calendar'])
                               ? $PHORUM['mod_js_calendar'] : array();
    $PHORUM['mod_js_calendar']['announcement_forums'] = $ids;
    phorum_db_update_settings(array('mod_js_calendar' => $PHORUM['mod_js_calendar']));
    phorum_admin_okmsg('The settings were successfully saved');
}

$chosen = isset($PHORUM['mod_js_calendar']['announcement_forums'])
        ? array_map('intval', (array)$PHORUM['mod_js_calendar']['announcement_forums']) : array();

include_once './include/admin/PhorumInputForm.php';
$frm = new PhorumInputForm('', 'post', 'Save');
$frm->hidden('module', 'modsettings');
$frm->hidden('mod', 'js_calendar');
$frm->addbreak('Calendar: dated announcements');
$frm->addmessage('In the forums checked below, the form of a new thread offers an optional '
    . '"Event date" field. A dated thread gets its event in the calendar, linked to the '
    . 'thread, and shows the date at the top of its first message. The event is changed '
    . 'from the announcement, and disappears with it.');

$forums = phorum_db_get_forums();
$any = false;
foreach ($forums as $forum) {
    if (!empty($forum['folder_flag'])) continue;
    $any = true;
    $id = (int)$forum['forum_id'];
    $frm->addrow(htmlspecialchars(strip_tags($forum['name'])),
        $frm->checkbox("announcement_forums[$id]", '1', 'Announcements with an event date',
                       in_array($id, $chosen, true) ? 1 : 0));
}
if (!$any) $frm->addmessage('There is no forum yet.');

$frm->show();
?>
