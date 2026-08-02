# Phase 1 Regression Checklist

Use this checklist before and after any Phase 2 foundation or feature work. Run tests on a local database copy first.

## Setup

- Start Apache and MySQL/MariaDB.
- Open `http://localhost/IAmStillHere/`.
- Confirm `backend/health.php` returns healthy status locally.
- Use at least two normal users and one admin user.
- Use public, family, and private privacy settings during testing.

## Authentication

- Register a new user with valid data.
- Try registration with duplicate username.
- Try registration with duplicate email.
- Try registration with invalid email.
- Try registration with password shorter than 8 characters.
- Complete email verification with a valid code.
- Try email verification with an invalid code.
- Try email verification with an expired code if test data is available.
- Login with username.
- Login with email.
- Try login with wrong password.
- Confirm suspended users cannot login.
- Logout.
- Confirm protected pages/endpoints reject logged-out users.
- Leave a session idle past timeout and confirm session expires.

## User Profile

- View own profile.
- View another public profile.
- Update full name.
- Update bio.
- Update date of birth.
- Mark profile as memorial.
- Set date of passing.
- Change tribute permission.
- Upload profile photo.
- Upload cover photo.
- Try invalid profile photo type.
- Try oversized profile/cover photo if local PHP limits allow.

## Family

- Search for a user by name/email.
- Send family request.
- Confirm duplicate family request is blocked.
- Accept family request.
- Reject family request.
- Cancel sent family request.
- Remove active family member.
- Confirm family list updates after accept/remove.
- Confirm family-only content is visible to accepted family member.
- Confirm family-only content is hidden from non-family user.
- Confirm private content is hidden from family member.

## Memories

- Upload image memory.
- Upload video memory.
- Upload audio memory.
- Upload document memory.
- Try invalid file extension.
- Try invalid MIME/extension mismatch if possible.
- Try oversized file.
- View own public memory.
- View own family memory.
- View own private memory.
- View another user's public memory.
- Confirm another non-family user cannot view family/private memory.
- Confirm accepted family user can view family memory.
- Download or open uploaded file.
- Delete own memory.
- Confirm deleted memory no longer appears.
- Try deleting another user's memory as normal user.

## Milestones

- Add milestone with required title and date.
- Add milestone with category.
- Add public milestone.
- Add family milestone.
- Add private milestone.
- View timeline as owner.
- View timeline as public visitor.
- View timeline as family member.
- Delete own milestone.
- Try deleting another user's milestone as normal user.

## Events

- Create scheduled event in the future.
- Try creating scheduled event in the past.
- Create public event.
- Create family event.
- Create private event.
- View own scheduled events.
- View another user's public events.
- Confirm private events are hidden from others.
- Delete own event.
- Try deleting another user's event as normal user.
- Run event notification cron on test data.
- Confirm private events are not emailed.
- Confirm notified events are not repeatedly sent.

## Tributes

- Submit tribute as logged-in user.
- Submit tribute as public visitor.
- Submit tribute with optional email.
- Try tribute with invalid email.
- Confirm tribute appears on memorial page.
- Delete own/admin-allowed tribute if supported.
- Confirm deleted tribute no longer appears.
- Confirm tribute permission settings are respected.

## Search

- Search users by username.
- Search users by full name.
- Search users by email.
- Confirm admin users are not exposed in public search where expected.
- Confirm suspended users are not exposed.

## Admin

- Login as admin.
- Open admin dashboard.
- List users.
- Change normal user status to suspended.
- Confirm suspended user cannot login.
- Restore user to active.
- Delete non-admin user only on disposable local data.
- Confirm admin user cannot be deleted through UI/API.
- View activity log.
- View admin statistics.
- View upcoming notifications.
- View notification log.

## Unauthorized Access Attempts

- Call protected create endpoints while logged out.
- Call delete endpoints while logged out.
- Try changing another user's profile.
- Try deleting another user's memory.
- Try deleting another user's milestone.
- Try deleting another user's event.
- Try admin endpoints as normal user.
- Try admin endpoints while logged out.
- Try private content access by changing `user_id` in query string.

## Final Smoke Test

- Refresh homepage.
- Register/login/logout still work.
- Dashboard still loads.
- Profile still loads.
- Upload still works.
- No visible PHP warnings/notices appear in browser.
- Browser console has no new fatal errors.
