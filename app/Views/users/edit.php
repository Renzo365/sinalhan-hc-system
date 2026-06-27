<?php
$title = 'Edit User Account';
$breadcrumbs = [
    'Users' => '/users',
    'Edit User' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/users') ?>" class="btn btn-outline-secondary btn-sm px-3 mb-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Accounts list
    </a>
    <h2 class="h3 mb-1 fw-bold text-primary-dark">Edit User Account: <?= h($user['username']) ?></h2>
    <p class="text-secondary small">Update demographics, assign role privileges, or modify account operational statuses.</p>
</div>

<div class="card card-premium shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h3 class="card-title h6 mb-0 fw-bold text-dark">
            <i class="bi bi-person-lines-fill text-primary me-2"></i>Account Details
        </h3>
    </div>
    
    <form action="<?= url('/users/' . $user['id']) ?>" method="POST">
        <?= csrf_field() ?>
        
        <div class="card-body p-4 bg-white">
            <div class="row g-4">
                <!-- Username (Readonly) -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold text-secondary small">Username</label>
                    <input type="text" class="form-control bg-light font-monospace text-secondary" value="<?= h($user['username']) ?>" readonly disabled>
                </div>
                
                <!-- Role selection -->
                <div class="col-12 col-md-3">
                    <label for="role" class="form-label fw-semibold text-secondary small">Access Privilege <span class="text-danger">*</span></label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff Personnel</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>

                <!-- Status selection -->
                <div class="col-12 col-md-3">
                    <label for="status" class="form-label fw-semibold text-secondary small">Account Status <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>

                <!-- First Name -->
                <div class="col-12 col-md-4">
                    <label for="first_name" class="form-label fw-semibold text-secondary small">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="form-control" value="<?= h($user['first_name']) ?>" required>
                </div>

                <!-- Middle Name -->
                <div class="col-12 col-md-4">
                    <label for="middle_name" class="form-label fw-semibold text-secondary small">Middle Name</label>
                    <input type="text" name="middle_name" id="middle_name" class="form-control" value="<?= h($user['middle_name'] ?? '') ?>" placeholder="Optional">
                </div>

                <!-- Last Name -->
                <div class="col-12 col-md-4">
                    <label for="last_name" class="form-label fw-semibold text-secondary small">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="form-control" value="<?= h($user['last_name']) ?>" required>
                </div>

                <!-- Email -->
                <div class="col-12 col-md-6">
                    <label for="email" class="form-label fw-semibold text-secondary small">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= h($user['email'] ?? '') ?>" placeholder="e.g. email@example.com">
                </div>

                <!-- Contact No -->
                <div class="col-12 col-md-6">
                    <label for="contact_no" class="form-label fw-semibold text-secondary small">Contact Number</label>
                    <input type="text" name="contact_no" id="contact_no" class="form-control" value="<?= h($user['contact_no'] ?? '') ?>" placeholder="e.g. 09171234567">
                </div>

                <!-- Job Title -->
                <div class="col-12 col-md-6">
                    <label for="job_title" class="form-label fw-semibold text-secondary small">Clinic Job Title</label>
                    <input type="text" name="job_title" id="job_title" class="form-control" value="<?= h($user['job_title'] ?? '') ?>" placeholder="e.g. Nurse, BHW, Midwife">
                </div>

                <!-- Force Password Reset Flag -->
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <div class="form-check form-switch mt-md-4">
                        <input type="hidden" name="must_change_password" value="0">
                        <input class="form-check-input" type="checkbox" name="must_change_password" id="must_change_password" value="1" <?= $user['must_change_password'] == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold text-secondary small ms-2" for="must_change_password">
                            Force password reset on next login attempt
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer bg-light py-3 border-0 d-flex justify-content-end gap-2" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <a href="<?= url('/users') ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
