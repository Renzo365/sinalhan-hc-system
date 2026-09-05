<?php
$title = 'Add New User Account';
$breadcrumbs = [
    'User Accounts' => '/users',
    'Add New User' => null
];
require dirname(__DIR__) . '/layout/header.php';

$old = $_SESSION['old_input'] ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['old_input'], $_SESSION['form_errors']);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-0 fw-bold text-primary-dark">Add New User Account</h2>
        <p class="text-secondary small mb-0">Register staff or administrative credentials and set up initial profile details.</p>
    </div>
    
    <div>
        <a href="<?= url('/users') ?>" class="btn btn-outline-secondary d-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Back to Users Directory
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 12px;">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
            <div>
                <strong class="d-block">Please correct the following issues:</strong>
                <ul class="mb-0 ps-3 small">
                    <?php foreach ($errors as $err): ?>
                        <li><?= h($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form action="<?= url('/users') ?>" method="POST" id="createUserForm">
    <?= csrf_field() ?>

    <!-- Section 1: Account Credentials -->
    <div class="card card-premium mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                <i class="bi bi-shield-lock-fill me-2 text-primary"></i>Account Credentials
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="username" class="form-label fw-semibold text-secondary small">Username <span class="text-danger">*</span> <span class="text-muted fw-normal">(Unique account handle)</span></label>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           class="form-control" 
                           placeholder="e.g. jdoe" 
                           value="<?= h($old['username'] ?? '') ?>" 
                           required 
                           pattern="^[a-zA-Z0-9_]{3,20}$" 
                           title="Username must be alphanumeric, between 3 to 20 characters.">
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="password" class="form-label fw-semibold text-secondary small">Initial Password <span class="text-danger">*</span> <span class="text-muted fw-normal">(Minimum 8 characters)</span></label>
                    <div class="input-group">
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control border-end-0" 
                               placeholder="Enter initial secure password" 
                               required 
                               minlength="8">
                        <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Personal Information -->
    <div class="card card-premium mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                <i class="bi bi-person-vcard-fill me-2 text-primary"></i>Personal Information
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label for="first_name" class="form-label fw-semibold text-secondary small">First Name <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="first_name" 
                           id="first_name" 
                           class="form-control" 
                           placeholder="e.g. Maria" 
                           value="<?= h($old['first_name'] ?? '') ?>" 
                           required>
                </div>
                
                <div class="col-12 col-md-4">
                    <label for="middle_name" class="form-label fw-semibold text-secondary small">Middle Name <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="text" 
                           name="middle_name" 
                           id="middle_name" 
                           class="form-control" 
                           placeholder="e.g. Santos" 
                           value="<?= h($old['middle_name'] ?? '') ?>">
                </div>
                
                <div class="col-12 col-md-4">
                    <label for="last_name" class="form-label fw-semibold text-secondary small">Last Name <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="last_name" 
                           id="last_name" 
                           class="form-control" 
                           placeholder="e.g. Cruz" 
                           value="<?= h($old['last_name'] ?? '') ?>" 
                           required>
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="email" class="form-label fw-semibold text-secondary small">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           class="form-control" 
                           placeholder="e.g. maria.cruz@example.com" 
                           value="<?= h($old['email'] ?? '') ?>">
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="contact_no" class="form-label fw-semibold text-secondary small">Contact Number <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="text" 
                           name="contact_no" 
                           id="contact_no" 
                           class="form-control" 
                           placeholder="e.g. 09171234567" 
                           value="<?= h($old['contact_no'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Professional & Organizational Details -->
    <div class="card card-premium mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                <i class="bi bi-briefcase-fill me-2 text-primary"></i>Employment & Assignment Details
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label for="job_title" class="form-label fw-semibold text-secondary small">Clinic Job Title</label>
                    <input type="text" 
                           name="job_title" 
                           id="job_title" 
                           class="form-control" 
                           placeholder="e.g. Midwife, Nurse, BHW, Doctor" 
                           value="<?= h($old['job_title'] ?? '') ?>">
                </div>
                
                <div class="col-12 col-md-4">
                    <label for="employee_id" class="form-label fw-semibold text-secondary small">Employee ID / PRC License No.</label>
                    <input type="text" 
                           name="employee_id" 
                           id="employee_id" 
                           class="form-control" 
                           placeholder="e.g. EMP-2026-004 or PRC-09823" 
                           value="<?= h($old['employee_id'] ?? '') ?>">
                </div>
                
                <div class="col-12 col-md-4">
                    <label for="department" class="form-label fw-semibold text-secondary small">Department / Clinic Unit</label>
                    <select name="department" id="department" class="form-select bg-light">
                        <option value="">-- Select Clinic Unit --</option>
                        <option value="General Consultation" <?= (isset($old['department']) && $old['department'] === 'General Consultation') ? 'selected' : '' ?>>General Consultation</option>
                        <option value="Maternal & Child Health" <?= (isset($old['department']) && $old['department'] === 'Maternal & Child Health') ? 'selected' : '' ?>>Maternal & Child Health</option>
                        <option value="Vaccination & Immunization" <?= (isset($old['department']) && $old['department'] === 'Vaccination & Immunization') ? 'selected' : '' ?>>Vaccination & Immunization</option>
                        <option value="Records & Administrative Office" <?= (isset($old['department']) && $old['department'] === 'Records & Administrative Office') ? 'selected' : '' ?>>Records & Administrative Office</option>
                        <option value="Laboratory & Diagnostics" <?= (isset($old['department']) && $old['department'] === 'Laboratory & Diagnostics') ? 'selected' : '' ?>>Laboratory & Diagnostics</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4: System Privileges & Status -->
    <div class="card card-premium mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                <i class="bi bi-sliders me-2 text-primary"></i>Privileges & Initial Status
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="role" class="form-label fw-semibold text-secondary small">Access Privilege <span class="text-danger">*</span></label>
                    <?php if ($_SESSION['user_id'] == 1): ?>
                        <select name="role" id="role" class="form-select bg-light" required>
                            <option value="staff" <?= (isset($old['role']) && $old['role'] === 'staff') ? 'selected' : '' ?>>Staff Personnel</option>
                            <option value="admin" <?= (isset($old['role']) && $old['role'] === 'admin') ? 'selected' : '' ?>>Co-Administrator</option>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="role" value="staff">
                        <select class="form-select bg-light text-muted" disabled>
                            <option selected>Staff Personnel</option>
                        </select>
                        <div class="form-text small text-muted">Co-Admins can only create Staff Personnel accounts.</div>
                    <?php endif; ?>
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="status" class="form-label fw-semibold text-secondary small">Initial Account Status <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select bg-light" required>
                        <option value="active" <?= (isset($old['status']) && $old['status'] === 'inactive') ? '' : 'selected' ?>>Active (Can log in immediately)</option>
                        <option value="inactive" <?= (isset($old['status']) && $old['status'] === 'inactive') ? 'selected' : '' ?>>Inactive (Access disabled)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Footer -->
    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="<?= url('/users') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle-fill me-1"></i> Save User Account
        </button>
    </div>
</form>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
