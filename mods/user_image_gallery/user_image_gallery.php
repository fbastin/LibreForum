<?php

/* User Image Gallery
===============================================================================================================================================
TODO:

(( All version 1 todo's have been taken care of ))


Not doing the following:

  * Posting page: ability to include an uploaded item into a post
  ** Too involved / complicated: Generate the URL on image, people can copy & paste

Optional Specs:
  * Albums and subalbums
  * An album showing ALL images from all users all together
    ** workaround: search ' ' (space)

FUTURE:
  * Fix security hole: user could "re-upload" image over an image in someone else's gallery, if they use a local script to fake-up post data. (Maybe. Haven't tried doing it.)

  *check phorum setting for max file upload limit (per-user)

  * User could cheat "max filespace" check by uploading a small image and then "re-uploading" a larger image.


===============================================================================================================================================
Created by James Lehmann (Ravenswood)
Home URL: http://www.scriptmonkeys.us

Based on an idea by Ryan

tested on LibreForum v 5.2.13 only

Official Specs:
  * Each registered user would have an image gallery.
  * If option to do so is selected, moderators/administrator are alerted to new images, in order to keep an eye on the content.
  * ControlCenter "Image Gallery" page:
    * a link to upload a new item or items. New page would include:
      * multiple upload fields, and ability to add more upload fields
      * mention of max file size limit
    * a view of all images previously uploaded by the user.
    * link to allow user to edit an item. The page would include:
      * a display of the item, no bigger than a certain size
      * ability to rotate the item clockwise or counter clockwise
      * "Title" field
      * "Description" field
      * "Date Created" field
      * "Keywords" field
      * "Reupload Photo" field
      * option to submit, reset, or delete
    * image items organized by paging like LibreForum's: Page 1 of 7 Pages: 1 2 3 4 5 > >>
    * mention of the maximum size for a user gallery, and how much space has been used so far
  * Posting page: ability to include an uploaded item into a post
    * Part of Maurice's mod?
    ** Too involved / complicated: Generate the URL on image, people can copy & paste
  * Profile page would include a link to the user's Image Gallery, which would have:
    * a view of all images uploaded by the author.
      * When an item is clicked, an image viewer or new page (up to the developer) would open with
        *the image,
        *data,
        *datestamp of upload,
        *and option for the viewer to report the image with the reason why (vulgar, copyrighted, etc).
        ** Just sends email alert to admin -- doesn't alter db, and doesn't allow user to specify why they're flagging a certain image
    * image items organized by paging like LibreForum's: Page 1 of 7 Pages: 1 2 3 4 5 > >>
    * a search box for searching by keywords
  * Admin settings:
    * Option of creating a user gallery when the user registers, or when they first access their account (or maybe give them an option asking if the user wants the user gallery)
    * Option on whether or not to moderate (pre-approve) uploaded images
    * Option of having the albums viewable by Public or Members Only
    * Maximum size for uploading an image
    * Maximum number of images for a user gallery

Optional Specs:
  * Watermarking
  * Thumbnail cropping options
  * Albums and subalbums
  * An album showing ALL images from all users all together

Notes:
  * There is no mention of taking images attached to messages and adding them to the gallery -- so I won't do this. But I do need to mention
      that I'm not doing this.
*/

if (!defined("PHORUM")) return;

require_once('./mods/user_image_gallery/defaults.php');

// Handle module installation.
function mod_user_image_gallery_common()
{
    // Load the module installation code if this was not yet done.
    // The installation code will take care of automatically adding
    // the custom profile field that is needed for this module.
    if (empty($GLOBALS['PHORUM']["mod_user_image_gallery"]["mod_user_image_gallery_installed"])) {
        include("./mods/user_image_gallery/install.php");
    }
}

// Register the additional CSS code for this module.
function mod_user_image_gallery_css_register($data)
{
    $data['register'][] = array(
        "module" => "user_image_gallery",
        "where"  => "after",
        "source" => "file(mods/user_image_gallery/user_image_gallery.css)"
    );
    return $data;
}

// Add an extra image_gallery option to the control center menu.
function mod_user_image_gallery_tpl_cc_menu_options_hook()
{
    global $PHORUM;
    
    // Phase out old gallery: Only show menu item if user has existing images
    $user_id = $PHORUM['user']['user_id'] ?? 0;
    if (!$user_id) return;
    
    require_once('./include/api/file_storage.php');
    $files = phorum_api_file_list('image_g', $user_id, NULL);
    
    if (empty($files)) {
        return; // No images, don't show the deprecated panel
    }
    
    // Generate the require template data for the control panel menu button.
    if ($PHORUM["DATA"]["PROFILE"]["PANEL"] == 'image_gallery') {
        $PHORUM["DATA"]["IMAGE_GALLERY_PANEL_ACTIVE"] = TRUE;
    }
    $PHORUM["DATA"]["URL"]["CC_IMAGE_GALLERY"] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=image_gallery");

    // Show the menu button.
    include(phorum_get_template('user_image_gallery::cc_menu_item'));
}

// OK to view gallery?
// If gallery visibility is set to everybody, return true
// If gallery visibility is set to nobody, return false
// If gallery visibility is set to logged-in only, check if user is logged in and set it accordingly
function mod_user_image_gallery_visible () {
    global $PHORUM;

    switch ($PHORUM['mod_user_image_gallery']['gallery_visibility']):
    case 'everybody':
      return true;
      break;
    case 'loggedin':
      if ( $PHORUM["user"]["user_id"] ):
        return true;
      else:
        return false;
      endif;
      break;
    default:
    case 'nobody':
      return false;
      break;
    endswitch;
}

// Add link to author's image_gallery to messages that are being read.
function mod_user_image_gallery_read($messages)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // don't bother if galleries are currently not visible
    if ( ! mod_user_image_gallery_visible () ):
      return $messages;
    endif;

    foreach ($messages as $messageid => $message)
    {
        // Only registered users can have an image_gallery.
        if (empty($message["user_id"])) continue;

        // Use the cached image_gallery URL if we have one.
        if (isset($cache[$message["user_id"]])) {
            if ($cache[$message["user_id"]]) {
                $data = $cache[$message["user_id"]];
                // mod_user_image_gallery = backward compatibility
                $messages[$messageid]["mod_user_image_gallery"]   =
                    $messages[$messageid]["user_image_gallery"]   = $data[0];
                $messages[$messageid]["mod_user_image_gallery_w"] =
                    $messages[$messageid]["user_image_gallery_w"] = $data[1];
                $messages[$messageid]["mod_user_image_gallery_h"] =
                    $messages[$messageid]["user_image_gallery_h"] = $data[2];
            }
            continue;
        }


        // Retrieve the author information.
        if (isset($message['user'])) {
            $author = $message['user'];
        } else {
             $author = phorum_api_user_get($message["user_id"]);
        }

        // Use the new gallery system
        $url = '/gallery.php?q=' . urlencode(strtolower($author['username']));

        // This user has an image_gallery. Add it to the message data.
        // mod_user_image_gallery = backward compatibilty.
        $messages[$messageid]["mod_user_image_gallery"] =
            $messages[$messageid]["user_image_gallery"] = $url;

//	    echo $author;
//	    echo $url;

        // Cache the info, in case we encounter this user again in the loop.
        $cache[$message["user_id"]][0] = $url; // positive caching.
    }

    unset($cache);

    return $messages;
}

// return all known information for a given image
function mod_user_image_gallery_get_image_info ($image_id) {        
  global $PHORUM;
  // Some of the information is attached to the file itself, and is stored and retrieved with the LibreForum file storage API
  // Some of the information is stored in this module's area of the giant $PHORUM array
  // This routine gathers all of it and returns it in a single array.
  $info1 = phorum_api_file_check_read_access ($image_id);               // doesn't just "check read access" - this actually returns (nearly) all info phorum_api has about a file
  if ($info1 === false):
    //error
    $info1 = array('api_error' => phorum_api_strerror(),);
  endif;
  $info2 = $PHORUM['mod_user_image_gallery']['image_info'][$image_id];
  $allinfo = array_merge($info1, $info2);
  $allinfo['dateadded'] = phorum_date($PHORUM['short_date_time'], $allinfo['add_datetime']);
  $allinfo['moddate'] = phorum_date($PHORUM["short_date_time"], $allinfo['mod_date']);
  $allinfo['url'] = phorum_get_url(PHORUM_FILE_URL, 'file='.$image_id, 'modified='.$allinfo['mod_date'], 'filename='.urlencode($allinfo['filename']));    // file=(number) is enough to get the file -- filename=(filename) is helpful, but not required (and, in fact, ignored) (but some browsers require, for example, a gif image to have a url ending in '.gif' -- it also helps to identify the file, in case something unexpected happens)
  $allinfo['owner'] = phorum_api_user_get_display_name($allinfo['user_id'], NULL, PHORUM_FLAG_HTML);
  return $allinfo;
}



// Add link to image gallery to user profiles.
function mod_user_image_gallery_profile($profile)
{
    $user_id = $profile['user_id'] ?? 0;
    if (!$user_id) return $profile;

    require_once('./include/api/file_storage.php');
    $files = phorum_api_file_list('image_g', $user_id, NULL);
    
    if (!empty($files)) {
        $profile["URL"]["user_image_gallery"] = '/gallery.php?q=' . urlencode(strtolower($profile['username']));
    }
    
    return $profile;
}

// Add image_gallery images to the active LibreForum user.
function mod_user_image_gallery_common_post_user()
{
    global $PHORUM;
    $PHORUM['user'] = mod_user_image_gallery_profile($PHORUM['user'], TRUE);
}


// Add an extra image_gallery panel to the control center.
function mod_user_image_gallery_cc_panel($data)
{
    global $PHORUM;

    if ($data['panel'] == 'image_gallery')
    {
      // Separate include file, because of its length.
      include('./mods/user_image_gallery/cc_panel.php');
      $data['handled'] = TRUE;
    }

    return $data;
}

// Cleanup image_gallerys for users that are deleted.
function mod_user_image_gallery_user_delete($user_id) {

    global $PHORUM;

    // Retrieve the list of image_gallery files for the user.
    require_once('./include/api/file_storage.php');

//     $lang = $PHORUM["DATA"]["LANG"]["mod_user_image_gallery"];

    $files = phorum_api_file_list('image_g', $user_id, NULL);

    // Delete the files, and info stored in module
    foreach ($files as $file) {
        phorum_api_file_delete($file);
        unset($PHORUM['mod_user_image_gallery']['image_info'][$file_id]);
    }
    phorum_db_update_settings(array('mod_user_image_gallery' => $PHORUM['mod_user_image_gallery']));
    return $user_id;
}


// wrote this as a separate function, so that if the method I use for storing keywords changes later, none of the routines that use keywords will have to change.

function mod_user_image_gallery_get_keywords_array () {

    global $PHORUM;

    //fix a bug that appears when there are no images in gallery
    if ( ! is_array($PHORUM['mod_user_image_gallery']['image_info']) ):
      $PHORUM['mod_user_image_gallery']['image_info'] = array();
    endif;

    $temp = array();     // will be in form $key=>$val, where $key is image number

    foreach ( $PHORUM['mod_user_image_gallery']['image_info'] as $key => $val):
      // $key is image number, $val is an array
      $temp[$key] = $val['title'] . ', ' . $val['keywords'];    // keyword search automatically includes titles
    endforeach;

    return $temp;

} // end function mod_user_image_gallery_get_keywords_array

// ===================================================================================================================================
// if more functions are added, keep this one last to make it easy to find :)

function mod_user_image_gallery_addon () {

    global $PHORUM;

    // Redirect public gallery views to the new gallery system
    $view = $PHORUM['args']['view'] ?? 'gallery';
    if ($view === 'gallery' || $view === 'image') {
        $redirect_url = '/gallery.php';
        if (isset($PHORUM['args']['user'])) {
            $user_id = (int)$PHORUM['args']['user'];
            if ($user_id > 0) {
                $author = phorum_api_user_get($user_id);
                if ($author && !empty($author['username'])) {
                    $redirect_url = '/gallery.php?q=' . urlencode(strtolower($author['username']));
                }
            }
        }
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect_url);
        exit;
    }

    require_once('./include/format_functions.php');
    require_once('./include/forum_functions.php');
    require_once('./include/api/file_storage.php');

    phorum_build_common_urls();

    $lang = $PHORUM["DATA"]["LANG"]["mod_user_image_gallery"];

    $PHORUM["DATA"]["HEADING"] = $lang['ImageHeading'];


// set-up variables for HTML form
// This needs to be done here because every form submission returns control to the control panel page, then (if necessary) redirects here.
// It's a horribly clunky way to do things. A future version of this script will handle things much more smoothly.
// The following lines were copied from control.php:
// -------------------------------------------------
define("PHORUM_CONTROL_CENTER", 1);
// CSRF protection: we do not accept posting to this script,
// when the browser does not include a LibreForum signed token
// in the request.
if ( function_exists('phorum_check_posting_token') ):
  phorum_check_posting_token('control');
endif;
// The form action for the common form.
$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url(PHORUM_CONTROLCENTER_ACTION_URL);
// used in nearly all or all cc-panels
$PHORUM['DATA']['POST_VARS'] .= "<input type=\"hidden\" name=\"panel\" value=\"image_gallery\" />\n";
// -------------------------------------------------

    // get passed variables -- user, view, page, image, additional, search, return_url, from, m
    if(isset($PHORUM['args']['user'])){
        $gallery_id = (int)$PHORUM['args']['user'];
    } else {
        unset($gallery_id);
    }
    if(isset($PHORUM['args']['view'])){
        $view = (string)$PHORUM['args']['view'];
    } else {
        $view = 'gallery';
    }
    if(isset($PHORUM['args']['page'])){
        $page = (int)$PHORUM['args']['page'];
    } else {
        unset($page);
    }
    if(isset($PHORUM['args']['image'])){
        $image_id = (int)$PHORUM['args']['image'];
    } else {
        unset($image_id);
    }
    if(isset($PHORUM['args']['additional'])){
        $additional = (string)$PHORUM['args']['additional'];
    } else {
        $additional = 'gallery';
    }
    if(isset($PHORUM['args']['search'])){
        $search = $PHORUM['args']['search'];
    } else {
        unset($search);
    }
    if(isset($PHORUM['args']['return_url'])){
        $return_url = $PHORUM['args']['return_url'];
    } else {
        unset($return_url);
    }
    if(isset($PHORUM['args']['from'])){
        $came_from = (int)$PHORUM['args']['from'];
    } else {
        unset($came_from);
    }
    if(isset($PHORUM['args']['m'])){
        // this is a special one
        $messages = unserialize($PHORUM['args']['m']);
    } else {
        unset($messages);
    }

// at this point, we can display one of three pages based on $view
//   gallery - public gallery, no editing -- actually, there IS editing, because users can post comments
//   info    \__ private area where user can edit his/her own image files -- this is basically an extension of the control panel
//   manage  /

    switch ($view):
    default:
    case 'gallery':
      include ('./mods/user_image_gallery/view_gallery.php');
      break;
    case 'image':
      include ('./mods/user_image_gallery/view_image.php');
      break;
    case 'info':
      include ('./mods/user_image_gallery/view_info.php');
      break;
    case 'manage':
      include ('./mods/user_image_gallery/view_manage.php');
      break;
    case 'search':
      include ('./mods/user_image_gallery/view_search.php');
      break;
    endswitch;

} // end function mod_user_image_gallery_addon

?>