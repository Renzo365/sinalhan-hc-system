# Comprehensive Analysis & System Design: Health Center Patient Records

This document serves as the foundational design reference and source of truth for digitizing the three physical paper records of Barangay Sinalhan Health Center (CHO Santa Rosa I):
1. `docs/records/individual_health_profile.md` (PhilHealth Annex A1 IHP)
2. `docs/records/prenatal_record.md` (CHO I Pre-Natal Record)
3. `docs/records/wellbaby_record.md` (Brgy. Ibaba Well Baby Record)

---

## 1. Clinical Records Analysis

### 1.1 Record Breakdown
* **Individual Health Profile (IHP)**: Master baseline record for all registered citizens. Contains demographics, PhilHealth categorization, immediate family identifiers, complete medical/surgical history, hereditary diseases, habits, and female reproductive history.
* **Pre-Natal Record**: Program-specific tracking for pregnant women. Tracks gestational age (AOG), due date (EDC via Naegele's rule), GTPAL obstetric score, past deliveries matrix, and serial trimester follow-up checkup logs (FHT, Fundal Height, Presentation, TCB).
* **Well Baby Record**: Program-specific tracking for infants (0–5 years). Tracks birth context (birth weight/length, time, delivery mode, attendant, newborn screening), maternal CPAB status, DOH Routine EPI Immunization schedule, Vitamin A/Deworming supplementation, and serial growth monitoring (head/chest circumference, feeding method).

### 1.2 Core Entity Architecture
* **Single Master Patient**: Every person (newborn, pregnant mother, senior citizen) receives one permanent Master Patient Record (`patients` table, `P-YYYY-XXXXX`).
* **Household Clustering**: The `family_no` groups household members under a single family folder.
* **1:1 vs 1:N Relationships**:
  * `patients` (1) <-> (1) `patient_medical_histories` (IHP Medical History)
  * `patients` (1) <-> (N) `prenatal_records` (Pregnancy Episodes)
  * `prenatal_records` (1) <-> (N) `prenatal_visits` (Serial Follow-up Visits)
  * `patients` (1) <-> (N) `past_obstetric_histories` (Prior Deliveries)
  * `patients` (1) <-> (1) `wellbaby_records` (Birth Context & Mother Link)
  * `wellbaby_records` (1) <-> (N) `child_growth_logs` (Monthly Anthropometrics & Vaccines)

---

## 2. Two-Stage Intake & Clutter-Free UI Architecture

### Stage 1: Fast Front-Desk Registration (60–90 seconds)
Captures core demographics only:
1. Personal Identity (Name, Suffix, DOB, Sex, Civil Status, Blood Type)
2. Household & Address (Family Number, Contact No, Barangay, Street Address)
3. PhilHealth & Socioeconomic (Status, Category, PIN, Occupation, Education, Religion)
4. Immediate Family & Emergency Contact (Father, Mother, Spouse, Emergency Person, Relationship, Emergency No)

### Stage 2: Progressive Profile Workstation (`/patients/{id}`)
* **Persistent Master Header**: Patient ID, Family No, Name, Age/Sex, Blood Type, PhilHealth PIN, Contact, Emergency Person, and **Critical Safety Badges** (Allergies, Pre-Eclampsia risk).
* **Action Toolbar**: Quick action buttons for queueing, vitals, consults, edit, and print.
* **Modular Tabs**:
  * Tab 1: Overview & Clinical Snapshot
  * Tab 2: Annex A1 IHP Medical History
  * Tab 3: Consultations (SOAP Notes)
  * Tab 4: Vital Signs History & BMI Trends
  * Tab 5: Universal Immunization History
  * Tab 6: Maternal & Prenatal Care Workstation (Active Pregnancy, GTPAL, Serial Visits)
  * Tab 7: Well Baby & Pediatric Growth Workstation (Birth History, EPI Tracker, Growth Logs)
  * Tab 8: Appointments & Queue History
