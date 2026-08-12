<!-- BEGIN TEMPLATE pm_list.tpl -->
<div class="nav">
{INCLUDE "paging"}
<!--  DOES NOT WORK!
<form action="{URL->ACTION}" method="get">
<input type="hidden" name="action" value="list" />
 <input type="hidden" name="forum_id" value="{FORUM_ID}" />
  <input type="hidden" name="folder_id" value="{FOLDER_ID}" />
        <input type="text" name="search" value="{SAFE_SEARCH}" />
        <input type="submit" value="{LANG->Search}" />
    </form>
-->
</div>
<form action="{URL->ACTION}" method="post" id="phorum-pm-list">
    {POST_VARS}
    <input type="hidden" name="action" value="list" />
    <input type="hidden" name="folder_id" value="{FOLDER_ID}" />
    {IF FOLDER_IS_INCOMING}
        {INCLUDE "pm_list_incoming"}
    {ELSE}
        {INCLUDE "pm_list_outgoing"}
    {/IF}
    <!-- CONTINUE TEMPLATE list.tpl -->
</form>
<!-- END TEMPLATE pm_list.tpl -->
