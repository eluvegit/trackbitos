# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Trackbitos — a personal Spanish-language "life tracking" web app (hábitos y vida diaria) built on **CodeIgniter 4** (PHP 8.1+) with **Myth\Auth** for authentication. It's a single-user/small-scale monolith: server-rendered PHP views styled with Bootstrap 5 (via CDN), no JS build pipeline, no SPA framework.

## Commands

Install dependencies:
```
composer install
```

Run the CLI (spark):
```
php spark <command>
```

Run the full test suite:
```
./phpunit
# or
vendor/bin/phpunit
```

Run a single test file or directory:
```
./phpunit tests/unit/HealthTest.php
./phpunit app/Models
```

Run migrations:
```
php spark migrate
```

Custom app command — import a Notion CSV export into the `enlaces_*` tables:
```
php spark import:notion <path_to_csv>
```

There is no `package.json` / JS build step — front-end assets are plain files in `public/assets/` plus Bootstrap 5 and Bootstrap Icons loaded from CDN in `app/Views/layouts/default.php`.

## Architecture

### Feature-module structure

The app is organized as largely independent feature modules, each with its own Controller(s), Models, and Views directory sharing the module's name. There is no shared "domain layer" beyond the modules themselves — cross-module logic is rare (an exception is `App\Services\RecipeService`, used by the Comidas module). Current modules: Comidas, Gimnasio, Compras, Coche, Lentillas, Rodajes/RodajesEscenas, Enlaces, Journal, Youtube, Dashboard, Home (see `app/Config/Routes.php` for full route maps).

### Database schema is NOT fully migration-managed

Only the Coche module has migrations under `app/Database/Migrations/`. Every other module's tables (Comidas, Compras, Gimnasio, Lentillas, Rodajes, Enlaces, Journal, Youtube) exist in the live MySQL database without a corresponding migration file. **Do not assume the migrations directory reflects the current schema** — when you need to know a table's columns, check the Model (`$allowedFields`, `$returnType`) or query the live DB directly rather than relying on migrations.

Default DB driver is MySQLi (`app/Config/Database.php`); the `tests` group falls back to SQLite3 and is auto-selected when `ENVIRONMENT === 'testing'`.

### Auth & routing

- Auth is handled by the `myth/auth` package. `app/Config/Auth.php` extends `Myth\Auth\Config\Auth`; `landingRoute` is `dashboard`.
- Almost every route group in `app/Config/Routes.php` is protected with `['filter' => 'auth']`, which maps to `\Myth\Auth\Filters\LoginFilter` (aliased as `auth` in `app/Config/Filters.php`).
- `app/Filters/LoginFilter.php`, `PermissionFilter.php`, and `RoleFilter.php` extend a local `BaseFilter` and are available but not currently wired into routes/filter aliases beyond the Myth\Auth login filter.

### Routing conventions

Routes are grouped per module in `app/Config/Routes.php`, mostly `$routes->group('modulename', ['filter' => 'auth'], function ($routes) {...})`. Nested resources use numeric segments in the path, e.g. Rodajes → Escenas: `rodajes/(:num)/escenas/edit/(:num)`. When a route pattern could match more than one handler, more specific patterns must be declared before more generic ones (see the comment in the Comidas `diario` group about ordering `seleccionar-tipo` / two-segment / one-segment routes).

### Helpers & Services

- `app/Helpers/comidas_helper.php` and `comidas_parse_helper.php` — procedural helper functions for the Comidas module (auto-loaded globally via BaseController).
- `app/Services/RecipeService.php` — recalculates a recipe's per-100g macros from its ingredients and upserts a "virtual food" row (`es_receta=1`) into `comidas_alimentos`. This is the main example of cross-model business logic living outside a controller.
- `app/Commands/importNotion.php` — spark command that parses a Notion CSV export (auto-detects `,`/`;` separator, normalizes headers) into the `enlaces_*` tables.
