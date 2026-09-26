<?php

if(!defined("PHORUM")) return;

require_once("./mods/smileys/defaults.php");

// Register the additional CSS code for this module.
function phorum_mod_smileys_css_register($data)
{
    $data['register'][] = array(
        "module" => "smileys",
        "where"  => "after",
        "source" => "file(mods/smileys/smileys.css)"
    );
    return $data;
}

// Register the additional JavaScript code for this module.
function phorum_mod_smileys_javascript_register($data)
{
    // We only need javascript for Editor Tools support.
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['mod_smileys']['smileys_tool_enabled']) &&
        empty($PHORUM['mod_smileys']['subjectsmileys_tool_enabled']))
        return $data;

    // The generated javascript depends on the settings, so we use
    // a specific cache_key for this module.
    $cache_key = (isset($GLOBALS['PHORUM']['mod_smileys']['cache_key'])
               ? $GLOBALS['PHORUM']['mod_smileys']['cache_key'] : 0) .
               '-' . @filemtime("mods/smileys/smileys_editor_tools.js.php");

    $data[] = array(
        "module"    => "smileys",
        "source"    => "file(mods/smileys/smileys_editor_tools.js.php)",
        "cache_key" => $cache_key
    );
    return $data;
}

function phorum_mod_smileys_after_header()
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Return immediately if we have no active smiley replacements.
    if (!isset($PHORUM["mod_smileys"])||!$PHORUM["mod_smileys"]["do_smileys"]){
        return;
    }
}

// Build the search pattern and replacement table for one target
// ("body" or "subject"). The table stored in the settings by the admin
// page carries absolute image URLs from the day it was saved (http://),
// so it is rebuilt here from the smiley list and the current http_path.
function phorum_mod_smileys_matcher($target)
{
    static $cache = array();
    if (isset($cache[$target])) return $cache[$target];

    $PHORUM = $GLOBALS["PHORUM"];
    $prefix = preg_replace('!/\./!', '/',
        $PHORUM["http_path"] . "/" . $PHORUM["mod_smileys"]["prefix"]);

    $map = array();
    foreach ($PHORUM["mod_smileys"]["smileys"] as $smiley)
    {
        if (empty($smiley["active"])) continue;
        $uses = (int) $smiley["uses"];
        if ($target == "body"    && $uses == 1) continue;
        if ($target == "subject" && $uses == 0) continue;

        $alt = htmlspecialchars(empty($smiley["alt"]) ? $smiley["search"] : $smiley["alt"]);
        $img = "<img class=\"mod_smileys_img\" src=\"" .
               htmlspecialchars($prefix . $smiley["smiley"]) .
               "\" alt=\"$alt\" title=\"$alt\"/>";

        // The text was escaped by Phorum before formatting.
        $map[htmlspecialchars($smiley["search"])] = $img;

        // Markdown turns "_word_" into "<em>word</em>" before we run.
        if ($target == "body" && preg_match('/^_(\w+)_$/', $smiley["search"], $m)) {
            $map["<em>{$m[1]}</em>"] = $img;
        }
    }

    // Longest first, so ":)-D" wins over ":)".
    $keys = array_keys($map);
    usort($keys, function ($a, $b) { return strlen($b) - strlen($a); });
    $alts = array();
    foreach ($keys as $key) {
        $alt = preg_quote($key, '~');
        // A smiley starting with a dot or a letter must not be glued to a
        // number or a word: "7.51" is a diameter, "point B)" a list item.
        if (preg_match('/^[.A-Za-z0-9]/', $key)) $alt = '(?<![A-Za-z0-9])' . $alt;
        $alts[] = $alt;
    }

    // Order matters: known smiley first (some start with "&gt;" or "<em>"),
    // then any other tag or entity, which is kept as is. Entities are
    // consumed whole so that ";)" is not found inside "&#039;)".
    $cache[$target] = empty($alts) ? NULL : array(
        '~(' . implode('|', $alts) . ')|(<[^>]*>)|(&(?:[a-z]+|#[0-9]+);)~i',
        $map
    );
    return $cache[$target];
}

// Replace smileys in the visible text of an HTML fragment, leaving links,
// code and preformatted blocks alone.
function phorum_mod_smileys_replace($html, $target)
{
    $matcher = phorum_mod_smileys_matcher($target);
    if ($matcher === NULL) return $html;
    list($pattern, $map) = $matcher;

    $skip = 0;
    return preg_replace_callback($pattern, function ($m) use (&$skip, $map) {
        if (isset($m[2]) && $m[2] !== '') {
            if (preg_match('!^<(/?)(a|code|pre)\b!i', $m[2], $t)) {
                $skip = max(0, $skip + ($t[1] === '/' ? -1 : 1));
            }
            return $m[2];
        }
        if (isset($m[3]) && $m[3] !== '') return $m[3];
        return $skip ? $m[1] : $map[$m[1]];
    }, $html);
}

function phorum_mod_smileys_format_fixup($data)
{
    // Do not format smileys for the feeds.
    if (phorum_page == "feed") return $data;

    $PHORUM = $GLOBALS["PHORUM"];

    // Return immediately if we have no active smiley replacements.
    if (!isset($PHORUM["mod_smileys"])||!$PHORUM["mod_smileys"]["do_smileys"]){
        return $data;
    }

    foreach ($data as $key => $message)
    {
        // Check for disabled formatting.
        if (!empty($PHORUM["mod_smileys"]["allow_disable_per_post"]) &&
            !empty($message['meta']['disable_smileys'])) {
            continue;
        }

        if (isset($message["subject"])) {
            $data[$key]["subject"] = phorum_mod_smileys_replace($message["subject"], "subject");
        }
        if (isset($message["body"])) {
            $data[$key]["body"] = phorum_mod_smileys_replace($message["body"], "body");
        }
    }

    return $data;
}

// Add a smiley tool button to the Editor Tools module's tool bar.
function phorum_mod_smileys_editor_tool_plugin()
{
    $PHORUM = $GLOBALS['PHORUM'];
    $lang = $PHORUM["DATA"]["LANG"]["mod_smileys"];

    // Register the smiley tool button for the message body.
    if (!empty($PHORUM['mod_smileys']['smileys_tool_enabled']))
    {
        editor_tools_register_tool(
            'smiley',                              // Tool id
            $lang['smiley'],                       // Tool description
            "./mods/smileys/icon.gif",             // Tool button icon
            "editor_tools_handle_smiley()",        // Javascript click action
            NULL,                                  // Tool icon width
            NULL,                                  // Tool icon height
            'body'                                 // Tool target
        );
    }

    // Register the smiley tool button for the message subject.
    if (!empty($PHORUM['mod_smileys']['subjectsmileys_tool_enabled']))
    {
        editor_tools_register_tool(
            'subjectsmiley',                       // Tool id
            $lang['subjectsmiley'],                // Tool description
            "./mods/smileys/icon.gif",             // Tool button icon
            "editor_tools_handle_subjectsmiley()", // Javascript click action
            NULL,                                  // Tool icon width
            NULL,                                  // Tool icon height
            'subject'                              // Tool target
        );
    }
    
    $description = isset($lang['smileys help'])
                   ? $lang['smileys help'] : 'smileys help';

    // Register the smileys help page.
    editor_tools_register_help(
        $description,
        phorum_get_url(PHORUM_ADDON_URL, 'module=smileys', 'action=help')
    );
}

// The addon hook is used for displaying a help info screen.
function phorum_mod_smileys_addon()
{
    $PHORUM = $GLOBALS['PHORUM'];

    if (empty($PHORUM["args"]["action"])) trigger_error(
        'Missing "action" argument for smileys module addon call',
        E_USER_ERROR
    );

    // Include the smileys help page.
    if ($PHORUM["args"]["action"] == 'help')
    {
        $lang = $GLOBALS['PHORUM']['language'];
        if (!file_exists("./mods/smileys/help/$lang/smileys.php")) {
            $lang = 'english';
        }
        include("./mods/smileys/help/$lang/smileys.php");
        exit(0);
    }

    trigger_error(
        'Illegal "action" argument ' .
        '"' . htmlspecialchars($PHORUM['args']['action']) . '"' .
        'for smileys module addon call',
        E_USER_ERROR
    );
}

// Add the "Disable smileys" option to the template. Note that the template
// should contain the code {HOOK "tpl_editor_disable_smileys"} at an
// appropriate place for this to work.
function phorum_mod_smileys_tpl_editor_disable_smileys()
{
    $PHORUM = $GLOBALS["PHORUM"];
    if (empty($PHORUM["mod_smileys"]["allow_disable_per_post"]))
        return;

    include(phorum_get_template('smileys::disable_option'));
}

// Process "Disable smileys" option from the message form.
function phorum_mod_smileys_posting_custom_action($message)
{
    $PHORUM = $GLOBALS["PHORUM"];
    if (empty($PHORUM["mod_smileys"]["allow_disable_per_post"])) {
        unset($message['meta']['disable_smileys']);
        return $message;
    }

    if (count($_POST)) {
        if (empty($_POST['disable_smileys'])) {
            unset($message['meta']['disable_smileys']);
        } else {
            $message['meta']['disable_smileys'] = 1;
        }
    }

    return $message;
}




?>
