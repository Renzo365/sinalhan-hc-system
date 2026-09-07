# SQL Database Design - Barangay Sinalhan Health Center

This document outlines the database design and schema specifications for the **Patient Management System** of the Barangay Sinalhan Health Center.

The database is built on **MySQL 8.0** using the **InnoDB storage engine** to support referential integrity, transaction safety, and crash recovery. It is designed to run locally under XAMPP.

---

## 1. Database Architecture Principles

To ensure data integrity, speed, and security in a multi-user local area network (LAN) environment, the database follows these design principles:

1. **Referential Integrity**: To prevent accidental deletion of critical clinical logs, all patient-related foreign keys use `ON DELETE RESTRICT`. Patient records are archived rather than physically deleted.
2. **Indexing Strategy**: Secondary indexes are added to columns frequently used in `WHERE`, `JOIN`, `ORDER BY`, or search clauses (e.g., patient numbers, names, dates, and status fields).
3. **Audit Trail**: Tracking fields (`created_at`, `updated_at`, `created_by`, `updated_by`) are implemented on operational records. An independent `audit_logs` table records sensitive actions.
4. **Soft Deletes (Archiving)**: Patient and clinical records are never permanently deleted from the active interface. Instead, archiving is handled via soft-delete columns (`deleted_at`, `deleted_by`, `archive_reason`).
5. **Data Isolation & Security**: Passwords are saved as secure bcrypt hashes. A `must_change_password` flag is applied to default accounts to force user password updates on first login.
6. **Consistent Naming**: All table names are lowercase and plural (e.g., `patients`, `vital_signs`). Column names are snake_case.

---

## 2. Entity-Relationship Diagram (ERD)

The following Mermaid diagram shows the relationships between the core database tables:

```mermaid
erDiagram
    users ||--o{ patients : "registers (created_by)"
    users ||--o{ patients : "modifies (updated_by)"
    users ||--o{ patients : "archives (deleted_by)"
    users ||--o{ vital_signs : "records (recorded_by)"
    users ||--o{ consultations : "examines (consulted_by)"
    users ||--o{ appointments : "schedules (created_by)"
    users ||--o{ queue_entries : "creates (created_by)"
    users ||--o{ prescriptions : "prescribes (prescribed_by)"
    users ||--o{ immunizations : "vaccinates (administered_by)"
    users ||--o{ audit_logs : "performs (user_id)"
    users ||--o{ settings : "updates (updated_by)"

    patients ||--o| patient_medical_histories : "has_profile"
    patients ||--o{ vital_signs : "undergoes"
    patients ||--o{ consultations : "receives"
    patients ||--o{ appointments : "books"
    patients ||--o{ queue_entries : "joins"
    patients ||--o{ prescriptions : "is_prescribed"
    patients ||--o{ immunizations : "gets"
    patients ||--o{ prenatal_records : "maternal_episodes"
    patients ||--o| wellbaby_records : "birth_and_child_record"
    patients ||--o{ past_obstetric_histories : "prior_pregnancies"

    prenatal_records ||--o{ prenatal_visits : "follow_up_checks"
    wellbaby_records ||--o{ child_growth_logs : "growth_monitoring"
    patients ||--o{ wellbaby_records : "mother_of"

    vital_signs ||--o| consultations : "linked_to"
    consultations ||--o{ prescriptions : "contains"
    consultations ||--o{ lab_requests : "triggers"
    patients ||--o{ lab_requests : "requests"
    lab_requests ||--o| lab_results : "produces"
```

---

## 3. Data Dictionary

### 3.1 `users` Table
Stores login credentials, system roles, organizational assignments, and profile information for health center staff.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique internal identifier for the user. |
| `username` | VARCHAR(50) | UNIQUE, NOT NULL | | Login username. |
| `password_hash` | VARCHAR(255) | NOT NULL | | Bcrypt hash of the user password. |
| `role` | ENUM('admin', 'staff') | NOT NULL | | Determines access privileges. |
| `first_name` | VARCHAR(50) | NOT NULL | | User's first name. |
| `middle_name` | VARCHAR(50) | | NULL | User's middle name. |
| `last_name` | VARCHAR(50) | NOT NULL | | User's last name. |
| `email` | VARCHAR(100) | UNIQUE | NULL | User's professional email address. |
| `contact_no` | VARCHAR(20) | | NULL | User's contact/phone number. |
| `employee_id` | VARCHAR(50) | | NULL | PRC License / Employee ID number. |
| `department` | VARCHAR(100) | | NULL | Clinic Unit or Department assignment. |
| `job_title` | VARCHAR(50) | | NULL | E.g., 'Nurse', 'Midwife', 'BHW', 'Records Officer'. |
| `status` | ENUM('active', 'inactive') | NOT NULL | 'active' | Account status determining login authorization (inactive accounts blocked). |
| `failed_attempts` | INT | NOT NULL | 0 | Count of consecutive failed login attempts before lockout. |
| `must_change_password` | BOOLEAN | NOT NULL | TRUE | Flag forcing password reset upon first login (1 = must change, 0 = active). |
| `last_login_at` | DATETIME | | NULL | Timestamp of last successful authentication. |
| `last_failed_login_at` | DATETIME | | NULL | Timestamp of last failed authentication attempt. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Record creation timestamp. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Record modification timestamp. |
| `deleted_at` | TIMESTAMP | | NULL | Soft-deletion timestamp for deactivated/archived user accounts. |

\* *Automatic update on row modification (`ON UPDATE CURRENT_TIMESTAMP`)*

---

### 3.2 `patients` Table
Stores master demographics and core profile data. Every citizen (including pregnant women and infants) has a single master record. Patient files can be soft-deleted (archived) instead of permanently deleted.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique internal identifier for the patient. |
| `patient_no` | VARCHAR(20) | UNIQUE, NOT NULL | | Human-readable ID (e.g., `P-YYYY-XXXXX`). |
| `family_no` | VARCHAR(50) | | NULL | PhilHealth/CHO Family Identification Number for household grouping. |
| `first_name` | VARCHAR(50) | NOT NULL | | Patient's first name. |
| `middle_name` | VARCHAR(50) | | NULL | Patient's middle name. |
| `last_name` | VARCHAR(50) | NOT NULL | | Patient's last name. |
| `suffix` | VARCHAR(20) | | NULL | Name extension (e.g., 'Jr.', 'Sr.', 'III'). |
| `dob` | DATE | NOT NULL | | Patient's date of birth. |
| `sex` | ENUM('Male', 'Female') | NOT NULL | | Patient's biological sex. |
| `civil_status` | ENUM('Single', 'Married', 'Widowed', 'Divorced', 'Separated', 'Annulled', 'Others') | NOT NULL | | Patient's marital status. |
| `blood_type` | ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown') | NOT NULL | 'Unknown' | Patient's ABO blood type classification. |
| `religion` | VARCHAR(100) | | NULL | Religious affiliation. |
| `occupation` | VARCHAR(100) | | NULL | Patient's occupation or employment status. |
| `education_attainment` | ENUM('No Schooling', 'Elementary', 'High School', 'Vocational', 'College / Post-Graduate') | | NULL | Highest completed educational level. |
| `contact_no` | VARCHAR(20) | | NULL | Patient's primary phone number (11-digit 09XXXXXXXXX format). |
| `barangay` | VARCHAR(100) | NOT NULL | 'Sinalhan' | Resident barangay. |
| `address` | TEXT | NOT NULL | | Complete street address. |
| `phic_status` | ENUM('Member', 'Dependent', 'Non-Member') | NOT NULL | 'Non-Member' | PhilHealth membership status. |
| `phic_type` | VARCHAR(100) | | NULL | PhilHealth category (e.g., 'Sponsored - NHTS', 'Employed - Private', 'IPP - OFW', 'Lifetime'). |
| `philhealth_no` | VARCHAR(20) | UNIQUE | NULL | PhilHealth ID number (XX-XXXXXXXXX-X format). |
| `father_name` | VARCHAR(150) | | NULL | Father's full name. |
| `father_dob` | DATE | | NULL | Father's date of birth. |
| `mother_name` | VARCHAR(150) | | NULL | Mother's maiden name. |
| `mother_dob` | DATE | | NULL | Mother's date of birth. |
| `spouse_name` | VARCHAR(150) | | NULL | Spouse's full name. |
| `spouse_dob` | DATE | | NULL | Spouse's date of birth. |
| `emergency_name` | VARCHAR(100) | | NULL | Contact person in case of emergency. |
| `emergency_relationship`| VARCHAR(50) | | NULL | Relationship to emergency contact person. |
| `emergency_no` | VARCHAR(20) | | NULL | Phone number of emergency contact (11-digit 09XXXXXXXXX format). |
| `deleted_at` | TIMESTAMP | | NULL | Timestamp when patient was archived (NULL if active). |
| `deleted_by` | INT | FK (`users.id`), NULL | NULL | Admin user who archived the record. |
| `archive_reason` | TEXT | | NULL | Explanation for archiving the record. |
| `created_by` | INT | FK (`users.id`), NOT NULL | | User who registered the patient. |
| `updated_by` | INT | FK (`users.id`), NULL | NULL | User who last updated the profile. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Record creation timestamp. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Record modification timestamp. |

---

### 3.3 `vital_signs` Table
Records patient vitals. Multiple sets of vitals may exist for a patient over multiple clinic visits.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique vital signs record identifier. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE RESTRICT | | Patient linked to these vitals. |
| `bp_systolic` | INT | | NULL | Blood pressure - Systolic (mmHg). |
| `bp_diastolic` | INT | | NULL | Blood pressure - Diastolic (mmHg). |
| `heart_rate` | INT | | NULL | Pulse/Heart rate (bpm). |
| `respiratory_rate`| INT | | NULL | Respiration rate (breaths/min). |
| `temperature` | DECIMAL(4,2) | | NULL | Body temperature in Celsius. |
| `weight` | DECIMAL(5,2) | | NULL | Weight in kilograms (kg). |
| `height` | DECIMAL(5,2) | | NULL | Height in centimeters (cm). |
| `bmi` | DECIMAL(4,2) | | NULL | Body Mass Index (kg/m2), auto-calculated. |
| `waist_circumference`| DECIMAL(5,2) | | NULL | Adult waist circumference in centimeters (cm). |
| `oxygen_saturation`| INT | | NULL | Blood oxygen saturation SpO2 (%). |
| `notes` | TEXT | | NULL | Observations or complaints about vitals. |
| `recorded_by` | INT | FK (`users.id`), NOT NULL | | Staff member who took the vital signs. |
| `recorded_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Date and time recorded. |

---

### 3.4 `consultations` Table
Main clinic documentation logs using the SOAP (Subjective, Objective, Assessment, Plan) format.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique consultation record identifier. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE RESTRICT | | Patient who had the consultation. |
| `vital_signs_id` | INT | FK (`vital_signs.id`) ON DELETE SET NULL | NULL | Associated vitals captured during this visit. |
| `subjective` | TEXT | NOT NULL | | Patient complaints and subjective history. |
| `objective` | TEXT | NOT NULL | | Physical findings and objective assessments. |
| `assessment` | TEXT | NOT NULL | | Diagnosis, clinical impression, or ICD code description. |
| `plan` | TEXT | NOT NULL | | Treatment, prescriptions, medical advice, and tests. |
| `status` | ENUM('Open', 'Completed', 'Cancelled') | NOT NULL | 'Completed' | Status of the consultation record. |
| `consulted_by` | INT | FK (`users.id`), NOT NULL | | Doctor or healthcare worker conducting the checkup. |
| `consulted_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Timestamp of the consultation. |
| `created_by` | INT | FK (`users.id`), NOT NULL | | User who recorded the entry. |
| `updated_by` | INT | FK (`users.id`), NULL | NULL | User who last updated the entry. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Creation timestamp. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Modification timestamp. |
| `deleted_at` | TIMESTAMP | | NULL | Soft delete/archived timestamp. |
| `deleted_by` | INT | FK (`users.id`), NULL | NULL | User who archived the record. |
| `archive_reason` | TEXT | | NULL | Reason why this consultation record was soft-deleted. |

---

### 3.5 `appointments` Table
Manages future schedules and follow-up patient visits.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique appointment identifier. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE RESTRICT | | Patient booked for the appointment. |
| `program_type`| ENUM('General OPD', 'Prenatal Care', 'Well Baby Immunization', 'Senior Care') | NOT NULL | 'General OPD' | Clinical service program. |
| `appointment_date`| DATE | NOT NULL | | Scheduled date. |
| `appointment_time`| TIME | NOT NULL | | Scheduled time. |
| `purpose` | VARCHAR(255) | NOT NULL | | Goal (e.g., 'Prenatal Follow-up', 'EPI Vaccination', 'Hypertension Check'). |
| `status` | ENUM('Scheduled', 'Completed', 'Cancelled', 'Missed') | NOT NULL | 'Scheduled' | Booking status. |
| `notes` | TEXT | | NULL | Additional instructions or remarks. |
| `created_by` | INT | FK (`users.id`), NOT NULL | | Staff member who scheduled the appointment. |
| `updated_by` | INT | FK (`users.id`), NULL | NULL | Staff member who modified the booking. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Creation timestamp. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Modification timestamp. |

---

### 3.6 `queue_entries` Table
Tracks patient routing, status, and processing times for the day's clinic queue.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique queue entry identifier. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE RESTRICT | | Patient currently in queue. |
| `service_type`| ENUM('General OPD', 'Prenatal Care', 'Well Baby Immunization', 'Senior Care') | NOT NULL | 'General OPD' | Specialized service track for queue display and room routing. |
| `queue_date` | DATE | NOT NULL | | Date of queue allocation. |
| `queue_no` | INT | NOT NULL | | Daily queue number (resets daily from 1). |
| `status` | ENUM('Waiting', 'Called', 'Serving', 'Completed', 'Cancelled') | NOT NULL | 'Waiting' | Current location in queue process. |
| `time_in` | TIME | NOT NULL | | Clock-in time. |
| `time_called` | TIME | | NULL | Clock time when patient was called. |
| `time_completed` | TIME | | NULL | Clock time when consultation concluded. |
| `created_by` | INT | FK (`users.id`), NOT NULL | | Staff who queued the patient. |
| `updated_by` | INT | FK (`users.id`), NULL | NULL | Staff who updated queue status. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Creation timestamp. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Modification timestamp. |

---

### 3.7 `prescriptions` Table *(Supporting Feature)*
Prescription details associated with a specific consultation.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique prescription identifier. |
| `consultation_id` | INT | FK (`consultations.id`) ON DELETE CASCADE | | Linked clinical consultation. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE RESTRICT | | Patient getting the prescription. |
| `medicine_name` | VARCHAR(150) | NOT NULL | | Drug name / Brand / Generic. |
| `dosage` | VARCHAR(50) | NOT NULL | | Strength (e.g., '500 mg', '5 ml / 120 mg'). |
| `frequency` | VARCHAR(50) | NOT NULL | | E.g., 'Three times a day (TID)', 'Every 8 hours'. |
| `duration` | VARCHAR(50) | NOT NULL | | E.g., '7 days', '1 week'. |
| `instructions` | TEXT | | NULL | E.g., 'Take after meals', 'Take on empty stomach'. |
| `prescribed_by` | INT | FK (`users.id`), NOT NULL | | Doctor who prescribed the medicine. |
| `prescribed_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Timestamp when generated. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Record creation timestamp. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Record modification timestamp. |

---

### 3.8 `immunizations` Table *(Supporting Feature)*
Records childhood or adult immunizations administered at the health center.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique immunization identifier. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE RESTRICT | | Patient receiving the vaccine. |
| `vaccine_name` | VARCHAR(100) | NOT NULL | | Vaccine code/name (e.g., 'BCG', 'HepB', 'Pentavalent'). |
| `dose_number` | INT | NOT NULL | 1 | Dose sequence (e.g., 1 for 1st dose, 2 for booster). |
| `administered_date`| DATE | NOT NULL | | Vaccination date. |
| `remarks` | TEXT | | NULL | Batch numbers, manufacturer, reactions, etc. |
| `administered_by` | INT | FK (`users.id`), NOT NULL | | Clinician who gave the injection. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Record creation timestamp. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Record modification timestamp. |

---

### 3.9 `audit_logs` Table
A security ledger recording updates, deletions, access changes, and administrative actions.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique log entry identifier. |
| `user_id` | INT | FK (`users.id`) ON DELETE SET NULL | NULL | User who executed the action (NULL if guest/system). |
| `username` | VARCHAR(50) | | NULL | Denormalized username for historical traceability. |
| `action` | VARCHAR(100) | NOT NULL | | Action label (e.g. 'PATIENT_ARCHIVED', 'LOGIN_FAILED'). |
| `module` | VARCHAR(50) | NOT NULL | | Impacted module (e.g., 'Auth', 'Queue', 'Backup'). |
| `ip_address` | VARCHAR(45) | NOT NULL | | LAN Client IP address. |
| `user_agent` | TEXT | | NULL | Client web browser details. |
| `details` | TEXT | | NULL | Verbose detail logs or JSON state change diffs. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Occurrence timestamp. |

---

### 3.10 `settings` Table
Key-value configuration items (e.g., Health center name, operating hours, public screen options).

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `setting_key` | VARCHAR(50) | PRIMARY KEY | | Configuration identifier name. |
| `setting_value` | TEXT | | NULL | Value of config item. |
| `description` | VARCHAR(255) | | NULL | Explanatory note of what the key manages. |
| `updated_by` | INT | FK (`users.id`) ON DELETE SET NULL | NULL | Admin who changed the setting. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Modification timestamp. |

---

### 3.11 `lab_requests` Table *(Could-Have Enhancement)*
Tracks laboratory test requests made during consultations.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique laboratory request identifier. |
| `consultation_id` | INT | FK (`consultations.id`) ON DELETE CASCADE | | Associated clinical consultation. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE RESTRICT | | Patient requesting test. |
| `test_name` | VARCHAR(100) | NOT NULL | | Name of test (e.g., 'Fasting Blood Sugar', 'Urinalysis'). |
| `status` | ENUM('Pending', 'Completed', 'Cancelled') | NOT NULL | 'Pending' | Current status. |
| `notes` | TEXT | | NULL | Clinic clinical reasons, indicators. |
| `requested_by` | INT | FK (`users.id`), NOT NULL | | Referring doctor or healthcare worker. |
| `requested_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Creation timestamp. |

---

### 3.12 `lab_results` Table *(Could-Have Enhancement)*
Stores lab results details and references physical diagnostic upload files.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique laboratory result identifier. |
| `lab_request_id` | INT | FK (`lab_requests.id`) ON DELETE CASCADE | | Related laboratory request. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE RESTRICT | | Linked patient. |
| `result_details` | TEXT | NOT NULL | | Medical readings and findings. |
| `file_path` | VARCHAR(255) | | NULL | Local path of the scan report file in XAMPP filesystem. |
| `recorded_by` | INT | FK (`users.id`), NOT NULL | | Medical lab tech or records personnel who uploaded. |
| `recorded_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Time recorded in database. |

---

### 3.13 `patient_medical_histories` Table
Stores comprehensive Annex A1 Individual Health Profile (IHP) medical background, past surgical operations, family hereditary diseases, personal/social habits, and female menstrual history.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique profile history identifier. |
| `patient_id` | INT | UNIQUE, FK (`patients.id`) ON DELETE CASCADE | | Linked patient master record (1-to-1 relationship). |
| `past_medical_history` | JSON / TEXT | | NULL | Checklist of past diseases (allergies, asthma, cancer organ, diabetes, hypertension highest BP, TB organ/category, etc.). |
| `surgical_history` | JSON / TEXT | | NULL | Past operations and dates (Operation 1 & 2). |
| `family_history` | JSON / TEXT | | NULL | Hereditary family diseases checklist (diabetes, hypertension, cancer, asthma, etc.). |
| `smoking_status` | ENUM('Never', 'Yes', 'Quit') | | 'Never' | Tobacco smoking history. |
| `smoking_pack_years` | DECIMAL(4,1) | | NULL | Estimated smoking pack-years. |
| `alcohol_status` | ENUM('Never', 'Yes', 'Quit') | | 'Never' | Alcohol consumption history. |
| `alcohol_bottles_per_day` | DECIMAL(4,1) | | NULL | Average bottles consumed per day. |
| `illicit_drugs` | BOOLEAN | | FALSE | History of illicit drug use (TRUE/FALSE). |
| `menarche_age` | INT | | NULL | Age at first menstrual period (females). |
| `sexual_onset_age` | INT | | NULL | Age at onset of sexual intercourse. |
| `lmp` | DATE | | NULL | Most recent Last Menstrual Period date. |
| `period_duration_days` | INT | | NULL | Typical menstrual flow duration in days. |
| `cycle_interval_days` | INT | | NULL | Menstrual cycle interval in days (e.g., 28 days). |
| `pads_per_day` | INT | | NULL | Number of sanitary pads used per day during menstruation. |
| `is_menopausal` | BOOLEAN | | FALSE | Menopausal status (TRUE/FALSE). |
| `menopause_age` | INT | | NULL | Age when menopause started. |
| `birth_control_method`| VARCHAR(100) | | NULL | Current contraceptive / family planning method used. |
| `baseline_bp_systolic` | INT | | NULL | Baseline systolic blood pressure (mmHg). |
| `baseline_bp_diastolic` | INT | | NULL | Baseline diastolic blood pressure (mmHg). |
| `baseline_heart_rate` | INT | | NULL | Baseline pulse / heart rate (bpm). |
| `baseline_respiratory_rate` | INT | | NULL | Baseline respiratory rate (cpm). |
| `baseline_height` | DECIMAL(5,2) | | NULL | Baseline height in centimeters (cm). |
| `baseline_weight` | DECIMAL(5,2) | | NULL | Baseline weight in kilograms (kg). |
| `baseline_waist_circumference` | DECIMAL(5,2) | | NULL | Baseline waist circumference in centimeters (cm). |
| `gravida` | INT | | NULL | Lifetime total pregnancies. |
| `para` | INT | | NULL | Lifetime total viable deliveries. |
| `delivery_type` | VARCHAR(100) | | NULL | Primary delivery type (NSD, CS, etc.). |
| `term_births` | INT | | NULL | Full-term deliveries. |
| `preterm_births` | INT | | NULL | Preterm deliveries. |
| `abortions` | INT | | NULL | Miscarriages / abortions. |
| `living_children` | INT | | NULL | Number of living children. |
| `pre_eclampsia` | BOOLEAN | | FALSE | Lifetime history of pre-eclampsia (TRUE/FALSE). |
| `fp_counselling` | BOOLEAN | | FALSE | Family planning counselling received. |
| `physical_examination` | JSON / LONGTEXT | | NULL | 6-system physical examination findings (Skin, HEENT, Chest/Lungs, Heart, Abdomen, Extremities). |
| `external_immunizations`| JSON / LONGTEXT | | NULL | Annex A1 lifetime immunization checklist (Children, Young Women, Pregnant, Elderly, Others). |
| `updated_by` | INT | FK (`users.id`), NULL | NULL | Staff who last updated medical history. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Creation timestamp. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Last updated timestamp. |

---

### 3.14 `prenatal_records` Table
Tracks active and historical pregnancy episodes for female patients of reproductive age (1-to-many relationship with `patients`).

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique pregnancy record episode identifier. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE RESTRICT | | Expectant mother master record. |
| `husband_name` | VARCHAR(150) | | NULL | Husband / Partner full name. |
| `gravida` | INT | NOT NULL | 1 | Total number of pregnancies (including current). |
| `para` | INT | NOT NULL | 0 | Total number of deliveries / viable births. |
| `term_births` | INT | NOT NULL | 0 | Number of full-term deliveries (T in GTPAL). |
| `preterm_births` | INT | NOT NULL | 0 | Number of preterm deliveries (P in GTPAL). |
| `abortions` | INT | NOT NULL | 0 | Number of miscarriages/abortions (A in GTPAL). |
| `living_children` | INT | NOT NULL | 0 | Number of currently living children (L in GTPAL). |
| `lmp` | DATE | NOT NULL | | Last Menstrual Period date. |
| `edc` | DATE | NOT NULL | | Estimated Date of Confinement (calculated via Naegele's rule). |
| `is_active` | BOOLEAN | NOT NULL | TRUE | TRUE if currently pregnant; FALSE once delivered or ended. |
| `pre_eclampsia` | BOOLEAN | NOT NULL | FALSE | History of pregnancy-induced hypertension / pre-eclampsia. |
| `fp_counselling` | BOOLEAN | NOT NULL | TRUE | Access to family planning counselling (TRUE/FALSE). |
| `delivery_date` | DATE | | NULL | Actual delivery date (if episode is completed). |
| `delivery_outcome` | ENUM('Live Birth', 'Stillbirth', 'Miscarriage', 'Ectopic', 'Other') | | NULL | Outcome of this pregnancy episode. |
| `notes` | TEXT | | NULL | High-risk observations and clinical notes. |
| `created_by` | INT | FK (`users.id`), NOT NULL | | Midwife / Health worker who opened the prenatal record. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Date record was created. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Modification timestamp. |

---

### 3.15 `prenatal_visits` Table
Logs serial, recurring prenatal checkups across trimesters for an active pregnancy.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique prenatal visit identifier. |
| `prenatal_id` | INT | FK (`prenatal_records.id`) ON DELETE CASCADE | | Linked pregnancy episode. |
| `visit_date` | DATE | NOT NULL | | Date of follow-up checkup. |
| `chief_complaint` | VARCHAR(255) | | NULL | Patient's chief complaint during visit. |
| `aog_weeks` | DECIMAL(4,1) | NOT NULL | | Age of Gestation at visit in weeks. |
| `bp_systolic` | INT | | NULL | Maternal systolic blood pressure (mmHg). |
| `bp_diastolic` | INT | | NULL | Maternal diastolic blood pressure (mmHg). |
| `weight_kg` | DECIMAL(5,2) | | NULL | Maternal weight in kg during visit. |
| `height_cm` | DECIMAL(5,2) | | NULL | Maternal height in cm. |
| `fetal_heart_tone` | INT | | NULL | Fetal Heart Tone / Rate (bpm). |
| `fundal_height_cm` | DECIMAL(4,1) | | NULL | Fundal height measurement in centimeters. |
| `fetal_presentation` | ENUM('Cephalic', 'Breech', 'Transverse', 'Undetermined') | | 'Cephalic' | Fetal presentation / lie. |
| `tcb` | VARCHAR(100) | | NULL | Target Client Benefit / Tetanus status indicator. |
| `remarks` | TEXT | | NULL | Clinical observations, vitamins prescribed, next visit target. |
| `attended_by` | INT | FK (`users.id`), NOT NULL | | Attending midwife, nurse, or physician. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Record creation timestamp. |

---

### 3.16 `past_obstetric_histories` Table
Logs past pregnancy histories (Gravida 1, 2, 3...) for an expectant mother.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique obstetric history entry identifier. |
| `patient_id` | INT | FK (`patients.id`) ON DELETE CASCADE | | Linked female patient master record. |
| `gravida_no` | INT | NOT NULL | | Pregnancy number (e.g. 1 for 1st pregnancy, 2 for 2nd). |
| `delivery_type` | ENUM('NSD', 'CS', 'Abortion', 'Other') | NOT NULL | 'NSD' | Type of delivery (Normal Spontaneous, C-Section, Abortion). |
| `infant_sex` | ENUM('Male', 'Female', 'Unknown') | | 'Unknown' | Sex of the delivered child. |
| `place_of_delivery`| VARCHAR(150) | | NULL | Hospital, lying-in clinic, or home. |
| `year_delivered` | INT | | NULL | Calendar year when child was delivered. |
| `attended_by` | VARCHAR(100) | | NULL | Delivery attendant (Doctor, Midwife, Nurse, Hilot/TBA). |
| `status` | ENUM('Alive', 'Not Alive') | NOT NULL | 'Alive' | Current status of child. |
| `birth_date` | DATE | | NULL | Child's exact date of birth if known. |
| `tt_status` | VARCHAR(100) | | NULL | Tetanus Toxoid injection status and year given during that pregnancy. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Creation timestamp. |

---

### 3.17 `wellbaby_records` Table
Stores birth history, newborn screening, parental links, and infant care details for babies/children (0-5 years).

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique Well Baby record identifier. |
| `patient_id` | INT | UNIQUE, FK (`patients.id`) ON DELETE CASCADE | | Infant's master patient record (1-to-1 relationship). |
| `mother_patient_id`| INT | FK (`patients.id`) ON DELETE SET NULL | NULL | Optional foreign key link to mother's registered patient profile. |
| `birth_time` | TIME | | NULL | Time of birth. |
| `birth_weight_kg` | DECIMAL(4,2) | NOT NULL | | Weight at birth in kilograms (kg). |
| `birth_length_cm` | DECIMAL(4,1) | NOT NULL | | Length at birth in centimeters (cm). |
| `place_of_delivery`| ENUM('Hospital', 'Lying-in', 'Home', 'Others') | NOT NULL | 'Lying-in' | Place where delivery occurred. |
| `delivery_type` | ENUM('Normal Spontaneous Delivery (NSD)', 'Caesarean Section (CS)', 'Others') | NOT NULL | 'Normal Spontaneous Delivery (NSD)' | Method of delivery. |
| `attended_by` | ENUM('Doctor', 'Nurse', 'Midwife', 'Hilot/TBA', 'Others') | NOT NULL | 'Midwife' | Person who delivered the baby. |
| `newborn_screening_done` | BOOLEAN | NOT NULL | FALSE | Whether newborn screening test was performed (TRUE/FALSE). |
| `newborn_screening_date` | DATE | | NULL | Date newborn screening was conducted. |
| `newborn_screening_result` | VARCHAR(100) | | NULL | Screening result (Normal, Elevated, Pending). |
| `mother_cpab_tt` | VARCHAR(100) | | NULL | Child Protected at Birth (CPAB) status / Mother's TT doses. |
| `feeding_method` | ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') | NOT NULL | 'LAM / Exclusive Breastfeeding' | Primary feeding method. |
| `created_by` | INT | FK (`users.id`), NOT NULL | | Staff member who registered the infant. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Registration timestamp. |
| `updated_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP* | Modification timestamp. |

---

### 3.18 `child_growth_logs` Table
Tracks periodic pediatric growth checkups, anthropometrics, feeding changes, and EPI immunization entries for children 0-5 years.

| Column Name | Data Type | Constraints | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | | Unique growth monitoring entry identifier. |
| `wellbaby_id` | INT | FK (`wellbaby_records.id`) ON DELETE CASCADE | | Linked child health record. |
| `log_date` | DATE | NOT NULL | | Date of clinic checkup. |
| `age_months` | DECIMAL(4,1) | NOT NULL | | Exact age in months at visit. |
| `weight_kg` | DECIMAL(5,2) | NOT NULL | | Current weight in kilograms. |
| `height_cm` | DECIMAL(5,2) | NOT NULL | | Current length/height in centimeters. |
| `head_circumference_cm` | DECIMAL(4,1) | | NULL | Head circumference in centimeters. |
| `chest_circumference_cm`| DECIMAL(4,1) | | NULL | Chest circumference in centimeters. |
| `temperature` | DECIMAL(4,2) | | NULL | Body temperature in Celsius. |
| `feeding_method` | ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') | NOT NULL | 'LAM / Exclusive Breastfeeding' | Current feeding practice. |
| `vaccines_administered` | VARCHAR(255) | | NULL | Vaccines administered on this visit date (e.g., 'Penta 1, OPV 1, Rota 1'). |
| `vitamin_a_dose` | BOOLEAN | | FALSE | Whether Vitamin A supplement was administered on this date. |
| `deworming_dose` | BOOLEAN | | FALSE | Whether Deworming tablet was given on this date. |
| `tcb_notes` | TEXT | | NULL | Target Client Benefit notes, developmental milestones, remarks. |
| `recorded_by` | INT | FK (`users.id`), NOT NULL | | Attending nurse, midwife, or BHW. |
| `created_at` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Creation timestamp. |

---

## 4. DDL Database Schema Script

The complete schema script can be executed directly in MySQL or PHPMyAdmin to generate the database and all 18 tables.
*(Note: For instant 1-click deployment with default accounts included, use [`database/complete_setup.sql`](../database/complete_setup.sql) or run `setup_db.bat`)*.

```sql
-- -------------------------------------------------------------
-- Barangay Sinalhan Health Center - Patient Management System
-- Schema Initialization Script
-- -------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `sinalhan_hc_system` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `sinalhan_hc_system`;

-- Disable Foreign Key Checks temporarily to prevent drop order conflicts
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `child_growth_logs`;
DROP TABLE IF EXISTS `wellbaby_records`;
DROP TABLE IF EXISTS `past_obstetric_histories`;
DROP TABLE IF EXISTS `prenatal_visits`;
DROP TABLE IF EXISTS `prenatal_records`;
DROP TABLE IF EXISTS `patient_medical_histories`;
DROP TABLE IF EXISTS `child_health_records`;
DROP TABLE IF EXISTS `maternal_records`;
DROP TABLE IF EXISTS `lab_results`;
DROP TABLE IF EXISTS `lab_requests`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `immunizations`;
DROP TABLE IF EXISTS `prescriptions`;
DROP TABLE IF EXISTS `queue_entries`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `consultations`;
DROP TABLE IF EXISTS `vital_signs`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `contact_no` VARCHAR(20) DEFAULT NULL,
  `job_title` VARCHAR(50) DEFAULT NULL,
  `employee_id` VARCHAR(50) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `failed_attempts` INT NOT NULL DEFAULT 0,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_failed_login_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Patients Table
CREATE TABLE `patients` (
  `id` INT AUTO_INCREMENT,
  `patient_no` VARCHAR(20) NOT NULL,
  `family_no` VARCHAR(50) DEFAULT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `suffix` VARCHAR(20) DEFAULT NULL,
  `dob` DATE NOT NULL,
  `sex` ENUM('Male', 'Female') NOT NULL,
  `civil_status` ENUM('Single', 'Married', 'Widow/Widower', 'Annulled', 'Separated', 'Others') NOT NULL,
  `civil_status_other` VARCHAR(100) DEFAULT NULL,
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown') NOT NULL DEFAULT 'Unknown',
  `religion` VARCHAR(100) DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `education_attainment` ENUM('No Schooling', 'Elementary', 'High School', 'Vocational', 'College degree, post graduate') DEFAULT NULL,
  `contact_no` VARCHAR(20) DEFAULT NULL,
  `barangay` VARCHAR(100) NOT NULL DEFAULT 'Sinalhan',
  `address` TEXT NOT NULL,
  `phic_status` ENUM('Member', 'Dependent', 'Non-Member') NOT NULL DEFAULT 'Non-Member',
  `phic_type` VARCHAR(100) DEFAULT NULL,
  `philhealth_no` VARCHAR(20) DEFAULT NULL,
  `father_name` VARCHAR(150) DEFAULT NULL,
  `father_dob` DATE DEFAULT NULL,
  `mother_name` VARCHAR(150) DEFAULT NULL,
  `mother_dob` DATE DEFAULT NULL,
  `spouse_name` VARCHAR(150) DEFAULT NULL,
  `spouse_dob` DATE DEFAULT NULL,
  `emergency_name` VARCHAR(100) DEFAULT NULL,
  `emergency_relationship` VARCHAR(50) DEFAULT NULL,
  `emergency_no` VARCHAR(20) DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_patient_no` (`patient_no`),
  UNIQUE KEY `idx_philhealth` (`philhealth_no`),
  INDEX `idx_patients_family_no` (`family_no`),
  CONSTRAINT `fk_patients_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patients_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patients_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Vital Signs Table
CREATE TABLE `vital_signs` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `bp_systolic` INT DEFAULT NULL,
  `bp_diastolic` INT DEFAULT NULL,
  `heart_rate` INT DEFAULT NULL,
  `respiratory_rate` INT DEFAULT NULL,
  `temperature` DECIMAL(4,2) DEFAULT NULL,
  `weight` DECIMAL(5,2) DEFAULT NULL,
  `height` DECIMAL(5,2) DEFAULT NULL,
  `bmi` DECIMAL(4,2) DEFAULT NULL,
  `waist_circumference` DECIMAL(5,2) DEFAULT NULL,
  `oxygen_saturation` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `recorded_by` INT NOT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_vitals_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_vitals_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Consultations Table
CREATE TABLE `consultations` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `vital_signs_id` INT DEFAULT NULL,
  `subjective` TEXT NOT NULL,
  `objective` TEXT NOT NULL,
  `assessment` TEXT NOT NULL,
  `plan` TEXT NOT NULL,
  `status` ENUM('Open', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Completed',
  `consulted_by` INT NOT NULL,
  `consulted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT NOT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_consultation_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_vitals` FOREIGN KEY (`vital_signs_id`) REFERENCES `vital_signs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_consulted_by` FOREIGN KEY (`consulted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Appointments Table
CREATE TABLE `appointments` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `program_type` ENUM('General OPD', 'Prenatal Care', 'Well Baby Immunization', 'Senior Care') NOT NULL DEFAULT 'General OPD',
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `status` ENUM('Scheduled', 'Completed', 'Cancelled', 'Missed') NOT NULL DEFAULT 'Scheduled',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_appointment_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Queue Entries Table
CREATE TABLE `queue_entries` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `service_type` ENUM('General OPD', 'Prenatal Care', 'Well Baby Immunization', 'Senior Care') NOT NULL DEFAULT 'General OPD',
  `queue_date` DATE NOT NULL,
  `queue_no` INT NOT NULL,
  `status` ENUM('Waiting', 'Called', 'Serving', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Waiting',
  `time_in` TIME NOT NULL,
  `time_called` TIME DEFAULT NULL,
  `time_completed` TIME DEFAULT NULL,
  `created_by` INT NOT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_queue_date_no` (`queue_date`, `queue_no`),
  CONSTRAINT `fk_queue_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_queue_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_queue_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Prescriptions Table
CREATE TABLE `prescriptions` (
  `id` INT AUTO_INCREMENT,
  `consultation_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `medicine_name` VARCHAR(150) NOT NULL,
  `dosage` VARCHAR(50) NOT NULL,
  `frequency` VARCHAR(50) NOT NULL,
  `duration` VARCHAR(50) NOT NULL,
  `instructions` TEXT DEFAULT NULL,
  `prescribed_by` INT NOT NULL,
  `prescribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_prescription_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_prescribed_by` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Immunizations Table
CREATE TABLE `immunizations` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `vaccine_name` VARCHAR(100) NOT NULL,
  `dose_number` INT NOT NULL DEFAULT 1,
  `administered_date` DATE NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `administered_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_immunization_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_immunization_administered_by` FOREIGN KEY (`administered_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Patient Medical Histories (Annex A1 IHP Checklist)
CREATE TABLE `patient_medical_histories` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL UNIQUE,
  `past_medical_history` TEXT NULL COMMENT 'JSON/Text structured checklist of chronic illnesses',
  `surgical_history` TEXT NULL COMMENT 'JSON/Text structured past operations and dates',
  `family_history` TEXT NULL COMMENT 'JSON/Text structured family hereditary illnesses',
  `smoking_status` ENUM('Never', 'Yes', 'Quit') NOT NULL DEFAULT 'Never',
  `smoking_pack_years` DECIMAL(4,1) NULL,
  `alcohol_status` ENUM('Never', 'Yes', 'Quit') NOT NULL DEFAULT 'Never',
  `alcohol_bottles_per_day` DECIMAL(4,1) NULL,
  `illicit_drugs` TINYINT(1) NOT NULL DEFAULT 0,
  `menarche_age` INT NULL,
  `sexual_onset_age` INT NULL,
  `lmp` DATE NULL,
  `period_duration_days` INT NULL,
  `cycle_interval_days` INT NULL,
  `pads_per_day` INT NULL,
  `is_menopausal` TINYINT(1) NOT NULL DEFAULT 0,
  `menopause_age` INT NULL,
  `birth_control_method` VARCHAR(100) NULL,
  `baseline_bp_systolic` INT NULL,
  `baseline_bp_diastolic` INT NULL,
  `baseline_heart_rate` INT NULL,
  `baseline_respiratory_rate` INT NULL,
  `baseline_height` DECIMAL(5,2) NULL,
  `baseline_weight` DECIMAL(5,2) NULL,
  `baseline_waist_circumference` DECIMAL(5,2) NULL,
  `gravida` INT NULL,
  `para` INT NULL,
  `delivery_type` VARCHAR(100) NULL,
  `term_births` INT NULL,
  `preterm_births` INT NULL,
  `abortions` INT NULL,
  `living_children` INT NULL,
  `pre_eclampsia` TINYINT(1) NULL,
  `fp_counselling` TINYINT(1) NULL,
  `physical_examination` LONGTEXT NULL COMMENT 'JSON structured 6-system physical examination findings',
  `external_immunizations` LONGTEXT NULL COMMENT 'JSON structured Annex A1 lifetime immunization checklist',
  `updated_by` INT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pmh_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pmh_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_pmh_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Prenatal Records (Maternal Pregnancy Episodes)
CREATE TABLE `prenatal_records` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `husband_name` VARCHAR(150) NULL,
  `gravida` INT NOT NULL DEFAULT 1,
  `para` INT NOT NULL DEFAULT 0,
  `term_births` INT NOT NULL DEFAULT 0,
  `preterm_births` INT NOT NULL DEFAULT 0,
  `abortions` INT NOT NULL DEFAULT 0,
  `living_children` INT NOT NULL DEFAULT 0,
  `lmp` DATE NOT NULL,
  `edc` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `pre_eclampsia` TINYINT(1) NOT NULL DEFAULT 0,
  `fp_counselling` TINYINT(1) NOT NULL DEFAULT 1,
  `delivery_date` DATE NULL,
  `delivery_outcome` ENUM('Live Birth', 'Stillbirth', 'Miscarriage', 'Ectopic', 'Other') NULL,
  `notes` TEXT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pr_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pr_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_pr_patient` (`patient_id`),
  INDEX `idx_pr_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Prenatal Visits (Serial Trimester Checkup Logs)
CREATE TABLE `prenatal_visits` (
  `id` INT AUTO_INCREMENT,
  `prenatal_id` INT NOT NULL,
  `visit_date` DATE NOT NULL,
  `chief_complaint` VARCHAR(255) NULL,
  `aog_weeks` DECIMAL(4,1) NOT NULL,
  `bp_systolic` INT NULL,
  `bp_diastolic` INT NULL,
  `weight_kg` DECIMAL(5,2) NULL,
  `height_cm` DECIMAL(5,2) NULL,
  `fetal_heart_tone` INT NULL COMMENT 'FHT in bpm',
  `fundal_height_cm` DECIMAL(4,1) NULL COMMENT 'Fundal height in cm',
  `fetal_presentation` ENUM('Cephalic', 'Breech', 'Transverse', 'Undetermined') NOT NULL DEFAULT 'Cephalic',
  `tcb` VARCHAR(100) NULL COMMENT 'Target Client Benefit / Tetanus status',
  `remarks` TEXT NULL,
  `attended_by` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pv_prenatal` FOREIGN KEY (`prenatal_id`) REFERENCES `prenatal_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pv_attendant` FOREIGN KEY (`attended_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_pv_prenatal` (`prenatal_id`),
  INDEX `idx_pv_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Past Obstetric Histories (Prior Delivery Matrix)
CREATE TABLE `past_obstetric_histories` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `gravida_no` INT NOT NULL,
  `delivery_type` ENUM('NSD', 'CS', 'Abortion', 'Other') NOT NULL DEFAULT 'NSD',
  `infant_sex` ENUM('Male', 'Female', 'Unknown') NOT NULL DEFAULT 'Unknown',
  `place_of_delivery` VARCHAR(150) NULL,
  `year_delivered` INT NULL,
  `attended_by` VARCHAR(100) NULL,
  `status` ENUM('Alive', 'Not Alive') NOT NULL DEFAULT 'Alive',
  `birth_date` DATE NULL,
  `tt_status` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_poh_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  INDEX `idx_poh_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Well Baby Records (Infant Birth Context & Child Health)
CREATE TABLE `wellbaby_records` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL UNIQUE,
  `mother_patient_id` INT NULL,
  `birth_time` TIME NULL,
  `birth_weight_kg` DECIMAL(4,2) NOT NULL,
  `birth_length_cm` DECIMAL(4,1) NOT NULL,
  `place_of_delivery` ENUM('Hospital', 'Lying-in', 'Home', 'Others') NOT NULL DEFAULT 'Lying-in',
  `delivery_type` ENUM('Normal Spontaneous Delivery (NSD)', 'Caesarean Section (CS)', 'Others') NOT NULL DEFAULT 'Normal Spontaneous Delivery (NSD)',
  `attended_by` ENUM('Doctor', 'Nurse', 'Midwife', 'Hilot/TBA', 'Others') NOT NULL DEFAULT 'Midwife',
  `newborn_screening_done` TINYINT(1) NOT NULL DEFAULT 0,
  `newborn_screening_date` DATE NULL,
  `newborn_screening_result` VARCHAR(100) NULL,
  `mother_cpab_tt` VARCHAR(100) NULL,
  `feeding_method` ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding',
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_wb_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wb_mother` FOREIGN KEY (`mother_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_wb_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_wb_patient` (`patient_id`),
  INDEX `idx_wb_mother` (`mother_patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Child Growth Logs (Monthly Anthropometrics & EPI Immunizations)
CREATE TABLE `child_growth_logs` (
  `id` INT AUTO_INCREMENT,
  `wellbaby_id` INT NOT NULL,
  `log_date` DATE NOT NULL,
  `age_months` DECIMAL(4,1) NOT NULL,
  `weight_kg` DECIMAL(5,2) NOT NULL,
  `height_cm` DECIMAL(5,2) NOT NULL,
  `head_circumference_cm` DECIMAL(4,1) NULL,
  `chest_circumference_cm` DECIMAL(4,1) NULL,
  `temperature` DECIMAL(4,2) NULL,
  `feeding_method` ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding',
  `vaccines_administered` VARCHAR(255) NULL,
  `vitamin_a_dose` TINYINT(1) NOT NULL DEFAULT 0,
  `deworming_dose` TINYINT(1) NOT NULL DEFAULT 0,
  `tcb_notes` TEXT NULL,
  `recorded_by` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cgl_wellbaby` FOREIGN KEY (`wellbaby_id`) REFERENCES `wellbaby_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cgl_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_cgl_wellbaby` (`wellbaby_id`),
  INDEX `idx_cgl_log_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Lab Requests Table
CREATE TABLE `lab_requests` (
  `id` INT AUTO_INCREMENT,
  `consultation_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `test_name` VARCHAR(100) NOT NULL,
  `status` ENUM('Pending', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` TEXT DEFAULT NULL,
  `requested_by` INT NOT NULL,
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lab_request_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_request_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_request_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Lab Results Table
CREATE TABLE `lab_results` (
  `id` INT AUTO_INCREMENT,
  `lab_request_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `result_details` TEXT NOT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `recorded_by` INT NOT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lab_result_request` FOREIGN KEY (`lab_request_id`) REFERENCES `lab_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_result_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_result_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Settings Table
CREATE TABLE `settings` (
  `setting_key` VARCHAR(50) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`),
  CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Audit Logs Table
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `username` VARCHAR(50) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Secondary Indexing for Performance Optimization
-- -------------------------------------------------------------

-- Patients search indexes
CREATE INDEX `idx_patients_search` ON `patients` (`last_name`, `first_name`);
CREATE INDEX `idx_patients_dob` ON `patients` (`dob`);
CREATE INDEX `idx_patients_sex` ON `patients` (`sex`);
CREATE INDEX `idx_patients_barangay` ON `patients` (`barangay`);
CREATE INDEX `idx_patients_archived` ON `patients` (`deleted_at`);

-- Vital Signs indexes
CREATE INDEX `idx_vitals_patient_date` ON `vital_signs` (`patient_id`, `recorded_at`);

-- Consultations indexes
CREATE INDEX `idx_consultation_patient_date` ON `consultations` (`patient_id`, `consulted_at`);
CREATE INDEX `idx_consultation_deleted` ON `consultations` (`deleted_at`);

-- Appointments indexes
CREATE INDEX `idx_appointment_date_time` ON `appointments` (`appointment_date`, `appointment_time`);
CREATE INDEX `idx_appointment_status` ON `appointments` (`status`);
CREATE INDEX `idx_appointment_patient_date` ON `appointments` (`patient_id`, `appointment_date`);

-- Queue indexes
CREATE INDEX `idx_queue_daily` ON `queue_entries` (`queue_date`, `queue_no`);
CREATE INDEX `idx_queue_status_date` ON `queue_entries` (`queue_date`, `status`);

-- Immunization tracking index
CREATE INDEX `idx_immunization_lookup` ON `immunizations` (`patient_id`, `vaccine_name`);

-- Audit logs search indexes
CREATE INDEX `idx_audit_logs_search` ON `audit_logs` (`created_at`, `module`, `action`);
```

---

## 5. DML Seed Data Script

The following script populates core administrative defaults, including default roles and basic application settings:

```sql
-- -------------------------------------------------------------
-- Barangay Sinalhan Health Center - Default Seeding Script
-- -------------------------------------------------------------

USE `sinalhan_hc_system`;

-- 1. Insert Default Admins and Staff
-- Note: Default passwords are bcrypt hashes of 'admin1234' and 'staff1234'
-- ALWAYS force password changes on first authentication!
INSERT INTO `users` (`username`, `password_hash`, `role`, `first_name`, `last_name`, `job_title`, `status`, `must_change_password`)
VALUES 
(
  'admin', 
  '$2y$10$Mp8OmLNojA8jzqN.PPLyBOUmHAuRNkqD0X6XCmDGB/4LfLJEc75rm', -- bcrypt hash for: admin1234
  'admin', 
  'System', 
  'Administrator', 
  'IT Support / Records Head', 
  'active',
  1
),
(
  'records_staff', 
  '$2y$10$MQ1JTmlMiH24jODpgfuA5OHI9tH96t1pvwsAXm58YbcGWXOn.T5ba', -- bcrypt hash for: staff1234
  'staff', 
  'Maria', 
  'Santos', 
  'Barangay Health Worker (BHW)', 
  'active',
  1
),
(
  'midwife_user', 
  '$2y$10$MQ1JTmlMiH24jODpgfuA5OHI9tH96t1pvwsAXm58YbcGWXOn.T5ba', -- bcrypt hash for: staff1234
  'staff', 
  'Juana', 
  'Dela Cruz', 
  'Midwife', 
  'active',
  1
);

-- 2. Insert Default Application Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_by`)
VALUES
('clinic_name', 'Barangay Sinalhan Health Center', 'The name of the healthcare center displayed on the system header.', 1),
('clinic_address', 'Barangay Sinalhan, Santa Rosa City, Laguna, Philippines', 'Physical address of the clinic used in printed report headers.', 1),
('queue_alert_sound', 'enabled', 'Plays a chime sound when a new queue number is called (enabled/disabled).', 1),
('public_queue_display_names', 'initials', 'Privacy option for queue display board: initials, none (number only), or full (show names).', 1),
('backup_interval_days', '7', 'Database reminder interval for admins to trigger a SQL backup.', 1);
```

---

## 6. Key Operations & Optimization Queries

To assist database engine performance and simplify reporting queries, use these pre-optimized SQL templates:

### 6.1 Patient Daily Queue Generation (Atomic Increment)
To calculate the daily queue number reliably during concurrent operations:
```sql
-- Calculate next sequence number for the current day
SELECT IFNULL(MAX(queue_no), 0) + 1 AS next_queue_no 
FROM queue_entries 
WHERE queue_date = CURRENT_DATE();
```

### 6.2 Today's Queue Board (Public Display Board)
For the public dashboard display monitor to show current queue numbers safely (without showing full patient names if configured for privacy):
```sql
SELECT 
  qe.queue_no,
  qe.status,
  qe.time_in,
  CASE 
    WHEN (SELECT setting_value FROM settings WHERE setting_key = 'public_queue_display_names') = 'full' 
      THEN CONCAT(p.last_name, ', ', p.first_name)
    WHEN (SELECT setting_value FROM settings WHERE setting_key = 'public_queue_display_names') = 'initials' 
      THEN CONCAT(SUBSTRING(p.first_name, 1, 1), '. ', SUBSTRING(p.last_name, 1, 1), '.')
    ELSE '***'
  END AS patient_display_name
FROM queue_entries qe
JOIN patients p ON qe.patient_id = p.id
WHERE qe.queue_date = CURRENT_DATE()
  AND qe.status IN ('Waiting', 'Called', 'Serving')
ORDER BY 
  CASE qe.status
    WHEN 'Serving' THEN 1
    WHEN 'Called' THEN 2
    WHEN 'Waiting' THEN 3
    ELSE 4
  END ASC, 
  qe.queue_no ASC;
```

### 6.3 Daily Visitor Traffic Report (Grouping by Hour)
Generates aggregate counts for reporting trends on when the clinic is busiest:
```sql
SELECT 
  HOUR(qe.time_in) AS hour_of_day,
  COUNT(qe.id) AS total_patients
FROM queue_entries qe
WHERE qe.queue_date BETWEEN :start_date AND :end_date
  AND qe.status = 'Completed'
GROUP BY HOUR(qe.time_in)
ORDER BY hour_of_day ASC;
```

### 6.4 Patient Medical History Log
Aggregates consultations and vital signs histories chronologically on a single query list:
```sql
SELECT 
  'Consultation' AS record_type,
  c.id AS record_id,
  c.consulted_at AS record_date,
  u.first_name AS staff_first,
  u.last_name AS staff_last,
  c.assessment AS summary_details
FROM consultations c
JOIN users u ON c.consulted_by = u.id
WHERE c.patient_id = :patient_id AND c.deleted_at IS NULL

UNION ALL

SELECT 
  'Vital Signs' AS record_type,
  vs.id AS record_id,
  vs.recorded_at AS record_date,
  u.first_name AS staff_first,
  u.last_name AS staff_last,
  CONCAT('BP: ', vs.bp_systolic, '/', vs.bp_diastolic, ' | Temp: ', vs.temperature, ' Celsius | BMI: ', IFNULL(vs.bmi, 'N/A')) AS summary_details
FROM vital_signs vs
JOIN users u ON vs.recorded_by = u.id
WHERE vs.patient_id = :patient_id

ORDER BY record_date DESC;
```
