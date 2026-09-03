# Sinalhan Health Center - Patient Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-teal.svg?style=flat-square)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-8.0-blue.svg?style=flat-square)](https://www.mysql.com/)
[![Web Server](https://img.shields.io/badge/XAMPP-v3.3%2B-orange.svg?style=flat-square)](https://www.apachefriends.org/)


A capstone-oriented, local area network (LAN) patient record and clinic management system built specifically for the **Barangay Sinalhan Health Center**. 

This system replaces traditional paper-based workflows with a secure, offline-first digital database that handles patient intake, vital signs logging, SOAP-format clinical consultations, appointment booking, waiting-room queue management, and daily operational reporting.

---

## 🚀 Core Features

### 📋 Patient Registry
* **Intelligent ID Generation**: Automatically formats patient codes as `P-[YEAR]-[5-digit-sequence]` (e.g. `P-2026-00042`).
* **Intake Duplicate Warnings**: Dynamic AJAX checker alerts staff of exact matching records on registration blur.
* **Filterable Directory**: Search by key attributes and filter by Barangay, Sex, or calculated Age Groups.

### 🩺 Clinical Documentation
* **Vital Signs Tracking**: Log Blood Pressure, Temperature, Pulse Rate, Respiratory Rate, Oxygen Saturation, Weight, and Height.
* **Automatic BMI Calculator**: Real-time Body Mass Index calculation and category indexing (Underweight, Normal, Overweight, Obese).
* **Vitals Range Alerts**: Highlights clinical abnormalities (Fever, Hypothermia, Hypertension, Hypotension, Hypoxia) in distinct colors.
* **SOAP Consultation Logs**: Standardized clinical documentation (Subjective, Objective, Assessment, Plan) with asynchronous detail modals.

### 📅 Appointment Scheduler
* **Schedule Overlap Prevention**: Dynamic AJAX validator warns of slot conflicts for overlapping appointments.
* **Quick Status Updates**: Transition visits (`Scheduled`, `Completed`, `Cancelled`, `Missed`) with one click.

### 🚶 Lobby Queue Board
* **Daily Ticket Queue**: Auto-incrementing queues (`001`, `002`, `003`...) resetting daily.
* **Lobby Display Monitor**: A full-screen display page (`/queue/display`) for waiting-area TVs showing active queue numbers.
* **Native Audio Synthesizer**: Generates alert chimes using browser `AudioContext` on new calls, requiring no external media files.
* **JSON Polling**: Updates wait statuses every 5 seconds.

### 📊 Reports & Administration
* **Admin-only Controls**: Administrative accounts manage user credentials, review audit logs, and trigger database backups.
* **Brute-force Lockouts**: Accounts automatically suspend after 5 failed login attempts.
* **Operational Reports**: View, filter, and print (via clean `@media print` print-layouts) or export logs as CSV spreadsheets.
* **Data Recovery**: Archive (soft-delete) and restore patient records.

---

## 🛠️ Technology Stack

* **Backend Engine**: PHP 8.2+ (Framework-free custom MVC architecture).
* **Database Layer**: MySQL 8.0 (InnoDB engine, strict constraints, and parameter indexing).
* **Frontend UI**: Bootstrap 5, Bootstrap Icons, custom CSS.
* **Local Plugins**: DataTables, SweetAlert2, Flatpickr, Chart.js, jQuery.
* **Asset Model**: 100% self-hosted, offline-ready assets (zero CDN calls) to ensure uninterrupted operations on LAN.

---

## 📁 Folder Structure

```text
sinalhan-hc-system/
│
├── app/                      # Application Source Files
│   ├── Core/                 # Router, Autoloader, Singleton DB, Base Controllers
│   ├── Controllers/          # Request Handler classes (Auth, Patient, Queue, etc.)
│   ├── Models/               # Data Queries & Validations (User, Patient, VitalSigns, etc.)
│   ├── Views/                # PHP/HTML page templates and layout grids
│   └── Middleware/           # Route Guards (Auth, Admin, Guest, Password Reset interceptor)
│
├── config/                   # System Configurations (database, session, routing paths)
├── database/                 # SQL Schemas, structural updates, and sample seeds
├── docs/                     # Architectural, Database, Features, and Security docs
├── public/                   # Public Web Root
│   ├── index.php             # Front Controller (Application Entry Point)
│   └── assets/               # CSS, Local JS libraries, and vendor directories
├── storage/                  # Protected backups, local error logs, and file uploads
└── README.md                 # Project Github Index
```

---

## ⚙️ Installation & Setup (XAMPP local deployment)

Follow these steps to deploy and run the system on your local XAMPP stack:

### Prerequisite
* Install [XAMPP](https://www.apachefriends.org/) with PHP 8.2+ and MySQL/MariaDB.

### 1. Project Placement
Clone this repository directly into your XAMPP's web directory:
```bash
cd C:\xampp\htdocs
git clone https://github.com/your-username/sinalhan-hc-system.git
```

### 2. Database Initialization
1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Open your browser and navigate to `http://localhost/phpmyadmin/`.
3. Create a new database named `sinalhan_hc`.
4. Import the schema and seed data:
   * Import [`database/schema.sql`](database/schema.sql) first.
   * Import [`database/seed.sql`](database/seed.sql) to populate initial lookup roles and administrative accounts.

### 3. Connection Configuration
Navigate to `config/database.php` and verify the MySQL connection settings:
```php
return [
    'host' => 'localhost',
    'dbname' => 'sinalhan_hc',
    'username' => 'root',
    'password' => '', // Set your local database password here
    'charset' => 'utf8mb4'
];
```

### 4. Running the System
1. Open your browser and go to:
   ```text
   http://localhost/sinalhan-hc-system/public/
   ```
2. Log in using the default administrator credentials:
   * **Username**: `admin`
   * **Password**: `AdminPassword123`
3. Upon first login, the system will prompt you to change the temporary password to a secure one before proceeding to the main dashboard.

---

## 📖 Detailed System Documentation

For in-depth technical analysis and system flows, review the documents inside the [`docs/`](docs) folder:

* **[IHP Implementation Roadmap](IHP_phases.md)**: Phased, incremental roadmap for digitizing IHP, Maternal, and Well Baby patient records.
* **[Clinical Records Analysis & Design](docs/records/analysis_and_design_of_hc_records.md)**: Architectural analysis of the three health center paper records.
* **[UI Wireframes](docs/wireframes.md)**: Comprehensive low-fidelity wireframes including the modular Patient Profile Workstation.
* **[File & Codebase Guide](docs/file_guide.md)**: Plain-English guide explaining the purpose of every file and directory.
* **[Architecture Guide](docs/architecture.md)**: Details the MVC core engine, request lifecycles, and local network setups.
* **[Database Design](docs/database.md)**: Tables schema, relationships, indexes, ERD diagram, and data dictionary.
* **[Features Specifications](docs/features.md)**: Details clinical tools, calculated metrics, and operation dashboards.
* **[Security Architecture](docs/security.md)**: Explains hashing protocols, CSRF/XSS defenses, and account lockouts.


