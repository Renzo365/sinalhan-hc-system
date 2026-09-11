# System Changelog & Release Notes
### Barangay Sinalhan Health Center Patient Management System

This document records the official beta release history and updates automatically maintained by the **Multi-Agent AI Engineering Squad**.

---

## [Beta 1.4] - 2026-09-09
### Clinical Care Workstation Real-Time URL Routing & Multi-PC Database Consolidation Fix
* **Workstation Navigation & Dedicated URLs**
  * Enabled real-time HTML5 `history.pushState` address bar synchronization across all 9 Clinical Care Workstation tabs (`#tab-overview`, `#tab-ihp`, `#tab-pcb`, `#tab-consultations`, `#tab-vitals`, `#tab-immunizations`, `#tab-prenatal`, `#tab-wellbaby`, `#tab-appointments`).
  * Added full page-refresh (`F5`) and direct URL bookmark persistence, eliminating unexpected resets to the *Overview* tab.
  * Integrated browser Back and Forward history navigation (`popstate` / `hashchange`).
  * Added smart alias and shorthand resolution (`#ihp`, `#pcb`, `#consultations`, `#vitals`, `#appointments`, etc.).
* **Database Setup & Multi-Device Deployment Fix**
  * Resolved fatal `SQLSTATE[42S02]: Table 'pcb_obligated_services' doesn't exist` (HTTP 500 error) during multi-PC fresh installations.
  * Added missing `CREATE TABLE` definitions for `pcb_obligated_services` and `pcb_service_logs` into `database/complete_setup.sql` and `database/schema.sql`.
  * Verified automated one-click installation batch script (`setup_db.bat`) cleanly initializes all 20 tables on any secondary device without migration discrepancies.
  * Updated database architectural documentation in `docs/database.md`.

---

## [Beta 1.3] - 2026-09-07
### PhilHealth PCB Patient Ledger (Page 3), Category Realignment & Patient Directory DataTables
* **PhilHealth Annex A1 & PCB Compliance (Page 3)**
  * Implemented dedicated **PHIC / PCB Ledger** tab (`#tab-pcb`) in the Clinical Care Workstation between *IHP History* (Pages 1–2) and *Consultations* (Page 4).
  * Added **Card 1: Obligated Services Annual Surveillance Matrix** for quarterly BP tracking, annual Clinical Breast Examination (CBE), and Visual Inspection with Acetic Acid (VIA), with hypertension tracking and target year selector.
  * Added **Card 2: Diagnostic Examination & PCB Services Encounter Ledger** with client-side category filter pills (`All`, `Diagnostic`, `PCB1`, `Other`), status tracking (`Given` in clinic vs. `Referred`), referral facility input, and datalist suggestions.
  * Created backend model `app/Models/PcbLedger.php` and controller `app/Controllers/PcbLedgerController.php` with RBAC authorization and audit logging.
  * Aligned PhilHealth membership category dropdowns in Patient Registration (`patients/create.php`) and Profile Edit (`patients/edit.php`) to the 4 official classifications: *Sponsored*, *Individually Paying Program (IPP)*, *Employed*, and *Lifetime Member*.
* **Patient Directory Scalability & DataTables**
  * Upgraded Patient Directory (`patients/index.php`) from a standard HTML table to DataTables with Bootstrap 5 styling.
  * Integrated client-side pagination (10, 25, 50, 100 per page), multi-column sorting (Patient ID, Name, Age/Sex, Family No., PHIC PIN, Registered Date), instant quick search, and responsive layout.
  * Retained full compatibility with server-side filters (`sex`, `barangay`, `civil_status`, `sort`, `order`).

---

## [Beta 1.2] - 2026-09-09
### User Account Module (/users) Hardening & Architectural Refactoring
* **Security**
  * Added CSRF defense-in-depth token verification (`hash_equals`) across all 5 mutation endpoints (`store`, `update`, `resetPassword`, `toggleStatus`, `resetLockout`).
  * Enforced automatic `SECURITY_VIOLATION` audit trail logging when CSRF tokens are missing or tampered.
  * Aligned `AdminMiddleware` with `AuthMiddleware` to inherit the 15-minute server-side inactivity session timeout and activity heartbeat refresh.
  * Added strict backend regex validation for usernames (`^[a-zA-Z0-9_]{3,20}$`).
  * Added RFC email format validation via `filter_var(..., FILTER_VALIDATE_EMAIL)` and Philippine mobile number formatting (`^09\d{9}$`).
* **Architecture & MVC**
  * Eliminated direct database model instantiation (`new User()`) from `app/Views/users/index.php`.
  * Pre-computed account lockout status (`lockout_info`) in `UserController@index` prior to view dispatch.
  * Added real-time session full name (`$_SESSION['user_fullname']`) synchronization when administrators edit their own account details.
* **UI / UX**
  * Added form state retention (`$_SESSION['old_input']`) on update validation failures so typed input is never erased in `users/edit.php`.
  * Added an error alert banner on `users/edit.php` matching the registration form.
  * Added a one-click **"Generate Secure Password"** helper button in the Password Reset Modal (generates a 14-character secure string and automatically copies it to clipboard).
  * Expanded table action button touch targets to >= 34px with responsive flex wrapping.

---

## [Beta 1.1] - 2026-09-09
### User Profile Self-Service & Modern Topbar Dropdown
* **Added**
  * Added self-service User Profile page (`GET /profile`) allowing staff and administrators to view employment metadata.
  * Added personal contact information update form (`POST /profile/update`) with real-time topbar synchronization.
  * Added voluntary password change module (`POST /profile/password`) with current password verification, 8+ character rule, and bcrypt hashing.
  * Added `PROFILE_UPDATED` and `USER_PASSWORD_CHANGED` audit logging.
* **UI / UX**
  * Upgraded topbar header with circular avatar badge displaying the user's capitalized first initial on a healthcare teal background (`#0d9488`).
  * Added user role subtitle (*Admin*, *Co-Admin*, *Staff*) and dropdown chevron.
  * Added Bootstrap 5 dropdown menu containing direct links to **My Profile**, **Password Settings**, and CSRF-protected SweetAlert2 logout.
* **Security**
  * Added column whitelisting in `User::updateProfile()` to guarantee zero privilege escalation (cannot modify role, status, or username).
  * Added `session_regenerate_id(true)` upon password changes to defend against session fixation attacks.

---

## [Beta 1.0] - Initial Release
### Core Health Center Patient Management System
* **Added**
  * Patient demographic registration, family indexing, and profile directory.
  * Triage vital signs recording with automated BMI calculation.
  * SOAP clinical consultation workflow.
  * Daily patient queue management and live fullscreen TV lobby display.
  * Appointment scheduling with program type conflict prevention.
  * Maternal & Pre-Natal Care module (Naegele EDC, dynamic AOG, GTPAL).
  * Well Baby & Pediatric Growth Monitoring (DOH EPI vaccination matrix, growth logs).
  * PhilHealth PCB patient ledger.
  * Administrative user account provisioning, role hierarchy, and 15-minute login lockout.
  * Immutable system audit logging and database backup snapshot utilities.
