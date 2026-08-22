<?php

require_once("./mods/single_notification/db.php");

function mod_single_notification_init() {
	
}

// MODIFICATION LOCALE (tireur.org) --------------------------------------------
//
// Version d'origine : toute page d'un forum consultee en etant connecte
// effaçait TOUTES les retenues de ce forum (db_del sans clause thread_id).
// Consequence : ouvrir l'alerte d'un fil rearmait la notification pour tous
// les autres fils du meme forum. Sur un forum ou les reponses sont espacees
// de plusieurs jours et ou l'abonne passe tous les jours, la retenue etait
// systematiquement levee avant l'arrivee du message suivant : le module ne
// retenait plus rien et chaque message donnait un courriel.
//
// Version locale : la retenue n'est levee que par la LECTURE DU FIL concerne.
// Le module tient alors sa promesse — une alerte par discussion tant qu'elle
// n'a pas ete lue — au lieu d'une alerte par discussion et par visite.
//
// Corollaire assume : un fil dont l'alerte n'est jamais ouverte ne redonnera
// plus d'alerte, quel que soit le nombre de reponses qui s'y accumulent.
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