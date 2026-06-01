<!-- BEGIN TEMPLATE report.tpl -->
<div class="nav">
    {IF URL->INDEX}<a class="icon" href="{URL->INDEX}"><i class="li-folder"></i> {LANG->ForumList}</a>{/IF}
    <a class="icon" href="{URL->LIST}"><i class="li-clock"></i> {LANG->MessageList}</a>
    <a class="icon" href="{URL->POST}"><i class="li-msg-add"></i> {LANG->NewTopic}</a>
</div>

{IF ReportPostMessage}<div class="attention">{ReportPostMessage}</div>{/IF}

<div class="generic">
    <span class="h4">{LANG->ConfirmReportMessage}</span class="h4">
    <p>{LANG->ReportPostExplanation}</p>
    <form method="post" action="{ReportURL}">
        <textarea name="explanation" rows="5" cols="60" wrap="virtual">{explanation}</textarea><br />
        <br />
        <input type="submit" name="report" value="{LANG->Report}" />
    </form>
</div>

<p>&nbsp;</p>

<div class="generic">
<strong>{PostSubject}</strong>
<p>{LANG->Postedby}: {PostAuthor}</p>
<p>{PostBody}</p>

</div>
<!-- END TEMPLATE report.tpl -->
