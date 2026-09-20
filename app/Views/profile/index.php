<?php
$title = 'My Profile';
$breadcrumbs = [
    'My Profile' => null
];
require dirname(__DIR__) . '/layout/header.php';

$profileErrors = $_SESSION['profile_errors'] ?? [];
$passwordErrors = $_SESSION['password_errors'] ?? [];
$oldProfile = $_SESSION['old_profile'] ?? [];
unset($_SESSION['profile_errors'], $_SESSION['password_errors'], $_SESSION['old_profile']);

// Derive user values with fallback to old inputs if validation failed
$firstName = $oldProfile['first_name'] ?? $user['first_name'] ?? '';
$middleName = $oldProfile['middle_name'] ?? $user['middle_name'] ?? '';
$lastName = $oldProfile['last_name'] ?? $user['last_name'] ?? '';
$email = $oldProfile['email'] ?? $user['email'] ?? '';
$contactNo = $oldProfile['contact_no'] ?? $user['contact_no'] ?? '';

// Format full name and initial
$fullName = trim($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']);
$avatarInitial = strtoupper(mb_substr(trim($user['first_name'] ?: 'U'), 0, 1, 'UTF-8'));

// Format Role Display
$roleDisplay = 'Staff Member';
$roleBadgeClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
if ($user['role'] === 'super_admin') {
    $roleDisplay = 'Super Administrator';
    $roleBadgeClass = 'bg-primary-subtle text-primary border border-primary-subtle fw-bold';
} elseif ($user['role'] === 'admin') {
    $roleDisplay = 'Administrator';
    $roleBadgeClass = 'bg-info-subtle text-info border border-info-subtle fw-bold';
}
?>

<div class="mb-4">
    <h2 class="h3 mb-1 fw-bold text-primary-dark">User Account Profile</h2>
    <p class="text-secondary small mb-0">View your assigned account role, update personal contact details, and manage your access password.</p>
</div>

<div class="row g-4 mb-5">
    <!-- Left Column: Account Overview Card -->
    <div class="col-12 col-lg-4 col-md-5">
        <div class="card card-premium shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body p-4 text-center">
                <!-- Large Circular Avatar with Initial -->
                <div class="mx-auto mb-3" 
                     style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.25rem; font-weight: 700; color: #fff; background-color: #0d9488; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.25);">
                    <?= h($avatarInitial) ?>
                </div>

                <h3 class="h5 fw-bold text-dark mb-1"><?= h($fullName) ?></h3>
                <div class="mb-3">
                    <span class="badge <?= $roleBadgeClass ?> px-3 py-1 rounded-pill small">
                        <?= h($roleDisplay) ?>
                    </span>
                </div>

                <hr class="my-3 text-muted">

                <!-- Read-only Details List -->
                <div class="text-start small">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-person-badge me-2 text-primary"></i>Username</span>
                        <span class="fw-semibold text-dark"><?= h($user['username']) ?></span>
                    </div>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-card-heading me-2 text-primary"></i>Employee ID</span>
                        <span class="fw-semibold text-dark"><?= !empty($user['employee_id']) ? h($user['employee_id']) : '<span class="text-muted fst-italic">Not Assigned</span>' ?></span>
                    </div>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-building me-2 text-primary"></i>Department</span>
                        <span class="fw-semibold text-dark"><?= !empty($user['department']) ? h($user['department']) : 'Health Center Staff' ?></span>
                    </div>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-briefcase me-2 text-primary"></i>Job Title</span>
                        <span class="fw-semibold text-dark"><?= !empty($user['job_title']) ? h($user['job_title']) : 'Health Worker' ?></span>
                    </div>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-shield-check me-2 text-primary"></i>Status</span>
                        <?php if ($user['status'] === 'active'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                <i class="bi bi-check-circle-fill me-1"></i>Active
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                <i class="bi bi-x-circle-fill me-1"></i>Inactive
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-calendar3 me-2 text-primary"></i>Member Since</span>
                        <span class="fw-semibold text-dark"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                    </div>

                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted"><i class="bi bi-clock-history me-2 text-primary"></i>Last Login</span>
                        <span class="fw-semibold text-dark"><?= !empty($user['last_login_at']) ? date('M d, Y h:i A', strtotime($user['last_login_at'])) : '<span class="text-muted fst-italic">Never</span>' ?></span>
                    </div>
                </div>

                <!-- Admin Constraint Note -->
                <div class="alert alert-light border text-start p-3 mt-3 mb-0 small text-muted" style="border-radius: 8px;">
                    <div class="d-flex">
                        <i class="bi bi-info-circle text-primary fs-5 me-2 flex-shrink-0"></i>
                        <div>
                            <strong>Administrative Access:</strong> System role, username, department, and employee ID are managed directly by administrative personnel.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Personal Details & Password Forms -->
    <div class="col-12 col-lg-8 col-md-7">
        <!-- Card 1: Personal & Contact Details -->
        <div class="card card-premium shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fs-6 fw-bold text-primary-dark d-flex align-items-center">
                    <i class="bi bi-person-lines-fill me-2 text-primary"></i>Personal & Contact Information
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($profileErrors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 8px;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                            <div>
                                <strong class="d-block small fw-bold">Please correct the following errors:</strong>
                                <ul class="mb-0 ps-3 small">
                                    <?php foreach ($profileErrors as $err): ?>
                                        <li><?= h($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= url('/profile/update') ?>" method="POST" id="profileUpdateForm">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="first_name" class="form-label fw-semibold text-secondary small">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="first_name" 
                                   id="first_name" 
                                   class="form-control" 
                                   value="<?= h($firstName) ?>" 
                                   required 
                                   maxlength="50">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="middle_name" class="form-label fw-semibold text-secondary small">
                                Middle Name <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <input type="text" 
                                   name="middle_name" 
                                   id="middle_name" 
                                   class="form-control" 
                                   value="<?= h($middleName) ?>" 
                                   maxlength="50">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="last_name" class="form-label fw-semibold text-secondary small">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="last_name" 
                                   id="last_name" 
                                   class="form-control" 
                                   value="<?= h($lastName) ?>" 
                                   required 
                                   maxlength="50">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold text-secondary small">
                                Email Address <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       class="form-control" 
                                       placeholder="user@example.com" 
                                       value="<?= h($email) ?>" 
                                       maxlength="100">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="contact_no" class="form-label fw-semibold text-secondary small">
                                Contact Number <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="text" 
                                       name="contact_no" 
                                       id="contact_no" 
                                       class="form-control" 
                                       placeholder="e.g. 0917-123-4567" 
                                       value="<?= h($contactNo) ?>" 
                                       maxlength="20">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center">
                            <i class="bi bi-check2-circle me-2"></i>Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 2: Security & Password Settings -->
        <div class="card card-premium shadow-sm border-0 mb-4" id="password-settings" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fs-6 fw-bold text-primary-dark d-flex align-items-center">
                    <i class="bi bi-shield-lock-fill me-2 text-primary"></i>Security & Password Settings
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($passwordErrors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 8px;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                            <div>
                                <strong class="d-block small fw-bold">Password update could not be completed:</strong>
                                <ul class="mb-0 ps-3 small">
                                    <?php foreach ($passwordErrors as $err): ?>
                                        <li><?= h($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= url('/profile/password') ?>" method="POST" id="passwordUpdateForm">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <!-- Current Password -->
                        <div class="col-12">
                            <label for="current_password" class="form-label fw-semibold text-secondary small">
                                Current Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       name="current_password" 
                                       id="current_password" 
                                       class="form-control" 
                                       placeholder="Enter your current password" 
                                       required 
                                       autocomplete="current-password">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" title="Show password" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="col-12 col-md-6">
                            <label for="new_password" class="form-label fw-semibold text-secondary small">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       name="new_password" 
                                       id="new_password" 
                                       class="form-control" 
                                       placeholder="Minimum 8 characters" 
                                       required 
                                       minlength="8" 
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" title="Show password" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted">
                                <i class="bi bi-info-circle me-1"></i>Must be at least 8 characters long.
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="col-12 col-md-6">
                            <label for="confirm_password" class="form-label fw-semibold text-secondary small">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       name="confirm_password" 
                                       id="confirm_password" 
                                       class="form-control" 
                                       placeholder="Re-enter your new password" 
                                       required 
                                       minlength="8" 
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" title="Show password" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center">
                            <i class="bi bi-key-fill me-2"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
