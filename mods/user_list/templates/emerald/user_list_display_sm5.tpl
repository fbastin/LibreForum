<!-- start /mods/user_list/templates/emerald/user_list_display_sm5.tpl -->
<table cellspacing=1 class="list user_list tborder fullwidth">
    <tr>
        <!-- <td align="center" class="alt1"><a href="{URL->USER_LIST->All}">All</a></td> -->
        <td align="center" class="alt1"><a href="{URL->USER_LIST->number}">#</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->A}">A</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->B}">B</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->C}">C</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->D}">D</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->E}">E</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->F}">F</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->G}">G</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->H}">H</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->I}">I</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->J}">J</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->K}">K</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->L}">L</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->M}">M</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->N}">N</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->O}">O</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->P}">P</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->Q}">Q</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->R}">R</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->S}">S</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->T}">T</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->U}">U</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->V}">V</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->W}">W</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->X}">X</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->Y}">Y</a></td>
        <td align="center" class="alt1"><a href="{URL->USER_LIST->Z}">Z</a></td>
    </tr>
</table>
<div class="nav">
    {INCLUDE 'paging'}
</div>
<table cellspacing=1 class="list user_list tborder fullwidth">
    <tr>
<!-- Member Number -->
        <th align="center" width="200" style="white-space: nowrap;"><a href="{URL->USER_LIST->SORT_BY_USER_ID}">No.&nbsp;{IF USER_LIST_SORT 'user_id'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a></th>
        <!-- <th align="center">{LANG->MemberNumber}</th> -->
<!-- Member -->
        <th align="center" class="firstcol"><a href="{URL->USER_LIST->SORT_BY_USERNAME}">{LANG->Member}&nbsp;{IF USER_LIST_SORT 'username'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a></th>
        {IF LOGGEDIN}
            {IF ENABLE_PM}
<!-- PM -->
                <th align="center">{LANG->PrivateReply}</th>
<!-- Buddy -->
                <th align="center">{LANG->Buddy}</th>
            {/IF} {! ENABLE_PM}
        {/IF}{! LOGGEDIN}
<!-- Rank (later) -->
        <!-- <th align="center">Rank</th> -->
<!-- Posts -->
        <th align="center"><a href="{URL->USER_LIST->SORT_BY_POSTS}">{LANG->Posts}&nbsp;{IF USER_LIST_SORT 'posts'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a></th>
<!-- Date Joined -->
        <th align="center"><a href="{URL->USER_LIST->SORT_BY_DATE_ADDED}">Date Joined&nbsp;{IF USER_LIST_SORT 'date_added'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a></th>
        <!-- <th align="center">{LANG->DateReg}</th> -->
<!-- Last Seen -->
        <th align="center"><a href="{URL->USER_LIST->SORT_BY_DATE_LAST_ACTIVE}">Last Seen&nbsp;{IF USER_LIST_SORT 'date_last_active'}{IF USER_LIST_DIR 'asc'}&uarr;{ELSE}&darr;{/IF}{/IF}</a></th>
        <!-- <th align="center">{LANG->DateActive}</th> -->
    </tr>

    {LOOP USERS}
    <tr>
<!-- Member Number -->
        <td align="center" class="alt1">
            <span class="h4">
                {USERS->user_id}.
            </span>
        </td>
<!-- Member -->
        <td align="center" class="alt1">
            <span class="h4">
            {IF USERS->URL->PROFILE}<a href="{USERS->URL->PROFILE}">{/IF}
            {USERS->display_name}
            {IF USERS->URL->PROFILE}</a>{/IF}
            </span>
        </td>
        {IF LOGGEDIN}
            {IF ENABLE_PM}
<!-- PM -->
                <td align="center" class="alt2">
                    [ <a href="{USERS->URL->PM}">{LANG->PrivateReply}</a> ]
                </td>
<!-- Buddy -->
                <td align="center" class="alt1">
                    {IF USERS->user_id USER->user_id}
                        (self)
                    {ELSEIF USERS->is_buddy}
                        ({LANG->Buddy})
                    {ELSE}
                        [ <a href="{USERS->URL->ADD_BUDDY}">{LANG->BuddyAdd}</a> ]
                    {/IF}
                </td>
            {/IF} {! ENABLE_PM}
        {/IF}{! LOGGEDIN}
<!-- Rank -->
        <!-- (later) -->
<!-- Posts -->
        <td align="center" class="alt2">
            {IF USERS->posts}
                <a href="{USERS->URL->SEARCH}">{USERS->posts}</a>
            {ELSE}
                0
            {/IF}
        </td>
<!-- Date Joined -->
        <td align="center" class="alt1">
            {IF USERS->date_added}
                {USERS->date_added}
            {ELSE}
                &nbsp;
            {/IF}
        </td>
<!-- Last Seen -->
        <td align="center" class="alt2">
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
<!-- end ~/user_list_display_sm5.tpl -->
