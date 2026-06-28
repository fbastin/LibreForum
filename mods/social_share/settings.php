<?php

if (!defined("PHORUM_ADMIN")) return;

// Default settings
if (!isset($PHORUM['social_share'])) {
    $PHORUM['social_share'] = array(
        'share_twitter'  => 1,
        'share_facebook' => 1,
        'share_whatsapp' => 1,
        'share_linkedin' => 1,
        'share_telegram' => 1,
        'share_pinterest'=> 0,
        'link_new_window'=> 1
    );
}

// If data is posted, then store the posted settings in the database.
if (count($_POST))
{
    $PHORUM['social_share']['share_twitter']  = empty($_POST['share_twitter']) ? 0 : 1;
    $PHORUM['social_share']['share_facebook'] = empty($_POST['share_facebook']) ? 0 : 1;
    $PHORUM['social_share']['share_whatsapp'] = empty($_POST['share_whatsapp']) ? 0 : 1;
    $PHORUM['social_share']['share_linkedin'] = empty($_POST['share_linkedin']) ? 0 : 1;
    $PHORUM['social_share']['share_telegram'] = empty($_POST['share_telegram']) ? 0 : 1;
    $PHORUM['social_share']['share_pinterest']= empty($_POST['share_pinterest']) ? 0 : 1;
    $PHORUM['social_share']['link_new_window']= empty($_POST['link_new_window']) ? 0 : 1;

    // Store the settings.
    phorum_db_update_settings(array("social_share" => $PHORUM["social_share"]));
    phorum_admin_okmsg('The settings were saved successfully');
}

include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", "Submit this form");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "social_share");

$frm->addbreak("Social Media Sharing module settings");

$frm->addrow(
    "Share to Twitter / X?",
    $frm->checkbox("share_twitter", "1", "Yes", $PHORUM['social_share']['share_twitter'])
);
$frm->addrow(
    "Share to Facebook?",
    $frm->checkbox("share_facebook", "1", "Yes", $PHORUM['social_share']['share_facebook'])
);
$frm->addrow(
    "Share to WhatsApp?",
    $frm->checkbox("share_whatsapp", "1", "Yes", $PHORUM['social_share']['share_whatsapp'])
);
$frm->addrow(
    "Share to LinkedIn?",
    $frm->checkbox("share_linkedin", "1", "Yes", $PHORUM['social_share']['share_linkedin'])
);
$frm->addrow(
    "Share to Telegram?",
    $frm->checkbox("share_telegram", "1", "Yes", $PHORUM['social_share']['share_telegram'])
);
$frm->addrow(
    "Share to Pinterest?",
    $frm->checkbox("share_pinterest", "1", "Yes", $PHORUM['social_share']['share_pinterest'])
);
$frm->addrow(
    "Open Links in New Window?",
    $frm->checkbox("link_new_window", "1", "Yes", $PHORUM['social_share']['link_new_window'])
);

$frm->show();
?>
