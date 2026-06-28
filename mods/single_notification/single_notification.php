<?php

require_once("./mods/single_notification/db.php");

function mod_single_notification_init() {
	
}

function mod_single_notification_common() {
	global $PHORUM;
	if (! mod_single_notification_db_init()) return;
	
	if($PHORUM['DATA']['LOGGEDIN']) {
		$forum = $PHORUM['forum_id'];
		if($PHORUM['forum_id'] != $PHORUM['vroot']) {
			mod_single_notification_db_del($PHORUM['user']['email'],$forum);
		}
	}
	
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