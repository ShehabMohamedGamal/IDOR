# IDOR Book Review Lab: Plan + Implementation Sequence

## Summary
- Add this plan doc at `docs/idor-book-review-plan.md`.
- Build Laravel 13 Blade app with Breeze auth, admin-managed books, and user reviews/profiles.
- Keep authorization secure by default, with scenario-based vulnerability simulation via `APP_IDOR_SCENARIO`.

## Key Changes
### Auth and roles
- Use Breeze (Blade) for authentication scaffolding.
- Add `users.role` with values `admin|user`, default `user`.
- Add `admin` middleware for `/admin/*` endpoints.

### Data model
- Add `books` table: `title`, `author`, `published_year` nullable.
- Add `reviews` table: `book_id`, `user_id`, `rating` (1-5), `comment`, and unique (`book_id`, `user_id`).
- Add Eloquent relations across `User`, `Book`, and `Review`.

### Authorization and IDOR toggle
- Add `config/security.php` with scenario mode sourced from `APP_IDOR_SCENARIO`.
- In secure mode, enforce policies:
  - Profile: only owner can view/update.
  - Review: only owner can edit/update/delete.
  - Book: only admin can create/update/delete.
- In vulnerable mode, intentionally bypass authorization checks in profile/review/book mutation handlers.

### Routes and UI
- Public routes: book listing and detail pages.
- Auth routes: create/update/delete own reviews, view/update user profile by `/users/{user}`.
- Admin routes: `/admin/books` CRUD.
- Blade UI remains simple with validation and flash messages.

## Public Interfaces
- Env var: `APP_IDOR_SCENARIO=safe|basic_all|profile_update_only`.
- Routes:
  - Auth routes from Breeze.
  - `GET /books`, `GET /books/{book}`
  - `POST /books/{book}/reviews`
  - `GET|PUT|DELETE /reviews/{review}`
  - `GET|PUT /users/{user}`
  - `GET|POST|PUT|DELETE /admin/books/{book?}`

## Test Plan
- Feature tests with `RefreshDatabase` and seeded admin + two normal users + sample books/reviews.
- Secure mode (`APP_IDOR_SCENARIO=safe`):
  - User cannot edit another user's review (`403`).
  - User cannot update another user's profile (`403`).
  - Non-admin cannot mutate books (`403`).
- Basic vulnerable mode (`APP_IDOR_SCENARIO=basic_all`):
  - Cross-user review/profile/book mutation succeeds via direct object IDs.
- Profile-update-only vulnerable mode (`APP_IDOR_SCENARIO=profile_update_only`):
  - Only cross-user profile update succeeds; review and book checks stay enforced.
- Smoke checks:
  - Guests blocked from protected routes.
  - Owner happy paths pass.
  - Admin book CRUD happy path passes.

## Assumptions
- Plan file path is `docs/idor-book-review-plan.md`.
- SQLite is used locally for dev/testing.
- One review per user per book.
- Scenario defaults to safe (`APP_IDOR_SCENARIO=safe`).
