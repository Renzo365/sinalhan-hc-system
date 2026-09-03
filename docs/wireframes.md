# UI Wireframes - Barangay Sinalhan Health Center Patient Management System

> Low-fidelity wireframes for the capstone system. These wireframes define screen structure, navigation, and workflow before detailed UI implementation.

---

## 1. Wireframe Goals

The wireframes should help guide development of the Bootstrap 5 interface. They focus on:

- Main layout and navigation
- Staff workflow
- Patient lookup and record handling
- Appointment, queue, vital signs, and consultation flow
- Admin-only pages such as archived records, audit logs, and backup
- Privacy-aware display of patient information

These are low-fidelity wireframes. Colors, spacing, icons, and final visual styling will follow `CODEX.md`.

---

## 2. Shared App Layout

Most authenticated pages should use the same layout.

```text
+--------------------------------------------------------------------------------+
| Topbar: Page Title / Search / Logged-in User / Logout                           |
+----------------------+---------------------------------------------------------+
| Sidebar              | Main Content Area                                       |
|                      |                                                         |
| Dashboard            | Breadcrumbs / Page Header                               |
| Patients             |                                                         |
| Appointments         | Page-specific filters, actions, forms, tables, cards    |
| Queue                |                                                         |
| Vital Signs          |                                                         |
| Consultations        |                                                         |
| Reports              |                                                         |
| Archive (Admin)      |                                                         |
| Audit Logs (Admin)   |                                                         |
| Backup (Admin)       |                                                         |
| Settings (Admin)     |                                                         |
+----------------------+---------------------------------------------------------+
```

Layout notes:

- Sidebar should remain stable across pages.
- Admin-only items should be hidden from staff users.
- The topbar may include a quick patient search if implementation time allows.
- Main content should prioritize tables, forms, and readable clinical information.

---

## 3. Login Page

```text
+--------------------------------------------------------------------------------+
| Login Gradient Background                                                       |
|                                                                                |
|                         +--------------------------------+                     |
|                         | Health Center Name             |                     |
|                         | Patient Management System      |                     |
|                         |--------------------------------|                     |
|                         | Username                       |                     |
|                         | [____________________________] |                     |
|                         | Password                       |                     |
|                         | [____________________________] |                     |
|                         |                                |                     |
|                         | [ Sign In ]                    |                     |
|                         |                                |                     |
|                         | Error / validation message     |                     |
|                         +--------------------------------+                     |
|                                                                                |
+--------------------------------------------------------------------------------+
```

Key behavior:

- Show validation errors clearly.
- Do not expose whether username or password is specifically wrong.
- Redirect logged-in users to dashboard.

---

## 4. Dashboard

```text
+--------------------------------------------------------------------------------+
| Dashboard                                                    Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Dashboard                                               |
|                      |                                                         |
|                      | +------------+ +------------+ +------------+ +----------+ |
|                      | | Patients   | | Today Appt | | Queue Now  | | Visits   | |
|                      | | 1,245      | | 12         | | 8          | | 31       | |
|                      | +------------+ +------------+ +------------+ +----------+ |
|                      |                                                         |
|                      | +----------------------------+ +------------------------+ |
|                      | | Today's Appointments       | | Queue Summary          | |
|                      | | Table/List                 | | Chart/List             | |
|                      | +----------------------------+ +------------------------+ |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Recent Activity / Recent Consultations                | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Dashboard notes:

- Keep the dashboard operational, not decorative.
- Staff should quickly see today's appointments, queue count, and patient activity.
- Admin may see additional summary cards such as user activity or backups.

---

## 5. Patient List and Search

```text
+------------------------------------------------------------------------------------------------------------------------+
| Patients Directory & Household Lookup                                                              Staff Name | Logout |
+----------------------+-------------------------------------------------------------------------------------------------+
| Sidebar              | Patients Directory                                                                              |
|                      |                                                                                                 |
| Dashboard            | [ Search by Name, Patient ID (P-2026-XXXXX), Family No (FAM-XXXX), PhilHealth, Phone ]          |
| Patients (Active)    | [ Program: All Programs v ] [ Age Group: All Ages v ] [ Sex: All v ] [ Brgy: Sinalhan v ] [ Search ]  |
| Appointments         |                                                                                                 |
| Queue                | + Actions: [ + Register New Patient (Stage 1 Intake) ] [ Export CSV ] [ Print Active List ]      |
| Vital Signs          |                                                                                                 |
| Consultations        | +---------------------------------------------------------------------------------------------+ |
| Reports              | | Family No  | Patient ID   | Full Name & Suffix     | Age/Sex | Program Tag   | PhilHealth | Action  | |
| Archive (Admin)      | |------------|--------------|------------------------|---------|---------------|------------|---------| |
| Audit Logs (Admin)   | | FAM-0428   | P-2026-00042 | Dela Cruz, Maria S.    | 26y / F | [ Prenatal ]  | Member     | [ View ]| |
| Backup (Admin)       | | FAM-0428   | P-2026-00043 | Dela Cruz, Juan M. Jr. | 2y / M  | [ Well Baby ] | Dependent  | [ View ]| |
| Settings (Admin)     | | FAM-0105   | P-2026-00015 | Santos, Roberto G.     | 68y / M | [ Senior ]    | Lifetime   | [ View ]| |
|                      | | FAM-0891   | P-2026-00088 | Alvarez, Elena B.      | 34y / F | [ OPD Gen ]   | Non-Member | [ View ]| |
|                      | +---------------------------------------------------------------------------------------------+ |
|                      | Showing 1 to 4 of 1,245 patients  |  [ < Prev ] [ 1 ] [ 2 ] [ 3 ] [ Next > ]                    |
+----------------------+-------------------------------------------------------------------------------------------------+
```

Key behavior:
- **Instant Household Clustering**: Searching a `Family No` lists all registered family members living in that household.
- **Visual Program Tags**: Direct badges indicate whether a patient is currently active in the **Prenatal**, **Well Baby**, or **Senior** program.
- **Search Duplicate Prevention**: Real-time debounce query matches existing patients before proceeding with new registration.

---

## 6. Fast Two-Stage Patient Registration Form (`/patients/create`)

> **UX Rationale**: Front-desk registration captures only core demographics (1–2 minutes) to prevent waiting room bottlenecks. Detailed medical histories and program-specific records are enriched inside the Patient Profile Workstation.

```text
+------------------------------------------------------------------------------------------------------------------------+
| Register New Patient (Stage 1: Core Intake)                                                        Staff Name | Logout |
+----------------------+-------------------------------------------------------------------------------------------------+
| Sidebar              | Register Patient (Basic Identity & Household Intake)                                            |
|                      |                                                                                                 |
|                      | [!] DUPLICATE WARNING BANNER: "Found matching patient: Dela Cruz, Maria (P-2025-00112). [View]" |
|                      |                                                                                                 |
|                      | 1. Personal Identity                                                                            |
|                      | +------------------------+ +------------------------+ +------------------------+ +-----------+  |
|                      | | First Name *           | | Middle Name            | | Last Name *            | | Suffix    |  |
|                      | | [ Maria              ] | | [ Santos             ] | | [ Dela Cruz          ] | | [ Jr./Sr] |  |
|                      | +------------------------+ +------------------------+ +------------------------+ +-----------+  |
|                      | | Date of Birth *        | | Biological Sex *       | | Civil Status *         | | Blood Type|  |
|                      | | [ 1999-08-14         ] | | (o) Female  ( ) Male   | | [ Married            v]| | [ O+    v]|  |
|                      | +------------------------+ +------------------------+ +------------------------+ +-----------+  |
|                      |                                                                                                 |
|                      | 2. Household & Address                                                                          |
|                      | +------------------------+ +------------------------+ +---------------------------------------+  |
|                      | | Family Number (CHO/PHIC| | Primary Contact No. *   | | Complete Street Address *             |  |
|                      | | [ FAM-0428           ] | | [ 09171234567        ] | | [ 123 Purok 4, Lakeside St.         ] |  |
|                      | +------------------------+ +------------------------+ +---------------------------------------+  |
|                      |                                                                                                 |
|                      | 3. PhilHealth & Socioeconomic Classification                                                    |
|                      | +------------------------+ +------------------------+ +---------------------------------------+  |
|                      | | PhilHealth Status *    | | PhilHealth Category    | | PhilHealth Identification No. (PIN)   |  |
|                      | | [ Member             v]| | [ Sponsored - NHTS   v]| | [ 12-345678901-2                      ] |  |
|                      | +------------------------+ +------------------------+ +---------------------------------------+  |
|                      | | Occupation             | | Educational Attainment | | Religion                              |  |
|                      | | [ Housewife          ] | | [ High School        v]| | [ Roman Catholic                    ] |  |
|                      | +------------------------+ +------------------------+ +---------------------------------------+  |
|                      |                                                                                                 |
|                      | 4. Immediate Family & Emergency Contact                                                          |
|                      | +-----------------------------------------------+ +-------------------------------------------+  |
|                      | | Father's Full Name: [ Juan Dela Cruz Sr.    ] | | Mother's Maiden Name: [ Ana Santos        ] |  |
|                      | | Spouse's Full Name: [ Pedro Dela Cruz       ] | | Emergency Person:     [ Pedro Dela Cruz   ] |  |
|                      | | Emergency Relation: [ Spouse                ] | | Emergency Contact No: [ 09187654321       ] |  |
|                      | +-----------------------------------------------+ +-------------------------------------------+  |
|                      |                                                                                                 |
|                      | [ Cancel ]                                         [ Save & Open Patient Profile Workstation ]  |
+----------------------+-------------------------------------------------------------------------------------------------+
```

---

## 7. Patient Profile Workstation (`/patients/{id}`)

> **Vertical Sidebar & Workstation Layout**: The Patient Profile features a dedicated vertical Left Sidebar card (`col-lg-3`) containing identity, demographics, contact details, emergency info, and record management actions. The Right Content Area (`col-lg-9`) provides the Clinical Care Workstation action toolbar and modular tabs.

```text
+------------------------------------------------------------------------------------------------------------------------+
| Patient Profile Workstation: P-2026-00042                                                          Staff Name | Logout |
+----------------------+-------------------------------------------------------------------------------------------------+
| Sidebar              | [!] CLINICAL SAFETY ALLERGY ALERT: Penicillin, Seafood Recorded                                 |
|                      |                                                                                                 |
| Dashboard            | +---------------------------+  +--------------------------------------------------------------+ |
| Patients             | | [Avatar: MD]              |  | Clinical Care Workstation    [+ Enqueue] [+ Vitals] [+ Consult] |
| Appointments         | | MARIA S. DELA CRUZ        |  | Manage checkups, IHP, maternal/child care, and appointments. |
| Queue                | | 26 y/o • Female           |  +--------------------------------------------------------------+ |
| Reports              | | [ Prenatal Care (29w) ]   |  | TABS: [Overview] [IHP History] [Consults (2)] [Vitals (3)]   | |
|                      | |                           |  |       [Immunizations] [Prenatal Care *] [Appointments]       | |
|                      | | • IDENTIFICATION          |  +--------------------------------------------------------------+ |
|                      | | Patient ID: P-2026-00042  |  | TAB 1: OVERVIEW & CLINICAL SNAPSHOT                          | |
|                      | | Birth Date: 1999-08-14    |  |                                                              | |
|                      | | Blood Type: O+            |  | +-- Latest Vital Signs (2026-09-01) -----------------------+ | |
|                      | | Civil: Married            |  | | BP: 138/88 mmHg  |  HR: 82 bpm  |  Temp: 36.7 °C         | | |
|                      | |                           |  | | Wt: 58.5 kg      |  Ht: 154 cm  |  BMI: 24.7 (Normal)    | | |
|                      | | • ADDRESS & CONTACT       |  | +----------------------------------------------------------+ | |
|                      | | Purok 4, Sinalhan         |  |                                                              | |
|                      | | 09171234567               |  | +-- Household Members (FAM-0428) -+ +-- IHP Health Summary --+ | |
|                      | |                           |  | | • Pedro Dela Cruz (Spouse, 28y) | | • Asthma, Allergies    | | |
|                      | | • PHILHEALTH              |  | | • Juan Dela Cruz Jr. (Son, 2y)  | | • Non-Smoker           | | |
|                      | | PIN: 12-345678901-2       |  | +---------------------------------+ +------------------------+ | |
|                      | | Category: Member (NHTS)   |  |                                                              | |
|                      | |                           |  +--------------------------------------------------------------+ |
|                      | | • ACTIONS                 |                                                                    |
|                      | | [ Edit Info ]             |                                                                    |
|                      | | [ Archive Patient ]       |                                                                    |
|                      | +---------------------------+                                                                    |
+----------------------+-------------------------------------------------------------------------------------------------+
```

---

## 7.1 Patient Profile - Tab 2: Medical History (Annex A1 IHP)

```text
+------------------------------------------------------------------------------------------------------------------------+
| TAB 2: INDIVIDUAL HEALTH PROFILE (ANNEX A1 MEDICAL HISTORY)                                     [ Edit Medical History ]|
+------------------------------------------------------------------------------------------------------------------------+
| 1. Past Medical Illnesses Checklist                                                                                    |
| [X] Allergy: [ Penicillin, Seafoods                  ]    [X] Asthma:       [ Maintenance: Salbutamol Inhaler    ] |
| [ ] Cancer:  [ _____________________________________ ]    [X] Hypertension: [ Highest Recorded BP: 150/95 mmHg   ] |
| [ ] Diabetes: Type [ _______________________________ ]    [ ] Tuberculosis: Category [ _________________________ ] |
| [ ] Kidney Disease  [ ] Heart Disease  [ ] Stroke  [ ] Epilepsy  [ ] Thyroid Disorder  [ ] Hepatitis                  |
|                                                                                                                        |
| 2. Surgical & Hospitalization History                                                                                  |
| • Operation 1: [ Appendectomy                             ]  Date / Year: [ 2019       ]  Hospital: [ Calamba Med    ] |
| • Operation 2: [ None                                     ]  Date / Year: [ __________ ]  Hospital: [ ______________ ] |
|                                                                                                                        |
| 3. Family Hereditary Disease History (Parents & Siblings)                                                              |
| [X] Hypertension (Mother's side)   [X] Diabetes Mellitus (Father's side)   [ ] Cancer   [ ] Asthma   [ ] Kidney Stones |
|                                                                                                                        |
| 4. Personal & Social Lifestyle Habits                                                                                  |
| • Tobacco Smoking: [ (o) Never Smoked   ( ) Former Smoker   ( ) Active Smoker: Pack-Years: [___] ]                     |
| • Alcohol Intake:  [ (o) Non-Drinker    ( ) Occasional      ( ) Regular Drinker: Bottles/Day: [___] ]                  |
| • Illicit Drug Use History: [ No ]                                                                                     |
|                                                                                                                        |
| 5. Female Reproductive & Menstrual History (Females Only)                                                              |
| • Menarche (Age at first period): [ 13 y/o ]        • Coitarche (Age at sexual onset): [ 21 y/o ]                      |
| • Typical Menstrual Duration:     [ 4-5 Days ]      • Typical Cycle Interval:          [ 28 Days regular ]             |
| • Sanitary Pads per Day:          [ 3-4 Pads/day ]  • Menopause:                       [ No ]                          |
| • Current Family Planning Method: [ None (Currently Pregnant) ]                                                        |
+------------------------------------------------------------------------------------------------------------------------+
```

---

## 7.2 Patient Profile - Tab 6: Maternal & Prenatal Care Workstation

> **Context-Aware Trigger**: Displayed prominently for female patients with an active pregnancy or obstetric history.

```text
+------------------------------------------------------------------------------------------------------------------------+
| TAB 6: MATERNAL & PRENATAL CLINICAL WORKSTATION                         [ + Log Prenatal Visit ] [ + New Pregnancy Ep ]|
+------------------------------------------------------------------------------------------------------------------------+
| ACTIVE PREGNANCY EPISODE #2 (STATUS: ACTIVE | HIGH-RISK MONITORING)                                                    |
| • Last Menstrual Period (LMP): [ 2026-02-10 ]           • Estimated Due Date (EDC / Naegele's): [ 2026-11-17 ]         |
| • Current Age of Gestation:    [ 29 Weeks, 2 Days ]     • Gestational Trimester:                [ 3rd Trimester ]      |
| • Obstetric GTPAL Score:       [ G:2  P:1  T:1  P:0  A:0  L:1 ] (Gravida 2, Para 1, 1 Living Child)                    |
| • Pre-Eclampsia Alert:         [ YES - Blood Pressure tracking required at every visit ]                               |
| • Husband / Partner Name:      [ Pedro Dela Cruz ]      • FP Counselling Received:              [ YES ]                |
+------------------------------------------------------------------------------------------------------------------------+
| PAST PREGNANCIES HISTORY MATRIX                                                                                        |
| +---+----------+---------+---------------+-------+---------------+---------------+--------+--------------------------+ |
| | G | DelivType| Sex     | Delivery Place| Year  | Attendant     | Child Status  | BirthWt| Maternal TT Doses Given  | |
| |---|----------|---------|---------------|-------|---------------|---------------|--------|--------------------------| |
| | 1 | NSD      | Male    | Sinalhan Lying| 2024  | Midwife Ramos | Alive (2 y/o) | 3.1 kg | TT1 (2023), TT2 (2024)   | |
| +---+----------+---------+---------------+-------+---------------+---------------+--------+--------------------------+ |
+------------------------------------------------------------------------------------------------------------------------+
| SERIAL PRENATAL VISITS LOG (RECURRING TRIMESTER VISITS)                                                                |
| +------------+-------+---------+--------+---------+--------+--------------+---------+------------------+-------------+ |
| | Visit Date | AOG   | BP mmHg | Weight | FHT bpm | FH cm  | Presentation | TCB/TT  | Clinical Remarks | Attended By | |
| |------------|-------|---------|--------|---------|--------|--------------|---------|------------------|-------------| |
| | 2026-08-15 | 26w4d | 130/85  | 58.5kg | 144 bpm | 25 cm  | Cephalic     | TT3 giv | Given FeSO4+FA   | MW. Ramos   | |
| | 2026-06-20 | 18w4d | 120/80  | 55.0kg | 148 bpm | 17 cm  | Cephalic     | Advised | Normal quickening| MW. Ramos   | |
| | 2026-04-12 | 08w5d | 118/78  | 52.2kg | Heard + | -      | -            | CBC/Urn | 1st Prenatal Check| Dr. Castro  | |
| +------------+-------+---------+--------+---------+--------+--------------+---------+------------------+-------------+ |
+------------------------------------------------------------------------------------------------------------------------+
```

---

## 7.3 Patient Profile - Tab 7: Well Baby & Pediatric Growth Workstation

> **Context-Aware Trigger**: Displayed for pediatric patients aged 0–5 years.

```text
+------------------------------------------------------------------------------------------------------------------------+
| TAB 7: WELL BABY & CHILD HEALTH WORKSTATION (AGES 0-5)                 [ + Record Growth Visit ] [ Print Immuni Card ]|
+------------------------------------------------------------------------------------------------------------------------+
| INFANT BIRTH RECORD: DELA CRUZ, JUAN JR. (AGE: 2 Years 1 Month | MALE)                                                 |
| • Date & Time of Birth:  [ 2024-07-28 @ 04:15 AM ]      • Mother:    [ Dela Cruz, Maria (P-2026-00042) ]               |
| • Birth Weight & Length: [ 3.10 kg / 49.0 cm ]          • Father:    [ Dela Cruz, Pedro ]                              |
| • Place of Delivery:     [ Sinalhan Lying-in Clinic ]   • Attendant: [ Midwife Elena Ramos, RM ]                       |
| • Delivery Mode:         [ Normal Spontaneous (NSD) ]   • Mother CPAB TT Status: [ Protected at Birth (TT2 active) ]   |
| • Newborn Screening:     [ YES - Done on 2024-07-30 | Result: NORMAL Certificate #NBS-2024-9811 ]                      |
+------------------------------------------------------------------------------------------------------------------------+
| DOH MANDATORY ROUTINE EPI IMMUNIZATION MATRIX                                                                          |
| +-------------------------+-----------------+--------------------+---------------------------------------------------+ |
| | Vaccine Name            | Target Age      | Date Administered  | Status / Health Worker Signature                  | |
| |-------------------------|-----------------|--------------------|---------------------------------------------------| |
| | BCG (Tuberculosis)      | At Birth        | 2024-07-28         | [ COMPLETED ] - MW. Ramos                         | |
| | Hepatitis B             | At Birth (<24h) | 2024-07-28         | [ COMPLETED ] - MW. Ramos                         | |
| | Pentavalent 1 / OPV 1   | 1 1/2 Months    | 2024-09-10         | [ COMPLETED ] - BHW Santos                        | |
| | Pentavalent 2 / OPV 2   | 2 1/2 Months    | 2024-10-15         | [ COMPLETED ] - BHW Santos                        | |
| | Pentavalent 3 / OPV 3   | 3 1/2 Months    | 2024-11-20         | [ COMPLETED ] - BHW Santos                        | |
| | IPV (Inactivated Polio) | 3 1/2 Months    | 2024-11-20         | [ COMPLETED ] - BHW Santos                        | |
| | Rotavirus 1 & 2         | 1 1/2 & 2 1/2 M | 2024-09-10, 10-15  | [ COMPLETED ] - MW. Ramos                         | |
| | MCV 1 (Anti-Measles)    | 9 Months        | 2025-04-30         | [ COMPLETED ] - MW. Ramos                         | |
| | MCV 2 (MMR Booster)     | 12 Months       | 2025-07-30         | [ COMPLETED ] - MW. Ramos                         | |
| +-------------------------+-----------------+--------------------+---------------------------------------------------+ |
| SEMI-ANNUAL SUPPLEMENTATION:  Vitamin A: [ 2025-01-15, 2025-07-20 ]   |   Deworming: [ 2025-07-20, 2026-02-10 ]       |
+------------------------------------------------------------------------------------------------------------------------+
| PERIODIC PEDIATRIC GROWTH MONITORING LOG                                                                               |
| +------------+-----------+----------+-----------+------------+------------+-------+--------------------+-----------------+ |
| | Date       | Age (Mos) | Weight   | Height cm | Head Circ  | Chest Circ | Temp  | Feeding Method     | TCB / Milestones| |
| |------------|-----------|----------|-----------|------------|------------|-------|--------------------|-----------------| |
| | 2026-08-01 | 24.0 Mos  | 12.2 kg  | 86.5 cm   | 48.0 cm    | 49.0 cm    | 36.5C | Solid + Cup Milk   | Walking, Talking| |
| | 2025-07-30 | 12.0 Mos  | 9.8 kg   | 75.0 cm   | 46.0 cm    | 46.5 cm    | 36.6C | Weaned / Mixed     | Standing alone  | |
| | 2024-09-10 | 1.5 Mos   | 4.4 kg   | 54.0 cm   | 37.5 cm    | 37.0 cm    | 36.7C | Exclusive Breast/LAM| Responsive gaze | |
| +------------+-----------+----------+-----------+------------+------------+-------+--------------------+-----------------+ |
+------------------------------------------------------------------------------------------------------------------------+
```

---

## 8. Appointment List (`/appointments`)

```text
+--------------------------------------------------------------------------------+
| Appointments                                                 Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Appointments                                            |
|                      |                                                         |
|                      | [Date From] [Date To] [Status v] [ Search ] [ + New ]   |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Date | Time | Patient | Purpose | Status | Actions    | |
|                      | |------|------|---------|---------|--------|------------| |
|                      | | ...  | ...  | ...     | ...     | Badge  | View/Edit  | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Status values:

- Scheduled
- Completed
- Cancelled
- Missed

---

## 9. Appointment Form

```text
+--------------------------------------------------------------------------------+
| New Appointment                                             Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Schedule Appointment                                    |
|                      |                                                         |
|                      | Patient                                                 |
|                      | [ Search or select patient ]                             |
|                      |                                                         |
|                      | Appointment Details                                      |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Date           | | Time           | | Status        | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Purpose                                                   |
|                      | +--------------------------------------------------------+ |
|                      | | Notes                                                     |
|                      | +--------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Cancel ]                           [ Save Appointment ] |
+----------------------+---------------------------------------------------------+
```

Key behavior:

- Use Flatpickr for date/time fields.
- Validate required patient, date, time, and purpose.
- Warn if the selected schedule conflicts with an existing appointment when possible.

---

## 10. Queue Management

```text
+--------------------------------------------------------------------------------+
| Queue                                                        Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Queue - Today                                           |
|                      |                                                         |
|                      | [ Search/select patient ] [ + Add to Queue ]             |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Queue No. | Patient | Status | Time In | Actions      | |
|                      | |-----------|---------|--------|---------|--------------| |
|                      | | 001       | ...     | Waiting| 08:01   | Call/Serve   | |
|                      | | 002       | ...     | Called | 08:05   | Complete     | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Open Queue Display ]                                  |
+----------------------+---------------------------------------------------------+
```

Key behavior:

- Queue numbers reset per day.
- Status changes should be confirmed where appropriate.
- Queue actions should be quick and easy to use.

---

## 11. Public Queue Display

```text
+--------------------------------------------------------------------------------+
| Barangay Sinalhan Health Center                                                 |
| Queue Display                                                                   |
|                                                                                |
|                NOW SERVING                                                      |
|                                                                                |
|                  004                                                           |
|                                                                                |
| +----------------------------+ +----------------------------+                  |
| | Waiting                    | | Recently Called            |                  |
| | 005                        | | 003                        |                  |
| | 006                        | | 002                        |                  |
| | 007                        | |                            |                  |
| +----------------------------+ +----------------------------+                  |
+--------------------------------------------------------------------------------+
```

Privacy note:

- Show queue numbers only unless the health center approves showing patient initials or names.

---

## 12. Vital Signs Form

```text
+--------------------------------------------------------------------------------+
| Vital Signs                                                  Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Record Vital Signs                                      |
|                      | Patient: Name | Patient No. | Age | Sex                 |
|                      |                                                         |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | BP Systolic    | | BP Diastolic   | | Heart Rate    | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Respiratory    | | Temperature    | | Oxygen Sat.   | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Weight kg      | | Height cm      | | BMI auto      | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Notes                                                     |
|                      | +--------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Cancel ]                            [ Save Vital Signs ]|
+----------------------+---------------------------------------------------------+
```

Key behavior:

- BMI may be auto-calculated if weight and height are provided.
- Important abnormal values may be highlighted carefully.
- Patient identity must be visible while recording vitals.

---

## 13. Consultation Form

```text
+--------------------------------------------------------------------------------+
| Consultation                                                Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | New Consultation                                        |
|                      | Patient: Name | Patient No. | Age | Sex                 |
|                      |                                                         |
|                      | [ Link latest vital signs v ]                            |
|                      |                                                         |
|                      | Subjective                                               |
|                      | +------------------------------------------------------+ |
|                      | | Chief complaint / history                              | |
|                      | +------------------------------------------------------+ |
|                      | Objective                                                |
|                      | +------------------------------------------------------+ |
|                      | | Findings / observations                                | |
|                      | +------------------------------------------------------+ |
|                      | Assessment                                               |
|                      | +------------------------------------------------------+ |
|                      | | Diagnosis or clinical impression                       | |
|                      | +------------------------------------------------------+ |
|                      | Plan                                                     |
|                      | +------------------------------------------------------+ |
|                      | | Treatment plan / advice / follow-up                    | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Cancel ]                            [ Save Consultation]|
+----------------------+---------------------------------------------------------+
```

Form notes:

- Use SOAP-style sections.
- Keep patient identity visible.
- Preserve form data after validation errors.

---

## 14. Reports Page

```text
+--------------------------------------------------------------------------------+
| Reports                                                      Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Reports                                                 |
|                      |                                                         |
|                      | Report Type                                             |
|                      | [ Daily Visits v ] [Date From] [Date To] [ Generate ]   |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Summary Cards / Chart                                 | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Report Results Table                                  | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Print ] [ Export PDF ] [ Export CSV ]                 |
+----------------------+---------------------------------------------------------+
```

Initial report types:

- Daily patient visits
- Consultation summary
- Patient registration summary
- Queue summary
- Vital signs record report

---

## 15. Archived Records

```text
+--------------------------------------------------------------------------------+
| Archived Records                                           Admin Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Archived Patient Records                                |
|                      |                                                         |
|                      | [ Search name or patient no. ] [Date From] [Date To]    |
|                      | [Archived By v] [ Search ]                              |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Patient No | Name | Archived Date | By | Reason | ... | |
|                      | |------------|------|---------------|----|--------|-----| |
|                      | | P-000001   | ...  | YYYY-MM-DD    | ...| ...    |View | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | Selected archived patient                               |
|                      | +------------------------------------------------------+ |
|                      | | Patient summary and archive reason                    | |
|                      | | [ View Details ] [ Restore Record ]                   | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Key behavior:

- Archived records are hidden from normal patient lists.
- Archive page is admin-focused.
- Restore action should require confirmation.
- Permanent delete should not be available in the initial capstone scope.

---

## 16. Audit Logs

```text
+--------------------------------------------------------------------------------+
| Audit Logs                                                   Admin Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Audit Logs                                              |
|                      |                                                         |
|                      | [Date From] [Date To] [User v] [Action v] [ Search ]    |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Date/Time | User | Action | Module | IP | Details     | |
|                      | |-----------|------|--------|--------|----|-------------| |
|                      | | ...       | ...  | ...    | ...    | ...| View        | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Access note:

- Audit logs are admin-only.
- Logs should be filterable and readable, but not editable.

---

## 17. Backup Page

```text
+--------------------------------------------------------------------------------+
| Backup                                                       Admin Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Database Backup                                         |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Last Backup: YYYY-MM-DD HH:MM                        | |
|                      | | Backup Location: storage/backups                     | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Create Backup ]                                      |
|                      |                                                         |
|                      | Backup History                                          |
|                      | +------------------------------------------------------+ |
|                      | | Filename | Date Created | Size | Created By | Action  | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Safety notes:

- Backup is admin-only.
- Backup creation should require confirmation.
- Full restore can remain a future enhancement.

---

## 18. User Management Page

```text
+--------------------------------------------------------------------------------+
| User Management                                              Admin Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | User Accounts Management                  [ + Add User ]|
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Username | Name | Role | Status | Last Login | Action  | |
|                      | |----------|------|------|--------|------------|---------| |
|                      | | admin    | ...  | Admin| Active | YYYY-MM-DD | [Edit]  | |
|                      | | doctor1  | ...  | Doctor| Active| YYYY-MM-DD | [Edit]  | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | Add / Edit User Modal                                   |
|                      | +------------------------------------------------------+ |
|                      | | Username: [               ] Role: [ Staff    v ]      | |
|                      | | First Name: [             ] Last Name: [           ]  | |
|                      | | Email: [                  ]                           | |
|                      | | [x] Force password reset on next login                | |
|                      | |                                                       | |
|                      | |                      [ Cancel ] [ Save Account ]      | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Access control:
- Access is restricted strictly to Administrator role.
- Activity logging: creation, updates, password resets, and role modifications must generate audit trails.
- Automatic account lockout: accounts are locked or suspended after 5 failed login attempts.

---

## 19. Suggested User Workflow

```text
Login
  |
  v
Dashboard
  |
  +--> Search/Register Patient
          |
          v
      Patient Profile
          |
          +--> Schedule Appointment
          |
          +--> Add to Queue
          |
          +--> Record Vital Signs
          |
          +--> Create Consultation
          |
          +--> Generate/Print Related Records

Admin
  |
  +--> View Archived Records
  |       |
  |       +--> View Archived Patient Details
  |       |
  |       +--> Restore Record
  |
  +--> User Management
          |
          +--> Add/Edit Users & Reset Passwords
```

This workflow should guide the main navigation and quick action buttons.
