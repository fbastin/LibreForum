<?php

if (!defined('PHORUM_CONTROL_CENTER')) return;

global $PHORUM;
require_once('./include/api/file_storage.php');

$lang = $PHORUM["DATA"]["LANG"]["mod_user_image_gallery"];

$PHORUM["DATA"]["HEADING"] = $lang['ImageHeading'];

$user_id = $PHORUM["user"]["user_id"];
$user_display_name = phorum_api_user_get_display_name($user_id, NULL, PHORUM_FLAG_PLAINTEXT);


// Retrieve the list of images for the user.
$images = phorum_api_file_list('image_g', $PHORUM["user"]["user_id"], NULL);
// This will only get file_id, filename, filesize and add_datetime. If you need any other
//  information about a file, you will need to call mod_user_image_gallery_get_image_info
//   for each file you need.

// find total space used by all files
$total_bytes = 0;
foreach ( $images as $image ):
  $total_bytes += $image['filesize'];
endforeach;

// Keep track if we need to store the extra data which is stored in the module
$do_store_module_data = FALSE;

if (empty($PHORUM['mod_user_image_gallery'])) {
    $PHORUM['mod_user_image_gallery'] = array();
}
if (empty($PHORUM['mod_user_image_gallery']['image_info'])) {
    $PHORUM['mod_user_image_gallery']['image_info'] = array();
}

$messages = array();
// format of $messages:
// $messages[0]['type'] = MOD_USER_IMAGE_GALLERY_INFO
// $messages[0]['message'] = 'File zanzibar.png uploaded successfully.'
// $messages[1]['type'] = MOD_USER_IMAGE_GALLERY_WARNING
// $messages[1]['message'] = 'File luigi.png could not be uploaded: You have exceeded the number of pictures you can have in your account.'

$next_screen = 'simple';          // this may be overridden by a $_POST setting
// $next_screen tells us which screen (template) to load up next -- possible values are
//   'simple'  - the default: show the control center screen, show the user images, allow one upload
//   'multi'   - same as simple but allow mutiple-file uploads
//   'info'    - show one image full-size and allow user to enter information about it such as a caption or keywords
//   'manage'  - show one image full-size and allow user to edit the image, such as rotate it or add a watermark
//   'gallery' - public image gallery
//   'view'    - public view single image, with comments
//   'search'  - search results
// simple, multi, info, and manage are only viewable by the gallery owner
// gallery, view, and search are viewable by everybody
// (Currently, 'gallery' is not used here -- but it's set-up to be used incase someone in the future needs it)

if(isset($PHORUM['args']['screen'])):
    $next_screen = $PHORUM['args']['screen'];
endif;

if (count($_POST)):
  if (isset($_POST['screen'])):
    $next_screen = $_POST['screen'];
  endif;
  $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
  switch ($action):
  case 'upload':
    $messages[] = array(
        'type' => MOD_USER_IMAGE_GALLERY_WARNING,
        'message' => 'L\'ajout de nouvelles images via cette interface est désactivé. Merci d\'utiliser la nouvelle galerie du site.'
    );
    break;

// ----------------------------------------------------------------------
// Handle deleting images and updating settings.
// ----------------------------------------------------------------------
  case 'bulk_edit':
    $changes_made = false;

    // if it's info or manage, just open a new screen to provide the requested function
    // look through all the keys in $_POST -- if one of them is 'info#' or 'manage#', then go to the appropriate screen
    $pk = array_keys($_POST);
    $info = -1;
    $manage = -1;
    foreach ($pk as $key):
      if( preg_match('/^(info|manage)([0-9]*)$/', $key, $matches) ):
        // do nothing here, but set-up to redirect when you get to the end of this script
        $next_screen = $matches[1];     // will be either 'info' or 'manage'
        $file_id = $matches[2];
      endif;
    endforeach;

    // Delete images.
    if ( isset($_POST['delete_checked'])):
      if ( ! empty($_POST['delete'])) {
        foreach($_POST["delete"] as $file_id) {
          phorum_api_file_delete($file_id);
          unset($images[$file_id]);
          unset($PHORUM['mod_user_image_gallery']['image_info'][$file_id]);
          $changes_made = true;
          $do_store_module_data = true;
        }
      }
    endif;

    // if any changes made at all, report success
    if ($changes_made):
      $messages[] = array(
                           'type' => MOD_USER_IMAGE_GALLERY_INFO,
                           'message' => $PHORUM["DATA"]["LANG"]["ChangesSaved"],
                         );
    endif;
    // Problem: We get to the next screen via a redirect, which means any variables we set here -- such as $messages -- will be lost
    break;
  case 'info_edit':
    // store changes from info screen
    $file_id = (int)$_POST['file_id'];
    if (isset($_POST['title']      )): $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['title']       = $_POST['title']      ; endif;
    if (isset($_POST['description'])): $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['description'] = $_POST['description']; endif;
    if (isset($_POST['keywords']   )): $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['keywords']    = $_POST['keywords']   ; endif;
    $changes_made = true;
    $do_store_module_data = true;
    if ($changes_made):
      $messages[] = array(
                           'type' => MOD_USER_IMAGE_GALLERY_INFO,
                           'message' => $PHORUM["DATA"]["LANG"]["ChangesSaved"],
                         );
    endif;
    // set-up to redirect below
    // $file_id = (int)$_POST['file_id'];     // already set
    // $next_screen = $_POST['screen'];       // ALSO already set -- I guess I didn't have to do anything
    break;
  case 'manage_edit':
    // manipulate image -- do in a separate file
    require('./mods/user_image_gallery/manage_edit.php');
    break;
  case 'post_comment_on_image':
    // save comment *as a text file* using file storage API

    $file_id                   = (int)$_POST['image_number'];
    $current_user              = (int)$_POST['current_user'];
    $current_user_display_name =      $_POST['current_user_dn'];
    $message                   =      $_POST['message'];

// message will be saved *exactly as entered by the user*, including HTML and hack attempts.
// We need to run the message through the same kind of filtering forum posts go through.
// !!! TODO !!!

    $image_number = $file_id;

    $filetext = '';

    $filetext .= "image_id=$image_number\n";
    $filetext .= "datestamp=".time()."\n";
    $filetext .= "sender_id=$current_user\n";
    $filetext .= "sender_name=$current_user_display_name\n";
    $filetext .= "message=\n";
    $filetext .= $message;


    // below is a copy of the other time we used phorum_api_file_store, with the variable names changed to avoid collisions

    // Create the file array for the file storage API.
    $filex = array(
        "user_id"   => NULL,                           // if NULL then it will be set to current user -- I don't know if this will cause any problems or not
        "filename"  => $image_number.'.txt',           // use (image number).txt as filename -- this will result in a lot of dup filenames, but down the road it will make it easier to remove all comments associated with a deleted image
        "filesize"  => strlen($filetext),              // I don't think we need this, but keeping it doesn't seem to cause any harm either
        "file_data" => $filetext,
        "link"      => "image_gc"                      // use "image_gc" because we have a limit of 10 characters
    );

    // Store the file.
    $file_retx = phorum_api_file_store($filex);
    if ( $file_retx === false ) {
        // someday test this -- force error and see what happens
        $error = str_replace( '%file_name%', $filex['filename'], $lang["GeneralError"] );
        $error .= phorum_api_strerror();
        $messages[] = array(
                             'type' => MOD_USER_IMAGE_GALLERY_WARNING,
                             'message' => $error,
                           );
    }

    // Add this message to the list of comments on this message
    $comment_number = $file_retx['file_id'];

    if (isset($PHORUM['mod_user_image_gallery']['image_info'][$image_number]['comments'])):
      $PHORUM['mod_user_image_gallery']['image_info'][$image_number]['comments'] .= ',' . $comment_number;
    else:
      $PHORUM['mod_user_image_gallery']['image_info'][$image_number]['comments'] = $comment_number;
    endif;
    $do_store_module_data = true;

    $next_screen = 'view';
    break;
  case 'delete_comment_on_image':
    // delete comment (see above) using file storage API

    $file_id        = (int)$_POST['image_number'];
    $current_user   = (int)$_POST['current_user'];
    $comment_number = (int)$_POST['comment_number'];

    // Create the file array for the file storage API.
    $filex = array(
        "file_id" => $comment_number,     // This is the only thing we need
    );

    // Delete the file.
    $file_ret = phorum_api_file_delete($filex);       // no return value - not even false on error

//     if ( $file_retx === false ) {
//         // someday test this -- force error and see what happens
//         $error = str_replace( '%file_name%', $filex['filename'], $lang["GeneralError"] );
//         $error .= phorum_api_strerror();
//         $messages[] = array(
//                              'type' => MOD_USER_IMAGE_GALLERY_WARNING,
//                              'message' => $error,
//                            );
//     }

    // Remove this message from the list of comments on this message
    $temp = explode(',', $PHORUM['mod_user_image_gallery']['image_info'][$image_number]['comments']);
    foreach($temp as $key => $value) {
      if( $value == $comment_number ) {
        unset($array[$key]);
      }
    }
    $PHORUM['mod_user_image_gallery']['image_info'][$image_number]['comments'] = implode(',', $temp);
    $do_store_module_data = true;

    $next_screen = 'view';
    break;
  case 'report_image':

    // alert moderators (or whoever)

    $image_number     = (int)$_POST['image_number'];
    $file_id          = $image_number;
    $reported_by      = (int)$_POST['current_user'];
    $reported_by_name = phorum_api_user_get_display_name($reported_by, NULL, PHORUM_FLAG_PLAINTEXT);
    $image_owner      = (int)$_POST['image_owner'];
    $image_owner_name = phorum_api_user_get_display_name($image_owner, NULL, PHORUM_FLAG_PLAINTEXT);

    if ( $PHORUM['mod_user_image_gallery']['allow_report_violation'] and
         $PHORUM['mod_user_image_gallery']['alert_email'] != ''
       ):
      // send an email to $PHORUM['mod_user_image_gallery']['alert_email'] about the image
      include_once("./include/email_functions.php");

      $email_subject = str_replace('%image_number%', $image_number, $lang['violation_email_subject']);

      $image_link = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view=image', 'image='.$file_id );    // someday - change this to some sort of admin screen?

      $r1 = array( '%image_number%', '%image_link%', '%image_owner_name%', '%image_owner%', '%reported_by_name%', '%reported_by%' );  // replace these...
      $r2 = array(  $image_number,    $image_link,    $image_owner_name,    $image_owner,    $reported_by_name,    $reported_by   );  // ...with these

      $email_text = str_replace($r1, $r2, $lang['violation_email_text']);

      // now send it
      $addresses = array();
      $addresses[] = $PHORUM['mod_user_image_gallery']['alert_email'];

      $data = array();
      $data['mailmessage'] = $email_text;
      $data['mailsubject'] = $email_subject;

      phorum_email_user($addresses, $data);

      // report success
      $messages[] = array(
                           'type' => MOD_USER_IMAGE_GALLERY_INFO,
                           'message' => $lang['A_moderator_has_been_alerted'],
                         );
    else:
      // no moderator email -- we should not have gotten here
      // just report failure and continue
      $messages[] = array(
                           'type' => MOD_USER_IMAGE_GALLERY_WARNING,
                           'message' => $lang['alert_could_not_be_sent'],
                         );
    endif;

// Problem: We get to the "view image" screen via a redirect, which means any variables we set here -- such as $messages -- will be lost
// todo

    $next_screen = 'view';
    break;
  default:
  case 'search':     // handle search further downstream
  case 'nothing':
    // just like it says on the tin: do nothing
    break;
  endswitch;
endif;  // if (count($_POST)):

// ----------------------------------------------------------------------
// Store updated module data if required.
// ----------------------------------------------------------------------

if ($do_store_module_data) {
  phorum_db_update_settings(array('mod_user_image_gallery' => $PHORUM['mod_user_image_gallery']));
}

// ----------------------------------------------------------------------
// Determine the list of available images for the current user.
// ----------------------------------------------------------------------

$total_size = 0;

// Retrieve a fresh list of images for the user. We did keep the
// $images array up-to-date in the above code, but some data like
// "add_datetime" is mising from them.
$images = phorum_api_file_list('image_g', $PHORUM["user"]["user_id"], NULL);

$info = $PHORUM['mod_user_image_gallery']['image_info'];

foreach ($images as $id => $file)
{
    $dimensions = NULL;
    if (isset($info[$id]['width']) && isset($info[$id]['height'])) {
        $dimensions = $info[$id]['width'] . ' x ' . $info[$id]['height'];
        if ( ($info[$id]['width'] >= $info[$id]['height']) and ($info[$id]['width'] > $PHORUM['mod_user_image_gallery']['thumbnail_size']) ):
          $images[$id]['adjustment'] = 'width="'.$PHORUM['mod_user_image_gallery']['thumbnail_size'].'"';
        elseif ( ($info[$id]['height'] > $info[$id]['width']) and ($info[$id]['height'] > $PHORUM['mod_user_image_gallery']['thumbnail_size']) ):
          $images[$id]['adjustment'] = 'height="'.$PHORUM['mod_user_image_gallery']['thumbnail_size'].'"';
        else:
          $images[$id]['adjustment'] = '';
        endif;
    }
    $images[$id]["dimensions"] = $dimensions;

    $images[$id]["filesize"] = str_replace(' ', '&nbsp;', phorum_filesize($file["filesize"]));
    $images[$id]["raw_dateadded"] = $file["add_datetime"];
    $images[$id]["dateadded"] = phorum_date($PHORUM["short_date_time"], $file["add_datetime"]);
    $images[$id]["moddate"] = phorum_date($PHORUM["short_date_time"], $info[$id]['mod_date']);
    $images[$id]["url"] = phorum_get_url(PHORUM_FILE_URL, "file=$id", 'modified='.$info[$id]['mod_date'], "filename=".urlencode($file['filename']));

    $total_size += $file["filesize"];
}

// ----------------------------------------------------------------------
// Setup template data.
// ----------------------------------------------------------------------

if ( $messages ):
  $m = urlencode(serialize($messages));   // really hate this, btw
endif;

$do_redirect = false;
switch ($next_screen):
default:
case 'simple':
  $data['template'] = 'user_image_gallery::cc_panel';
  break;
case 'multi':
  $data['template'] = 'user_image_gallery::cc_multi_upload';
  break;
case 'info':
case 'manage':
  if ($additional == ''):
    if ($m):
      $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view='.$next_screen, 'image='.$file_id, 'm='.$m );
    else:
      $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view='.$next_screen, 'image='.$file_id );
    endif;
  else:
    if ($m):
      $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view='.$next_screen, 'image='.$file_id, 'additional='.$additional, 'm='.$m );
    else:
      $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view='.$next_screen, 'image='.$file_id, 'additional='.$additional );
    endif;
  endif;
  $do_redirect = true;
  break;
case 'gallery':
  if ($m):
    $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view=gallery', 'user='.$current_gallery, 'm='.$m );
  else:
    $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view=gallery', 'user='.$current_gallery );
  endif;
  $do_redirect = true;
  break;
case 'view':
  if ($m):
    $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view=image', 'image='.$file_id, 'm='.$m );
  else:
    $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view=image', 'image='.$file_id );
  endif;
  $do_redirect = true;
  break;
case 'search':
  $search_parameter = isset($_POST['search']) ? urlencode($_POST['search']) : '';
  if ($m):
    $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view=search', 'search='.$search_parameter, 'm='.$m );
  else:
    $redirect = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view=search', 'search='.$search_parameter );
  endif;
  $do_redirect = true;
  break;
endswitch;

if ($do_redirect):
  phorum_redirect_by_url($redirect);
  die();     // in case the redirect fails
endif;

// if we get here, then we're showing a control panel page which displays thumbnails
// at this point, $images will already be set
// figure out which page we're on before continuing
// the below is an attempt at somewhat-formalizing the page number handling

// first, number of elements per page
$display_per_page = $PHORUM['mod_user_image_gallery']['thumbs_per_page_cc'];

// second, total number of items to display
$total_items = count($images);

// third, from that calculate the total number of pages we shall need
$total_pages = ceil($total_items / $display_per_page);
if ($total_pages < 1):
  $total_pages = 1;
endif;

// fourth, get current page number requested
$current_page = 0;
if(isset($PHORUM['args']['page'])):
    $current_page = (int)$PHORUM['args']['page'];
endif;
if(isset($_POST['page'])):
    $current_page = (int)$_POST['page'];
endif;
if ($current_page > $total_pages):
  $current_page = $total_pages;
endif;
if ($current_page < 1):
  $current_page = 1;
endif;

// fifth, calculate first and last element to be displayed (zero base)
$start = ($current_page - 1) * $display_per_page;
$end = $current_page * $display_per_page - 1;
if ( $end >= $total_items ):
  $end = $total_items - 1;
endif;

// at this point, we need to figure out paging stuff
// it looks like they're headed towards some sort of general page-number-handling routine, but never got to it
// I'd do it myself, but I don't have time.
// The following is modified from search.php and list.php:

// figure out paging

$url_template = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=image_gallery", 'screen='.$next_screen, 'page=%page_num%');

$PHORUM["DATA"]["CURRENTPAGE"] = $current_page;
$PHORUM["DATA"]["TOTALPAGES"] = $total_pages;
$PHORUM["DATA"]["URL"]["PAGING_TEMPLATE"] = $url_template;

if ($total_pages <= 5) {
    $start_page = 1;
} elseif ($total_pages - $current_page < 2) {
    $start_page = $total_pages - 4;
} elseif ($total_pages > 5 and $current_page > 3) {
    $start_page = $current_page - 2;
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
        "url" => str_replace ( '%page_num%', $pagenumber, $url_template ),
    );
endfor;

if ($start_page > 1) {
    $PHORUM['DATA']['URL']['FIRSTPAGE'] = str_replace ( '%page_num%', '1',                  $url_template );
}

if ($end_page < $total_pages) {
    $PHORUM['DATA']['URL']['LASTPAGE'] =  str_replace ( '%page_num%', (string)$total_pages, $url_template );
}

if ($current_page > 1) {
    $prevpage = $current_page - 1;
    $PHORUM['DATA']['URL']['PREVPAGE'] =  str_replace ( '%page_num%', (string)$prevpage,    $url_template );
}

if ($current_page < $total_pages) {
    $nextpage = $current_page + 1;
    $PHORUM['DATA']['URL']['NEXTPAGE'] =  str_replace ( '%page_num%', (string)$nextpage,    $url_template );
}

$PHORUM['DATA']['MULTIPLE_PAGES'] = true;
if ( $total_pages == 1 ):
  $PHORUM['DATA']['MULTIPLE_PAGES'] = false;
endif;

// ----------------------------------------------------------------------
// Setup template data.
// ----------------------------------------------------------------------

// fill-up FILES loop with just the images we need for this page
// $PHORUM["DATA"]["FILES"] = $images;   // old way: grab all images
// new way:
$PHORUM["DATA"]["FILES"] = array();
$i = 0;
foreach ($images as $key => $value):
  if ( $i >= $start and $i <= $end ):
    $PHORUM["DATA"]["FILES"][$key] = $value;
    if ( isset ( $PHORUM['mod_user_image_gallery']['image_info'][$key]['title'] )):
      $PHORUM["DATA"]["FILES"][$key]['title'] = $PHORUM['mod_user_image_gallery']['image_info'][$key]['title'];
    endif;
    if ( $PHORUM['mod_user_image_gallery']['image_info'][$key]['approved'] == MOD_USER_IMAGE_GALLERY_WAITING ):
      $PHORUM["DATA"]["FILES"][$key]['status'] = $lang['state_waiting'];
    endif;
    if ( $PHORUM['mod_user_image_gallery']['image_info'][$key]['approved'] == MOD_USER_IMAGE_GALLERY_BANNED ):
      $PHORUM["DATA"]["FILES"][$key]['status'] = $lang['state_banned'];
    endif;
  endif;
  ++$i;
endforeach;


$PHORUM["DATA"]["NUMBER_OF_FILES"] = count($images);

$PHORUM["DATA"]["mod_user_image_gallery"] = $PHORUM['mod_user_image_gallery'];

$PHORUM["DATA"]["mod_user_image_gallery"]["disable_image_gallery_display"] =
    !empty($PHORUM['user']["mod_user_image_gallery"]["disable_image_gallery_display"]);

if ($PHORUM['mod_user_image_gallery']['max_filesize']) {
    $PHORUM["DATA"]["FILE_SIZE_LIMIT"] = str_replace(
        array(
            '%filesize%',
            '%width%',
            '%height%'
        ),
        array(
            phorum_filesize($PHORUM['mod_user_image_gallery']['max_filesize']*1024),
            $PHORUM['mod_user_image_gallery']["max_width"],
            $PHORUM['mod_user_image_gallery']["max_height"]
        ),
        $lang["FileSizeLimits"]
    );
}

if ($PHORUM['mod_user_image_gallery']["file_types"]) {
    $file_type_list = implode(", ",$PHORUM['mod_user_image_gallery']["file_types"]);
    $PHORUM["DATA"]["FILE_TYPE_LIMIT"] = str_replace(
        '%file_type_list%',
        $file_type_list,
        $lang["FileTypeLimits"]
    );
}

$PHORUM["DATA"]["LANG"]["mod_user_image_gallery"]["ImageLimit"] = str_replace(
    '%max_images%',
    $PHORUM['mod_user_image_gallery']['max_images'],
    $lang["ImageLimit"]
);

$PHORUM["DATA"]["TOTAL_FILES"] = count($images);
$PHORUM["DATA"]["TOTAL_FILE_SIZE"] = phorum_filesize($total_size);

// messages from uploads or POST data stuff

$PHORUM['DATA']['MESSAGES'] = $messages;

// has the user reached any limits?

if ( count($images) >= $PHORUM['mod_user_image_gallery']['max_images'] ):
  $PHORUM["DATA"]["FILES_LIMIT_REACHED"] = true;
endif;

// set-up link to user's own gallery

$PHORUM["DATA"]["URL"]["my_gallery"] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view=gallery', 'user='.$PHORUM["user"]["user_id"] );

// set-up multiple upload blanks

if ($next_screen == 'multi'):
  $n = $PHORUM['mod_user_image_gallery']['max_images'] - count($images);
  // restrict number of blanks to range zero through $PHORUM['mod_user_image_gallery']['multi_upload_blanks']
  if ($n < 0) {$n = 0;}
  if ($n > $PHORUM['mod_user_image_gallery']['multi_upload_blanks']) {$n = $PHORUM['mod_user_image_gallery']['multi_upload_blanks'];}

  $PHORUM["DATA"]["MULTI_UPLOAD_BLANKS"] = $n;

  // set up our own loop -- call it NUMBERS
  $PHORUM['DATA']['NUMBERS'] = array();
  for ($i = 1; $i <= $n; ++$i):
    $PHORUM['DATA']['NUMBERS'][$i]['number'] = $i;
  endfor;
  /* usage:
    {LOOP NUMBERS}
      {NUMBERS->number}
    {/LOOP NUMBERS}
  */
endif;
