<?php
if (!defined("PHORUM")) return;

// if galleries are set to private, beat it

// old method:
// if ( ! mod_user_image_gallery_visible () ):
//     phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
//     exit();
// endif;

// new method:

if ( $PHORUM['mod_user_image_gallery']['gallery_visibility'] == 'nobody' ):

  // tell users galleries have been set to private
  phorum_output('user_image_gallery::private');

elseif ( $PHORUM['mod_user_image_gallery']['gallery_visibility'] == 'loggedin' and $PHORUM['user']['user_id'] == 0 ):

  // tell users they must log-in to see galleries
  phorum_output('user_image_gallery::pleaselogin');

else:
  // begin "giant if"


// display one image full-size

// image number in $image_id

// Get everything there is to know about this image
$image = mod_user_image_gallery_get_image_info ($image_id);

// This needs to be set to tell LibreForum which template to use
$data['template'] = 'user_image_gallery::image';

// give template access to everything we know, that is...
// ...everything about this image...
$PHORUM['DATA']['IMAGE'] = $image;

// ...and all of this module's settings
// $PHORUM['DATA']['mod_user_image_gallery'] = $PHORUM['mod_user_image_gallery'];     // original line, but that gave us everything, and we don't need ['image_info'] -- so now do this:
$temp_copy = $PHORUM['mod_user_image_gallery'];
unset ($temp_copy['image_info']);                          // we don't need all this
$PHORUM['DATA']['mod_user_image_gallery'] = $temp_copy;
unset($temp_copy);                                         // free-up memory

// set-up loop to display comments

    $comment_list_x = $image['comments'];
    $comment_list = explode(',', $comment_list_x);

    // this is a list of "text files" containing comments about the current image
    // the format is:
/*
image_id=((imagenumber))
datestamp=((seconds))
sender_id=((user id number))
sender_name=((user display name))
message=
((everything from this point forward
including line breaks, is the message))
*/
    $comments = array();
    foreach ($comment_list as $comment_number):
      if ($comment_number != 0):
        $comments[$comment_number]['comment_number'] = $comment_number;
        // get the text file
        $textfileinfo = phorum_api_file_retrieve($comment_number);
        $text = $textfileinfo['file_data'];
        $text_lines = explode("\n", $text);

        // pull lines out of $text_lines one at a time
        // each one will be in the form "settingname=settingvalue"
        // after "message=", the rest will be the message

        $finished = false;
        while ( ! $finished):
          $line = array_shift($text_lines);
          if (is_null($line)):
            // array was empty before message was found
            // first, declare that we are finished, to avoid infinite loop
            $finished = true;
            // since there was no message, this is a bad comment -- remove it from the list
            unset($comments[$comment_number]);
            continue;  // skip the rest of the loop
          endif;
          list($key, $value) = explode('=', $line, 2);
          // check image_id
          if ($key == 'image_id'):
            if ( (int)$value != $image_id ):
              // this message is for a different image
              // don't raise an error, just quietly suppress the comment and move on
              unset($comments[$comment_number]);
              $finished = true;
              continue;
            endif;
          endif;
          if ($key == 'message'):
            // everything still remaining in $text_lines is the message
            $comments[$comment_number]['message'] = implode("\n", $text_lines);
            $finished = true;
          else:
            $comments[$comment_number][$key] = $value;
            if ($key == 'datestamp'):
              $comments[$comment_number]['posted_date'] = phorum_date($PHORUM['short_date_time'], $value);
            endif;
          endif;
        endwhile;
      endif;
    endforeach;

    $PHORUM['DATA']['COMMENTS'] = $comments;
    /* usage:
      {LOOP COMMENTS}
        {COMMENTS->message}
      {/LOOP COMMENTS}
    */

    // Override the default title and description.
    if ($image['title'] != ''):
      $PHORUM['DATA']['HEADING'] = $image['title'];
    else:
      $PHORUM['DATA']['HEADING'] = $image['filename'];
    endif;
    $PHORUM['DATA']['HTML_TITLE'] =
        htmlspecialchars(strip_tags($PHORUM['DATA']['HEADING']));
    $PHORUM['DATA']['HTML_DESCRIPTION'] = '';

    // throw out all of breadcrumb except home
    $home_breadcrumb = $PHORUM['DATA']['BREADCRUMBS'][0];    // save home
    $PHORUM['DATA']['BREADCRUMBS'] = array();                // destroy all
    $PHORUM['DATA']['BREADCRUMBS'][0] = $home_breadcrumb;    // restore link to home

    // now add our breadcrumb
    $PHORUM['DATA']['BREADCRUMBS'][] = array(
        'URL'  => NULL,
        'TEXT' => $PHORUM['DATA']['HEADING']
    );

    // url to get back to gallery
    if ( isset($return_url) and ($return_url != '')):
      $PHORUM['DATA']['URL']['BACK'] = $return_url;
    else:
      $PHORUM['DATA']['URL']['BACK'] = phorum_get_url(PHORUM_ADDON_URL, 'module=user_image_gallery', 'view=gallery', 'user='.$image['user_id'] );
      if ($came_from == 0):
        $came_from = 1;
      endif;
    endif;

    // back-to caption
    // uses $came_from to tell if we came here from a gallery or a search page -- I'm really not happy with this, it's a magic number, and another variable in an already-overloaded url -- I'm hoping to come up with a better solution someday
    switch ($came_from):
    case 1:
      $PHORUM['DATA']['BACK_CAPTION'] = str_replace('%owner%', $image['owner'], $lang['caption_back_to_gallery']);
      break;
    case 2:
      $PHORUM['DATA']['BACK_CAPTION'] = $lang['caption_back_to_search_results'];
      break;
    default:
      $PHORUM['DATA']['BACK_CAPTION'] = $lang['caption_back'];
      break;
    endswitch;

    // image file number
    $PHORUM['DATA']['image_number'] = $image_id;

    $PHORUM['DATA']['MESSAGES'] = $messages;

    // if user is looking at his/her own image, give them a button they can use to delete comments
    if ( $PHORUM['user']['user_id'] == $image['user_id'] ):
      $PHORUM['DATA']['delete_button'] = 1;
    else:
      $PHORUM['DATA']['delete_button'] = 0;
    endif;
    
    $PHORUM['DATA']['loggedin'] = !!$PHORUM['user']['user_id'];   // not-not-user_id, will be true if user is logged in and false otherwise

// include phorum_get_template('user_image_gallery::image');   // displays template only
phorum_output('user_image_gallery::image');      // displays header, template, footer

endif;   // end "giant if"
?>