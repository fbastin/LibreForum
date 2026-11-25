<?php
if (!defined("PHORUM")) return;

/**
 * Creates the custom profile fields this module handles, when they do not
 * exist yet (idempotent). Called on every request through the "common" hook;
 * once the fields exist, this is only an in-memory check.
 *
 *   user_birthday          YYYY-MM-DD, validated below; read by js_calendar
 *   user_birthday_privacy  1 = keep the birthday private
 *
 * The module used to create only "real_name_privacy", a flag that nothing in
 * this distribution reads, and never the two birthday fields it validates: on
 * a new forum the birthday was never stored.
 */
function phorum_mod_custom_profile_fields_handler_ensure_fields()
{
    if (!function_exists('phorum_api_custom_profile_field_byname')) {
        @include_once('./include/api/custom_profile_fields.php');
    }
    if (!function_exists('phorum_api_custom_profile_field_byname')) return;

    $fields = array('user_birthday' => 10, 'user_birthday_privacy' => 1);
    foreach ($fields as $name => $length) {
        if (!phorum_api_custom_profile_field_byname($name)) {
            phorum_api_custom_profile_field_configure(array(
                'id'            => NULL,
                'name'          => $name,
                'length'        => $length,
                'html_disabled' => TRUE,
                'show_in_admin' => FALSE,
            ));
        }
    }
}

// "common" hook: creates the fields early, before anything reads them.
function phorum_mod_custom_profile_fields_handler_common()
{
    phorum_mod_custom_profile_fields_handler_ensure_fields();
}

/** A string of the language file, with an English fallback. */
function phorum_mod_custom_profile_fields_handler_lang($key, $fallback)
{
    return isset($GLOBALS['PHORUM']['DATA']['LANG'][$key])
         ? $GLOBALS['PHORUM']['DATA']['LANG'][$key] : $fallback;
}

function phorum_mod_custom_profile_fields_handler_save($userdata)
{
    // Only the "user" panel (my profile) carries these fields.
    if (isset($userdata['panel']) && $userdata['panel'] != "user") {
        return $userdata;
    }

    // The fields must exist before they are saved, whether or not the
    // "common" hook is active yet (it may need the module settings saved).
    phorum_mod_custom_profile_fields_handler_ensure_fields();

    // 1. Date of birth
    if (isset($_POST['user_birthday']) && trim($_POST['user_birthday']) !== "") {
        $birthday = trim($_POST['user_birthday']);

        // Format
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $birthday, $matches)) {
            $userdata['error'] = phorum_mod_custom_profile_fields_handler_lang(
                'BirthdayFormatError', 'The date of birth must be written YYYY-MM-DD (e.g. 1980-05-15).');
            return $userdata;
        }

        $y = (int)$matches[1];
        $m = (int)$matches[2];
        $d = (int)$matches[3];

        // A real date (no 31 February)
        if (!checkdate($m, $d, $y)) {
            $userdata['error'] = phorum_mod_custom_profile_fields_handler_lang(
                'BirthdayInvalid', 'The date of birth is not a valid date.');
            return $userdata;
        }

        // Not in the future
        if (mktime(0, 0, 0, $m, $d, $y) > time()) {
            $userdata['error'] = phorum_mod_custom_profile_fields_handler_lang(
                'BirthdayInFuture', 'The date of birth cannot be in the future.');
            return $userdata;
        }

        $userdata['user_birthday'] = $birthday;
    } else {
        $userdata['user_birthday'] = "";
    }

    // 2. "Keep private" box: an unchecked box is not sent in $_POST.
    $userdata['user_birthday_privacy'] = isset($_POST['user_birthday_privacy']) ? 1 : 0;

    return $userdata;
}

?>
