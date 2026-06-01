<!-- BEGIN TEMPLATE index_new.tpl -->

<table cellspacing="0" class="forumlist fullwidth">
{! Hack for "New Messages Icon" mod }
<?php if(isset($PHORUM['hooks']['scriptmonkeys_custom_hook_1'])) { phorum_hook('scriptmonkeys_custom_hook_1','sm2'); } else { ?>
    {LOOP FORUMS}
        {IF FORUMS->level 0}
            <tr>
                {IF FORUMS->forum_id FORUMS->vroot}
                    <th align="left">
                        <i class="li-folder"></i>
                        {LANG->Forums}
                    </th>
                {ELSE}
                    <th align="left">
                        <i class="li-folder"></i>
                        <b><a href="{FORUMS->URL->LIST}">{FORUMS->name}</a></b>
                    </th>
                {/IF}
                <th>{LANG->Threads}</th>
                <th>{LANG->Posts}</th>
                <th align="left">{LANG->LastPost}</th>
            </tr>
        {ELSE}
            <tr>
                {IF FORUMS->folder_flag}
                    <td colspan="4">
                        <i class="li-folder"></i>
                        <a href="{FORUMS->URL->INDEX}">{FORUMS->name}</a><p>{FORUMS->description}</p>
                    </td>
                {ELSE}
                    <td width="55%">
                        <b><span class="h3"><a href="{FORUMS->URL->LIST}">{FORUMS->name}</a></b>{IF FORUMS->new_message_check}&nbsp;&nbsp;<span class="new-indicator">({LANG->NewMessages})</span>{/IF}</span class="h3">
                        <p>{FORUMS->description}</p>
                        {IF FORUMS->URL->MARK_READ}<a class="icon" href="{FORUMS->URL->MARK_READ}"><i class="li-tag"></i> {LANG->MarkForumRead}</a>&nbsp;&nbsp;&nbsp;{/IF}
                        {IF FORUMS->URL->FEED}<a class="icon" href="{FORUMS->URL->FEED}"><i class="li-rss"></i> {FEED}</a>{/IF}
                    </td>
                    <td align="center" width="12%" nowrap="nowrap">
                        {FORUMS->thread_count}
                        {IF FORUMS->new_threads}
                            (<span class="new-flag">{FORUMS->new_threads} {LANG->newflag}</span>)
                        {/IF}
                    </td>
                    <td align="center" width="12%" nowrap="nowrap">
                        {FORUMS->message_count}
                        {IF FORUMS->new_messages}
                            (<span class="new-flag">{FORUMS->new_messages} {LANG->newflag}</span>)
                        {/IF}
                    </td>
                    <td width="21%" nowrap="nowrap">
                        {FORUMS->last_post}
                    </td>
                {/IF}
            </tr>
        {/IF}
    {/LOOP FORUMS}
<?php } ?> {! Hack for "New Messages Icon" mod }
</table>
<!-- END TEMPLATE index_new.tpl -->
