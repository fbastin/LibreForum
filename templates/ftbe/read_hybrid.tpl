<!-- BEGIN TEMPLATE read_hybrid.tpl -->
<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE read_hybrid.tpl -->
    {IF URL->INDEX}<a class="icon" href="{URL->INDEX}"><i class="li-folder"></i> {LANG->ForumList}</a>{/IF}
    {IF FORUM_ID}<a class="icon" href="{URL->LIST}"><i class="li-clock"></i> {LANG->MessageList}</a>{/IF}
    {IF FORUM_ID}<a class="icon" href="{URL->POST}"><i class="li-msg-add"></i> {LANG->NewTopic}</a>{/IF}
    <a class="icon" href="{URL->PRINTVIEW}" target="_blank"><i class="li-printer"></i> {LANG->PrintView}</a>
</div>

{LOOP MESSAGES}

    {IF NOT MESSAGES->parent_id 0}
        <a name="msg-{MESSAGES->message_id}"></a>
    {/IF}

    <div class="message" style="padding-left: {MESSAGES->indent_cnt}px;">

        <div class="generic">

            <table border="0" cellspacing="0">
                <tr>

                   {IF MESSAGES->user_avatar}
                     <td style="padding-right:10px">
                         <img src="{MESSAGES->user_avatar}" alt="avatar"
                          {IF MESSAGES->user_avatar_w}
                            style="width:{MESSAGES->user_avatar_w}px;
                                   height:{MESSAGES->user_avatar_h}px"
                          {/IF} />
                     </td>
                   {/IF}

                    <td width="100%">
                        <div class="message-author icon-user">
                            {IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}{MESSAGES->author}{IF MESSAGES->URL->PROFILE}</a>{/IF}
                            {IF MESSAGES->URL->PM}<small>[ <a href="{MESSAGES->URL->PM}">{LANG->PrivateReply}</a> ]</small>{/IF}
                        </div>
                        <small>
                        <strong><a href="{MESSAGES->URL->READ}">{MESSAGES->subject}</a> {IF MESSAGES->new}<span class="new-flag">{MESSAGES->new}</span>{/IF}</strong><br />
                        {MESSAGES->datestamp}
                        </small>
                    </td>
                    <td class="message-user-info" nowrap="nowrap">
                        {IF MESSAGES->user->admin}
                            <strong>{LANG->Admin}</strong><br />
                        {ELSEIF MESSAGES->moderator_post}
                            <strong>{LANG->Moderator}</strong><br />
                        {/IF}
                        {IF MESSAGES->ip}
                            {LANG->IP}: {MESSAGES->ip}<br />
                        {/IF}
                        {IF MESSAGES->user}
                            {LANG->DateReg}: {MESSAGES->user->date_added}<br />
                            {LANG->Posts}: {MESSAGES->user->posts}
                        {/IF}
                    </td>
                </tr>
            </table>
        </div>

        <div class="message-body">
            {IF MESSAGES->is_unapproved}
                <div class="warning">
                    {LANG->UnapprovedMessage}
                </div>
            {/IF}

            {MESSAGES->body}
            {IF MESSAGES->URL->CHANGES}
                (<a href="{MESSAGES->URL->CHANGES}">{LANG->ViewChanges}</a>)
            {/IF}
            <div class="message-options">
                {IF MESSAGES->edit 1}
                    {IF MODERATOR false}
                        <a class="icon" href="{MESSAGES->URL->EDIT}"><i class="li-pencil"></i> {LANG->EditPost}</a>
                    {/IF}
                {/IF}
                <a class="icon" href="{MESSAGES->URL->REPLY}"><i class="li-msg-add"></i> {LANG->Reply}</a>
                <a class="icon" href="{MESSAGES->URL->QUOTE}"><i class="li-msg-add"></i> {LANG->QuoteMessage}</a>
                {IF MESSAGES->URL->REPORT}<a class="icon" href="{MESSAGES->URL->REPORT}"><i class="li-alert"></i> {LANG->Report}</a>{/IF}
{HOOK "social_share" MESSAGE}
            </div>

            {IF MESSAGES->attachments}
                <div class="attachments">
                    {LANG->Attachments}:<br/>
                    {LOOP MESSAGES->attachments}
                        <a href="{MESSAGES->attachments->url}">{LANG->AttachOpen}</a> | <a href="{MESSAGES->attachments->download_url}">{LANG->AttachDownload}</a> -
                        {MESSAGES->attachments->name}
                        ({MESSAGES->attachments->size})<br/>
                    {/LOOP MESSAGES->attachments}
                </div>
            {/IF}

            {IF MODERATOR true}
                <div class="message-moderation">
                    {IF MESSAGES->threadstart true}
                        <a class="icon" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->URL->DELETE_THREAD}';"><i class="li-trash"></i> {LANG->DelMessReplies}</a>
                    {ELSE}
                        <a class="icon" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->URL->DELETE_MESSAGE}';"><i class="li-trash"></i> {LANG->DeleteMessage}</a>
                        <a class="icon" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->URL->DELETE_THREAD}';"><i class="li-trash"></i> {LANG->DelMessReplies}</a>
                        <a class="icon" href="{MESSAGES->URL->SPLIT}"><i class="li-split"></i> {LANG->SplitThread}</a>
                    {/IF}
                    {IF MESSAGES->is_unapproved}
                        <a class="icon" href="{MESSAGES->URL->APPROVE}"><i class="li-check"></i> {LANG->ApproveMessage}</a>
                    {ELSE}
                        <a class="icon" href="{MESSAGES->URL->HIDE}"><i class="li-trash"></i> {LANG->HideMessage}</a>
                    {/IF}
                    <a class="icon" href="{MESSAGES->URL->EDIT}"><i class="li-pencil"></i> {LANG->EditPost}</a>
                </div>
            {/IF}

        </div>
    </div>
{/LOOP MESSAGES}

<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE read_hybrid.tpl -->
    <a class="icon" href="{URL->NEWERTHREAD}"><i class="li-arrow-left"></i> {LANG->NewerThread}</a>
    <a class="icon" href="{URL->OLDERTHREAD}"><i class="li-arrow-right"></i> {LANG->OlderThread}</a>
</div>

<div id="thread-options" class="nav">
    <a class="icon" href="{URL->PRINTVIEW}" target="_blank"><i class="li-printer"></i> {LANG->PrintView}</a>
    {IF URL->MARKTHREADREAD}
        <a class="icon" href="{URL->MARKTHREADREAD}"><i class="li-tag"></i> {LANG->MarkThreadRead}</a>
    {/IF}
    {IF TOPIC->URL->FOLLOW}
        <a class="icon" href="{TOPIC->URL->FOLLOW}"><i class="li-msg-add"></i> {LANG->FollowThread}</a>
    {/IF}
    {IF URL->FEED}
        <a class="icon" href="{URL->FEED}"><i class="li-rss"></i> {FEED}</a>
    {/IF}
    {IF MODERATOR true}
        <a class="icon" href="{TOPIC->URL->MERGE}"><i class="li-merge"></i> {LANG->MergeThread}</a>
        {IF TOPIC->closed false}
            <a class="icon" href="{TOPIC->URL->CLOSE}"><i class="li-ban"></i> {LANG->CloseThread}</a>
        {ELSE}
            <a class="icon" href="{TOPIC->URL->REOPEN}"><i class="li-eye"></i> {LANG->ReopenThread}</a>
        {/IF}
        <a class="icon" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{TOPIC->URL->DELETE_THREAD}';"><i class="li-trash"></i> {LANG->DeleteThread}</a>
        {IF TOPIC->URL->MOVE}<a class="icon" href="{TOPIC->URL->MOVE}"><i class="li-move"></i> {LANG->MoveThread}</a>{/IF}
{HOOK "social_share" TOPIC}
    {/IF}
</div>
<!-- END TEMPLATE read_hybrid.tpl -->
