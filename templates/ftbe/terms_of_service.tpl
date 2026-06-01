<!-- BEGIN TEMPLATE terms_of_service.tpl -->
<div class="nav">
    {IF URL->INDEX}<a class="icon" href="{URL->INDEX}"><i class="li-folder"></i> {LANG->ForumList}</a>{/IF}
</div>

<div class="information">
    {LANG->TOS->Content}<br />
    {LANG->TOS->Version}
</div>

{IF LOGGEDIN}
    {IF NOT FULLY_LOGGEDIN}
        <div class="information">{LANG->PeriodicLogin}</div>
    {/IF}
    {IF USER->mod_tos_current}
        <div class="information">{LANG->TOS->LastAgree}</div>
    {ELSE}
        <div class="generic">
            <h4>{LANG->TOS->Header}</h4>
            <form action="{URL->TOS}" method="post">
                {POST_VARS}
                {LANG->TOS->Reforce}<br /><br />
                <input type="checkbox" name="tos_accept" value="1" />{LANG->TOS->Agree}<br /><br />
                <input type="submit" class="PhorumSubmit" value=" {LANG->Submit} " />
            </form>
        </div>
    {/IF}
{/IF}
<!-- END TEMPLATE terms_of_service.tpl -->