<!-- BEGIN TEMPLATE pm_folders.tpl -->
<form action="{URL->ACTION}" method="post">
    {POST_VARS}
    <input type="hidden" name="action" value="folders" />
    <div class="generic">
        <span class="h4">{LANG->PMFolderCreate}</span class="h4">
        <input type="text" name="create_folder_name" value="{CREATE_FOLDER_NAME}" size="20" maxlength="20" />
        <input type="submit" name="create_folder" value="{LANG->Submit}" />
    </div>
</form>
{IF PM_USERFOLDERS}
    <form action="{URL->ACTION}" method="post">
        {POST_VARS}
        <input type="hidden" name="action" value="folders" />
        <div class="generic">
            <span class="h4">{LANG->PMFolderRename}</span class="h4">
            <select name="rename_folder_from" style="vertical-align: middle">
                <option value="">{LANG->PMSelectAFolder}</option>
                {LOOP PM_USERFOLDERS}
                    <option value="{PM_USERFOLDERS->id}">{PM_USERFOLDERS->name}</option>
                {/LOOP PM_USERFOLDERS}
            </select>
            {LANG->PMFolderRenameTo}
            <input type="text" name="rename_folder_to" value="{RENAME_FOLDER_NAME}" size="20" maxlength="20" />
            <input type="submit" name="rename_folder" value="{LANG->Submit}" />
        </div>
    </form>

    <form action="{URL->ACTION}" method="post">
        {POST_VARS}
        <input type="hidden" name="action" value="folders" />
        <div class="generic">
            <span class="h4">{LANG->PMFolderDelete}</span class="h4">
            <p>{LANG->PMFolderDeleteExplain}</p>
            {LANG->PMFolderDelete}
            <select name="delete_folder_target" style="vertical-align: middle">
                <option value="">{LANG->PMSelectAFolder}</option>
                {LOOP PM_USERFOLDERS}
                    <option value="{PM_USERFOLDERS->id}">{PM_USERFOLDERS->name}{IF PM_USERFOLDERS->total} ({PM_USERFOLDERS->total}){/IF}</option>
                {/LOOP PM_USERFOLDERS}
            </select>
            <input type="submit" name="delete_folder" value="{LANG->Submit}" onclick="return confirm('{LANG->PMFolderDeleteConfirm}')" />
        </div>
    </form>
{/IF}
<!-- END TEMPLATE pm_folders.tpl -->
