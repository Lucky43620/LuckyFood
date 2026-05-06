# Avancement LuckyFood

## Termine

- [x] Corriger le scanner code-barres avec demarrage dans `onMounted`.
- [x] Corriger l'etat vide de la recherche quand la saisie est vide ou trop courte.
- [x] Completer la page Objectifs avec `useForm`, erreurs serveur, lipides, fibres, genre, age, taille et calcul conseille.
- [x] Ajouter l'historique de poids avec table `weight_entries`.
- [x] Fiabiliser les recettes cote serveur en recalculant les totaux depuis les ingredients.
- [x] Corriger la barre macros des recettes avec les calories macros reelles.
- [x] Transmettre les attributs HTML dans `AppInput`.
- [x] Ajouter edition du journal.
- [x] Ajouter favoris, aliments recents et repetition du repas d'hier.
- [x] Ajouter aliment manuel.
- [x] Ajouter recette vers journal.
- [x] Utiliser les recettes publiques dans la bibliotheque.
- [x] Ajouter liste de courses depuis une recette.
- [x] Ajouter rapports hebdo et historique eau dans Progression.
- [x] Ajouter export CSV/JSON.
- [x] Verifier format, analyse, tests, migrations, build et smoke E2E.
- [x] Rendre les aliments du dashboard cliquables vers leur fiche detaillee ou leur recette.
- [x] Rendre les ingredients des recettes cliquables vers leur fiche detaillee FatSecret.
- [x] Adapter le retour de fiche aliment selon l'origine : tableau de bord, recette, journal, favoris ou recherche.
- [x] Ajouter une miniature photo aux recettes avec stockage public et affichage bibliotheque/fiche.
- [x] Ajouter edition et duplication des recettes.
- [x] Ajouter une page Favoris dediee avec ajout au journal et retrait rapide.
- [x] Mettre `image_path` directement dans la migration originale des recettes pour le prochain `migrate:fresh`.
- [x] Corriger l'URL publique des images recettes en chemin relatif `/storage/...`.
- [x] Ajouter un bouton de deconnexion dans la page Profil.

## Notes

- Le projet avait deja des modifications non commitees au depart. Je travaille avec ces changements sans les annuler.
- Les migrations locales ont ete executees avec `php artisan migrate --force`.
- Verification OK : Pint, PHPStan, PHPUnit complet, ESLint, Prettier, Vitest et Vite build.
- Pour cette passe, la base locale n'a pas ete migree : tu as prevu un `migrate:fresh`.
