<?php
$title = 'Edit User Account';
$breadcrumbs = [
    'User Accounts' => '/users',
    'Edit User' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-0 fw-bold text-primary-dark">Edit User Account: <?= h($user['username']) ?></h2>
        <p class="text-secondary small mb-0">Update staff demographics, organizational assignment, or access privileges.</p>
    </div>
    
    <div>
        <a href="<?= url('/users') ?>" class="btn btn-outline-secondary d-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Back to Users Directory
        </a>
    </div>
</div>

<!-- Account Status & Metadata Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card card-premium bg-white p-3 h-100">
            <div class="text-muted small fw-semibold">Account Reference ID</div>
            <div class="fs-5 fw-bold text-dark font-monospace mt-1">#<?= h($user['id']) ?></div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card card-premium bg-white p-3 h-100">
            <div class="text-muted small fw-semibold">Current Status</div>
            <div class="mt-1">
                <?php if ($user['status'] === 'active'): ?>
                    <span class="badge bg-success-bg text-success border border-success-subtle px-3 py-1.5"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                <?php else: ?>
                    <span class="badge bg-secondary text-white px-3 py-1.5"><i class="bi bi-pause-circle-fill me-1"></i> Inactive</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card card-premium bg-white p-3 h-100">
            <div class="text-muted small fw-semibold">Date Created</div>
            <div class="fw-bold text-dark mt-1 small"><?= date('M d, Y h:i A', strtotime($user['created_at'])) ?></div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card card-premium bg-white p-3 h-100">
            <div class="text-muted small fw-semibold">Last System Login</div>
            <div class="fw-bold text-dark mt-1 small">
                <?= $user['last_login_at'] ? date('M d, Y h:i A', strtotime($user['last_login_at'])) : '<span class="text-muted fw-normal">Never logged in</span>' ?>
            </div>
        </div>
    </div>
</div>

<form action="<?= url('/users/' . $user['id']) ?>" method="POST" id="editUserForm">
    <?= csrf_field() ?>

    <!-- Section 1: Account Credentials & Privileges -->
    <div class="card card-premium mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                <i class="bi bi-shield-lock-fill me-2 text-primary"></i>Credentials & Access Privileges
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold text-secondary small">Username <span class="text-muted fw-normal">(Cannot be changed)</span></label>
                    <input type="text" class="form-control bg-light font-monospace text-secondary" value="<?= h($user['username']) ?>" readonly disabled>
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="role" class="form-label fw-semibold text-secondary small">Access Privilege <span class="text-danger">*</span></label>
                    <?php if ($user['id'] == $_SESSION['user_id'] || $_SESSION['user_id'] != 1): ?>
                        <select id="role" class="form-select bg-light text-muted" disabled>
                            <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff Personnel</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>><?= ($user['id'] == 1) ? 'Main Administrator' : 'Co-Administrator' ?></option>
                        </select>
                        <input type="hidden" name="role" value="<?= h($user['role']) ?>">
                    <?php else: ?>
                        <select name="role" id="role" class="form-select bg-light" required>
                            <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff Personnel</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Co-Administrator</option>
                        </select>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Personal Demographics -->
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
                    <input type="text" name="first_name" id="first_name" class="form-control" value="<?= h($user['first_name']) ?>" required>
                </div>

                <div class="col-12 col-md-4">
                    <label for="middle_name" class="form-label fw-semibold text-secondary small">Middle Name <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="text" name="middle_name" id="middle_name" class="form-control" value="<?= h($user['middle_name'] ?? '') ?>" placeholder="Optional">
                </div>

                <div class="col-12 col-md-4">
                    <label for="last_name" class="form-label fw-semibold text-secondary small">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="form-control" value="<?= h($user['last_name']) ?>" required>
                </div>

                <div class="col-12 col-md-6">
                    <label for="email" class="form-label fw-semibold text-secondary small">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= h($user['email'] ?? '') ?>" placeholder="e.g. email@example.com">
                </div>

                <div class="col-12 col-md-6">
                    <label for="contact_no" class="form-label fw-semibold text-secondary small">Contact Number <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="text" name="contact_no" id="contact_no" class="form-control" value="<?= h($user['contact_no'] ?? '') ?>" placeholder="e.g. 09171234567">
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Professional & Employment Details -->
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
                    <input type="text" name="job_title" id="job_title" class="form-control" value="<?= h($user['job_title'] ?? '') ?>" placeholder="e.g. Nurse, BHW, Midwife">
                </div>
                
                <div class="col-12 col-md-4">
                    <label for="employee_id" class="form-label fw-semibold text-secondary small">Employee ID / PRC License No.</label>
                    <input type="text" name="employee_id" id="employee_id" class="form-control" value="<?= h($user['employee_id'] ?? '') ?>" placeholder="e.g. EMP-2026-004 or PRC-09823">
                </div>
                
                <div class="col-12 col-md-4">
                    <label for="department" class="form-label fw-semibold text-secondary small">Department / Clinic Unit</label>
                    <select name="department" id="department" class="form-select bg-light">
                        <option value="">-- Select Clinic Unit --</option>
                        <option value="General Consultation" <?= ($user['department'] ?? '') === 'General Consultation' ? 'selected' : '' ?>>General Consultation</option>
                        <option value="Maternal & Child Health" <?= ($user['department'] ?? '') === 'Maternal & Child Health' ? 'selected' : '' ?>>Maternal & Child Health</option>
                        <option value="Vaccination & Immunization" <?= ($user['department'] ?? '') === 'Vaccination & Immunization' ? 'selected' : '' ?>>Vaccination & Immunization</option>
                        <option value="Records & Administrative Office" <?= ($user['department'] ?? '') === 'Records & Administrative Office' ? 'selected' : '' ?>>Records & Administrative Office</option>
                        <option value="Laboratory & Diagnostics" <?= ($user['department'] ?? '') === 'Laboratory & Diagnostics' ? 'selected' : '' ?>>Laboratory & Diagnostics</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="<?= url('/users') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle-fill me-1"></i> Save Changes
        </button>
    </div>
</form>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
