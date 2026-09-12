# System Features Guide - Barangay Sinalhan Health Center

This document outlines the clinical and administrative features built into the **Patient Management System** for the Barangay Sinalhan Health Center.

---

## 1. Patient Directory & Registration

The Patient module organizes demographics and profiles, reducing paper record dependencies.

### 1.1 Unique ID Generation
Upon successful registration, the system auto-generates a unique patient number formatted as:
`P-[CURRENT_YEAR]-[INCREMENTING_5_DIGIT_ID]` (e.g., `P-2026-00042`).
This value is computed atomically at the model level to prevent sequence gaps or collisions.

### 1.2 Duplicate Prevention
When registering a new patient, an AJAX listener monitors first name and last name input fields on loss of focus (`blur`). If an exact matching active patient is found, a warning banner appears at the top of the form with a direct link to the matching patient's profile folder to prevent duplicate registrations.

### 1.3 Dual-Layer Input Validation & Formatting
The Patient module enforces comprehensive client-side and server-side validation rules based on official **Annex A1: Individual Health Profile (IHP)** requirements:
* **Name Protection**: Rejects numeric digits and special symbols in name fields (`/^[a-zA-ZñÑ\s\-\'\.]{2,50}$/u`). Supports Name Suffixes (Jr., Sr., III).
* **Standardized Philippine Mobile Format**: Enforces 11-digit mobile numbers starting with `09` (`/^09\d{9}$/`). Non-numeric input is stripped automatically on keypress.
* **Date of Birth Boundaries**: Restricts DOB between `1900-01-01` and current date (`Today`) on both Flatpickr and PHP backend (`DateTime` validation).
* **Household Family Numbering**: Captures PhilHealth/CHO **Family Number** for family/household clustering.
* **PhilHealth Auto-Formatting Mask & Categorization**: Dynamically formats typed numbers into `XX-XXXXXXXXX-X` (12 digits with hyphens) and stores membership status (*Member, Dependent, Non-Member*) and program type (*Sponsored, Employed, IPP/OFW, Lifetime*).
* **Comprehensive Demographics**: Captures ABO **Blood Type**, **Religion**, **Educational Attainment**, **Occupation**, **Father/Mother/Spouse Identifiers**, and **Emergency Contact Relationship**.

### 1.4 Active Directory Filtering & Household Search
The main directory uses server-side search and filters:
* **Multi-Parameter Search**: Matches against patient number, first/last/middle names, Family Number, PhilHealth PIN, phone number, and barangay.
* **Demographics & Program Filters**: Narrow list by Sex (Male/Female), Barangay, Age Group (Well Baby `0-5`, Child `6-15`, Reproductive `15-49`, Adult `25-59`, Senior `60+`), or Program Category (*General OPD, Prenatal, Well Baby, Senior*).
* **Exporting**: Prints search results or exports records to a CSV spreadsheet.

---

## 2. Vital Signs Monitoring

Vitals can be saved standalone or linked to clinical checkups.

### 2.1 Metric Inputs & Validation
* Systolic & Diastolic BP (mmHg)
* Pulse Rate (bpm)
* Body Temperature (°C)
* Respiratory Rate (cpm)
* Oxygen Saturation ($SpO_2$ %)
* Weight (kg) & Height (cm)

### 2.2 Automated BMI Calculation
The Vital Signs Modal includes client-side JavaScript that listens to changes in the Weight and Height input boxes. It automatically calculates and updates the Body Mass Index (BMI) in real-time using:
$$\text{BMI} = \frac{\text{Weight (kg)}}{\left(\frac{\text{Height (cm)}}{100}\right)^2}$$
The computed classification (Underweight, Normal, Overweight, Obese) is formatted on screen.

### 2.3 Abnormal Clinical Alerts
Vitals are automatically scanned against ranges and highlighted in the profile history:
* **Fever**: Red text for temperatures $\ge 37.8^\circ\text{C}$; **Hypothermia**: Blue text for $< 35.0^\circ\text{C}$.
* **Hypertension**: Red text for Systolic $\ge 140$ or Diastolic $\ge 90$ mmHg; **Hypotension**: Blue text for Systolic $< 90$ or Diastolic $< 60$ mmHg.
* **Hypoxia**: Red text for oxygen levels ($SpO_2$) $< 95\%$.

---

## 3. Clinical Consultations (SOAP Notes)

Provides structured clinical documentation following the global medical **SOAP** framework:

* **Subjective (S)**: Patient's chief complaint, active symptoms, and history of present illness.
* **Objective (O)**: Clinical measurements. Users can link the patient's latest recorded vital signs directly to the checkup.
* **Assessment (A)**: Diagnosis, diagnostic impressions, and clinical findings.
* **Plan (P)**: Recommendations, prescriptions, lab requests, or follow-up instructions.

### 3.1 Asynchronous Detail Viewer
A consultation history log is displayed on the patient's profile. Clicking **View Details** triggers an AJAX load, rendering the SOAP notes and linked vital signs within a clean modal popup without reloading the main profile.

---

## 4. Appointment Scheduling

Manages patient scheduling, service programs, and clinic provider resources.

### 4.1 Program-Tagged Booking & Conflict Prevention
* **Clinical Program Categorization**: Bookings are organized by program type: **General OPD**, **Prenatal Care**, **Well Baby Immunization**, or **Senior Care**.
* **Overlap Prevention**: When selecting an appointment date and time, an AJAX conflict check queries the database for existing active bookings. If a clinician or time slot overlaps, a red alert box appears warning the staff to coordinate a reschedule.

### 4.2 Status Tracking & Quick Actions
Appointments can be filtered by date range, program type, or status:
* **Scheduled**: Initial booking state.
* **Completed**: Linked to a patient's consultation.
* **Cancelled**: Cancelled by staff or patient.
* **Missed**: Automatically flagged if the patient fails to show up on the date.

---

## 5. Daily Operations Queue

Optimizes daily patient flow inside the waiting area and routes patients across care tracks.

### 5.1 Service-Tagged Daily Queue Numbers
Enqueuing a patient generates an auto-incrementing queue number that resets daily (`001`, `002`, `003`...) tagged with the clinical service track:
* `General OPD`
* `Prenatal Care`
* `Well Baby Immunization`
* `Senior Care`

### 5.2 Waiting Monitor Board
Provides a dedicated, public-facing, full-screen monitor display at `/queue/display` designed to run on a TV or tablet in the lobby:
* **Privacy Controls**: Exposes only queue numbers (e.g., `005`), program tags, and active call statuses, preserving patient privacy.
* **Audio Chime System**: Generates a professional double-tone audio chime (using the browser's native `AudioContext` synth) when a new queue number is transitioned to "Called", alerting waiting patients without relying on external media assets or CDNs.
* **Real-Time Polling**: Feeds updates from the database every 5 seconds via JSON calls without page flickering.

---

## 6. Reports & Data Auditing

### 6.1 Administrative Reports
Extracts operational metrics by date range:
* **Daily Patient Visits**: Log of checkups and registrations.
* **Consultations Summary**: Volume of SOAP diagnoses.
* **Queue Operations**: Metrics on wait times, cancellations, and completed operations.
* **Vital Signs Records**: Log of triage records.

### 6.2 DOH-Compliant Clinical Registries
* **Maternal Health Registry**: Monitors active pregnancies, EDC due dates, dynamic AOG, GTPAL scores, and high-risk Pre-Eclampsia flags.
* **Childhood Immunization (EPI) Coverage Report**: Tracks administered vaccine dates across BCG, Hep B, Pentavalent, OPV, IPV, Rota, and Measles/MMR, monitoring Fully Immunized Child (FIC) milestones.
* **Chronic Morbidity & NCD Registry**: Surveillance report tracking registered citizens diagnosed with Hypertension, Diabetes, Asthma, Allergies, or Tuberculosis based on their PhilHealth Annex A1 IHP records.

### 6.3 Print Layouts & CSV Exports
* **Custom Print CSS**: Hides navigation sidebars, headers, and buttons during browser printing (Ctrl+P) or "Save as PDF" calls, formatting clean, printable grids.
* **Instant CSV Export**: Triggers a raw-comma stream download for use in spreadsheet software.

### 6.4 Audit Trails
An admin-only dashboard indexes chronological logs of important data modifications (logins, registrations, database backups, patient archiving, password resets) showing the timestamp, IP address, user, and detailed action summary.

---

## 7. User Accounts & Access Control

Admin-only management panel to handle health center staff access credentials:
* **Dedicated User Registration Page**: Dedicated, sectioned registration page at `/users/create` for creating new accounts with credentials, demographics, Employee ID / PRC License numbers, and Department unit assignments.
* **Role Hierarchy & Privilege Protection**:
  * **Main Administrator (User ID 1)** holds master permission over all administrator and staff accounts.
  * **Co-Administrators (User ID > 1 with role `admin`)** can manage `staff` accounts, but are strictly blocked from promoting staff to `admin`, creating admin accounts (overridden to `staff`), or editing peer administrators.
* **Comprehensive CSRF Defense-in-Depth**:
  * All 5 mutation endpoints (`store`, `update`, `resetPassword`, `toggleStatus`, and `resetLockout`) strictly enforce server-side CSRF validation via `hash_equals(csrf_token(), $token)`.
  * Forged or omitted tokens trigger a `SECURITY_VIOLATION` audit log with IP, actor, and targeted entity details, halting execution with user feedback.
* **15-Minute Session Inactivity Timeout Inheritance**:
  * `AdminMiddleware` delegates session validation to `AuthMiddleware`, inheriting automatic 15-minute (900 seconds) inactivity session invalidation, session destruction, and `SESSION_TIMEOUT` audit logging.
  * Handles both standard browser redirects (`/login?timeout=1`) and AJAX/JSON requests (HTTP 401 Unauthorized with descriptive JSON payloads).
* **Strict Backend Input Validation**:
  * **Username Validation**: Strict alphanumeric regex pattern with underscores (`/^[a-zA-Z0-9_]{3,20}$/`) alongside database uniqueness checks.
  * **Email Validation**: RFC-compliant format verification via `filter_var(..., FILTER_VALIDATE_EMAIL)` paired with duplicate detection excluding the current subject user.
  * **Philippine Mobile Format**: Enforces standard 11-digit mobile format (`/^09\d{9}$/`).
* **MVC Architectural Clean-Up**:
  * Pre-computes lockout state (`lockout_info`) inside `UserController::index` for each rendered user, eliminating direct `new \App\Models\User()` instantiations from the view template (`app/Views/users/index.php`).
* **Form Input Retention on Validation Errors**:
  * On update validation errors in `/users/{id}/edit`, submitted form data is preserved in `$_SESSION['old_input']` and flash errors in `$_SESSION['form_errors']`.
  * The edit view repopulates fields from `old_input` before falling back to database records, preventing data loss upon typo corrections.
* **Reset Password Modal "Generate Secure Password" Helper**:
  * Integrated one-click password generator creates randomized 14-character alphanumeric passwords containing uppercase, lowercase, numbers, and special symbols (`!@#$%^&*()-_=+`).
  * Automatically reveals the password, synchronizes the confirmation input, copies to system clipboard via the Clipboard API, and flashes a copy-confirmation banner.
* **Touch Target & Accessibility Expansions**:
  * Action buttons across the user accounts directory table (Edit, Clear Lockout, Reset Password, Activate/Deactivate) feature enlarged touch targets (minimum $34 \times 34\text{ px}$), border styling, and flex-centered icon placement for improved tablet and mobile usability.
* **Status Lifecycle & Deactivation (No Account Deletion)**: Account deletion is permanently disabled to preserve audit and medical logs. Account status is managed via dedicated table action buttons (**Activate** `bi-person-check-fill` and **Deactivate** `bi-person-x-fill`).
* **15-Minute Temporary Lockout Safeguard**: After 5 consecutive failed login attempts, an account is placed in a **15-minute temporary cooldown window**. Account status remains `active`, but authentication is blocked until the 15 minutes expire (displaying exact remaining time to the user).
* **Admin Clear Lockout Button**: Administrators can instantly override a staff member's 15-minute lockout timer by clicking the **Clear Lockout** button (`bi-unlock-fill`) on the User Accounts directory table, writing a `USER_LOCKOUT_RESET` audit log.
* **Security Password Reset**: Critical user resets require the logged-in administrator to enter their current password to authorize a custom temporary password. Validates that the new password is not identical to the user's existing password and enforces `must_change_password` flag on subsequent login.
* **CLI Account Unlocker**: Console utility (`php scripts/unlock_user.php [username]`) to clear lockouts and reset failed attempts directly from the server CLI.

---

## 8. Maternal & Prenatal Care Tracking

A specialized clinical module supporting midwives and nurses in managing pregnant patients through their gestational timeline:
* **Automated Gestational Calculations**: Health staff enters LMP (Last Menstrual Period); the system automatically calculates:
  * **EDC (Estimated Date of Confinement / Due Date)** via Naegele's Rule.
  * **Dynamic AOG (Age of Gestation)** in current weeks and days.
* **Obstetric GTPAL Index**: Computes and displays Gravida, Parity, Full-Term, Preterm, Abortions, and Living Children.
* **Past Pregnancies Matrix**: Historical log of prior deliveries (delivery type NSD/CS/Abortion, place of delivery, infant gender, year, attendant, survival status, and maternal Tetanus Toxoid history).
* **Serial Trimester Visit Logs**: Records serial clinical metrics per visit:
  * Maternal BP & Weight
  * **Fetal Heart Tone (FHT)** in bpm (with normal range alerts 120–160 bpm)
  * **Fundal Height (FH)** in cm
  * **Fetal Presentation** (*Cephalic, Breech, Transverse*)
  * **TCB** / Maternal Tetanus status
  * High-risk warnings (pre-eclampsia, severe hypertension).

---

## 9. Well Baby, Child Health & Routine Immunization

A dedicated pediatric care and growth tracking module for infants and young children (0–5 years):
* **Birth History & Newborn Screening**: Records birth weight, birth length, time of birth, delivery mode (NSD/CS), place of delivery, attendant, and **Newborn Screening** completion date and laboratory result.
* **Maternal CPAB Linkage**: Tracks whether the child was protected against tetanus at birth (CPAB) based on the mother's immunization record.
* **DOH EPI Routine Immunization Matrix**: Visual schedule tracker with date-administered timestamps for all mandatory childhood vaccines:
  * *At Birth*: BCG, Hepatitis B (within 24 hrs)
  * *6, 10, 14 Weeks*: Pentavalent 1–3, OPV 1–3, Rotavirus 1–2, IPV
  * *9 & 12 Months*: MCV1 (Measles), MCV2 (MMR)
* **National Supplementation Programs**: Tracks Vitamin A capsules and Deworming doses every 6 months.
* **Pediatric Anthropometrics & Growth Monitoring**: Serial checkup logs for exact age in months, weight, height, **Head Circumference**, **Chest Circumference**, body temperature, and infant feeding practices (*Exclusive Breastfeeding / LAM, Bottle Feeding, Mixed Feeding*).

---

## 10. Clinical Decision Support (CDS) & Patient Safety Alerts

An integrated safety engine that scans clinical histories and renders contextual warning banners across consultation forms, queues, and profiles:
* **Allergy Alert Banner**: Prominently warns clinicians if a patient has documented drug, food, or contact allergies (e.g., Penicillin, Seafood) to prevent adverse medication events during consultation.
* **Maternal Pre-Eclampsia High-Risk Banner**: Warns healthcare workers if an expectant mother has diagnosed hypertension, elevated BP, or pre-eclampsia history, advising immediate blood pressure checks and urine protein monitoring.
* **Chronic NCD Warning Banners**: Contextual badges indicating active chronic conditions (Hypertension, Diabetes Mellitus, Bronchial Asthma) to encourage comprehensive chronic care management during routine visits.
* **Vital Signs Quick Strip**: Displays latest recorded vitals directly within the SOAP consultation editor so clinicians don't need to switch between screens to review blood pressure, heart rate, or temperature.

---

## 11. User Profile & Account Self-Service

Provides self-service account management and personalized profile controls for authenticated clinic staff and administrators, accessible from any screen via the topbar avatar dropdown:

### 11.1 Topbar User Avatar Dropdown
* **Persistent Access & Identity Display**: Embedded across all system views within the top navigation bar.
* **Dynamic Circular Avatar**: Renders a 36px circular avatar featuring the user's capitalized first initial against a teal badge (`#0d9488`).
* **Name & Role Subtitle**: Displays the authenticated user's full name alongside an accurate role subtitle (*Admin*, *Co-Admin*, or *Staff*).
* **Self-Service Navigation**:
  * **My Profile**: Direct link to `/profile` overview and personal contact details editor.
  * **Password Settings**: Anchored shortcut to `/profile#password-settings`.
  * **Secure Logout**: Form-driven `POST /logout` action with CSRF validation and SweetAlert2 confirmation dialogue to prevent accidental sign-outs.

### 11.2 Self-Service Profile Overview (`GET /profile`)
* **Role & Credential Verification**: Read-only display of administrative and organizational metadata, including Username, Assigned Role (System Administrator, Co-Administrator, Staff Member), Employee ID, Department, Job Title, Account Status, Date Joined, and Last Login Timestamp.
* **Strict Privilege Separation**: Users cannot modify their assigned administrative role, account active/inactive status, or departmental assignment via self-service.

### 11.3 Personal Contact Details Management (`POST /profile/update`)
* **Editable Contact Fields**: Allows staff and admins to self-update First Name, Middle Name, Last Name, Work/Personal Email, and Mobile Contact Number.
* **Integrity Validation**: Enforces non-empty First and Last Names, valid email format (`filter_var`), and database-wide email uniqueness checking (`isEmailUnique`) excluding the user's own record.
* **Real-Time Session Synchronization**: Upon saving, updates `$_SESSION['user_fullname']` immediately so topbar navigation reflects name changes without requiring a re-login.
* **Audit Trail**: Generates an immutable `PROFILE_UPDATED` record in `audit_logs` logging the timestamp, client IP, user ID, and update summary.

### 11.4 Voluntary Password Modification (`POST /profile/password`)
* **Current Password Confirmation**: Requires verification of the user's current password against the stored bcrypt hash via `password_verify()`.
* **Complexity & Confirmation Enforcement**: Requires a new password of at least 8 characters and strict matching confirmation (`confirm_password`).
* **Bcrypt Hashing & Cooldown Reset**: Hashes new passwords with standard PHP `PASSWORD_BCRYPT` cost factor, clears any failed login counter to 0, and resets the `must_change_password` flag.
* **Session Fixation Defense**: Automatically regenerates the PHP session identifier (`session_regenerate_id(true)`) upon successful credential change to prevent session hijacking.
* **Security Audit Logging**: Creates a `USER_PASSWORD_CHANGED` audit record tracking the self-service credential update.

---

## 12. Authentication Portal & First-Time Password Security Enclave

The Authentication Portal provides a secure, audited gateway for health center staff and administrators accessing the system across the local clinic network.

### 12.1 Split-Screen Institutional Design & Civic Branding
* **Dual-Column Layout**: High-impact split-screen responsive layout separating civic authority branding (left) from credential authentication forms (right).
* **Official Seals & Civic Emblems**: Features the Republic of the Philippines emblem and Barangay Sinalhan Health Services crest against an emerald/teal brand gradient with subtle ambient radial glow.
* **Compliance & Governance Badges**: Prominently displays civic compliance status:
  * **Republic Act 10173 (Data Privacy Act of 2012)**: Reaffirms strict patient confidentiality, data subject rights, and statutory health data protection.
  * **Department of Health (DOH) Aligned**: Highlights compliance with standardized clinical charting, routine immunization registries, and Field Health Services Information System (FHSIS) reporting.
  * **100% Offline LAN Operation**: Reinforces that the system operates strictly within the local clinic network without remote external dependencies.

### 12.2 Dual-Identifier Authentication
* **Flexible Identification**: Health center workers may sign in using either their standard system **Username** or their official **Employee ID** (e.g., `EMP-2026-001`, `MIDWIFE-01`).
* **Optimized Database Lookup**: Handled securely in [`User.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/User.php) via `findByLoginIdentifier($identifier)` using PDO parameterized queries (`WHERE (username = :u_identifier OR employee_id = :e_identifier) AND deleted_at IS NULL LIMIT 1`).
* **Brute-Force Lockout Defense**: Failed login attempts tracked seamlessly regardless of whether the user entered a username or Employee ID, incrementing the counter toward the 5-attempt threshold and triggering the 15-minute sliding lockout window.

### 12.3 Architectural & Security Design Decisions
* **Intentional Omission of "Forgot Password"**: In a strictly offline local area network (LAN) without outbound SMTP mail relay or SMS gateway infrastructure, self-service password reset links are impossible and present unnecessary attack surfaces. Password resets are intentionally centralized through authorized administrators via the User Accounts module (`/users`).
* **Intentional Omission of "Remember Credentials"**: Health center workstations are shared among rotating doctors, nurses, midwives, and BHW shifts. Persistent client-side credential storage (cookies or browser autofill) creates severe cross-shift data leakage risks, violating Section 20 of RA 10173 (Security of Personal Information). Staff must explicitly authenticate for each clinic session.

### 12.4 First-Time Password Update Enclave (`/change-password`)
When an account is newly provisioned or reset by an administrator (`must_change_password = 1`), the user is immediately sequestered in an activation enclave:
* **Enclave Gatekeeper**: `AuthMiddleware` and `AuthController` intercept requests to any other system route, redirecting the user back to `/change-password` until new credentials are established. Activated users (`must_change_password = 0`) attempting to access this view are redirected to `/dashboard`.
* **Staff Identity Verification Card**: Displays the authenticated staff member's two-letter capitalized avatar initials, full name, employee ID, and assigned clinical role to confirm user identity prior to credential activation.
* **4-Segment Dynamic Password Strength Meter**: Real-time visual progress bar evaluating password entropy across 4 discrete stages (*Weak*, *Fair*, *Good*, *Strong*) with dynamic color shifting (crimson $\to$ amber $\to$ sky $\to$ emerald).
* **Live Interactive Criteria Checklist**: Real-time evaluation checklist with dynamic icon feedback (`bi-x-circle` / `bi-check-circle-fill`) for:
  1. Minimum 8 characters in length
  2. Uppercase and lowercase alphabetic characters (`[A-Z]` and `[a-z]`)
  3. At least one numeric digit (`0-9`) and special symbol (`@$!%*?&`)
  4. Distinct from the current temporary password
* **Instant Confirmation Matcher**: Real-time matching indicator verifying that the confirmation password exactly mirrors the new password before submission.
* **Safe Session Exit**: Includes a CSRF-protected "Cancel & Sign Out" button wrapped in a POST form that terminates the session and safely returns the workstation to the login portal.

### 12.5 Offline Statutory Compliance Modals
* **Privacy Policy Modal (Data Privacy Act of 2012 / RA 10173)**: Self-contained offline modal accessible from all authentication views detailing health data collection purposes, patient record retention, lawful processing, and health worker confidentiality duties.
* **Terms of Use Modal (Staff Acceptable Use Policy)**: Offline modal detailing health center computer usage guidelines, audit logging disclosure, session lockout rules, and non-sharing of user credentials.
* **Zero External Dependencies**: All modals, styles, SVGs, and scripts are served 100% locally with zero external CDNs, Google Fonts, or remote asset requests.


