# Sinalhan Health Center - System Analysis Report

## 1. Project Structure & Source Code
The system is built on a lightweight, custom MVC (Model-View-Controller) framework without external heavy dependencies like Laravel. It uses PHP 8.2+ and relies on a Front Controller (`public/index.php`) and a custom PSR-4 Autoloader (`app/Core/Autoloader.php`).
- **Core (`app/Core/`)**: Contains the Database Singleton (`Database.php`), `Router.php`, `Controller.php`, and `ErrorHandler.php`.
- **Controllers (`app/Controllers/`)**: Manage the HTTP requests and business logic (e.g., `PatientController.php`, `WellbabyController.php`, `ConsultationController.php`).
- **Models (`app/Models/`)**: Handle the database queries and validation. The system uses a pattern of soft deletes for most operational data.
- **Views (`app/Views/`)**: Contains the frontend templates built using PHP, HTML, Bootstrap 5, and JavaScript. 
- **Validators (`app/Validators/`)**: Separate classes to handle complex form normalization and business logic validation.
- **Middleware (`app/Middleware/`)**: Used for route guards such as `AuthMiddleware` (checks login and timeouts), `AdminMiddleware` (role protection), and `GuestMiddleware`.

## 2. Database & Data Models
The database uses MySQL 8.0 with InnoDB. The schema was recently updated and refactored.
- **Core Entities**: `users`, `patients`, `vital_signs`, `consultations`, `appointments`, `queue_entries`, `prescriptions`.
- **Specialized Clinical Tables**: `patient_medical_histories` (PhilHealth Annex A1), `prenatal_records`, `prenatal_visits`, `past_obstetric_histories`, `wellbaby_records`, `child_growth_logs`, `immunizations`.
- **PCB (Primary Care Benefit)**: `pcb_obligated_services`, `pcb_service_logs`.
- **Administrative**: `audit_logs`, `settings`.
- **Recent Changes**: Lab-related tables (`lab_results`, `lab_requests`) were dropped as part of a recent migration to streamline the system. User account fields like `employee_id` and `department` are being phased out in favor of simpler role-based management.

## 3. Pages, Modules & Workflows
The system is divided into several clinical and operational modules:
- **Patient Registry**: Intake with dynamic duplicate detection, Annex A1 Individual Health Profile (IHP), and demographic data.
- **Vital Signs**: Standalone or consultation-linked vitals with automated BMI calculation and color-coded abnormal alerts (e.g., Fever, Hypertension).
- **SOAP Consultations**: Subjective, Objective, Assessment, Plan structured notes.
- **Maternal Care (Prenatal)**: Gestational calculators (Naegele's Rule for EDC, AOG), obstetric matrix, and trimester follow-up logs.
- **Well Baby Care (0-5 yrs)**: Birth records, newborn screening, pediatric growth tracking, and DOH EPI routine immunization schedule.
- **Appointment Scheduling**: Booking with program tagging and overlap prevention.
- **Queue Board**: A daily ticket system with a dedicated public lobby display (`/queue/display`) equipped with an audio chime and 5-second JSON polling.
- **Reports & Administration**: Built-in DOH registries, audit trails, and backup utilities. Also includes an **Archived Records Hub** for managing soft-deleted patients and consultations.

## 4. User Roles & Authentication
The system uses a 3-tier role architecture (`super_admin`, `admin`, `staff`):
- **Authentication**: Supports dual-identifier login. A strict 5-attempt brute-force lockout window (15 mins) is implemented.
- **First-Time Setup**: The `must_change_password` flag traps new users in an enclave (`/change-password`) until they set a secure 8-character password.
- **Role Privileges**: Administrators have access to audit logs, backups, user management, and the archived records hub. Regular staff can only access their specific clinical tools and can only delete data they personally recorded.
- **Session Security**: Uses a 15-minute inactivity timeout.

## 5. Assets & Configuration
- **Configurations (`config/`)**: Contains `database.php` for PDO settings, `app.php` for environment toggles (debug mode), and `routes.php` defining all GET/POST endpoints.
- **Assets (`public/assets/`)**: Completely offline-first. Uses local Bootstrap 5, jQuery, DataTables, Chart.js, SweetAlert2, and Flatpickr without relying on CDNs.
- **Documentation (`docs/`)**: Extensive documentation mapping the architecture, database schema, security, and capstone requirements (such as the recent Chapter 3 updates).

## Summary
The system is a highly structured, offline-first LAN web app tailored for Philippine barangay health centers. It heavily utilizes AJAX for non-disruptive workflows, enforces strict data retention (soft-deletes) compliance, and integrates clinical decision support (warnings for vitals/allergies) directly into the UI. It is stable, well-documented, and ready for further development.
