# LuckyFood

LuckyFood est une application de suivi nutritionnel construite avec Laravel, Inertia et Vue. Elle permet de rechercher des aliments via FatSecret, remplir un journal alimentaire, suivre l'eau, configurer des objectifs et créer des recettes.

## Stack

- PHP 8.3+ avec Laravel 13, Jetstream, Fortify, Sanctum et Inertia.
- Vue 3, Vite, Tailwind CSS 3 et lucide-vue-next.
- FatSecret Platform API avec signature OAuth 1.0a.
- Tests backend PHPUnit, analyse Larastan, format Pint.
- Tests frontend Vitest, ESLint, Prettier et Playwright.

## Installation

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

En developpement :

```bash
composer run dev
```

## Configuration FatSecret

Renseigner ces variables dans `.env` :

```dotenv
FATSECRET_CONSUMER_KEY=
FATSECRET_CONSUMER_SECRET=
FATSECRET_BASE_URL=https://platform.fatsecret.com/rest
FATSECRET_CONNECT_TIMEOUT=3
FATSECRET_REQUEST_TIMEOUT=8
FATSECRET_CATEGORY_CACHE_TTL=86400
```

Les categories FatSecret sont mises en cache par region et langue. Les recherches, l'autocomplete et le scan code-barres sont limites par un throttle applicatif dedie.

## Commandes Qualite

Backend :

```bash
composer validate --strict
composer audit
composer run format:test
composer run analyse
php artisan test --do-not-cache-result
```

Frontend :

```bash
npm audit --omit=dev
npm run lint
npm run format:check
npm run test:unit -- --run
npm run build
```

Tout lancer :

```bash
npm run ci
```

E2E Playwright :

```bash
npx playwright install chromium
npm run test:e2e
```

## Architecture

- `app/Services/FatSecretService.php` reste la facade applicative et retourne des resultats types.
- `app/Http/Integrations/FatSecret` contient le transport Saloon, la signature OAuth, la normalisation et la presentation des erreurs.
- `app/Http/Requests` centralise les validations de recherche, journal, eau, recettes et objectifs.
- `app/Policies` protege les ressources utilisateur comme les entrees du journal et les recettes.
- `resources/js/constants` et `resources/js/composables` regroupent les constantes nutritionnelles, le formatage et les actions de journal.
- `resources/js/Components/food` et `resources/js/Components/ui` contiennent les composants reutilisables recents.

## Decisions Jetstream

Sanctum est conserve pour l'authentification de session. Les API tokens Jetstream ne sont pas actives dans ce prototype, donc les pages et tests API tokens generiques ont ete retires.

## Base De Donnees

Le prototype utilise par defaut SQLite en local. Les migrations applicatives ont ete regroupees en schema final : `recipes.instructions` est cree directement, et `user_goals.user_id` est unique.
