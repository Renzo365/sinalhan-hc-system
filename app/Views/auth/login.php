<?php
$title = 'Staff Portal Sign In';
require dirname(__DIR__) . '/layout/header.php';
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

    <!-- Right Form Panel (Staff Sign In) -->
    <div class="auth-form-col">
        <div class="auth-form-box">
            <!-- Top Status Badges -->
            <div class="auth-top-badges">
                <span class="authorized-only-badge">
                    <i class="bi bi-lock-fill"></i>
                    <span>Authorized Staff Only</span>
                </span>
            </div>

            <!-- Header Section -->
            <h2 class="auth-card-title">Staff Portal Sign In</h2>
            <p class="auth-card-subtitle">
                Enter your employee credentials to access patient charts, schedules, and clinical logs.
            </p>

            <!-- Session Timeout Alert Banner -->
            <?php if (!empty($timeoutMessage)): ?>
                <div class="alert alert-warning d-flex align-items-center mb-4 py-2.5 px-3 small border-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-clock-history text-warning fs-5 me-2 flex-shrink-0"></i>
                    <div>
                        <strong>Session Expired:</strong> <?= h($timeoutMessage) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Error Notification Banner -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4 py-2.5 px-3 small border-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5 flex-shrink-0"></i>
                    <div><?= h($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= url('/login') ?>" method="POST" autocomplete="off">
                <?= csrf_field() ?>

                <!-- Field 1: Username or Employee ID -->
                <div class="mb-3">
                    <label for="username" class="auth-field-label">
                        <span>Username or Employee ID</span>
                    </label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" 
                               name="username" 
                               id="username" 
                               class="form-control" 
                               placeholder="e.g. bhw-nurse01 or BHC-2026-0042" 
                               value="<?= h($username ?? '') ?>" 
                               required 
                               autofocus>
                    </div>
                </div>

                <!-- Field 2: Password -->
                <div class="mb-4">
                    <label for="password" class="auth-field-label">
                        <span>Password</span>
                    </label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control" 
                               placeholder="Enter password" 
                               required>
                        <button class="btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mb-3">
                    <button type="submit" class="btn-auth-primary">
                        <span>Log In to Portal</span>
                        <i class="bi bi-box-arrow-in-right fs-5"></i>
                    </button>
                </div>
            </form>

            <!-- Medical Officer Notice Callout Box -->
            <div class="notice-callout-card">
                <i class="bi bi-info-circle-fill notice-callout-icon"></i>
                <div>
                    <div class="notice-callout-title">Notice for Medical Officers</div>
                    <p class="notice-callout-text">
                        Unauthorized access is strictly prohibited and audited under Republic Act No. 10173. Ensure active session logout upon leaving the terminal.
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add auth body styling hook
    document.body.classList.add('auth-redesign-body');

    // Clean up address bar query parameters (e.g. ?timeout=1) immediately
    if (window.location.search) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    <?php if (!empty($timeoutMessage)): ?>
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Session Expired',
            text: <?= json_encode($timeoutMessage) ?>,
            confirmButtonColor: '#0D7377',
            confirmButtonText: 'OK',
            allowOutsideClick: false
        });
    }
    <?php endif; ?>
});
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
