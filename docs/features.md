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
* **Role Hierarchy & Abuse Prevention**:
  * **Main Administrator (User ID 1)** holds master permission over all administrator and staff accounts.
  * **Co-Administrators (User ID > 1 with role `admin`)** can manage `staff` accounts, but are strictly blocked from promoting staff to `admin`, creating admin accounts (overridden to `staff`), or editing peer administrators.
* **Status Lifecycle & Deactivation (No Account Deletion)**: Account deletion is permanently disabled to preserve audit and medical logs. Account status is managed via dedicated table action buttons (**Activate** `bi-person-check-fill` and **Deactivate** `bi-person-x-fill`).
* **15-Minute Temporary Lockout Safeguard**: After 5 consecutive failed login attempts, an account is placed in a **15-minute temporary cooldown window**. Account status remains `active`, but authentication is blocked until the 15 minutes expire (displaying exact remaining time to the user).
* **Admin Clear Lockout Button**: Administrators can instantly override a staff member's 15-minute lockout timer by clicking the **Clear Lockout** button (`bi-unlock-fill`) on the User Accounts directory table, writing a `USER_LOCKOUT_RESET` audit log.
* **Security Password Reset**: Critical user resets require the logged-in administrator to enter their current password to authorize a custom temporary password. Validates that the new password is not identical to the user's existing password.
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

