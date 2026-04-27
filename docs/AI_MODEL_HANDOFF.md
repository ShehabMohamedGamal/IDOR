# AI Model Handoff: IDOR Training App (Laravel 13)

## 1) Project goal
This repo is an intentionally vulnerable training app for IDOR practice.
Core product is a book review site with auth, profile management, reviews, and admin book management.

Design rule:
- Default behavior is secure.
- Vulnerability behavior is controlled by scenario config, so trainers can switch between safe/vulnerable states.

## 2) Current stack
- Laravel 13 + Breeze (Blade)
- PHP 8.5
- SQLite for local/dev tests
- Tailwind + Vite frontend

## 3) Current scenario system (important)
Scenario selection is in config `security.idor_scenario`.
Source files:
- `config/security.php`
- `app/Support/IdorScenario.php`

Supported scenarios now:
- `safe`
- `basic_all` (legacy "all vulnerable" behavior)
- `profile_update_only` (only profile update IDOR; reviews/books remain safe)
- `hidden_params_review_store` (review store trusts hidden `user_id` in form/JSON payload)
- `indirect_refs_review_update` (review update vulnerable only via indirect refs: filename/hash/encoded)
- `uuid_review_update` (review update vulnerable only via UUID-like reference route)

Env vars:
- `APP_IDOR_SCENARIO` (primary)
- `APP_VULNERABLE_IDOR` (legacy fallback; maps to `basic_all` when scenario not set)

`.env.example` includes both.

## 4) Security behavior by scenario
`safe`
- Review ownership checks enforced.
- Profile view/update ownership checks enforced.
- Admin book checks enforced.

`basic_all`
- Review ownership checks bypassed.
- Profile view/update checks bypassed.
- Admin book checks bypassed.

`profile_update_only`
- Profile update check bypassed (`PUT /users/{user}`).
- Profile view check still enforced.
- Review checks enforced.
- Admin book checks enforced.

`hidden_params_review_store`
- `POST /books/{book}/reviews` can be abused with hidden `user_id`.
- Vulnerability works with request bodies and JSON payloads.
- Profile and admin-book protections stay enforced.

`indirect_refs_review_update`
- `PUT /indirect/reviews/{reference}` can update another user's review.
- Supports reference styles: `review-<id>.txt`, `hash_<...>`, `enc_<...>`.
- Direct review update route (`PUT /reviews/{id}`) remains protected in this scenario.
- Profile/admin-book protections stay enforced.

`uuid_review_update`
- `PUT /uuid/reviews/{uuid}` can update another user's review.
- UUID-like refs are exposed in the review sample list on book pages.
- Direct review update route (`PUT /reviews/{id}`) remains protected in this scenario.
- Profile/admin-book protections stay enforced.

## 5) Key files to understand first
Core controllers:
- `app/Http/Controllers/ReviewController.php`
- `app/Http/Controllers/UserProfileController.php`
- `app/Http/Controllers/Admin/BookController.php`
- `app/Http/Middleware/IsAdmin.php`

Scenario engine:
- `app/Support/IdorScenario.php`
- `config/security.php`

Routes:
- `routes/web.php`

Authorization/policies:
- `app/Policies/ReviewPolicy.php`
- `app/Policies/ProfilePolicy.php`
- `app/Policies/BookPolicy.php`
- `app/Providers/AppServiceProvider.php` (policy registration)

Data model:
- `app/Models/User.php`, `Book.php`, `Review.php`
- migrations under `database/migrations/*books*`, `*reviews*`, `*add_role_to_users*`

UI:
- `resources/views/books/*`
- `resources/views/reviews/edit.blade.php`
- `resources/views/users/show.blade.php`
- `resources/views/admin/books/*`
- navigation/layout updated in `resources/views/layouts/*`

Tests:
- main IDOR coverage: `tests/Feature/IdorBookReviewTest.php`

Docs:
- `docs/idor-book-review-plan.md`
- `docs/idor-lab-runbook.md`

## 6) Route summary
Public:
- `GET /books`
- `GET /books/{book}`

Auth required:
- `POST /books/{book}/reviews`
- `GET|PUT|DELETE /reviews/{review}`
- `GET|PUT /indirect/reviews/{reference}`
- `GET|PUT /uuid/reviews/{uuid}`
- `GET|PUT /users/{user}`

Admin area:
- `/admin/books` resource CRUD (except show)
- guarded by `auth` + `admin` middleware

## 7) Test status
Current status: passing.
- `php artisan test` => all tests pass (last run: 52 passed)
- IDOR suite includes scenario coverage for:
  - safe
  - basic_all
  - profile_update_only
  - hidden_params_review_store
  - indirect_refs_review_update
  - uuid_review_update

## 8) Environment notes
If DB errors appear (`could not find driver`):
- Ensure PHP has `pdo_sqlite` enabled.
- This machine uses `/etc/php/php.ini` plus user scan ini.
- Required modules: `pdo_sqlite`, `sqlite3`.

Basic bootstrap:
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan test
```

## 9) How to add next IDOR scenarios (recommended pattern)
When asked for a new scenario:
1. Add new constant in `IdorScenario`.
2. Add/adjust capability methods in `IdorScenario` (example: `bypassReviewOwnership()`, `bypassProfileUpdateAuthorization()`).
3. Keep controllers/middleware thin: call scenario capability methods; avoid scenario string checks scattered in controllers.
4. Add scenario to `config/security.php` allowed list.
5. Add tests in `IdorBookReviewTest`:
   - positive vulnerable assertion(s)
   - negative assertion(s) proving unrelated paths stay safe.
6. Update docs (`idor-lab-runbook.md`, and plan doc if behavior changed materially).

## 10) Collaboration constraints for next model
- Do not remove existing `basic_all` behavior; user asked to keep basic scenario.
- Keep scenario logic explicit and test-backed.
- Prefer adding new scenario flags in `IdorScenario` rather than branching directly in controllers.
- Preserve existing routes unless user requests API changes.

## 11) Git/worktree note
Repo has local git initialized, but many changes may be uncommitted.
Before large edits, run:
```bash
git status --short
git diff --stat
```
Then continue from current working tree, do not reset user changes.
