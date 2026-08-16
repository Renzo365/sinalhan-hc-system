# Patient Management System - Barangay Sinalhan Health Center

> A capstone-oriented, LAN-based healthcare information system built with PHP 8+, MySQL 8, and XAMPP for Barangay Sinalhan Health Center.

---

## 1. Project Purpose

This project aims to improve patient record management, consultation tracking, queue handling, and basic reporting for Barangay Sinalhan Health Center. The system is designed to run on a local area network (LAN), allowing multiple health center staff to access it from different workstations without depending on an internet connection.

The project is intentionally scoped as a practical capstone system: it focuses first on the most important health center workflows, then treats more specialized modules as enhancements.

### Primary Goals

- Reduce manual paper-based patient record handling.
- Improve speed and accuracy of patient search and registration.
- Support basic clinical documentation through vital signs and consultation records.
- Provide a queue management workflow for daily health center operations.
- Generate useful reports for monitoring patient activity.
- Protect sensitive health information through authentication, authorization, audit logs, and controlled access.

### Key Constraints

| Constraint | Decision |
|---|---|
| Deployment | XAMPP on a local Windows server, accessed through LAN IP |
| Runtime | PHP 8.2+ |
| Database | MySQL 8.0 using InnoDB |
| Internet | No internet dependency during operation |
| Users | Admin users and staff users, where staff may include BHWs, midwives, nurses, and records personnel |
| Scope | Prioritize a complete working core system before advanced modules |

---

## 2. Capstone Scope Strategy

The original system vision is broad, but the capstone delivery should be focused. The project will follow a priority-based scope so the final output is complete, demonstrable, and defensible.

### 2.1 Must Have - Core Capstone System

These features are required for the system to be considered complete for capstone demonstration:

1. Authentication and session management
2. Role-based access control
3. Patient registration, search, update, and profile view
4. Vital signs recording
5. Consultation records using SOAP-style notes
6. Appointment scheduling
7. Queue management with daily queue numbers
8. Basic dashboard statistics
9. Basic reports with printable or exportable output
10. Audit logging for important data changes
11. Database backup

### 2.2 Should Have - Strong Supporting Features

These features add value and should be implemented after the core system is stable:

1. PDF report generation
2. CSV export for reports
3. Advanced patient filters
4. Archived records page with restore support
5. User management for admin users

### 2.3 Could Have - Polish and Enhancements

These features are useful but should not delay the core system:

1. Prescription records
2. Immunization records
3. Laboratory request and result tracking
4. Maternal and child health records
5. Senior citizen records
6. Patient photo capture
7. Real-time-looking queue display using polling
8. Dark mode
9. Advanced analytics charts

### 2.4 Future Enhancements

These features can be documented as future improvements:

1. Data migration from an existing system
2. Full database restore workflow through the web interface
3. SMS reminders
4. Online cloud backup
5. PhilHealth or DOH system integration
6. Mobile application companion
7. Offline-first synchronization across multiple sites

---

## 3. Technology Stack

| Layer | Technology | Rationale |
|---|---|---|
| Backend | PHP 8.2+ using custom MVC | Works well with XAMPP and avoids deployment complexity |
| Database | MySQL 8.0 with InnoDB | Supports transactions, foreign keys, and reliable local storage |
| Frontend UI | Bootstrap 5, HTML5, CSS3 | Speeds up responsive layouts, forms, modals, alerts, and tables |
| Frontend Scripts | Vanilla JavaScript, with jQuery only where plugin support is needed | Keeps custom code lightweight while supporting mature table plugins |
| Icons | Bootstrap Icons bundled locally | Matches Bootstrap 5 and works offline |
| Tables | DataTables bundled locally | Useful for searchable, sortable, paginated patient lists, reports, and audit logs |
| Alerts and Confirmations | SweetAlert2 bundled locally | Provides clearer confirmation dialogs for delete, logout, backup, and other important actions |
| Date and Time Inputs | Flatpickr bundled locally | Improves date range filters, appointment dates, and report inputs |
| Charts | Chart.js bundled locally | Useful for dashboard and reports without CDN dependency |
| PDF Reports | TCPDF bundled locally | PHP-native PDF generation |
| Authentication | PHP sessions, bcrypt, CSRF tokens | Practical and secure for a LAN-based system |
| Assets | Self-hosted fonts, icons, CSS, and JS | Ensures the system works without internet |

### Technology Decision

The project will use vanilla PHP with a simple MVC structure instead of a full framework such as Laravel. This is appropriate for the capstone because it keeps installation simple on XAMPP, avoids Composer dependency issues, and makes the code easier to explain during defense.

Bootstrap 5 is approved for the frontend because it improves development speed and visual consistency. Bootstrap 5 does not require jQuery, so custom application scripts should use vanilla JavaScript by default. jQuery should only be included when a selected plugin requires it, such as the traditional DataTables setup.

All frontend libraries must be downloaded and served locally from the project. CDN links should not be used because the system must work offline on a LAN.

---

## 4. System Architecture

### 4.1 Architecture Pattern

The system follows a Model-View-Controller (MVC) pattern.

```text
Browser / LAN Clients
        |
        v
Apache / XAMPP
        |
        v
public/index.php
        |
        v
Router
        |
        v
Controllers
        |
        +--> Views
        |
        +--> Models
                  |
                  v
              MySQL Database
```

### 4.2 Core Responsibilities

| Layer | Responsibility |
|---|---|
| Router | Maps URLs to controller actions |
| Controllers | Handles user requests and business workflow |
| Models | Handles database operations |
| Views | Displays pages, forms, tables, and reports |
| Middleware | Handles authentication, authorization, CSRF, and audit checks |
| Database | Stores patient, clinical, user, queue, and report data |

---

## 5. Proposed Folder Structure

```text
patient-management-system/
|-- public/
|   |-- index.php
|   |-- .htaccess
|   |-- assets/
|       |-- css/
|       |-- js/
|       |-- fonts/
|       |-- icons/
|       |-- img/
|       |-- vendor/
|           |-- bootstrap/
|           |-- bootstrap-icons/
|           |-- chartjs/
|           |-- datatables/
|           |-- flatpickr/
|           |-- jquery/
|           |-- sweetalert2/
|
|-- app/
|   |-- Core/
|   |-- Middleware/
|   |-- Controllers/
|   |-- Models/
|   |-- Views/
|
|-- config/
|   |-- app.php
|   |-- database.php
|   |-- routes.php
|
|-- database/
|   |-- schema.sql
|   |-- seed.sql
|   |-- migrations/
|
|-- storage/
|   |-- backups/
|   |-- logs/
|   |-- uploads/
|
|-- docs/
|   |-- architecture.md
|   |-- database.md
|   |-- features.md
|   |-- security.md
|
|-- tests/
```

---

## 6. Core Modules

### 6.1 Authentication and User Roles

The system requires users to log in before accessing protected modules. Each user has a role (`admin` or `staff`) that determines what actions they can perform.

#### Role Hierarchy & Privilege Protection
* **Main Administrator (User ID 1)**: Primary System Administrator with master authority over all administrator and staff accounts.
* **Co-Administrators (User ID > 1 with role `admin`)**: Can manage standard `staff` accounts, but are strictly blocked from promoting staff to `admin`, creating admin accounts (overridden to `staff`), or modifying peer administrators.
* **Staff**: Healthcare workers (BHWs, midwives, nurses, records personnel) handling daily clinical operations.

#### Account Lifecycle & Safeguards
* **No Account Deletion**: Account deletion is permanently disabled across the system to preserve audit logs and clinical integrity. Accounts are activated or deactivated via table toggles (**Activate** `bi-person-check-fill` and **Deactivate** `bi-person-x-fill`).
* **15-Minute Temporary Lockout**: Entering 5 consecutive wrong passwords triggers a **15-minute temporary cooldown**. Account status remains `active`, but login is blocked until the 15 minutes expire (displaying exact remaining minutes and seconds).
* **Admin Lockout Override**: Administrators can clear a user's 15-minute lockout timer immediately by clicking the **Clear Lockout** button (`bi-unlock-fill`) on the User Accounts directory page.
* **Password Resets**: Password resets require the logged-in administrator to enter their current password, and the system prevents setting temporary passwords to the user's current password. Forced password resets on first login (`must_change_password`) have been removed in favor of direct admin password resets.

### 6.2 Patient Management

Patient management is the main module of the system.

Core features:

- Register new patients via `/patients/create` with dual-layer validation.
- Enforce strict validation: letters-only in names (`/^[a-zA-ZñÑ\s\-\'\.]{2,50}$/u`), 11-digit `09XXXXXXXXX` Philippine mobile numbers, DOB bounds (`1900-01-01 <= dob <= Today`), and PhilHealth mask (`XX-XXXXXXXXX-X`).
- Record extended demographics: ABO **Blood Type**, **Occupation**, and **Emergency Contact Relationship**.
- Search patients by name, patient number, age, sex, or barangay.
- View patient profile and full clinical history (Vitals, Consultations, Appointments, Queue).
- Update patient demographic information.
- Soft-delete / Archive patient records when allowed.
- View patient-related history such as vitals and consultations.

### 6.3 Vital Signs

Health staff can record basic patient vital signs.

Fields may include:

- Blood pressure
- Heart rate
- Respiratory rate
- Temperature
- Weight
- Height
- BMI
- Oxygen saturation
- Notes
- Recorded by
- Date and time recorded

### 6.4 Consultation Records

Consultation records will use a SOAP-style format:

| Section | Meaning |
|---|---|
| Subjective | Chief complaint and patient-reported symptoms |
| Objective | Observations, vital signs, and physical findings |
| Assessment | Diagnosis or clinical impression |
| Plan | Treatment, advice, prescription, or follow-up |

This makes the system easier to explain because SOAP notes are a recognizable clinical documentation format.

### 6.5 Appointment Scheduling

The appointment scheduling module helps staff organize upcoming patient visits and follow-ups.

Core features:

- Create patient appointments.
- Link each appointment to an existing patient.
- Record appointment date, time, purpose, status, and notes.
- View appointments by date or date range.
- Search or filter appointments by patient, status, or schedule date.
- Update appointment status such as scheduled, completed, cancelled, or missed.
- Show upcoming appointments on the dashboard.

### 6.6 Queue Management

The queue module supports daily patient flow.

Core features:

- Add patient to queue.
- Generate queue number per day.
- Mark patient as waiting, called, serving, completed, or cancelled.
- Show current queue list.
- Optional public queue display for waiting area monitor.

The public queue display should avoid exposing sensitive patient information. It should show queue numbers and service status only, unless the health center approves displaying names.

### 6.7 Dashboard

The dashboard should provide quick operational summaries.

Recommended dashboard cards:

- Total patients
- Patients registered today
- Consultations today
- Current queue count
- Completed queue entries today
- Upcoming appointments

### 6.8 Reports

The reporting module should focus on useful and achievable reports.

Recommended initial reports:

- Daily patient visits
- Consultation summary by date range
- Patient registration summary
- Queue summary
- Vital signs record report

Recommended export formats:

- Printable HTML
- PDF
- CSV

Excel export can be listed as a future enhancement if time is limited.

### 6.9 Audit Logs

Audit logs should track important system actions such as:

- Login and logout
- Patient creation
- Patient update
- Consultation creation
- Vital signs creation
- Queue status changes
- Report generation
- Backup creation

Audit logs help demonstrate accountability and data protection during capstone defense.

### 6.10 Archived Records

Archiving is the user-facing workflow for soft-deleted records. Instead of permanently deleting patient records, the system marks them as archived and hides them from normal active lists.

Recommended behavior:

- Normal patient lists should show active records only.
- Archived records should be visible from a separate admin-focused page.
- Archiving should store `deleted_at`, `deleted_by`, and an optional `archive_reason`.
- Admin users should be able to view and restore archived records when appropriate.
- Staff users should not restore archived records unless explicitly allowed.
- Permanent deletion should not be included in the initial capstone scope.

### 6.11 System Reliability & Global Error Handling

To handle unexpected system crashes, database outages, or unhandled PHP exceptions:

- **Centralized Error Engine (`App\Core\ErrorHandler`)**: Captures uncaught exceptions, runtime errors, and fatal shutdown events globally.
- **Structured Error Logging**: Writes complete crash traces (timestamp, user, client IP, requested URI, error message, file line, stack trace) to `storage/logs/error.log`.
- **Audit Log Trail**: Automatically logs `SYSTEM_ERROR` events to the audit trail if database connection is accessible.
- **500 Error Page (`app/Views/errors/500.php`)**: Replaces raw stack traces or blank screens with a clean healthcare-themed 500 error page featuring *"Try Again"* and *"Back to Dashboard"* buttons.
- **Debug Mode Toggle**: Controlled via `'debug'` in `config/app.php` (enables detailed technical trace views in development, hides traces in production).

This approach is safer for a health record system because old patient records may still be needed for accountability, history, or reporting.

### 6.11 Backup

The system should allow an admin to create a database backup. For capstone scope, backup creation is more important than a full web-based restore workflow.

Recommended approach:

- Admin-only backup page
- Generate SQL backup file
- Store backup under `storage/backups`
- Include date and time in backup filename

Full restore can be documented as a future enhancement because it is riskier and requires stronger safeguards.

---

## 7. Database Design

### 7.1 Database Principles

| Principle | Implementation |
|---|---|
| Referential integrity | Use foreign keys for related records |
| Transaction safety | Use InnoDB tables |
| Soft deletes / archiving | Use `deleted_at`, `deleted_by`, and optional `archive_reason` for important records hidden from active lists |
| Audit trail | Track `created_at`, `updated_at`, `created_by`, and `updated_by` where appropriate |
| Search performance | Add indexes on patient number, names, dates, and status fields |
| Security | Store password hashes, never plain-text passwords |
| Local reliability | Keep schema simple enough to maintain on XAMPP |

### 7.2 Recommended Core Tables

Must-have tables:

- `users`
- `roles` or role field in `users`
- `patients`
- `appointments`
- `vital_signs`
- `consultations`
- `queue_entries`
- `audit_logs`
- `settings`

Should-have tables:

- `prescriptions`
- `immunizations`
- `reports` or report metadata table, if needed

Could-have tables:

- `lab_requests`
- `lab_results`
- `maternal_records`
- `child_health_records`
- `senior_citizen_records`

### 7.3 Data Modeling Note

JSON columns may be useful for flexible fields, but they can make reports harder. If a record needs to be searched, filtered, or reported often, it should usually be stored in a normal relational table instead of JSON.

---

## 8. Security Design

Security is important because the system handles sensitive health information.

### 8.1 Authentication

- Use `password_hash()` for passwords.
- Use `password_verify()` during login.
- Regenerate session ID after login.
- Apply session timeout.
- Lock or delay login attempts after repeated failed attempts.

### 8.2 Authorization

- Use role-based access checks.
- Protect every sensitive route.
- Hide unauthorized actions from the interface.
- Verify permissions in controllers, not only in views.

### 8.3 Input and Output Safety

- Use PDO prepared statements for database queries.
- Escape output using `htmlspecialchars()`.
- Validate required fields, date formats, numeric values, and allowed options.
- Use CSRF tokens on all forms that modify data.

### 8.4 File and Backup Safety

- Store uploaded files outside the public web root when possible.
- Validate file type and size.
- Restrict backup creation to admin users.
- Avoid exposing backup files directly through public URLs.

### 8.5 Privacy Reminder

The queue display and reports should avoid unnecessary exposure of sensitive patient information. For public screens, queue numbers are safer than full patient names.

---

## 9. Route Design

Routes will go through a front controller at `public/index.php`.

### 9.1 Route Pattern

```text
GET    /module                 Controller@index
GET    /module/create          Controller@create
POST   /module                 Controller@store
GET    /module/{id}            Controller@show
GET    /module/{id}/edit       Controller@edit
POST   /module/{id}            Controller@update
POST   /module/{id}/delete     Controller@destroy
```

### 9.2 Core Routes

```text
GET/POST  /login
POST      /logout

GET       /dashboard

GET       /patients
GET       /patients/create
POST      /patients
GET       /patients/{id}
GET       /patients/{id}/edit
POST      /patients/{id}
POST      /patients/{id}/delete
GET       /patients/search
GET       /archive/patients
GET       /archive/patients/{id}
POST      /archive/patients/{id}/restore

POST      /vital-signs
GET       /patients/{id}/vital-signs

GET       /consultations
POST      /consultations
GET       /patients/{id}/consultations

GET       /queue
POST      /queue
POST      /queue/{id}/call
POST      /queue/{id}/complete
POST      /queue/{id}/cancel
GET       /queue/display

GET       /reports
GET       /reports/generate
GET       /reports/export/{format}

GET       /audit-logs

GET       /backup
POST      /backup/create

GET       /settings/users
POST      /settings/users
```

---

## 10. UI/UX Direction

The interface should be simple, readable, and efficient for repeated daily use by health center staff.

### 10.1 Design Principles

- Prioritize readability over decoration.
- Use clear tables, forms, filters, and status badges.
- Keep navigation predictable.
- Use large enough text for medical data.
- Make common actions easy to find.
- Avoid clutter on clinical screens.

### 10.2 Suggested Layout

- Sidebar navigation for main modules
- Topbar with logged-in user and logout action
- Dashboard as the first screen after login
- Breadcrumbs or clear page titles
- Tables with search, filters, and pagination
- Print-friendly report pages

Detailed low-fidelity screen wireframes are documented in `docs/wireframes.md`.

### 10.3 Recommended Frontend Libraries

| Library | Use In This System |
|---|---|
| Bootstrap 5 | Main layout, grid, forms, buttons, modals, cards, badges, alerts, and responsive behavior |
| Bootstrap Icons | Sidebar icons, action buttons, dashboard cards, and status indicators |
| SweetAlert2 | Confirmation dialogs for delete, logout, queue status changes, backup creation, and other important actions |
| DataTables | Patient list, consultation list, audit logs, reports, and other data-heavy tables |
| Chart.js | Dashboard statistics, report charts, queue trends, and consultation summaries |
| Flatpickr | Date of birth, appointment dates, consultation dates, and report date ranges |
| jQuery | Only for plugins that require it, especially DataTables if using the jQuery version |

### 10.4 Frontend Usage Rules

- Do not use CDN links.
- Store all third-party assets under `public/assets/vendor`.
- Use Bootstrap 5 components before creating custom UI components.
- Use vanilla JavaScript for custom application behavior where practical.
- Use jQuery only when required by a plugin.
- Keep plugin usage consistent across modules.
- Avoid adding a new library if Bootstrap 5 or existing JavaScript can already solve the problem cleanly.

### 10.5 Project Color Palette

The system will use a teal and cyan-based healthcare palette. This gives the interface a calm, professional look while still feeling distinct from a generic Bootstrap theme.

#### Default Palette

| Token | Color | Usage |
|---|---|---|
| Primary | `#0D7377` | Main buttons, active states, links, highlights |
| Primary Light | `#14A3A8` | Hover states, focus states, secondary highlights |
| Primary Dark | `#095B5E` | Active sidebar items, strong headers, important emphasis |
| Primary Transparent | `rgba(13, 115, 119, 0.1)` | Soft backgrounds, selected rows, subtle highlights |
| Secondary / Accent | `#4A90D9` | Secondary actions, charts, supporting visual accents |
| Sidebar Background | `#0A3D40` | Main sidebar navigation background |
| Sidebar Hover | `#0D7377` | Sidebar hover and active navigation state |
| Background Main | `#F4F6F9` | Main application background |
| Surface White | `#FFFFFF` | Cards, tables, forms, modals |
| Text Primary | `#2D3436` | Main text |
| Text Secondary | `#636E72` | Supporting text |
| Text Muted | `#707C80` | Help text, placeholders, less important labels |
| Border Color | `#DFE6E9` | Form borders, table borders, dividers |

#### Status and Alert Palette

| Status | Text / Icon Color | Background |
|---|---|---|
| Success | `#28A745` | `rgba(40, 167, 69, 0.1)` |
| Warning | `#FFC107` | `rgba(255, 193, 7, 0.15)` |
| Danger | `#DC3545` | `rgba(220, 53, 69, 0.1)` |
| Info | `#17A2B8` | `rgba(23, 162, 184, 0.1)` |

#### Login Page Gradient

| Token | Color | Usage |
|---|---|---|
| Gradient Start | `#0A3D40` | Login page background start |
| Gradient End | `#0D7377` | Login page background end |
| Floating Abstract Background Elements | `rgba(20, 163, 168, 0.15)` | Subtle teal glow elements on login page |

#### Suggested CSS Variables

```css
:root {
  --color-primary: #0D7377;
  --color-primary-light: #14A3A8;
  --color-primary-dark: #095B5E;
  --color-primary-soft: rgba(13, 115, 119, 0.1);

  --color-accent: #4A90D9;

  --color-sidebar: #0A3D40;
  --color-sidebar-hover: #0D7377;

  --color-bg: #F4F6F9;
  --color-surface: #FFFFFF;
  --color-text: #2D3436;
  --color-text-secondary: #636E72;
  --color-text-muted: #707C80;
  --color-border: #DFE6E9;

  --color-success: #28A745;
  --color-success-bg: rgba(40, 167, 69, 0.1);
  --color-warning: #FFC107;
  --color-warning-bg: rgba(255, 193, 7, 0.15);
  --color-danger: #DC3545;
  --color-danger-bg: rgba(220, 53, 69, 0.1);
  --color-info: #17A2B8;
  --color-info-bg: rgba(23, 162, 184, 0.1);

  --login-gradient-start: #0A3D40;
  --login-gradient-end: #0D7377;
  --login-bg-glow: rgba(20, 163, 168, 0.15);
}
```

### 10.6 Additional UI/UX Principles

#### Workflow-First Design

The interface should follow the real daily workflow of the health center:

1. Patient arrives.
2. Staff searches for an existing patient record or registers a new patient.
3. Staff creates an appointment or queue entry.
4. Staff records vital signs.
5. Staff records consultation details.
6. Staff prints or exports reports when needed.

Screens should be arranged around this workflow instead of only around database tables.

#### Fast Patient Lookup

Patient search should be available from major screens because staff will frequently need to find existing records.

Recommended search behavior:

- Search by patient number, first name, last name, contact number, or barangay.
- Show clear empty states when no patient is found.
- Show possible matches before registration to help avoid duplicate patient records.

#### Form Usability

Forms should be easy to complete during busy clinic hours.

Guidelines:

- Group related fields into sections.
- Mark required fields clearly.
- Use helpful placeholders and short helper text only when needed.
- Keep validation messages close to the field.
- Preserve entered values after validation errors.
- Use dropdowns, date pickers, and radio buttons where they reduce typing errors.
- Avoid long forms without section breaks.

#### Clinical Data Readability

Medical information should be easy to scan.

Guidelines:

- Use tables for histories such as consultations, vital signs, and appointments.
- Highlight abnormal or important values carefully without overusing red.
- Use badges for statuses such as scheduled, waiting, called, completed, cancelled, and missed.
- Keep patient identity visible on clinical screens, especially patient name, age, sex, and patient number.

#### Error Prevention

The system should prevent common mistakes before they happen.

Examples:

- Confirm destructive actions with SweetAlert2.
- Warn before leaving a form with unsaved changes.
- Prevent duplicate appointment times when possible.
- Validate dates so future birth dates and invalid consultation dates cannot be saved.
- Use soft delete instead of permanent delete for patient records.

#### Privacy-Aware Interface

The UI should avoid exposing sensitive patient information unnecessarily.

Guidelines:

- Public queue display should show queue numbers only unless approved otherwise.
- Hide sensitive fields from users who do not need them.
- Avoid showing too much patient information on dashboard cards.
- Provide clear logout access, especially on shared clinic computers.

#### Responsive LAN Workstation Design

The system should work well on desktop computers and tablets used inside the health center.

Guidelines:

- Prioritize desktop and tablet layouts over phone-first design.
- Keep tables usable on smaller screens through horizontal scrolling or responsive table layouts.
- Make buttons and form controls large enough for quick use.
- Ensure modals and date pickers work on tablet screens.

#### Consistent Status Language

Use consistent status names across the system.

Recommended status values:

- Appointment: `Scheduled`, `Completed`, `Cancelled`, `Missed`
- Queue: `Waiting`, `Called`, `Serving`, `Completed`, `Cancelled`
- Consultation: `Open`, `Completed`, `Cancelled`

#### Empty, Loading, and Error States

Every major table or dashboard widget should have clear states.

Examples:

- Empty: `No appointments scheduled for today.`
- Loading: show a spinner, skeleton state, or simple loading message.
- Error: `Unable to load records. Please try again.`

#### Print-Friendly Design

Printed documents should look clean and official.

Guidelines:

- Use the health center name and document title.
- Include patient name, patient number, date, and prepared by.
- Hide navigation, buttons, filters, and screen-only controls when printing.
- Use black text on a white background for print views.

#### Accessibility Basics

The system should be readable and usable for different staff members.

Guidelines:

- Maintain strong color contrast.
- Do not rely on color alone to communicate status.
- Use labels for all form inputs.
- Make focus states visible.
- Use readable font sizes, especially in tables and forms.

---

## 11. Implementation Roadmap

### Phase 1 - Foundation

1. Create project folder structure.
2. Configure database connection.
3. Build router, controller, model, and view foundation.
4. Create initial database schema.
5. Add users and authentication.
6. Add role-based access control.
7. Build main layout with sidebar and topbar.

### Phase 2 - Core Patient Workflow

1. Build patient registration.
2. Build patient list and search.
3. Build patient profile page.
4. Build patient update flow.
5. Add vital signs recording.
6. Show vital signs history on patient profile.

### Phase 3 - Clinical Workflow

1. Build consultation form.
2. Link consultations to patients and providers.
3. Display consultation history.
4. Add SOAP note fields.
5. Add validation for clinical forms.

### Phase 4 - Appointment Scheduling

1. Build appointment creation.
2. Link appointments to patients.
3. Add appointment date, time, purpose, status, and notes.
4. Build appointment list with date filters.
5. Add appointment status updates such as scheduled, completed, cancelled, and missed.

### Phase 5 - Queue and Dashboard

1. Build queue entry creation.
2. Generate daily queue numbers.
3. Add queue status updates.
4. Build queue display page.
5. Build dashboard summary cards.

### Phase 6 - Reports, Audit, and Backup

1. Build basic reports.
2. Add printable report pages.
3. Add PDF or CSV export.
4. Add audit logging.
5. Add archived records page.
6. Add restore support for archived patient records.
7. Add admin backup function.

### Phase 7 - Enhancements

1. Add the User Management Module.
2. Improve the UI and responsive behavior.
3. Perform a comprehensive security and usability review.

---

## 12. Verification Plan

### 12.1 Functional Testing

- Verify login and logout.
- Verify role-based access restrictions.
- Register, search, update, and view patients.
- Create and update appointments.
- Record vital signs.
- Create consultation records.
- Add and update queue entries.
- Generate reports.
- Archive and restore patient records as admin.
- Create database backup.

### 12.2 Security Testing

- Test invalid login attempts.
- Test access to restricted pages.
- Test CSRF protection on forms.
- Test SQL injection attempts on search and login forms.
- Verify passwords are hashed.
- Verify users cannot access unauthorized actions by directly entering URLs.

### 12.3 Data Testing

- Verify required fields.
- Verify date and number validation.
- Verify foreign key relationships.
- Verify archive and restore behavior.
- Verify audit logs are created for important actions.

### 12.4 Deployment Testing

- Run the system through XAMPP.
- Access the system from another device on the LAN.
- Confirm assets load without internet.
- Test multiple users using the system at the same time.
- Test backup file creation.

---

## 13. Capstone Defense Rationale

This project is suitable for a capstone because it addresses a real operational problem in a local health center. It is not only a generic CRUD system; it includes patient workflows, clinical records, queue management, user roles, audit logs, reports, and offline LAN deployment.

The system is practical for a barangay setting because it does not require internet access or expensive infrastructure. XAMPP, PHP, and MySQL are accessible technologies that can be installed on a local Windows server and maintained by future technical staff.

The project also demonstrates important software engineering concepts:

- Requirements analysis
- Database design
- MVC architecture
- Authentication and authorization
- Data validation
- Audit logging
- Reporting
- Local network deployment
- Privacy and security considerations

---

## 14. Open Decisions

These decisions should be finalized before implementation:

1. Should the system use a first-run admin setup wizard, or a default admin account that must change password after first login?
2. Should the public queue display show only queue numbers, or queue numbers with patient initials?
3. Should PDF export be required for the first defense version, or is printable HTML plus CSV acceptable?
4. Are patient photos required, or should they be listed as a future enhancement?
5. Is there existing patient data to import, or will the system start with fresh records?

## 15. Assumptions and Limitations

The project is designed around the following assumptions and limitations:

- The system will be deployed on a local Windows server using XAMPP.
- The system is intended for LAN-only use inside the health center.
- The system must continue working without an internet connection.
- The initial version will not integrate with PhilHealth, DOH, SMS gateways, or cloud services.
- The system supports health center record management but does not replace licensed medical diagnosis systems.
- Staff users are responsible for accurate and complete data entry.
- Backup files must be handled carefully because they may contain sensitive patient information.
- Advanced features such as cloud backup, mobile applications, and multi-branch synchronization are future enhancements.

---

## 16. Data Privacy and Ethical Considerations

Because the system handles patient and clinical information, privacy and ethical handling of data are important project requirements.

Key considerations:

- Only authorized users should access patient records.
- Admin and staff permissions should be enforced on protected pages and actions.
- Public queue displays should avoid exposing sensitive patient information.
- Audit logs should support accountability for important system actions.
- Patient data should only be used for legitimate health center operations.
- Printed reports should only include information needed for their purpose.
- Backup files should not be publicly accessible through the web server.
- Shared clinic computers should provide clear logout access.

---

## 17. Success Criteria

The system can be considered successful for capstone defense when the following criteria are met:

- Admin and staff users can log in and log out.
- Role-based access control protects admin-only functions.
- Staff can register, search, view, and update patient records.
- Staff can schedule and update appointments.
- Staff can record patient vital signs.
- Staff can create and view consultation records.
- Staff can add patients to the queue and update queue status.
- Dashboard cards show useful daily summaries.
- Reports can be generated for selected date ranges.
- Admin users can view and restore archived patient records.
- Important actions are recorded in audit logs.
- Admin users can create a database backup.
- The system can be accessed from another device on the LAN.
- The system loads required assets without internet access.

---

## 18. Non-Functional Requirements

| Requirement | Target |
|---|---|
| Offline Operation | The system must run on LAN without internet dependency |
| Usability | Staff should be able to complete common workflows quickly during clinic hours |
| Security | Authentication, authorization, CSRF protection, validation, and audit logging must be applied |
| Privacy | Sensitive patient information should only be shown where necessary |
| Performance | The system should support approximately 10 to 20 concurrent LAN users |
| Maintainability | Code should follow a clear MVC structure and consistent naming conventions |
| Reliability | Database operations should use constraints and transactions where needed |
| Backup | Admin users should be able to create database backups |
| Compatibility | The system should run on XAMPP with PHP 8.2+ and MySQL 8.0 |

---

## 19. Out of Scope

The following items are outside the initial capstone implementation scope:

- SMS notifications
- Cloud deployment
- Online backup
- Mobile application
- PhilHealth or DOH system integration
- Full electronic medical record standard compliance
- Advanced laboratory information system
- Full web-based database restore workflow
- Multi-branch or multi-health-center synchronization
- Offline-first synchronization across multiple devices
- AI diagnosis, automated medical advice, or clinical decision support

---

## 20. Recommended Final Scope for Defense

For the final capstone defense, the recommended working scope is:

- Login and logout
- Role-based dashboard
- Patient registration and search
- Patient profile with vital signs and consultation history
- Appointment scheduling
- Vital signs recording
- Consultation records
- Queue management
- Basic reports
- Archived records page
- Audit logs
- Admin backup

This scope is realistic, useful, and strong enough to demonstrate a complete health center information system.
