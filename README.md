# IamAlwaysHere

IamAlwaysHere is a privacy-first social networking and digital legacy platform for preserving life stories, connecting families, sharing memories, and preparing meaningful communications for the future.

Platform combines memorial profiles, media-rich memories, personal timelines, family relationships, tributes, encrypted document storage, scheduled messages, and approved-source AI experiences in one responsive product.

## Product capabilities

- Personal and memorial profiles with granular visibility controls
- Photo, video, document, and formatted-post memories
- Folders, albums, comments, reactions, and family connections
- Expandable life timelines with milestones and child updates
- Tributes and remembrance messages
- Scheduled events, posts, and personalized future messages
- Shared journeys and collaborative family storytelling
- AI Avatar grounded only in user-approved knowledge sources
- AI-assisted autobiography drafting and chapter regeneration
- Secure Vault with encrypted documents and email-verified downloads
- Role-based administration, moderation, audit logs, and AI-usage tracking
- Responsive interfaces for desktop, Android, iOS, and tablets

## Privacy and trust model

Content can be public, family-only, shared with selected users, or private. Server-side authorization remains authoritative; client-side visibility is never an access-control boundary.

Sensitive workflows use CSRF validation, PDO prepared statements, password hashing, session checks, file validation, audit logging, and verification-code gates. Vault documents use authenticated encryption before storage.

AI features use approved platform knowledge sources. Generated content remains reviewable by account owners and is not legal, medical, or financial advice.

## Technology

- PHP 8+
- MySQL with PDO
- Bootstrap 5 and Bootstrap Icons
- Vanilla JavaScript and Fetch API
- PHPMailer
- OpenAI-compatible chat and embedding providers
- Apache/cPanel-compatible deployment

## Repository structure

- backend: JSON endpoints, domain services, email, AI, and Vault logic
- config: application and database configuration
- data: runtime uploads and protected application data
- docs: project documentation
- frontend: PHP views, JavaScript, CSS, and image assets
- scripts: maintenance and operational scripts
- schema.sql: database schema
- TESTING.md: test and QA guidance
- LOCAL_SETUP.md: local environment instructions

## Local setup

Requirements:

- PHP 8+ with PDO MySQL, OpenSSL, cURL, mbstring, and fileinfo
- MySQL 5.7+ or MySQL 8+
- Apache, Nginx, or PHP development server
- SMTP credentials for email verification
- AI provider key when AI features are enabled

Installation:

1. Clone repository.
2. Create MySQL database and import schema.sql.
3. Configure database, mail, application URL, session, and AI settings.
4. Make runtime upload directories writable by web-server user.
5. Serve project through HTTPS in production.

Read LOCAL_SETUP.md for environment-specific details.

Never commit .env, credentials, API keys, production logs, user uploads, or encryption secrets.

## Development

Start local server with: php -S 127.0.0.1:5000

PHP development server is not for production. Production requires HTTPS, secure cookies, restricted file permissions, protected runtime directories, disabled error display, backups, and tested scheduled jobs.

## Testing

Run php -l on changed PHP files and node --check on changed JavaScript files. Read TESTING.md for feature-level QA.

Security-sensitive changes must test successful requests plus unauthorized, invalid-CSRF, expired-code, invalid-file, and cross-user access cases.

## API conventions

Backend endpoints live under backend and generally return JSON. Authenticated mutations must validate session state, authorization, request method, CSRF token, input shape, and resource ownership on server.

Undocumented endpoints are not a stable public API.

## Deployment checklist

- Enforce HTTPS and secure session cookies
- Keep secrets outside web root and source control
- Disable PHP error display
- Block script execution in upload paths
- Configure SMTP and scheduled jobs
- Back up database, Vault data, and encryption material
- Review upload and request-size limits
- Verify robots.txt, privacy policy, and Terms links
- Smoke-test registration, login, uploads, privacy, AI, email, and Vault

## Responsible use

Platform may contain sensitive personal, memorial, and family information. Deployers remain responsible for consent, retention, privacy compliance, moderation, backups, recovery, and applicable law.

Report security issues privately to project owner. Do not publish exploit details.

## Contributing

1. Create focused branch.
2. Preserve privacy and authorization behavior.
3. Avoid unrelated formatting changes.
4. Add tests for behavior changes.
5. Document database and deployment requirements.

## License

Licensed under MIT License. See LICENSE.

Copyright (c) 2026 SV mobile teleshoppe pvt. ltd.