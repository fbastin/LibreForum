{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}
{IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->allow_user_unsubscribe_self}
<form action="{URL->ACTION}" method="post">
    {POST_VARS}
    <input type="hidden" name="forum_subscriptions_form" value="settings" />
    <div class="generic">

        <dl>
              <dt>{LANG->forum_subscriptions->EnableSelfEmails}:&nbsp;</dt>
              <dd>
                  <select name="phorum_mod_forum_subscriptions_user_unsubscribe_setting_self">
                      <option value="yes" {IF PROFILE->phorum_mod_forum_subscriptions_user_unsubscribe_setting_self "yes"}selected="selected"{/IF}>{LANG->Yes}</option>
                      <option value="no" {IF PROFILE->phorum_mod_forum_subscriptions_user_unsubscribe_setting_self "no"}selected="selected"{/IF}>{LANG->No}</option>
                  </select>
              </dd>
        </dl>

        <div><input type="submit" value=" {LANG->Submit} " /></div>

    </div>

</form>
{/IF}
<form action="{URL->ACTION}" method="post">
  {POST_VARS}
  <input type="hidden" name="forum_subscriptions_form" value="subscriptions" />
  <table cellspacing="0" class="list" style="width:100%">
    <tr>
      <th align="left" width="60%">
        {LANG->forum_subscriptions->ForumSubscriptions}
      </th>
      <th align="left" width="20%">
        {LANG->forum_subscriptions->SubscriptionType}
      </th>
      <th align="left" width="20%">
        {LANG->forum_subscriptions->Frequency}
      </th>
    </tr>
    {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS_EMPTY}
    <tr>
      <td colspan="3">{LANG->forum_subscriptions->NoForumsToSelect}</td>
    </tr>
    {ELSE}
    <?php global $PHORUM; ?>
    {LOOP PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS}
    <tr>
      <td><strong>{PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->indent_spaces}{PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->name}</strong></td>
      <td>
        {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->folder_flag AND NOT PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->vroot PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->forum_id}&nbsp;
        {ELSE}
        {VAR fsub_current_vroot PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->vroot}
        {VAR fsub_current_forum PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->forum_id}
        <?php 
            if (!empty($PHORUM["DATA"]["PHORUM_MOD_FORUM_SUBSCRIPTIONS"]["ALL_FORUMS"][$PHORUM["DATA"]["fsub_current_vroot"]]["sub_type"])
                    && $PHORUM["DATA"]["fsub_current_forum"] != $PHORUM["DATA"]["fsub_current_vroot"]) {
                print $PHORUM["DATA"]["PHORUM_MOD_FORUM_SUBSCRIPTIONS"]["ALL_FORUMS"][$PHORUM["DATA"]["fsub_current_vroot"]]["sub_type"];
            } else {
        ?>
        <select name="phorum_mod_forum_subscriptions_forums[{PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->forum_id}][sub_type]">
          <option value="<?php print PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NONE; ?>">{LANG->forum_subscriptions->Nothing}</option>
          {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->send_only_new_threads FALSE}
          <option value="<?php print PHORUM_MOD_FORUM_SUB_SUBSCRIBE_ALL; ?>" {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->sub_type PHORUM_MOD_FORUM_SUB_SUBSCRIBE_ALL}selected="selected"{/IF}>{LANG->forum_subscriptions->ThreadsAndReplies}</option>
          <option value="<?php print PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NEW_THREAD; ?>" {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->sub_type PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NEW_THREAD}selected="selected"{/IF}>{LANG->forum_subscriptions->NewThreadsOnly}</option>
          {ELSE}
          <option value="<?php print PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NEW_THREAD; ?>" {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->sub_type PHORUM_MOD_FORUM_SUB_SUBSCRIBE_NEW_THREAD OR PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->sub_type PHORUM_MOD_FORUM_SUB_SUBSCRIBE_ALL}selected="selected"{/IF}>{LANG->forum_subscriptions->NewThreadsOnly}</option>
          {/IF}
        </select>
        <?php } ?>
        {/IF}
      </td>
      <td>
        {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->folder_flag AND NOT PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->vroot PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->forum_id}&nbsp;
        {ELSE}
        <?php 
            if (!empty($PHORUM["DATA"]["PHORUM_MOD_FORUM_SUBSCRIPTIONS"]["ALL_FORUMS"][$PHORUM["DATA"]["fsub_current_vroot"]]["frequency"])
                    && $PHORUM["DATA"]["fsub_current_forum"] != $PHORUM["DATA"]["fsub_current_vroot"]) {
                print $PHORUM["DATA"]["PHORUM_MOD_FORUM_SUBSCRIPTIONS"]["ALL_FORUMS"][$PHORUM["DATA"]["fsub_current_vroot"]]["frequency"];
            } else {
        ?>
        <select name="phorum_mod_forum_subscriptions_forums[{PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->forum_id}][frequency]">
          <option value="<?php print PHORUM_MOD_FORUM_SUB_FREQUENCY_NEVER; ?>">{LANG->forum_subscriptions->Never}</option>
          <option value="<?php print PHORUM_MOD_FORUM_SUB_FREQUENCY_IMMEDIATE; ?>" {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->frequency PHORUM_MOD_FORUM_SUB_FREQUENCY_IMMEDIATE}selected="selected"{/IF}>{LANG->forum_subscriptions->Immediate}</option>
          {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->allow_daily_digests}
          <option value="<?php print PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY; ?>" {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->frequency PHORUM_MOD_FORUM_SUB_FREQUENCY_DAILY}selected="selected"{/IF}>{LANG->forum_subscriptions->DailyDigests}</option>
          {/IF}
          {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->allow_weekly_digests}
          <option value="<?php print PHORUM_MOD_FORUM_SUB_FREQUENCY_WEEKLY; ?>" {IF PHORUM_MOD_FORUM_SUBSCRIPTIONS->FORUMS->frequency PHORUM_MOD_FORUM_SUB_FREQUENCY_WEEKLY}selected="selected"{/IF}>{LANG->forum_subscriptions->WeeklyDigests}</option>
          {/IF}
        </select>
        <?php } ?>
        {/IF}
      </td>
    </tr>
    {/LOOP FILES}
    <tr>
      <td><!-- --></td>
      <td colspan="2">
        <input type="submit" value=" {LANG->Submit} " />
      </td>
    </tr>
    {/IF}
  </table>
</form>

