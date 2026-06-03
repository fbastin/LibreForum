<?php

if (!defined('PHORUM')) return;

/**
 * Hook start_output:
 * Identifie les articles Wiki pertinents basés sur le sujet de la discussion.
 */
function phorum_mod_wiki_links_start_output() {
    global $PHORUM;

    // On ne traite que la page de lecture des messages
    if (phorum_page != 'read') return;

    // On récupère le sujet de la discussion
    if (!isset($PHORUM['DATA']['TOPIC']['subject'])) {
        return;
    }
    $subject = $PHORUM['DATA']['TOPIC']['subject'];

    // Extraction des mots-clés (logique similaire à related_threads.php du wiki)
    $stopwords = ['le','la','les','de','du','des','un','une','et','en','à','au','aux',
                  'par','pour','sur','dans','avec','son','ses','qui','que','est','sont',
                  'ce','cette','ces','ou','ne','pas','plus','the','of','and','for','a',
                  'sa','ton','tes','vos','nos','leur','tout','tous','toute','être','sans',
                  'mon','ma','mes','ton','ta','tes','son','sa','ses','notre','votre','leur',
                  'faire','comment','quel','quels','quelle','quelles','avec','pour','dans'];
    
    // Mots génériques à exclure car trop fréquents sur un forum de tir
    $weakwords = ['tir', 'arme', 'armes', 'fusil', 'douille', 'etui', 'etuis', 'balle', 'balles', 'sujet', 'question'];

    $strip = function($s) {
        return strtr($s, ['é'=>'e','è'=>'e','ê'=>'e','à'=>'a','â'=>'a','î'=>'i','ô'=>'o','û'=>'u','ç'=>'c']);
    };

    $words = preg_split('/[\s,\-—:()\/\[\]]+/', mb_strtolower($subject));
    $keywords = [];
    foreach ($words as $w) {
        $w = trim($w, '«»"\'.');
        $ws = $strip($w);
        if (mb_strlen($w) >= 3 && !in_array($w, $stopwords) && !in_array($ws, $weakwords) && !in_array($w, $weakwords)) {
            $keywords[] = $w;
        }
    }

    if (empty($keywords)) return;

    // Groupes de synonymes pour élargir la recherche (reprise du wiki)
    $synonyms = [
        'étui' => ['etui', 'douille'], 'étuis' => ['etui', 'douille'],
        'douille' => ['etui', 'étui'],
        'recuit' => ['annealing', 'recuire'], 'collet' => ['neck', 'collet'],
        'silex' => ['silex', 'flintlock', 'platine'],
        'optique' => ['lunette', 'optique', 'scope'], 'optiques' => ['lunette', 'optique', 'scope'],
        'réticule' => ['reticule', 'reticle'], 'réticules' => ['reticule', 'reticle'],
        'balistique' => ['balistique', 'trajectoire'],
        'précision' => ['precision', 'groupement', 'benchrest'],
        'nettoyage' => ['nettoyage', 'nettoyer', 'entretien'],
        'amorce' => ['amorce', 'primer'], 'poudre' => ['poudre', 'powder'],
    ];

    $strip = function($s) {
        return strtr($s, ['é'=>'e','è'=>'e','ê'=>'e','à'=>'a','â'=>'a','î'=>'i','ô'=>'o','û'=>'u','ç'=>'c']);
    };

    $search_terms = [];
    foreach ($keywords as $kw) {
        $search_terms[] = $kw;
        $search_terms[] = $strip($kw);
        if (isset($synonyms[$kw])) {
            foreach ($synonyms[$kw] as $syn) {
                $search_terms[] = $syn;
                $search_terms[] = $strip($syn);
            }
        }
    }
    $search_terms = array_unique($search_terms);
    
    // Construction de la requête pour BOOLEAN MODE (OR de tous les termes)
    $boolean_query = implode(' ', $search_terms);

    // Récupération de la connexion via la couche DB de Phorum
    $conn_func = 'phorum_db_' . $PHORUM['DBCONFIG']['type'] . '_connect';
    $conn = $conn_func();
    
    $sql = "SELECT title, url, 
                   MATCH(title, body) AGAINST ('" . mysqli_real_escape_string($conn, $boolean_query) . "' IN BOOLEAN MODE) as relevance
            FROM site_search_index
            WHERE type = 'wiki'
              AND MATCH(title, body) AGAINST ('" . mysqli_real_escape_string($conn, $boolean_query) . "' IN BOOLEAN MODE)
            ORDER BY relevance DESC
            LIMIT 3";

    $res = mysqli_query($conn, $sql);
    $links = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $links[] = $row;
        }
    }

    $PHORUM['DATA']['WIKI_LINKS'] = $links;
}

/**
 * Hook before_footer:
 * Affiche les liens Wiki si trouvés.
 */
function phorum_mod_wiki_links_before_footer() {
    global $PHORUM;

    // On ne traite que la page de lecture des messages
    if (phorum_page != 'read') return;

    if (empty($PHORUM['DATA']['WIKI_LINKS'])) {
        return;
    }

    echo '<div class="wiki-related-box noprint">';
    echo '  <div class="wiki-related-header">';
    echo '    <i class="li-book"></i> Articles Wiki recommand&eacute;s';
    echo '  </div>';
    echo '  <ul class="wiki-related-list">';
    foreach ($PHORUM['DATA']['WIKI_LINKS'] as $link) {
        echo '    <li><a href="' . htmlspecialchars($link['url']) . '">' . htmlspecialchars($link['title']) . '</a></li>';
    }
    echo '  </ul>';
    echo '</div>';
    
    // On ajoute un peu de CSS spécifique si ce n'est pas déjà dans tireur.css
    // Pour l'instant on se base sur les classes existantes ou on injecte un petit style.
    echo '<style>
    .wiki-related-box {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-left: 4px solid var(--color-accent);
        border-radius: var(--radius);
        margin: 15px 0;
        padding: 12px 18px;
    }
    .wiki-related-header {
        font-weight: bold;
        color: var(--color-text);
        margin-bottom: 8px;
        font-size: 0.95em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .wiki-related-header i { color: var(--color-accent); font-size: 1.1em; }
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
        color: var(--color-accent);
        text-decoration: none;
        font-weight: 500;
        font-size: 1.05em;
    }
    .wiki-related-list li a:hover { text-decoration: underline; }
    </style>';
}
