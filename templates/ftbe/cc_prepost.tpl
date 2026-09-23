<!-- BEGIN TEMPLATE cc_prepost.tpl -->
<!--
  Refonte du 2026-09-23 : UN SEUL tableau (le forum devient une colonne) au lieu d'une
  ligne d'en-tête répétée par forum ; colonnes triables au clic (/js/table-tri.js — aucun
  script ni style en ligne ici : dans un gabarit Phorum, une accolade sur une ligne est lue
  comme un appel de variable) ; largeurs laissées au contenu, défilement horizontal sur
  petit écran ; état « caché » ou « en attente » ; lien d'auteur vers le profil (il pointait
  vers le nom) ; libellé « Valider » (la clé ApproveMessage Short n'existait pas).
-->
<script src="/js/table-tri.js" defer></script>

<form action="{URL->ACTION}" method="POST">
    {POST_VARS}
    <div class="generic" style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
        {LANG->ShowOnlyMessages}
        <select name="onlyunapproved">
            <option value="0"{IF SELECTED_2 0} selected="selected"{/IF}>{LANG->AllNotShown}</option>
            <option value="1"{IF SELECTED_2 1} selected="selected"{/IF}>{LANG->OnlyUnapproved}</option>
        </select>
        {LANG->DatePosted}
        <select name="moddays">
            <option value="1"{IF SELECTED 1} selected="selected"{/IF}>1 {LANG->Day}</option>
            <option value="2"{IF SELECTED 2} selected="selected"{/IF}>2 {LANG->Days}</option>
            <option value="7"{IF SELECTED 7} selected="selected"{/IF}>7 {LANG->Days}</option>
            <option value="30"{IF SELECTED 30} selected="selected"{/IF}>1 {LANG->Month}</option>
            <option value="180"{IF SELECTED 180} selected="selected"{/IF}>6 {LANG->Months}</option>
            <option value="365"{IF SELECTED 365} selected="selected"{/IF}>1 {LANG->Year}</option>
            <option value="0"{IF SELECTED 0} selected="selected"{/IF}>{LANG->AllDates}</option>
        </select>
        <input type="submit" value="{LANG->Go}" />
    </div>
</form>

{IF UNAPPROVEDMESSAGE}
    <div class="information">{UNAPPROVEDMESSAGE}</div>
{ELSE}
<form action="{URL->ACTION}" method="POST" id="fprepost">
    {POST_VARS}
    <div style="overflow-x:auto; max-width:100%;">
    <table cellspacing="0" class="list table-triable cc-prepost">
        <thead>
            <tr>
                <th align="left" data-tri="texte">Forum</th>
                <th align="left" data-tri="texte" class="cc-sujet">Message</th>
                <th align="left" data-tri="texte">{LANG->Author}</th>
                <th align="left" data-tri="nombre" style="white-space:nowrap;">{LANG->Date}</th>
                <th align="left" data-tri="texte">État</th>
                <th align="left" style="white-space:nowrap;">
                    <label><input type="checkbox" data-tout-cocher="suppression" title="Tout cocher" /> {LANG->Delete}</label>
                </th>
            </tr>
        </thead>
        <tbody>
        {LOOP PREPOST}
            <tr>
                <td>{PREPOST->forumname}</td>
                <td class="cc-sujet">
                    <a href="{PREPOST->URL->READ}" target="_blank">{PREPOST->subject}</a>
                    <div style="font-size:0.85em; margin-top:0.2em;">
                        <a href="{PREPOST->URL->APPROVE_MESSAGE}">{LANG->ApproveMessage}</a>
                        &bull; <a href="{PREPOST->URL->APPROVE_TREE}">{LANG->ApproveMessageReplies}</a>
                        &bull; <a href="{PREPOST->URL->DELETE}">{LANG->DeleteMessage}</a>
                    </div>
                </td>
                <td>{IF PREPOST->URL->PROFILE}<a href="{PREPOST->URL->PROFILE}">{PREPOST->author}</a>{ELSE}{PREPOST->author}{/IF}</td>
                <td data-valeur="{PREPOST->raw_short_datestamp}" style="white-space:nowrap;">{PREPOST->short_datestamp}</td>
                <td>{IF PREPOST->is_hidden 1}caché{ELSE}en attente{/IF}</td>
                <td><input type="checkbox" name="deleteids[{PREPOST->message_id}]" value="1" data-groupe="suppression" /></td>
            </tr>
        {/LOOP PREPOST}
        </tbody>
    </table>
    </div>
    <p style="text-align:right;"><input type="submit" name="submit" value="{LANG->Delete}" /></p>
</form>
{/IF}
<!-- END TEMPLATE cc_prepost.tpl -->
