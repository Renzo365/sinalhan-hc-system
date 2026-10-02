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
        <p class="text-secondary small mb-0">Register staff or administrative credentials and assign clinical designations.</p>
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

    <div class="row g-4">
        <!-- Left Column: Sticky Live Account Preview & Role Matrix -->
        <div class="col-12 col-lg-4">
            <div class="sticky-top" style="top: 1rem; z-index: 10;">
                <!-- Live Account Preview Card -->
                <div class="card card-premium mb-4 text-center">
                    <div class="card-header bg-white py-3 border-bottom text-start">
                        <h6 class="card-title mb-0 fw-bold text-primary-dark d-flex align-items-center">
                            <i class="bi bi-person-badge-fill me-2 text-primary"></i>Account Preview
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <!-- Dynamic Monogram Avatar -->
                        <div class="d-flex justify-content-center mb-3">
                            <div id="previewAvatar" 
                                 class="d-flex align-items-center justify-content-center shadow-sm rounded-circle text-white fw-bold" 
                                 style="width: 76px; height: 76px; font-size: 1.85rem; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); transition: all 0.3s ease;">
                                ?
                            </div>
                        </div>

                        <!-- Name & Username -->
                        <h5 class="fw-bold text-dark mb-1" id="previewFullName">New Health Worker</h5>
                        <div class="font-monospace text-muted small mb-3" id="previewUsername">@username</div>

                        <!-- Role & Job Title Chips -->
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                            <span id="previewRoleBadge" class="badge fw-medium" style="background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                                Staff Personnel
                            </span>
                        </div>

                        <div class="p-2 rounded bg-light border text-secondary small" id="previewJobTitle">
                            <i class="bi bi-briefcase me-1"></i> <span id="previewJobText">Staff Member</span>
                        </div>
                    </div>
                </div>

                <!-- Role Capability Matrix Card -->
                <div class="card card-premium">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title mb-0 fw-bold text-primary-dark d-flex align-items-center">
                            <i class="bi bi-shield-check me-2 text-primary"></i>Role Capability Matrix
                        </h6>
                    </div>
                    <div class="card-body p-3 small">
                        <!-- Staff Privileges -->
                        <div class="p-2 rounded mb-2 role-card-matrix" id="matrixStaff" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="fw-bold text-dark d-flex align-items-center justify-content-between mb-1">
                                <span><i class="bi bi-person-check-fill text-secondary me-1"></i> Staff Personnel</span>
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">Clinical</span>
                            </div>
                            <ul class="mb-0 ps-3 text-muted" style="font-size: 0.76rem;">
                                <li>Patient registration & records</li>
                                <li>Maternal Care & Well-Baby/EPI logs</li>
                                <li>Daily Queue triage & consultations</li>
                                <li>Standard clinical reports</li>
                            </ul>
                        </div>

                        <!-- Admin Privileges -->
                        <div class="p-2 rounded mb-2 role-card-matrix" id="matrixAdmin" style="border: 1px solid #99f6e4; background: #f0fdfa;">
                            <div class="fw-bold text-teal d-flex align-items-center justify-content-between mb-1" style="color: #0d9488;">
                                <span><i class="bi bi-shield-lock-fill me-1" style="color: #0d9488;"></i> Administrator</span>
                                <span class="badge fw-semibold" style="font-size: 0.65rem; background-color: #e6fffa; color: #0d9488; border: 1px solid #99f6e4;">Elevated</span>
                            </div>
                            <ul class="mb-0 ps-3 text-secondary" style="font-size: 0.76rem;">
                                <li>All Staff operational capabilities</li>
                                <li>Create & manage Staff user accounts</li>
                                <li>Password resets & temporary unlock</li>
                                <li>Access to Archived records hub</li>
                            </ul>
                        </div>

                        <!-- Super Admin Note -->
                        <div class="p-2 rounded role-card-matrix" id="matrixSuperAdmin" style="border: 1px solid #ccfbf1; background: #f0fdfa; opacity: 0.7;">
                            <div class="fw-bold d-flex align-items-center justify-content-between mb-1" style="color: #0f766e;">
                                <span><i class="bi bi-shield-shaded me-1" style="color: #0f766e;"></i> Super Admin</span>
                                <span class="badge" style="font-size: 0.65rem; background-color: #0f766e; color: #fff;">Full System</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.74rem;">
                                Controls system backup, security audit logs, and administrative elevations.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Form Inputs -->
        <div class="col-12 col-lg-8">
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
                            <label for="username" class="form-label fw-semibold text-secondary small">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0">@</span>
                                <input type="text" 
                                       name="username" 
                                       id="username" 
                                       class="form-control border-start-0 font-monospace" 
                                       placeholder="e.g. m_cruz" 
                                       value="<?= h($old['username'] ?? '') ?>" 
                                       required 
                                       autocomplete="username"
                                       pattern="^[a-zA-Z0-9_]{3,20}$" 
                                       title="Username must be alphanumeric, between 3 to 20 characters.">
                            </div>
                            <div class="form-text small text-muted">Unique account handle (3-20 chars, letters, numbers, underscore).</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="role" class="form-label fw-semibold text-secondary small">Access Privilege <span class="text-danger">*</span></label>
                            <select name="role" id="role" class="form-select bg-light" required>
                                <option value="staff" <?= (isset($old['role']) && $old['role'] === 'staff') ? 'selected' : '' ?>>Staff Personnel</option>
                                <?php if (is_super_admin()): ?>
                                    <option value="admin" <?= (isset($old['role']) && $old['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
                                <?php endif; ?>
                            </select>
                            <div class="form-text small text-muted">
                                <?php if (is_super_admin()): ?>
                                    Administrators can manage staff credentials and review audit logs.
                                <?php else: ?>
                                    Only the Super Administrator can elevate accounts to Admin.
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Initial Password -->
                        <div class="col-12 col-md-6">
                            <label for="password" class="form-label fw-semibold text-secondary small">Initial Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       class="form-control border-end-0" 
                                       placeholder="Min 8 characters" 
                                       required 
                                       autocomplete="new-password"
                                       data-lpignore="true"
                                       minlength="8">
                                <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted">Minimum 8 characters.</div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-12 col-md-6">
                            <label for="confirm_password" class="form-label fw-semibold text-secondary small">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" 
                                       name="confirm_password" 
                                       id="confirm_password" 
                                       class="form-control border-end-0" 
                                       placeholder="Repeat password" 
                                       required 
                                       autocomplete="new-password"
                                       data-lpignore="true"
                                       minlength="8">
                                <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted" id="confirmPasswordMatchText">Must match initial password.</div>
                        </div>

                        <!-- Live Password Strength Meter -->
                        <div class="col-12" id="passwordStrengthSection">
                            <div class="p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold text-secondary">Password Security Strength:</span>
                                    <span class="badge bg-secondary" id="strengthBadge">Too Short</span>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div id="strengthProgressBar" class="progress-bar bg-danger" role="progressbar" style="width: 0%; transition: width 0.3s ease;"></div>
                                </div>
                                <div class="row g-2 small text-muted" style="font-size: 0.75rem;">
                                    <div class="col-12 col-sm-4" id="reqLength">
                                        <i class="bi bi-circle text-muted me-1"></i> At least 8 characters
                                    </div>
                                    <div class="col-12 col-sm-4" id="reqComplexity">
                                        <i class="bi bi-circle text-muted me-1"></i> Letters & numbers/symbols
                                    </div>
                                    <div class="col-12 col-sm-4" id="reqMatch">
                                        <i class="bi bi-circle text-muted me-1"></i> Passwords match
                                    </div>
                                </div>
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
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       class="form-control border-start-0" 
                                       placeholder="e.g. maria.cruz@example.com" 
                                       value="<?= h($old['email'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="contact_no" class="form-label fw-semibold text-secondary small">Contact Number <span class="text-muted fw-normal">(Optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                                <input type="text" 
                                       name="contact_no" 
                                       id="contact_no" 
                                       class="form-control border-start-0" 
                                       placeholder="e.g. 09171234567" 
                                       value="<?= h($old['contact_no'] ?? '') ?>">
                            </div>
                            <div class="form-text small text-muted">Philippine mobile format (e.g. 09171234567).</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Professional Information -->
            <div class="card card-premium mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                        <i class="bi bi-briefcase-fill me-2 text-primary"></i>Professional Information
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label for="job_title" class="form-label fw-semibold text-secondary small">Clinic Job Title</label>
                            <input type="text" 
                                   name="job_title" 
                                   id="job_title" 
                                   class="form-control" 
                                   placeholder="e.g. Midwife, Nurse, BHW, Doctor" 
                                   value="<?= h($old['job_title'] ?? '') ?>">
                            <div class="form-text small text-muted">Designation or healthcare role within Barangay Sinalhan Health Center.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Footer -->
            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="<?= url('/users') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i> Save User Account
                </button>
            </div>
        </div>
    </div>
</form>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inputs
    const firstNameInput = document.getElementById('first_name');
    const lastNameInput = document.getElementById('last_name');
    const usernameInput = document.getElementById('username');
    const roleSelect = document.getElementById('role');
    const jobTitleInput = document.getElementById('job_title');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    // Preview targets
    const previewAvatar = document.getElementById('previewAvatar');
    const previewFullName = document.getElementById('previewFullName');
    const previewUsername = document.getElementById('previewUsername');
    const previewRoleBadge = document.getElementById('previewRoleBadge');
    const previewJobText = document.getElementById('previewJobText');

    // Matrix cards
    const matrixStaff = document.getElementById('matrixStaff');
    const matrixAdmin = document.getElementById('matrixAdmin');

    // Strength elements
    const strengthBadge = document.getElementById('strengthBadge');
    const strengthProgressBar = document.getElementById('strengthProgressBar');
    const reqLength = document.getElementById('reqLength');
    const reqComplexity = document.getElementById('reqComplexity');
    const reqMatch = document.getElementById('reqMatch');
    const confirmPasswordMatchText = document.getElementById('confirmPasswordMatchText');

    function updatePreview() {
        const first = firstNameInput ? firstNameInput.value.trim() : '';
        const last = lastNameInput ? lastNameInput.value.trim() : '';
        const user = usernameInput ? usernameInput.value.trim() : '';
        const role = roleSelect ? roleSelect.value : 'staff';
        const job = jobTitleInput ? jobTitleInput.value.trim() : '';

        // Monogram
        let initials = '';
        if (first && last) {
            initials = (first[0] + last[0]).toUpperCase();
        } else if (first) {
            initials = first.substring(0, 2).toUpperCase();
        } else if (user) {
            initials = user.substring(0, 2).toUpperCase();
        } else {
            initials = '?';
        }
        previewAvatar.textContent = initials;

        // Full Name
        if (first || last) {
            previewFullName.textContent = `${last ? last + ', ' : ''}${first || ''}`.trim();
        } else {
            previewFullName.textContent = 'New Health Worker';
        }

        // Username
        previewUsername.textContent = user ? `@${user}` : '@username';

        // Role badge & Matrix highlight
        if (role === 'admin') {
            previewRoleBadge.textContent = 'Administrator';
            previewRoleBadge.style.backgroundColor = '#e6fffa';
            previewRoleBadge.style.color = '#0d9488';
            previewRoleBadge.style.border = '1px solid #99f6e4';
            previewRoleBadge.className = 'badge fw-semibold';

            if (matrixAdmin) matrixAdmin.style.boxShadow = '0 0 0 2px #0d9488';
            if (matrixStaff) matrixStaff.style.boxShadow = 'none';
        } else {
            previewRoleBadge.textContent = 'Staff Personnel';
            previewRoleBadge.style.backgroundColor = '#f1f5f9';
            previewRoleBadge.style.color = '#475569';
            previewRoleBadge.style.border = '1px solid #e2e8f0';
            previewRoleBadge.className = 'badge fw-medium';

            if (matrixStaff) matrixStaff.style.boxShadow = '0 0 0 2px #0d9488';
            if (matrixAdmin) matrixAdmin.style.boxShadow = 'none';
        }

        // Job Title
        previewJobText.textContent = job || 'Staff Member';
    }

    function checkPasswordStrength() {
        const pass = passwordInput ? passwordInput.value : '';
        const confirm = confirmPasswordInput ? confirmPasswordInput.value : '';

        const hasLength = pass.length >= 8;
        const hasLetters = /[a-zA-Z]/.test(pass);
        const hasNumbersOrSpecial = /[0-9!@#$%^&*(),.?":{}|<>]/.test(pass);
        const isComplex = hasLetters && hasNumbersOrSpecial;
        const doesMatch = pass.length > 0 && pass === confirm;

        // Update Checklist Icons
        updateChecklist(reqLength, hasLength);
        updateChecklist(reqComplexity, isComplex);
        updateChecklist(reqMatch, doesMatch);

        // Match feedback
        if (confirm.length > 0) {
            if (doesMatch) {
                confirmPasswordMatchText.textContent = '✓ Passwords match successfully.';
                confirmPasswordMatchText.className = 'form-text small text-success fw-semibold';
            } else {
                confirmPasswordMatchText.textContent = '✗ Passwords do not match.';
                confirmPasswordMatchText.className = 'form-text small text-danger fw-semibold';
            }
        } else {
            confirmPasswordMatchText.textContent = 'Must match initial password.';
            confirmPasswordMatchText.className = 'form-text small text-muted';
        }

        // Score
        let score = 0;
        if (hasLength) score++;
        if (hasLetters && /[A-Z]/.test(pass) && /[a-z]/.test(pass)) score++;
        if (hasNumbersOrSpecial) score++;
        if (pass.length >= 12) score++;

        if (pass.length === 0) {
            strengthBadge.textContent = 'Empty';
            strengthBadge.className = 'badge bg-secondary';
            strengthProgressBar.style.width = '0%';
            strengthProgressBar.className = 'progress-bar bg-secondary';
        } else if (score <= 1) {
            strengthBadge.textContent = 'Weak';
            strengthBadge.className = 'badge bg-danger';
            strengthProgressBar.style.width = '25%';
            strengthProgressBar.className = 'progress-bar bg-danger';
        } else if (score === 2) {
            strengthBadge.textContent = 'Fair';
            strengthBadge.className = 'badge bg-warning text-dark';
            strengthProgressBar.style.width = '50%';
            strengthProgressBar.className = 'progress-bar bg-warning';
        } else if (score === 3) {
            strengthBadge.textContent = 'Good';
            strengthBadge.className = 'badge bg-info text-dark';
            strengthProgressBar.style.width = '75%';
            strengthProgressBar.className = 'progress-bar bg-info';
        } else {
            strengthBadge.textContent = 'Strong';
            strengthBadge.className = 'badge bg-success';
            strengthProgressBar.style.width = '100%';
            strengthProgressBar.className = 'progress-bar bg-success';
        }
    }

    function updateChecklist(element, isValid) {
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

    // Bind listeners
    [firstNameInput, lastNameInput, usernameInput, roleSelect, jobTitleInput].forEach(function(el) {
        if (el) {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        }
    });

    [passwordInput, confirmPasswordInput].forEach(function(el) {
        if (el) {
            el.addEventListener('input', checkPasswordStrength);
        }
    });

    // Initial run
    updatePreview();
    checkPasswordStrength();
});
</script>
