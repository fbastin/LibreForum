<?php
    if (!defined("PHORUM_ADMIN")) return;

    if(empty($PHORUM["mod_pm_enhanced"]["count"])){
        $PHORUM["mod_pm_enhanced"] = array(
            "count" => 50
        );
    }

    // Save module settings to the database.
    if(count($_POST))
    {
        // Save settings array.
        $settings = array(
            "count" => (int)$_POST["count"]
        );
        phorum_db_update_settings(array(
            "mod_pm_enhanced" => $settings
        ));
        phorum_admin_okmsg("The module settings were successfully saved.");
    }

    include_once "./include/admin/PhorumInputForm.php";
    $frm = new PhorumInputForm ("", "post", "Save");
    $frm->hidden("module", "modsettings");
    $frm->hidden("mod", "pm_enhanced");

    $frm->addrow("Number of PMs to show per page", $frm->text_box('count', $PHORUM["mod_pm_enhanced"]["count"], 6));

    $frm->show();
?>
