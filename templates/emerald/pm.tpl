<!-- BEGIN TEMPLATE pm.tpl -->

<div class="PhorumStdBlockHeader">
<a {IF PM_PAGE "send"}class="current" {/IF}href="{URL->PM_SEND}">{LANG->SendPM}</a>
</div>
<div class="PhorumStdBlock" style="text-align: left;">
<ul>
{LOOP PM_FOLDERS}
<li><a {IF PM_FOLDERS->id FOLDER_ID}class="current" {/IF}href="{PM_FOLDERS->url}">{PM_FOLDERS->name}</a><small>{IF PM_FOLDERS->total}&nbsp;({PM_FOLDERS->total}){/IF}{IF PM_FOLDERS->new}&nbsp;(<span class="new-flag">{PM_FOLDERS->new} {LANG->newflag}</span>){/IF}</small></li>
{/LOOP PM_FOLDERS}
</ul>

<ul>
<li><a {IF PM_PAGE "folders"}class="current" {/IF}href="{URL->PM_FOLDERS}">{LANG->EditFolders}</a></li>
<li><a {IF PM_PAGE "buddies"}class="current" {/IF} href="{URL->BUDDIES}">{LANG->Buddies}</a></li>
</ul>
</div>

{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}
{INCLUDE PM_TEMPLATE}
<!-- CONTINUE TEMPLATE pm.tpl -->
