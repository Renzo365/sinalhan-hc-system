<?php
$title = 'First-Time Password Update';
require dirname(__DIR__) . '/layout/header.php';

// Prepare user display attributes
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if (empty($fullName)) {
    $fullName = $user['username'] ?? 'Healthcare Staff';
}
$employeeId = !empty($user['employee_id']) ? $user['employee_id'] : ($user['username'] ?? 'BHC-STAFF');
$roleName = ucwords(str_replace('_', ' ', $user['role'] ?? 'Staff'));

$initials = '';
if (!empty($user['first_name'])) {
    $initials .= mb_substr(trim($user['first_name']), 0, 1);
}
if (!empty($user['last_name'])) {
    $initials .= mb_substr(trim($user['last_name']), 0, 1);
}
$initials = strtoupper($initials ?: 'ST');
?>

<!-- Link Dedicated Authentication Portal Stylesheet -->
<link rel="stylesheet" href="<?= asset('css/auth-redesign.css') ?>">

<div class="auth-page-container">
    <!-- Left Institutional Branding Panel -->
    <div class="auth-brand-col">
        <div class="auth-brand-content">
            <!-- Institutional Seal Badge -->
            <div class="brand-header-top">
                <div class="brand-seal-badge">
                    <div class="brand-seal-icon-box">
                        <i class="bi bi-shield-shaded"></i>
                    </div>
                    <div>
                        <div class="brand-republic-text">Republic of the Philippines</div>
                        <div class="brand-republic-sub">Barangay Sinalhan Health Services</div>
                    </div>
                </div>
            </div>

            <!-- Main Heading & Mission -->
            <h1 class="brand-hero-title">Barangay Sinalhan<br>Health Center</h1>
            <div class="brand-hero-subtitle">Patient Management &amp; Health Records System</div>
            <p class="brand-mission-desc">
                Providing accessible, standardized, and secure public health services for the constituents of Barangay Sinalhan, City of Santa Rosa, Laguna. Dedicated to compassionate primary care and digital health governance.
            </p>
        </div>

        <!-- Institutional Footer / Assistance Block -->
        <div class="brand-footer-block">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-headset text-teal-light"></i>
                <span>Need assistance? BHC IT Desk: <strong>Local 104</strong></span>
            </div>
            <div>
                Contact: <a href="mailto:support@sinalhan.gov.ph">support@sinalhan.gov.ph</a> &bull; Clinic Administrator
            </div>
        </div>
    </div>

    <!-- Right Form Panel (First-Time Password Update) -->
    <div class="auth-form-col">
        <div class="auth-form-box">
            <!-- Top Status Badges -->
            <div class="auth-top-badges">
                <span class="authorized-only-badge">
                    <i class="bi bi-patch-check-fill text-success"></i>
                    <span>Staff Account Activation</span>
                </span>
            </div>

            <!-- Emblem Icon Container -->
            <div class="auth-lock-emblem">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <!-- Title & Explanatory Subtitle -->
            <h2 class="auth-card-title">First-Time Password Update</h2>
            <p class="auth-card-subtitle">
                For security compliance, you must update your temporary default password before accessing clinical charts, consultations, and patient registers.
            </p>

            <!-- Authenticated Staff Identity Card -->
            <div class="staff-identity-card">
                <div class="staff-initials-avatar">
                    <?= h($initials) ?>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="staff-info-title text-truncate">
                        Authenticated Staff: <?= h($fullName) ?>
                    </div>
                    <div class="staff-info-role">
                        <span>ID: <strong><?= h($employeeId) ?></strong></span>
                        <span class="text-muted">&bull;</span>
                        <span class="staff-role-badge"><?= h($roleName) ?></span>
                    </div>
                </div>
            </div>

            <!-- Error Alerts Banner -->
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger mb-4 py-2.5 px-3 small border-0 shadow-sm rounded-3" role="alert">
                    <div class="fw-bold mb-1 d-flex align-items-center gap-1.5">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                        <span>Please correct the following:</span>
                    </div>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= h($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Password Update Form -->
            <form action="<?= url('/change-password') ?>" method="POST" id="changePasswordForm" autocomplete="off">
                <?= csrf_field() ?>

                <!-- Field 1: Current Temporary Password -->
                <div class="mb-3">
                    <div class="auth-field-label">
                        <span>Current Temporary Password *</span>
                        <span class="auth-field-sublabel">Provided by IT Administrator</span>
                    </div>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" 
                               name="current_password" 
                               id="current_password" 
                               class="form-control" 
                               placeholder="Enter temporary password" 
                               required 
                               autofocus>
                        <button class="btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Field 2: New Secure Password -->
                <div class="mb-3">
                    <div class="auth-field-label">
                        <span>New Secure Password *</span>
                        <span id="strength-status-label" class="fw-bold text-muted" style="font-size: 0.75rem;">Strength: &mdash;</span>
                    </div>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input type="password" 
                               name="new_password" 
                               id="new_password" 
                               class="form-control" 
                               placeholder="Min 8 characters, mixed case, symbol" 
                               required>
                        <button class="btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    
                    <!-- 4-Segment Strength Meter Bar -->
                    <div class="strength-meter-container">
                        <div class="strength-meter-bars" id="strength-meter-bars">
                            <div class="strength-segment" id="seg-1"></div>
                            <div class="strength-segment" id="seg-2"></div>
                            <div class="strength-segment" id="seg-3"></div>
                            <div class="strength-segment" id="seg-4"></div>
                        </div>
                    </div>
                </div>

                <!-- Interactive Password Criteria Checklist Box -->
                <div class="criteria-card">
                    <div class="criteria-card-title">Password Requirements</div>
                    <div class="criteria-item" id="crit-length">
                        <i class="bi bi-circle"></i>
                        <span>At least 8 characters long</span>
                    </div>
                    <div class="criteria-item" id="crit-case">
                        <i class="bi bi-circle"></i>
                        <span>Contains uppercase and lowercase letters</span>
                    </div>
                    <div class="criteria-item" id="crit-symbol">
                        <i class="bi bi-circle"></i>
                        <span>Contains at least 1 number and 1 special symbol (@$!%*?&amp;)</span>
                    </div>
                    <div class="criteria-item" id="crit-different">
                        <i class="bi bi-circle"></i>
                        <span>Must be different from your current temporary password</span>
                    </div>
                </div>

                <!-- Field 3: Confirm New Password -->
                <div class="mb-4">
                    <div class="auth-field-label">
                        <span>Confirm New Password *</span>
                        <span id="confirm-match-label" class="fw-bold" style="font-size: 0.75rem;"></span>
                    </div>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="password" 
                               name="confirm_password" 
                               id="confirm_password" 
                               class="form-control" 
                               placeholder="Confirm new password" 
                               required>
                        <button class="btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Primary Submit Action -->
                <div class="d-grid mb-2">
                    <button type="submit" class="btn-auth-primary" id="btnSubmitPassword">
                        <span>Save &amp; Proceed</span>
                        <i class="bi bi-arrow-right fs-5"></i>
                    </button>
                </div>
            </form>

            <!-- Secondary Action: Cancel & Sign Out Form -->
            <div class="auth-secondary-actions">
                <form action="<?= url('/logout') ?>" method="POST" class="d-inline" id="cancelSignOutForm">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-auth-cancel" data-confirm="Are you sure you want to cancel activation and sign out of the system?">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Cancel &amp; Sign Out</span>
                    </button>
                </form>
            </div>

            <!-- Healthcare Staff Notice Callout -->
            <div class="notice-callout-card">
                <i class="bi bi-shield-exclamation notice-callout-icon"></i>
                <div>
                    <div class="notice-callout-title">Notice for Healthcare Staff</div>
                    <p class="notice-callout-text">
                        Once updated, your new password takes effect immediately across all Barangay Sinalhan consultation workstations and RHU clinical terminals.
                    </p>
                </div>
            </div>

            <!-- Footer Section -->
            <div class="auth-card-footer">
                <div class="auth-footer-links">
                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#privacyPolicyModal">Privacy Policy</a>
                    <span class="auth-footer-separator">&bull;</span>
                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Use</a>
                </div>
                <div class="mb-1">&copy; <?= date('Y') ?> Barangay Sinalhan Health Center. All rights reserved.</div>
                <div class="auth-footer-location">City of Santa Rosa, Laguna &bull; Republic of the Philippines</div>
            </div>
        </div>
    </div>
</div>

<!-- Offline Modal: Privacy Policy (RA 10173 Notice) -->
<div class="modal fade compliance-modal" id="privacyPolicyModal" tabindex="-1" aria-labelledby="privacyPolicyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock-fill fs-5 text-teal-mint"></i>
                    <h5 class="modal-title mb-0" id="privacyPolicyModalLabel">Privacy Policy &amp; RA 10173 Compliance Notice</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                    <strong>Statutory Mandate:</strong> This system operates in strict compliance with Republic Act No. 10173, otherwise known as the <em>Data Privacy Act of 2012</em>, and the National Privacy Commission (NPC) health data protection circulars.
                </div>
                
                <h6 class="fw-bold text-dark mt-3">1. Scope of Protected Health Information (PHI)</h6>
                <p>The Barangay Sinalhan Health Center Patient Management System (BHC-PMS) processes sensitive personal data, including patient demographics, vital signs, clinical consultation notes, prenatal and immunization registers, diagnoses, and attending clinician identities.</p>

                <h6 class="fw-bold text-dark mt-3">2. Health Worker Duty of Confidentiality</h6>
                <p>All healthcare personnel (physicians, nurses, midwives, barangay health workers, encoders, and system administrators) are bound by statutory confidentiality. Viewing, printing, altering, or disclosing patient medical records without explicit clinical necessity or legal authority is strictly prohibited.</p>

                <h6 class="fw-bold text-dark mt-3">3. Technical &amp; Organizational Safeguards</h6>
                <ul>
                    <li><strong>Role-Based Access Control (RBAC):</strong> Access is compartmentalized strictly according to clinical role.</li>
                    <li><strong>Automatic Inactivity Timeout:</strong> Sessions automatically terminate after 15 minutes of inactivity to prevent unauthorized access at shared clinic workstations.</li>
                    <li><strong>Immutable Audit Logging:</strong> Every authentication event, failed login, record viewing, consultation creation, and profile modification is recorded with employee ID and timestamp.</li>
                    <li><strong>Cryptographic Defense:</strong> Password hashes are secured using industry-standard bcrypt algorithms with salted keys.</li>
                </ul>

                <h6 class="fw-bold text-dark mt-3">4. Penalties for Breach</h6>
                <p class="mb-0">Unauthorized processing, access due to negligence, and improper disposal of sensitive health records are subject to criminal penalties under Section 31 of RA 10173, as well as immediate termination and revocation of clinical licenses.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Offline Modal: Terms of Use -->
<div class="modal fade compliance-modal" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-medical-fill fs-5 text-teal-mint"></i>
                    <h5 class="modal-title mb-0" id="termsModalLabel">Terms of Use &amp; Acceptable Use Policy</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold text-dark">1. Official Health Center Use Only</h6>
                <p>This information system is the exclusive property of Barangay Sinalhan Health Services, City of Santa Rosa, Laguna. It is provisioned solely for authorized clinical consultations, triage, immunization recording, and administrative health services.</p>

                <h6 class="fw-bold text-dark mt-3">2. Account Responsibility &amp; Credential Security</h6>
                <ul>
                    <li>User accounts and employee IDs are assigned individually and are strictly non-transferable.</li>
                    <li>Sharing account credentials, leaving workstations logged in unattended, or utilizing shared passwords is a direct violation of clinical protocol.</li>
                    <li>Users must promptly report any suspected unauthorized access or compromise of credentials to the BHC IT Desk (Local 104).</li>
                </ul>

                <h6 class="fw-bold text-dark mt-3">3. Clinical Data Integrity</h6>
                <p>Health workers must ensure all recorded patient entries, triage vital signs, and consultation diagnoses are truthful, complete, and entered concurrently with patient encounters. Falsification of medical records is punishable under the Revised Penal Code.</p>

                <h6 class="fw-bold text-dark mt-3">4. Monitoring &amp; Compliance Audit</h6>
                <p class="mb-0">System activities are monitored continuously by clinic administration to ensure patient safety, diagnostic quality, and compliance with City Health Office standards.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Real-Time Password Strength & Criteria Checklist Vanilla JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('auth-redesign-body');

    const currentPwdInput = document.getElementById('current_password');
    const newPwdInput = document.getElementById('new_password');
    const confirmPwdInput = document.getElementById('confirm_password');

    const critLength = document.getElementById('crit-length');
    const critCase = document.getElementById('crit-case');
    const critSymbol = document.getElementById('crit-symbol');
    const critDifferent = document.getElementById('crit-different');

    const strengthLabel = document.getElementById('strength-status-label');
    const seg1 = document.getElementById('seg-1');
    const seg2 = document.getElementById('seg-2');
    const seg3 = document.getElementById('seg-3');
    const seg4 = document.getElementById('seg-4');
    const matchLabel = document.getElementById('confirm-match-label');

    function updateItemState(el, isValid) {
        if (!el) return;
        const icon = el.querySelector('i');
        if (isValid) {
            el.classList.add('valid');
            if (icon) {
                icon.className = 'bi bi-check-circle-fill text-success';
            }
        } else {
            el.classList.remove('valid');
            if (icon) {
                icon.className = 'bi bi-circle text-muted';
            }
        }
    }

    function evaluatePassword() {
        const currentVal = currentPwdInput ? currentPwdInput.value : '';
        const newVal = newPwdInput ? newPwdInput.value : '';
        const confirmVal = confirmPwdInput ? confirmPwdInput.value : '';

        // Criteria 1: Length >= 8
        const hasLength = newVal.length >= 8;
        updateItemState(critLength, hasLength);

        // Criteria 2: Both uppercase and lowercase
        const hasUpper = /[A-Z]/.test(newVal);
        const hasLower = /[a-z]/.test(newVal);
        const hasCase = hasUpper && hasLower;
        updateItemState(critCase, hasCase);

        // Criteria 3: Digit and Symbol
        const hasDigit = /[0-9]/.test(newVal);
        const hasSymbol = /[^A-Za-z0-9]/.test(newVal);
        const hasNumAndSymbol = hasDigit && hasSymbol;
        updateItemState(critSymbol, hasNumAndSymbol);

        // Criteria 4: Different from current temporary password
        const hasDifferent = newVal.length > 0 && currentVal.length > 0 && newVal !== currentVal;
        updateItemState(critDifferent, hasDifferent);

        // Calculate Strength Score (0 to 4)
        let score = 0;
        if (hasLength) score++;
        if (hasCase) score++;
        if (hasNumAndSymbol) score++;
        if (hasDifferent) score++;

        // Reset segments
        [seg1, seg2, seg3, seg4].forEach(seg => {
            seg.className = 'strength-segment';
        });

        if (newVal.length === 0) {
            strengthLabel.innerHTML = 'Strength: &mdash;';
            strengthLabel.className = 'fw-bold text-muted';
        } else if (score <= 1) {
            seg1.classList.add('active-weak');
            strengthLabel.innerHTML = 'Strength: <span class="text-danger">Weak</span>';
        } else if (score === 2) {
            seg1.classList.add('active-fair');
            seg2.classList.add('active-fair');
            strengthLabel.innerHTML = 'Strength: <span class="text-warning">Fair</span>';
        } else if (score === 3) {
            seg1.classList.add('active-good');
            seg2.classList.add('active-good');
            seg3.classList.add('active-good');
            strengthLabel.innerHTML = 'Strength: <span class="text-info">Good</span>';
        } else if (score === 4) {
            seg1.classList.add('active-strong');
            seg2.classList.add('active-strong');
            seg3.classList.add('active-strong');
            seg4.classList.add('active-strong');
            strengthLabel.innerHTML = 'Strength: <span class="text-success">Strong</span>';
        }

        // Confirm match check
        if (confirmVal.length > 0) {
            if (newVal === confirmVal) {
                matchLabel.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Passwords match';
                matchLabel.className = 'fw-bold text-success';
            } else {
                matchLabel.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Passwords do not match';
                matchLabel.className = 'fw-bold text-danger';
            }
        } else {
            matchLabel.innerHTML = '';
        }
    }

    if (newPwdInput) {
        newPwdInput.addEventListener('input', evaluatePassword);
    }
    if (currentPwdInput) {
        currentPwdInput.addEventListener('input', evaluatePassword);
    }
    if (confirmPwdInput) {
        confirmPwdInput.addEventListener('input', evaluatePassword);
    }
});
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
