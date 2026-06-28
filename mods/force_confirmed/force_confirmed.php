<?php
if (!defined("PHORUM")) return;

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
    
    if ($posts >= 5 && $days >= 7) {
        $user_array['CONFIRMED'] = 1;
    }
    
    // Admins are always confirmed
    if (!empty($user_array['admin'])) {
        $user_array['CONFIRMED'] = 1;
    }
}
?>
