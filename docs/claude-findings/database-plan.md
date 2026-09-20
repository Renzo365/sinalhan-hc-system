# Complete Database Schema & Model Layer Research Findings

## 1. Model-by-Model Analysis (15 models in `app/Models/`)

### 1.1 `Patient.php` (table: `patients`)
- **`deleted_at` column:** ✅ YES — SQL queries filter `WHERE deleted_at IS NULL`
- **`archive($id, $userId, $reason)`:** ✅ YES (line 397) — sets `deleted_at`, `deleted_by`, `archive_reason`
- **`restore($id)`:** ✅ YES (line 418) — NULLs out `deleted_at`, `deleted_by`, `archive_reason`
- **Hard `delete()`:** ❌ NO — no hard delete method
- **Other CRUD:** `allActive()`, `findById()`, `findDuplicates()`, `create()`, `update()`, `updateParentalInfo()`, `allArchived()`, `isPhilHealthUnique()`, `familyMembers()`, `getProgramBadge()`, `findPotentialMothers()`, `getUnregisteredChildren()`, `getEligibleMaternalPatients()`

### 1.2 `Consultation.php` (table: `consultations`)
- **`deleted_at` column:** ✅ YES
- **`archive($id, $userId, $reason)`:** ✅ YES (line 193) — sets `deleted_at`, `deleted_by`, `archive_reason`
- **`restore($id)`:** ✅ YES (line 213) — NULLs out `deleted_at`, `deleted_by`, `archive_reason`
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findByPatientId()`, `findById()`, `create()`, `update()`, `cancel($id, $userId, $reason)` (sets `status='Cancelled'`, stores reason in `archive_reason` field), `allArchived()`, `findWithArchivedById()`

### 1.3 `User.php` (table: `users`)
- **`deleted_at` column:** ✅ YES — `all()` filters `WHERE deleted_at IS NULL`
- **`archive($id)`:** ✅ YES (line 191) — sets `deleted_at`, `status='inactive'`. ⚠️ **NOTE:** Does **not** accept a reason parameter — no `archive_reason` stored
- **`restore($id)`:** ✅ YES (line 208) — NULLs `deleted_at`, sets `status='active'`, resets lockout
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findByUsername()`, `findByLoginIdentifier()`, `findById()`, `updatePassword()`, `updateProfile()`, `all()`, `allArchived()`, `create()`, `update()`, `setStatus()` (deprecated), `incrementFailedAttempts()`, `isLockedOut()`, `clearLockout()`, `resetFailedAttempts()`, `updateLoginTimestamp()`, `isUsernameUnique()`, `isEmailUnique()`

### 1.4 `Appointment.php` (table: `appointments`)
- **`deleted_at` column:** ❌ NO — schema has no `deleted_at` column
- **`archive()` / `restore()`:** ❌ NO
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findAll()`, `findByPatientId()`, `findById()`, `create()`, `update()`, `updateStatus()`, `hasConflict()`, `getTodayAppointments()`

### 1.5 `Immunization.php` (table: `immunizations`)
- **`deleted_at` column:** ✅ YES
- **`archive()` / `restore()`:** ❌ No named archive/restore
- **Soft delete method:** `deleteDose($id, $userId, $reason)` (line 143) — sets `deleted_at`, `deleted_by`, `archive_reason`. This is a soft delete, named `deleteDose`
- **`deleteByPatientVaccineDose()`** (line 168): another soft-delete variant by composite key
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findByPatientId()`, `getVaccineMap()`, `recordDose()` (upsert), `findById()`

### 1.6 `VitalSigns.php` (table: `vital_signs`)
- **`deleted_at` column:** ✅ YES
- **`archive()` / `restore()`:** ❌ No named archive/restore
- **Soft delete method:** `delete($id, $userId, $reason)` (line 121) — sets `deleted_at`, `deleted_by`, `archive_reason`. Soft delete despite being named `delete()`
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findByPatientId()`, `latestByPatientId()`, `getLatestByPatientId()`, `create()`, `findById()`

### 1.7 `PrenatalRecord.php` (table: `prenatal_records`)
- **`deleted_at` column:** ✅ YES
- **`archive()` / `restore()`:** ❌ No named archive/restore
- **Soft delete method:** `deleteEpisode($id, $userId, $reason)` (line 294) — sets `deleted_at`, `deleted_by`, `archive_reason`, also sets `is_active = 0`
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findActiveByPatientId()`, `hasActiveEpisode()`, `findAllByPatientId()`, `findById()`, `createEpisode()`, `updateEpisode()`, `concludeEpisode()`, `calculateEDC()`, `calculateCurrentAOG()`, `getActiveRoster()`, `getRecentlyDelivered()`

### 1.8 `PrenatalVisit.php` (table: `prenatal_visits`)
- **`deleted_at` column:** ✅ YES
- **`archive()` / `restore()`:** ❌ NO
- **Soft delete method:** `deleteVisit($id, $userId, $reason)` (line 95) — sets `deleted_at`, `deleted_by`, `archive_reason`
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findByPrenatalId()`, `findById()`, `createVisit()`, `countByPrenatalId()`, `updateVisit()`

### 1.9 `PastObstetricHistory.php` (table: `past_obstetric_histories`)
- **`deleted_at` column:** ✅ YES
- **`archive()` / `restore()`:** ❌ NO
- **Soft delete method:** `deleteRecord($id, $userId, $reason)` (line 81) — sets `deleted_at`, `deleted_by`, `archive_reason`
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findByPatientId()`, `findById()`, `createRecord()`

### 1.10 `WellbabyRecord.php` (table: `wellbaby_records`)
- **`deleted_at` column:** ✅ YES
- **`archive()` / `restore()`:** ❌ NO
- **Soft delete method:** `deleteRecord($id, $userId, $reason)` (line 161) — sets `deleted_at`, `deleted_by`, `archive_reason`
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findByPatientId()`, `findById()`, `createRecord()` (UPSERT with `ON DUPLICATE KEY`), `updateRecord()`, `getRegisteredRoster()`

### 1.11 `ChildGrowthLog.php` (table: `child_growth_logs`)
- **`deleted_at` column:** ✅ YES
- **`archive()` / `restore()`:** ❌ NO
- **Soft delete method:** `deleteLog($id, $userId, $reason)` (line 94) — sets `deleted_at`, `deleted_by`, `archive_reason`
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findByWellbabyId()`, `findById()`, `createLog()`

### 1.12 `PatientMedicalHistory.php` (tables: `patient_medical_histories` + `patient_conditions` + `patient_surgeries` + `patient_external_immunizations`)
- **`deleted_at` column:** ✅ YES — on all four tables
- **`archive()` / `restore()`:** ❌ NO
- **Soft delete method:** `deleteHistory($patientId, $userId, $reason)` (line 539) — transactional soft delete across all four tables
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findByPatientId()` (hydrates from 4 tables), `saveHistory()` (transactional upsert across 4 tables), `updateObstetricOutcome()`, `decrementGravida()`
- **Note:** `saveHistory()` internally soft-deletes old condition/surgery/immunization rows (superseded pattern) before inserting new ones

### 1.13 `PcbLedger.php` (tables: `pcb_obligated_services` + `pcb_service_logs`)
- **`deleted_at` column:** ❌ NO — neither table has `deleted_at`
- **`archive()` / `restore()`:** ❌ NO
- **Hard `delete()`:** ✅ YES — `deleteServiceLog($id, $patientId)` (line 181) executes `DELETE FROM pcb_service_logs WHERE id = :id` — **this is the only true hard delete in the entire codebase**
- **Other CRUD:** `getObligatedServices()`, `saveObligatedServices()` (upsert), `getServiceLogs()`, `createServiceLog()`, `findLogById()`

### 1.14 `QueueEntry.php` (table: `queue_entries`)
- **`deleted_at` column:** ❌ NO
- **`archive()` / `restore()`:** ❌ NO
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `findAllToday()`, `findByPatientId()`, `findById()`, `isPatientQueuedToday()`, `getNextQueueNo()`, `create()`, `updateStatus()`, `getTodayStats()`, `getPublicDisplayData()`

### 1.15 `AuditLog.php` (table: `audit_logs`)
- **`deleted_at` column:** ❌ NO
- **`archive()` / `restore()`:** ❌ NO
- **Hard `delete()`:** ❌ NO
- **Other CRUD:** `log()` (static), `allFiltered()`, `getUniqueActions()`

---

## 2. Database Schema — Tables With `deleted_at` Columns

From `database/schema.sql`, confirmed tables with `deleted_at` + `deleted_by` + `archive_reason`:

| Table | Has `deleted_at` | Has `deleted_by` | Has `archive_reason` |
|---|---|---|---|
| users | ✅ | ❌ (no `deleted_by` col) | ❌ (no `archive_reason` col) |
| patients | ✅ | ✅ | ✅ |
| vital_signs | ✅ | ✅ | ✅ |
| consultations | ✅ | ✅ | ✅ |
| prescriptions | ✅ | ✅ | ✅ |
| immunizations | ✅ | ✅ | ✅ |
| patient_medical_histories | ✅ | ✅ | ✅ |
| patient_conditions | ✅ | ✅ | ✅ |
| patient_surgeries | ✅ | ✅ | ✅ |
| patient_external_immunizations | ✅ | ✅ | ✅ |
| prenatal_records | ✅ | ✅ | ✅ |
| prenatal_visits | ✅ | ✅ | ✅ |
| past_obstetric_histories | ✅ | ✅ | ✅ |
| wellbaby_records | ✅ | ✅ | ✅ |
| child_growth_logs | ✅ | ✅ | ✅ |
| lab_requests | ✅ | ✅ | ✅ |
| lab_results | ✅ | ✅ | ✅ |

**Tables without `deleted_at` (no soft delete):**
- `appointments`
- `queue_entries`
- `queue_daily_counters`
- `settings`
- `audit_logs`
- `pcb_obligated_services`
- `pcb_service_logs`

> ⚠️ **Key gap:** The `users` table has `deleted_at` in the schema but does **not** have `deleted_by` or `archive_reason` columns — unlike all other soft-deletable tables. `User::archive()` accordingly only sets `deleted_at` and `status='inactive'` without storing who archived the record or why.

---

## 3. Routes Related to Delete, Archive, and Restore

```
# Patient Archive/Restore (Admin Only)
POST /patients/{id}/archive          → PatientController@archive       [AdminMiddleware]
GET  /archive                        → PatientController@archivedIndex [AdminMiddleware]
GET  /archive/patients               → PatientController@archivedIndex [AdminMiddleware]
POST /archive/patients/{id}/restore  → PatientController@restore       [AdminMiddleware]

# Consultation Archive/Restore
POST /consultations/{id}/archive           → ConsultationController@archive  [AuthMiddleware]
POST /archive/consultations/{id}/restore   → ConsultationController@restore  [AdminMiddleware]
POST /consultations/{id}/cancel            → ConsultationController@cancel   [AuthMiddleware]

# User Archive/Restore (Admin Only)
POST /users/{id}/archive       → UserController@archive  [AdminMiddleware]
POST /users/{id}/restore       → UserController@restore  [AdminMiddleware]
POST /users/{id}/toggle-status → UserController@archive  [AdminMiddleware]  (alias)

# Vital Signs Delete (soft)
POST /vital-signs/{id}/delete  → VitalSignsController@delete  [AuthMiddleware]

# Immunization Delete (soft)
POST /immunizations/{id}/delete → WellbabyController@deleteImmunization  [AuthMiddleware]

# Growth Log Delete (soft)
POST /wellbaby/growth-log/{id}/delete → WellbabyController@deleteGrowthLog  [AuthMiddleware]

# Past Obstetric Delete (soft)
POST /past-obstetric/{id}/delete → PrenatalController@deletePastObstetric  [AuthMiddleware]

# Prenatal Visit Delete (soft)
POST /prenatal/visit/{id}/delete → PrenatalController@deleteVisit  [AuthMiddleware]

# Prenatal Episode Cancel (soft delete)
POST /prenatal/{id}/cancel → PrenatalController@cancelEpisode  [AuthMiddleware]

# PCB Service Log Delete (⚠️ HARD DELETE)
POST /pcb/service-log/{id}/delete → PcbLedgerController@deleteLog  [AuthMiddleware]
```

---

## 4. Archive Patient Modal (`#archivePatientModal`, lines 3095–3130)

- Only shown to admins (`$_SESSION['user_role'] === 'admin'`)
- Contains a Bootstrap modal with a danger-styled header ("Archive Patient Record")
- Warning alert: *"Archiving will hide this patient from active directories, daily queues, and scheduling lists. Only administrators can view and restore archived records."*
- **Asks for a reason:** has a `<textarea name="archive_reason">` labeled "Reason for Archiving *" (required)
  - Placeholder text: *"e.g. Patient moved, deceased, or record duplicate..."*
- Form POSTs to `/patients/{id}/archive`
- Includes CSRF protection
- Triggered by a button at line 179: `data-bs-toggle="modal" data-bs-target="#archivePatientModal"`

---

## 5. Consultation Archive JavaScript (`btn-archive-consultation` handler, lines 3809–3851)

- **Asks for a reason** via SweetAlert
- Uses `Swal.fire()` with:
  - `title`: `'Archive Consultation?'`
  - `text`: `'Archiving hides this consultation note from the active history. Please provide a reason:'`
  - `input`: `'textarea'` (SweetAlert input type)
  - `inputPlaceholder`: `'e.g. Inadvertent duplication, erroneous entry...'`
  - `inputValidator`: checks `if (!value || !value.trim())` → returns `'Archive reason is required!'`
  - `confirmButtonText`: `'Yes, Archive Consultation'`
- On confirmation: sets the hidden form (`#archiveConsultationForm`) action to `/consultations/{id}/archive`, puts the reason into the hidden `#archiveConsultationReasonInput` input, then submits
- **Fallback:** if SweetAlert is not available, falls back to a browser `prompt()` dialog
- Hidden form at line 3075–3078: `<form id="archiveConsultationForm">` with `<input type="hidden" name="reason">`

---

## 6. Summary Table: Models With `archive()`/`restore()` vs. Soft-Delete-Named-Delete

| Model | Named `archive()` | Named `restore()` | Soft delete `*()` | Hard `DELETE` |
|---|---|---|---|---|
| Patient | ✅ | ✅ | ❌ | ❌ |
| Consultation | ✅ | ✅ | ❌ | ❌ |
| User | ✅ (no reason) | ✅ | ❌ | ❌ |
| Appointment | ❌ | ❌ | ❌ | ❌ |
| Immunization | ❌ | ❌ | ✅ `deleteDose()` | ❌ |
| VitalSigns | ❌ | ❌ | ✅ `delete()` | ❌ |
| PrenatalRecord | ❌ | ❌ | ✅ `deleteEpisode()` | ❌ |
| PrenatalVisit | ❌ | ❌ | ✅ `deleteVisit()` | ❌ |
| PastObstetricHistory | ❌ | ❌ | ✅ `deleteRecord()` | ❌ |
| WellbabyRecord | ❌ | ❌ | ✅ `deleteRecord()` | ❌ |
| ChildGrowthLog | ❌ | ❌ | ✅ `deleteLog()` | ❌ |
| PatientMedicalHistory | ❌ | ❌ | ✅ `deleteHistory()` | ❌ |
| PcbLedger | ❌ | ❌ | ❌ | ✅ `deleteServiceLog()` |
| QueueEntry | ❌ | ❌ | ❌ | ❌ |
| AuditLog | ❌ | ❌ | ❌ | ❌ |