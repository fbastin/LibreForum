<!-- BEGIN TEMPLATE read_threads.tpl -->
<div class="nav">
    <div class="nav-right">
        <a class="icon" href="{MESSAGE->URL->PREV}"><i class="li-arrow-left"></i> {LANG->PreviousMessage}</a>
        <a class="icon" href="{MESSAGE->URL->NEXT}"><i class="li-arrow-right"></i> {LANG->NextMessage}</a>
    </div>
    {IF URL->INDEX}<a class="icon" href="{URL->INDEX}"><i class="li-folder"></i> {LANG->ForumList}</a>{/IF}
    <a class="icon" href="{URL->LIST}"><i class="li-clock"></i> {LANG->MessageList}</a>
    <a class="icon" href="{URL->POST}"><i class="li-msg-add"></i> {LANG->NewTopic}</a>
    <a class="icon" href="{URL->PRINTVIEW}" target="_blank"><i class="li-printer"></i> {LANG->PrintView}</a>
</div>

<div class="message">

    <div class="generic">

        <table border="0" cellspacing="0">
            <tr>

                 {IF MESSAGE->user_avatar}
                   <td style="padding-right:10px">
                       <img src="{MESSAGE->user_avatar}" alt="avatar"
                        {IF MESSAGE->user_avatar_w}
                          style="width:{MESSAGE->user_avatar_w}px;
                                 height:{MESSAGE->user_avatar_h}px"
                        {/IF} />
                   </td>
                 {/IF}

                <td width="100%">
                    <div class="message-author icon-user">
                        {IF MESSAGE->URL->PROFILE}<a href="{MESSAGE->URL->PROFILE}">{/IF}{MESSAGE->author}{IF MESSAGE->URL->PROFILE}</a>{/IF}
                        {IF MESSAGE->URL->PM}<small>[ <a href="{MESSAGE->URL->PM}">{LANG->PrivateReply}</a> ]</small>{/IF}
                    </div>
                    <small>{MESSAGE->datestamp}</small>
                </td>
                <td class="message-user-info" nowrap="nowrap">
                    {IF MESSAGE->user->admin}
                        <strong>{LANG->Admin}</strong><br />
                    {ELSEIF MESSAGE->moderator_post}
                        <strong>{LANG->Moderator}</strong><br />
                    {/IF}
                    {IF MESSAGE->ip}
                        {LANG->IP}: {MESSAGE->ip}<br />
                    {/IF}
			{IF MESSAGE->user->city}
			    {MESSAGE->user->city},
			    {MESSAGE->user->country}
			    <br/>
			{/IF}
                    {IF MESSAGE->user}
                        {LANG->DateReg}: {MESSAGE->user->date_added}<br />
                        {LANG->Posts}: {MESSAGE->user->posts}
                    {/IF}
                </td>
            </tr>
        </table>
    </div>

    <div class="message-body">
        {IF MESSAGE->is_unapproved}
            <div class="warning">
                {LANG->UnapprovedMessage}
            </div>
        {/IF}

        {MESSAGE->body}
        {IF MESSAGE->URL->CHANGES}
            (<a href="{MESSAGE->URL->CHANGES}">{LANG->ViewChanges}</a>)
        {/IF}
        <div class="message-options">
            {IF MESSAGE->edit 1}
                {IF MODERATOR false}
                    <a class="icon" href="{MESSAGE->URL->EDIT}"><i class="li-pencil"></i> {LANG->EditPost}</a>
                {/IF}
            {/IF}
            <a class="icon" href="{MESSAGE->URL->REPLY}"><i class="li-msg-add"></i> {LANG->Reply}</a>
            <a class="icon" href="{MESSAGE->URL->QUOTE}"><i class="li-msg-add"></i> {LANG->QuoteMessage}</a>
            {IF MESSAGE->URL->REPORT}<a class="icon" href="{MESSAGE->URL->REPORT}"><i class="li-alert"></i> {LANG->Report}</a>{/IF}
        </div>

        {IF MESSAGE->attachments}
            <div class="attachments">
                {LANG->Attachments}:<br/>
                {LOOP MESSAGE->attachments}
                    <a href="{MESSAGE->attachments->url}">{LANG->AttachOpen}</a> | <a href="{MESSAGE->attachments->download_url}">{LANG->AttachDownload}</a> -
                    {MESSAGE->attachments->name}
                    ({MESSAGE->attachments->size})<br/>
                {/LOOP MESSAGE->attachments}
            </div>
        {/IF}

        {IF MODERATOR true}
            <div class="message-moderation">
                {IF MESSAGE->threadstart false}
                    <a class="icon" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGE->URL->DELETE_MESSAGE}';"><i class="li-trash"></i> {LANG->DeleteMessage}</a>
                    <a class="icon" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGE->URL->DELETE_THREAD}';"><i class="li-trash"></i> {LANG->DelMessReplies}</a>
                    <a class="icon" href="{MESSAGE->URL->SPLIT}"><i class="li-split"></i> {LANG->SplitThread}</a>
                {/IF}
                {IF MESSAGE->is_unapproved}
                    <a class="icon" href="{MESSAGE->URL->APPROVE}"><i class="li-check"></i> {LANG->ApproveMessage}</a>
                {ELSE}
                    <a class="icon" href="{MESSAGE->URL->HIDE}"><i class="li-trash"></i> {LANG->HideMessage}</a>
                {/IF}
                <a class="icon" href="{MESSAGE->URL->EDIT}"><i class="li-pencil"></i> {LANG->EditPost}</a>
            </div>
        {/IF}

    </div>

</div>

<div class="nav">
    {IF MODERATOR true}
        <div class="nav-right">
            <a class="icon" href="{TOPIC->URL->MERGE}"><i class="li-merge"></i> {LANG->MergeThread}</a>
            {IF TOPIC->closed false}
                <a class="icon" href="{TOPIC->URL->CLOSE}"><i class="li-ban"></i> {LANG->CloseThread}</a>
            {ELSE}
                <a class="icon" href="{TOPIC->URL->REOPEN}"><i class="li-eye"></i> {LANG->ReopenThread}</a>
            {/IF}
            <a class="icon" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{TOPIC->URL->DELETE_THREAD}';"><i class="li-trash"></i> {LANG->DeleteThread}</a>
            {IF TOPIC->URL->MOVE}<a class="icon" href="{TOPIC->URL->MOVE}"><i class="li-move"></i> {LANG->MoveThread}</a>{/IF}
        </div>
    {/IF}

    {IF URL->MARKTHREADREAD}
        <a class="icon" href="{URL->MARKTHREADREAD}"><i class="li-tag"></i> {LANG->MarkThreadRead}</a>
    {/IF}
    {IF TOPIC->URL->FOLLOW}
        <a class="icon" href="{TOPIC->URL->FOLLOW}"><i class="li-msg-add"></i> {LANG->FollowThread}</a>
    {/IF}
    {IF URL->FEED}
        <a class="icon" href="{URL->FEED}"><i class="li-rss"></i> {FEED}</a>
    {/IF}
</div>

<table cellspacing="0" class="list">
    <tr class="heading">
        <th align="left">
            {LANG->Subject}
        </th>
        <th align="left" nowrap="nowrap">{LANG->Author}</th>
        <?php $columncount = 3; ?>
        {IF VIEWCOUNT_COLUMN}
          <th>{LANG->Views}</th>
          <?php $columncount += 1; ?>
        {/IF}
        <th align="left" nowrap="nowrap">{LANG->Posted}</th>
        <?php $columncount += 1; ?>
        {IF MODERATOR true}
            <?php /* No moderator buttons in this view */ ?>
        {/IF}
    </tr>

    {VAR first_message "true"}       <?php /* first message altogether */ ?>

    {LOOP MESSAGES}

    {IF MESSAGES->parent_id 0}
            {VAR firstclass "first"}       <?php /* first message of this thread */ ?>
            {IF first_message "false"}
                <tr><td class="{altclass} spacer" colspan=<?php echo $columncount; ?> height=5></td></tr> <?php /* spacer */ ?>
            {/IF}
            <?php /* btw, use of "altclass" above MUST come before altclass is changed in the IF statement below */ ?>
            {IF altclass "alt"}
                {VAR altclass "not_alt"}
            {ELSE}
            {VAR altclass "alt"}
            {/IF}
    {ELSE}
            {VAR firstclass "not_first"}
    {/IF}

    {IF MESSAGES->parent_id 0}
        {IF MESSAGES->sort PHORUM_SORT_STICKY}
            {IF MESSAGES->new}
                {VAR icon "flag_red"}
                {VAR alt LANG->NewMessage}
            {ELSE}
                {VAR icon "bell"}
                {VAR alt LANG->Sticky}
            {/IF}
            {VAR title LANG->Sticky}
        {ELSEIF MESSAGES->moved}
            {VAR icon "page_go"}
            {VAR title LANG->MovedSubject}
            {VAR alt LANG->MovedSubject}
        {ELSEIF MESSAGES->new}
            {VAR icon "flag_red"}
            {VAR title LANG->NewMessage}
	        {VAR alt LANG->NewMessage}
        {ELSE}
            {VAR icon "comment"}
            {VAR title ""}
	        {VAR alt ""}
        {/IF}
    {ELSEIF MESSAGES->new}
        {VAR icon "flag_red"}
        {VAR title LANG->New}
    {ELSE}
        {VAR icon "bullet_black"}
        {VAR title ""}
    {/IF}

    {IF MESSAGES->new}
        {VAR newclass "message-new"}
    {ELSE}
        {VAR newclass ""}
    {/IF}

    {VAR currentclass ""}
    {IF MESSAGES->message_id MESSAGE->message_id}
        {! This is the current message - override some stuff that was just set a second ago }
        {VAR currentclass "current"}
        {VAR icon "bullet_go"}
    {/IF}

    <tr>
        <td width="65%" class="{altclass} {firstclass} {currentclass}">
            <span class="h4" style="padding-left: {MESSAGES->indent_cnt}px; display: block;">
                <img src="{URL->TEMPLATE}/images/{icon}.png" class="icon1616" alt="{alt}" title="{title}" />
            <a href="{MESSAGES->URL->READ}" class="{newclass}" title="{title}">{MESSAGES->subject}</a>
                {IF MESSAGES->meta->attachments}<img src="{URL->TEMPLATE}/images/attach.png" class="icon1616" title="{LANG->Attachments}"  alt="{LANG->Attachments}" /> {/IF}
            {IF MESSAGES->sort PHORUM_SORT_STICKY}<small>({MESSAGES->thread_count} {LANG->Posts})</small>{/IF}
            </span class="h4">
        </td>
        <td width="10%" class="{altclass} {firstclass} {currentclass}" nowrap="nowrap">{IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}{MESSAGES->author}{IF MESSAGES->URL->PROFILE}</a>{/IF}</td>
        {IF VIEWCOUNT_COLUMN}
            <td align="center" width="10%" class="{altclass} {firstclass} {currentclass}" nowrap="nowrap">{MESSAGES->viewcount}</td>
        {/IF}
        <td width="15%" class="{altclass} {firstclass} {currentclass}" nowrap="nowrap">{MESSAGES->datestamp}</td>
    {IF MODERATOR true}
        <?php /* No moderator buttons in this view */ ?>
    {/IF}
    </tr>
        {VAR first_message "false"}
    {/LOOP MESSAGES}
    <tr><td class="{altclass} spacer {currentclass}" colspan=<?php echo $columncount; ?> height=5></td></tr> <?php /* copy of spacer */ ?>
</table>
<br />
<br />
<br />
<!-- END TEMPLATE read_threads.tpl -->
