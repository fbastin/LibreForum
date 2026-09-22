<!-- BEGIN TEMPLATE pm_list.tpl -->
<div class="nav">
{INCLUDE "paging"}
{! Le formulaire de recherche du module pm_enhanced vivait ici, dans un }
{! commentaire HTML marque DOES NOT WORK. Le compilateur de gabarits ne lit }
{! pas les commentaires HTML : il compilait quand meme l appel a la variable }
{! SAFE_SEARCH, que seul ce module -- desactive -- renseigne, d ou un }
{! avertissement PHP a chaque affichage de la liste des messages prives. }
{! Ne jamais remettre d accolade dans ce commentaire non plus. }
</div>
<form action="{URL->ACTION}" method="post" id="phorum-pm-list">
    {POST_VARS}
    <input type="hidden" name="action" value="list" />
    <input type="hidden" name="folder_id" value="{FOLDER_ID}" />
    {IF FOLDER_IS_INCOMING}
        {INCLUDE "pm_list_incoming"}
    {ELSE}
        {INCLUDE "pm_list_outgoing"}
    {/IF}
    <!-- CONTINUE TEMPLATE list.tpl -->
</form>
<!-- END TEMPLATE pm_list.tpl -->
