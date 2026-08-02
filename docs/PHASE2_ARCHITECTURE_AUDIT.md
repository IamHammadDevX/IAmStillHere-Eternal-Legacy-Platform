# Phase 2 Architecture Audit

## Current Architecture Summary

I Am Still Here / IamAlwaysHere is a plain PHP application with Bootstrap 5 and vanilla JavaScript. It does not use a framework, Composer application autoloading, or a central router. Public pages live in `index.php` and `frontend/*.php`. JSON endpoints live under `backend/*/*.php`. Shared configuration and helper functions live in `config/config.php`, and database access is centralized through `config/database.php`.

The application is currently deployed and developed as a document-root style PHP app, suitable for Apache/XAMPP locally and cPanel in production.

## Actual Project Folder Structure

```text
backend/
  admin/
  auth/
  cron/
  events/
  family/
  helpers/
  memories/
  memorials/
  milestones/
  phpmailer/
  tributes/
  users/
config/
frontend/
  css/
  images/
  js/
docs/
index.php
schema.sql
README.md
LOCAL_SETUP.md
TESTING.md
```

## Application Entry Points

- `index.php` is the public landing page.
- `frontend/login.php`, `frontend/register.php`, `frontend/dashboard.php`, `frontend/profile.php`, `frontend/admin.php`, `frontend/memorials.php`, and related pages are direct browser entry points.
- `backend/**/*.php` files are direct JSON/API endpoints.
- `backend/cron/send_event_notifications.php` is intended for scheduled execution.
- `backend/cron/trigger_notifications.php` includes the notification cron script.

## Current Request Flow

Browser pages load PHP-rendered HTML, Bootstrap assets from CDN, and project JavaScript files. JavaScript calls backend PHP endpoints directly using hardcoded local URLs in several places, commonly `http://localhost/IAmStillHere/...`.

There is no central request bootstrap for all endpoints. Most endpoints include `config/config.php`, which starts a PHP session and loads database configuration.

## Frontend Pages and JavaScript Files

Frontend PHP pages:

- `frontend/login.php`
- `frontend/register.php`
- `frontend/verify_email.php`
- `frontend/forgot_password.php`
- `frontend/reset_password.php`
- `frontend/dashboard.php`
- `frontend/profile.php`
- `frontend/memorials.php`
- `frontend/family_requests.php`
- `frontend/approve_family.php`
- `frontend/admin.php`

JavaScript files:

- `frontend/js/auth.js`
- `frontend/js/dashboard.js`
- `frontend/js/profile.js`
- `frontend/js/family.js`
- `frontend/js/family_requests.js`
- `frontend/js/search.js`

## Backend Endpoint Inventory

Authentication:

- `backend/auth/login.php`
- `backend/auth/logout.php`
- `backend/auth/check_session.php`
- `backend/auth/register.php`
- `backend/auth/send_verification.php`
- `backend/auth/verify_code.php`
- `backend/auth/request_reset.php`
- `backend/auth/verify_reset_code.php`
- `backend/auth/reset_password.php`

Users:

- `backend/users/profile.php`
- `backend/users/update_profile.php`
- `backend/users/search.php`
- `backend/users/find.php`
- `backend/users/memorial_settings.php`

Memorials:

- `backend/memorials/list.php`

Memories:

- `backend/memories/upload.php`
- `backend/memories/list.php`
- `backend/memories/delete.php`

Milestones:

- `backend/milestones/create.php`
- `backend/milestones/list.php`
- `backend/milestones/delete.php`

Events:

- `backend/events/create.php`
- `backend/events/list.php`
- `backend/events/delete.php`

Tributes:

- `backend/tributes/create.php`
- `backend/tributes/list.php`
- `backend/tributes/delete.php`
- `backend/tributes/get_count.php`

Family:

- `backend/family/add.php`
- `backend/family/list.php`
- `backend/family/find.php`
- `backend/family/remove.php`
- `backend/family/respond_request.php`
- `backend/family/pending_requests.php`
- `backend/family/sent_requests.php`
- `backend/family/cancel_request.php`

Admin:

- `backend/admin/users.php`
- `backend/admin/activity_log.php`
- `backend/admin/notification_log.php`
- `backend/admin/notification_stats.php`
- `backend/admin/upcoming_notifications.php`

Cron:

- `backend/cron/send_event_notifications.php`
- `backend/cron/trigger_notifications.php`

## Current Authentication Flow

Login uses `backend/auth/login.php`, verifies a bcrypt/password-hash value from the `users` table, regenerates the session ID, and stores:

- `$_SESSION['user_id']`
- `$_SESSION['username']`
- `$_SESSION['user_role']`
- `$_SESSION['full_name']`
- `$_SESSION['last_activity']`

`config/config.php` provides compatibility helpers:

- `is_logged_in()`
- `get_user_role()`
- `is_admin()`
- `is_client()`

Registration has two flows:

- `backend/auth/register.php` directly creates a user.
- `backend/auth/send_verification.php` stores pending verification data, then `backend/auth/verify_code.php` creates the user.

The active frontend registration flow appears to use the email verification path.

## Current Role and Permission Matrix

| Role | Current Meaning | Current Checks |
| --- | --- | --- |
| `admin` | Administrative user | `is_admin()` or direct session checks |
| `client` | Registered user | Default registration role |
| `visitor` | Public visitor | Mostly implicit unauthenticated access |

Privacy levels:

| Privacy | Current Meaning |
| --- | --- |
| `public` | Publicly visible |
| `family` | Visible to accepted family members |
| `private` | Owner/admin only in most flows |

Access control is implemented endpoint-by-endpoint. There is no central policy layer in the current Phase 1 code.

## Current Database Configuration

Actual database implementation is MySQL/MariaDB through PDO in `config/database.php`.

Current hardcoded settings:

- Host: `localhost`
- Database: `eternal_legacy`
- User: `root`
- Password: empty
- Driver: `mysql`
- Charset: `utf8mb4`

The code does not currently load DB credentials from environment variables.

## Actual Database Engine Used

The application uses MySQL/MariaDB. This is confirmed by:

- `config/database.php` DSN: `mysql:host=...`
- `schema.sql` uses `AUTO_INCREMENT`, `ENGINE=InnoDB`, `ENUM`, and MySQL timestamp syntax.

## Existing Database Table Inventory

From `schema.sql`:

- `users`
- `memories`
- `milestones`
- `scheduled_events`
- `family_requests`
- `family_members`
- `tributes`
- `activity_log`
- `email_verifications`
- `password_resets`
- `sessions`

## Current Upload and Media Flow

Memory uploads use `backend/memories/upload.php`.

Profile and cover uploads use `backend/users/update_profile.php`.

Configured upload root:

- `DATA_PATH/uploads`

Configured categories:

- `photos`
- `videos`
- `audio`
- `documents`

Risks:

- Validation allows MIME type or extension match.
- SVG is allowed.
- Uploaded files are stored under a web-accessible path.
- Profile upload validation is narrower than memory upload validation but still extension-based.
- FFmpeg conversion flags exist, but upload conversion is not functionally implemented in the current upload flow.

## Memory, Milestone, Family, Tribute, Event, and Admin Modules

Memory module:

- Upload, list, delete.
- Privacy filtering is implemented in list endpoints.

Milestone module:

- Create, list, delete.
- Uses owner/admin checks for destructive actions.

Family module:

- Request, respond, list, remove, cancel.
- Some endpoints accept `user_id` from request data or query params.

Tribute module:

- Create/list/delete tributes.
- Supports public visitor authors with optional email.

Event module:

- Create/list/delete scheduled events.
- Cron sends upcoming event notifications.

Admin module:

- User list/status/delete.
- Activity log.
- Notification log/statistics/upcoming notifications.

Known admin issue:

- Some notification endpoints check `$_SESSION['role']`, but login sets `$_SESSION['user_role']`.

## Current Event and Email Automation Flow

Email is sent through bundled PHPMailer source under `backend/phpmailer`.

Main helper:

- `backend/helpers/EmailHelper.php`

Email flows:

- Verification code
- Family request email
- Password reset email
- Scheduled event notification email

Cron:

- `backend/cron/send_event_notifications.php` finds scheduled events within the next hour, sends email to active approved family members, then marks events as notified.

Risk:

- SMTP credentials are hardcoded in source.
- Cron logs recipient email addresses to `data/logs/event_notifications.log`.
- Email URLs are hardcoded to localhost.

## Cron Jobs or Scheduled Scripts

Existing scripts:

- `backend/cron/send_event_notifications.php`
- `backend/cron/trigger_notifications.php`

Suggested cPanel cron command should call the PHP binary with `backend/cron/send_event_notifications.php`.

## Environment Variables and Configuration Handling

Current application config is constant-based and hardcoded. There is no `.env` loader and no dependency-based config system.

`config/env.example` has been added as documentation only. `EnvironmentValidator.php` has been added but is not globally integrated to avoid breaking the current running app.

## Production-Specific Assumptions

- Apache/cPanel style deployment.
- Project may live directly under `public_html` or a subfolder.
- Database credentials are manually edited in `config/database.php`.
- Uploads live under project-local `data/uploads`.
- Email credentials are currently in source and should be moved out before future production work.
- Many frontend URLs assume `/IAmStillHere/`.

## Security Findings

High:

- SMTP credentials are hardcoded in `backend/helpers/EmailHelper.php`.
- No CSRF protection for state-changing requests.
- Some endpoints trust browser-supplied `user_id`.
- Upload validation allows extension fallback and SVG.
- Uploaded content is web-accessible.
- Admin notification endpoints use an incompatible session key.

Medium:

- `display_errors` is enabled in `config/config.php`.
- Several endpoints return raw exception messages.
- Password reset and email verification lack clear rate limiting.
- Public search exposes user data.
- Cron logs may contain personal data.
- Hardcoded localhost URLs make production behavior fragile.

Low:

- Duplicate registration paths.
- README and code disagree on database engine and setup.
- No central API response format.
- No request IDs or structured logging.
- No migration tracking before this foundation work.

## Phase 2 Readiness Gaps

- Centralized authorization policy needed before private/family/social/vault features.
- Upload hardening needed before video thumbnails, vault, or media-heavy features.
- Config/env handling needed before production-safe integrations.
- Migration system needed before schema changes.
- Regression checklist needed before UI redesign.
- Email and cron observability need improvement before automated messaging expansion.
- API response consistency needed before adding new endpoints.

## High-Risk Files That Should Not Be Changed Without Tests

- `config/config.php`
- `config/database.php`
- `backend/helpers/EmailHelper.php`
- `backend/auth/login.php`
- `backend/auth/send_verification.php`
- `backend/auth/verify_code.php`
- `backend/auth/reset_password.php`
- `backend/memories/upload.php`
- `backend/users/update_profile.php`
- `backend/family/add.php`
- `backend/family/respond_request.php`
- `backend/cron/send_event_notifications.php`
- `frontend/js/dashboard.js`
- `frontend/js/profile.js`

## Recommended Refactoring Order

1. Add regression checklist and migration tracking.
2. Add API response, request context, and logging helpers.
3. Add compatibility auth/role/policy helpers without changing existing behavior.
4. Move secrets and URLs into environment-backed config.
5. Fix admin session key mismatch.
6. Harden uploads.
7. Add CSRF protection for write endpoints.
8. Convert endpoints gradually to shared auth/policy/response helpers.
9. Add Phase 2 schema migrations.
10. Implement Phase 2 features one module at a time.

## Documentation/Code Inconsistencies

- README says PostgreSQL; code uses MySQL/MariaDB.
- README says environment variables are used for DB; code uses hardcoded DB credentials.
- README references a default admin password, but no default admin creation is present.
- README lists `schema.sql`; this repo now has `schema.sql`, but original setup did not reliably include it.
- Docs suggest upload paths and production assumptions that may not match cPanel structure.
- Frontend and email templates hardcode localhost URLs.

## Open Questions

- What is the production database name, driver, and cPanel DB username?
- Is production currently MySQL/MariaDB or PostgreSQL anywhere outside this repo?
- Should public visitor tribute posting remain open?
- Should family relationships be directional or mutual?
- Should uploaded media remain web-accessible or move behind controlled download endpoints?
- Should SVG uploads be allowed?
- What PHP version is production cPanel running?
- What is the production base URL: `iamalwayshere.com`, subfolder, or another path?
- Which registration flow is canonical: direct register or email verification?
- Should cron send emails only once, or retry failed sends?
