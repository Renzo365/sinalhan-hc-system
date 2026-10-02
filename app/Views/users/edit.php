<?php
$title = 'Edit User Account';
$breadcrumbs = [
    'User Accounts' => '/users',
    'Edit User' => null
];
require dirname(__DIR__) . '/layout/header.php';

$old = $_SESSION['old_input'] ?? [];
$errors = $_SESSION['form_errors'] ?? [];
$resetErrors = $_SESSION['reset_password_errors'] ?? [];
unset($_SESSION['old_input'], $_SESSION['form_errors'], $_SESSION['reset_password_errors'], $_SESSION['reset_password_target_id']);

$currentRole = $old['role'] ?? $user['role'];
$canChangeRole = is_super_admin() && ($user['id'] != $_SESSION['user_id']) && ($user['role'] !== 'super_admin');
$canResetPassword = is_super_admin() || ($user['role'] !== 'super_admin' && $user['role'] !== 'admin') || ((int)$user['id'] === (int)$_SESSION['user_id']);
$firstName = $old['first_name'] ?? $user['first_name'];
$lastName = $old['last_name'] ?? $user['last_name'];
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) ?: 'U';
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

<div class="row g-4">
    <!-- Left Column: Sticky Account Summary & Role Matrix -->
        <div class="col-12 col-lg-4">
            <div class="sticky-top" style="top: 1rem; z-index: 10;">
                <!-- Account Overview Card -->
                <div class="card card-premium mb-4 text-center">
                    <div class="card-header bg-white py-3 border-bottom text-start">
                        <h6 class="card-title mb-0 fw-bold text-primary-dark d-flex align-items-center">
                            <i class="bi bi-person-badge-fill me-2 text-primary"></i>Account Overview
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <!-- Dynamic Monogram Avatar -->
                        <div class="d-flex justify-content-center mb-3">
                            <div id="previewAvatar" 
                                 class="d-flex align-items-center justify-content-center shadow-sm rounded-circle text-white fw-bold" 
                                 style="width: 76px; height: 76px; font-size: 1.85rem; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); transition: all 0.3s ease;">
                                <?= h($initials) ?>
                            </div>
                        </div>

                        <!-- Name & Username -->
                        <h5 class="fw-bold text-dark mb-1" id="previewFullName"><?= h($lastName) ?>, <?= h($firstName) ?></h5>
                        <div class="font-monospace text-muted small mb-3" id="previewUsername">@<?= h($user['username']) ?></div>

                        <!-- Role Badge -->
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                            <?php if ($currentRole === 'super_admin'): ?>
                                <span id="previewRoleBadge" class="badge shadow-sm" style="background-color: #0f766e; color: #fff;">
                                    Super Admin
                                </span>
                            <?php elseif ($currentRole === 'admin'): ?>
                                <span id="previewRoleBadge" class="badge fw-semibold" style="background-color: #e6fffa; color: #0d9488; border: 1px solid #99f6e4;">
                                    Administrator
                                </span>
                            <?php else: ?>
                                <span id="previewRoleBadge" class="badge fw-medium" style="background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                                    Staff Personnel
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="p-2 rounded bg-light border text-secondary small mb-3" id="previewJobTitle">
                            <i class="bi bi-briefcase me-1"></i> <span id="previewJobText"><?= h($old['job_title'] ?? $user['job_title'] ?: 'Staff Member') ?></span>
                        </div>

                        <!-- System Metadata List -->
                        <div class="text-start bg-light rounded p-3 border small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Account ID:</span>
                                <span class="font-monospace fw-bold text-dark">#<?= h($user['id']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Status:</span>
                                <span>
                                    <?php if (!empty($user['deleted_at'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Archived</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Registered:</span>
                                <span class="text-dark fw-medium"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Last Login:</span>
                                <span class="text-dark fw-medium">
                                    <?= $user['last_login_at'] ? date('M d, Y h:i A', strtotime($user['last_login_at'])) : '<span class="text-muted">Never</span>' ?>
                                </span>
                            </div>
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
                        <div class="p-2 rounded mb-2 role-card-matrix" id="matrixStaff" style="border: 1px solid <?= $currentRole === 'staff' ? '#0d9488' : '#e2e8f0' ?>; background: <?= $currentRole === 'staff' ? '#f0fdfa' : '#f8fafc' ?>;">
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
                        <div class="p-2 rounded mb-2 role-card-matrix" id="matrixAdmin" style="border: 1px solid <?= $currentRole === 'admin' ? '#0d9488' : '#99f6e4' ?>; background: <?= $currentRole === 'admin' ? '#f0fdfa' : '#ffffff' ?>;">
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
                        <div class="p-2 rounded role-card-matrix" id="matrixSuperAdmin" style="border: 1px solid <?= $currentRole === 'super_admin' ? '#0f766e' : '#ccfbf1' ?>; background: <?= $currentRole === 'super_admin' ? '#f0fdfa' : '#f8fafc' ?>;">
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

        <!-- Right Column: Edit Form Cards -->
        <div class="col-12 col-lg-8">
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
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0">@</span>
                                    <input type="text" class="form-control bg-light font-monospace text-secondary border-start-0" value="<?= h($user['username']) ?>" readonly disabled>
                                </div>
                                <div class="form-text small text-muted">User handles remain constant for audit log integrity.</div>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="role" class="form-label fw-semibold text-secondary small">Access Privilege <span class="text-danger">*</span></label>
                                <?php if (!$canChangeRole): ?>
                                    <select id="role" class="form-select bg-light text-muted" disabled>
                                        <?php if ($currentRole === 'super_admin'): ?>
                                            <option value="super_admin" selected>Super Admin</option>
                                        <?php endif; ?>
                                        <option value="staff" <?= $currentRole === 'staff' ? 'selected' : '' ?>>Staff Personnel</option>
                                        <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                    </select>
                                    <input type="hidden" name="role" value="<?= h($currentRole) ?>">
                                    <div class="form-text small text-muted">
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            You cannot modify your own assigned role.
                                        <?php elseif ($user['role'] === 'super_admin'): ?>
                                            The Super Admin role cannot be altered.
                                        <?php else: ?>
                                            Only the Super Admin can change user roles.
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <select name="role" id="role" class="form-select bg-light" required>
                                        <option value="staff" <?= $currentRole === 'staff' ? 'selected' : '' ?>>Staff Personnel</option>
                                        <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                    </select>
                                    <div class="form-text small text-muted">Elevating to Admin grants access to user management and archive hubs.</div>
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
                                <input type="text" name="first_name" id="first_name" class="form-control" value="<?= h($old['first_name'] ?? $user['first_name']) ?>" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="middle_name" class="form-label fw-semibold text-secondary small">Middle Name <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="text" name="middle_name" id="middle_name" class="form-control" value="<?= h($old['middle_name'] ?? $user['middle_name'] ?? '') ?>" placeholder="Optional">
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="last_name" class="form-label fw-semibold text-secondary small">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name" class="form-control" value="<?= h($old['last_name'] ?? $user['last_name']) ?>" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label fw-semibold text-secondary small">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control border-start-0" value="<?= h($old['email'] ?? $user['email'] ?? '') ?>" placeholder="e.g. email@example.com">
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="contact_no" class="form-label fw-semibold text-secondary small">Contact Number <span class="text-muted fw-normal">(Optional)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="contact_no" id="contact_no" class="form-control border-start-0" value="<?= h($old['contact_no'] ?? $user['contact_no'] ?? '') ?>" placeholder="e.g. 09171234567">
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
                                <input type="text" name="job_title" id="job_title" class="form-control" value="<?= h($old['job_title'] ?? $user['job_title'] ?? '') ?>" placeholder="e.g. Midwife, Nurse, BHW, Doctor">
                                <div class="form-text small text-muted">Designation or healthcare role within Barangay Sinalhan Health Center.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="<?= url('/users') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i> Save Changes
                    </button>
                </div>
            </form>

            <?php if ($canResetPassword): ?>
            <!-- Section 4: Administrative Password Reset Card -->
            <div class="card card-premium mb-5 border-warning-subtle shadow-sm" id="password-reset-section">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0 fs-6 fw-bold text-primary-dark d-flex align-items-center">
                        <i class="bi bi-shield-lock-fill me-2 text-warning"></i>Administrative Password Reset
                    </h5>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                        <i class="bi bi-key me-1"></i> Admin Authorization Required
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning-subtle border border-warning-subtle p-3 mb-4 rounded d-flex align-items-start gap-2 small">
                        <i class="bi bi-info-circle-fill text-warning-emphasis fs-5 flex-shrink-0"></i>
                        <div class="text-secondary">
                            Resetting this account's password issues a temporary credential. The user will be required to choose a new private password upon their next login. For security compliance and audit logging, your own administrator password is required to authorize this reset.
                        </div>
                    </div>

                    <?php if (!empty($resetErrors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 8px;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                                <div>
                                    <strong class="d-block small fw-bold">Password reset failed:</strong>
                                    <ul class="mb-0 ps-3 small">
                                        <?php foreach ($resetErrors as $err): ?>
                                            <li><?= h($err) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('/users/' . $user['id'] . '/reset-password') ?>" method="POST" id="adminResetPasswordForm">
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <!-- Admin Authorization Password -->
                            <div class="col-12">
                                <label for="admin_password" class="form-label fw-semibold text-secondary small">
                                    Your Administrator Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" 
                                           name="admin_password" 
                                           id="admin_password" 
                                           class="form-control border-start-0 border-end-0" 
                                           placeholder="Enter your current password to authorize reset" 
                                           required 
                                           autocomplete="current-password">
                                    <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" title="Show password" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text small text-muted">
                                    Required to authorize this credential override.
                                </div>
                            </div>

                            <!-- New Temporary Password -->
                            <div class="col-12 col-md-6">
                                <label for="new_password" class="form-label fw-semibold text-secondary small">
                                    New Temporary Password <span class="text-danger">*</span>
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
                            </div>

                            <!-- Confirm New Password -->
                            <div class="col-12 col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold text-secondary small">
                                    Confirm Temporary Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" 
                                           name="confirm_password" 
                                           id="confirm_password" 
                                           class="form-control border-start-0 border-end-0" 
                                           placeholder="Re-enter temporary password" 
                                           required 
                                           minlength="8" 
                                           autocomplete="new-password">
                                    <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" title="Show password" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text small text-muted" id="resetPasswordMatchText">
                                    Re-enter the temporary password to confirm.
                                </div>
                            </div>

                            <!-- Real-Time Password Security Feedback -->
                            <div class="col-12">
                                <div class="p-3 bg-light rounded border">
                                    <div class="row g-2 small text-muted" style="font-size: 0.75rem;">
                                        <div class="col-12 col-sm-4" id="resetReqLength">
                                            <i class="bi bi-circle text-muted me-1"></i> At least 8 characters
                                        </div>
                                        <div class="col-12 col-sm-4" id="resetReqComplexity">
                                            <i class="bi bi-circle text-muted me-1"></i> Letters & numbers
                                        </div>
                                        <div class="col-12 col-sm-4" id="resetReqMatch">
                                            <i class="bi bi-circle text-muted me-1"></i> Passwords match
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" 
                                    id="btnAdminResetSubmit"
                                    class="btn btn-warning px-4 fw-bold d-flex align-items-center">
                                <i class="bi bi-shield-lock-fill me-2"></i>Reset User Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const firstNameInput = document.getElementById('first_name');
    const lastNameInput = document.getElementById('last_name');
    const roleSelect = document.getElementById('role');
    const jobTitleInput = document.getElementById('job_title');

    const previewAvatar = document.getElementById('previewAvatar');
    const previewFullName = document.getElementById('previewFullName');
    const previewRoleBadge = document.getElementById('previewRoleBadge');
    const previewJobText = document.getElementById('previewJobText');

    const matrixStaff = document.getElementById('matrixStaff');
    const matrixAdmin = document.getElementById('matrixAdmin');
    const matrixSuperAdmin = document.getElementById('matrixSuperAdmin');

    function updatePreview() {
        const first = firstNameInput ? firstNameInput.value.trim() : '';
        const last = lastNameInput ? lastNameInput.value.trim() : '';
        const job = jobTitleInput ? jobTitleInput.value.trim() : '';
        const role = roleSelect ? roleSelect.value : '<?= h($currentRole) ?>';

        // Monogram
        let initials = '';
        if (first && last) {
            initials = (first[0] + last[0]).toUpperCase();
        } else if (first) {
            initials = first.substring(0, 2).toUpperCase();
        } else {
            initials = '<?= h($initials) ?>';
        }
        if (previewAvatar) previewAvatar.textContent = initials;

        // Full Name
        if (previewFullName) {
            if (first || last) {
                previewFullName.textContent = `${last ? last + ', ' : ''}${first || ''}`.trim();
            } else {
                previewFullName.textContent = 'User Account';
            }
        }

        // Job Title
        if (previewJobText) {
            previewJobText.textContent = job || 'Staff Member';
        }

        // Role badge & Matrix
        if (previewRoleBadge) {
            if (role === 'super_admin') {
                previewRoleBadge.textContent = 'Super Admin';
                previewRoleBadge.style.backgroundColor = '#0f766e';
                previewRoleBadge.style.color = '#ffffff';
                previewRoleBadge.style.border = 'none';
                previewRoleBadge.className = 'badge shadow-sm';
            } else if (role === 'admin') {
                previewRoleBadge.textContent = 'Administrator';
                previewRoleBadge.style.backgroundColor = '#e6fffa';
                previewRoleBadge.style.color = '#0d9488';
                previewRoleBadge.style.border = '1px solid #99f6e4';
                previewRoleBadge.className = 'badge fw-semibold';
            } else {
                previewRoleBadge.textContent = 'Staff Personnel';
                previewRoleBadge.style.backgroundColor = '#f1f5f9';
                previewRoleBadge.style.color = '#475569';
                previewRoleBadge.style.border = '1px solid #e2e8f0';
                previewRoleBadge.className = 'badge fw-medium';
            }
        }

        // Matrix card highlights
        if (matrixStaff) {
            matrixStaff.style.boxShadow = (role === 'staff') ? '0 0 0 2px #0d9488' : 'none';
        }
        if (matrixAdmin) {
            matrixAdmin.style.boxShadow = (role === 'admin') ? '0 0 0 2px #0d9488' : 'none';
        }
        if (matrixSuperAdmin) {
            matrixSuperAdmin.style.boxShadow = (role === 'super_admin') ? '0 0 0 2px #0f766e' : 'none';
        }
    }

    [firstNameInput, lastNameInput, roleSelect, jobTitleInput].forEach(function(el) {
        if (el) {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        }
    });

    updatePreview();

    // Administrative Password Reset validation & confirmation
    const newPasswordResetInput = document.getElementById('new_password');
    const confirmPasswordResetInput = document.getElementById('confirm_password');
    const resetMatchText = document.getElementById('resetPasswordMatchText');
    const resetReqLength = document.getElementById('resetReqLength');
    const resetReqComplexity = document.getElementById('resetReqComplexity');
    const resetReqMatch = document.getElementById('resetReqMatch');

    function updateResetIndicator(element, isValid) {
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

    function checkResetPasswordMatch() {
        if (!newPasswordResetInput) return;
        const newPass = newPasswordResetInput.value;
        const confirmPass = confirmPasswordResetInput ? confirmPasswordResetInput.value : '';

        const hasLength = newPass.length >= 8;
        const hasLetters = /[a-zA-Z]/.test(newPass);
        const hasNumbers = /[0-9]/.test(newPass);
        const isComplex = hasLetters && hasNumbers;
        const doesMatch = newPass.length > 0 && newPass === confirmPass;

        updateResetIndicator(resetReqLength, hasLength);
        updateResetIndicator(resetReqComplexity, isComplex);
        updateResetIndicator(resetReqMatch, doesMatch);

        if (!resetMatchText) return;
        if (confirmPass.length > 0) {
            if (doesMatch) {
                resetMatchText.textContent = '✓ Passwords match successfully.';
                resetMatchText.className = 'form-text small text-success fw-semibold';
            } else {
                resetMatchText.textContent = '✗ Passwords do not match.';
                resetMatchText.className = 'form-text small text-danger fw-semibold';
            }
        } else {
            resetMatchText.textContent = 'Re-enter the temporary password to confirm.';
            resetMatchText.className = 'form-text small text-muted';
        }
    }

    [newPasswordResetInput, confirmPasswordResetInput].forEach(function(el) {
        if (el) {
            el.addEventListener('input', checkResetPasswordMatch);
        }
    });

    const btnAdminReset = document.getElementById('btnAdminResetSubmit');
    const formAdminReset = document.getElementById('adminResetPasswordForm');
    if (btnAdminReset && formAdminReset) {
        btnAdminReset.addEventListener('click', function(e) {
            if (!formAdminReset.checkValidity()) {
                formAdminReset.reportValidity();
                return;
            }
            e.preventDefault();
            Swal.fire({
                title: 'Reset User Password?',
                text: "You are setting a temporary password for user '<?= h($user['username']) ?>'. They will be required to change it on their next login.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0D7377',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reset Password',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    formAdminReset.submit();
                }
            });
        });
    }

    // Scroll to reset section if hash present or errors returned
    if (window.location.hash === '#password-reset-section' || <?= !empty($resetErrors) ? 'true' : 'false' ?>) {
        const resetSection = document.getElementById('password-reset-section');
        if (resetSection) {
            setTimeout(function() {
                resetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 200);
        }
    }
});
</script>
