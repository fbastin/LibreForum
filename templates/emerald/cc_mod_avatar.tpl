{IF Message}
<div class="PhorumUserError">{Message}</div>
{/IF}

<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->mod_avatar->AvatarOptions}</div>
<div class="PhorumStdBlock" style="text-align: left;">
<form method="post" action="{mod_avatar->url}">
{POST_VARS}
<input type="checkbox" name="disable_avatar_display" value="1"{IF mod_avatar->disable_avatar_display} CHECKED{/IF} /> {LANG->mod_avatar->BlockAvatars} 
<br /><input type="submit" class="PhorumSubmit" name="disable_submit" value="{LANG->SaveChanges}" />
</form>
</div>
</div>
<br />
<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->mod_avatar->EditAvatars}</div>
<div class="PhorumStdBlock" style="text-align: left;">
{LANG->mod_avatar->ListOfAvatars} (<a href="{URL->CC9}">{LANG->EditMyFiles}</a>).
<br /><br />{LANG->mod_avatar->MaxDimensions}({mod_avatar->max_width} x {mod_avatar->max_height}).
<br />{LANG->mod_avatar->ValidFileTypes} {mod_avatar->valid_file_types}.
<br /><br />{LANG->mod_avatar->SetLabel}
<table width="90%" cellspacing="5" border="0" style="text-align: left;">
<form method="post" action="{mod_avatar->url}">
{POST_VARS}
<tr><th>{LANG->mod_avatar->Avatar}</th><th>{LANG->Default}</th>{IF mod_avatar->enable_namedposting}<th>{LANG->mod_avatar->AlternateDefault}</th>{/IF}<th>{LANG->mod_avatar->Label}</th></tr>
{LOOP mod_avatar_filelist}
<tr>
<td><img src="{mod_avatar_filelist->url}"></td>
<td><input type="radio" name="default_avatar" value="{mod_avatar_filelist->fileid}"{IF mod_avatar_filelist->default} CHECKED{/IF} /></td>
{IF mod_avatar->enable_namedposting}
<td><input type="radio" name="alternate_default_avatar" value="{mod_avatar_filelist->fileid}"{IF mod_avatar_filelist->alternate} CHECKED{/IF} /></td>
{/IF}
<td><input type="text" name="label[{mod_avatar_filelist->fileid}]" value="{mod_avatar_filelist->label}" size="25" maxlength="30" /></td>
</tr>
{/LOOP mod_avatar_filelist}
<tr><td colspan='3'><input type="submit" class="PhorumSubmit" name="submit" value="{LANG->SaveChanges}" /> <input type="reset" class="PhorumSubmit" value="{LANG->mod_avatar->ResetForm}" /></td></tr>
</form>
</table>
</div>
