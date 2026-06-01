<!-- BEGIN TEMPLATE list.tpl -->
<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE list.tpl -->
    {IF URL->INDEX}<a class="icon" href="{URL->INDEX}"><i class="li-folder"></i> {LANG->ForumList}</a>{/IF}
    <a class="icon" href="{URL->POST}"><i class="li-msg-add"></i> {LANG->NewTopic}</a>
    {IF URL->MARK_READ}
        <a class="icon" href="{URL->MARK_READ}"><i class="li-tag"></i> {LANG->MarkForumRead}</a>
    {/IF}
    {IF URL->FEED}
        <a class="icon" href="{URL->FEED}"><i class="li-rss"></i> {FEED}</a>
    {/IF}
</div>

<table cellspacing="0" class="list">
    <colgroup>
        <col class="col-icon" />
        <col class="col-subject" />
        {IF VIEWCOUNT_COLUMN}
            <col class="col-views" />
        {/IF}
        <col class="col-posts" />
        <col class="col-last" />
        {IF MODERATOR true}
            <col class="col-mod" />
        {/IF}
    </colgroup>
    <tr>
        <th align="left">&nbsp;</th>
        <th align="left">
            {LANG->Subject}
        </th>
        {IF VIEWCOUNT_COLUMN}
          <th>{LANG->Views}</th>
        {/IF}
        <th nowrap="nowrap">{LANG->Posts}</th>
        <th align="left" nowrap="nowrap">{LANG->LastPost}</th>
        {IF MODERATOR true}
            <th nowrap="nowrap">{LANG->Moderate}</th>
        {/IF}
    </tr>

    {LOOP MESSAGES}
    {IF altclass ""}
        {VAR altclass "alt"}
    {ELSE}
        {VAR altclass ""}
    {/IF}

    {IF MESSAGES->sort PHORUM_SORT_STICKY}
        {IF MESSAGES->new}
            {VAR icon "bell"}
            {VAR alt LANG->NewMessage}
        {ELSE}
            {VAR icon "flag"}
            {VAR alt LANG->Sticky}
        {/IF}
        {VAR title LANG->Sticky}
    {ELSEIF MESSAGES->moved}
        {VAR icon "arrow-right"}
        {VAR title LANG->MovedSubject}
        {VAR alt LANG->MovedSubject}
    {ELSEIF MESSAGES->new}
        {VAR icon "bell"}
        {VAR title LANG->NewMessage}
        {VAR alt LANG->NewMessage}
    {ELSE}
        {VAR icon "msg"}
        {VAR title ""}
        {VAR alt ""}
    {/IF}

    {IF MESSAGES->new}
        {VAR newclass "message-new"}
    {ELSE}
        {VAR newclass ""}
    {/IF}

    <tr>

        <td class="{altclass}"><a href="{IF MESSAGES->new}{MESSAGES->URL->NEWPOST}{ELSE}{MESSAGES->URL->READ}{/IF}" title="{title}"><i class="li-{icon}"></i></a></td>
        <td class="{altclass}">
            <span class="h4">
                <a href="{MESSAGES->URL->READ}" class="{newclass}" title="{title}">{MESSAGES->subject}</a>
                {IF MESSAGES->meta->attachments}<i class="li-file-text" title="{LANG->Attachments}"></i> {/IF}
                {IF MESSAGES->pages}&nbsp;<small>&nbsp;({LANG->Pages}:&nbsp;{MESSAGES->pages})</small>{/IF}
            </span class="h4">
            {LANG->by} {IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}{MESSAGES->author}{IF MESSAGES->URL->PROFILE}</a>{/IF}
        </td>

        {IF VIEWCOUNT_COLUMN}
            <td align="center" class="{altclass}" nowrap="nowrap">
                {IF MESSAGES->moved}
                    &nbsp;
                {ELSE}
                    {MESSAGES->viewcount}
                {/IF}
            </td>
        {/IF}

        {IF MESSAGES->moved}
            <td class="{altclass}">&nbsp;</td>
            <td align="left" class="{altclass}" nowrap="nowrap">{LANG->MovedSubject}</td>
        {ELSE}

            <td align="center" class="{altclass}" nowrap="nowrap">{MESSAGES->thread_count}</td>
            <td class="{altclass}" nowrap="nowrap">{MESSAGES->lastpost}<br /><a href="{MESSAGES->URL->LAST_POST}">{LANG->LastPostLink}</a> {LANG->by} {IF MESSAGES->URL->RECENT_AUTHOR_PROFILE}<a href="{MESSAGES->URL->RECENT_AUTHOR_PROFILE}">{/IF}{MESSAGES->recent_author}{IF MESSAGES->URL->RECENT_AUTHOR_PROFILE}</a>{/IF}</td>

        {/IF}

        {IF MODERATOR true}
            <td align="right" class="{altclass}" nowrap="nowrap">
                {IF MESSAGES->moved}
                    <a title="{LANG->DeleteMessage}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->URL->DELETE_THREAD}';"><i class="li-trash"></i></a>
                {ELSE}
                    {IF MESSAGES->URL->MOVE}
                        <a title="{LANG->MoveThread}" href="{MESSAGES->URL->MOVE}"><i class="li-arrow-right"></i></a>
                    {/IF}
                    <a title="{LANG->MergeThread}" href="{MESSAGES->URL->MERGE}"><i class="li-link"></i></a>
                    <a title="{LANG->DeleteThread}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->URL->DELETE_THREAD}';"><i class="li-trash"></i></a>
                {/IF}
            </td>
        {/IF}

    </tr>
  {/LOOP MESSAGES}
</table>
<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE list.tpl -->
</div>
<br />
<!-- END TEMPLATE list.tpl -->
