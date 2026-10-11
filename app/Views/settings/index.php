<?php
$title = 'System Settings';
$breadcrumbs = [
    'System Settings' => null
];
require dirname(__DIR__) . '/layout/header.php';

$activeTab = $activeTab ?? 'facility';
$facility = $settings['facility'] ?? [];
$scheduling = $settings['scheduling'] ?? [];
$security = $settings['security'] ?? [];

$isSuperAdmin = is_super_admin();
?>

<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h1 class="h3 mb-1 fw-bold text-primary-dark">System &amp; Facility Settings</h1>
            <p class="text-secondary small mb-0">Configure clinic profile details, clinical scheduling limits, and system security policies.</p>
        </div>
        <div>
            <span class="badge bg-light text-dark border px-3 py-2 d-inline-flex align-items-center gap-1">
                <i class="bi bi-shield-check text-primary"></i>
                <span>Role: <strong><?= $isSuperAdmin ? 'Super Administrator' : 'Administrator' ?></strong></span>
            </span>
        </div>
    </div>
</div>

<!-- Settings Navigation Tabs -->
<ul class="nav nav-pills mb-4 border-bottom pb-3 gap-2" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link px-3 py-2 <?= $activeTab === 'facility' ? 'active fw-bold' : 'text-secondary' ?>" 
           href="<?= url('/settings?tab=facility') ?>" 
           role="tab" 
           style="border-radius: 8px;">
            <i class="bi bi-hospital me-1.5" aria-hidden="true"></i> Facility Profile
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link px-3 py-2 <?= $activeTab === 'scheduling' ? 'active fw-bold' : 'text-secondary' ?>" 
           href="<?= url('/settings?tab=scheduling') ?>" 
           role="tab" 
           style="border-radius: 8px;">
            <i class="bi bi-calendar2-range me-1.5" aria-hidden="true"></i> Clinical Operations
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link px-3 py-2 <?= $activeTab === 'security' ? 'active fw-bold' : 'text-secondary' ?> <?= !$isSuperAdmin ? 'text-muted' : '' ?>" 
           href="<?= url('/settings?tab=security') ?>" 
           role="tab" 
           style="border-radius: 8px;">
            <i class="bi bi-shield-lock me-1.5" aria-hidden="true"></i> Security Policies
            <?php if (!$isSuperAdmin): ?>
                <span class="badge bg-secondary-subtle text-secondary small ms-1" style="font-size: 0.65rem;">Super Admin</span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link px-3 py-2 <?= $activeTab === 'system' ? 'active fw-bold' : 'text-secondary' ?>" 
           href="<?= url('/settings?tab=system') ?>" 
           role="tab" 
           style="border-radius: 8px;">
            <i class="bi bi-cpu me-1.5" aria-hidden="true"></i> Server &amp; Environment
        </a>
    </li>
</ul>

<div class="row g-4 mb-5">
    <div class="col-12 col-xl-9 col-lg-10">

        <!-- TAB 1: FACILITY PROFILE -->
        <?php if ($activeTab === 'facility'): ?>
        <div class="card card-premium shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                        <i class="bi bi-building me-2 text-primary"></i>Facility Information &amp; Official Header Details
                    </h2>
                    <p class="text-muted small mb-0 mt-1">These values are automatically embedded into clinical receipts, health certs, and official reports.</p>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="<?= url('/settings') ?>" method="POST" id="facilitySettingsForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="category" value="facility">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="facility_name" class="form-label fw-semibold text-secondary small">
                                Facility / Health Station Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="facility_name" 
                                   id="facility_name" 
                                   class="form-control" 
                                   value="<?= h($facility['facility_name'] ?? 'Barangay Health Station Sinalhan 2') ?>" 
                                   required 
                                   maxlength="128">
                        </div>

                        <div class="col-12">
                            <label for="facility_address" class="form-label fw-semibold text-secondary small">
                                Complete Station Address
                            </label>
                            <input type="text" 
                                   name="facility_address" 
                                   id="facility_address" 
                                   class="form-control" 
                                   value="<?= h($facility['facility_address'] ?? 'Barangay Sinalhan, City of Santa Rosa, Laguna') ?>" 
                                   maxlength="255">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="facility_contact" class="form-label fw-semibold text-secondary small">
                                Official Contact Number / Hotline
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="text" 
                                       name="facility_contact" 
                                       id="facility_contact" 
                                       class="form-control" 
                                       value="<?= h($facility['facility_contact'] ?? '09175558888') ?>" 
                                       maxlength="32">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="facility_email" class="form-label fw-semibold text-secondary small">
                                Official Health Center Email
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" 
                                       name="facility_email" 
                                       id="facility_email" 
                                       class="form-control" 
                                       value="<?= h($facility['facility_email'] ?? 'bhc.sinalhan2@santarosa.gov.ph') ?>" 
                                       maxlength="100">
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="facility_mho_name" class="form-label fw-semibold text-secondary small">
                                Municipal Health Officer (MHO) / Attending Physician in Charge
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person-badge"></i></span>
                                <input type="text" 
                                       name="facility_mho_name" 
                                       id="facility_mho_name" 
                                       class="form-control" 
                                       value="<?= h($facility['facility_mho_name'] ?? 'Dr. Maria Santos, MD') ?>" 
                                       placeholder="e.g. Dr. Juan Dela Cruz, MD" 
                                       maxlength="100">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="facility_phic_code" class="form-label fw-semibold text-secondary small">
                                PhilHealth Accreditation / Provider ID
                            </label>
                            <input type="text" 
                                   name="facility_phic_code" 
                                   id="facility_phic_code" 
                                   class="form-control font-monospace" 
                                   value="<?= h($facility['facility_phic_code'] ?? 'PHIC-R4A-LAG-SR-0042') ?>" 
                                   placeholder="e.g. PHIC-R4A-LAG-SR-0042" 
                                   maxlength="64">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="facility_doh_code" class="form-label fw-semibold text-secondary small">
                                DOH Facility Code (National Health Facility Registry)
                            </label>
                            <input type="text" 
                                   name="facility_doh_code" 
                                   id="facility_doh_code" 
                                   class="form-control font-monospace" 
                                   value="<?= h($facility['facility_doh_code'] ?? 'DOH-BHS-043428-02') ?>" 
                                   placeholder="e.g. DOH-BHS-043428-02" 
                                   maxlength="64">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center">
                            <i class="bi bi-check2-circle me-2"></i> Save Facility Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 2: CLINIC OPERATIONS & SCHEDULING -->
        <?php if ($activeTab === 'scheduling'): ?>
        <div class="card card-premium shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                        <i class="bi bi-calendar-check me-2 text-primary"></i>Clinical Operations &amp; Appointment Limits
                    </h2>
                    <p class="text-muted small mb-0 mt-1">Configure appointment booking windows and maximum patient capacity to prevent overcrowding.</p>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="<?= url('/settings') ?>" method="POST" id="schedulingSettingsForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="category" value="scheduling">

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="appointment_slot_capacity" class="form-label fw-semibold text-secondary small">
                                Slot Capacity (Patients per 30-minute window) <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   name="appointment_slot_capacity" 
                                   id="appointment_slot_capacity" 
                                   class="form-control" 
                                   value="<?= h($scheduling['appointment_slot_capacity'] ?? '2') ?>" 
                                   min="1" 
                                   max="20" 
                                   required>
                            <div class="form-text small">Standard setting is 2-4 patients per 30 minutes for Barangay Health Stations.</div>
                        </div>

                        <div class="col-12"><hr class="my-2 text-muted"></div>

                        <div class="col-12">
                            <h6 class="fw-bold text-dark mb-2">Morning Outpatient Department (OPD) Window</h6>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="morning_opd_start" class="form-label fw-semibold text-secondary small">Morning Start Time</label>
                            <input type="time" name="morning_opd_start" id="morning_opd_start" class="form-control" value="<?= h($scheduling['morning_opd_start'] ?? '08:30') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="morning_opd_end" class="form-label fw-semibold text-secondary small">Morning End Time</label>
                            <input type="time" name="morning_opd_end" id="morning_opd_end" class="form-control" value="<?= h($scheduling['morning_opd_end'] ?? '12:00') ?>">
                        </div>

                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-dark mb-2">Afternoon Outpatient Department (OPD) Window</h6>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="afternoon_opd_start" class="form-label fw-semibold text-secondary small">Afternoon Start Time</label>
                            <input type="time" name="afternoon_opd_start" id="afternoon_opd_start" class="form-control" value="<?= h($scheduling['afternoon_opd_start'] ?? '13:00') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="afternoon_opd_end" class="form-label fw-semibold text-secondary small">Afternoon End Time</label>
                            <input type="time" name="afternoon_opd_end" id="afternoon_opd_end" class="form-control" value="<?= h($scheduling['afternoon_opd_end'] ?? '17:00') ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center">
                            <i class="bi bi-check2-circle me-2"></i> Save Clinical Operations
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 3: SECURITY POLICIES -->
        <?php if ($activeTab === 'security'): ?>
        <div class="card card-premium shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                        <i class="bi bi-shield-lock me-2 text-primary"></i>Security Policies &amp; Inactivity Timeout
                    </h2>
                    <p class="text-muted small mb-0 mt-1">Regulate staff session lifespans and brute-force defenses in accordance with Philippine Data Privacy Act standards.</p>
                </div>
                <?php if (!$isSuperAdmin): ?>
                    <span class="badge bg-warning text-dark"><i class="bi bi-lock me-1"></i> Read Only</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if (!$isSuperAdmin): ?>
                    <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-center" role="alert" style="border-radius: 8px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                        <div class="small">
                            <strong>Restricted Access:</strong> Modifying security and session parameters requires <strong>Super Administrator</strong> authorization. You may inspect the current active policy below.
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= url('/settings') ?>" method="POST" id="securitySettingsForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="category" value="security">

                    <?php 
                    $currentTimeout = (int)($security['session_idle_timeout'] ?? 7200);
                    $currentLockout = (int)($security['max_login_attempts'] ?? 5);
                    ?>

                    <div class="row g-4">
                        <div class="col-12">
                            <label for="session_idle_timeout" class="form-label fw-semibold text-secondary small">
                                Inactivity Auto-Logout Timeout
                            </label>
                            <select name="session_idle_timeout" 
                                    id="session_idle_timeout" 
                                    class="form-select" 
                                    <?= !$isSuperAdmin ? 'disabled' : '' ?>>
                                <option value="900" <?= $currentTimeout === 900 ? 'selected' : '' ?>>15 minutes (High Security / DPA Recommended)</option>
                                <option value="1800" <?= $currentTimeout === 1800 ? 'selected' : '' ?>>30 minutes</option>
                                <option value="3600" <?= $currentTimeout === 3600 ? 'selected' : '' ?>>1 hour (Standard Clinic Shift)</option>
                                <option value="7200" <?= $currentTimeout === 7200 ? 'selected' : '' ?>>2 hours (Default)</option>
                                <option value="14400" <?= $currentTimeout === 14400 ? 'selected' : '' ?>>4 hours (Extended OPD Busy Days)</option>
                            </select>
                            <div class="form-text small">
                                When idle, staff will see a 2-minute countdown warning modal before automatically being signed out to protect patient medical records.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="max_login_attempts" class="form-label fw-semibold text-secondary small">
                                Account Lockout Threshold (Failed Logins)
                            </label>
                            <select name="max_login_attempts" 
                                    id="max_login_attempts" 
                                    class="form-select" 
                                    <?= !$isSuperAdmin ? 'disabled' : '' ?>>
                                <option value="3" <?= $currentLockout === 3 ? 'selected' : '' ?>>3 failed attempts (Strict)</option>
                                <option value="5" <?= $currentLockout === 5 ? 'selected' : '' ?>>5 failed attempts (Recommended Default)</option>
                                <option value="10" <?= $currentLockout === 10 ? 'selected' : '' ?>>10 failed attempts (Lenient)</option>
                            </select>
                            <div class="form-text small">
                                Exceeding this threshold locks the user account for 15 minutes to defeat automated brute-force attacks.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light border rounded small">
                                <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i>Audit Trail Notice</div>
                                <div class="text-muted">
                                    Every modification to system security parameters is irrevocably logged in the <a href="<?= url('/audit-logs') ?>" class="text-decoration-underline text-primary fw-medium">Audit Logs</a> with the updater's user ID, IP address, and timestamp.
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($isSuperAdmin): ?>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center">
                            <i class="bi bi-shield-check me-2"></i> Save Security Policy
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 4: SYSTEM & SERVER INFO -->
        <?php if ($activeTab === 'system'): ?>
        <div class="card card-premium shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="card-title mb-0 fs-6 fw-bold text-primary-dark">
                        <i class="bi bi-hdd-network me-2 text-primary"></i>Server &amp; Environment Diagnostics
                    </h2>
                    <p class="text-muted small mb-0 mt-1">Technical specifications of the underlying host environment and active database.</p>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Parameter</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold text-secondary small"><i class="bi bi-code-slash me-2 text-primary"></i>PHP Version</td>
                                <td class="font-monospace fw-bold text-dark"><?= h($systemInfo['php_version']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-secondary small"><i class="bi bi-database me-2 text-primary"></i>Database Engine &amp; Version</td>
                                <td class="font-monospace fw-bold text-dark">MySQL <?= h($systemInfo['db_version']) ?> (<?= h($systemInfo['database_name']) ?>)</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-secondary small"><i class="bi bi-laptop me-2 text-primary"></i>Server Operating System</td>
                                <td class="font-monospace text-dark"><?= h($systemInfo['server_os']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-secondary small"><i class="bi bi-globe me-2 text-primary"></i>Web Server Software</td>
                                <td class="font-monospace text-dark"><?= h($systemInfo['server_software']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-secondary small"><i class="bi bi-clock me-2 text-primary"></i>Server Time &amp; Timezone</td>
                                <td class="font-monospace text-dark"><?= h($systemInfo['current_time']) ?> (<?= h($systemInfo['timezone']) ?>)</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-secondary small"><i class="bi bi-upload me-2 text-primary"></i>PHP Max File Upload / Post Size</td>
                                <td class="font-monospace text-dark"><?= h($systemInfo['upload_max_filesize']) ?> / <?= h($systemInfo['post_max_size']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-secondary small"><i class="bi bi-memory me-2 text-primary"></i>PHP Memory Limit</td>
                                <td class="font-monospace text-dark"><?= h($systemInfo['memory_limit']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-secondary small"><i class="bi bi-shield me-2 text-primary"></i>Application Environment</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2.5 py-1">
                                        <?= strtoupper(h($systemInfo['app_environment'])) ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Need to safeguard database contents?</span>
                    <a href="<?= url('/backup') ?>" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center">
                        <i class="bi bi-database-down me-1.5"></i> Disaster Recovery &amp; Backup Hub
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
