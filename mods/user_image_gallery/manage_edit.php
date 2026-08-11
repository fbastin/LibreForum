<?php

// switch(0):
// default:     // set-up fake goto

// The primary purpose of class.upload.php is to handle uploaded files, but LibreForum already handles uploaded files pefectly well.
// We're using class.upload.php for its secondary functions -- a large collection of image-manipulation routines.
include_once('./mods/user_image_gallery/include/class.upload.php');

    // image manipulation
    // first, make sure the user pressed one of the defined buttons
    // if not, do nothing
    $function = 'nothing';
    foreach (array_keys($_POST) as $key):
      switch ($key):
      case 'rotate90':
        $function = 'rotate';
        $param1 = 90;
        break;
      case 'rotate180':
        $function = 'rotate';
        $param1 = 180;
        break;
      case 'rotate270':
        $function = 'rotate';
        $param1 = 270;
        break;
      case 'watermark2':
        $function = 'watermark_using_text';
        $text = $_POST['watermark_text'];
        $gdf_font = $_POST['watermark_font'];
        $text_color = $_POST['watermark_color'];
        break;
      case 're_upload':
        // I guess I lied. We *are* using it to upload files!
        $function = 're_upload';
        $param1 = '';
        break;
//       case '':
//         $function = '';
//         $param1 = ;
//         break;
      default:
        // do nothing
      endswitch;
    endforeach;
    if ($function !== 'nothing'):

//ob_start();                               //debugg
// print 'checkpoint Z<br>';                 //debugg
// print recursive_print('$_POST', $_POST);  //debugg

      $file_id = (int)$_POST['file_id'];     // id of image to work on

      // get all info for the requested image
      // $image = mod_user_image_gallery_get_image_info ($file_id);   // don't need all this
      $image = phorum_api_file_retrieve($file_id);                    // just this
// note: $image['file_data'] contains raw binary contents of file (i.e., not encoded)

      // store temp copy of image, so class.upload can work on it
      // if it's a re-upload, use the actual uploaded file
      if ( $function == 're_upload' ):
          if ( !empty($_FILES) and is_uploaded_file($_FILES['newfile1']['tmp_name'])):
              $tempf = $_FILES['newfile1']['tmp_name'];
          else:
              // goto would be really really useful here
              // reallllllllllllllllly useful..........................
              // screw it. I'm putting everything into a giant "switch" statement and then "break"ing out of it
              // break 2;   // fake goto   (( break 2: 1 for foreach, 2 for switch ))
              // error message: Fatal error: Cannot break/continue 2 levels in /home/scriptmo/public_html/ph/mods/user_image_gallery/manage_edit.php on line 69
              // WTF??? I give up. I'm just going to use a redirect to get out of here.
              phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
              die();     // in case the redirect fails
          endif;
      else:
          $cache_dir = $PHORUM["cache"];
          $extensionx = "";
          $filename = $image['filename'];
          $dotpos = strrpos($filename, ".");
          if ($dotpos !== FALSE) {
              $extensionx = strtolower(substr($filename, $dotpos));    // unlike $extension, $extensionx includes the '.'
          }
          // create random filename
          $tempf = $cache_dir . '/' . rand() . $extensionx;
          while ( file_exists($tempf) ):
            $tempf = $cache_dir . '/' . rand() . $extensionx;
          endwhile;
          //copy file from internal storage (api) to temp filename ($tempf)
          $flags = defined('FILE_BINARY') ? FILE_BINARY : 0;
          file_put_contents ($tempf, $image['file_data'], $flags);       // Will PHP turn \x0A into \x0D\x0A ? Will PHP turn ' and " into \' and \" ?? I don't know!
      endif;

      // now tell class.upload to do stuff to it based on what user selected
      $handle = new upload($tempf, $PHORUM['language']);

      if ( ! $handle->uploaded):
        die ('Error! File ' . __FILE__ . ' line ' . __LINE__ . ": " . $handle->error);   // I don't know if this will provide an error message or not
      endif;

      $dosomething = false;
      $new_image = false;

      if ($function == 'rotate'):
        $dosomething = true;
        $handle->image_rotate = $param1;
      endif;

      if ($function == 'watermark_using_text'):
        $dosomething = true;
        // instead of using a watermark image, fake one up by using very pale text, in color chosen by user
        $handle->image_text = $text;
        $handle->image_text_color = $text_color;
        $handle->image_text_percent = 20;
        $handle->image_text_font = $gdf_font;
      endif;

      if ( $function == 're_upload' ):
        $dosomething = true;            // say to do something even though we don't really do anything
        $new_image = true;

        // even though this is a "re-upload", the new image may have a different filename (or even file type) than the old image
        // change filename field in $image
        $image['filename']  = $_FILES['newfile1']['name'];
        $image['mime_type'] = $_FILES['newfile1']['type'];

        // also, for some reason, the nearly-flawless class.upload.php will not return the destination image height and width on a straight upload
        //   however, it will find the hieght and width of the source file
        $change_width    = $handle->image_src_x;
        $change_height   = $handle->image_src_y;
        $change_filename = $_FILES['newfile1']['name'];    // grab name and file size while we're at it
        $change_filesize = $handle->file_src_size;
      endif;

      if ($dosomething):                  // more to the point, if there's nothing to do, *don't* do something
        $rawfiledata = $handle->Process();
        if ( ! $handle->processed):
          $error = str_replace('%error%', $handle->error, $lang['ImageCouldNotBeProcessed']);
          die ('Error! File ' . __FILE__ . ' line ' . __LINE__ . ': ' . $error);
        endif;

        // save raw data back into image api
        $image['file_data'] = $rawfiledata;

        // everything else in $image array stays the same
        $file_ret = phorum_api_file_store ($image);

        // now, replace module memory with anything that changed in image
        $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['filesize'] = $file_ret['filesize'];
        $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['width']    = $handle->image_dst_x;
        $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['height']   = $handle->image_dst_y;
        $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['mod_date'] = time();                   // modified date
        if ($new_image):
          // if new upload, moderator must re-approve
          if ($PHORUM['mod_user_image_gallery']['suppress_until_approved']):                                // moderator approval
            $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['approved'] = MOD_USER_IMAGE_GALLERY_WAITING;
          endif;
        endif;
        if ( $function == 're_upload' ):
          $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['width']  = $change_width;
          $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['height'] = $change_height;
          $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['filename'] = $change_filename;
          $PHORUM['mod_user_image_gallery']['image_info'][$file_id]['filesize'] = $change_filesize;           // in bytes
        endif;
        phorum_db_update_settings(array('mod_user_image_gallery' => $PHORUM['mod_user_image_gallery']));

      endif;
      // do this whether file was altered or not:
      $handle->clean();                  // Delete the original temp file.

      // set-up redirect
      // $file_id = (int)$_POST['file_id'];     // already set
      // $next_screen = $_POST['screen'];       // ALSO already set -- I guess I didn't have to do anything

    else:    // if ($function !== 'nothing'): else:

      // Gather additional information
      // Some image-manipulation functions require us to come back and ask the user a question or two
      // This is the first stop: If needed, set the "additional" parameter to tell manage.tpl to display the
      //  questions that need answering instead of the regular menu

      $file_id = (int)$_POST['file_id'];     // id of image to work on
      $additional = '';
      foreach (array_keys($_POST) as $key):
        switch ($key):
        case 'watermark1':
          $additional = 'watermark';
          break;
    //       case '':
    //         $additional = '';
    //         break;
        default:
          // do nothing
        endswitch;
      endforeach;

    endif;   // end if ($function !== 'nothing'): else:


// endswitch; // fake goto will go here
