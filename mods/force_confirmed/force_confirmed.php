<?php
if (!defined("PHORUM")) return;

// A member is confirmed after this many messages and days of registration.
define('FORCE_CONFIRMED_POSTS', 5);
define('FORCE_CONFIRMED_DAYS', 7);

function phorum_mod_force_confirmed_common()
{
    global $PHORUM;

    // 1. Handle the logged-in user
    if (!empty($PHORUM['user']['user_id'])) {
        phorum_mod_force_confirmed_apply($PHORUM['user']);
        
        // Also ensure it's available in DATA->PROFILE for the control center if it's the current user
        if (isset($PHORUM['DATA']['PROFILE']) && 
            isset($PHORUM['DATA']['PROFILE']['user_id']) && 
            $PHORUM['DATA']['PROFILE']['user_id'] == $PHORUM['user']['user_id']) {
            if (!empty($PHORUM['user']['CONFIRMED'])) {
                $PHORUM['DATA']['PROFILE']['CONFIRMED'] = 1;
            }
        }
    }

    // 2. Handle admin viewing another user's profile in the Control Center
    // In control.php, the user being viewed is loaded into $PHORUM['DATA']['PROFILE']
    if (defined('PHORUM_CONTROL_CENTER') && 
        isset($PHORUM['DATA']['PROFILE']) && 
        isset($PHORUM['DATA']['PROFILE']['user_id'])) {
        
        phorum_mod_force_confirmed_apply($PHORUM['DATA']['PROFILE']);
    }
}

/**
 * Apply the confirmation logic to a user data array (passed by reference)
 */
function phorum_mod_force_confirmed_apply(&$user_array)
{
    // Check post count
    $posts = isset($user_array['posts']) ? (int)$user_array['posts'] : 0;
    
    // Check registration days
    $date_added = isset($user_array['date_added']) ? (int)$user_array['date_added'] : 0;
    $days = $date_added > 0 ? (time() - $date_added) / 86400 : 0;
    
    if ($posts >= FORCE_CONFIRMED_POSTS && $days >= FORCE_CONFIRMED_DAYS) {
        $user_array['CONFIRMED'] = 1;
    }
    
    // Admins are always confirmed
    if (!empty($user_array['admin'])) {
        $user_array['CONFIRMED'] = 1;
    }
}
/**
 * Closes the groups panel of the control center to members not yet confirmed,
 * with a message naming the thresholds. The panel used to test PROFILE->CONFIRMED
 * itself, so that without this module nobody could open it, and the message was
 * written in French in the template. The language is loaded after
 * common_post_user, hence this second hook.
 */
function phorum_mod_force_confirmed_start_output()
{
    global $PHORUM;
    if (phorum_page !== 'control' || empty($PHORUM['user']['user_id']) ||
        !empty($PHORUM['user']['CONFIRMED'])) {
        return;
    }
    $text = isset($PHORUM['DATA']['LANG']['GroupsLockedUntilConfirmed'])
          ? $PHORUM['DATA']['LANG']['GroupsLockedUntilConfirmed']
          : 'You must have been registered for at least %days% days and have posted at least %posts% messages to access the groups.';
    $PHORUM['DATA']['GROUPS_LOCKED'] = str_replace(
        array('%days%', '%posts%'), array(FORCE_CONFIRMED_DAYS, FORCE_CONFIRMED_POSTS), $text);
}
?>
