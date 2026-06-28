<!-- start /mods/user_list/templates/emerald/user_list_display.tpl -->
{IF LOGGEDIN}
<form method="get" action="addon.php" style="margin-bottom: 15px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
    <input type="hidden" name="0" value="0" />
    <input type="hidden" name="module" value="user_list" />
    <label for="user_search" style="font-weight: bold; margin-right: 5px;">Rechercher un membre :</label>
    <input type="text" id="user_search" name="search" value="{USER_LIST_SEARCH}" placeholder="Nom, pseudonyme..." style="padding: 5px; border: 1px solid #ccc; border-radius: 4px; min-width: 200px;" />
    <label style="margin-left: 10px; margin-right: 10px; font-size: 13px; font-weight: normal; cursor: pointer; vertical-align: middle;">
        <input type="checkbox" name="case_sensitive" value="1" {IF USER_LIST_CASE_SENSITIVE}checked="checked"{/IF} style="vertical-align: middle; margin-right: 3px;" />
        Sensible à la casse
    </label>
    <input type="submit" value="Rechercher" style="padding: 5px 10px; cursor: pointer;" />
    {IF USER_LIST_SEARCH}
        <a href="{URL->USER_LIST->All}" style="text-decoration: none; margin-left: 10px; padding: 5px 10px; background: #e0e0e0; color: #333; border-radius: 4px; font-size: 13px;">Effacer la recherche</a>
    {/IF}
</form>
<table cellspacing="0" class="list user_list">
    <tr>
        <!-- <td align="center"><a href="{URL->USER_LIST->All}">Tous</a></td> -->
        <td align="center"><a href="{URL->USER_LIST->number}">#</a></td>
        <td align="center"><a href="{URL->USER_LIST->A}">A</a></td>
        <td align="center"><a href="{URL->USER_LIST->B}">B</a></td>
        <td align="center"><a href="{URL->USER_LIST->C}">C</a></td>
        <td align="center"><a href="{URL->USER_LIST->D}">D</a></td>
        <td align="center"><a href="{URL->USER_LIST->E}">E</a></td>
        <td align="center"><a href="{URL->USER_LIST->F}">F</a></td>
        <td align="center"><a href="{URL->USER_LIST->G}">G</a></td>
        <td align="center"><a href="{URL->USER_LIST->H}">H</a></td>
        <td align="center"><a href="{URL->USER_LIST->I}">I</a></td>
        <td align="center"><a href="{URL->USER_LIST->J}">J</a></td>
        <td align="center"><a href="{URL->USER_LIST->K}">K</a></td>
        <td align="center"><a href="{URL->USER_LIST->L}">L</a></td>
        <td align="center"><a href="{URL->USER_LIST->M}">M</a></td>
        <td align="center"><a href="{URL->USER_LIST->N}">N</a></td>
        <td align="center"><a href="{URL->USER_LIST->O}">O</a></td>
        <td align="center"><a href="{URL->USER_LIST->P}">P</a></td>
        <td align="center"><a href="{URL->USER_LIST->Q}">Q</a></td>
        <td align="center"><a href="{URL->USER_LIST->R}">R</a></td>
        <td align="center"><a href="{URL->USER_LIST->S}">S</a></td>
        <td align="center"><a href="{URL->USER_LIST->T}">T</a></td>
        <td align="center"><a href="{URL->USER_LIST->U}">U</a></td>
        <td align="center"><a href="{URL->USER_LIST->V}">V</a></td>
        <td align="center"><a href="{URL->USER_LIST->W}">W</a></td>
        <td align="center"><a href="{URL->USER_LIST->X}">X</a></td>
        <td align="center"><a href="{URL->USER_LIST->Y}">Y</a></td>
        <td align="center"><a href="{URL->USER_LIST->Z}">Z</a></td>
    </tr>
</table>
<div class="nav">
    {INCLUDE 'paging'}
</div>
<table cellspacing="0" class="list user_list">
    <tr>
<!-- Member Number -->
        <th align="center" width="80" style="white-space: nowrap;">
            <a href="{URL->USER_LIST->SORT_BY_USER_ID}">No.&nbsp;{IF USER_LIST_SORT 'user_id'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a>
        </th>
<!-- Member -->
        <th align="center">
            <a href="{URL->USER_LIST->SORT_BY_USERNAME}">{LANG->Member}&nbsp;{IF USER_LIST_SORT 'username'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a>
        </th>
            {IF ENABLE_PM}
<!-- PM -->
                <th align="center">{LANG->PrivateReply}</th>
<!-- Buddy -->
                <th align="center">{LANG->Buddy}</th>
            {/IF} {! ENABLE_PM}
<!-- Posts -->
        <th align="center">
            <a href="{URL->USER_LIST->SORT_BY_POSTS}">{LANG->Posts}&nbsp;{IF USER_LIST_SORT 'posts'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a>
        </th>
<!-- Date Joined -->
        <th align="center">
            <a href="{URL->USER_LIST->SORT_BY_DATE_ADDED}">Date d'inscription&nbsp;{IF USER_LIST_SORT 'date_added'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a>
        </th>
<!-- Last Seen -->
        <th align="center">
            <a href="{URL->USER_LIST->SORT_BY_DATE_LAST_ACTIVE}">Dernière activité&nbsp;{IF USER_LIST_SORT 'date_last_active'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a>
        </th>
    </tr>

    {LOOP USERS}
    <tr>
<!-- Member Number -->
        <td align="center">
            {USERS->user_id}.
        </td>
<!-- Member -->
        <td align="center">
            {IF USERS->URL->PROFILE}<a href="{USERS->URL->PROFILE}">{/IF}
            {USERS->display_name}
            {IF USERS->URL->PROFILE}</a>{/IF}
        </td>
            {IF ENABLE_PM}
<!-- PM -->
                <td align="center">
                    [ <a href="{USERS->URL->PM}">{LANG->PrivateReply}</a> ]
                </td>
<!-- Buddy -->
                <td align="center">
                    {IF USERS->user_id USER->user_id}
                        (self)
                    {ELSEIF USERS->is_buddy}
                        ({LANG->Buddy})
                    {ELSE}
                        [ <a href="{USERS->URL->ADD_BUDDY}">{LANG->BuddyAdd}</a> ]
                    {/IF}
                </td>
            {/IF} {! ENABLE_PM}
<!-- Rank -->
        <!-- (later) -->
<!-- Posts -->
        <td align="center">
            {IF USERS->posts}
                <a href="{USERS->URL->SEARCH}">{USERS->posts}</a>
            {ELSE}
                0
            {/IF}
        </td>
<!-- Date Joined -->
        <td align="center">
            {IF USERS->date_added}
                {USERS->date_added}
            {ELSE}
                &nbsp;
            {/IF}
        </td>
<!-- Last Seen -->
        <td align="center">
            {IF USERS->date_last_active}
                {USERS->date_last_active}
            {ELSE}
                &nbsp;
            {/IF}
        </td>
    </tr>
    {/LOOP USERS}

</table>
<div class="nav">
    {INCLUDE 'paging'}
</div>
{ELSE}
<div class="warning">
Vous devez être connecté pour voir la liste des membres.
  <br/><br/>
  <a href="{URL->LOGINOUT}">{LANG->ClickHereToLogin}</a><br/>
  <a href="{URL->REGISTERPROFILE}">{LANG->NotRegistered}</a>
</div>
{/IF}{! LOGGEDIN}
<!-- end ~/user_list_display.tpl -->
