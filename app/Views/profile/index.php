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
$jobTitle = $oldProfile['job_title'] ?? $user['job_title'] ?? '';

// Format full name and two-letter initial monogram
$fullName = trim($firstName . ' ' . ($middleName ? $middleName . ' ' : '') . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) ?: 'U';

// Format Role Display with Healthcare Teal Tokens
if ($user['role'] === 'super_admin') {
    $roleDisplay = 'Super Administrator';
    $roleBadgeStyle = 'background-color: #0f766e; color: #ffffff;';
    $roleBadgeClass = 'badge shadow-sm px-3 py-1.5';
} elseif ($user['role'] === 'admin') {
    $roleDisplay = 'Administrator';
    $roleBadgeStyle = 'background-color: #e6fffa; color: #0d9488; border: 1px solid #99f6e4;';
    $roleBadgeClass = 'badge fw-semibold px-3 py-1.5';
} else {
    $roleDisplay = 'Staff Personnel';
    $roleBadgeStyle = 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
    $roleBadgeClass = 'badge fw-medium px-3 py-1.5';
}
?>

<div class="mb-4">
    <h2 class="h3 mb-1 fw-bold text-primary-dark">User Account Profile</h2>
    <p class="text-secondary small mb-0">View your assigned account role, update personal contact details, and manage your access password.</p>
</div>

<div class="row g-4 mb-5">
    <!-- Left Column: Sticky Account Overview Card -->
    <div class="col-12 col-lg-4 col-md-5">
        <div class="sticky-top" style="top: 1rem; z-index: 10;">
            <div class="card card-premium shadow-sm border-0 mb-4" style="border-radius: 12px;">
                <div class="card-body p-4 text-center">
                    <!-- Circular Avatar with Two-Letter Initials -->
                    <div class="mx-auto mb-3" 
                         id="profileAvatar"
                         style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; color: #fff; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); box-shadow: 0 4px 10px rgba(13, 148, 136, 0.25); transition: all 0.3s ease;">
                        <?= h($initials) ?>
                    </div>

                    <h3 class="h5 fw-bold text-dark mb-1" id="profileFullName"><?= h($fullName ?: $user['username']) ?></h3>
                    <div class="mb-3">
                        <span class="<?= $roleBadgeClass ?>" style="<?= $roleBadgeStyle ?>">
                            <?= h($roleDisplay) ?>
                        </span>
                    </div>

                    <div class="p-2 rounded bg-light border text-secondary small mb-3">
                        <i class="bi bi-briefcase me-1 text-primary"></i> <span id="profileJobText"><?= !empty($jobTitle) ? h($jobTitle) : 'Staff Member' ?></span>
                    </div>

                    <hr class="my-3 text-muted">

                    <!-- Read-only Details List -->
                    <div class="text-start small">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted"><i class="bi bi-hash me-2 text-primary"></i>Account ID</span>
                            <span class="fw-bold font-monospace text-dark">#<?= h($user['id']) ?></span>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted"><i class="bi bi-person-badge me-2 text-primary"></i>Username</span>
                            <span class="fw-semibold text-dark font-monospace">@<?= h($user['username']) ?></span>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted"><i class="bi bi-shield-check me-2 text-primary"></i>Status</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Active</span>
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
                            <i class="bi bi-shield-check text-primary fs-5 me-2 flex-shrink-0"></i>
                            <div>
                                <strong>Account Permissions:</strong> System access level and username are governed by administrative personnel. You can freely update your clinical job designation and contact details.
                            </div>
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
                                   placeholder="Optional"
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

                        <div class="col-12 col-md-4">
                            <label for="job_title" class="form-label fw-semibold text-secondary small">
                                Clinic Job Title <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-briefcase"></i></span>
                                <input type="text" 
                                       name="job_title" 
                                       id="job_title" 
                                       class="form-control border-start-0" 
                                       placeholder="e.g. Midwife, Nurse, BHW" 
                                       value="<?= h($jobTitle) ?>" 
                                       maxlength="50">
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="email" class="form-label fw-semibold text-secondary small">
                                Email Address <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       class="form-control border-start-0" 
                                       placeholder="user@example.com" 
                                       value="<?= h($email) ?>" 
                                       maxlength="100">
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="contact_no" class="form-label fw-semibold text-secondary small">
                                Contact Number <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                                <input type="text" 
                                       name="contact_no" 
                                       id="contact_no" 
                                       class="form-control border-start-0" 
                                       placeholder="e.g. 09171234567" 
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
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key-fill"></i></span>
                                <input type="password" 
                                       name="current_password" 
                                       id="current_password" 
                                       class="form-control border-start-0 border-end-0" 
                                       placeholder="Enter your current password" 
                                       required 
                                       autocomplete="current-password">
                                <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" title="Show password" aria-label="Show password">
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
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" 
                                       name="new_password" 
                                       id="new_password" 
                                       class="form-control border-start-0 border-end-0" 
                                       placeholder="Minimum 8 characters" 
                                       required 
                                       minlength="8" 
                                       autocomplete="new-password">
                                <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" title="Show password" aria-label="Show password">
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
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" 
                                       name="confirm_password" 
                                       id="confirm_password" 
                                       class="form-control border-start-0 border-end-0" 
                                       placeholder="Re-enter your new password" 
                                       required 
                                       minlength="8" 
                                       autocomplete="new-password">
                                <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" title="Show password" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted" id="profilePasswordMatchText">
                                Re-enter the new password to confirm.
                            </div>
                        </div>

                        <!-- Real-Time Password Security Feedback -->
                        <div class="col-12" id="passwordChecklistSection">
                            <div class="p-3 bg-light rounded border">
                                <div class="row g-2 small text-muted" style="font-size: 0.75rem;">
                                    <div class="col-12 col-sm-3" id="reqLength">
                                        <i class="bi bi-circle text-muted me-1"></i> At least 8 characters
                                    </div>
                                    <div class="col-12 col-sm-3" id="reqComplexity">
                                        <i class="bi bi-circle text-muted me-1"></i> Letters & numbers/symbols
                                    </div>
                                    <div class="col-12 col-sm-3" id="reqDifferent">
                                        <i class="bi bi-circle text-muted me-1"></i> New & different
                                    </div>
                                    <div class="col-12 col-sm-3" id="reqMatch">
                                        <i class="bi bi-circle text-muted me-1"></i> Passwords match
                                    </div>
                                </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live Profile Preview Sync
    const firstNameInput = document.getElementById('first_name');
    const lastNameInput = document.getElementById('last_name');
    const jobTitleInput = document.getElementById('job_title');

    const profileAvatar = document.getElementById('profileAvatar');
    const profileFullName = document.getElementById('profileFullName');
    const profileJobText = document.getElementById('profileJobText');

    function syncProfilePreview() {
        const first = firstNameInput ? firstNameInput.value.trim() : '';
        const last = lastNameInput ? lastNameInput.value.trim() : '';
        const job = jobTitleInput ? jobTitleInput.value.trim() : '';

        // Monogram
        let initials = '';
        if (first && last) {
            initials = (first[0] + last[0]).toUpperCase();
        } else if (first) {
            initials = first.substring(0, 2).toUpperCase();
        } else {
            initials = '<?= h($initials) ?>';
        }
        if (profileAvatar) profileAvatar.textContent = initials;

        // Full Name
        if (profileFullName) {
            if (first || last) {
                profileFullName.textContent = `${first} ${last}`.trim();
            } else {
                profileFullName.textContent = '<?= h($user['username']) ?>';
            }
        }

        // Job Title
        if (profileJobText) {
            profileJobText.textContent = job || 'Staff Member';
        }
    }

    [firstNameInput, lastNameInput, jobTitleInput].forEach(function(el) {
        if (el) {
            el.addEventListener('input', syncProfilePreview);
        }
    });

    // Real-Time Password Match & Difference Feedback
    const currentPasswordInput = document.getElementById('current_password');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const matchText = document.getElementById('profilePasswordMatchText');
    const reqLength = document.getElementById('reqLength');
    const reqComplexity = document.getElementById('reqComplexity');
    const reqDifferent = document.getElementById('reqDifferent');
    const reqMatch = document.getElementById('reqMatch');

    function checkPasswordMatch() {
        const currentPass = currentPasswordInput ? currentPasswordInput.value : '';
        const newPass = newPasswordInput ? newPasswordInput.value : '';
        const confirmPass = confirmPasswordInput ? confirmPasswordInput.value : '';

        const hasLength = newPass.length >= 8;
        const hasLetters = /[a-zA-Z]/.test(newPass);
        const hasNumbersOrSpecial = /[0-9!@#$%^&*(),.?":{}|<>]/.test(newPass);
        const isComplex = hasLetters && hasNumbersOrSpecial;
        const isDifferent = newPass.length > 0 && (currentPass.length === 0 || newPass !== currentPass);
        const doesMatch = newPass.length > 0 && newPass === confirmPass;

        // Checklist icons
        updateIndicator(reqLength, hasLength);
        updateIndicator(reqComplexity, isComplex);
        updateIndicator(reqDifferent, isDifferent);
        updateIndicator(reqMatch, doesMatch);

        // Feedback text
        if (currentPass.length > 0 && newPass.length > 0 && newPass === currentPass) {
            matchText.textContent = '✗ New password cannot be the same as your current password.';
            matchText.className = 'form-text small text-danger fw-semibold';
        } else if (confirmPass.length > 0) {
            if (doesMatch) {
                matchText.textContent = '✓ Passwords match successfully.';
                matchText.className = 'form-text small text-success fw-semibold';
            } else {
                matchText.textContent = '✗ Passwords do not match.';
                matchText.className = 'form-text small text-danger fw-semibold';
            }
        } else {
            matchText.textContent = 'Re-enter the new password to confirm.';
            matchText.className = 'form-text small text-muted';
        }
    }

    function updateIndicator(element, isValid) {
        if (!element) return;
        const icon = element.querySelector('i');
        if (isValid) {
            element.classList.remove('text-muted');
            element.classList.add('text-success', 'fw-semibold');
            if (icon) {
                icon.className = 'bi bi-check-circle-fill text-success me-1';
            }
        } else {
            element.classList.remove('text-success', 'fw-semibold');
            element.classList.add('text-muted');
            if (icon) {
                icon.className = 'bi bi-circle text-muted me-1';
            }
        }
    }

    [currentPasswordInput, newPasswordInput, confirmPasswordInput].forEach(function(el) {
        if (el) {
            el.addEventListener('input', checkPasswordMatch);
        }
    });
});
</script>
