<?php
if(!defined("PHORUM")) return;

// title: Liste des usagers
// desc: Present a list of active users
// version: 1.1.0
// author: Jim Lehmann a.k.a. Ravenswood
// url: http://www.scriptmonkeys.us/
//
// hook: addon|phorum_mod_user_list_display

// MODIFIED!
// TODO:
//
// Settings page
// * Option of: Visible for logged-in users only
// * Optional Member number
//   ** Yes
//   ** No
//   ** Admins only
//
// Sort by Member number
//
// Sort by number of posts

// require_version: 5.?.?
//
// hook: some_hook|phorum_mod_foo_some_hook
// hook: some_other_hook|phorum_mod_foo_some_other_hook



// call with:                        v-- current forum number
// http://your.phorum.site/addon.php?1,module=user_list
// but don't do that directly, instead use:
// $url = phorum_get_url(PHORUM_ADDON_URL, "module=user_list");

// To add on more stuff:
//
//    http://your.phorum.site/addon.php?1,module=foo,action=bar
//
// Using this, your addon function can check $PHORUM["args"]["action"]
// to see what action to perform. Generating an URL for this example
// would look like this:
//
//   $url = phorum_get_url(PHORUM_ADDON_URL, "module=foo", "action=bar");
//




// Do we need these?
// include_once("./common.php");
// include_once("./include/format_functions.php");
// include_once('./include/forum_functions.php');

function phorum_mod_user_list_start_output()
{
    global $PHORUM;
    $PHORUM['DATA']['URL']['USER_LIST']['All']        = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list');
    $PHORUM['DATA']['URL']['USER_LIST']['NumberSort'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'sort=membernumber');
    $PHORUM['DATA']['URL']['USER_LIST']['number']     = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=number');
    $PHORUM['DATA']['URL']['USER_LIST']['A'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=A');
    $PHORUM['DATA']['URL']['USER_LIST']['B'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=B');
    $PHORUM['DATA']['URL']['USER_LIST']['C'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=C');
    $PHORUM['DATA']['URL']['USER_LIST']['D'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=D');
    $PHORUM['DATA']['URL']['USER_LIST']['E'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=E');
    $PHORUM['DATA']['URL']['USER_LIST']['F'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=F');
    $PHORUM['DATA']['URL']['USER_LIST']['G'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=G');
    $PHORUM['DATA']['URL']['USER_LIST']['H'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=H');
    $PHORUM['DATA']['URL']['USER_LIST']['I'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=I');
    $PHORUM['DATA']['URL']['USER_LIST']['J'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=J');
    $PHORUM['DATA']['URL']['USER_LIST']['K'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=K');
    $PHORUM['DATA']['URL']['USER_LIST']['L'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=L');
    $PHORUM['DATA']['URL']['USER_LIST']['M'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=M');
    $PHORUM['DATA']['URL']['USER_LIST']['N'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=N');
    $PHORUM['DATA']['URL']['USER_LIST']['O'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=O');
    $PHORUM['DATA']['URL']['USER_LIST']['P'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=P');
    $PHORUM['DATA']['URL']['USER_LIST']['Q'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=Q');
    $PHORUM['DATA']['URL']['USER_LIST']['R'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=R');
    $PHORUM['DATA']['URL']['USER_LIST']['S'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=S');
    $PHORUM['DATA']['URL']['USER_LIST']['T'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=T');
    $PHORUM['DATA']['URL']['USER_LIST']['U'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=U');
    $PHORUM['DATA']['URL']['USER_LIST']['V'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=V');
    $PHORUM['DATA']['URL']['USER_LIST']['W'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=W');
    $PHORUM['DATA']['URL']['USER_LIST']['X'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=X');
    $PHORUM['DATA']['URL']['USER_LIST']['Y'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=Y');
    $PHORUM['DATA']['URL']['USER_LIST']['Z'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_list', 'letter=Z');
}

function phorum_mod_user_list_load_one_person ($user_id) {

    global $PHORUM;

// The following is all copied (and modified) from profile.php

    $user = phorum_api_user_get($user_id, false);

    // This gets us everything except a link to the profile page
    // Take care of that now:
    $user["URL"]["PROFILE"] = phorum_get_url(PHORUM_PROFILE_URL, $user_id);

    // set any custom profile fields that are not present.
    if (!empty($PHORUM["PROFILE_FIELDS"])) {
        foreach($PHORUM["PROFILE_FIELDS"] as $id => $field) {
            if ($id === 'num_fields' || !empty($field['deleted'])) continue;
            if (!isset($user[$field['name']])) $user[$field['name']] = "";
        }
    }

    // No need to show the real name if it's the same as the display name.
    if ($user["real_name"] == $user["display_name"]) {
        unset($user["real_name"]);
    }

    // remove some stuff to save space (and also for security measures)
    unset($user['password']);
    unset($user['permissions']);
    unset($user['password_temp']);
    unset($user['sessid_lt']);
    unset($user['sessid_st']);
    unset($user['sessid_st_timeout']);
    unset($user['email']);
    unset($user['email_temp']);
    unset($user['hide_email']);
    unset($user['signature']);
    unset($user['threaded_list']);
    unset($user['threaded_read']);
    unset($user['last_active_forum']);
    unset($user['hide_activity']);
    unset($user['show_signature']);
    unset($user['email_notify']);
    unset($user['pm_email_notify']);
    unset($user['tz_offset']);
    unset($user['is_dst']);
    unset($user['user_language']);
    unset($user['user_template']);
    unset($user['moderator_data']);
    unset($user['moderation_email']);
    unset($user['settings_data']);
    unset($user['mod_tos']);
    unset($user['mod_user_avatar']);

    // getting FANCY here!
    // set up our own loop -- call it USERS

    $PHORUM['DATA']['USERS'][$user_id] = $user; // sets... I'm not sure... whatever's in $user

    $PHORUM['DATA']['USERS'][$user_id]["raw_date_added"] = $PHORUM['DATA']['USERS'][$user_id]["date_added"];
    $PHORUM['DATA']['USERS'][$user_id]["date_added"] = phorum_date( $PHORUM['short_date'], $PHORUM['DATA']['USERS'][$user_id]["date_added"]);

    $PHORUM['DATA']['USERS'][$user_id]["raw_date_last_active"]=$PHORUM['DATA']['USERS'][$user_id]["date_last_active"];
    $PHORUM['DATA']['USERS'][$user_id]["date_last_active"]=phorum_date( $PHORUM['short_date'], $PHORUM['DATA']['USERS'][$user_id]["date_last_active"]);

    $PHORUM['DATA']['USERS'][$user_id]["posts"] = number_format($PHORUM['DATA']['USERS'][$user_id]["posts"], 0, "", $PHORUM["thous_sep"]);

    $PHORUM['DATA']['USERS'][$user_id]["URL"]["PM"] = phorum_get_url(PHORUM_PM_URL, "page=send", "to_id=".urlencode($user["user_id"]));
    $PHORUM['DATA']['USERS'][$user_id]["URL"]["ADD_BUDDY"] = phorum_get_url(PHORUM_PM_URL, "page=buddies", "action=addbuddy", "addbuddy_id=".urlencode($user["user_id"]));
    $PHORUM['DATA']['USERS'][$user_id]["is_buddy"] = phorum_db_pm_is_buddy($user["user_id"]);

    $PHORUM['DATA']['USERS'][$user_id]["URL"]["SEARCH"] = phorum_get_url(PHORUM_SEARCH_URL, "author=".urlencode($PHORUM['DATA']['USERS'][$user_id]["user_id"]), "match_type=USER_ID", "match_dates=0", "match_threads=0");

    $PHORUM['DATA']['USERS'][$user_id]["username"] =
        htmlspecialchars($PHORUM['DATA']['USERS'][$user_id]["username"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

    if (isset($PHORUM['DATA']['USERS'][$user_id]["real_name"])) {
        $PHORUM['DATA']['USERS'][$user_id]["real_name"] =
            htmlspecialchars($PHORUM['DATA']['USERS'][$user_id]["real_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    }

    if (empty($PHORUM["custom_display_name"])) {
        $PHORUM['DATA']['USERS'][$user_id]["display_name"] =
            htmlspecialchars($PHORUM['DATA']['USERS'][$user_id]["display_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    }

} // end function phorum_mod_user_list_load_one_person

function phorum_mod_user_list_display () {

    global $PHORUM;

    require_once('./include/format_functions.php');
    require_once('./include/forum_functions.php');

    phorum_build_common_urls();

    $display_per_page = 25;

    // get passed variables -- letter, sort, dir, and page
    $letter = isset($PHORUM['args']['letter']) ? (string)$PHORUM['args']['letter'] : null;
    $sort   = isset($PHORUM['args']['sort'])   ? (string)$PHORUM['args']['sort']   : 'username';
    $dir    = isset($PHORUM['args']['dir'])    ? (string)$PHORUM['args']['dir']    : 'asc';
    $page   = isset($PHORUM['args']['page'])   ? (int)$PHORUM['args']['page']      : 1;

    if ($dir !== 'desc') $dir = 'asc';

    $valid_sorts = array(
        'user_id'          => 'user_id',
        'username'         => 'username',
        'posts'            => 'posts',
        'date_added'       => 'date_added',
        'date_last_active' => 'date_last_active'
    );

    // Legacy support
    if ($sort === 'membernumber') $sort = 'user_id';
    if (!isset($valid_sorts[$sort])) $sort = 'username';

    // valid settings for letter are 'number' or a single letter
    $singlequote = "'";
    if ( isset($letter) ):
        if ( $letter === 'number' ):
            $pattern = 'REGEXP ' . $singlequote . '^[^a-z]' . $singlequote;
        else:
            $pattern = 'LIKE ' . $singlequote . substr($letter, 0, 1) . '%' . $singlequote;
        endif;
    endif;

    // first, count how many there are total
    $select    = 'SELECT COUNT(user_id)';
    $from      = ' FROM ' . $PHORUM['user_table'];
    $where     = ' WHERE active = ' . PHORUM_USER_ACTIVE;
    if ( isset($letter) ):
        $where .= ' AND UPPER(username) ' . $pattern;
    endif;
    $sql_user_list_count = $select . $from . $where . ';';
    $user_list_count = (int)phorum_db_interact(DB_RETURN_VALUE, $sql_user_list_count);

    $total_pages = ceil($user_list_count / $display_per_page);
    if ($total_pages < 1) $total_pages = 1;
    if ($page > $total_pages) $page = $total_pages;
    if ($page < 1) $page = 1;

    $offset = $page - 1;
    $start = $offset * $display_per_page;

    // now get a list of everybody we want to display
    $select    = 'SELECT user_id, username';
    $from      = ' FROM ' . $PHORUM['user_table'];
    
    $order_field = $valid_sorts[$sort];
    if ($order_field === 'username') $order_field = 'UPPER(username)';
    
    $order_by  = " ORDER BY $order_field " . strtoupper($dir);
    $limit     = " LIMIT $start, $display_per_page";

    $sql_user_list = $select . $from . $where . $order_by . $limit . ';';
    $user_list_data = phorum_db_interact(DB_RETURN_ASSOCS, $sql_user_list);

    foreach ($user_list_data as $datum) {
      phorum_mod_user_list_load_one_person ($datum['user_id']);
    }

    // figure out paging
    $args = array('module=user_list', "page=%page_num%");
    if (isset($letter)) $args[] = "letter=$letter";
    if ($sort != 'username') $args[] = "sort=$sort";
    if ($dir != 'asc') $args[] = "dir=$dir";
    
    $user_list_url_template = call_user_func_array('phorum_get_url', array_merge(array(PHORUM_ADDON_URL), $args));

    $PHORUM["DATA"]["CURRENTPAGE"] = $page;
    $PHORUM["DATA"]["TOTALPAGES"] = $total_pages;
    $PHORUM["DATA"]["URL"]["PAGING_TEMPLATE"] = $user_list_url_template;

    // Generate URLs for the headers
    foreach ($valid_sorts as $key => $field) {
        $new_dir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
        $hargs = array('module=user_list', "sort=$key", "dir=$new_dir");
        if (isset($letter)) $hargs[] = "letter=$letter";
        
        $PHORUM['DATA']['URL']['USER_LIST']['SORT_BY_'.strtoupper($key)] = 
            call_user_func_array('phorum_get_url', array_merge(array(PHORUM_ADDON_URL), $hargs));
    }
    $PHORUM['DATA']['USER_LIST_SORT'] = $sort;
    $PHORUM['DATA']['USER_LIST_DIR'] = $dir;

    if ($total_pages <= 5) {
        $start_page = 1;
    } elseif ($total_pages - $page < 2) {
        $start_page = $total_pages - 4;
    } elseif ($total_pages > 5 and $page > 3) {
        $start_page = $page - 2;
    } else {
        $start_page = 1;
    }
    $end_page = $start_page + 4;
    if ($end_page > $total_pages):
      $end_page = $total_pages;
    endif;

    for ( $pagenumber = $start_page; $pagenumber <= $end_page; ++$pagenumber ):
        $PHORUM["DATA"]["PAGES"][] = array
        ("pageno" => $pagenumber,
            "url" => str_replace ( '%page_num%', $pagenumber, $user_list_url_template ),
        );
    endfor;

    if ($start_page > 1) {
        $PHORUM['DATA']['URL']['FIRSTPAGE'] = str_replace ( '%page_num%', '1',                  $user_list_url_template );
    }

    if ($end_page < $total_pages) {
        $PHORUM['DATA']['URL']['LASTPAGE'] =  str_replace ( '%page_num%', (string)$total_pages, $user_list_url_template );
    }

    if ($page > 1) {
        $prevpage = $page - 1;
        $PHORUM['DATA']['URL']['PREVPAGE'] =  str_replace ( '%page_num%', (string)$prevpage,    $user_list_url_template );
    }

    if ($page < $total_pages) {
        $nextpage = $page + 1;
        $PHORUM['DATA']['URL']['NEXTPAGE'] =  str_replace ( '%page_num%', (string)$nextpage,    $user_list_url_template );
    }

// after everything is set up, this is the general way to display stuff:

    // Override the default title and description.
    $PHORUM['DATA']['HEADING'] = 'Liste des membres';
    // $PHORUM['DATA']['HTML_DESCRIPTION'] = 'If we had a description, it would go here.';
    $PHORUM['DATA']['HTML_DESCRIPTION'] = '';
    // The next two are idiomatic -- just leave them as-is
    $PHORUM['DATA']['HTML_TITLE'] = htmlspecialchars(strip_tags($PHORUM['DATA']['HEADING']));
    $PHORUM['DATA']['BREADCRUMBS'][] = array( 'URL' => NULL, 'TEXT' => $PHORUM['DATA']['HEADING'] );

    // Now figure out tpl file to use based on template
    // This is a cheat to allow us to use only 5* template files for over 40 Scriptmonkeys templates

    // *Actually, 6...
    //  OK, This is complicated. The way Phorum handles templates is, it takes a tpl file and all tpl files it INCLUDEs and
    //   "compiles" them into executable PHP code, which is then cached. For the Scriptmonkeys templates, there are cases where
    //    there will be two templates with identical tpl files which INCLUDE different "paging.tpl" files -- for example, if you
    //     are using the Bad Fish template and you look at the user list page, Phorum would load the file "user_list_display_sm1.tpl"
    //      (as it was originally called), which would then INCLUDE paging.tpl. Since there is no paging.tpl file in the
    //       mods/user_list/templates/emerald directory, Phorum would get it from templates/badfish. So far, so good. The problem
    //        is that Phorum would then cache the results. Meaning that if the user switches to, for example, the Blue Lime
    //         template, and the views the user list page, this is what happens: Phorum will see that the file it is supposed to use
    //          is mods/user_list/templates/emerald/user_list_display_sm1.tpl, but instead of loading it and compiling it, it will grab
    //           the cached copy of it which includes templates/badfish/paging.tpl. Unfortunately, it was supposed to use
    //            templates/bluelime/paging.tpl, which is completely different. So, the user list itself will appear fine but the page
    //             numbers are all messed up. Until I find a more clever solution to this, "user_list_display_sm1.tpl" has been
    //              copied and renamed to "user_list_display_sm1a.tpl" and "user_list_display_sm1b.tpl", which are identical.

    switch ($PHORUM['DATA']['TEMPLATE']):
        default:
            $template_file = 'user_list_display';
            break;
        case 'bluelime':
        case 'emerald-alt':
        case 'furnace':
        case 'melon':
        case 'melon-cantaloupe':
        case 'melon-honeydew':
        case 'melon-watermelon':
        case 'purplexity':
        case 'romantic':
        case 'rosie':
        case 'royal':
        case 'simple-blue':
        case 'skin':
        case 'teal-purple':
            $template_file = 'user_list_display_sm1a';
            break;
        case 'alienwrench':
        case 'alienwrench-alt':
        case 'badfish':
        case 'coffeeshop':
        case 'fans':
        case 'food':
        case 'maidens':
        case 'pinkflower':
            $template_file = 'user_list_display_sm1b';
            break;
        case 'camo':
        case 'classicrr':
        case 'darkflower':
        case 'redbirds':
        case 'warcraft':
            $template_file = 'user_list_display_sm2';
            break;
        case 'darkteal':
        case 'finance':
        case 'fourthjuly':
        case 'grape':
        case 'greenfields':
        case 'mocha':
        case 'rainbow':
        case 'stargazer':
        case 'tealsky':
        case 'winter-wonderland':
            $template_file = 'user_list_display_sm3';
            break;
        case 'bitmapworld':
        case 'chess':
        case 'onyx':
        case 'retro':
        case 'salsa':
        case 'scriptmonkeys':
        case 'terminal':
        case 'violet':
            $template_file = 'user_list_display_sm4';
            break;
        case 'harvey':
            $template_file = 'user_list_display_sm5';
            break;
        case 'custom':                                    // To add your own template to this list, change 'custom' to the name of your template
            $template_file = 'user_list_display_custom';
            break;
    endswitch;

    // Display the result page.
    phorum_output('user_list::' . $template_file);

} // end function phorum_mod_user_list_display

?>
