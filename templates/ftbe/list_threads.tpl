<!-- BEGIN TEMPLATE list_threads.tpl -->

<?php /* On the read_threads template, after a message is displayed, the system presents a threaded view of the thread this message is a part of. */ ?>
<?php /* In other words, the following section needs to match between the list_threads template and the read_threads template. */ ?>
<?php /* Also, list_threads.tpl and list.tpl (which lists only thread-starters) need to be very similar. */ ?>
<?php /* Also, the announcements mod ( /forum/mods/announcements/templates/templatename/announcements.tpl ) needs to be very similar to list.tpl */ ?>
<?php /* sync text: */ ?>
<?php /* Lorem ipsum dolor sit amet, consectetur adipiscing elit. */ ?>
<?php /* Nunc et orci sit amet lorem varius adipiscing. */ ?>
<?php /* Nulla facilisis nulla vitae metus. */ ?>
<?php /* Curabitur rhoncus dolor a massa. */ ?>

<?php /* Actually, this section will be different */ ?>

<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE list_threads.tpl -->
    {IF URL->INDEX}
        <a class="icon" href="{URL->INDEX}"><i class="li-folder"></i> {LANG->ForumList}</a>
    {/IF}
    <a class="icon" href="{URL->POST}"><i class="li-msg-add"></i> {LANG->NewTopic}</a>
    {IF URL->MARK_READ}
        <a class="icon" href="{URL->MARK_READ}"><i class="li-tag"></i> {LANG->MarkForumRead}</a>
    {/IF}
    {IF URL->FEED}
        <a class="icon" href="{URL->FEED}"><i class="li-rss"></i> {FEED}</a>
    {/IF}
</div>

<?php /* sync text: */ ?>
<?php /* Donec adipiscing risus in neque. */ ?>
<?php /* Nam lobortis ultricies massa. */ ?>
<?php /* Curabitur accumsan ligula vel velit. */ ?>
<?php /* Nulla dignissim purus id nisl. */ ?>

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
            <th nowrap="nowrap">{LANG->Moderate}</th>
            <?php $columncount += 1; ?>
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

    <tr>
        <td width="65%" class="{altclass} {firstclass}">
        <span class="h4" style="padding-left: {MESSAGES->indent_cnt}px; display: block;">
            <img src="{URL->TEMPLATE}/images/{icon}.png" class="icon1616" alt="{alt}" title="{title}" />
            <a href="{MESSAGES->URL->READ}" class="{newclass}" title="{title}">{MESSAGES->subject}</a>
            {IF MESSAGES->meta->attachments}<img src="{URL->TEMPLATE}/images/attach.png" class="icon1616" title="{LANG->Attachments}"  alt="{LANG->Attachments}" /> {/IF}
            {IF MESSAGES->sort PHORUM_SORT_STICKY}<small>({MESSAGES->thread_count} {LANG->Posts})</small>{/IF}
        </span>
    </td>
        <td width="10%" class="{altclass} {firstclass}" nowrap="nowrap">{IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}{MESSAGES->author}{IF MESSAGES->URL->PROFILE}</a>{/IF}</td>
    {IF VIEWCOUNT_COLUMN}
            <td align="center" width="10%" class="{altclass} {firstclass}" nowrap="nowrap">{MESSAGES->viewcount}</td>
    {/IF}
        <td width="15%" class="{altclass} {firstclass}" nowrap="nowrap">{MESSAGES->datestamp}</td>
    {IF MODERATOR true}
            <td width="1%" class="{altclass} {firstclass}" nowrap="nowrap">
            {IF MESSAGES->moved}
                <a title="{LANG->DeleteThread}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->URL->DELETE_MESSAGE}';"><img src="{URL->TEMPLATE}/images/delete.png" class="icon1616" alt="{LANG->DeleteThread}" /></a>
            {ELSE}
                {IF MESSAGES->threadstart true}
                    {IF MESSAGES->URL->MOVE}
                        <a title="{LANG->MoveThread}" href="{MESSAGES->URL->MOVE}"><img src="{URL->TEMPLATE}/images/page_go.png" class="icon1616" alt="{LANG->MoveThread}" /></a>
                    {/IF}
                    <a title="{LANG->MergeThread}" href="{MESSAGES->URL->MERGE}"><img src="{URL->TEMPLATE}/images/arrow_join.png" alt="{LANG->MergeThread}" /></a>
                    <a title="{LANG->DeleteThread}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->URL->DELETE_THREAD}';"><img src="{URL->TEMPLATE}/images/delete.png" class="icon1616" alt="{LANG->DeleteThread}" /></a>
                {ELSE}
                    <a title="{LANG->DeleteMessage}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->URL->DELETE_MESSAGE}';"><img src="{URL->TEMPLATE}/images/delete.png" class="icon1616" alt="{LANG->DeleteMessage}" /></a>
                {/IF}
            {/IF}
        </td>
    {/IF}
    </tr>
        {VAR first_message "false"}
    {/LOOP MESSAGES}
    <tr><td class="{altclass} spacer" colspan=<?php echo $columncount; ?> height=5></td></tr> <?php /* copy of spacer */ ?>
</table>
<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE list_threads.tpl -->
</div>
<br />
<!-- END TEMPLATE list_threads.tpl -->
