{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}
{IF MESSAGES}
  {LOOP MESSAGES}
    <div class="{MESSAGES->type}">{MESSAGES->message}</div>
  {/LOOP MESSAGES}
{/IF}

<!-- cc_panel.tpl -->

  <form action="{URL->ACTION}" method="post" enctype="multipart/form-data">
      {POST_VARS}
      <input type="hidden" name="action" value="upload">
      <input type="hidden" name="screen" value="simple">

      <div class="generic">
          <dt>{LANG->mod_user_image_gallery->UploadHeadline}</dt>
          <dd>
            {IF FILE_SIZE_LIMIT}<div>{FILE_SIZE_LIMIT}</div>{/IF}
            {IF FILE_TYPE_LIMIT}<div>{FILE_TYPE_LIMIT}</div>{/IF}
            <div>{LANG->mod_user_image_gallery->ImageLimit}</div>
            <br />
            {IF FILES_LIMIT_REACHED}
              {LANG->mod_user_image_gallery->LimitReached}
            {ELSE}
              <input type="file" name="newfile1" size="50" class="uploadblank" /><br />
              <input type="submit" value="{LANG->Submit}" />
            {/IF}
          </dd>
      </div>

  </form>

  <br/>

<form action="{URL->ACTION}" method="post">
  {POST_VARS}
  <input type="hidden" name="action" value="bulk_edit">
  <input type="hidden" name="screen" value="simple">

  {IF NOT NUMBER_OF_FILES 0} {! AND PERMISSION->IMAGE_GALLERY_CREATE}
    <table cellspacing="0" class="list" style="width:100%">
      <tr>
        <th align="center" style="white-space:nowrap" colspan="{mod_user_image_gallery->display_columns_cc}">
          {LANG->Preview} ({LANG->mod_user_image_gallery->Thumbnail})
        </th>
      </tr>

      {VAR CURRCOL 0}
      {LOOP FILES}
      {IF CURRCOL 0}
        <tr>
        <?php $second_row='<tr>'; ?>
      {/IF}
          <td style="vertical-align:middle;text-align:center;" class="row1">
            <img src="{FILES->url}" {FILES->adjustment} border=0 />
          </td>
          <?php ob_start(); ?>
          <td style="vertical-align:bottom;text-align:center;" class="row2">
              {IF FILES->status}{FILES->status}<br />{/IF}
              {IF FILES->title}{FILES->title}<br />{/IF}
              <a href="{FILES->url}" target="_blank">{FILES->filename}</a><br/>
              {FILES->filesize} {IF FILES->dimensions}({FILES->dimensions}){/IF} <br/>
              <input type="submit" name="info{FILES->file_id}" value="{LANG->mod_user_image_gallery->Info}" />
              <input type="submit" name="manage{FILES->file_id}" value="{LANG->mod_user_image_gallery->Manage}" /><br />
              <input type="checkbox" name="delete[]" value="{FILES->file_id}" />{LANG->Delete}
          </td>
          <?php $second_row .= ob_get_contents(); ob_end_clean(); ?>
      {! VAR CURRCOL CURRCOL+1}
      <?php $PHORUM['DATA']['CURRCOL'] = $PHORUM['DATA']['CURRCOL']+1; ?>
      {IF CURRCOL mod_user_image_gallery->display_columns_cc}
        </tr>
        <?php print $second_row.'</tr>'; $second_row=''; ?>
        {VAR CURRCOL 0}
      {/IF}
      {/LOOP FILES}

      {IF NOT CURRCOL 0}
          <td colspan="<?php echo ( $PHORUM['DATA']['mod_user_image_gallery']['display_columns_cc'] - $PHORUM['DATA']['CURRCOL']  ); ?>" class="row1">
            &nbsp;
          </td>
        </tr>
        <?php print $second_row; ?>
          <td colspan="<?php echo ( $PHORUM['DATA']['mod_user_image_gallery']['display_columns_cc'] - $PHORUM['DATA']['CURRCOL']  ); ?>" class="row2">
            &nbsp;
          </td>
        </tr>
      {/IF}

    </table>
  {/IF}



<div class="nav">
    {IF MULTIPLE_PAGES}
      {INCLUDE 'paging'}
    {/IF}
</div>


<?php if (false): ?>
    <input type="checkbox" id="disable_image_gallery_display" name="disable_image_gallery_display" value="1" {IF mod_user_image_gallery->disable_image_gallery_display}checked="checked"{/IF} />
    <label for="disable_image_gallery_display">{LANG->mod_user_image_gallery->BlockImages}</label><br /><br />
<?php endif; ?>


  {LANG->mod_user_image_gallery->Total_files}: {TOTAL_FILES} ({LANG->mod_user_image_gallery->limit_of_}{mod_user_image_gallery->max_images})<br />
  {LANG->mod_user_image_gallery->Total_file_size}: {TOTAL_FILE_SIZE} ({LANG->mod_user_image_gallery->limit_of_}{mod_user_image_gallery->max_total_filesize}{LANG->mod_user_image_gallery->_KB})<br />
  <br />

<!--   <input type="submit" value="{LANG->SaveChanges}" /> -->
  <input type="submit" name="delete_checked" value="{LANG->mod_user_image_gallery->delete_checked}" class="gallery_button" />

</form>

<br />
<form action="{URL->ACTION}" method="post">
  {POST_VARS}
  <input type="hidden" name="action" value="nothing" />
  <input type="hidden" name="screen" value="multi" />
  <input type="submit" name="upload_multi" value="{LANG->mod_user_image_gallery->Click_here_to_upload_multiple_images}" class="gallery_button" />
</form>
<br />

<form action="{URL->my_gallery}" method="post">
  {POST_VARS}
  <input type="submit" value="{LANG->mod_user_image_gallery->see_your_gallery}" class="gallery_button" />
</form>
<br />
