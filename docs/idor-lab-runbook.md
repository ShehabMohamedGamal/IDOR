# IDOR Book Review Lab Runbook

## Prerequisites
- PHP 8.3+
- One DB driver available for Laravel tests/runtime:
  - Preferred for this repo: `pdo_sqlite` + `sqlite3`
  - Or configure MySQL in `.env`
- Node dependencies installed (`npm install`)

## Local setup
1. Copy env and set key.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
2. Ensure DB configured in `.env`.
3. Run migrations and seed data.
   ```bash
   php artisan migrate --seed
   ```
4. Build assets.
   ```bash
   npm run build
   ```
5. Start app.
   ```bash
   php artisan serve
   ```

## Demo accounts (from seeder)
- Admin: `admin@example.com` / `password`
- Regular users: created by factory (check DB)

## Scenario switch
Control behavior with:
```env
APP_IDOR_SCENARIO=safe
```

Supported values:
- `safe`: all ownership/authorization checks enforced.
- `basic_all`: original training mode (review/profile/book IDOR paths vulnerable).
- `profile_update_only`: only `PUT /users/{user}` bypasses ownership check.
- `hidden_params_review_store`: `POST /books/{book}/reviews` trusts hidden `user_id` in body/JSON.
- `indirect_refs_review_update`: indirect review references (`filename/hash/encoded`) allow cross-user review update.
- `uuid_review_update`: UUID-based review reference route allows cross-user review update.

`APP_VULNERABLE_IDOR=true` is still supported as legacy toggle and maps to `basic_all` when `APP_IDOR_SCENARIO` is not set.

## Secure mode (default)
- Keep `.env`:
  ```env
  APP_IDOR_SCENARIO=safe
  ```
- Expected behavior:
  - User cannot edit/delete another user's review.
  - User cannot update another user's profile (`/users/{id}`).
  - Non-admin cannot create/update/delete books in `/admin/books`.

## Basic vulnerable mode (intentional IDOR)
1. Toggle `.env`:
   ```env
   APP_IDOR_SCENARIO=basic_all
   ```
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```
3. Re-login as normal user and test direct object IDs:
   - `PUT /reviews/{other_user_review_id}`
   - `PUT /users/{other_user_id}`
   - `POST /admin/books` as non-admin
4. Expected: unauthorized object mutations now succeed.

## Profile-update-only vulnerable mode
1. Toggle `.env`:
   ```env
   APP_IDOR_SCENARIO=profile_update_only
   ```
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```
3. Expected:
   - `PUT /users/{other_user_id}` succeeds (IDOR present).
   - Review ownership checks stay enforced (`403`).
   - Admin book checks stay enforced (`403` for non-admin).

## Hidden-params vulnerable mode
1. Toggle `.env`:
   ```env
   APP_IDOR_SCENARIO=hidden_params_review_store
   ```
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```
3. Send review create with hidden parameter:
   - Form payload or JSON payload including `user_id=<victim_id>`.
4. Expected:
   - Review is created/updated for victim user id (IDOR-like mass assignment/trust flaw).
   - Profile update route remains protected.
   - Admin books remain protected.

## Indirect-references vulnerable mode
1. Toggle `.env`:
   ```env
   APP_IDOR_SCENARIO=indirect_refs_review_update
   ```
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```
3. Use one of these review references:
   - Filename-like: `review-<id>.txt`
   - Hash-like: `hash_<value>`
   - Encoded-like: `enc_<value>`
4. Call:
   - `PUT /indirect/reviews/{reference}`
5. Expected:
   - Cross-user review update succeeds via indirect refs.
   - Direct `PUT /reviews/{id}` still enforced (`403` for non-owner).
   - Profile/admin checks remain safe.

## UUID-reference vulnerable mode
1. Toggle `.env`:
   ```env
   APP_IDOR_SCENARIO=uuid_review_update
   ```
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```
3. Use UUID-like review reference (shown in book page sample refs), then call:
   - `PUT /uuid/reviews/{uuid}`
4. Expected:
   - Cross-user review update succeeds via UUID route.
   - Direct `PUT /reviews/{id}` still enforced (`403` for non-owner).
   - Profile/admin checks remain safe.

## Quick lab scenarios
- Review IDOR:
  - User A finds User B review ID.
  - Sends update request to `/reviews/{id}`.
  - Compare secure (`403`) vs vulnerable (`302` + changed data).
- Profile IDOR:
  - User A updates `/users/{userB}`.
  - Compare secure (`403`) vs vulnerable (profile changed).
- Admin book IDOR:
  - Non-admin submits `/admin/books` create form.
  - Compare secure (`403`) vs vulnerable (book created).

## Test execution
```bash
php artisan test
```
If tests fail with `could not find driver`, install/enable required PDO DB extension first.
