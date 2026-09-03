# System File Guide & Codebase Reference
**Barangay Sinalhan Health Center - Patient Management System**

This guide explains the purpose of **every folder and file** in this system in simple, easy-to-understand language.

---

## 🍽️ The Big Picture: How the System Works (MVC Analogy)

This system is built using the **MVC (Model-View-Controller)** pattern. Think of it like a restaurant:

```
[ User in Browser ] 
        │  1. Makes a request (e.g. "Show me patient list")
        ▼
[ Router & Middleware ] (The Receptionist & Security Guard)
   - Checks if the page exists and if you are logged in.
        │  2. Passes request to the Controller
        ▼
[ Controller ] (The Waiter / Manager)
   - Takes your request, talks to the Model for data, and prepares the View.
   ├── talks to ──► [ Model ] (The Pantry & Kitchen Database)
   │                   - Fetches, saves, or updates data in MySQL.
   └── renders  ──► [ View ] (The Plated Meal)
                       - Clean HTML & CSS that the user sees on screen.
```

1. **Router (`app/Core/Router.php`)**: The receptionist. It reads the URL you clicked and directs it to the correct controller.
2. **Middleware (`app/Middleware/`)**: The security guard. Checks if you are logged in or if you have admin permissions before letting you in.
3. **Controller (`app/Controllers/`)**: The waiter/coordinator. It receives user actions, asks the Model for data, applies business rules, and loads the webpage (View).
4. **Model (`app/Models/`)**: The database worker. It directly queries, saves, updates, or deletes records in the MySQL database.
5. **View (`app/Views/`)**: The visual webpage. The HTML, Bootstrap styles, tables, and forms that the user interacts with.

---

## 📁 Directory Structure Overview

```text
sinalhan-hc-system/
├── app/                  # Application code (Core, Controllers, Models, Views, Middleware)
│   ├── Controllers/      # Handles user requests and coordinates responses
│   ├── Core/             # Foundational framework engine (Router, DB connection, Error Handler)
│   ├── Middleware/       # Security gatekeepers (Login check, Role check)
│   ├── Models/           # Database query handlers for each table
│   └── Views/            # HTML/PHP templates and user interfaces
├── config/               # System configuration files (Database, App settings, Routes)
├── database/             # SQL database schema and sample data
├── docs/                 # Detailed technical, security, architecture, and feature documentation
├── public/               # The only folder accessible to web browsers (Entry point & Assets)
│   ├── assets/           # CSS, JavaScript, Fonts, and Icons
│   └── index.php         # Front controller (Where every request starts)
├── scripts/              # Command-line maintenance tools (CLI utilities)
├── storage/              # File uploads, database backup archives, and error logs
├── CODEX.md              # Complete project specification and master rules
└── README.md             # Project overview and setup instructions
```

---

## ⚙️ 1. Core Framework Files (`app/Core/`)

The files in `app/Core/` make up the custom, lightweight PHP framework that powers the entire system.

| File Name | Purpose (Simple Explanation) |
|---|---|
| **`Autoloader.php`** | **The Automatic File Finder.** Instead of writing `require` or `include` at the top of every file, the autoloader automatically loads PHP classes as soon as they are needed. |
| **`Controller.php`** | **The Base Controller.** The parent class for all controllers. It provides shared helper functions like `view()` (to display a page), `redirect()` (to send the user to another page), and `json()` (to send API data). |
| **`Database.php`** | **The Database Connection Manager.** Connects PHP to the MySQL database safely using PDO. It uses the Singleton pattern so the whole app shares one efficient connection. |
| **`ErrorHandler.php`** | **The Safety Net & Crash Handler.** If an unexpected error or exception happens, this catches it, logs the crash details into `storage/logs/error.log`, and displays a friendly 500 error page instead of a broken screen. |
| **`Model.php`** | **The Base Model.** The parent class for all models. It automatically gives every model access to the database connection. |
| **`Router.php`** | **The URL Traffic Director.** Reads the incoming website link (e.g. `/patients/create`) and calls the corresponding controller method to handle it. |

---

## 🛡️ 2. Middleware Security Gatekeepers (`app/Middleware/`)

Middleware runs **before** a controller is reached. It acts as a checkpoint.

| File Name | Purpose (Simple Explanation) |
|---|---|
| **`AuthMiddleware.php`** | **Login Checker & Inactivity Timer.** Ensures only logged-in users can access protected pages. If a user is not logged in, it redirects them to `/login`. It also monitors user activity and automatically logs them out after 15 minutes of idle time. |
| **`AdminMiddleware.php`** | **Admin Only Checkpoint.** Ensures only users with the `admin` role can access sensitive features (like User Management, System Audit Logs, and Database Backups). Regular staff are blocked with an unauthorized message. |
| **`GuestMiddleware.php`** | **Guest Only Checkpoint.** Prevents already logged-in users from seeing the `/login` page again, redirecting them straight to their dashboard. |

---

## 🎮 3. Controllers (`app/Controllers/`)

Controllers receive user inputs from buttons or forms, perform necessary checks, ask the Models for data, and display the correct View.

| File Name | Purpose (Simple Explanation) |
|---|---|
| **`AuthController.php`** | **Authentication & Security Controller.** Handles logging in, logging out, password resets, enforcing the 15-minute temporary lockout after 5 failed password attempts, and managing session security. |
| **`DashboardController.php`** | **Dashboard Overview Controller.** Gathers summary numbers for the main dashboard (total patients, today's queue count, upcoming appointments, low inventory alerts, and quick actions). |
| **`PatientController.php`** | **Patient Records Controller.** Handles patient registration, searching, viewing full profiles, editing demographic info (including blood type, occupation, PhilHealth number), and archiving/restoring patient records. |
| **`AppointmentController.php`** | **Appointment Scheduling Controller.** Handles booking patient appointments, editing schedules, preventing time-slot conflicts, updating statuses (*Scheduled, Completed, Cancelled, Missed*), and listing upcoming appointments. |
| **`QueueController.php`** | **Daily Queue Flow Controller.** Manages the walk-in patient queue (*Waiting, In Consultation, Done, Cancelled*), moves patients between stages, and powers the live Public TV Queue Display screen. |
| **`ConsultationController.php`** | **Doctor & Nurse Clinical Notes Controller.** Handles creating and viewing SOAP clinical consultation records (Subjective, Objective, Assessment, Plan) linked to patient histories. |
| **`VitalSignsController.php`** | **Triage & Vital Signs Controller.** Handles recording blood pressure, heart rate, temperature, respiratory rate, weight, height, and BMI during triage before consultation. |
| **`UserController.php`** | **Staff Accounts & Access Controller.** Admin-only panel for registering health center staff, assigning job titles/departments, toggling account active/inactive status, clearing failed login lockouts, and resetting passwords. Enforces Main Admin vs Co-Admin privilege boundaries. |
| **`ReportController.php`** | **Reports & Analytics Controller.** Generates administrative reports and DOH clinical registries (Maternal Health Registry, Child EPI Coverage, Chronic Morbidity Registry) with printable views and CSV data exports. |
| **`AuditLogController.php`** | **Audit Trail Controller.** Admin-only panel to review system activity logs (who logged in, created a patient, edited a record, or changed a user status) with date and role filtering. |
| **`BackupController.php`** | **Database Backup Controller.** Admin-only panel that creates full SQL database backup snapshots covering all clinical and system tables, allows downloading backups, and manages backup history. |
| **`PatientMedicalHistoryController.php`** | **Annex A1 IHP Controller.** Handles saving and updating comprehensive PhilHealth Annex A1 Individual Health Profile histories, surgical logs, hereditary diseases, and habits. |
| **`PrenatalController.php`** | **Maternal & Pre-Natal Controller.** Manages pregnancy episode enrollment, Naegele EDC and dynamic AOG calculations, serial trimester follow-up checkup visits, and past deliveries matrix. |
| **`WellbabyController.php`** | **Well Baby & Pediatric Growth Controller.** Manages infant birth records, DOH Routine EPI Childhood Immunization checkoffs, Vitamin A/Deworming, and monthly pediatric growth monitoring logs. |

---

## 🗄️ 4. Models (`app/Models/`)

Models interact directly with MySQL database tables using secure prepared statements.

| File Name | Database Table | Purpose (Simple Explanation) |
|---|---|---|
| **`User.php`** | `users` | Manages staff accounts, password hashing, role permissions, tracking failed login attempts, and lockout cooldown calculations. |
| **`Patient.php`** | `patients` | Manages patient demographic data, PhilHealth uniqueness checks, auto-generating patient ID numbers, search filters, and archiving (soft-deletes). |
| **`PatientMedicalHistory.php`** | `patient_medical_histories` | Stores PhilHealth Annex A1 medical background, past surgical operations, family hereditary diseases, habits, and menstrual history. |
| **`PrenatalRecord.php`** | `prenatal_records` | Manages pregnancy episodes, Naegele's rule EDC computation, dynamic AOG calculation, GTPAL scores, and pre-eclampsia flags. |
| **`PrenatalVisit.php`** | `prenatal_visits` | Records serial prenatal checkup visits across trimesters (AOG, BP, Fetal Heart Tone, Fundal Height, Presentation, TCB). |
| **`PastObstetricHistory.php`** | `past_obstetric_histories` | Stores historical records of prior deliveries (Gravida #, delivery mode NSD/CS/Abortion, place, attendant, child survival, and maternal TT). |
| **`WellbabyRecord.php`** | `wellbaby_records` | Stores infant birth context, birth weight/length, delivery mode, Newborn Screening certificates, and maternal CPAB links. |
| **`ChildGrowthLog.php`** | `child_growth_logs` | Logs monthly pediatric checkups (weight, height, head/chest circumference, feeding practice, Vitamin A, and Deworming). |
| **`Immunization.php`** | `immunizations` | Manages universal childhood (EPI) and adult immunization records, vaccine doses, and administration timestamps. |
| **`Appointment.php`** | `appointments` | Manages appointment records with Program Type tagging, date/time conflict validation, status updates, and calendar listings. |
| **`QueueEntry.php`** | `queue_entries` | Manages daily queue ticket numbering (e.g. `001`), Service Type tagging, queue status updates, and live display board data feeds. |
| **`Consultation.php`** | `consultations` | Saves and retrieves clinical diagnosis notes, chief complaints, physical findings, and prescription plans. |
| **`VitalSigns.php`** | `vital_signs` | Saves patient triage measurements (BP, Pulse, Temperature, Height, Weight, BMI) linked to patients or consultations. |
| **`AuditLog.php`** | `audit_logs` | Writes and queries immutable security audit logs (user, action name, IP address, timestamp, details) for accountability. |

---

## 🖥️ 5. Views & User Interfaces (`app/Views/`)

Views contain the HTML and presentation markup that users see in their web browsers.

### General Layout (`app/Views/layout/`)
* **`header.php`**: The top of every page (HTML head, CSS links, page title, breadcrumbs).
* **`navbar.php`**: The main navigation menu with links to Patients, Queue, Appointments, Reports, and Admin tools.
* **`footer.php`**: The bottom of every page (JavaScript scripts, SweetAlert popups, client-side 15-minute inactivity timer).

### Main Dashboard
* **`dashboard.php`**: The home screen displaying health center quick stats, today's queue summary, upcoming appointments, and shortcut buttons.

### Module Views
| Folder | Files | Purpose (Simple Explanation) |
|---|---|---|
| **`auth/`** | `login.php`<br>`change-password.php` | The sign-in page with lockout warnings and the password update form. |
| **`patients/`** | `index.php`<br>`create.php`<br>`edit.php`<br>`show.php` | Patient directory table with search, patient registration form with live validation, profile editing form, and the full medical profile timeline view. |
| **`appointments/`** | `index.php`<br>`create.php`<br>`edit.php` | The appointment schedule list, booking form with conflict warning, and status updater. |
| **`queue/`** | `index.php`<br>`display.php` | Staff queue management workstation and the fullscreen TV display board for waiting patients. |
| **`consultations/`** | `create.php`<br>`show.php` | SOAP clinical examination form and consultation details view. |
| **`users/`** | `index.php`<br>`create.php`<br>`edit.php` | Staff accounts table with role badges and lockout reset buttons, user registration form, and account editor. |
| **`reports/`** | `index.php`<br>`export.php` | Statistical reporting filters and the clean printable/exportable report layout. |
| **`audit-logs/`** | `index.php` | System audit trail table with filters for date, user, role, and action type. |
| **`backup/`** | `index.php` | Database backup management table with one-click snapshot and download buttons. |
| **`archive/`** | `patients.php` | Admin-only archive vault where soft-deleted patient records can be viewed and restored. |
| **`errors/`** | `404.php`<br>`500.php` | Clean, branded error pages for "Page Not Found" (404) and "Internal Server Error" (500). |

---

## 🔧 6. Configuration Files (`config/`)

| File Name | Purpose (Simple Explanation) |
|---|---|
| **`app.php`** | Contains general system settings (System Name, Timezone: `Asia/Manila`, Debug Mode `true/false`, and Session Cookie options). |
| **`database.php`** | Stores the MySQL database connection credentials (Host: `127.0.0.1`, Database Name: `sinalhan_hc_db`, Port: `3306`, Charset: `utf8mb4`). |
| **`routes.php`** | The complete registry of all valid URLs in the system, mapping each URL to its controller and security middleware. |

---

## 🗃️ 7. Database Files (`database/`)

| File Name | Purpose (Simple Explanation) |
|---|---|
| **`schema.sql`** | The complete MySQL database blueprint. Defines all tables, columns, data types, primary keys, foreign key constraints, and performance indexes. |
| **`seed.sql`** | Initial setup data containing default admin and staff accounts to get the system running on a new computer. |

---

## 🌐 8. Public Entry Point & Assets (`public/`)

This is the only directory exposed to the web browser.

| File Name / Folder | Purpose (Simple Explanation) |
|---|---|
| **`index.php`** | **The Front Controller.** The first file executed on every click. It boots the autoloader, starts secure sessions, checks CSRF tokens, registers the global error handler, and dispatches the router. |
| **`.htaccess`** | Apache web server configuration that routes all website requests through `public/index.php`. |
| **`assets/`** | Local copies of Bootstrap CSS, Bootstrap Icons, Flatpickr datepicker, SweetAlert2 alerts, and custom health center styling (guaranteeing 100% offline functionality). |

---

## 🛠️ 9. Maintenance & CLI Scripts (`scripts/`)

| File Name | Purpose (Simple Explanation) |
|---|---|
| **`initialize_db.php`** | Setup script that connects to MySQL, creates the database, and executes `schema.sql` and `seed.sql` automatically. |
| **`unlock_user.php`** | Command-line emergency tool (`php scripts/unlock_user.php [username]`) to instantly unlock a locked-out staff account directly from the server terminal. |
| **`copy_assets.js`** | Node.js helper script that copies vendor CSS and JS libraries from `node_modules` into `public/assets/`. |

---

## 📦 10. Storage Directory (`storage/`)

| Folder | Purpose (Simple Explanation) |
|---|---|
| **`storage/backups/`** | Stores generated `.sql` database backup snapshot files. |
| **`storage/logs/`** | Stores `error.log` where system crashes, unhandled exceptions, and diagnostic traces are safely recorded. |
| **`storage/uploads/`** | Dedicated folder for any uploaded patient documents or attachments. |

---

## 📚 11. System Documentation (`docs/` & Root)

| File Name | Purpose (Simple Explanation) |
|---|---|
| **`docs/file_guide.md`** | *(This document)* Plain English explanation of every file and folder across the entire codebase. |
| **`docs/architecture.md`** | Technical architecture document detailing the MVC flow, class structures, and LAN deployment design. |
| **`docs/database.md`** | Comprehensive database data dictionary detailing every table, column, relationship, and indexing rule. |
| **`docs/features.md`** | Detailed functional feature specifications for all 10 core health center modules. |
| **`docs/security.md`** | Deep-dive security guide covering CSRF, XSS escaping, SQL injection prevention, RBAC hierarchy, inactivity timeouts, and temporary lockout safeguards. |
| **`docs/wireframes.md`** | UI/UX wireframes and layout blueprints for all screens and forms. |
| **`CODEX.md`** | The comprehensive master specification rulebook and source of truth for the entire system. |
| **`README.md`** | Quick-start guide covering installation steps, default login credentials, and server prerequisites. |

---

*Barangay Sinalhan Health Center - Patient Management System*  
*Developed for offline-first, reliable, and secure local clinic management.*
