<?php
if(!defined("PHORUM")) return;

// TODO:
//  * Store title and other information per page
//  * Display output in an optional frame so it looks nice
//  * Add javascript to warn people if they try to leave an edit screen without saving.
//  * What if user selects "new" file which already exists?
//  * What happens if filename in list for some reason doesn't exist?

// Do we need these?
// include_once("./common.php");
// include_once("./include/format_functions.php");
// include_once('./include/forum_functions.php');

function phorum_mod_static_pages_start_output()
{
    global $PHORUM;

// these need to match settings.php
$template = 'emerald';      // emerald is the default, so use this no matter what the actual template is
$template_path = './mods/static_pages/templates/' . $template;     // why does it start with a period-slash? Why not just 'mods...' ?

// don't create links for these files:
$disallowed = array('.', '..', 'frame_start.tpl', 'frame_end.tpl');

// gather a list of the files (except those listed in $disallowed)
$a_files = array();
$d = dir( $template_path );                     // "dir" doesn't work with wildcards - it always takes everything
while ( false !== ( $entry = $d->read() ) ) {
    if ( substr($entry, -4, 4) === '.tpl' ) {
        if ( ! in_array ($entry, $disallowed) ) {
            $a_files[] = substr($entry, 0, -4);    // chop the '.tpl' off the end
        }
    }
}

foreach ($a_files as $x):
    $PHORUM['DATA']['URL']['static_pages'][$x] = phorum_get_url(PHORUM_ADDON_URL, 'module=static_pages', 'page=' . $x);
endforeach;

//    $PHORUM['DATA']['URL']['static_pages']['sample_page'] = phorum_get_url(PHORUM_ADDON_URL, 'module=static_pages', 'page=sample_page');
// {URL->static_pages->sample_page}
}

function phorum_mod_static_pages_display () {

    global $PHORUM;

    require_once('./include/format_functions.php');
    require_once('./include/forum_functions.php');     // Someday, check to see if these NEED to be included.

    phorum_build_common_urls();                        // You have to call this yourself. It doesn't get called automatically.

    // get passed variable -- page
    if(isset($PHORUM['args']['page'])){
        $page = (string)$PHORUM['args']['page'];
    } else {
        // error - no page name passed
    }
    // does page name match an existing page?

// after everything is set up, this is the general way to display stuff:

    // Override the default title and description.
    $PHORUM['DATA']['HEADING'] = '';
    // $PHORUM['DATA']['HTML_DESCRIPTION'] = 'If we had a description, it would go here.';
    $PHORUM['DATA']['HTML_DESCRIPTION'] = '';
    // The next two are idiomatic -- just leave them as-is
    $PHORUM['DATA']['HTML_TITLE'] = htmlspecialchars(strip_tags($PHORUM['DATA']['HEADING']));
    $PHORUM['DATA']['BREADCRUMBS'][] = array( 'URL' => NULL, 'TEXT' => $PHORUM['DATA']['HEADING'] );

    // Display the requested page, inside a frame
//     phorum_output('static_pages::frame_start');
    phorum_output('static_pages::' . $page);
//     phorum_output('static_pages::frame_end');

} // end function phorum_mod_static_pages_display

// The pages are stored as template files, meaning they will be able to contain PHP code, and have access to LibreForum variables and LibreForum template syntax.

// require_version: 5.?.?

?>
