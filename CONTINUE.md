# CONTINUE.md — Status: migration complete

PAThrive's PHP → Laravel + PostgreSQL migration is **done**. All 4 roles
(Extension Coordinator, Trainer/"Project Leader", Evaluator, Beneficiary)
plus the public marketing/auth pages are built, routed, and have been
verified against a real Postgres database — including a full
`migrate:fresh --seed` regression pass where every page, button, form, and
modal across all 4 roles was clicked through, plus a full cross-role
walkthrough (EC creates a training and assigns a Project Leader → Project
Leader takes attendance and logs a module → Beneficiary enrolls and submits
an evaluation + skills survey → Evaluator submits an impact assessment → EC
reviews it). Zero application errors surfaced in that pass.

This file is now a **reference for future work** on the app, not a task
list. If you're extending PAThrive, read section 2 (conventions) before
touching anything — they're load-bearing across the whole codebase.

## 0. Where things are

- **This Laravel project**: wherever you're reading this from.
- **Original PHP source**: `PAThrive_reorganized/PAThrive/public/` (ask the
  user for the current path — it's outside this repo). Every converted page
  has a same-named file in there. If you're changing existing behavior,
  read the original first — several pages have original bugs that were
  *intentionally* reproduced for parity (see §5) rather than silently fixed.
- `SETUP.md` — one-time environment setup.

## 1. Booting it

```bash
composer install
php artisan migrate:fresh --seed   # or `migrate` if you want to keep existing data
php artisan serve
```

Seeded accounts (password `password123` for all three staff accounts):
- EC: `ec@pathrive.test`
- Trainer ("Project Leader"): `trainer@pathrive.test` (also pre-approved
  whitelist entry `PL-2026-0001`; a second unregistered whitelist entry
  `PL-2026-0002` — Pedro Ramos — exists so you can test the real trainer
  self-signup flow without touching the seeded account)
- Evaluator: `evaluator@pathrive.test` (whitelist `EV-2026-0001`; a second
  unregistered entry `EV-2026-0002` — Ana Reyes — for testing signup)
- Beneficiary: none seeded — self-register via `/beneficiary-signup` against
  a Participant record an EC has created first (see §2, Auth)

## 2. Conventions — these are followed throughout; keep following them

**Routing**: exact original filenames as URIs (`Route::get('/ec/participants.php', ...)`).
Named routes drop the `.php`. POST actions reusing the same URL as GET (the
original's `$_POST['action']` dispatch) get a `.store` suffix name, e.g.
`ec.participants.store`. The handful of pages ported before this convention
was nailed down (`/login`, `/trainer-signup`, `/evaluator-signup`,
`/beneficiary-signup`) don't have the `.php` suffix — that's intentional,
not an inconsistency to "fix".

**Controllers**: one per original page, in `app/Http/Controllers/{Role}/`.
A `store()` method `match()`es on `$request->input('action')` and delegates
to private methods, mirroring the original's if/elseif dispatch.

**Views**: `resources/views/{role}/{page}.blade.php`, `@extends('layouts.{role}')`,
content in `@section('content')`.

**Frontend parity is a hard requirement.** Every page reuses the *exact*
CSS classes, inline `style="..."` patterns, and icon conventions (raw HTML
entities vs. Font Awesome `fa-*`, whichever the original used) from its
source PHP file. No Tailwind/Bootstrap, no restructured layouts, no
"improving" the original's spacing. The app's only stylesheet is
`public/assets/css/style.css`, linked directly via `asset()` — see §6 for
why there's a Tailwind/Vite toolchain in this repo that has nothing to do
with any of that.

**Layouts**: all 4 exist — `layouts/{ec,trainer,evaluator,beneficiary}.blade.php`,
each paired with a `{Role}LayoutComposer` in `app/View/Composers/` that
supplies the topbar notification count/list and profile initials/name.
Registered in `AppServiceProvider::boot()`. Each composer scopes
notifications to `role='{role}' AND user_id=<this user>` — the EC composer
is the only one with an additional `role='all'` broadcast branch, matching
the original per-layout notification queries exactly.

**Models**: all 20 in `app/Models/`, relationships wired. Check there before
creating a new one.

**IDs**: sequential IDs (`PL-YYYY-####`, `EV-YYYY-####`, `BF-YYYY-####`) go
through `App\Support\IdGenerator` (`DB::transaction()` + `lockForUpdate()`
instead of the original's MySQL `LOCK TABLES`).

**File uploads**: `Storage::disk('public')` / `storeAs()`. `php artisan
storage:link` must have been run (it has, in this environment).

**Auth**: two guards — `web` (staff: EC/Trainer/Evaluator, table `users`)
and `beneficiary` (table `beneficiaries`). Staff routes are gated with
`role:extension_coordinator` / `role:trainer` / `role:evaluator` on top of
`auth:web`; beneficiary routes use the `beneficiary` middleware instead —
verified this is actually applied on every route via `route:list -v`, not
just assumed. Trainer and Evaluator self-registration require a matching,
not-yet-registered whitelist entry (`TrainerWhitelist`/`EvaluatorWhitelist`,
seeded by the EC via `ec/trainers.php` / `ec/evaluators.php`). Beneficiary
self-registration requires a matching, not-yet-linked `Participant` record
(seeded by the EC via `ec/participants.php`) — this is a deliberate fix
over the original's beneficiary signup (see §5). EC self-registration
(`ecsignuppage.php`) has **no whitelist gate** — open self-service, matching
the original's design intent (the original had a bug that silently blocked
every submission; that bug was fixed, the open-registration behavior was
kept, per explicit user decision).

**Labels**: the `trainer` role/table/column names stay as-is in code — only
user-facing text says "Project Leader". This is applied consistently
everywhere, including the Trainer role's own dashboard/nav and the
per-document visibility dropdown label ("EC & Project Leaders", not the
original's "EC & Trainers").

## 3–6. All 4 roles + EC — done

Every page listed in the old version of this file is built, routed, and
tested. Route inventory (`php artisan route:list`, 81 routes total) has
zero dangling `coming-soon` placeholders — every route resolves to a real
controller action or a genuinely static `Route::view()` (privacy/terms/
about/choose-role/trainings-public, which have no dynamic content in the
original either). The leftover `coming-soon.blade.php` placeholder views
(one each for ec/trainer/beneficiary, plus a generic one) were unreferenced
by any route and have been deleted.

**`ec/reports.php`**: on-screen Chart.js dashboard only — the original never
had PDF/Excel export, so neither does the port. If CHED-style export is
wanted, it's a new feature, not a parity gap.

## 7. Known gaps / decisions surfaced during security + regression review

Not bugs in the Laravel port specifically — inherited from the original or
flagged as pre-production checklist items. Listed here so they don't get
"rediscovered" as new findings:

- **Document visibility (`private`/`ec_trainer`/`public`) is UI-only.** All
  uploaded files are served as public, unauthenticated static assets via
  `asset('storage/uploads/...')`. This matches the original's `UPLOAD_URL`
  direct-static-serving design exactly (no download-gate script exists in
  the original either) — not a Laravel regression. Fixing it properly would
  mean replacing every "view document" link across all 4 roles with an
  authenticated, permission-checked download route. Not done; needs a
  decision before starting since it changes routes used throughout the app.
- **Filename predictability**: most upload controllers use `uniqid('prefix_')`
  (guessable), matching the original. `Evaluator\ImpactAssessmentController`
  already uses `bin2hex(random_bytes(8))` instead — the safer pattern, if
  the others get revisited.
- **`APP_DEBUG`**: make sure it's `false` before any production deploy.
- **`SESSION_SECURE_COOKIE`**: unset locally (fine over HTTP); set to `true`
  once deployed behind HTTPS.
- Throttling (`throttle:5,1`) is on the login POST route; CSRF is enforced
  app-wide with no exclusions; SQL injection review of all `whereRaw`/
  `selectRaw` usages came back clean (parameterized throughout).

## 8. Frontend build tooling — installed, unused, harmless

`package.json`, `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`,
and `resources/views/welcome.blade.php` are all untouched leftovers from the
default `laravel/laravel` skeleton (Tailwind v4 + Vite + Bunny fonts).
Nothing in the app references them: every real page links
`public/assets/css/style.css` directly via `asset()`, no Blade file calls
`@vite()` except `welcome.blade.php` itself, and `welcome.blade.php` isn't
routed to anything (`/` goes to `PublicSite\LandingController`). `npm
install` has never been run in this environment and `public/build/` doesn't
exist. Safe to leave alone (matches how a fresh Laravel project always
looks) or safe to delete entirely if you want a cleaner tree — neither
choice affects the running app.

## 9. Testing notes for whoever picks this up next

- Dev server + Postgres, then log in via the seeded accounts above.
- The CSRF pattern that works reliably in this Windows/Git-Bash environment:
  fetch a fresh token with a GET in one tool call, POST with that token in a
  **separate** tool call. Combining fetch+post in one shell invocation has
  produced spurious 419s here before.
- For multipart file uploads via curl on this machine, convert the path
  with `cygpath -w` first — this curl build doesn't resolve MSYS-style
  paths in `-F file=@path`.
- `php artisan tinker --execute="..."` is the fastest way to seed/inspect/
  clean up test data between runs; remember to clean up throwaway records
  (test forms, notifications, documents) after a testing session so seeded
  data stays representative.
