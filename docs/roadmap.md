# Roadmap LibreForum - Prochaines Étapes

Maintenant que le dépôt GitHub de **LibreForum** est stabilisé, exploitable de manière autonome et débarrassé de ses dépendances historiques problématiques, nous pouvons aborder les optimisations restantes avec sérénité.

Voici la feuille de route proposée pour clôturer les 4 derniers chantiers :

## 1. Résolution des problèmes de cache (Firefox)
L'affichage d'éléments obsolètes ou manquants (notamment pour les icônes) sous Firefox est souvent lié à la façon dont le navigateur gère son cache et ses requêtes conditionnelles.
- **Action :** Implémenter des en-têtes HTTP de contrôle de cache plus stricts (`Cache-Control: no-cache, must-revalidate`) sur les ressources dynamiques.
- **Action :** S'assurer que le système de *"cache-busting"* (ajout de `?v=...` aux URLs des assets) couvre bien l'intégralité des images et scripts chargés.
- **Livrable :** Disparition des décalages d'affichage lors des mises à jour graphiques, particulièrement sous Firefox.

## 2. Harmonisation de l'impression (Firefox vs Edge)
Le comportement des feuilles de style pour l'impression (`@media print`) diffère souvent selon le moteur de rendu du navigateur (Gecko pour Firefox, Blink pour Edge).
- **Action :** Auditer la feuille de style `css_print.tpl` et `tireur.min.css`.
- **Action :** Ajouter des règles spécifiques pour forcer le comportement des sauts de page (`page-break-inside: avoid;`) et la gestion des marges sur Firefox.
- **Action :** Tester le rendu PDF généré pour s'assurer d'une homogénéité inter-navigateurs.
- **Livrable :** Des impressions ou exports PDF propres et identiques, quel que soit le navigateur.

## 3. Curation du forum (21 sujets de référence pour tireur.org)
Il s'agit d'un travail de fond sur le contenu, visant à extraire les discussions les plus pertinentes de LibreForum pour les intégrer et les valoriser sous forme d'articles de référence directement sur **tireur.org**.
- **Action :** Identifier et consolider le contenu des 21 sujets majeurs.
- **Action :** Nettoyer et formater ce contenu pour une publication éditoriale.
- **Action :** Intégrer ces articles de référence sur le site principal `tireur.org` (ex: section articles ou wiki).
- **Livrable :** Une base de connaissances claire et accessible pour les utilisateurs de tireur.org, tirant parti des archives du forum.

## 4. Modernisation des URLs (Clean URLs)
Actuellement, LibreForum utilise un routage classique basé sur des paramètres de requête (ex: `read.php?1,234`). Pour le SEO et la lisibilité, il faut passer à des URLs propres.
- **Action :** Activer / configurer un module Phorum de réécriture d'URLs (comme `sef_urls` ou similaire).
- **Action :** Mettre en place les règles `.htaccess` (ou configuration Nginx) adéquates pour rediriger silencieusement les URLs du type `/forum/sujet-titre-id` vers `read.php`.
- **Action :** S'assurer de la mise en place de redirections 301 des anciennes URLs vers les nouvelles pour ne pas briser les liens externes ou le référencement existant.
- **Livrable :** Des URLs lisibles (ex: `https://slashbin.net/libreforum/nom-du-sujet`), modernes et optimisées pour les moteurs de recherche.
