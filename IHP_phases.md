# Implementation Roadmap: Patient Record Digitization & IHP System Overhaul

> **Document Purpose**: This document outlines the step-by-step, phased implementation plan for digitizing the three physical paper records of the Barangay Sinalhan Health Center:
> 1. `docs/records/individual_health_profile.md` (PhilHealth Annex A1 IHP)
> 2. `docs/records/prenatal_record.md` (CHO I Pre-Natal Record)
> 3. `docs/records/wellbaby_record.md` (Brgy. Ibaba / CHO Santa Rosa Well Baby Record)
>
> **Development Strategy**: Work will proceed **one phase at a time**. Each phase is self-contained, leaving the system in a clean, fully testable state before proceeding to the next phase.

---

## 🗺️ Master Phase Overview

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ PHASE 1: Database Migration & Core Relational Models                                   │
│ └─► Create 6 new tables, expand patients table, create Active Record models.           │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ PHASE 2: Stage 1 Fast Patient Intake & Household Directory Search                      │
│ └─► 60-second registration form, Family Numbering, program badges, household search.   │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ PHASE 3: Modular Patient Profile Workstation & Annex A1 IHP Medical History (Tabs 1-2) │
│ └─► Sticky Master Header, safety alert badges, Overview tab, full IHP checklist tab.   │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ PHASE 4: Maternal & Prenatal Care Clinical Workstation (Tab 6)                         │
│ └─► Active pregnancy episodes, Naegele EDC, dynamic AOG, GTPAL, serial prenatal visits.│
├────────────────────────────────────────────────────────────────────────────────────────┤
│ PHASE 5: Well Baby & Pediatric Growth Monitoring Workstation (Tab 7)                   │
│ └─► Birth context, NBS certificate, DOH EPI vaccine tracker, head/chest circ logs.    │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ PHASE 6: Cross-Module Integration, Reports, Backup & End-to-End Audit                  │
│ └─► Queue service tagging, appointment linking, DOH/CHO report exports, SQL backup.    │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 📌 Phase 1: Database Foundation, Migration & Core Models

### 1. Goal
Establish the complete relational database architecture and Active Record PHP Models supporting Annex A1 IHP, Maternal/Prenatal Care, and Well Baby Child Health without breaking any existing CRUD endpoints.

### 2. What Needs to Be Implemented
1. Create a dedicated SQL migration file: `database/migrations/2026_09_02_ihp_maternal_wellbaby_schema.sql`.
2. Update the master schema definition in `database/schema.sql` and `docs/database.md`.
3. Expand existing `patients` table with household and socioeconomic columns.
4. Add `waist_circumference` to `vital_signs` table.
5. Create 6 new relational tables:
   - `patient_medical_histories` (1-to-1 with `patients`)
   - `prenatal_records` (1-to-Many with `patients`)
   - `prenatal_visits` (1-to-Many with `prenatal_records`)
   - `past_obstetric_histories` (1-to-Many with `patients`)
   - `wellbaby_records` (1-to-1 with `patients`)
   - `child_growth_logs` (1-to-Many with `wellbaby_records`)
6. Create corresponding PHP Model classes under `app/Models/` with relationship methods, validation rules, and fillable properties.

### 3. Database Changes
* **Modify Table `patients`**:
  - `family_no` VARCHAR(50) NULL (Household cluster identifier)
  - `suffix` VARCHAR(20) NULL (Jr., Sr., III)
  - `religion` VARCHAR(100) NULL
  - `education_attainment` ENUM('No Schooling', 'Elementary', 'High School', 'Vocational', 'College / Post-Graduate') NULL
  - `phic_status` ENUM('Member', 'Dependent', 'Non-Member') NOT NULL DEFAULT 'Non-Member'
  - `phic_type` VARCHAR(100) NULL (Sponsored - NHTS, Employed - Private, IPP - OFW, Lifetime)
  - `father_name` VARCHAR(150) NULL, `father_dob` DATE NULL
  - `mother_name` VARCHAR(150) NULL, `mother_dob` DATE NULL
  - `spouse_name` VARCHAR(150) NULL, `spouse_dob` DATE NULL
* **Modify Table `vital_signs`**:
  - `waist_circumference` DECIMAL(5,2) NULL (cm)
* **New Table `patient_medical_histories`**:
  - `id` INT PK AI, `patient_id` INT UNIQUE FK (`patients.id`) ON DELETE CASCADE
  - `past_medical_history` JSON/TEXT NULL (Allergies, asthma, cancer organ, diabetes, hypertension highest BP, TB organ/category)
  - `surgical_history` JSON/TEXT NULL (Operations 1 & 2 + dates)
  - `family_history` JSON/TEXT NULL (Hereditary diseases checklist)
  - `smoking_status` ENUM('Never', 'Yes', 'Quit') DEFAULT 'Never', `smoking_pack_years` DECIMAL(4,1) NULL
  - `alcohol_status` ENUM('Never', 'Yes', 'Quit') DEFAULT 'Never', `alcohol_bottles_per_day` DECIMAL(4,1) NULL
  - `illicit_drugs` BOOLEAN DEFAULT FALSE
  - `menarche_age` INT NULL, `sexual_onset_age` INT NULL, `lmp` DATE NULL, `period_duration_days` INT NULL, `cycle_interval_days` INT NULL, `pads_per_day` INT NULL, `is_menopausal` BOOLEAN DEFAULT FALSE, `menopause_age` INT NULL, `birth_control_method` VARCHAR(100) NULL
  - `updated_by` INT FK (`users.id`) NULL, `updated_at` TIMESTAMP
* **New Table `prenatal_records`**:
  - `id` INT PK AI, `patient_id` INT FK (`patients.id`) ON DELETE RESTRICT
  - `husband_name` VARCHAR(150) NULL
  - `gravida` INT NOT NULL DEFAULT 1, `para` INT NOT NULL DEFAULT 0
  - `term_births` INT NOT NULL DEFAULT 0, `preterm_births` INT NOT NULL DEFAULT 0, `abortions` INT NOT NULL DEFAULT 0, `living_children` INT NOT NULL DEFAULT 0
  - `lmp` DATE NOT NULL, `edc` DATE NOT NULL, `is_active` BOOLEAN NOT NULL DEFAULT TRUE
  - `pre_eclampsia` BOOLEAN NOT NULL DEFAULT FALSE, `fp_counselling` BOOLEAN NOT NULL DEFAULT TRUE
  - `delivery_date` DATE NULL, `delivery_outcome` ENUM('Live Birth', 'Stillbirth', 'Miscarriage', 'Ectopic', 'Other') NULL
  - `notes` TEXT NULL, `created_by` INT FK (`users.id`) NOT NULL, `created_at` TIMESTAMP, `updated_at` TIMESTAMP
* **New Table `prenatal_visits`**:
  - `id` INT PK AI, `prenatal_id` INT FK (`prenatal_records.id`) ON DELETE CASCADE
  - `visit_date` DATE NOT NULL, `chief_complaint` VARCHAR(255) NULL, `aog_weeks` DECIMAL(4,1) NOT NULL
  - `bp_systolic` INT NULL, `bp_diastolic` INT NULL, `weight_kg` DECIMAL(5,2) NULL, `height_cm` DECIMAL(5,2) NULL
  - `fetal_heart_tone` INT NULL, `fundal_height_cm` DECIMAL(4,1) NULL, `fetal_presentation` ENUM('Cephalic', 'Breech', 'Transverse', 'Undetermined') DEFAULT 'Cephalic'
  - `tcb` VARCHAR(100) NULL, `remarks` TEXT NULL, `attended_by` INT FK (`users.id`) NOT NULL, `created_at` TIMESTAMP
* **New Table `past_obstetric_histories`**:
  - `id` INT PK AI, `patient_id` INT FK (`patients.id`) ON DELETE CASCADE, `gravida_no` INT NOT NULL
  - `delivery_type` ENUM('NSD', 'CS', 'Abortion', 'Other') NOT NULL DEFAULT 'NSD', `infant_sex` ENUM('Male', 'Female', 'Unknown') DEFAULT 'Unknown'
  - `place_of_delivery` VARCHAR(150) NULL, `year_delivered` INT NULL, `attended_by` VARCHAR(100) NULL, `status` ENUM('Alive', 'Not Alive') DEFAULT 'Alive'
  - `birth_date` DATE NULL, `tt_status` VARCHAR(100) NULL, `created_at` TIMESTAMP
* **New Table `wellbaby_records`**:
  - `id` INT PK AI, `patient_id` INT UNIQUE FK (`patients.id`) ON DELETE CASCADE
  - `mother_patient_id` INT FK (`patients.id`) ON DELETE SET NULL NULL
  - `birth_time` TIME NULL, `birth_weight_kg` DECIMAL(4,2) NOT NULL, `birth_length_cm` DECIMAL(4,1) NOT NULL
  - `place_of_delivery` ENUM('Hospital', 'Lying-in', 'Home', 'Others') NOT NULL DEFAULT 'Lying-in'
  - `delivery_type` ENUM('Normal Spontaneous Delivery (NSD)', 'Caesarean Section (CS)', 'Others') NOT NULL DEFAULT 'Normal Spontaneous Delivery (NSD)'
  - `attended_by` ENUM('Doctor', 'Nurse', 'Midwife', 'Hilot/TBA', 'Others') NOT NULL DEFAULT 'Midwife'
  - `newborn_screening_done` BOOLEAN NOT NULL DEFAULT FALSE, `newborn_screening_date` DATE NULL, `newborn_screening_result` VARCHAR(100) NULL
  - `mother_cpab_tt` VARCHAR(100) NULL, `feeding_method` ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding'
  - `created_by` INT FK (`users.id`) NOT NULL, `created_at` TIMESTAMP, `updated_at` TIMESTAMP
* **New Table `child_growth_logs`**:
  - `id` INT PK AI, `wellbaby_id` INT FK (`wellbaby_records.id`) ON DELETE CASCADE
  - `log_date` DATE NOT NULL, `age_months` DECIMAL(4,1) NOT NULL, `weight_kg` DECIMAL(5,2) NOT NULL, `height_cm` DECIMAL(5,2) NOT NULL
  - `head_circumference_cm` DECIMAL(4,1) NULL, `chest_circumference_cm` DECIMAL(4,1) NULL, `temperature` DECIMAL(4,2) NULL
  - `feeding_method` ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding'
  - `vaccines_administered` VARCHAR(255) NULL, `vitamin_a_dose` BOOLEAN DEFAULT FALSE, `deworming_dose` BOOLEAN DEFAULT FALSE
  - `tcb_notes` TEXT NULL, `recorded_by` INT FK (`users.id`) NOT NULL, `created_at` TIMESTAMP

### 4. Backend Changes
* Update [`app/Models/Patient.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/Patient.php) with new fillable fields and relationship helpers (`medicalHistory()`, `prenatalRecords()`, `wellbabyRecord()`, `familyMembers()`).
* Update [`app/Models/VitalSigns.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/VitalSigns.php) to support `waist_circumference`.
* Create Models:
  - [`app/Models/PatientMedicalHistory.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/PatientMedicalHistory.php)
  - [`app/Models/PrenatalRecord.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/PrenatalRecord.php)
  - [`app/Models/PrenatalVisit.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/PrenatalVisit.php)
  - [`app/Models/PastObstetricHistory.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/PastObstetricHistory.php)
  - [`app/Models/WellbabyRecord.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/WellbabyRecord.php)
  - [`app/Models/ChildGrowthLog.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/ChildGrowthLog.php)

### 5. Frontend / UI Changes
* None (pure backend/schema phase). Existing UI must continue functioning normally.

### 6. Affected Files
* `database/schema.sql` [MODIFY]
* `database/migrations/2026_09_02_ihp_maternal_wellbaby_schema.sql` [NEW]
* `app/Models/Patient.php` [MODIFY]
* `app/Models/VitalSigns.php` [MODIFY]
* `app/Models/PatientMedicalHistory.php` [NEW]
* `app/Models/PrenatalRecord.php` [NEW]
* `app/Models/PrenatalVisit.php` [NEW]
* `app/Models/PastObstetricHistory.php` [NEW]
* `app/Models/WellbabyRecord.php` [NEW]
* `app/Models/ChildGrowthLog.php` [NEW]

### 7. Testing Requirements & Verification
* Execute the SQL migration script in MySQL.
* Verify all 6 new tables and column additions exist via `SHOW TABLES;` and `DESCRIBE patients;`.
* Run test PHP script to instantiate each new Model and perform a test query.
* Open `/patients`, `/consultations`, `/queue`, `/appointments` in the browser to ensure zero regressions.

### 8. Phase Gate Criteria
* All tables and foreign keys exist in MySQL.
* Zero errors or regressions on existing patient registration and consultation workflows.

---

## 📌 Phase 2: Stage 1 Fast Patient Intake & Household Directory Search

### 1. Goal
Upgrade patient registration into a fast 4-section intake form (60–90 seconds) and enable instant household clustering search (`Family No`) in the Patient Directory.

### 2. What Needs to Be Implemented
1. Redesign [`app/Views/patients/create.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/create.php) and [`app/Views/patients/edit.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/edit.php) into 4 structured cards:
   - **Card 1: Personal Identity** (First, Middle, Last, Suffix, DOB, Sex, Civil Status, Blood Type)
   - **Card 2: Household & Address** (Family Number, Contact No, Barangay, Street Address)
   - **Card 3: PhilHealth & Socioeconomic** (PHIC Status, Category, PIN, Occupation, Education, Religion)
   - **Card 4: Immediate Family & Emergency Contact** (Father, Mother, Spouse, Emergency Person, Relationship, Emergency No)
2. Upgrade [`app/Controllers/PatientController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/PatientController.php) validation to validate and sanitize all new demographic fields.
3. Upgrade [`app/Views/patients/index.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/index.php):
   - Add **Family Number** column.
   - Add **Visual Program Badges** (`[Prenatal]`, `[Well Baby]`, `[Senior]`, `[OPD Gen]`).
   - Add Directory Filter for **Program Category** (*All Programs, General OPD, Maternal/Prenatal, Well Baby, Senior*).
   - Add Directory Filter for **Age Groups** (*0–1 Infant, 2–5 Toddler, 6–15 School Age, 16–24 Youth, 25–59 Adult, 60+ Senior*).
   - Enable searching by `Family No` or `PhilHealth PIN`.

### 3. Database Changes
* None (uses schema from Phase 1).

### 4. Backend Changes
* [`app/Controllers/PatientController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/PatientController.php):
  - In `store()` and `update()`: add validation rules for `family_no`, `suffix`, `religion`, `education_attainment`, `phic_status`, `phic_type`, `father_name`, `mother_name`, `spouse_name`.
  - In `index()`: update query builder to filter by `program_type` (detecting active prenatal or well baby records) and search across `family_no` and `philhealth_no`.
* [`app/Models/Patient.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/Patient.php):
  - Add search scoping helper `searchWithFilters($filters)`.
  - Add auto-computed accessor `getProgramBadgeAttribute()`.

### 5. Frontend / UI Changes
* [`app/Views/patients/create.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/create.php): 4-card progressive layout with Flatpickr DOB boundaries, name letter-only mask, and PhilHealth PIN auto-formatting.
* [`app/Views/patients/edit.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/edit.php): Matching 4-card layout pre-populating existing data.
* [`app/Views/patients/index.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/index.php): Data table with Family No column, program badge pill, and filter toolbar.

### 6. Affected Files
* `app/Controllers/PatientController.php` [MODIFY]
* `app/Models/Patient.php` [MODIFY]
* `app/Views/patients/index.php` [MODIFY]
* `app/Views/patients/create.php` [MODIFY]
* `app/Views/patients/edit.php` [MODIFY]

### 7. Testing Requirements & Verification
* **Test 1 (Fast Intake Registration)**: Register a patient with complete demographics; verify successful save and redirection to profile with flash toast.
* **Test 2 (Validation Boundaries)**: Attempt invalid submissions (digits in name, 10-digit phone, future DOB); verify clean error messages.
* **Test 3 (Household Clustering)**: Register 3 patients (Mother, Father, Child) with the same Family Number `FAM-0428`. In the directory, search `FAM-0428` and verify all 3 family members display together.
* **Test 4 (Filters & Badges)**: Test program filter and age group filter dropdowns.

### 8. Phase Gate Criteria
* Fast registration creates records in under 90 seconds.
* Household search by `family_no` groups family members accurately.
* Program badges and filters operate smoothly.

---

## 📌 Phase 3: Modular Patient Profile Workstation & Annex A1 IHP Medical History (Tabs 1 & 2)

### 1. Goal
Transform `/patients/{id}` into a multi-tab clinical workstation with a persistent Master Header card, clinical safety badges, Tab 1 Overview, and Tab 2 full Annex A1 IHP Medical History.

### 2. What Needs to Be Implemented
1. Refactor [`app/Views/patients/show.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/show.php) into the **Master Profile Workstation Layout**:
   - **Persistent Master Header Card**: Full Name + Suffix, Patient ID, Family No, Age/Sex, Blood Type, PhilHealth PIN & Status, Complete Address, Emergency Contact.
   - **Prominent Clinical Safety Badges**: `[!] ALLERGY ALERT: (Specifics)`, `[!] HIGH-RISK: Pre-Eclampsia`, `[!] HYPERTENSION ALERT`.
   - **Action Bar**: `[ + Enqueue Today ]`, `[ + Take Vitals ]`, `[ + New Consult ]`, `[ Edit Profile ]`, `[ Print Record ]`.
   - **Modular Tab Strip**:
     * `[ 📊 Tab 1: Overview & Snapshot ]`
     * `[ 📋 Tab 2: IHP Medical History ]`
     * `[ 🩺 Tab 3: Consultations (SOAP) ]`
     * `[ 📈 Tab 4: Vital Signs History ]`
     * `[ 💉 Tab 5: Universal Immunizations ]`
     * `[ 🤰 Tab 6: Maternal / Prenatal * ]`
     * `[ 👶 Tab 7: Well Baby & Growth * ]`
     * `[ 📅 Tab 8: Appointments & Queue ]`
2. Implement **Tab 1 (Overview & Clinical Snapshot)**:
   - Latest vital signs card with auto-calculated BMI and abnormal vital alerts.
   - Active medical conditions list.
   - Household family members card (clicking a relative opens their profile).
   - Recent consultations and upcoming appointments.
3. Implement **Tab 2 (Annex A1 IHP Medical History)**:
   - Section 1: Past Medical Illnesses Checklist (Allergy, Asthma, Cancer organ, Diabetes, Hypertension highest BP, PTB category, etc.).
   - Section 2: Surgical & Hospitalization History (Operations 1 & 2 + Dates & Hospitals).
   - Section 3: Family Hereditary Diseases (Hypertension, Diabetes, Cancer, Asthma, Kidney stones).
   - Section 4: Personal & Social Lifestyle Habits (Smoking pack-years, Alcohol bottles/day, Illicit drugs).
   - Section 5: Female Reproductive & Menstrual History (Menarche age, sexual onset, cycle interval, pads/day, menopause, birth control method).
4. Create Controller endpoints to save and update IHP Medical History via asynchronous AJAX or standard POST.

### 3. Database Changes
* None (uses schema from Phase 1).

### 4. Backend Changes
* Create [`app/Controllers/PatientMedicalHistoryController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/PatientMedicalHistoryController.php) (or update `PatientController.php`) with:
  - `saveHistory($patientId)`: Validates and persists JSON checklists and reproductive fields to `patient_medical_histories`.
  - `getHistory($patientId)`: Retrieves medical history JSON payload.
* Update `PatientController@show`: Eager-load `medicalHistory`, `vitalSigns`, `consultations`, `familyMembers`.
* Update Routes in [`config/routes.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/config/routes.php).

### 5. Frontend / UI Changes
* [`app/Views/patients/show.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/show.php): Master Header + Tab Bar + Tab 1 Overview + Tab 2 IHP Form with interactive toggle cards.

### 6. Affected Files
* `config/routes.php` [MODIFY]
* `app/Controllers/PatientController.php` [MODIFY]
* `app/Controllers/PatientMedicalHistoryController.php` [NEW]
* `app/Models/PatientMedicalHistory.php` [MODIFY]
* `app/Views/patients/show.php` [MODIFY]

### 7. Testing Requirements & Verification
* **Test 1 (Workstation Layout)**: Open a patient profile; verify Master Header card stays clean, formatted, and un-cluttered.
* **Test 2 (Household Linking)**: Verify linked family members sharing the same `family_no` appear on Tab 1 with working navigation links.
* **Test 3 (IHP Save & Update)**: Fill out past medical conditions (e.g. check "Allergy: Penicillin" and "Hypertension: Highest BP 150/90"), past appendectomy in 2019, family history, and smoking habits. Save form.
* **Test 4 (Safety Alert Banner)**: Verify that saving an allergy immediately renders the red `[ ALLERGY: Penicillin ]` badge in the Master Header.

### 8. Phase Gate Criteria
* Profile layout renders without visual clutter.
* IHP medical history saves and updates accurately.
* Critical allergy alerts appear dynamically on the master header.

---

## 📌 Phase 4: Maternal & Prenatal Care Clinical Workstation (Tab 6)

### 1. Goal
Build the specialized clinical workstation for pregnant patients implementing the official CHO I Pre-Natal Record.

### 2. What Needs to Be Implemented
1. Implement **Tab 6 (Maternal & Prenatal Workstation)** inside [`app/Views/patients/show.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/show.php):
   - **Active Pregnancy Episode Card**:
     * Input **LMP** (Last Menstrual Period).
     * System auto-calculates **EDC (Estimated Due Date)** via Naegele's Rule (`LMP + 1 year - 3 months + 7 days`).
     * System auto-calculates **Dynamic AOG (Age of Gestation)** in weeks and days (`floor((today - lmp) / 7) weeks + remainder days`).
     * Obstetric **GTPAL Score** input (Gravida, Para, Term, Preterm, Abortion, Living).
     * Husband / Partner Name, Pre-eclampsia risk toggle, and Family Planning Counselling status.
   - **Past Obstetric History Matrix**:
     * Structured table logging prior deliveries (Gravida 1, 2, 3... Delivery type NSD/CS/Abortion, Infant Sex, Delivery Place, Year Delivered, Attendant, Child Status Alive/Deceased, Maternal TT injections with year).
   - **Serial Prenatal Follow-up Visits Log**:
     * Trimester visit history table tracking: Visit Date, Chief Complaint, AOG, Maternal BP, Weight kg, Height cm, **Fetal Heart Tone (FHT bpm)** with 120–160 bpm normal range alert, **Fundal Height (FH cm)**, **Fetal Presentation** (*Cephalic, Breech, Transverse*), **TCB / Tetanus status**, and Midwife Remarks.
     * Modal form for logging new follow-up visits (`[ + Log Prenatal Visit ]`).
     * Button to conclude/deliver pregnancy episode (`[ Conclude Pregnancy / Record Delivery Outcome ]`).

### 3. Database Changes
* None (uses schema from Phase 1).

### 4. Backend Changes
* Create [`app/Controllers/PrenatalController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/PrenatalController.php):
  - `storeEpisode($patientId)`: Validates and creates pregnancy episode, auto-computing EDC and AOG.
  - `updateEpisode($id)`: Updates GTPAL, partner, and risk factors.
  - `storeVisit($prenatalId)`: Validates and records a serial prenatal visit.
  - `storePastObstetric($patientId)`: Adds historical delivery row to `past_obstetric_histories`.
  - `concludeEpisode($id)`: Records delivery date, outcome (*Live Birth, Stillbirth, Miscarriage*), and marks episode inactive (`is_active = FALSE`).
* Helper calculations in [`app/Models/PrenatalRecord.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/PrenatalRecord.php):
  - `calculateEDC($lmpDate)`: Naegele's rule calculation.
  - `calculateCurrentAOG($lmpDate)`: Current weeks and days calculation.
* Update Routes in [`config/routes.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/config/routes.php).

### 5. Frontend / UI Changes
* Update [`app/Views/patients/show.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/show.php): Add Tab 6 Prenatal Workstation view with active episode banner, past obstetric history table, serial visits log, and modals for "New Pregnancy", "Log Prenatal Visit", and "Add Past Delivery".

### 6. Affected Files
* `config/routes.php` [MODIFY]
* `app/Controllers/PrenatalController.php` [NEW]
* `app/Models/PrenatalRecord.php` [MODIFY]
* `app/Models/PrenatalVisit.php` [MODIFY]
* `app/Models/PastObstetricHistory.php` [MODIFY]
* `app/Views/patients/show.php` [MODIFY]

### 7. Testing Requirements & Verification
* **Test 1 (Pregnancy Enrollment & Auto-Calculations)**: For a female patient (e.g. 26 y/o), start a pregnancy episode with LMP `2026-02-10`. Verify auto-calculated EDC is `2026-11-17` and AOG accurately calculates current gestational weeks/days.
* **Test 2 (GTPAL & Past Deliveries)**: Enter GTPAL `G:2 P:1 T:1 P:0 A:0 L:1` and log Gravida 1 (NSD, Male, 2024, Midwife Ramos, Alive, TT2 given). Verify clean tabular display.
* **Test 3 (Serial Visit Logging)**: Record a follow-up visit: BP `130/85`, Wt `58.5 kg`, FHT `144 bpm`, FH `25 cm`, Presentation `Cephalic`, TCB `TT3 given`. Verify record saves and appears in the chronological visit log.
* **Test 4 (High-Risk Alerts)**: Check Pre-eclampsia toggle; verify `[ HIGH-RISK: Pre-Eclampsia ]` banner appears in the Master Header.

### 8. Phase Gate Criteria
* Automated EDC and AOG calculations are accurate.
* Past delivery matrix and serial prenatal visits log persist and render cleanly.
* High-risk maternal warnings trigger properly.

---

## 📌 Phase 5: Well Baby & Pediatric Growth Monitoring Workstation (Tab 7)

### 1. Goal
Build the pediatric workstation for infants and children (0–5 years) implementing the official Brgy. Ibaba / CHO Santa Rosa Well Baby Record.

### 2. What Needs to Be Implemented
1. Implement **Tab 7 (Well Baby Workstation)** inside [`app/Views/patients/show.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/show.php):
   - **Infant Birth Context & Certificate Card**:
     * Birth Time, Birth Weight (kg/g), Birth Length (cm).
     * Place of Delivery (*Hospital, Lying-in, Home*), Delivery Type (*NSD, CS*), Birth Attendant (*Doctor, Midwife, Nurse, Hilot/TBA*).
     * **Newborn Screening (NBS)** record: Done (Yes/No), Date Screened, Result (*Normal, Elevated, Pending*), Certificate Number.
     * Mother Link: Link to registered mother (`mother_patient_id`), mother's name, mother's age, and **Maternal CPAB TT status** (Child Protected at Birth).
     * Father's Name and Complete Address.
   - **DOH Mandatory Routine EPI Immunization Matrix**:
     * Visual grid with date-administered timestamps for all childhood vaccines:
       - *At Birth*: BCG, Hepatitis B (<24 hours)
       - *1.5 Months (6 Wks)*: Pentavalent 1, OPV 1, Rotavirus 1
       - *2.5 Months (10 Wks)*: Pentavalent 2, OPV 2, Rotavirus 2
       - *3.5 Months (14 Wks)*: Pentavalent 3, OPV 3, IPV
       - *9 Months*: MCV 1 (Anti-Measles)
       - *12 Months*: MCV 2 (MMR Booster)
     * Checkbox toggles with date picker allowing staff to record administered doses on the spot.
   - **National Supplementation Program Log**:
     * Semi-annual Vitamin A capsule dose tracking (6 doses).
     * Semi-annual Deworming tablet dose tracking (6 doses).
   - **Periodic Pediatric Growth Monitoring Log**:
     * Serial growth measurements table: Date, Exact Age in Months, Weight (kg), Height (cm), **Head Circumference (cm)**, **Chest Circumference (cm)**, Body Temperature, **Infant Feeding Practice** (*LAM / Exclusive Breastfeeding, Bottle Feeding, Mixed Feeding*), Vaccines Given, and TCB / Developmental Milestone notes.
     * Modal for `[ + Record Growth Visit ]`.

### 3. Database Changes
* None (uses schema from Phase 1).

### 4. Backend Changes
* Create [`app/Controllers/WellbabyController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/WellbabyController.php):
  - `storeBirthRecord($patientId)`: Validates and saves infant birth circumstances and newborn screening.
  - `updateImmunization($wellbabyId)`: Saves/updates EPI routine immunization dates and syncs with central `immunizations` table.
  - `storeGrowthLog($wellbabyId)`: Validates and saves monthly growth anthropometrics.
* Helper methods in [`app/Models/WellbabyRecord.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/WellbabyRecord.php) and [`app/Models/ChildGrowthLog.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/ChildGrowthLog.php).
* Update Routes in [`config/routes.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/config/routes.php).

### 5. Frontend / UI Changes
* Update [`app/Views/patients/show.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/patients/show.php): Add Tab 7 Well Baby Workstation with birth context card, EPI vaccine grid, supplementation pills, growth monitoring table, and "Record Growth Visit" modal.

### 6. Affected Files
* `config/routes.php` [MODIFY]
* `app/Controllers/WellbabyController.php` [NEW]
* `app/Models/WellbabyRecord.php` [MODIFY]
* `app/Models/ChildGrowthLog.php` [MODIFY]
* `app/Views/patients/show.php` [MODIFY]

### 7. Testing Requirements & Verification
* **Test 1 (Infant Enrollment & Mother Link)**: Register an infant (e.g. 2 months old), link to registered mother `P-2026-00042`, enter birth weight `3.10 kg`, length `49 cm`, Lying-in delivery by Midwife Ramos, and NBS certificate details. Save and verify data.
* **Test 2 (EPI Vaccine Administration)**: Check off BCG and Hepatitis B with birth date, and Penta 1/OPV 1/Rota 1 with 1.5-month date. Save and verify completion stamps.
* **Test 3 (Supplementation & Growth Logs)**: Record a monthly growth checkup: Age `1.5 months`, Wt `4.4 kg`, Ht `54 cm`, Head `37.5 cm`, Chest `37.0 cm`, Feeding `LAM / Exclusive Breastfeeding`. Verify entry saves and appears in the growth log table.

### 8. Phase Gate Criteria
* Infant birth details and newborn screening certificate persist accurately.
* DOH EPI vaccine matrix updates and reflects administered dates.
* Monthly growth anthropometrics log correctly.

---

## 📌 Phase 6: Cross-Module Integration, Reports, Backup & End-to-End Audit

### 1. Goal
Connect the digitized patient records across all remaining system modules (Queue Service Tagging, Appointment Program Linking, Report Generator, and SQL Backup) and perform an end-to-end regression audit.

### 2. What Needs to Be Implemented
1. **Queue Management Module**:
   - Update [`app/Views/queue/index.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/queue/index.php) and `QueueController.php` to allow selecting **Service Program** (*General OPD, Prenatal Care, Well Baby Immunization, Senior Care*) when enqueuing a patient.
   - Display service program badges on the queue management dashboard and lobby display board (`/queue/display`).
2. **Appointment Scheduling Module**:
   - Update [`app/Views/appointments/create.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/appointments/create.php) and `AppointmentController.php` to categorize appointment purpose (*General Consultation, Prenatal Follow-up, Routine EPI Vaccine, Deworming / Vitamin A, Senior Checkup*).
3. **Consultations (SOAP Notes)**:
   - When opening a consultation for a patient, display their IHP active conditions and allergy alerts directly above the Subjective complaint input.
4. **Reports & Analytics Module**:
   - Expand [`app/Controllers/ReportController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/ReportController.php) and [`app/Views/reports/index.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Views/reports/index.php) to generate exportable (HTML print & CSV) reports for:
     * **Maternal Health Report**: Active pregnancies, estimated deliveries by month, pre-eclampsia cases.
     * **Child Immunization Coverage Report**: DOH EPI completion rates for infants in Barangay Sinalhan.
     * **Morbidity / Chronic Disease Registry**: Disease distribution (Hypertension, Diabetes, Asthma) from IHP medical histories.
5. **Database Backup Module**:
   - Verify [`app/Controllers/BackupController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/BackupController.php) includes all new tables (`patient_medical_histories`, `prenatal_records`, `prenatal_visits`, `past_obstetric_histories`, `wellbaby_records`, `child_growth_logs`) in SQL dump generation.

### 3. Database Changes
* Update `queue_entries` with `service_type` VARCHAR(50) NULL.
* Update `appointments` with `program_type` VARCHAR(50) NULL.

### 4. Backend Changes
* Modify [`app/Controllers/QueueController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/QueueController.php)
* Modify [`app/Controllers/AppointmentController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/AppointmentController.php)
* Modify [`app/Controllers/ConsultationController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/ConsultationController.php)
* Modify [`app/Controllers/ReportController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/ReportController.php)
* Modify [`app/Controllers/BackupController.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Controllers/BackupController.php)

### 5. Frontend / UI Changes
* Update Queue views, Appointment modals, Consultation SOAP views, and Report generator filters.

### 6. Affected Files
* `app/Controllers/QueueController.php` [MODIFY]
* `app/Controllers/AppointmentController.php` [MODIFY]
* `app/Controllers/ConsultationController.php` [MODIFY]
* `app/Controllers/ReportController.php` [MODIFY]
* `app/Controllers/BackupController.php` [MODIFY]
* `app/Views/queue/index.php` [MODIFY]
* `app/Views/appointments/create.php` [MODIFY]
* `app/Views/consultations/create.php` [MODIFY]
* `app/Views/reports/index.php` [MODIFY]

### 7. Testing Requirements & End-to-End Audit
* **Complete Clinical Journey Test**:
  1. Register a Family: Mother (`Maria`), Father (`Pedro`), and Infant (`Juan Jr.`) sharing `Family No: FAM-0428`.
  2. Complete Mother's IHP medical history with Penicillin allergy.
  3. Enroll Mother in Prenatal Care episode (LMP, EDC, GTPAL, serial visit).
  4. Enroll Infant in Well Baby Care (Birth record, NBS certificate, BCG & HepB vaccine checkoff, 1-month growth visit).
  5. Enqueue Mother for "Prenatal Care" and Infant for "Well Baby Vaccine".
  6. Conduct a SOAP consultation for the mother, verifying the Penicillin allergy banner is visible during prescribing.
  7. Generate and print the Maternal Health Report and EPI Vaccine Coverage Report.
  8. Create an automated SQL database backup at `/backup` and verify all new tables and patient data are intact in the generated `.sql` file.

### 8. Phase Gate Criteria
* End-to-end clinical workflow executes flawlessly across all 8 modules.
* Reports export accurate statistics.
* Database backups successfully archive the complete clinical database.
* Zero PHP warnings, strict notices, or JavaScript console errors.

---

## 📋 Implementation Checklist Tracker

| Phase | Title | Status | Tested & Approved |
|---|---|---|---|
| **Phase 1** | Database Foundation, Migration & Core Models | ✅ Completed | [x] Passed (30/30) |
| **Phase 2** | Stage 1 Fast Patient Intake & Household Directory Search | ✅ Completed | [x] Passed (17/17) |
| **Phase 3** | Modular Patient Profile & Annex A1 IHP Medical History | ✅ Completed | [x] Passed (16/16) |
| **Phase 4** | Maternal & Prenatal Care Clinical Workstation | ✅ Completed | [x] Passed (19/19) |
| **Phase 5** | Well Baby & Pediatric Growth Monitoring Workstation | ✅ Completed | [x] Passed (22/22) |
| **Phase 6** | Cross-Module Integration, Reports, Backup & Audit | ✅ Completed | [x] Passed (26/26) |
