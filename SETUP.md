# PAThrive → Laravel + PostgreSQL: Foundation Setup

This package contains the **backend foundation**: database migrations, Eloquent
models, and the multi-role auth system (login/register for all 4 roles),
converted from the original plain-PHP `PAThrive` app. It does **not** yet
include the CRUD controllers/views for every dashboard page — that's the next
phase, built role by role on top of this foundation.

## 1. Create the Laravel project

```bash
composer create-project laravel/laravel pathrive
cd pathrive
```

## 2. Configure `.env` for PostgreSQL

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pathrive_db
DB_USERNAME=your_pg_user
DB_PASSWORD=your_pg_password
```

Create the database first: `createdb pathrive_db` (or via psql/pgAdmin).

## 3. Copy in these files

Copy this package's contents into your fresh Laravel project, **merging**
(don't overwrite the whole project):

```
database/migrations/*.php   → pathrive/database/migrations/
app/Models/*.php             → pathrive/app/Models/
app/Support/IdGenerator.php  → pathrive/app/Support/IdGenerator.php
app/Http/Middleware/*.php    → pathrive/app/Http/Middleware/
app/Http/Controllers/Auth/*.php → pathrive/app/Http/Controllers/Auth/
routes/web.php               → pathrive/routes/web.php  (replaces the default one)
```

Laravel ships a default `User` model at `app/Models/User.php` — **replace it**
with the one in this package (it's tailored to the `password_hash` column and
`role` field).

## 4. Register the auth guards (`config/auth.php`)

Laravel's default `config/auth.php` only has one `users` guard/provider. Add a
second guard + provider for beneficiaries. Edit the `guards` and `providers`
arrays to look like this:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'beneficiary' => [
        'driver' => 'session',
        'provider' => 'beneficiaries',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'beneficiaries' => [
        'driver' => 'eloquent',
        'model' => App\Models\Beneficiary::class,
    ],
],
```

Also update `password_resets` in the same file if you plan to support
password reset for both guards later — not required for login/register to
work now.

## 5. Register the `role` middleware alias (`bootstrap/app.php`)

Laravel 11 uses `bootstrap/app.php` for middleware registration instead of a
Kernel class. Add this inside the `->withMiddleware()` closure:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureRole::class,
        'beneficiary' => \App\Http\Middleware\EnsureBeneficiary::class,
    ]);
})
```

## 5b. Register the EC layout view composer

The EC layout (topbar notifications, unread message badge) is populated by a
view composer. Register it in `app/Providers/AppServiceProvider.php`:

```php
use App\View\Composers\EcLayoutComposer;
use Illuminate\Support\Facades\View;

public function boot(): void
{
    View::composer('layouts.ec', EcLayoutComposer::class);
}
```

## 5c. Copy static assets

`asset('assets/css/style.css')` and `asset('imgs/logofinalpt.png')` in the
Blade layout point at Laravel's `public/` folder. Copy these over from the
original project:

```bash
cp -r PAThrive_reorganized/PAThrive/public/assets pathrive/public/assets
cp -r PAThrive_reorganized/PAThrive/public/imgs   pathrive/public/imgs
```

## 6. Run the migrations

```bash
php artisan migrate
```

This creates all 20 tables (users, beneficiaries, trainings, participants,
documents, evaluations, skills_utilization, eval_forms/responses,
skills_forms/responses, notifications, attendance, training_docs,
contact_messages, trainer_whitelist, evaluator_whitelist, impact_assessments,
impact_assessment_forms/responses) with proper foreign keys, matching the
**actual runtime schema** (I cross-checked the base SQL file against the
inline `ALTER TABLE` statements hidden in `ec/trainings.php` and
`ec/participants.php`, since those had drifted from `pathrive_db.sql` — the
migrations here reflect what the app actually uses).

## 6b. Link storage for document uploads

The Documents module (`ec/documents.php`) stores uploads via Laravel's
`Storage` facade (`storage/app/public/uploads`) instead of the original flat
`public/uploads/` folder. Run this once so uploaded files are web-accessible:

```bash
php artisan storage:link
```

## 7. Seed the whitelist + default EC account (optional but recommended)

Your original SQL seeds one EC account and 8 whitelisted trainers. Create a
seeder (`php artisan make:seeder PathriveSeeder`) and port over the same
sample data if you want to log in immediately after migrating — happy to
write that seeder next if you want it.

## What's included vs. what's next

**Included (this package):**
- All 20 tables as migrations, with correct FKs/enums/JSON columns for Postgres
- All 20 Eloquent models with relationships
- Multi-role login (single form, tries staff guard then beneficiary guard —
  same UX as the original `login.php`)
- Trainer signup (validates against `trainer_whitelist`, matching original logic)
- Evaluator signup (validates against `evaluator_whitelist`)
- Beneficiary signup (open self-registration)
- Role middleware (`role:extension_coordinator`, `role:trainer`, etc.) and a
  separate `beneficiary` guard middleware
- Postgres-safe sequential ID generator (`App\Support\IdGenerator`) replacing
  the old MySQL `LOCK TABLES` approach with `SELECT ... FOR UPDATE`

**Also included (EC module — 8 of 17 pages done):**
- `layouts/ec.blade.php` — full topbar/sidebar/footer, converted 1:1 from
  `ec/layout.php` + `ec/layout_end.php`
- `EcLayoutComposer` — feeds the layout its notification/badge data
- **Dashboard** — recent trainings table w/ inline status change, latest
  uploads, skills utilization averages, quick links, "Create Training" modal
- **Notifications** — mark-all-read redirect
- **Messages** (Contact Messages) — list, mark read, mark all read, delete
- **Profile** — view/edit personal info, change password
- **Privacy** / **Terms** — static pages
- **Trainers (labeled "Project Leader" in the UI)** — whitelist management,
  registered account list, search, edit, enable/disable, add-to-whitelist
  with a Postgres-safe `PL-YYYY-####` ID generator
- **Evaluators** — same pattern as Trainers, `EV-YYYY-####` IDs
- **Documents** — file upload (via Laravel's `Storage` facade, not the
  original's raw `move_uploaded_file`), visibility control, search, delete
- `Ec\TrainingController@store` — handles `create` + `update_status` (used
  by the dashboard; the rest of `trainings.php` is still pending)

**Not yet included (next, still within the EC module):**
- Trainings — full index/edit/delete/budget/filter/detail view
- Participants, Evaluations, Impact Assessment, Skills, Reports

**Not yet started:**
- Trainer role (14 pages), Evaluator role (6 pages), Beneficiary role (10 pages)

I'm converting this one module at a time so each piece is testable against
your actual Postgres DB as we go, rather than generating a huge amount of
code you can't verify until the very end.
