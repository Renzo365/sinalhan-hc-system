# Authorization Patterns Research — Complete Findings

## 1. Controller Delete/Archive Methods

### 1.1 `VitalSignsController::delete()` (Lines 91–138)

**Uses:** Raw session checks — does **not** use the `is_admin()` helper.

```php
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
$canDelete = ($userRole === 'admin' || $currentUserId === (int)$vital['recorded_by']);
```

**Key details:**
- Checks `$_SESSION['user_role']`, falls back to `$_SESSION['role']`, defaults to `'staff'`
- Only allows `'admin'` role — does **not** include `'super_admin'`
- OR allows the original recorder (`recorded_by`) to delete
- Has CSRF validation and a consultation-link safety check before deletion
- Model's `delete()` is a **soft-delete** (`SET deleted_at = CURRENT_TIMESTAMP`)

---

### 1.2 `WellbabyController::deleteGrowthLog()` (Lines 640–674)

**Uses:** Raw session checks — does **not** use the `is_admin()` helper.

```php
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? 'staff';
if (!in_array($userRole, ['admin', 'super_admin'], true) && $currentUserId !== (int)$log['recorded_by']) {
```

**Key details:**
- Checks `$_SESSION['user_role']` only (no `$_SESSION['role']` fallback)
- Includes both `'admin'` and `'super_admin'` via `in_array()`
- OR allows the original recorder to delete
- Has CSRF validation
- Model's `deleteLog()` is a **soft-delete** (`SET deleted_at = CURRENT_TIMESTAMP`)

---

### 1.3 `WellbabyController::deleteImmunization()` (Lines 681–722)

**Uses:** Raw session checks — does **not** use the `is_admin()` helper.

```php
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
$canDelete = ($userRole === 'admin' || $currentUserId === (int)$imm['administered_by']);
```

**Key details:**
- Checks `$_SESSION['user_role']`, falls back to `$_SESSION['role']`, defaults to `'staff'`
- Only allows `'admin'` — does **not** include `'super_admin'`
- OR allows the person who administered (`administered_by`) to delete
- Has CSRF validation and audit log for CSRF violations
- Model's `deleteDose()` is a **soft-delete** (`SET deleted_at = CURRENT_TIMESTAMP`)

---

### 1.4 `PcbLedgerController::deleteLog()` (Lines 167–210)

**Uses:** Raw session checks — does **not** use the `is_admin()` helper.

```php
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? 'staff';
if (!in_array($userRole, ['admin', 'super_admin'], true) && $currentUserId !== (int)$log['recorded_by']) {
```

**Key details:**
- Checks `$_SESSION['user_role']` only (no fallback)
- Includes both `'admin'` and `'super_admin'` via `in_array()`
- OR allows original recorder to delete
- Has CSRF validation with audit log for CSRF violations
- **⚠️ CRITICAL:** Model's `deleteServiceLog()` is a **HARD DELETE** (`DELETE FROM pcb_service_logs WHERE id = :id`) — data is permanently destroyed

---

### 1.5 `PrenatalController::deletePastObstetric()` (Lines 417–447)

**Uses:** `is_admin()` helper — the **only** delete method that does!

```php
$patientId = (int)$record['patient_id'];
if (!is_admin()) {
    $_SESSION['error_message'] = 'Only an administrator may remove past obstetric history.';
    ...
}
```

**Key details:**
- Uses the `is_admin()` helper function (checks for both `'admin'` and `'super_admin'`)
- Admin-only — does **not** allow the original recorder to delete (unlike other controllers)
- Has CSRF validation
- Model's `deleteRecord()` is a **soft-delete** (`SET deleted_at = CURRENT_TIMESTAMP`)

---

### 1.6 `PrenatalController::deleteVisit()` (Lines 589–628)

**Uses:** Raw session checks — does **not** use the `is_admin()` helper.

```php
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
$canDelete = ($userRole === 'admin' || $currentUserId === (int)$visit['attended_by']);
```

**Key details:**
- Checks `$_SESSION['user_role']`, falls back to `$_SESSION['role']`, defaults to `'staff'`
- Only allows `'admin'` — does **not** include `'super_admin'`
- OR allows the attending clinician (`attended_by`) to delete
- Has CSRF validation with audit log for CSRF violations
- Model's `deleteVisit()` is a **soft-delete** (`SET deleted_at = CURRENT_TIMESTAMP`)

---

### 1.7 `ConsultationController::archive()` (Lines 358–388)

**Uses:** Raw session checks — does **not** use the `is_admin()` helper.

```php
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
$canArchive = (in_array($userRole, ['admin', 'super_admin'], true) || $currentUserId === (int)$consultation['created_by'] || $currentUserId === (int)$consultation['consulted_by']);
```

**Key details:**
- Checks `$_SESSION['user_role']` with `$_SESSION['role']` fallback, defaults to `'staff'`
- Includes both `'admin'` and `'super_admin'` via `in_array()`
- OR allows the creator (`created_by`) or the consulting clinician (`consulted_by`) to archive
- Model's `archive()` is a **soft-delete** (`SET deleted_at = CURRENT_TIMESTAMP`)
- **No CSRF validation** in this method

---

## 2. Role-Checking Helpers — `helpers.php` (Lines 49–59)

```php
if (!function_exists('is_admin')) {
    function is_admin() {
        return in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'], true);
    }
}
if (!function_exists('is_super_admin')) {
    function is_super_admin() {
        return ($_SESSION['user_role'] ?? '') === 'super_admin';
    }
}
```

**Key observations:**
- `is_admin()` checks `$_SESSION['user_role']` and returns `true` for **both** `'admin'` and `'super_admin'`
- `is_super_admin()` checks `$_SESSION['user_role']` for only `'super_admin'`
- Both use strict type comparison (`true` for `in_array` / `===`)
- Neither falls back to `$_SESSION['role']` — only checks `$_SESSION['user_role']`
- Other helpers in the file: `h()` (HTML escape), `csrf_token()`, `csrf_field()`, `url()`, `asset()`

---

## 3. View-Level Check — Archive Patient Button (`patients/show.php`, Line 178)

**Uses:** Raw session check — does **not** use the `is_admin()` helper.

```php
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <button type="button" class="btn btn-outline-danger btn-sm ..." data-bs-toggle="modal" data-bs-target="#archivePatientModal" title="Archive Patient Record">
        <i class="bi bi-archive me-1"></i> Archive
    </button>
<?php endif; ?>
```

**Key issue:** Only checks for `'admin'` exactly — a `'super_admin'` user would **not** see the Archive Patient button, even though they logically should be able to.

---

## 4. Models: Hard-Delete vs. Soft-Delete Analysis

| Model | Has Delete Method | Delete Type | Has `deleted_at` Soft-Delete | Has `archive()` Method |
|---|---|---|---|---|
| PcbLedger | `deleteServiceLog()` | ⚠️ **HARD DELETE** (`DELETE FROM`) | NO — `deleted_at` nowhere | NO |
| VitalSigns | `delete()` | Soft-delete | YES | NO |
| ChildGrowthLog | `deleteLog()` | Soft-delete | YES | NO |
| Immunization | `deleteDose()`, `deleteByPatientVaccineDose()` | Soft-delete | YES | NO |
| PastObstetricHistory | `deleteRecord()` | Soft-delete | YES | NO |
| PrenatalVisit | `deleteVisit()` | Soft-delete | YES | NO |
| PrenatalRecord | `deleteEpisode()` | Soft-delete | YES | NO |
| PatientMedicalHistory | `deleteHistory()` | Soft-delete | YES | NO |
| Consultation | `cancel()` + `archive()` | Soft-delete | YES | YES |
| Patient | `archive()` | Soft-delete | YES | YES |
| User | `archive()` | Likely soft | NO `deleted_at` found | YES |
| WellbabyRecord | `deleteRecord()` | Soft-delete | YES | NO |
| Appointment | No delete method | N/A | References patient's `deleted_at` only | NO |
| QueueEntry | No delete method | N/A | NO | NO |
| AuditLog | No delete method (immutable) | N/A | NO | NO |

**Model with hard delete and no soft-delete support:**
`PcbLedger` — `deleteServiceLog()` uses `DELETE FROM pcb_service_logs WHERE id = :id`. There is zero `deleted_at` usage anywhere in this model. Data is permanently destroyed with no recovery path.

---

## 5. Summary of Authorization Inconsistencies

1. **Inconsistent `super_admin` coverage:** 3 methods only check `'admin'` (`VitalSigns::delete`, `WellbabyController::deleteImmunization`, `PrenatalController::deleteVisit`), while 3 methods correctly include both `'admin'` and `'super_admin'` (`WellbabyController::deleteGrowthLog`, `PcbLedgerController::deleteLog`, `ConsultationController::archive`).

2. **Only 1 of 6 delete methods uses the `is_admin()` helper:** `PrenatalController::deletePastObstetric()`. All others use raw session checks with varying patterns.

3. **Inconsistent session key fallback:** Some methods check `$_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff'` (double fallback), while others check only `$_SESSION['user_role'] ?? 'staff'` (no fallback).

4. **Archive Patient button (view)** uses `$_SESSION['user_role'] === 'admin'` — excludes `super_admin`.

5. **PcbLedger is the only model with a hard `DELETE`** — no `deleted_at` column, no recovery possible.