<?php

function randImage($path)
{
        $path = $_SERVER['DOCUMENT_ROOT'] . "/" . $path;
//        echo $path;
	if (is_dir($path)) 
	{
		$images = glob($path.'/*.*'); // will grab every files in the current directory
		$arrayImage = array(); // create an empty array
		
		// read throught all files
		foreach ($images as $img) 
		{
			// check file mime type like (jpeg,jpg,gif,png,webp), you can limit or allow certain file type	
			if (preg_match('/[.](jpeg|jpg|gif|png|webp)$/i', basename($img))) { $arrayImage[] = $img; }
		}
		
		return($arrayImage); // return every images back as an array
	}
	else
	{
		return(array());
	}
}

if ($PHORUM['DATA']['CHARSET']) {
    header("Content-Type: text/html; charset=".htmlspecialchars($PHORUM['DATA']['CHARSET']));
    echo '<?xml version="1.0" encoding="'.$PHORUM['DATA']['CHARSET'].'"?>';
} else {
    echo '<?xml version="1.0" ?>';
}

$bkgd = randImage('backgrounds');
if (count($bkgd) > 0) {
	$i = rand(0, count($bkgd)-1);
	$selectedBg = $bkgd[$i];
} else {
	$selectedBg = '';
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!-- START TEMPLATE {TEMPLATE}/header.tpl -->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{LOCALE}" lang="{LOCALE}">

<head>

<style type="text/css">
form {
display: inline;
}
</style>
<link rel="icon" href="/favicon.svg" type="image/svg+xml" />
<link rel="icon" href="/favicon.ico" sizes="any" />

<title>{HTML_TITLE}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

{! Language meta data from the language file ($PHORUM['DATA']['LANG_META']). }
{IF LANG_META}{LANG_META}{/IF}

{! Load CSS code. This code origins from css.tpl, css_print.tpl. }
{! Additionally, modules can add their own CSS code to these, using the }
{! "css_register" module hook. }
{IF PRINTVIEW}
  <meta name="robots" content="index, follow">
  <meta name="keywords" content="tir, tir sportf, tireur, shooting, firearms, armes, rechargement, Belgique, Europe, munitions, slashbin.net/libreforum" />
  <link rel="stylesheet" type="text/css" href="{URL->CSS_PRINT}" media="screen,print" />
{ELSE}
  <link rel="stylesheet" type="text/css" href="{URL->CSS}" media="screen" />
  <link rel="stylesheet" type="text/css" href="{URL->CSS_PRINT}" media="print" />
  <link rel="stylesheet" type="text/css" href="{URL->HTTP_PATH}/css/tireur.min.css?v=<?php echo filemtime(dirname(__FILE__).'/../css/tireur.min.css'); ?>" media="all"/>
  <link rel="stylesheet" type="text/css" href="{URL->HTTP_PATH}/css/lucide.css?v=1" media="all"/>
{/IF}

{! Load Javascript code. This code origins from core LibreForum javascript }
{! code, template javascript code (templates/.../javascript.tpl) and }
{! modules that add their code using the "javascript_register" module hook. }
<script type="text/javascript" src="{URL->JAVASCRIPT}"></script>
<script type="text/javascript" src="/js/theme-switcher.js"></script>

{! Add links to the available RSS feeds. }
{IF FEEDS}
  {LOOP FEEDS}
  <link rel="alternate" type="{FEED_CONTENT_TYPE}" title="{FEEDS->TITLE}" href="{FEEDS->URL}" />
  {/LOOP FEEDS}
{/IF}

{! Sometimes, a page redirect is needed. This code is used to redirect the }
{! browser to a different page, if a URL->REDIRECT is set from LibreForum. }
{IF URL->REDIRECT}
  <meta http-equiv="refresh" content="{IF REDIRECT_TIME}{REDIRECT_TIME}{ELSE}5{/IF}; url={URL->REDIRECT}" />
{/IF}

{! The meta description for the page. This is initially filled from the }
{! option "LibreForum Description" under "General Settings" in the LibreForum }
{! admin interface. Modules can override this description by overriding }
{! the template variable $PHORUM['DATA']['DESCRIPTION']. }
{IF DESCRIPTION}
  <meta name="description" content="{DESCRIPTION}" />
{/IF}

{! Additional tags for the <head> section of the page. This is initially }
{! filled from the option "LibreForum Head Tags" under "General Settings" in }
{! the LibreForum admin interface. Modules that need to add data to the <head> }
{! section dynamically can do so by adding that data to the template }
{! variable $PHORUM['DATA']['HEAD_TAGS']. }
{HEAD_TAGS}

{! A special hack for being able to set the max width for the #phorum }
{! container in MSIE6 and before. This uses the width that is set from }
{! settings.tpl in the max_width_ie variable. If you want to disable }
{! this hack, then you can delete this code or set max_width_id to zero }
{IF max_width_ie}
  <!--[if lte IE 6]>
  <style type="text/css">
  #phorum {
  width:       expression(document.body.clientWidth > {max_width_ie}
               ? '{max_width_ie}px': 'auto' );
  margin-left: expression(document.body.clientWidth > {max_width_ie}
               ? parseInt((document.body.clientWidth-{max_width_ie})/2) : 0 );
  }
  </style>
  <![endif]-->
{/IF}

<!--
Some Icons courtesy of:
  FAMFAMFAM - http://www.famfamfam.com/lab/icons/silk/
  Tango Project - http://tango-project.org/
-->
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Tireur.org" />
<meta property="og:title" content="{HTML_TITLE}" />
{! og:url se bâtit sur la requête courante, et non sur URL->READ / URL->INDEX : }
{! la première n'est jamais définie à ce niveau (seulement par ligne, dans      }
{! list.php et read.php) et la seconde renvoie déjà une URL absolue, que le     }
{! préfixe codé en dur doublait. Tout fil annonçait donc l'index en canonique.  }
<meta property="og:url" content="https://www.slashbin.net/libreforum<?php echo htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '#'), ENT_QUOTES, 'UTF-8'); ?>" />
<meta property="og:image" content="https://www.slashbin.net/libreforum/images/logo-site.png" />
<meta property="og:locale" content="fr_BE" />
<meta name="theme-color" content="#141D26">
<link rel="stylesheet" href="/js/vendor/katex/katex.min.css?v=0.16.8">
<script defer src="/js/vendor/katex/katex.min.js?v=0.16.8"></script>
<script defer src="/js/vendor/katex/auto-render.min.js?v=0.16.8" onload="var opts = new Object(); var d1 = new Object(); d1.left = '$$'; d1.right = '$$'; d1.display = true; var d2 = new Object(); d2.left = '$'; d2.right = '$'; d2.display = false; var d3 = new Object(); d3.left = '\\('; d3.right = '\\)'; d3.display = false; var d4 = new Object(); d4.left = '\\['; d4.right = '\\]'; d4.display = true; opts.delimiters = [d1, d2, d3, d4]; opts.throwOnError = false; renderMathInElement(document.body, opts);"></script>
</head>

{! Start of the page body. }
{! The default onload code for the <body> uses the FOCUS_TO_ID template }
{! variable to specify what page element should get the focus. }
<body onload="{IF FOCUS_TO_ID}var focuselt=document.getElementById('{FOCUS_TO_ID}'); if (focuselt) focuselt.focus();{/IF}">

<div id="wrapper">
<header id="header">
    <a href="/libreforum/index.php" style="text-decoration: none; display: flex; align-items: center; padding: 15px 20px;">
        <i class="li-message-circle" style="font-size: 2rem; color: var(--color-accent); margin-right: 10px;"></i>
        <h1 style="color: var(--color-accent); font-family: Outfit\, sans-serif; font-size: 2rem; margin: 0;">LibreForum</h1>
    </a>
</header>
<div id="phorum">
  {IF NOT PRINTVIEW}
  {/IF}

<main id="content">

    <div id="user-info" class="user-bar {IF LOGGEDIN}logged-in{ELSE}logged-out{/IF}">
      {IF LOGGEDIN}
        <span class="welcome">{LANG->Welcome}, {USER->username}</span>
      {ELSE}
        <span class="welcome">{LANG->Welcome}!</span>
      {/IF}
    </div>

    <div id="user-nav" class="user-nav-bar">
      {IF LOGGEDIN}
        <a class="icon" href="{URL->REGISTERPROFILE}"><i class="li-user"></i> {LANG->MyProfile}</a>
        {IF ENABLE_PM}
            {IF USER->new_private_messages}
              <a class="icon" href="{URL->PM}"><i class="li-mail"></i> <strong>{LANG->NewPrivateMessages}</strong></a>
            {ELSE}
              <a class="icon" href="{URL->PM}"><i class="li-mail"></i> {LANG->PrivateMessages}</a>
            {/IF}
        {/IF}
        <a class="icon" href="{URL->RECENT_MESSAGES}"><i class="li-clock"></i> {LANG->mod_recent_messages->RecentMessages}</a>
        <a class="icon" href="{URL->LOGINOUT}"><i class="li-log-out"></i> {LANG->LogOut}</a>
      {ELSE}
        <a class="icon" href="{URL->LOGINOUT}"><i class="li-log-in"></i> {LANG->LogIn}</a>
        <a class="icon" href="{URL->REGISTERPROFILE}"><i class="li-user-plus"></i> {LANG->Register}</a>
      {/IF}
    </div>

<div id="forum-content"><!-- main body of page -->
    {! This <div> holds the breadcrumb navigation code. This breadcrumb }
    {! navigation shows the user where he is on the site, relative to }
    {! the LibreForum start location (leaving a "breadcrumb" at every step }
    {! deeper into the site structure.) }
    <div id="breadcrumb">
      <div id="breadcrumb-trail">
        {VAR FIRST TRUE}
        {LOOP BREADCRUMBS}
          {IF NOT FIRST} &gt;{/IF}
          {IF BREADCRUMBS->URL}
            <a {IF BREADCRUMBS->ID AND BREADCRUMBS->TYPE}rel="breadcrumb-{BREADCRUMBS->TYPE}[{BREADCRUMBS->ID}]"{/IF} href="{BREADCRUMBS->URL}">{BREADCRUMBS->TEXT}</a>
          {ELSE}
            {BREADCRUMBS->TEXT}
          {/IF}
          {VAR FIRST FALSE}
        {/LOOP BREADCRUMBS}
        <span id="breadcrumbx"></span>
      </div>
      {! Forum search, integrated into the breadcrumb bar to save space }
      <div id="search-area" class="icon-zoom">
        <form id="header-search-form" action="{URL->SEARCH}" method="get">
          {POST_VARS}
          <input type="hidden" name="phorum_page" value="search" />
          <input type="hidden" name="match_forum" value="ALL" />
          <input type="hidden" name="match_dates" value="0" />
          <input type="hidden" name="match_threads" value="0" />
          <input type="hidden" name="match_type" value="ALL" />
          <input type="text" name="search" size="20" value="" class="styled-text" placeholder="{LANG->Search}&hellip;" /><input type="submit" value="{LANG->Search}" class="styled-button" />
          <a href="{URL->SEARCH}">{LANG->Advanced}</a>
        </form>
      </div> <!-- end of div id=search-area -->
    </div> <!-- end of div id=breadcrumb -->

    {! This div holds info about the active page (heading and description).     }
    {! It is only emitted when there is an actual heading to show, so the forum }
    {! index renders no empty page-info box.                                    }
    {IF HEADING}
        {! This is custom set heading }
        <div id="page-info">
          <span class="h1 heading">{HEADING}</span>
          {IF HTML_DESCRIPTION}
            <div class="description">{HTML_DESCRIPTION}</div>
          {/IF}
        </div>
      {ELSEIF MESSAGE->subject}
        {! This is a threaded read page }
        <div id="page-info">
          <span class="h1 heading">{MESSAGE->subject}</span>
        </div>
      {ELSEIF TOPIC->subject}
        {! This is a read page }
        <div id="page-info">
          <span class="h1 heading">{TOPIC->subject}</span>
          <div class="description">{LANG->Postedby} {IF TOPIC->URL->PROFILE}<a href="{TOPIC->URL->PROFILE}">{/IF}{TOPIC->author}{IF TOPIC->URL->PROFILE}</a>{/IF}&nbsp;</div>
        </div>
      {ELSEIF NAME}
        {! This is a forum page other than a read page or a folder page }
        <div id="page-info">
          <span class="h1 heading">{NAME}</span>
          {IF HTML_DESCRIPTION}
            <div class="description">{HTML_DESCRIPTION}&nbsp;</div>
          {/IF}
        </div>
      {ELSE}
        {! This is the index: no page-info heading is shown here on purpose,    }
        {! it is redundant with the site branding and too restrictive.          }
      {/IF}

    {! The template variable GLOBAL_ERROR can be used to show an error }
    {! message at the start of the page. }
    {IF GLOBAL_ERROR}
      <div id="global-error" class="attention">
        {GLOBAL_ERROR}
      </div>
    {/IF}

    {! Various notices for situations that require the user's attention. }
    {IF USER->NOTICE->SHOW}
      <div id="notices" class="attention">
        <span class="h4 heading">{LANG->NeedsAttention}</span class="h4">
        {IF USER->NOTICE->MESSAGES}<a class="icon" href="{URL->NOTICE->MESSAGES}"><i class="li-file-check"></i> {LANG->UnapprovedMessagesLong}</a>{/IF}
        {IF USER->NOTICE->USERS}<a class="icon" href="{URL->NOTICE->USERS}"><i class="li-user-plus"></i> {LANG->UnapprovedUsersLong}</a>{/IF}
        {IF USER->NOTICE->GROUPS}<a class="icon" href="{URL->NOTICE->GROUPS}"><i class="li-users"></i> {LANG->UnapprovedGroupMembers}</a>{/IF}
      </div> <!-- end of div id=notices -->
    {/IF}

<!-- END TEMPLATE {TEMPLATE}/header.tpl -->
<style type="text/css">
#phorum table.list { border: 1px solid #ccc !important; table-layout: fixed !important; width: 100% !important; border-collapse: collapse !important; }
#phorum table.list col.col-icon { width: 35px !important; }
#phorum table.list col.col-views { width: 7% !important; }
#phorum table.list col.col-posts { width: 7% !important; }
#phorum table.list col.col-last { width: 30% !important; }
#phorum table.list col.col-mod { width: 80px !important; }
#phorum table.list th, #phorum table.list td { padding: 8px 10px !important; overflow: hidden !important; text-overflow: ellipsis !important; }
</style>
