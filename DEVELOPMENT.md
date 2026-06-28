# Guide de Développement — LibreForum

## Gestion des Branches

Il est essentiel de maintenir **deux branches distinctes** sur ce dépôt :

1. **`main`** : La branche générique et autonome de `LibreForum`. Elle doit rester 100 % "standalone", propre, sans aucun template ou réglage spécifique à *tireur.org* (comme le thème `ftbe`). C'est cette branche qui sert de socle réutilisable pour d'autres projets.
2. **`tireur-org-site`** : La branche spécifique à l'intégration sur *tireur.org*. Elle contient le template `ftbe`, le logo du site, l'authentification unique (SSO) liée au DokuWiki local et les adaptations sur-mesure.

Toute amélioration générique ou correction de bug standalone doit être développée sur `main` (ou sur une branche de fonctionnalité) puis fusionnée/cherry-pickée sur `tireur-org-site`.

## Modules & Extensions

Les modules de LibreForum doivent également être gérés avec soin pour conserver la compatibilité entre les deux branches.
