<?php

/**
 * Intercepts a search GET URL and sends it to a proper Phorum URL
 *
 * @return  void
 *
 */
function pm_enhanced_intercept_search_form()
{
    if(phorum_page=="pm"){

        if(count($_GET) && isset($_GET["search"])){

            $dest_url = phorum_get_url(PHORUM_PM_URL, "page=list", "folder_id=".$_GET["folder_id"], "search=".rawurlencode($_GET["search"]));
            phorum_redirect_by_url($dest_url);
            exit();

        }

    }
}

/**
 * Retrieve all private messages for a user in a folder.
 *
 * @param mixed $folder
 *     The folder to use. Either a special folder (PHORUM_PM_INBOX or
 *     PHORUM_PM_OUTBOX) or the id of a custom user folder.
 *
 * @param integer $user_id
 *     The user to retrieve messages for or NULL to use the active
 *     Phorum user (default).
 *
 * @param boolean $reverse
 *     If set to a true value (default), sorting of messages is done
 *     in reverse (newest first).
 *
 * @return array
 *     An array of private messages for the folder.
 */
function pm_enhanced_get_list($folder, $user_id = NULL, $reverse = TRUE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if(empty($PHORUM["mod_pm_enhanced"]["count"])){
        $PHORUM["mod_pm_enhanced"] = array(
            "count" => 50
        );
    }

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');
    settype($reverse, 'bool');

    if (is_numeric($folder)) {
        $folder_where = "pm_folder_id = $folder";
    } elseif ($folder == PHORUM_PM_INBOX || $folder == PHORUM_PM_OUTBOX) {
        $folder_where = "(pm_folder_id = 0 AND special_folder = '$folder')";
    } else trigger_error(
        'phorum_db_pm_list(): Illegal folder "'.htmlspecialchars($folder).'" '.
        'requested for user id "'.$user_id.'"',
        E_USER_ERROR
    );

    if(isset($PHORUM["args"]["pageno"]) && $PHORUM["args"]["pageno"] > 1){
        $page = $PHORUM["args"]["pageno"];
        $start = ($PHORUM["args"]["pageno"] - 1) * $PHORUM["mod_pm_enhanced"]["count"];
    } else {
        $page = 1;
        $start = 0;
    }

    $count = $PHORUM["mod_pm_enhanced"]["count"];

    $search_where = "";
    $url_safe_search = "";
    $html_safe_search = "";

    if(!empty($PHORUM["args"]["search"])){
        $like_string = _pm_enhanced_create_like_string(
            array(
                "author",
                "subject",
                "message"
            ),
            $PHORUM["args"]["search"]
        );
        $search_where = "AND $like_string";
        $url_safe_search = rawurlencode($PHORUM["args"]["search"]);
        $html_safe_search = htmlspecialchars($PHORUM["args"]["search"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    }

    // Retrieve the messages from the folder.
    $messages = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT SQL_CALC_FOUND_ROWS
                m.pm_message_id AS pm_message_id,
                m.user_id,       author,
                subject,         datestamp,
                meta,            pm_xref_id,
                pm_folder_id,    special_folder,
                read_flag,       reply_flag
         FROM   {$PHORUM['pm_messages_table']} AS m,
                {$PHORUM['pm_xref_table']} AS x
         WHERE  x.user_id = $user_id AND
                $folder_where AND
                x.pm_message_id = m.pm_message_id
                $search_where
         ORDER  BY x.pm_message_id " . ($reverse ? 'DESC' : 'ASC')."
         LIMIT $start, $count",
        'pm_message_id'
    );

    $total_rows = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT found_rows() as total_rows",
        "total_rows"
    );

    // Add the recipient information unserialized to the messages.
    foreach ($messages as $id => $message) {
        $meta = unserialize($message['meta']);
        $messages[$id]['recipients'] = $meta['recipients'];
    }

    $pages=ceil($total_rows/$count);

    if($pages<=11){
        $page_start=1;
    } elseif($pages-$page<5) {
        $page_start=$pages-10;
    } elseif($pages>11 && $page>6){
        $page_start=$page-5;
    } else {
        $page_start=1;
    }

    if(!empty($url_safe_search)){
        $pm_page_template = phorum_get_url(PHORUM_PM_URL, "page=list", "folder_id=$folder", "pageno=%pageno%", "search=".$url_safe_search);
    } else {
        $pm_page_template = phorum_get_url(PHORUM_PM_URL, "page=list", "folder_id=$folder", "pageno=%pageno%");
    }

    $GLOBALS["PHORUM"]["DATA"]["URL"]["PM_SEARCH_ACTION"] = phorum_get_url(PHORUM_PM_URL, "page=list", "folder_id=$folder");
    $GLOBALS["PHORUM"]["DATA"]["SAFE_SEARCH"] = $html_safe_search;

    for($x=0;$x<11 && $x<$pages;$x++){
        $pageno=$x+$page_start;
        $GLOBALS["PHORUM"]["DATA"]["PAGES"][] = array(
        "pageno"=>$pageno,
        'url'=>str_replace("%pageno%", $pageno , $pm_page_template),
        );
    }

    $GLOBALS["PHORUM"]["DATA"]["CURRENTPAGE"]=$page;
    $GLOBALS["PHORUM"]["DATA"]["TOTALPAGES"]=$pages;

    if($page_start>1){
        $GLOBALS["PHORUM"]["DATA"]["URL"]["FIRSTPAGE"]=str_replace("%pageno%", 1 ,$pm_page_template);
    }

    if($pageno<$pages){
        $GLOBALS["PHORUM"]["DATA"]["URL"]["LASTPAGE"]=str_replace("%pageno%", $pages, $pm_page_template);
    }

    if($pages>$page){
        $nextpage=$page+1;
        $GLOBALS["PHORUM"]["DATA"]["URL"]["NEXTPAGE"]=str_replace("%pageno%", $nextpage, $pm_page_template);
    }
    if($page>1){
        $prevpage=$page-1;
        $GLOBALS["PHORUM"]["DATA"]["URL"]["PREVPAGE"]=str_replace("%pageno%", $prevpage, $pm_page_template);
    }

    return $messages;
}


function _pm_enhanced_create_like_string($fields, $search) {

    $tokens = _pm_enhanced_tokenize_terms($search);

    $clauses = array();

    foreach($tokens as $token){

        if(preg_match('!\((.+?)\)!', $token, $match)){

            $sub_token = explode(",", $match[1]);

        } else {

            $sub_token = array($token);
        }

        $tok_clauses = array();

        foreach($sub_token as $sub){

            $sub = trim($sub);

            if($sub[0]=="-"){
                $sub = substr($sub, 1);
                $cond = "NOT LIKE";
                $op = "AND";
            } else {
                $cond = "LIKE";
                $op = "OR";
            }

            if(preg_match('!"(.+?)"!', $sub, $match)){
                $sub = $match[1];
            }

            $sub = addslashes($sub);

            foreach($fields as $field){

                $tok_clauses[] = "$field $cond '%$sub%'";
            }

        }

        $clauses[] = "(".implode(" {$op} ", $tok_clauses).")";
    }

    return implode(" AND\n", $clauses);
}


/**
 * Tokenizes a string into an array of terms including negation and quoting
 *
 * @param   string  $search_string  The string to tokenize
 * @return  array
 *
 */
function _pm_enhanced_tokenize_terms( $search_string ) {
    // surround with spaces so matching is easier
    $search_string = " $search_string ";

    $paren_terms = array();
    if ( strstr( $search_string, '(' ) ) {
        // now pull out all grouped terms eg. (nano mini)
        preg_match_all( '/ ([+\-~]*\(.+?\)) /', $search_string, $tokenArray1 );
        $search_string = preg_replace( '/ [+\-~]*\(.+?\) /', ' ', $search_string );
        $paren_terms = $tokenArray1[1];
    }

    $quoted_terms = array();
    if ( strstr( $search_string, '"' ) ) {
        // first pull out all the double quoted strings (e.g. '"iMac DV" or -"iMac DV"')
        preg_match_all( '/ ([+\-~]*".+?") /', $search_string, $tokenArray0 );
        $search_string = preg_replace( '/ [+\-~]*".+?" /', ' ', $search_string );
        $quoted_terms = $tokenArray0[1];
    }

    // finally pull out the rest words in the string
    $norm_terms = preg_split( "/\s+/", $search_string, 0, PREG_SPLIT_NO_EMPTY );

    // merge them all together and return
    return array_merge( $quoted_terms, $paren_terms, $norm_terms );

} // end of tokenizeTerms()


?>