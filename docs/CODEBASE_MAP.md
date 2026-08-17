# IAmStillHere codebase map

Last inspected: 2026-08-17.

## Snapshot

- Runtime: PHP endpoint/page application; PDO + MySQL (`config/database.php`).
- Frontend: PHP-rendered pages, Bootstrap CDN, vanilla JS, one shared stylesheet.
- Size: 290 PHP files, 15 frontend JS files, about 27,934 application lines (excluding vendored PHPMailer language files).
- Existing generated graph: `.code-review-graph/graph.db` and `graph.html`.
- Main entrypoint: `index.php`.
- Global bootstrap: `config/config.php` -> environment loader -> database configuration.

## Request/data flow

```text
Browser/PHP page
  -> frontend/*.php or index.php
  -> frontend/js/*.js fetch('/backend/<domain>/<endpoint>.php')
  -> endpoint bootstrap/helper
  -> Database::getConnection() -> MySQL
  -> ApiResponse JSON (newer endpoints) or direct JSON/redirect (legacy endpoints)
```

Most newer domains follow:

```text
config.php + ApiResponse + SessionHelper + CsrfHelper + Logger
  -> domain helper (_*_helpers.php)
  -> PrivacyService / NotificationService / domain SQL
```

## Domain map

| Domain | Location | Responsibility |
|---|---|---|
| Auth | `backend/auth`, `frontend/login.php`, `register.php` | session, registration verification, login, reset |
| Users/memorials | `backend/users`, `backend/memorials`, `frontend/profile.php`, `memorials.php` | profiles, memorial settings, public discovery |
| Memories | `backend/memories`, `frontend/js/dashboard.js` | media/document upload, list, update, delete |
| Timeline | `backend/milestones`, `backend/events`, `backend/on_this_day` | milestones and scheduled/published events |
| Social | `backend/friends`, `backend/family`, `backend/tributes`, `backend/posts` | relationships, requests, tributes, posts/comments |
| Journeys | `backend/journeys`, `frontend/js/journeys.js` | shared collections, invitations, contributions, review |
| Vault | `backend/vault`, `frontend/js/privacy.js` | encrypted documents, folders, permissions, re-auth, audit logs |
| AI | `backend/ai`, `backend/services/AI*`, `frontend/js/ai_*.js` | chat, knowledge ingestion/search, avatar, autobiography, personalized messages |
| Privacy | `backend/services/PrivacyService.php`, `backend/privacy/rules` | public/family/friends/specific/private/release-event checks |
| Operations | `backend/cron`, `backend/admin`, `backend/health.php`, `backend/migrations` | jobs, admin views, health, migrations |

## Shared dependency hubs

- `config/config.php`: session start, constants, environment loading, utility functions, DB include. Changes here affect every page/API.
- `config/database.php`: single MySQL connection factory. No framework/container.
- `backend/helpers/ApiResponse.php`: normalized JSON response contract for newer APIs.
- `backend/helpers/SessionHelper.php`, `CsrfHelper.php`, `RequestContext.php`: auth/request security context.
- `backend/services/PrivacyService.php`: central visibility decision point. Any content visibility change should begin here.
- `backend/services/NotificationService.php`: cross-domain notification writes.
- `backend/helpers/Logger.php`: error/activity logging.
- `schema.sql` plus migration files: database contract. Verify live schema before changing SQL.

## Database graph

```text
users
  -> memories, milestones, scheduled_events, journeys, vault_documents
  -> family_requests/family_members
  -> friend_requests/friendships
  -> posts/comments, notifications, tributes, activity_log

memories/milestones/events
  -> privacy_rules/privacy_rule_users
  -> journeys/journey_items

journeys
  -> journey_participants/journey_invitations/journey_items/journey_media

vault_documents
  -> vault_folders/vault_permissions/vault_access_logs

AI sources/conversations/messages/autobiographies
  -> AI services/providers and scheduled AI jobs
```

## Important implementation boundaries

1. Preserve endpoint response shape when editing frontend-connected APIs; inspect its JS caller first.
2. Use the domain helper already present before adding new auth/CSRF/privacy/notification logic.
3. Treat `PrivacyService::canView()` as authoritative for content visibility; do not duplicate partial checks.
4. Treat vault code as a separate security boundary: re-auth, CSRF, verified window, ownership/permission, encryption, and access log all matter.
5. Scheduled behavior is driven by `backend/cron/*`; web requests do not reliably publish/process jobs.
6. AI flows cross endpoint, service, provider interface, and ingestion/search tables; change all layers together.

## Verified drift/risk points

- README claims PostgreSQL compatibility, but runtime configuration is MySQL and `schema.sql` uses MySQL syntax (`AUTO_INCREMENT`, `ENUM`, `ENGINE=InnoDB`).
- README's feature/API list is older than the actual domains (journeys, posts, friends, vault, AI, automations, privacy rules).
- `schema.sql` appears older than current code: current code references newer tables/columns such as `friendships`, `privacy_rules`, `journeys`, vault tables, and `folder_id` on memories. Check migrations/live DB before schema edits.
- `config.php` enables `display_errors=1`; production deployment must override this.
- Upload/security behavior is split between legacy endpoints and newer helper-based endpoints; do not assume all endpoints share the same response/security contract.
- Vendored PHPMailer under `backend/phpmailer` is third-party code; avoid editing it for application changes.

## Future change protocol

```text
1. Identify page/JS caller.
2. Identify exact backend endpoint.
3. Read endpoint helper imports and response shape.
4. Trace tables/columns and privacy/auth checks.
5. Patch smallest owning file(s).
6. Run PHP syntax checks + focused smoke/test path.
7. Check graph/docs/schema impact before handoff.
```

This map is the working architectural baseline; update it when adding a new domain, shared service, table family, or cross-domain job.
