# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Trackbitos — a personal Spanish-language "life tracking" web app (hábitos y vida diaria) built on **CodeIgniter 4** (PHP 8.1+) with **Myth\Auth** for authentication. It's a single-user/small-scale monolith: server-rendered PHP views styled with Bootstrap 5 (via CDN), no JS build pipeline, no SPA framework.

## Database schema is NOT fully migration-managed

Older modules (Comidas, Compras, Gimnasio, Lentillas, Rodajes, Enlaces, Journal, Youtube) have tables in the live MySQL database without a migration file — to know their columns, check the Model (`$allowedFields`) or query the live DB. Newer modules (Piezas, Silo, Sesiones, etc.) are migration-managed: every new schema change goes as a CI4 migration (`$this->forge->addColumn(...)`) + `php spark migrate`, run in each environment; no manual production SQL unless asked.

## Auth & routing

- Auth is handled by the `myth/auth` package. `app/Config/Auth.php` extends `Myth\Auth\Config\Auth`; `landingRoute` is `dashboard`.
- Two Myth\Auth groups: `admin` (whole site) and `inventario` (only its own read-only `inventario/...` section). Every route group in `app/Config/Routes.php` uses `['filter' => 'admin']` (`App\Filters\SoloAdmin`: not logged in → login; `inventario` user → redirected to `inventario`; no group → logged out). Only `cuenta` (own password) uses plain `auth`. New admin routes must use `admin`, never `auth`.
- Registration is closed (`allowRegistration = false`); users are managed at `cuenta/usuarios` (`App\Controllers\Usuarios`). Myth's `strong_password` rule crashes on CI 4.7 — use `min_length[10]` instead.
- `app/Filters/LoginFilter.php`, `PermissionFilter.php`, and `RoleFilter.php` are available but not wired into routes.

## Routing conventions

Nested resources use numeric segments in the path, e.g. `rodajes/(:num)/escenas/edit/(:num)`. When a route pattern could match more than one handler, more specific patterns must be declared before more generic ones (see the comment in the Comidas `diario` group about ordering `seleccionar-tipo` / two-segment / one-segment routes).
