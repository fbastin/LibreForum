<?php
    if (!defined("PHORUM_ADMIN")) return;

    include("./mods/content_restrictions/defaults.php");

?>
	<h1>Content Restrictions Module</h1>
	<div style="">
		<p>
			Restrict users from posting certain content based on whether they are unregistered, newly registered or registered for long enough that we think we can trust them.
		</p>
		<p>
			Please note, for the purposes of this module, hyperlinks are considered to be clickable links or non clickable strings of the form www.somesite...
		</p>
	</div>
	<br style="clear:both" />
<?php
    // Save module settings to the database.
    if(count($_POST))
    {
        // Build new settings array.
        $settings = array();
        $settings["max_hyperlinks_unregistered"]      = (int) $_POST["max_hyperlinks_unregistered"];
        $settings["registered_days_before_trust"]      = (int) $_POST["registered_days_before_trust"];
        $settings["max_hyperlinks_registered_untrusted"]      = (int) $_POST["max_hyperlinks_registered_untrusted"];
        $settings["max_hyperlinks_registered_trusted"]      = (int) $_POST["max_hyperlinks_registered_trusted"];
        $settings["max_bytes_unregistered"]      = (int) $_POST["max_bytes_unregistered"];
        $settings["max_bytes_registered_untrusted"]      = (int) $_POST["max_bytes_registered_untrusted"];
        $settings["max_bytes_registered_trusted"]      = (int) $_POST["max_bytes_registered_trusted"];
        $settings["no_restriction_for_subscribers"]      = (int) $_POST["no_restriction_for_subscribers"];

        // Take care of applying sane settings.
        if ((int)$settings["max_hyperlinks_unregistered"] <= -1) $settings["max_hyperlinks_unregistered"]=-1;
        if ((int)$settings["registered_days_before_trust"] <= 0) $settings["registered_days_before_trust"]=0;
        if ((int)$settings["max_hyperlinks_registered_untrusted"] <= -1) $settings["max_hyperlinks_registered_untrusted"]=-1;
        if ((int)$settings["max_hyperlinks_registered_trusted"] <= -1) $settings["max_hyperlinks_registered_trusted"]=-1;
        if ((int)$settings["max_bytes_unregistered"] <= 0) $settings["max_bytes_unregistered"]=0;
        if ((int)$settings["max_bytes_registered_untrusted"] <= 0) $settings["max_bytes_registered_untrusted"]=0;
        if ((int)$settings["max_bytes_registered_trusted"] <= 0) $settings["max_bytes_registered_trusted"]=0;
        if ((int)$settings["no_restriction_for_subscribers"] != 1) $settings["no_restriction_for_subscribers"]=0;
        
        

        // Save settings array.
        $PHORUM["mod_content_restrictions"] = $settings;
        phorum_db_update_settings(array(
            "mod_content_restrictions" => $settings
        ));
        phorum_admin_okmsg("The module settings were successfully saved.");
    }

    include_once "./include/admin/PhorumInputForm.php";
    $frm = new PhorumInputForm ("", "post", "Save");
    $frm->hidden("module", "modsettings");
    $frm->hidden("mod", "content_restrictions");

    $frm->addbreak("Unregistered Users");
    $frm->addrow("Maximum hyperlinks allowed (0 = no restriction, -1 = no links allowed)", $frm->text_box('max_hyperlinks_unregistered', (int)$PHORUM["mod_content_restrictions"]["max_hyperlinks_unregistered"], 6) . ' links');
    $frm->addrow("Maximum length of message in bytes (0 = no restriction)", $frm->text_box('max_bytes_unregistered', (int)$PHORUM["mod_content_restrictions"]["max_bytes_unregistered"], 6) . ' bytes');

	$frm->addbreak("Newly Registered Users (Untrusted)");
	$row = $frm->addrow("Number of days to class users as \"new\"", $frm->text_box('registered_days_before_trust', (int)$PHORUM["mod_content_restrictions"]["registered_days_before_trust"], 6) . ' days');
	$frm->addhelp($row, "Number of days to class users as \"new\"", "eg: if you set this to 30, registered users will be classed as \"untrusted\" for 30 days.<br/>Set to \"0\" to ignore the concept of \"new\" users.");
    $frm->addrow("Maximum hyperlinks allowed", $frm->text_box('max_hyperlinks_registered_untrusted', (int)$PHORUM["mod_content_restrictions"]["max_hyperlinks_registered_untrusted"], 6) . ' links');
    $frm->addrow("Maximum length of message in bytes (0 = no restriction)", $frm->text_box('max_bytes_registered_untrusted', (int)$PHORUM["mod_content_restrictions"]["max_bytes_registered_untrusted"], 6) . ' bytes');
    
	$frm->addbreak("Registered Users (Trusted)");
    $frm->addrow("Maximum hyperlinks allowed (0 = no restriction, -1 = no links allowed)", $frm->text_box('max_hyperlinks_registered_trusted', (int)$PHORUM["mod_content_restrictions"]["max_hyperlinks_registered_trusted"], 6) . ' links');
    $frm->addrow("Maximum length of message in bytes (0 = no restriction)", $frm->text_box('max_bytes_registered_trusted', (int)$PHORUM["mod_content_restrictions"]["max_bytes_registered_trusted"], 6) . ' bytes');

	$frm->addbreak("Subscribers Module");
	$row = $frm->addrow('Remove all restrictions for Premium Subscribers',
		$frm->select_tag('no_restriction_for_subscribers',array(1 => "Yes",0 => "No"), (int)$PHORUM["mod_content_restrictions"]["no_restriction_for_subscribers"]));
	$frm->addhelp($row, "Remove all restrictions for Premium Subscribers", "As Premium Subscribers to your forum are presumably trusted, you can remove all restrictions from this module for them.
		\n<br/>\nFor more information about the Subscribers module please see <a href=\"http://www.phorum.org/phorum5/read.php?16,50056,page=1\" target=\"_blank\">here.</a>");
	
	/*    
    $row = $frm->addrow(
        "Deny any markup in signatures",
        $frm->checkbox(
            'deny_markup', 1, "",
            $PHORUM["mod_content_restrictions"]["deny_markup"]) .
            "Yes, unless user has been registered<br/>for at least " .
            $frm->text_box('markup_user_registered_days',
              $PHORUM["mod_content_restrictions"]["markup_user_registered_days"], 6
            ) .
            " days (0 = deny for all users)");
    $frm->addhelp($row, "Deny any markup in signatures",
        "If this feature is enabled, then the user will only be allowed to
         use plain text in the signature. Formatting the signature is not
         allowed. Formatting could be done by using for example BBcode and/or
         HTML (in case respectively the BBcode and/or HTML module is
         enabled).<br/>
         <br/>
         This check is done by first formatting the signature in the same
         way as LibreForum does when reading messages. After that, a check is
         done to see if there is HTML code in the signature. If it is, then
         the signature is denied."
    );
	*/
    $frm->show();

?>
