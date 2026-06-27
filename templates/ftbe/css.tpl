/* BEGIN TEMPLATE css.tpl */

* {margin: 0; padding: 0; border: none;}

/* overall style */
body {
  background-color: #ffffff;
/*    background-color: #222222;   */ /* {body_background_color}; */
/*    background: black url("templates/{TEMPLATE}/images/bg2.gif") repeat scroll 0 0; */
  color: {default_font_color};
  margin: 5px;
  padding: 0;
}

.box {
        font-size: {defaultfontsize};
        font-family: {defaultfont};
	color: #000000;
    background-color: #ffffee;
/*        width: {tablewidth}; */
        border-left: 1px solid {tablebordercolor};
        border-right: 1px solid {tablebordercolor};
        border-top: 1px solid {tablebordercolor};
        border-bottom: 1px solid {tablebordercolor};
        padding: 3px;
        text-align: left;
  border-radius: 0px 0px 5px 5px;
}
.boxsq {
        font-size: {defaultfontsize};
        font-family: {defaultfont};
	color: #000000;
    background-color: #ffffee;
/*        width: {tablewidth}; */
        border-left: 1px solid {tablebordercolor};
        border-right: 1px solid {tablebordercolor};
        border-top: 1px solid {tablebordercolor};
        padding: 3px;
        text-align: left;
  border-radius: 0px 0px 0px 0px;
}

#phorum {
    font-family: {default_font};
    font-size: {base_font_size};
    color: {default_font_color};
    max-width: {max_width};
    margin: auto;
/*    background-color: black; */
}

/* new stuff I added for Bitmap World */

#phorum table.bigtable {
    background-color: white;
    width: {max_width};
}
#phorum td.bigtop {
    background-color: black;
    color: white;
}

#phorum td.leftsidebar {
    background-color: #ffffff;
    color: black;
    font-size: 8pt;
    width: 185px;                  /* will only work in IE if you also set width on mainbody */
}
#phorum td.leftsidebar a {
    color: #000000;
}

#phorum td.mainbody {
    background-color: white;
    valign: top;
 }

#phorum td.footer {
    background-color: white;
}

#phorum img{
    max-width:800px;
}

.fullwidth {
    width: 100%;   /* percentage widths sometimes mess up in IE */ 
}

/* HTML level styles */

img {
    vertical-align: top;
    border: none;
}

#phorum div.generic table th {
    text-align: left;
}

#phorum table.forumlist {
    margin-bottom: 4px;
    border: 1px solid {border_color};
    border-bottom: 0;
}

#phorum table.forumlist th  {
    color: {border_font_color};
    background-color: {th_background_color};
    font-size: {font_small};
    padding: 5px;
/*
    background-repeat: repeat-x;
    background-image: url('{header_background_image}');
*/
}

#phorum table.forumlist th a {
    color: {border_font_color};
}

#phorum table.forumlist td {
    background-color: {default_background_color};
    padding: 8px;
    border-bottom: 1px solid {border_color};
    font-size: {font_small};
}

#phorum table.forumlist td.alt {
    background-color: {alt_background_color};
}

#phorum table.forumlist td.current {
    background-color: {highlight_background_color};
}

#phorum table.forumlist td p {
    margin: 4px 8px 6px 4px;
}

#phorum table.forumlist td .h3 {
    margin: 0;
}

#phorum table.forumlist td .h4 {
    font-size: {font_large};
    margin: 0;
    font-weight: normal;
}

#phorum table.forumlist td span.new-indicator {
    color: {new_color};
    font-size: 80%;
    font-weight: normal;
}

#phorum table.list {
    margin-bottom: 4px;
    border: 1px solid {border_color};
    clear: both;
    width: 100%;
}

#phorum table.list th  {
    background-color: {th_background_color};

    color: {border_font_color};
    font-size: {font_small};
    padding: 5px;
/*
    background-image: url('{header_background_image}');
    background-repeat: repeat-x;
*/
}

#phorum table.list th a {
    color: {border_font_color};
}

#phorum table.list td {
    background-color: {default_background_color};
    padding: 8px;
    font-size: {font_small};
    padding: 3px 6px;
    margin: 0px 1px 0px 0px;
    border-bottom-style: none;
/*
    border-bottom: 1px solid {border_color};
*/
}

#phorum table.list td.alt {
/*    background-color: {alt_background_color}; */
}

#phorum table.list td.not_alt {
    background-color: {alt_background_color};
}

#phorum table.list td.first {
    border-top: 1px solid {border_color};
    padding-top: 6px;
}

/*
#phorum table.list td.not_first {
}
*/

#phorum table.list td.spacer {
    margin: 0px;
    padding: 0px;
    height: 5px;
    font-size: 4px;
}


#phorum table.list td.current {
    background-color: {highlight_background_color};
}

#phorum table.list td p {
    margin: 4px 8px 6px 4px;
}

#phorum table.list td .h3 {
    margin: 0;
}

#phorum table.list td .h4 {
    font-size: {font_large};
    margin: 0;
    font-weight: normal;
}

#phorum table.list td span.new-indicator {
    color: {new_color};
    font-size: {font_small};
    font-weight: normal;
}

#phorum a {
    color: {link_color};
}

#phorum a:hover {
    color: {link_hover_color};
}

#phorum span.welcome,
#phorum a.icon {
    background-repeat: no-repeat;
    background-position: 1px 2px;
    padding: 4px 10px 2px 21px;
    font-weight: normal;
    white-space: nowrap;
}

#phorum td.leftsidebar span.welcome,
#phorum td.leftsidebar a.icon {
    display: block;
}

#phorum .h1 {
    margin: 5px 0 0 0;
    font-size: {font_xx_large};
/*
    color: #445044;
    font-face: "Times New Roman", serif;
*/    
}

#phorum .h2 {
    margin: 0;
    font-size: {font_large};
    font-weight: normal;
}

#phorum .h4 {
    margin: 0 0 5px 0;
}

#phorum hr {
    height: 1px;
    border: 0;
    border-top: 1px solid {border_color};
}

/* global styles */

#phorum div.generic table {
}

#phorum div.generic {
    padding: 8px;
    background-color: {gen_background_color};
    border: 1px solid {border_color};
}

#phorum div.generic-lower {
    padding: 8px;
    margin-bottom: 8px;
}

#phorum div.paging {
    float: right;
}

#phorum div.paging a {
    font-weight: bold;
    margin: 0 4px 0 4px;
    padding: 0 0 1px 0;
}

#phorum div.paging img{
    vertical-align: bottom;
}

#phorum div.paging strong.current-page {
    margin: 0 4px 0 4px;
}

#phorum div.nav {
    font-size: {font_x_small};
    margin: 0 0 5px 0;
    line-height: 20px;
}

#phorum div.nav-right {
    float: right;
}

#phorum div.information {
    padding: 8px;
    border: 1px solid {information_border_color};
    background-color: {information_background_color};
    margin-bottom: 8px;
}

#phorum div.notice {
    padding: 8px;
    background-color: {alt_background_color};
    border: 1px solid {border_color};
    margin-bottom: 8px;
}

#phorum div.warning {
    border: 1px solid {warning_border_color};
    background-color: {warning_background_color};
    padding: 8px;
    margin-bottom: 8px;
}

#phorum div.attachments {
    background-color: {default_background_color};
    margin-top: 8px;
    padding: 16px;
    border: 1px solid {border_color};
}

#phorum span.new-flag {
    color: {new_color};
}

#phorum table.menu td {
    vertical-align: top;
}

#phorum table.menu td.menu {
    font-size: {font_small};
    padding: 0 8px 0 0;
}

#phorum table.menu td.menu ul {
    list-style: none;
    padding: 0;
    margin: 4px 0 8px 8px;
}

#phorum table.menu td.menu ul li {
    margin: 0 0 4px 0;
}

#phorum table.menu td.menu ul li a {
    text-decoration: none;
}

#phorum table.menu td.menu ul li a.current {
    font-weight: bold;
}

#phorum table.menu td.menu span.new {
    color: {new_color};
}

#phorum table.menu td.content {
    width: 100%;
    padding: 0;
}

#phorum table.menu td.content .h2 {
    margin: 0 0 8px 0;
    background-repeat: repeat-x;
    background-image: url('{header_background_image}');
    color: {border_font_color};
    background-color: {border_color};
    padding: 4px;
}

#phorum table.menu td.content div.generic {
    margin: 0 0 8px 0;
}

#phorum table.menu td.content dl {
    margin: 0;
    padding: 0;
}

#phorum table.menu td.content dt {
    font-weight: bold;
}

#phorum table.menu td.content dd {
    padding: 4px;
    margin: 0 0 8px 0;
}

#phorum fieldset {
    border: 0;
    padding: 0;
    margin: 0;
}

#phorum textarea.body {
    font-family: {default_font};
    width: 100%;
    border: 0;
}

#phorum table.form-table {
    width: 100%;
}



/* header styles */

#phorum #logo {
    background-color: {logo_background_color};
    vertical-align: bottom;
    height: auto;
/*    background-image: url('{top_background_image}');  */

}

#phorum #logo img {
    margin: 0px;
}

#phorum #page-info {
    padding: 8px 8px 8px 0;
    margin: 0 16px 16px 0;
}

#phorum #page-info .description {
    margin: 8px 8px 0 0;
    padding-right: 32px;
    font-size: {font_small};
}

#phorum #breadcrumb {
    border-top: 0;
    padding: 5px;
    font-size: {font_small};
    border-bottom: 1px solid {breadcrumb_border_color};
}

#phorum #user-info {
    margin: 0 4px 0 0;
    text-align: left;
/*
    font-size: {font_small};
*/
}

#phorum #user-info a {
    margin: 0 0 0 10px;
    padding: 4px 0 2px 21px;

    background-repeat: no-repeat;
    background-position: 1px 2px;
}

#phorum #user-info img {
    border-width : 0;
    margin: 4px 3px 0 0;
}

#phorum #user-info small a{
    margin: 0;
    padding: 0;
    display: inline;
}

#phorum div.attention {
    /* does not use template values on purpose */
    padding: 24px 8px 24px 64px;
    border: 1px solid #A76262;
    background-image: url('templates/{TEMPLATE}/images/dialog-warning.png');
    background-color: #FFD1D1;
    background-repeat: no-repeat;
    background-position: 8px 8px;
    color: Black;
    margin: 8px 0 8px 0;
}

#phorum div.attention a {
    /* does not use template values on purpose */
    color: #68312C;
    padding: 2px 2px 2px 21px;
    display: block;
    background-repeat: no-repeat;
    background-position: 1px 2px;
}

#phorum #right-nav {
    float: right;
}

#phorum #search-area {
    float: right;
    text-align: right;
    padding: 8px 8px 8px 32px;
    background-repeat: no-repeat;
    background-position: 8px 12px;
    margin: 0 16px 0 0;
}

#phorum #header-search-form {
    display: inline;
}

#phorum #header-search-form a {
    font-size: {font_xx_small};
}



/* Read styles */

#phorum div.message div.generic {
    border-bottom: 1;
}

#phorum td.message-user-info {
    font-size: {font_small};
    white-space: nowrap;
}

#phorum div.message-author {
    background-repeat: no-repeat;
    background-position: 0px 2px;
    padding: 0px 0 0px 21px;
    font-size: {font_large};
    font-weight: bold;
    margin-bottom: 5px;
}

#phorum div.message-author small {
    font-size: {font_xx_small};
    font-weight: normal;
    margin: 0 0 0 16px;
}

/* Avatars : vignette uniforme. Phorum code en ligne les dimensions d'origine
   (jusqu'à 200 px), ce qui rendait la colonne membre incohérente. Ciblage par
   alt="avatar" pour ne pas toucher aux pièces jointes servies par file.php. */
#phorum img[alt="avatar"] {
    width: auto !important;
    height: auto !important;
    max-width: 90px;
    max-height: 90px;
    border-radius: 4px;
}

#phorum div.message-subject {
    font-weight: bold;
    font-size: {font_small};
}

#phorum div.message-body {
    padding: 0px;
    margin: 0 0 0px 0;
/*    border: 1px solid {border_color}; */
    border-top: 0;
    background-repeat: repeat-x;
/*    background-color: {message_background_color}; */
    overflow: hidden; /* makes the div extend around floated elements */
/*
    background-image: url('{message_background_image}');
*/
}

#phorum div.pmmessage-body {
    padding: 0px;
    margin: 0 0 0px 0;
    border: 1px solid {border_color};
    background-repeat: repeat-x;
    background-color: {message_background_color};
    overflow: hidden; /* makes the div extend around floated elements */
/*
    background-image: url('{message_background_image}');
*/
}

#phorum div.message-body br {
    clear: both;
}

#phorum div.user-signature {
    margin-top: 15px;
    padding-top: 8px;
    border-top: 1px dashed {border_color};
    font-size: {font_small};
    color: #6a737d;
    font-style: italic;
    clear: both;
}

#phorum div.user-signature p {
    margin: 4px 0 !important;
}

#phorum div.message-date {
    font-size: {font_small};
}

#phorum div.message-moderation {
    margin-top: 8px;
    font-size: {font_small};
    border-top: 0;
    padding: 6px;
    background-color: {alt_background_color};
    border: 1px solid {border_color};
    line-height: 20px;
}

#phorum div.message-options {
    margin-top: 10px;
    text-align: right;
    font-size: {font_small};
    clear: both;
}

#phorum #thread-options {
    margin: 8px 0 32px 0;
    background-color: {alt_background_color};
    border: 1px solid {border_color};
    padding: 8px;
    text-align: center;
}

/* Changes styles */

#phorum span.addition {
    background-color: {span_addition_background_color};
    color: {span_addition_font_color};
}

#phorum span.removal {
    background-color: {span_removal_background_color};
    color: {span_removal_font_color};
}

/* Posting styles */

#phorum #post {
    clear: both;
}

#phorum #post ul {
    margin: 2px;
}

#phorum #post ul li {
    font-size: {font_small};
}

#phorum #post-body {
    border: 1px solid {border_color};
    background-color: {default_background_color};
    padding: 8px;
}

#phorum #post-moderation {
    font-size: {font_small};
    float: right;
    border: 1px solid {border_color};
    background-color: {post_moderation_background_color};
    padding: 8px;
}

#phorum #post-buttons {
    text-align: center;
    margin-top: 8px;
}

#phorum div.attach-link {
    background-image: url('templates/{TEMPLATE}/images/attach.png');
    background-repeat: no-repeat;
    background-position: 1px 2px;
    padding: 4px 10px 2px 21px;
    font-size: {font_small};
    font-weight: normal;
}

#phorum #attachment-list td {
    font-size: {font_small};
    padding: 6px;
}

#phorum #attachment-list input {
    font-size: {font_xx_small};
}


/* PM styles */

#phorum input.rcpt-delete-img {
    vertical-align: bottom;
}

#phorum div.pm {
    padding: 8px;
    background-color: {alt_background_color};
    border: 1px solid {border_color};
}

#phorum div.pm div.message-author {
    font-size: {font_small};
}

#phorum .phorum-gaugetable {
    margin-top: 10px;
    border-collapse: collapse;
}

#phorum .phorum-gauge {
    border: 1px solid {border_color};
    background-color: {default_background_color};
}

#phorum .phorum-gaugeprefix {
    border: none;
    background-color: {default_background_color};
    padding-right: 10px;
}


/* Profile styles */

#phorum #profile div.icon-user {
    background-repeat: no-repeat;
    background-position: 0px 2px;
    padding: 0px 0 0px 21px;
    font-size: {font_large};
    font-weight: bold;
    margin-bottom: 5px;
}

#phorum #profile div.icon-user small {
    font-size: {font_xx_small};
    font-weight: normal;
    margin: 0 0 0 16px;
}

#phorum #profile dt {
    font-weight: bold;
}

#phorum #profile dd {
    padding: 4px;
    margin: 0 0 8px 0;
}


/* Search Styles */

#phorum #search-form {
    margin-bottom: 35px;
}

#phorum #search-form form {
    font-size: {font_small};
}

#phorum div.search {
    background-color: {default_background_color};
}

#phorum div.search-result {
  font-size: {font_small};
  margin: 10px 0px;
  padding: 5px;
  border: 1px solid {border_color};
  background-color: {alt_background_color};
}

/*
#phorum div.search-result .h4 {
    font-size: {font_x_large};
    margin: 0;
}

#phorum div.search-result .h4 small {
    font-size: {font_x_small};
}
*/

#phorum div.search-result blockquote {
    margin: 3px 0 3px 0;
    padding: 0;
}

/* Footer styles */

#phorum #footer-plug {
    font-size: small;
    text-align: center;
    clear: both;
}


/* Icon Styles */

.icon-accept {
    background-image: url('templates/{TEMPLATE}/images/accept.png');
}

.icon-bell {
    background-image: url('templates/{TEMPLATE}/images/bell.png');
}

.icon-bullet-black {
    background-image: url('templates/{TEMPLATE}/images/bullet_black.png');
}

.icon-bullet-go {
    background-image: url('templates/{TEMPLATE}/images/bullet_go.png');
}

.icon-cancel {
    background-image: url('templates/{TEMPLATE}/images/cancel.png');
}

.icon-close {
    background-image: url('templates/{TEMPLATE}/images/lock.png');
}

.icon-comment {
    background-image: url('templates/{TEMPLATE}/images/comment.png');
}

.icon-comment-add {
    background-image: url('templates/{TEMPLATE}/images/comment_add.png');
}

.icon-comment-edit {
    background-image: url('templates/{TEMPLATE}/images/comment_edit.png');
}

.icon-comment-delete {
    background-image: url('templates/{TEMPLATE}/images/comment_delete.png');
}

.icon-delete {
    background-image: url('templates/{TEMPLATE}/images/delete.png');
}

.icon-exclamation {
    background-image: url('templates/{TEMPLATE}/images/exclamation.png');
}

.icon-feed {
    background-image: url('templates/{TEMPLATE}/images/feed.png');
}

.icon-flag-red {
    background-image: url('templates/{TEMPLATE}/images/flag_red.png');
}

.icon-folder {
    background-image: url('templates/{TEMPLATE}/images/folder.png');
}

.icon-group-add {
    background-image: url('templates/{TEMPLATE}/images/group_add.png');
}

.icon-key-go {
    background-image: url('templates/{TEMPLATE}/images/key_go.png');
}

.icon-key-delete {
    background-image: url('templates/{TEMPLATE}/images/key_delete.png');
}

.icon-list {
    background-image: url('templates/{TEMPLATE}/images/text_align_justify.png');
}

.icon-merge {
    background-image: url('templates/{TEMPLATE}/images/arrow_join.png');
}

.icon-move {
    background-image: url('templates/{TEMPLATE}/images/page_go.png');
}

.icon-next {
    background-image: url('templates/{TEMPLATE}/images/control_next.png');
}

.icon-note-add {
    background-image: url('templates/{TEMPLATE}/images/note_add.png');
}

.icon-open {
    background-image: url('templates/{TEMPLATE}/images/lock_open.png');
}

.icon-page-go {
    background-image: url('templates/{TEMPLATE}/images/page_go.png');
}

.icon-prev {
    background-image: url('templates/{TEMPLATE}/images/control_prev.png');
}

.icon-printer {
    background-image: url('templates/{TEMPLATE}/images/printer.png');
}

.icon-split {
    background-image: url('templates/{TEMPLATE}/images/arrow_divide.png');
}

.icon-table-add {
    background-image: url('templates/{TEMPLATE}/images/table_add.png');
}

.icon-tag-green {
    background-image: url('templates/{TEMPLATE}/images/tag_green.png');
}

.icon-user {
    background-image: url('templates/{TEMPLATE}/images/user.png');
}

.icon-user-add {
    background-image: url('templates/{TEMPLATE}/images/user_add.png');
}

.icon-user-comment {
    background-image: url('templates/{TEMPLATE}/images/user_comment.png');
}

.icon-user-edit {
    background-image: url('templates/{TEMPLATE}/images/user_edit.png');
}

.icon-zoom {
    background-image: url('templates/{TEMPLATE}/images/zoom.png');
}


.icon-information {
    background-image: url('templates/{TEMPLATE}/images/information.png');
}

.icon1616 {
    width: 16px;
    height: 16px;
    border: 0;
}






/*   BBCode styles  */

#phorum blockquote.bbcode {
    font-size: {font_small};
    margin: 0 0 0 10px;
}

#phorum blockquote.bbcode>div {
    margin: 0;
    padding: 5px;
    border: 1px solid {quote_border_color};
    overflow: hidden;
}

#phorum blockquote.bbcode strong {
    font-style: italic;
    margin: 0 0 3px 0;
}

#phorum pre.bbcode {
    border: 1px solid {pre_border_color};
    background-color: {pre_background_color};
    padding: 8px;
    overflow: auto;
}

    /* Standard classes for use in any page */
    /* PhorumDesignDiv - a div for keeping the forum-size size */
    .PDDiv
    {
        width: {forumwidth};
        text-align: left;
    }
    /* new class for layouting the submit-buttons in IE too */
    .PhorumSubmit {
        border: 1px dotted {tablebordercolor};
        color: {defaulttextcolor};
        background-color: {navbackcolor};
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        vertical-align: middle;
    }

    .PhorumTitleText
    {
        float: right;
    }

.PhorumStdBlock
{
  font-size: {defaultfontsize};
  font-family: {defaultfont};
  background-color: {gen_background_color};
  padding: 3px;
  text-align: left;
/*  border-radius: 5px; */
  border: 1px solid {border_color};
}

.PhorumStdBlockHeader
{
        font-size: {defaultfontsize};
        font-family: {defaultfont};
    background-color: {alt_background_color};
/*        width: {tablewidth}; */
        border-left: 1px solid {tablebordercolor};
        border-right: 1px solid {tablebordercolor};
        border-top: 1px solid {tablebordercolor};
        padding: 3px;
        text-align: left;
  border-radius: 5px 5px 0px 0px;
}

.PhorumHeaderText
{
  font-weight: bold;
}

    .PhorumNavBlock
    {
        font-size: {navfontsize};
        font-family: {navfont};
        border: 1px solid {tablebordercolor};
        margin-top: 1px;
        margin-bottom: 1px;
/*        width: {tablewidth}; */
        background-color: {navbackcolor};
        padding: 2px 3px 2px 3px;
    }

    .PhorumNavHeading
    {
        font-weight: bold;
    }

    A.PhorumNavLink
    {
        color: {navtextcolor};
        text-decoration: none;
        font-weight: {navtextweight};
        font-family: {navfont};
        font-size: {navfontsize};
        border-style: solid;
        border-color: {navbackcolor};
        border-width: 1px;
        padding: 0px 4px 0px 4px;
    }

    .PhorumSelectedFolder
    {
        color: {navtextcolor};
        text-decoration: none;
        font-weight: {navtextweight};
        font-family: {navfont};
        font-size: {navfontsize};
        border-style: solid;
        border-color: {navbackcolor};
        border-width: 1px;
        padding: 0px 4px 0px 4px;
    }

    A.PhorumNavLink:hover
    {
        background-color: {navhoverbackcolor};
        font-weight: {navtextweight};
        font-family: {navfont};
        font-size: {navfontsize};
        border-style: solid;
        border-color: {tablebordercolor};
        border-width: 1px;
        color: {navhoverlinkcolor};
    }

    .PhorumFloatingText
    {
        padding: 10px;
    }

    .PhorumHeadingLeft
    {
        padding-left: 3px;
        font-weight: bold;
    }

    .PhorumUserError
    {
        padding: 10px;
        text-align: center;
        color: {errorfontcolor};
        font-size: {largefontsize};
        font-family: {largefont};
        font-weight: bold;
    }

    .PhorumOkMsg
    {
        padding: 10px;
        text-align: center;
        color: {okmsgfontcolor};
        font-size: {largefontsize};
        font-family: {largefont};
        font-weight: bold;
    }

   .PhorumNewFlag
    {
        font-family: {defaultfont};
        font-size: {tinyfontsize};
        font-weight: bold;
        color: {newflagcolor};
    }

    .PhorumNotificationArea
    {
        float: right;
        border-style: dotted;
        border-color: {tablebordercolor};
        border-width: 1px;
    }

    /* PSUEDO Table classes                                       */
    /* In addition to these, each file that uses them will have a */
    /* column with a style property to set its right margin       */

    .PhorumColumnFloatXSmall
    {
        float: right;
        width: 75px;
    }

    .PhorumColumnFloatSmall
    {
        float: right;
        width: 100px;
    }

    .PhorumColumnFloatMedium
    {
        float: right;
        width: 150px;
    }

    .PhorumColumnFloatLarge
    {
        float: right;
        width: 200px;
    }

    .PhorumColumnFloatXLarge
    {
        float: right;
        width: 400px;
    }

    .PhorumRowBlock
    {
        background-color: {backcolor};
        border-bottom: 1px solid {listlinecolor};
        padding: 5px 0px 0px 0px;
    }

    .PhorumRowBlockAlt
    {
        background-color: {altbackcolor};
        border-bottom: 1px solid {listlinecolor};
        padding: 5px 0px 0px 0px;
    }

    /************/


    /* All that is left of the tables */

    .PhorumStdTable
    {
        border-style: solid;
        border-color: {tablebordercolor};
        border-width: 1px;
        width: {tablewidth};
    }

    .PhorumTableHeader
    {
        background-color: {headerbackcolor};
        border-bottom-style: solid;
        border-bottom-color: {tablebordercolor};
        border-bottom-width: 1px;
        color: {headertextcolor};
        font-size: {headerfontsize};
        font-family: {headerfont};
        font-weight: {headertextweight};
        padding: 3px;
    }

    .PhorumTableRow
    {
        background-color: {backcolor};
        border-bottom-style: solid;
        border-bottom-color: {listlinecolor};
        border-bottom-width: 1px;
        color: {defaulttextcolor};
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        height: 35px;
        padding: 3px;
        vertical-align: middle;
    }

    .PhorumTableRowAlt
    {
        background-color: {altbackcolor};
        border-bottom-style: solid;
        border-bottom-color: {listlinecolor};
        border-bottom-width: 1px;
        color: {altlisttextcolor};
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        height: 35px;
        padding: 3px;
        vertical-align: middle;
    }

    table.PhorumFormTable td
    {
        height: 26px;
    }

    /**********************/


    /* Read Page specifics */

    .PhorumReadMessageBlock
    {
        margin-bottom: 5px;
    }

   .PhorumReadBodySubject
    {
        color: Black;
        font-size: {largefontsize};
        font-family: {largefont};
        font-weight: bold;
        padding-left: 3px;
    }

    .PhorumReadBodyHead
    {
        padding-left: 5px;
    }

    .PhorumReadBodyText
    {
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        padding: 5px;
    }

    .PhorumReadNavBlock
    {
        font-size: {navfontsize};
        font-family: {navfont};
        border-left: 1px solid {tablebordercolor};
        border-right: 1px solid {tablebordercolor};
        border-bottom: 1px solid {tablebordercolor};
/*        width: {tablewidth}; */
        background-color: {navbackcolor};
        padding: 2px 3px 2px 3px;
    }

    /********************/

    /* List page specifics */

    .PhorumListSubText
    {
        color: {listpagelinkcolor};
        font-size: {tinyfontsize};
        font-family: {tinyfont};
    }

    .PhorumListPageLink
    {
        color: {listpagelinkcolor};
        font-size: {tinyfontsize};
        font-family: {tinyfont};
    }

    .PhorumListSubjPrefix
    {
        font-weight: bold;
    }

    /********************/

    /* Posting editor specifics */

    .PhorumListModLink, .PhorumListModLink a
    {
        color: {listmodlinkcolor};
        font-size: {tinyfontsize};
        font-family: {tinyfont};
    }

    .PhorumAttachmentRow {
        border-bottom: 1px solid {altbackcolor};
        padding: 3px 0px 3px 0px;
    }

    /********************/

    /* PM specifics */

    .phorum-recipientblock
    {
        border: 1px solid black;
        position:relative;
        float:left;
        padding: 1px 1px 1px 5px;
        margin: 0px 5px 5px 0px;
        font-size: {smallfontsize};
        background-color: {backcolor};
        border: 1px solid {tablebordercolor};
        white-space: nowrap;
    }

    .phorum-pmuserselection
    {
        padding-bottom: 5px;
    }

    .phorum-gaugetable {
        border-collapse: collapse;
    }

    .phorum-gauge {
        border: 1px solid {tablebordercolor};
        background-color: {navbackcolor};
    }

    .phorum-gaugeprefix {
        border: none;
        background-color: white;
        padding-right: 10px;
    }

    /********************/

    /* Override classes - Must stay at the end */

    .PhorumNarrowBlock
    {
        width: {narrowtablewidth};
    }

    .PhorumSmallFont
    {
        font-size: {smallfontsize};
    }

    .PhorumLargeFont
    {
        color: {defaulttextcolor};
        font-size: {largefontsize};
        font-family: {largefont};
        font-weight: bold;
    }


    .PhorumFooterPlug
    {
        margin-top: 10px;
        font-size: {tinyfontsize};
        font-family: {tinyfont};
    }



    /*   BBCode styles  */

    blockquote.bbcode
    {
        font-size: {smallfontsize};
        margin: 0 0 0 10px;
    }

    blockquote.bbcode>div
    {
        margin: 0;
        padding: 5px;
        border: 1px solid {tablebordercolor};
        overflow: hidden;
    }

    blockquote.bbcode strong
    {
        font-style: italic;
        margin: 0 0 3px 0;
    }

/* END TEMPLATE css.tpl */
\r