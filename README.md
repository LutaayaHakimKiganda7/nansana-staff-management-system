# Nansana Staff Management System

A staff management system for managing government teachers.

## MGTM System, Phases 1 to 4

Fresh clone setup: copy `app/config/config.example.php` to `app/config/config.php`, set your MySQL connection details, create the `mgtm` database, then open `/install.php` to create the tables and first admin account.

## Upgrading from Phase 1
1. Back up your database and files.
2. In phpMyAdmin, import `database/phase2_4.sql` (adds schools, teachers, next of kin, postings).
3. Upload this zip over your install. Do NOT overwrite your `app/config/config.php` (the new one only adds `db_port`; `Core.php` already includes your port fix).
4. Make sure `public/uploads/photos/` is writable (chmod 755 or 775).
Fresh installs: run `/install.php` as before (it now loads both SQL files).

## New in Phases 2 to 4
Schools registration · teacher registration (tabbed form, photo upload with auto-resize, 3 next of kin, auto retirement year, duplicate checks on registration number, NIN and IPPS) · teacher ledger (sortable, filterable, CSV export, print-friendly profile with school history and time at current school) · dashboard with live counts.
Permissions: Admin, MEO and Records Clerk can edit schools and teachers; every role can view. All changes are written to the audit log.

## Phase 5: Transfers and retirement
Upgrade: import `database/phase5.sql`, upload the files (keep your `config.php`; optionally add `'allow_self_approval' => false,`).
- Requests: Transfer and Retirement. Records Clerk, Admin and MEO can submit these requests; Admin/MEO can approve them. Payroll changes and termination requests are not available.
- Every request needs approval from the MEO or System Admin, who cannot approve their own request (config option to allow). Approval applies the change, updates school history, and writes before/after values to the audit log.
- One pending request per teacher at a time. Retirement list shows who is due (by year) and who is overdue.

## Phase 6: Email and SMS
Upgrade: run `database/phase6.sql` once (adds teacher email column and 3 tables), upload files, then install PHPMailer (`composer require phpmailer/phpmailer`, or copy its `src` folder to `app/lib/PHPMailer/src`).
Sign in as Admin > Messaging > Email setup / SMS setup. Secrets are encrypted in the database using a key in `storage/app.key` (back it up with the database; if lost you must re-enter the passwords). Optionally set `'app_key'` in config.
Retirement reminders: add a daily cron job running `php /path/to/mgtm/cron/retirement_alerts.php`.

## Phase 7: Confirmation and biometrics (System Admin only)
Upgrade: run `database/phase7.sql` once, upload files. Admin sees **Verification** in the menu.
- Capture: consent tick, face (webcam or upload), 10 fingerprints (scanner or image upload). Minimum for verification: face + 2 fingerprints (`BiometricsController::MIN_FP`).
- Verify: side-by-side profile photo vs captured face, record check, confirmation tick. Re-capturing biometrics automatically resets verification.
- Samples are encrypted in the database (same key as Phase 6, `storage/app.key`) and served only to the Admin through a controlled route.
- Scanner bridge contract (used by the Electron app in Phase 8): define `window.MGTMFingerprint.capture(slot)` returning a Promise of `{image: "data:image/png;base64,...", template: "optional string", quality: 0-100}`. When present, a Scan button appears on each finger.

## Phase 8: Offline, deployment, backups, import
No database changes. Upload the files.
- **Offline (browser and desktop app):** pages you open and pages saved with *Work offline > Save for offline use* open without internet. Registering or editing a teacher or school offline is saved on the device and sent automatically when back online (review under *Work offline*). Verification, approvals, messaging and biometrics need a connection. Edits are rejected if someone else changed the record meanwhile. Signing out clears saved pages and unsent changes.
- **Security:** HTTPS redirect, HSTS and a strict content-security policy are set in `public/.htaccess`.
- **Hosting:** point the domain at `public/`. If you cannot, copy `deploy/root.htaccess` to the project root as `.htaccess` (URLs then contain `/public/`).
- **Backups:** Admin > Backups (back up now, download), plus a daily cron `php cron/backup.php`. Keep `storage/app.key` somewhere separate.
- **Import:** Teachers > Import CSV (template, check, then confirm; all or nothing, each teacher audited).
