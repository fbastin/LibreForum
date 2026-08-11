<?php
// Make sure that this script is loaded from the admin interface.
if(!defined("PHORUM_ADMIN")) return;

// TODO:
//  * Store title and other information per page
//  * Display output in an optional frame so it looks nice
//  * Add javascript to warn people if they try to leave an edit screen without saving.
//  * What if user selects "new" file which already exists?
//  * What happens if filename in list for some reason doesn't exist?

// Giant switch to fake goto
switch (1):
default:

/* settings */

$template = 'emerald';      // emerald is the default, so use this no matter what the actual template is
$template_path = './mods/static_pages/templates/' . $template;     // why does it start with a period-slash? Why not just 'mods...' ?

// don't edit these files:
$disallowed = array('.', '..', 'frame_start.tpl', 'frame_end.tpl');

/* initialize */

$curr_file = '';
$file_contents = '';
$file_contents_set = false;
$new_file_flag = false;
$edit_this_file = 'maybe';   // other possible values are 'yes' and 'no'

$delete_file = false;

/* do it */

// first gather a list of the files
// get all of them except those listed in $disallowed
$a_files = array();
$d = dir( $template_path );                     // "dir" doesn't work with wildcards - it always takes everything
while ( false !== ( $entry = $d->read() ) ) {
    if ( substr($entry, -4, 4) === '.tpl' ) {
        if ( ! in_array ($entry, $disallowed) ) {
            $a_files[] = $entry;
        }
    }
}
sort($a_files);

// if we're just coming here from the LibreForum Module Admin screen, don't load-in file to edit ( but do default to it in list )
if(count($_POST) == 0) {
    $edit_this_file = 'no';
} // end if

// if user changes file in pull-down but doesn't click on "Select File", this is how we will know what file we should be working with
if ( isset( $PHORUM['mod_static_pages']['curr_file'] ) ):
    $curr_file = (string)$PHORUM['mod_static_pages']['curr_file'];
else:
    $curr_file = '';
    $edit_this_file = 'no';
endif;

// decide what to do based on button user has clicked on
if(count($_POST)) {
    if ( isset($_POST['select_file']) ) {
        $curr_file = $_POST['file'];
        $PHORUM['mod_static_pages']['curr_file'] = $curr_file;    // if user changes file in pull-down but doesn't click on "Select File", this is how we will know what file we should be working with
        $edit_this_file = 'yes';
    } elseif ( isset($_POST['new_file']) ) {
        $curr_file = phorum_mod_static_pages_build_filename($_POST['new_filename']);
        $PHORUM['mod_static_pages']['curr_file'] = $curr_file;
        // wait, what if they gave us the name of an exisitng file?
        $found_key = array_search($curr_file, $a_files);
        if ($found_key === false):
            // not found -- good
            $new_file_flag = true;
            $edit_this_file = 'yes';
            $file_contents = '';
            $file_contents_set = true;
            // add to pull-down list
            $a_files[] = $curr_file;
            sort($a_files);
            $zerdly_one = true;
        else:
            // file exists -- warning message, but continue anyway
            phorum_admin_error('Specified file ' . $curr_file . ' already exists. Opened for editing.');
            $edit_this_file = 'yes';
            $file_contents_set = false;
        endif;

    } elseif ( isset($_POST['delete_file']) ) {
        // force special mode
        $delete_file = true;

        // we don't NEED to set any of these, but...
        $file_contents = '';
        $file_contents_set = true;
        $new_file_flag = false;
        $edit_this_file = 'no';

    } elseif ( isset($_POST['delete_file_yes']) ) {
        $curr_file = (string)$PHORUM['mod_static_pages']['curr_file'];
        // they said yes -- delete it
        $success = unlink( $template_path.'/'.$curr_file );
        if ($success === false) {
            phorum_admin_error('An error occured. The file was not deleted.');
            // Now what?
            // I think... continue editing file as if nothing happened
            // Which we accomplish by NOT altering any variables from whatever values they currently have
        } else {
            phorum_admin_okmsg('Successfully deleted ' . $curr_file);
            // remove from $a_files list
            $found_key = array_search($curr_file, $a_files);
            if ($found_key !== false):
                unset($a_files[$found_key]);
            endif;

            // re-set to state to: same as if we just came from admin screen
            $curr_file = '';
            $file_contents = '';
            $file_contents_set = true;
            $new_file_flag = false;
            $edit_this_file = 'no';
        }

    } elseif ( isset($_POST['save_file']) or ( $_POST['default_save'] == 'yes' ) ) {
        // Another fake goto switch
        switch (1):
        default:
            $file_contents = $_POST['file_contents'];
            // amazingly, we don't need to perform any substitutions -- the file contents as returned in $_POST are exactly what we need to save
            $file_contents_set = true;                  // this way, if file fails to save, text will still be available on-screen for user to copy and paste
            $edit_this_file = 'yes';
            if ( $curr_file == '' ):
                phorum_admin_error('Attempted to save file with no filename. The file was not saved.');
                // goto save_error
                break;
            endif;
            // now save file
            $success = file_put_contents( $template_path.'/'.$curr_file, $file_contents );
            if ($success === false) {
                phorum_admin_error('An error occured. The file was not saved.');
            } else {
                phorum_admin_okmsg('Successfully saved ' . $curr_file . ' at ' . date('Y-m-d H:i:s'));
                // don't // force re-load from saved file
                // $file_contents_set = false;
            }
            // handle special case zerdly_one
            if ($_POST['zerdly_one']):
                $a_files[] = $curr_file;
                sort($a_files);
                $zerdly_one = false; // the crisis has passed, turn off zerdly one flag
            endif;
        //label save_error:
        endswitch;
    } // end big multi-if
} // end if(count($_POST))

// $curr_file = '';
// $file_contents = '';
// $file_contents_set = false;
// $new_file_flag = false;
// $edit_this_file = 'maybe';   // other possible values are 'yes' and 'no'

// if we haven't loaded it by now, do so
if (! $file_contents_set):
    $file_contents = file_get_contents( $template_path.'/'.$curr_file );
    $file_contents_set = true;
endif;

// we really need to get it hammered out by now
if ($edit_this_file == 'maybe') {
    $edit_this_file = 'yes';      // yes, not saying no is saying yes
}


require_once('./include/admin/PhorumInputForm.php');

if ($edit_this_file == 'yes'):
    $button_text = 'Save File and Continue Editing';
    $default_save = 'yes';
else:
    $button_text = '';
    $default_save = 'no';
endif;

$frm = new PhorumInputForm ('', 'post', $button_text);
$frm->hidden('module', 'modsettings');                      // This is, apparently, necessary
$frm->hidden('mod', 'static_pages');

$frm->hidden('default_save', $default_save);
if ( $zerdly_one ):
    $frm->hidden('zerdly_one', '1');
endif;

$frm->addbreak('Static Pages');

// $frm->addmessage( '$_POST = ' . nl2br(var_export($_POST, true)) ); // debugg //
// $frm->addmessage( 'count($_POST) = ' . nl2br(var_export(count($_POST), true)) ); // debugg //
// $frm->addmessage( '$PHORUM[\'mod_static_pages\'] = ' . nl2br(var_export($PHORUM['mod_static_pages'], true)) ); // debugg //
// $frm->addmessage( '$curr_file = ' . nl2br(var_export($curr_file, true)) ); // debugg //
// $frm->addmessage( '$file_contents = <pre>' . $file_contents . '</pre>' ); // debugg //
// $frm->addmessage( '$file_contents_set = ' . nl2br(var_export($file_contents_set, true)) ); // debugg //
// $frm->addmessage( '$new_file_flag = ' . nl2br(var_export($new_file_flag, true)) ); // debugg //
// $frm->addmessage( '$edit_this_file = ' . nl2br(var_export($edit_this_file, true)) ); // debugg //

// LibreForum version 5.2.10 and earlier, this function did not exist
if ( function_exists('phorum_admin_build_url')):
    // $mods_url = phorum_admin_build_url(array('module=mods'));
    $mods_url = call_user_func('phorum_admin_build_url', array('module=mods'));
    $frm->addmessage( 'To exit this screen, click <a href="' . $mods_url . '">here.</a>' );
endif;

// special case: deleting a file
// ask if user truly wants to do this
if ($delete_file):
    $row_text = 'Are you sure you want to delete ' . $curr_file . '?';
    $control = '<input type="submit" name="delete_file_yes" value="Yes" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="delete_file_no" value="No" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    $frm->addrow($row_text, $control);
    //goto skip_to_end;
    break;
endif; // end if ($delete_file)

// first, ask if user wants to create new file

$row_text = 'Create a new file';
$control = 'Name of new file:&nbsp;';
$control .= '<input type="text" name="new_filename" value="" />&nbsp;';
$control .= '<input type="submit" name="new_file" value="Create" />';
$frm->addrow($row_text, $control);


// $curr_file is file we're currently editing

if ( count($a_files)):
    if ( $curr_file == '' ):
        $curr_file = $a_files[0];      // select first file
        $edit_this_file = 'no';        // but don't edit it
    endif;
    // create pull-down menu -- this routine returns the HTML text of a pulldown menu based on the array $a_files -- $curr_file is the default selected value
    $file_pulldown = $frm->select_tag_valaskey('file', $a_files, $curr_file);
    $file_pulldown .= '<input type="submit" name="select_file" value="Select file"/>';
else:
    $curr_file = '';
    $file_pulldown = '(No files)';
endif;
$row_text = 'Select file to edit';

$frm->addrow($row_text, $file_pulldown);

// If $curr_file is empty, we're done
if ($curr_file == ''):
    $file_contents = '';
    //goto skip_to_end;
    break;
endif;

// If we're told we're not editing this file, we're done
if ($edit_this_file == 'no'):
    //goto skip_to_end;
    break;
endif;

// if file exists, we need to offer option of editing it or deleting it

$frm->addbreak('Currently editing: ' . $curr_file);

$row_text = 'Delete this file';
$control = '<input type="submit" name="delete_file" value="Delete" />';
$frm->addrow($row_text, $control);

// Now, load / edit / save selected file
// path to file is $template_path.'/'.$curr_file
// REALLY should have been done by now
// if (! $file_contents_set):
//     $file_contents = file_get_contents( $template_path.'/'.$curr_file );
//     $file_contents_set = true;
// endif;

$frm->addrow('File', $frm->textarea('file_contents', $file_contents, $cols=60, $rows=22, 'style="width: 100%; font-family: monospace;"'), 'top');

// // goto skip_to_end;
// break;

//label skip_to_end:
endswitch;

if ( $edit_this_file == 'yes' ):

    $curr_page = substr($curr_file, 0, -4);

    $row_text = 'Use this URL to access this page:';
    $curr_page_url = phorum_get_url(PHORUM_ADDON_URL, 'module=static_pages', 'page=' . $curr_page);
    $frm->addrow($row_text, $curr_page_url);

    $row_text = 'In template files, use this construction:';
    $control = '{URL->static_pages->' . $curr_page . '}';
    $frm->addrow($row_text, $control);

    $row_text = '';
    $control = '<a href="' . $curr_page_url . '" target="phorum_mod_static_pages_view_page">Click here to view this page in a new window.</a>';
    $frm->addrow($row_text, $control);

endif;

if ( $edit_this_file == 'no' ):
    $frm->addbreak('&nbsp;');
endif;

$frm->show();

// save any changed parameters
phorum_db_update_settings(array('mod_static_pages' => $PHORUM['mod_static_pages']));


function phorum_mod_static_pages_build_filename ($p_text) {
    $r_filename = strtolower($p_text);
    $r_filename = str_replace(' ', '_', $r_filename);
    $r_filename = str_replace('.', '_', $r_filename);
    $r_filename = str_replace('/', '_', $r_filename);
    $r_filename = str_replace(':', '_', $r_filename);
    $r_filename = $r_filename . '.tpl';
    return ($r_filename);
} // end function phorum_mod_static_pages_build_filename

?>
