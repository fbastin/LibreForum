<?php

require_once("./mods/single_notification/db.php");

function mod_single_notification_init() {
	
}

// What resets the hold: reading the thread, not visiting the forum.
// -----------------------------------------------------------------------------
// This hook used to clear ALL the holds of the forum being visited (db_del
// without a thread_id clause), from the topic list on. Opening the alert of one
// discussion thus re-armed the notification for every other thread of the same
// forum. On a forum where replies come days apart and the subscriber visits
// every day, the hold was always lifted before the next message arrived: the
// module held nothing back, and every message gave a mail, the opposite of
// what it promises.
//
// The hold is now lifted only by READING THE THREAD concerned. The module then
// keeps its promise: one alert per discussion until it has been read, instead
// of one alert per discussion and per visit.
//
// Accepted consequence: a thread whose alert is never opened gives no further
// alert, however many replies pile up in it.
// -----------------------------------------------------------------------------
function mod_single_notification_common() {
	global $PHORUM;
	if (! mod_single_notification_db_init()) return;

	if (empty($PHORUM['DATA']['LOGGEDIN'])) return;

	// Seule la lecture d'un fil leve la retenue. La liste des fils (list.php)
	// ou l'index n'y suffisent pas : voir un titre n'est pas lire la discussion.
	if (!defined('phorum_page') || phorum_page !== 'read') return;

	// Sur read.php, args[1] est l'identifiant du fil dans toutes les branches
	// (lecture simple, printview, gotonewpost, markthreadread, newer/older).
	$thread = isset($PHORUM['args'][1]) ? (int)$PHORUM['args'][1] : 0;
	if ($thread <= 0) return;

	mod_single_notification_db_del(
		$PHORUM['user']['email'], (int)$PHORUM['forum_id'], $thread
	);
}

function mod_single_notification_email_user_start($input) {
	global $PHORUM;
	
	list($addresses,$maildata)=$input;
	
	// act only on New message reply
	if(isset($input[1]['mailmessagetpl']) && $input[1]['mailmessagetpl'] == 'NewReplyMessage') {
		// check if entry for email,thread,forum exists,
		// if it exists, remove his email from the $addresses list
		$returns = mod_single_notification_db_check($addresses,$maildata['forum_id'],$maildata['thread_id']);
		foreach($returns as $edata) {
			$found = array_search($edata['email'],$addresses);
			if($found !== false) {
				unset($addresses[$found]);
			}
		}
	
		if(count($addresses)) {
			// store new entry for email, thread, forum
			mod_single_notification_db_add($addresses,$maildata['forum_id'],$maildata['thread_id']);
		}
	}
	
	return array($addresses,$maildata);
}


?>