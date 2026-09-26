<?php
// LibreForum — https://github.com/fbastin/LibreForum
// Copyright 2026 Fabian Bastin and the LibreForum contributors
// SPDX-License-Identifier: Apache-2.0
//
// Contenu du guide de mise en forme (addon.php?module=markdown&action=guide).
// Chaque exemple est passé par la chaîne de mise en forme du forum au moment
// où la page est affichée : la colonne « Résultat » montre ce que le forum
// produit réellement. Un exemple marqué 'show' => FALSE n'est pas rendu
// (vidéo ou image externe : la page du guide ne charge rien d'ailleurs).

return array(
    'title'   => 'Guide de mise en forme',
    'intro'   => 'Les messages s\'écrivent en <strong>Markdown</strong> : quelques signes simples, tapés dans le texte, pour mettre en forme. Les boutons de la barre d\'outils de l\'éditeur insèrent ces mêmes signes. Chaque exemple ci-dessous est mis en forme par le forum lui-même : le résultat affiché est exactement celui qu\'on obtient dans un message.',
    'contents' => 'Sommaire',
    'syntax'  => 'Vous écrivez',
    'result'  => 'Résultat',
    'not_rendered' => 'Non montré ici',
    'back'    => 'Fermer cette fenêtre',

    'sections' => array(
        array('texte', 'Texte', '', array(
            array('Gras', '**texte en gras**'),
            array('Italique', '*texte en italique*'),
            array('Gras et italique', '***les deux***'),
            array('Barré', '~~texte barré~~'),
            array('Souligné', '<u>texte souligné</u>'),
            array('Exposant et indice', 'E = mc<sup>2</sup>, H<sub>2</sub>O'),
            array('Petit texte', '<small>texte plus petit</small>'),
            array('Couleur', '<span style="color: #c0392b">texte en rouge</span>'),
            array('Taille', '<span style="font-size: large">texte plus grand</span>'),
        )),
        array('paragraphes', 'Paragraphes et lignes', 'Un retour à la ligne passe à la ligne suivante ; une ligne vide commence un nouveau paragraphe.', array(
            array('Lignes et paragraphes', "Première ligne\nDeuxième ligne\n\nNouveau paragraphe"),
            array('Centrer', '<center>texte centré</center>'),
            array('Ligne horizontale', "Au-dessus\n\n---\n\nEn dessous"),
        )),
        array('titres', 'Titres', 'Un à six croisillons en début de ligne, suivis d\'une espace.', array(
            array('Grand titre', '# Grand titre'),
            array('Titre moyen', '## Titre moyen'),
            array('Petit titre', '### Petit titre'),
        )),
        array('citations', 'Citations', 'Un chevron en début de ligne. Le bouton « Citer » d\'un message le fait pour vous. La citation s\'arrête à la première ligne qui ne commence pas par un chevron : votre réponse peut suivre directement. Pour revenir d\'une citation imbriquée au niveau précédent, laissez une ligne ne contenant qu\'un chevron.', array(
            array('Citation', "> Texte cité\nVotre réponse"),
            array('Citation dans une citation', "> > Message d'origine\n>\n> Première réponse\n\nVotre réponse"),
        )),
        array('listes', 'Listes', 'Un tiret, un astérisque ou un numéro suivi d\'un point, en début de ligne.', array(
            array('Liste à puces', "- premier point\n- deuxième point\n  - sous-point (deux espaces devant)"),
            array('Liste numérotée', "1. premier\n2. deuxième\n3. troisième"),
        )),
        array('liens', 'Liens', 'Une adresse web écrite telle quelle devient cliquable. Les adresses qui exécuteraient un script (javascript:) sont neutralisées.', array(
            array('Adresse seule', 'https://example.org'),
            array('Lien avec un texte', '[le texte du lien](https://example.org)'),
            array('Adresse de courriel', '[écrire](mailto:nom@example.org)'),
        )),
        array('images', 'Images et vidéos', 'L\'image doit être en ligne, à une adresse publique. Pour une vidéo, l\'adresse seule sur sa ligne suffit ; le bouton Vidéo de l\'éditeur l\'insère aussi.', array(
            array('Image', '![description de l\'image](https://example.org/photo.jpg)', 'show' => FALSE),
            array('Image cliquable', '[![description](https://example.org/miniature.jpg)](https://example.org/photo.jpg)', 'show' => FALSE),
            array('Vidéo YouTube ou Vimeo', "L'adresse de la vidéo, seule sur sa ligne :\n\nhttps://www.youtube.com/watch?v=XXXXXXXXXXX", 'show' => FALSE),
        )),
        array('code', 'Code', 'Le texte d\'un bloc de code est montré tel quel, sans mise en forme.', array(
            array('Code dans une phrase', 'La commande `ls -l` liste les fichiers.'),
            array('Bloc de code', "```\nif (a < b && b > 0) {\n    **pas de gras ici**\n}\n```"),
        )),
        array('tableaux', 'Tableaux', 'Les colonnes sont séparées par des barres verticales ; la deuxième ligne sépare l\'en-tête. Un deux-points à droite aligne la colonne à droite.', array(
            array('Tableau', "| Nom | Valeur |\n| --- | ---: |\n| premier | 12 |\n| second | 3,5 |"),
        )),
        array('echapper', 'Écrire un signe sans qu\'il agisse', 'Une barre oblique inverse devant le signe le montre tel quel.', array(
            array('Astérisques visibles', '\\*pas en italique\\*'),
            array('Chevron en début de ligne', '\\> pas une citation'),
        )),
    ),

    'emoji'         => 'Émojis',
    'emoji_intro'   => 'Les émojis Unicode (😀 👍 🎯) sont acceptés dans les messages et les sujets. Pour ouvrir le sélecteur d\'émojis :',
    'emoji_windows' => 'Windows : touche Windows + <code>.</code> (point)',
    'emoji_mac'     => 'Mac : <code>Ctrl</code> + <code>Cmd</code> + <code>Espace</code>',
    'emoji_phone'   => 'Téléphone et tablette : la touche 😀 du clavier',
);
