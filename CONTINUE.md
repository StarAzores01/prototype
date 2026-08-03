# CONTINUE.md — Brief for Claude Code

You're continuing a PHP → Laravel + PostgreSQL migration of "PAThrive," a
CIT-SLSU extension training management system. About half of it has been
converted by hand already (outside this environment, without the ability to
run `composer`/`artisan`, so **nothing has been tested against a real DB
yet** — verify as you go). Read this whole file before writing code.

## 0. Where things are

- **This Laravel project**: wherever you're reading this from.
- **Original PHP source** (the thing you're converting *from*): ask the user
  for the path to `PAThrive_reorganized/PAThrive/public/` — it wasn't copied
  into this repo to keep it lean. Every remaining page listed below has a
  same-named file in there (e.g. `ec/participants.php`,
  `trainer/trainings.php`). **Always read the original file before
  converting it** — don't guess at fields/behavior.
- `SETUP.md` — one-time environment setup (already done if you're reading
  this in a working project; skim it once for context).

## 1. First, verify the foundation actually boots

Nothing here has run against real Postgres yet. Before converting anything
new:

```bash
composer install
php artisan migrate:status   # confirm all 20 tables migrated cleanly
php artisan serve
```

Log in as the seeded EC account (seed it yourself from the sample data in
the original `database/pathrive_db.sql` if no seeder exists yet) and click
through the built EC pages (dashboard, trainings' create/status-update,
messages, profile, project leaders, evaluators, documents). **Fix anything
broken before adding new pages** — this hasn't been checked page-by-page in
a real environment.

## 2. Conventions already established — follow them exactly

**Routing**: exact original filenames as URIs, e.g. `Route::get('/ec/participants.php', ...)`.
Named routes drop the `.php`: `->name('participants')`. POST actions that
reuse the same URL as GET (the original's `$_POST['action']` dispatch
pattern) get a `.store` suffix name, e.g. `ec.participants.store`. Look at
`routes/web.php` for the exact pattern — every EC route follows it.

**Controllers**: one per original page, in `app/Http/Controllers/{Role}/`.
A `store()` method that `match()`es on `$request->input('action')` and
delegates to private methods — mirrors the original's single-file
if/elseif action dispatch. See `TrainerController`/`EvaluatorController` for
the fullest examples (whitelist CRUD + ID generation).

**Views**: `resources/views/{role}/{page}.blade.php`, `@extends('layouts.{role}')`,
content in `@section('content')`. Keep the original's inline styles and CSS
classes as-is (this app has no separate CSS framework beyond
`assets/css/style.css` — don't introduce Tailwind/Bootstrap). Convert
`<?php foreach ?>` to `@foreach`, `<?= e($x) ?>` to `{{ $x }}`, etc. Forms
need `@csrf`.

**Layouts**: only `layouts/ec.blade.php` + `EcLayoutComposer` exist so far.
Trainer/Evaluator/Beneficiary layouts still need to be built (see §4) —
convert `trainer/layout.php`+`layout_end.php`,
`evaluator/layout.php`+`layout_end.php`, `beneficiary/layout.php`+`layout_end.php`
the same way `EcLayoutComposer`/`layouts/ec.blade.php` were built from
`ec/layout.php`.

**Models**: all 20 already exist in `app/Models/` with relationships wired.
Don't recreate them — check there first.

**IDs**: sequential IDs (`PL-YYYY-####`, `EV-YYYY-####`, `BF-...` for
participants) use `DB::transaction()` + `lockForUpdate()` instead of the
original's MySQL `LOCK TABLES` (see `TrainerController::addWhitelist()` for
the pattern, or reuse `App\Support\IdGenerator`).

**File uploads**: use `Storage::disk('public')` / `storeAs()`, not the
original's raw `move_uploaded_file()`. See `DocumentController` for the
pattern. Remember `php artisan storage:link`.

**Auth**: two guards — `web` (staff: EC/Trainer/Evaluator, table `users`)
and `beneficiary` (table `beneficiaries`). Role-restrict staff routes with
`role:extension_coordinator` / `role:trainer` / `role:evaluator` middleware.
Beneficiary routes use the `beneficiary` middleware instead.

**Labels**: the `trainer` role/table/column values stay as-is in code — only
**user-facing text** says "Project Leader" instead of "Trainer" (already
done for the EC module's Trainers page; keep it consistent everywhere else
you touch "trainer" UI text, including the Trainer role's own dashboard/nav).

## 3. Remaining EC pages (6 left)

Convert in this order — earlier ones are dependencies/data sources for later
ones:

1. **`ec/participants.php`** (13.6KB) — CRUD + toggle active, generates
   `BF-YYYY-####` IDs. Wire the "Register Participant" quick link from the
   dashboard here.
2. **`ec/skills.php`** (10.6KB) — skills utilization survey forms + responses.
3. **`ec/evaluations.php`** (20KB) — training evaluation forms + responses,
   the sidebar badge (`badge-red`) shows a pending count — wire that into
   `EcLayoutComposer` once built.
4. **`ec/impact_assessment.php`** (24.7KB) — EC's view of evaluator-submitted
   impact assessments + review workflow.
5. **`ec/reports.php`** (26KB) — the biggest single page; likely CHED
   compliance reports/exports. Read it fully before starting — check if it
   generates PDF/Excel exports (there's a `pdf` and `xlsx` capability
   available if so).
6. **`ec/trainings.php`** (33KB) — the largest page. `TrainingController`
   already has `store()` with `create`/`update_status` from the dashboard —
   extend it with `index()` (the full table/filter/search), `edit`,
   `delete`, budget update, and the detail view (`?view={id}`). Currently
   routed to a placeholder — replace `Route::view('/trainings.php', 'ec.coming-soon', ...)`
   with a real `index()` action once built.

After each page: update the `ec.` route from `ec.coming-soon` to the real
controller, same pattern as the last 8 pages.

## 4. Trainer role (14 pages, not started)

Build the layout first (`layouts/trainer.blade.php` + `TrainerLayoutComposer`,
converted from `trainer/layout.php`+`layout_end.php`), then:
`dashboard.php`, `trainings.php`, `participants.php`, `attendance.php`,
`activity.php`, `modules.php`, `skills.php`, `evaluations.php`,
`documents.php`, `notifications.php`, `profile.php`, `logout.php`.
Route group already scaffolded in `routes/web.php` under `role:trainer`
(currently just a placeholder dashboard route — replace it).

## 5. Evaluator role (6 pages, not started)

Layout from `evaluator/layout.php`+`layout_end.php`, then: `dashboard.php`,
`impact_assessment.php`, `profile.php`, `logout.php`. Small module — the
evaluator's `impact_assessment.php` is where records get *created*; EC's
`ec/impact_assessment.php` (§3.4) is where they get *reviewed*. Build
evaluator's version first since EC's depends on data existing.

## 6. Beneficiary role (10 pages, not started — separate guard)

Layout from `beneficiary/layout.php`+`layout_end.php`. Uses the
`beneficiary` guard/middleware, not `role:...`. Pages: `home.php`,
`trainings.php`, `evaluations.php`, `impact_assessment.php`, `skills.php`,
`notifications.php`, `profile.php`, `logout.php`.

## 7. `logout.php` pages (all roles)

Don't convert these individually — they all just destroy the session and
redirect to login, which the shared `AuthenticatedSessionController::destroy()`
already handles for both guards. Just point each layout's logout link/form
at `route('logout')` (already done in `layouts/ec.blade.php` — copy the
pattern).

## 8. General QA checklist per page you convert

- [ ] Route uses exact original filename, named route drops `.php`
- [ ] Controller validates input (the original's raw `$_POST` had none —
      add proper `Validator`/`FormRequest` rules based on the column types)
- [ ] Any inline `ALTER TABLE`/schema-patch code in the original (several
      EC pages had these) — confirm the target migration already has that
      column; if not, add a new migration rather than editing an already-run one
- [ ] Flash messages use `->with('success'|'error', ...)`, matching the toast
      JS already in each layout
- [ ] `@csrf` on every form
- [ ] Run `php artisan route:list` and click through in the browser — don't
      just eyeball the Blade

Work through §3 → §4 → §5 → §6 in order, testing each page against Postgres
as you go, rather than writing everything then debugging at the end.
