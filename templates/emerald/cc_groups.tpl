{IF Message}
  <div class="PhorumUserError">{Message}</div>
{/IF}

<br/>

<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->JoinAGroup}</div>

{IF PROFILE->CONFIRMED}
<div class="PhorumStdBlock" style="text-align: left;">
  {LANG->JoinGroupDescription}
  <br/>
  <form method="POST" action="{GROUP->url}">
  {POST_VARS}
    <select name="joingroup">
      <option value="0">&nbsp;</option>
      {LOOP JOINGROUP}
        <option value="{JOINGROUP->group_id}">{JOINGROUP->name}</option>
      {/LOOP JOINGROUP}
    </select>
    <input type="submit" value="{LANG->Join}" />
  </form>
</div><br />
<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->GroupMembership}</div>
<div class="PhorumStdBlock" style="text-align: left;">
  <table class="PhorumFormTable" cellspacing="0" border="0">
    <tr>
      <th>{LANG->Group}</th>
      <th>{LANG->Permission}</th>
    </tr>
    {LOOP Groups}
      <tr>
        <td>{Groups->groupname}&nbsp;&nbsp;</td>
        <td>{Groups->perm}</td>
      </tr>
    {/LOOP Groups}
  </table>
</div>
{ELSE}
<div class="PhorumStdBlock" style="text-align: left;">
Vous devez être inscrit depuis 7 jours au moins et avoir posté un minimum de 5 messages pour accéder aux groupes.
</div>
{/IF}