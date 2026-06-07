<?php

if (!defined('PHORUM')) return;

function phorum_mod_wiki_links_before_footer() {
    global $PHORUM;

    if (phorum_page != 'read') return;

    if (!isset($PHORUM['DATA']['TOPIC']['thread'])) return;
    $thread_id = $PHORUM['DATA']['TOPIC']['thread'];

    $cache_key = "wiki_links_" . $thread_id;
    $cached_links = phorum_cache_get('wiki_links', $cache_key);

    if (is_array($cached_links) && !empty($cached_links)) {
        $html = '<div class="wiki-related-box noprint">';
        $html .= '<div class="wiki-related-header"><i class="li-book"></i> Articles Wiki recommand&eacute;s pour cette discussion</div>';
        $html .= '<ul class="wiki-related-list">';
        foreach ($cached_links as $link) {
            $html .= '<li><a href="' . htmlspecialchars($link['url']) . '">' . htmlspecialchars($link['title']) . '</a></li>';
        }
        $html .= '</ul></div>';
        
        $html .= '<style>
        .wiki-related-box {
            background: var(--color-surface, #f9f9f9);
            border: 1px solid var(--color-border, #ccc);
            border-left: 4px solid var(--color-accent, #0056b3);
            border-radius: var(--radius, 4px);
            margin: 20px auto;
            padding: 15px 20px;
            clear: both;
            display: block;
            max-width: 100%;
        }
        .wiki-related-header {
            font-weight: bold;
            color: var(--color-text, #333);
            margin-bottom: 10px;
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .wiki-related-header i { color: var(--color-accent, #0056b3); font-size: 1.2em; }
        .wiki-related-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .wiki-related-list li { margin: 0; }
        .wiki-related-list li a {
            color: var(--color-accent, #0056b3);
            text-decoration: none;
            font-weight: 500;
            font-size: 1.05em;
        }
        .wiki-related-list li a:hover { text-decoration: underline; }
        </style>';

        echo $html;
    }
}
