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

## Notes

- Le projet avait deja des modifications non commitees au depart. Je travaille avec ces changements sans les annuler.
- Les migrations locales ont ete executees avec `php artisan migrate --force`.
- Verification OK : Pint, PHPStan, PHPUnit, ESLint, Prettier, Vitest, Vite build et Playwright smoke.
