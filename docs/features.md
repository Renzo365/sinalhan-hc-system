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

### 1.3 Active Directory Filtering
The main directory uses server-side search and filters:
* **Text Search**: Matches against patient number, first/last/middle names, phone number, and barangay.
* **Demographics Filter**: Narrow list by Sex (Male/Female), Barangay, or Age Group (Child `0-12`, Teen `13-19`, Adult `20-59`, Senior `60+`).
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

Manages patient scheduling and schedules clinic resources.

### 4.1 Conflict Prevention
When selecting an appointment date and time, an AJAX conflict check queries the database for existing active bookings. If a clinician or time slot overlaps, a red alert box appears warning the staff to coordinate a reschedule.

### 4.2 Status Tracking & Quick Actions
Appointments can be filtered by date range or status. Users can transition appointments using quick-action buttons:
* **Scheduled**: Initial state.
* **Completed**: Linked to a patient's consultation.
* **Cancelled**: Cancelled by staff or patient.
* **Missed**: Automatically flagged if the patient fails to show up on the date.

---

## 5. Daily Operations Queue

Optimizes daily patient flow inside the waiting area.

### 5.1 Daily Queue Numbers
Enqueuing a patient generates an auto-incrementing queue number that resets daily:
`001`, `002`, `003`...

### 5.2 Waiting Monitor Board
Provides a dedicated, public-facing, full-screen monitor display at `/queue/display` designed to run on a TV or tablet in the lobby:
* **Privacy Controls**: Exposes only queue numbers (e.g., `005`) and active call statuses, preserving patient privacy.
* **Audio Chime System**: Generates a professional double-tone audio chime (using the browser's native `AudioContext` synth) when a new queue number is transitioned to "Called", alerting waiting patients without relying on external media assets or CDNs.
* **Asynchronous Polling**: Feeds updates from the database every 5 seconds via JSON calls.

---

## 6. Reports & Data Auditing

### 6.1 Report Generator
Extracts operational metrics by date range:
* **Daily Patient Visits**: Log of checkups and registrations.
* **Consultations Summary**: Volume of SOAP diagnoses.
* **Queue Operations**: Metrics on wait times, cancellations, and completed operations.

### 6.2 Print layouts & CSV Exports
* **Custom Print CSS**: Hides navigation sidebars, headers, and buttons during browser printing (Ctrl+P) or "Save as PDF" calls, formatting clean, printable grids.
* **CSV Export**: Triggers a clean raw-comma export for use in spreadsheet software.

### 6.3 Audit Trails
An admin-only dashboard indexes chronological logs of important data modifications (logins, registrations, database backups, patient archiving, password resets) showing the timestamp, IP address, user, and detailed action summary.
