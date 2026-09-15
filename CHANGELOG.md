# System Changelog & Release Notes
### Barangay Sinalhan Health Center Patient Management System

This document records the official beta release history and updates automatically maintained by the **Multi-Agent AI Engineering Squad**.

---

## [Beta 1.8] - 2026-09-14
### Clinical Care Workstation Table Actions Refactoring & Child Growth Modal
* **Added**: Child Growth Visit Details Modal (`#viewGrowthLogModal`) displaying comprehensive anthropometric metrics (Weight, Height, Head Circumference, Chest Circumference, Temperature), infant feeding practices, micronutrient supplementation, and TCB developmental milestones / clinical remarks with DOM XSS prevention (`.textContent`) and automatic backdrop cleanup (`hidden.bs.modal`).
* **Changed**: Workstation Table Actions flattened from nested kebab dropdowns (`bi bi-three-dots-vertical`) to direct inline icon action buttons across Consultations, Vital Signs, Universal Immunizations, Prenatal Serial Follow-Up Visits, Appointments, and Child Growth tables, permanently bypassing `.table-responsive` overflow clipping.
* **Changed**: Compacted Vital Signs Log (streamlined to 6 columns: Date/Time, Blood Pressure, Heart Rate, Respiration Rate, Temperature, Actions) and Well Baby Growth Monitoring table (streamlined to 7 columns: Date, Age, Weight, Height, Feeding, Recorded By, Actions) for optimal visibility on 1366x768 clinic laptop displays.
* **Fixed**: Table kebab menu clipping and vertical scrollbar jumpiness when consultation, vital signs, or growth history contains fewer than 3 records inside `.table-responsive` card bodies.
* **Security**: Enforced CSRF defense-in-depth across all inline action and deletion forms (`archiveConsultationForm`, `deleteVitalForm`, `deleteImmunizationForm`, `deletePrenatalVisitForm`, `cancelAppointmentForm`, and child growth deletion) alongside role-restricted authorization verification.
* **Quality & Assurance**: 91/91 automated assertions passed via `scratch/test_table_actions_refactor.php` verifying zero kebab dropdowns, correct inline icon presence, table header and empty-state colspan synchronization, modal DOM structure, data attribute hydration, and CSRF token integrity.

---

## [Beta 1.7] - 2026-09-14
### Clinical Care Workstation UI/UX Modernization & Archived Records Hub Overhaul
* **Added**: Centralized **Archived Records Hub** (`GET /archive`, aliased with `/archive/patients`) strictly restricted to Administrators (`AdminMiddleware`), featuring a tabbed architecture: Tab 1 (Archived Patients) and Tab 2 (Archived Consultations) with live counter badges.
* **Added**: Soft-delete (Archive) and Admin-only restoration lifecycle for Consultations with mandatory reason tracking via SweetAlert2, CSRF token validation, and immutable audit logging (`CONSULTATION_ARCHIVED`, `CONSULTATION_RESTORED`).
* **Added**: Comprehensive `#viewVitalsModal` in the Clinical Care Workstation displaying complete physiological and anthropometric measurements (BP with triage coloring, Heart Rate, Respiration Rate, Temperature, Weight, Height, Asian BMI badge, $SpO_2$, Waist Circumference) and Clinical Notes / Symptoms with DOM XSS prevention (`.textContent`).
* **Added**: Secure, role-restricted Delete actions for Vital Signs, Universal Immunizations, and Prenatal Serial Follow-Up Visits with SweetAlert2 confirmations and CSRF defense-in-depth.
* **Changed**: Modernized Clinical Care Workstation action buttons across Consultations, Vitals, Immunizations, Prenatal Visits, and Appointments into unified Bootstrap 5 "More Actions" (`bi bi-three-dots-vertical`) dropdown menus, eliminating button crowding and visual clutter.
* **Changed**: Streamlined Vital Signs Log table by hiding $SpO_2$ and Waist Circumference from direct table columns to prevent horizontal scrolling on 1366x768 clinic laptop displays.
* **Security**: Enforced relational safety lock: vital signs linked to active consultations (`deleted_at IS NULL`) are protected against deletion until the parent consultation is soft-deleted, safeguarding SOAP objective audit integrity.
* **Security**: Role-based deletion boundaries restricting standard staff to deleting only their own authored/administered records, while granting supervisory deletion override authority to Administrators.
* **Quality & Assurance**: 116/116 automated assertions verified by `sinalhan_qa` across CSRF enforcement, consultation lifecycle, relational locks, role authorization, XSS escaping, boundary conditions, session inactivity timeouts, regression smoke checks, and pristine zero-collateral teardown.

---

## [Beta 1.6] - 2026-09-12
### Authentication Portal Color Palette Alignment
* **Improved**: Authentication portal styling aligned with the primary healthcare teal theme (`#0D7377`, `#0A3D40`, `#14A3A8`, `#095B5E`), harmonizing button states, left branding panel, and interactive elements with the core workstation.
* **Improved**: Privacy Policy & Terms of Use modal headers standardized to solid healthcare teal (`#0D7377`) with crisp white typography and zero gradients.
* **Fixed**: Removed legacy dark pine greens (`#0b3b32`, `#082d26`, `#06231e`) across buttons, brand backgrounds, input focus borders, and staff identity avatars.
* **Quality & Assurance**: 110/110 automated tests passed across color assertions and auth regression suites with zero syntax or functional regressions.

---

## [Beta 1.5.5] - 2026-09-12
### PhilHealth Annex A1 (IHP) Tab Layout & Baseline Vitals Polish
* **Improved**: PhilHealth Annex A1 (IHP) layout with sequential 1-to-9 card ordering across read-only (`#ihp-view-mode`) and edit (`#ihp-edit-mode`) views.
* **Added**: Baseline Vitals & Anthropometrics card (Card 6) in both read-only and edit modes of `#tab-ihp`, capturing Blood Pressure, Heart Rate, Respiratory Rate, Height, Weight, and Waist Circumference.
* **Added**: Dynamic Asian WHO BMI calculation badge with real-time classification (<18.5 Underweight, 18.5–22.9 Normal, 23.0–27.4 Overweight, $\ge$27.5 Obese) and zero-division mathematical guardrails.
* **Fixed**: Symmetrical 2-column desktop grid pairing (`col-12 col-md-6`) for Past Surgical History + Personal & Social History (Cards 3 & 4), and Lifetime Immunizations + Baseline Vitals & Anthropometrics (Cards 5 & 6), resolving unbalanced desktop whitespace gaps.
* **Security**: Added controller-level CSRF token validation (`hash_equals(csrf_token(), $token)`) and non-positive physiological measurement sanitization in `PatientMedicalHistoryController`. 95/95 automated assertions passed.

---

## [Beta 1.5] - 2026-09-12
### Authentication Portal Redesign & First-Time Password Security Enclave
* **Added**
  * **Dual-Identifier Authentication**: Health center personnel can sign in using either their standard system **Username** or their official **Employee ID** (`findByLoginIdentifier`).
  * **Redesigned Institutional Split-Screen Interface**: Upgraded `/login` and `/change-password` with split-column civic authority branding, Republic of the Philippines emblem, Barangay Sinalhan Health Services crest, and DOH / RA 10173 compliance status badges matching official design prototypes.
  * **Live Password Strength & Interactive Checklist**: Integrated real-time 4-segment entropy meter (*Weak*, *Fair*, *Good*, *Strong*) and dynamic criteria checklist (length $\ge 8$, mixed case, digit & special symbol, different from temp) with instant confirmation matching on first-time activation.
  * **Staff Identity Banner**: Prominently displays the authenticated staff member's avatar initials, full name, employee ID, and assigned role during initial account activation.
  * **Offline Statutory Compliance Modals**: Integrated self-contained offline modals for Privacy Policy (Republic Act 10173 / Data Privacy Act of 2012) and Terms of Use (Staff Acceptable Use Policy) accessible from all auth views.
* **Security**
  * **68/68 Automated QA Assertions Passed**: Comprehensive test suite covering PDO parameterized SQLi neutralization, sliding 15-minute brute-force lockout, enclave route isolation (`must_change_password`), strict password complexity enforcement, CSRF protection on all forms (including sign-out exit), and HTML entity XSS escaping.
  * **Zero Remote Asset Dependencies**: 100% offline LAN compliance with zero external CDNs, Google Fonts, or remote asset calls.

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

## [Beta 1.3] - 2026-09-09
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
