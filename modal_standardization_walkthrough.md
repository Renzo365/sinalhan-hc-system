# Modal Standardization Walkthrough

This document outlines the standardized **Edit modal designs** aligned with their corresponding **Create/Record modals** across the Sinalhan Health Center system.

---

## 1. Vital Signs Modals (`app/Views/patients/show.php`)

### Pair: **Record Vital Signs** vs. **Edit Vital Signs**
- **Modal Title:** Standardized from *"Edit Vital Signs Record"* to **"Edit Vital Signs"** (matching *"Record Vital Signs"*).
- **Patient Context Banner:** Added the patient metadata bar (`Patient: Last, First • DOB: YYYY-MM-DD`) into the Edit modal to match the Record modal.
- **Input Groups & Units:** Converted standalone number inputs in Edit to matching input groups (`mmHg`, `bpm`, `°C`, `cpm`, `%`, `kg`, `cm`).
- **Field Labels & Limits:** Synchronized labels (*"Blood Pressure - Systolic"*, *"Blood Pressure - Diastolic"*, *"Heart Rate / Pulse"*, *"Body Temperature"*, *"Respiratory Rate"*, *"Oxygen Saturation (SpO2)"*, *"Weight"*, *"Height"*, *"Calculated BMI"*, *"Waist Circumference"*, *"Clinical Notes / Symptoms"*).
- **Auto-Calculated BMI:** Added the readonly `Calculated BMI` input field and wired up dynamic BMI calculation (`calculateEditBMI`) on weight/height input and on opening the Edit modal.
- **Clinical Notes:** Upgraded notes field from a single-line input to a 2-row `<textarea>` with identical placeholder text.
- **Footer:** Standardized button styling and alignment (`Cancel` and `Save Changes`).

---

## 2. Child Growth & Anthropometrics Modals (`app/Views/wellbaby/show.php`)

### Pair: **Record Pediatric Anthropometrics & Growth Visit** vs. **Edit Pediatric Anthropometrics & Growth Visit**
- **Modal Title & Header Theme:** 
  - Standardized title from *"Edit Growth Monitoring Checkup"* to **"Edit Pediatric Anthropometrics & Growth Visit"**.
  - Updated header from `bg-primary` (blue) to `bg-success` (green) to adhere to the Well-Baby brand color scheme.
- **Field Nomenclature & Order:**
  - Standardized *"Date of Visit"* to **"Checkup Date *"**.
  - Standardized *"Age in Months"* to **"Exact Age in Months *"** with placeholder `e.g. 1.5`.
  - Standardized height label to **"Height / Length (cm) *"**.
  - Standardized head and chest labels from abbreviations (*"Head Circ. (cm)"*) to full terms (**"Head Circumference (cm)"** and **"Chest Circumference (cm)"**).
  - Standardized temperature to **"Body Temperature (°C)"**.
  - Standardized feeding label to **"Infant Feeding Practice"**.
- **Missing Field Restored:** Added **"Vaccine / Intervention Note"** (`vaccines_administered`) into the Edit modal, populated via the edit table button and updated in [ChildGrowthLog.php](file:///c:/xampp/htdocs/sinalhan-hc-system/app/Models/ChildGrowthLog.php).
- **Supplements Toggles:** Aligned checkbox labels (*"Vitamin A Capsule Given"*, *"Deworming Tablet Given"*).
- **Footer Action:** Styled submit button with `btn btn-success text-white px-4 fw-semibold` matching the Record modal.

---

## 3. Diagnostic / PCB Service Modals (`app/Views/patients/show.php`)

### Pair: **Record Diagnostic / PCB Service** vs. **Edit Diagnostic / PCB Service**
- **Field Layout & Ordering:**
  - Service Section / Category: Moved to a full-width (`col-12`) dropdown with full option labels (*"Diagnostic Examination Services"*, *"Other PCB1 Services"*, *"Other Services"*).
  - Encounter Date and Clinical Diagnosis: Aligned into a side-by-side row (`col-12 col-md-6` each).
  - Service / Test Type: Integrated the `<datalist id="commonPcbServices">` auto-complete choices into the Edit modal.
- **Status Checkboxes:** Standardized checkbox wording to *"Given In-Clinic"* and *"Referred"*, with a divider line above.
- **Placeholders:** Added consistent helper placeholders across Diagnosis, Test Type, Referred Facility, and Remarks.
- **Footer:** Updated button styling to standard `px-4 fw-semibold`.

---

## 4. Vaccine Dose / Immunization Modals (`app/Views/patients/show.php`)

### Pair: **Record Vaccine Dose** vs. **Edit Vaccine Dose**
- **Modal Title:** Aligned to **"Edit Vaccine Dose"**.
- **Remarks Field:** Replaced multi-line textarea with standard text input matching the Record modal (`Remarks / Lot No. / Site` with placeholder `e.g. Lot #ABC-123, Left Deltoid, Bakuna Eskwela`).
- **Footer Buttons:** Standardized padding and classes (`Cancel` and `Save Changes`).

---

## 5. Other Modals Audited (Already Consistent)
- **Prenatal Follow-Up Visit (`app/Views/maternal/show.php`):**
  - Record: *"Record Serial Prenatal Follow-Up Visit"*
  - Edit: *"Edit Serial Prenatal Follow-Up Visit"*
  - *Result:* Field order, AOG, FHT, fundic height, presentation, and layout were already identical.
- **Past Delivery Record (`app/Views/maternal/show.php`):**
  - Add: *"Add Past Delivery Record (G1, G2...)"*
  - Edit: *"Edit Past Delivery Record"*
  - *Result:* Gravida, delivery type, infant sex, place, year, attendant, and child status were already identical.
