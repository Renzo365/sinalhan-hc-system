<?php
$title = 'Archived Records Hub';
$breadcrumbs = [
    'Archive' => '/archive',
    'Records Hub' => null
];
require dirname(__DIR__) . '/layout/header.php';

$activeTab = $activeTab ?? 'patients';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Archived Records Hub</h2>
        <p class="text-secondary small mb-0">Demographic records, clinical consultation logs, and user accounts archived by administrators.</p>
    </div>
</div>

<style>
#archiveTabs .nav-link {
    color: #4b5563;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}
#archiveTabs .nav-link:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
#archiveTabs .nav-link.active {
    background-color: var(--color-primary, #0d9488) !important;
    border-color: var(--color-primary, #0d9488) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.25);
}
#archiveTabs .nav-link.active .badge {
    background-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
    border: none !important;
}
#archiveTabs .nav-link:not(.active) .badge {
    background-color: #f1f5f9 !important;
    color: #64748b !important;
    border: 1px solid #e2e8f0 !important;
}
.bg-teal-subtle {
    background-color: #ccfbf1 !important;
}
.text-teal {
    color: #0d9488 !important;
}
</style>

<!-- Tabbed Navigation -->
<ul class="nav nav-pills mb-4 gap-2" id="archiveTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'patients' ? 'active' : '' ?> px-4 py-2 fw-semibold" id="tab-patients-btn" data-bs-toggle="pill" data-bs-target="#tab-patients" type="button" role="tab">
            <i class="bi bi-people-fill me-2"></i> Archived Patients
            <span class="badge ms-2"><?= (int)($tabCounts['patients'] ?? count($patients ?? [])) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'consultations' ? 'active' : '' ?> px-4 py-2 fw-semibold" id="tab-consultations-btn" data-bs-toggle="pill" data-bs-target="#tab-consultations" type="button" role="tab">
            <i class="bi bi-clipboard2-pulse-fill me-2"></i> Archived Consultations
            <span class="badge ms-2"><?= (int)($tabCounts['consultations'] ?? count($consultations ?? [])) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'users' ? 'active' : '' ?> px-4 py-2 fw-semibold" id="tab-users-btn" data-bs-toggle="pill" data-bs-target="#tab-users" type="button" role="tab">
            <i class="bi bi-person-x-fill me-2"></i> Archived User Accounts
            <span class="badge ms-2"><?= (int)($tabCounts['users'] ?? count($users ?? [])) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="archiveTabsContent">

    <!-- ==============================================================
       TAB 1: ARCHIVED PATIENTS
       ============================================================== -->
    <div class="tab-pane fade <?= $activeTab === 'patients' ? 'show active' : '' ?>" id="tab-patients" role="tabpanel">
        
        <!-- Search and Filter Form for Patients -->
        <div class="card card-premium mb-4">
            <div class="card-body p-4 bg-white">
                <form action="<?= url('/archive/patients') ?>" method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="patients">
                    <div class="col-12 col-md-4">
                        <label for="search_patients" class="form-label text-secondary small fw-semibold">Search Patient</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search_patients" class="form-control border-start-0 bg-light" placeholder="Search by name or patient number..." value="<?= h($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="date_from_patients" class="form-label text-secondary small fw-semibold">Archived Date From</label>
                        <input type="date" name="date_from" id="date_from_patients" class="form-control bg-light" value="<?= h($filters['date_from'] ?? '') ?>">
                    </div>
                    
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="date_to_patients" class="form-label text-secondary small fw-semibold">Archived Date To</label>
                        <input type="date" name="date_to" id="date_to_patients" class="form-control bg-light" value="<?= h($filters['date_to'] ?? '') ?>">
                    </div>
                    
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="<?= url('/archive/patients?tab=patients') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Archived Patients Table -->
        <div class="card card-premium">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small" id="archivedPatientsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Patient No</th>
                                <th class="text-start">Name</th>
                                <th>Age/Sex</th>
                                <th>Address</th>
                                <th>Archived Date</th>
                                <th>Archived By</th>
                                <th>Reason for Archiving</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($patients)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-archive d-block fs-3 mb-2 text-muted"></i>
                                        No archived patient records match the criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($patients as $p): 
                                    $patientPayload = [
                                        'id' => $p['id'],
                                        'patient_no' => $p['patient_no'],
                                        'name' => trim($p['first_name'] . ' ' . ($p['middle_name'] ? $p['middle_name'] . ' ' : '') . $p['last_name'] . ($p['suffix'] ? ' ' . $p['suffix'] : '')),
                                        'age' => $p['age'],
                                        'sex' => $p['sex'],
                                        'dob' => $p['dob'] ? date('M d, Y', strtotime($p['dob'])) : '—',
                                        'civil_status' => $p['civil_status'] ?? '—',
                                        'contact_no' => $p['contact_no'] ?: '—',
                                        'address' => $p['address'] ?: '—',
                                        'philhealth_status' => $p['philhealth_status'] ?? 'None',
                                        'philhealth_no' => $p['philhealth_no'] ?: '—',
                                        'created_at' => $p['created_at'] ? date('M d, Y h:i A', strtotime($p['created_at'])) : '—',
                                        'deleted_at' => $p['deleted_at'] ? date('M d, Y h:i A', strtotime($p['deleted_at'])) : '—',
                                        'archiver_name' => !empty(trim($p['archiver_name'] ?? '')) ? $p['archiver_name'] : 'System',
                                        'archive_reason' => $p['archive_reason'] ?: 'No archive reason recorded.'
                                    ];
                                ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-secondary"><?= h($p['patient_no']) ?></td>
                                        <td class="text-start fw-bold text-dark">
                                            <?= h($p['last_name']) ?>, <?= h($p['first_name']) ?> <?= h($p['middle_name'] ?? '') ?>
                                        </td>
                                        <td><?= h($p['age']) ?> yrs / <?= h($p['sex']) ?></td>
                                        <td><?= h(!empty(trim($p['address'] ?? '')) ? $p['address'] : '—') ?></td>
                                        <td data-order="<?= h($p['deleted_at']) ?>"><?= date('Y-m-d h:i A', strtotime($p['deleted_at'])) ?></td>
                                        <td><span class="badge bg-secondary"><?= h(!empty(trim($p['archiver_name'] ?? '')) ? $p['archiver_name'] : 'System') ?></span></td>
                                        <td class="text-secondary text-start text-truncate" style="max-width: 200px;" title="<?= h($p['archive_reason']) ?>">
                                            <?= h($p['archive_reason']) ?>
                                        </td>
                                        <td class="pe-4 text-end text-nowrap">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary px-2.5 py-1 btn-view-patient-archive" 
                                                        data-patient='<?= htmlspecialchars(json_encode($patientPayload), ENT_QUOTES, 'UTF-8') ?>'
                                                        title="View Demographics & Archival Reason">
                                                    <i class="bi bi-eye me-1"></i> View Details
                                                </button>
                                                <form action="<?= url('/archive/patients/' . $p['id'] . '/restore') ?>" method="POST" class="d-inline restore-patient-form">
                                                    <?= csrf_field() ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success px-2.5 py-1 btn-restore-patient" data-name="<?= h($p['first_name'] . ' ' . $p['last_name']) ?>">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Record
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ==============================================================
       TAB 2: ARCHIVED CONSULTATIONS
       ============================================================== -->
    <div class="tab-pane fade <?= $activeTab === 'consultations' ? 'show active' : '' ?>" id="tab-consultations" role="tabpanel">
        
        <!-- Search and Filter Form for Consultations -->
        <div class="card card-premium mb-4">
            <div class="card-body p-4 bg-white">
                <form action="<?= url('/archive/patients') ?>" method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="consultations">
                    <div class="col-12 col-md-4">
                        <label for="search_consultations" class="form-label text-secondary small fw-semibold">Search Consultations</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search_consultations" class="form-control border-start-0 bg-light" placeholder="Search by patient name, no., or diagnosis..." value="<?= h($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="date_from_consultations" class="form-label text-secondary small fw-semibold">Archived Date From</label>
                        <input type="date" name="date_from" id="date_from_consultations" class="form-control bg-light" value="<?= h($filters['date_from'] ?? '') ?>">
                    </div>
                    
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="date_to_consultations" class="form-label text-secondary small fw-semibold">Archived Date To</label>
                        <input type="date" name="date_to" id="date_to_consultations" class="form-control bg-light" value="<?= h($filters['date_to'] ?? '') ?>">
                    </div>
                    
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="<?= url('/archive/patients?tab=consultations') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Archived Consultations Table -->
        <div class="card card-premium">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small" id="archivedConsultationsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Encounter & Archived Dates</th>
                                <th class="text-start">Patient Name & No.</th>
                                <th>Clinician</th>
                                <th class="text-start">Assessment / Impression</th>
                                <th>Archive Reason</th>
                                <th>Archived By</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($consultations)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-clipboard2-x d-block fs-3 mb-2 text-muted"></i>
                                        No archived consultation records match the criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($consultations as $c): 
                                    $consultationPayload = [
                                        'id' => $c['id'],
                                        'patient_no' => $c['patient_no'],
                                        'patient_name' => $c['pat_first'] . ' ' . $c['pat_last'],
                                        'clinician_name' => $c['clinician_name'] ?? 'Unassigned Clinician',
                                        'consulted_at' => $c['consulted_at'] ? date('M d, Y h:i A', strtotime($c['consulted_at'])) : '—',
                                        'deleted_at' => $c['deleted_at'] ? date('M d, Y h:i A', strtotime($c['deleted_at'])) : '—',
                                        'archiver_name' => !empty(trim($c['archiver_name'] ?? '')) ? $c['archiver_name'] : 'System',
                                        'archive_reason' => $c['archive_reason'] ?: 'Archived by staff',
                                        'subjective' => $c['subjective'] ?: 'None documented.',
                                        'objective' => $c['objective'] ?: 'None documented.',
                                        'assessment' => $c['assessment'] ?: 'None documented.',
                                        'plan' => $c['plan'] ?: 'None documented.',
                                        'status' => $c['status'] ?? 'Completed',
                                        'vitals' => [
                                            'blood_pressure' => (!empty($c['bp_systolic']) && !empty($c['bp_diastolic'])) ? ($c['bp_systolic'] . '/' . $c['bp_diastolic'] . ' mmHg') : '—',
                                            'heart_rate' => $c['heart_rate'] ? $c['heart_rate'] . ' bpm' : '—',
                                            'temperature' => $c['temperature'] ? $c['temperature'] . ' °C' : '—',
                                            'respiratory_rate' => $c['respiratory_rate'] ? $c['respiratory_rate'] . ' cpm' : '—',
                                            'weight' => $c['weight'] ? $c['weight'] . ' kg' : '—',
                                            'height' => $c['height'] ? $c['height'] . ' cm' : '—',
                                            'bmi' => $c['bmi'] ?: '—'
                                        ],
                                        'prescriptions' => array_map(function($pr) {
                                            return [
                                                'medicine' => $pr['medicine_name'] ?? '—',
                                                'dosage' => $pr['dosage'] ?? '—',
                                                'frequency' => $pr['frequency'] ?? '',
                                                'duration' => $pr['duration'] ?? '',
                                                'instructions' => $pr['instructions'] ?? ''
                                            ];
                                        }, $c['prescriptions'] ?? [])
                                    ];
                                ?>
                                    <tr>
                                        <td data-order="<?= h($c['deleted_at']) ?>">
                                            <div class="fw-semibold text-dark">
                                                <i class="bi bi-calendar-check me-1 text-primary"></i><?= date('Y-m-d h:i A', strtotime($c['consulted_at'])) ?>
                                            </div>
                                            <div class="text-muted small mt-0.5" style="font-size: 0.75rem;">
                                                <i class="bi bi-archive me-1 text-secondary"></i>Archived <?= date('Y-m-d h:i A', strtotime($c['deleted_at'])) ?>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <div class="fw-bold text-dark"><?= h($c['pat_first'] . ' ' . $c['pat_last']) ?></div>
                                            <div class="font-monospace text-secondary small" style="font-size: 0.78rem;"><?= h($c['patient_no']) ?></div>
                                        </td>
                                        <td><?= h($c['clinician_name'] ?? 'Clinician') ?></td>
                                        <td class="text-start" style="max-width: 250px;">
                                            <div class="text-truncate" title="<?= h($c['assessment']) ?>">
                                                <?= h($c['assessment']) ?>
                                            </div>
                                        </td>
                                        <td class="text-secondary text-truncate" style="max-width: 200px;" title="<?= h($c['archive_reason'] ?? 'Archived by staff') ?>">
                                            <?= h($c['archive_reason'] ?? 'Archived by staff') ?>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= h(!empty(trim($c['archiver_name'] ?? '')) ? $c['archiver_name'] : 'System') ?></span></td>
                                        <td class="pe-4 text-end text-nowrap">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary px-2.5 py-1 btn-view-consultation-archive" 
                                                        data-consultation='<?= htmlspecialchars(json_encode($consultationPayload), ENT_QUOTES, 'UTF-8') ?>'
                                                        title="Inspect Clinical SOAP Notes, Vitals & Prescriptions">
                                                    <i class="bi bi-eye me-1"></i> Inspect
                                                </button>
                                                <form action="<?= url('/archive/consultations/' . $c['id'] . '/restore') ?>" method="POST" class="d-inline restore-consultation-form">
                                                    <?= csrf_field() ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success px-2.5 py-1 btn-restore-consultation" data-patient="<?= h($c['pat_first'] . ' ' . $c['pat_last']) ?>">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Encounter
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ==============================================================
       TAB 3: ARCHIVED USER ACCOUNTS
       ============================================================== -->
    <div class="tab-pane fade <?= $activeTab === 'users' ? 'show active' : '' ?>" id="tab-users" role="tabpanel">
        
        <!-- Search and Filter Form for Users -->
        <div class="card card-premium mb-4">
            <div class="card-body p-4 bg-white">
                <form action="<?= url('/archive/patients') ?>" method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="users">
                    <div class="col-12 col-md-3">
                        <label for="search_users" class="form-label text-secondary small fw-semibold">Search User</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search_users" class="form-control border-start-0 bg-light" placeholder="Search by username, name, email..." value="<?= h($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 col-md-2">
                        <label for="role_users" class="form-label text-secondary small fw-semibold">Role</label>
                        <select name="role" id="role_users" class="form-select bg-light">
                            <option value="">-- All Roles --</option>
                            <option value="admin" <?= (isset($filters['role']) && $filters['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
                            <option value="staff" <?= (isset($filters['role']) && $filters['role'] === 'staff') ? 'selected' : '' ?>>Staff Personnel</option>
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-2">
                        <label for="date_from_users" class="form-label text-secondary small fw-semibold">Archived Date From</label>
                        <input type="date" name="date_from" id="date_from_users" class="form-control bg-light" value="<?= h($filters['date_from'] ?? '') ?>">
                    </div>

                    <div class="col-12 col-sm-6 col-md-2">
                        <label for="date_to_users" class="form-label text-secondary small fw-semibold">Archived Date To</label>
                        <input type="date" name="date_to" id="date_to_users" class="form-control bg-light" value="<?= h($filters['date_to'] ?? '') ?>">
                    </div>
                    
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="<?= url('/archive/patients?tab=users') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Archived Users Table -->
        <div class="card card-premium">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small" id="archivedUsersTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start ps-4">Username</th>
                                <th class="text-start">Full Name</th>
                                <th>Role</th>
                                <th>Clinical Job Title</th>
                                <th>Archived Date</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-person-x d-block fs-3 mb-2 text-muted"></i>
                                        No archived user accounts match the criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): 
                                    $roleBadge = $u['role'] === 'admin' ? 'bg-light text-primary border border-primary-subtle fw-bold' : 'bg-light text-dark border';
                                    $roleDisplay = $u['role'] === 'admin' ? 'Admin' : 'Staff';
                                    $canRestoreUser = true;
                                    $disabledUserReason = '';
                                    if ($u['role'] === 'admin' && !is_super_admin()) {
                                        $canRestoreUser = false;
                                        $disabledUserReason = 'Only Super Admin can restore administrator accounts';
                                    }
                                ?>
                                    <tr>
                                        <td class="text-start ps-4 fw-bold font-monospace text-dark"><?= h($u['username']) ?></td>
                                        <td class="text-start">
                                             <span class="fw-bold text-dark"><?= h($u['last_name']) ?>, <?= h($u['first_name']) ?></span>
                                            <div class="text-muted small" style="font-size: 0.72rem;"><?= h($u['email'] ?: '-') ?></div>
                                        </td>
                                        <td><span class="badge <?= $roleBadge ?>"><?= $roleDisplay ?></span></td>
                                        <td>
                                            <div class="fw-medium"><?= h($u['job_title'] ?: '-') ?></div>
                                        </td>
                                        <td data-order="<?= $u['deleted_at'] ? h($u['deleted_at']) : '' ?>">
                                            <?= $u['deleted_at'] ? date('Y-m-d h:i A', strtotime($u['deleted_at'])) : '-' ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <?php if ($canRestoreUser): ?>
                                                <form action="<?= url('/users/' . $u['id'] . '/restore') ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-success border px-2.5 py-1 btn-restore-user"
                                                            data-username="<?= h($u['username']) ?>">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Account
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="d-inline-block" style="cursor: not-allowed;" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= h($disabledUserReason) ?>">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-secondary border px-2.5 py-1"
                                                            style="pointer-events: none;"
                                                            disabled>
                                                        <i class="bi bi-lock me-1"></i> Super Admin Only
                                                    </button>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Patient Archive Details Modal -->
<div class="modal fade" id="modalPatientArchiveDetails" tabindex="-1" aria-labelledby="modalPatientArchiveDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-teal-subtle text-teal p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-person-badge fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="patientModalName">Patient Name</h5>
                        <div class="small font-monospace text-secondary" id="patientModalNo">P-XXXX-XXXXX</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Demographic & Clinical Profile Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small fw-semibold">Age / Sex</div>
                        <div class="fw-bold text-dark" id="patientModalAgeSex">—</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small fw-semibold">Date of Birth</div>
                        <div class="fw-bold text-dark" id="patientModalDob">—</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small fw-semibold">Civil Status</div>
                        <div class="fw-bold text-dark" id="patientModalCivil">—</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small fw-semibold">Contact No.</div>
                        <div class="fw-bold text-dark" id="patientModalContact">—</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-secondary small fw-semibold">Complete Address</div>
                        <div class="fw-medium text-dark" id="patientModalAddress">—</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small fw-semibold">PhilHealth Status</div>
                        <div class="fw-bold text-dark" id="patientModalPhilHealthStatus">—</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small fw-semibold">PhilHealth No.</div>
                        <div class="fw-bold font-monospace text-dark" id="patientModalPhilHealthNo">—</div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="text-secondary small fw-semibold">Initial Health Center Registration</div>
                        <div class="text-dark small" id="patientModalRegisteredAt">—</div>
                    </div>
                </div>

                <!-- Archive Audit Trail Box -->
                <div class="card border border-warning-subtle bg-warning-subtle bg-opacity-25 rounded-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-exclamation text-warning fs-5"></i>
                            <h6 class="fw-bold text-dark mb-0">Archival Audit Log</h6>
                        </div>
                        <div class="row g-2 small mb-2">
                            <div class="col-sm-6">
                                <span class="text-secondary">Archived By:</span>
                                <span class="fw-semibold text-dark ms-1" id="patientModalArchiver">—</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary">Archived At:</span>
                                <span class="fw-semibold text-dark ms-1" id="patientModalArchivedAt">—</span>
                            </div>
                        </div>
                        <div class="bg-white p-2.5 rounded border border-warning-subtle text-secondary small">
                            <strong class="d-block text-dark mb-1">Reason for Archiving:</strong>
                            <div id="patientModalReason" class="text-wrap" style="white-space: pre-line;">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
                <div id="patientModalActionContainer">
                    <!-- Populated dynamically with restore form button -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Consultation Archive Details Modal -->
<div class="modal fade" id="modalConsultationArchiveDetails" tabindex="-1" aria-labelledby="modalConsultationArchiveDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-teal-subtle text-teal p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-clipboard2-pulse fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Clinical Encounter Record</h5>
                        <div class="small text-secondary">
                            Patient: <strong id="consultModalPatientName" class="text-dark">—</strong>
                            <span class="font-monospace text-secondary ms-1" id="consultModalPatientNo">(P-XXXX-XXXXX)</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Encounter Metadata Header -->
                <div class="row g-3 p-3 bg-light rounded-3 mb-4 border">
                    <div class="col-sm-6 col-md-4">
                        <div class="text-secondary small fw-semibold">Consulting Clinician</div>
                        <div class="fw-bold text-dark" id="consultModalClinician">—</div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="text-secondary small fw-semibold">Consultation Date</div>
                        <div class="fw-bold text-dark" id="consultModalDate">—</div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="text-secondary small fw-semibold">Encounter Status</div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold" id="consultModalStatus">Completed</span>
                    </div>
                </div>

                <!-- Vital Signs Matrix -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary-dark mb-2"><i class="bi bi-heart-pulse me-1 text-primary"></i> Vital Signs at Triage</h6>
                    <div class="row g-2 text-center" id="consultModalVitalsGrid">
                        <div class="col-4 col-md-2 p-2 bg-white border rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Blood Pressure</div>
                            <div class="fw-bold text-dark small" id="vitalBP">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-white border rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Heart Rate</div>
                            <div class="fw-bold text-dark small" id="vitalHR">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-white border rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Temp</div>
                            <div class="fw-bold text-dark small" id="vitalTemp">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-white border rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Resp Rate</div>
                            <div class="fw-bold text-dark small" id="vitalRR">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-white border rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Weight / Height</div>
                            <div class="fw-bold text-dark small" id="vitalWeightHeight">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-white border rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">BMI</div>
                            <div class="fw-bold text-dark small" id="vitalBMI">—</div>
                        </div>
                    </div>
                </div>

                <!-- Clinical SOAP Notes -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary-dark mb-2"><i class="bi bi-journal-medical me-1 text-primary"></i> SOAP Clinical Encounter Notes</h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="p-3 bg-white border rounded">
                            <div class="fw-bold text-primary small mb-1">Subjective (Chief Complaint & History)</div>
                            <div class="text-dark small" id="consultModalSubjective">—</div>
                        </div>
                        <div class="p-3 bg-white border rounded">
                            <div class="fw-bold text-primary small mb-1">Objective (Physical Examination Findings)</div>
                            <div class="text-dark small" id="consultModalObjective">—</div>
                        </div>
                        <div class="p-3 bg-white border rounded">
                            <div class="fw-bold text-primary small mb-1">Assessment (Clinical Impression / Diagnosis)</div>
                            <div class="text-dark small fw-semibold" id="consultModalAssessment">—</div>
                        </div>
                        <div class="p-3 bg-white border rounded">
                            <div class="fw-bold text-primary small mb-1">Plan (Medical Management & Instructions)</div>
                            <div class="text-dark small" id="consultModalPlan">—</div>
                        </div>
                    </div>
                </div>

                <!-- Prescriptions Table -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary-dark mb-2"><i class="bi bi-capsule me-1 text-primary"></i> Prescribed Medications</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Frequency & Duration</th>
                                    <th>Instructions</th>
                                </tr>
                            </thead>
                            <tbody id="consultModalPrescriptionsBody">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-2">No prescriptions documented.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Archival Audit Box -->
                <div class="card border border-warning-subtle bg-warning-subtle bg-opacity-25 rounded-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-exclamation text-warning fs-5"></i>
                            <h6 class="fw-bold text-dark mb-0">Archival Audit Log</h6>
                        </div>
                        <div class="row g-2 small mb-2">
                            <div class="col-sm-6">
                                <span class="text-secondary">Archived By:</span>
                                <span class="fw-semibold text-dark ms-1" id="consultModalArchiver">—</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary">Archived At:</span>
                                <span class="fw-semibold text-dark ms-1" id="consultModalArchivedAt">—</span>
                            </div>
                        </div>
                        <div class="bg-white p-2.5 rounded border border-warning-subtle text-secondary small">
                            <strong class="d-block text-dark mb-1">Reason for Archiving:</strong>
                            <div id="consultModalReason" class="text-wrap" style="white-space: pre-line;">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
                <div id="consultModalActionContainer">
                    <!-- Populated dynamically with restore encounter form button -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. DataTables initialization
    <?php if (!empty($patients)): ?>
        $('#archivedPatientsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[4, "desc"]], // Sort by archive date descending
            "columnDefs": [
                { "orderable": false, "targets": 7 } // Actions
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>

    <?php if (!empty($consultations)): ?>
        $('#archivedConsultationsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "desc"]], // Sort by archive date descending
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Actions
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>

    <?php if (!empty($users)): ?>
        $('#archivedUsersTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[4, "desc"]], // Sort by archive date descending
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Actions
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>

    // 2. Tab switching URL preservation
    const archiveTabs = document.querySelectorAll('#archiveTabs button[data-bs-toggle="pill"]');
    archiveTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            let tabName = 'patients';
            if (target === '#tab-consultations') {
                tabName = 'consultations';
            } else if (target === '#tab-users') {
                tabName = 'users';
            }
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState(null, '', url);
        });
    });

    // 3. Restore Patient Confirmation with SweetAlert (Delegated for DataTables pagination)
    $(document).on('click', '.btn-restore-patient', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const name = $(this).data('name') || 'this patient';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Restore Patient Record?',
                text: `Are you sure you want to restore the patient record for ${name}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Restore Record',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else if (confirm(`Are you sure you want to restore the patient record for ${name}?`)) {
            form.submit();
        }
    });

    // 4. Restore Consultation Confirmation with SweetAlert (Delegated for DataTables pagination)
    $(document).on('click', '.btn-restore-consultation', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const patient = $(this).data('patient') || 'this patient';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Restore Consultation?',
                text: `Are you sure you want to restore this consultation record for ${patient}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Restore Record',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else if (confirm(`Are you sure you want to restore this consultation record for ${patient}?`)) {
            form.submit();
        }
    });

    // 5. Restore User Account Confirmation with SweetAlert (Delegated for DataTables pagination)
    $(document).on('click', '.btn-restore-user', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const username = $(this).data('username') || 'this user';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Restore User Account?',
                text: `Are you sure you want to restore user account @${username}? They will regain access to the system.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Restore Account',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else if (confirm(`Are you sure you want to restore user account @${username}?`)) {
            form.submit();
        }
    });

    // 6. View Archived Patient Details Modal
    $(document).on('click', '.btn-view-patient-archive', function() {
        const rawData = $(this).attr('data-patient');
        if (!rawData) return;
        try {
            const p = JSON.parse(rawData);
            $('#patientModalName').text(p.name || 'Unnamed Patient');
            $('#patientModalNo').text(p.patient_no || '—');
            $('#patientModalAgeSex').text(`${p.age || '—'} yrs / ${p.sex || '—'}`);
            $('#patientModalDob').text(p.dob || '—');
            $('#patientModalCivil').text(p.civil_status || '—');
            $('#patientModalContact').text(p.contact_no || '—');
            $('#patientModalAddress').text(p.address || '—');
            $('#patientModalPhilHealthStatus').text(p.philhealth_status || 'None');
            $('#patientModalPhilHealthNo').text(p.philhealth_no || '—');
            $('#patientModalRegisteredAt').text(p.created_at || '—');
            $('#patientModalArchiver').text(p.archiver_name || 'System');
            $('#patientModalArchivedAt').text(p.deleted_at || '—');
            $('#patientModalReason').text(p.archive_reason || 'No archive reason recorded.');

            // Setup Restore Button in Modal Footer
            const restoreActionHtml = `
                <form action="<?= url('/archive/patients/') ?>${p.id}/restore" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-primary px-3 btn-restore-patient" data-name="${p.name}">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Patient Record
                    </button>
                </form>
            `;
            $('#patientModalActionContainer').html(restoreActionHtml);

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPatientArchiveDetails'));
            modal.show();
        } catch (e) {
            console.error('Error parsing patient payload:', e);
        }
    });

    // 7. Inspect Archived Consultation Modal
    $(document).on('click', '.btn-view-consultation-archive', function() {
        const rawData = $(this).attr('data-consultation');
        if (!rawData) return;
        try {
            const c = JSON.parse(rawData);
            $('#consultModalPatientName').text(c.patient_name || 'Patient');
            $('#consultModalPatientNo').text(`(${c.patient_no || '—'})`);
            $('#consultModalClinician').text(c.clinician_name || 'Unassigned Clinician');
            $('#consultModalDate').text(c.consulted_at || '—');
            $('#consultModalStatus').text(c.status || 'Completed');

            // Vitals
            $('#vitalBP').text(c.vitals?.blood_pressure || '—');
            $('#vitalHR').text(c.vitals?.heart_rate || '—');
            $('#vitalTemp').text(c.vitals?.temperature || '—');
            $('#vitalRR').text(c.vitals?.respiratory_rate || '—');
            const wh = [];
            if (c.vitals?.weight && c.vitals.weight !== '—') wh.push(c.vitals.weight);
            if (c.vitals?.height && c.vitals.height !== '—') wh.push(c.vitals.height);
            $('#vitalWeightHeight').text(wh.length ? wh.join(' / ') : '—');
            $('#vitalBMI').text(c.vitals?.bmi || '—');

            // SOAP
            $('#consultModalSubjective').text(c.subjective || 'None documented.');
            $('#consultModalObjective').text(c.objective || 'None documented.');
            $('#consultModalAssessment').text(c.assessment || 'None documented.');
            $('#consultModalPlan').text(c.plan || 'None documented.');

            // Prescriptions
            const pBody = $('#consultModalPrescriptionsBody');
            pBody.empty();
            if (c.prescriptions && c.prescriptions.length > 0) {
                c.prescriptions.forEach(pr => {
                    const freqDur = [pr.frequency, pr.duration].filter(Boolean).join(' • ') || '—';
                    pBody.append(`
                        <tr>
                            <td class="fw-semibold text-dark">${pr.medicine || '—'}</td>
                            <td>${pr.dosage || '—'}</td>
                            <td>${freqDur}</td>
                            <td>${pr.instructions || '—'}</td>
                        </tr>
                    `);
                });
            } else {
                pBody.append(`<tr><td colspan="4" class="text-center text-muted py-2">No prescriptions documented.</td></tr>`);
            }

            // Archival info
            $('#consultModalArchiver').text(c.archiver_name || 'System');
            $('#consultModalArchivedAt').text(c.deleted_at || '—');
            $('#consultModalReason').text(c.archive_reason || 'Archived by staff');

            // Setup Restore Button in Modal Footer
            const restoreActionHtml = `
                <form action="<?= url('/archive/consultations/') ?>${c.id}/restore" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-primary px-3 btn-restore-consultation" data-patient="${c.patient_name}">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Encounter
                    </button>
                </form>
            `;
            $('#consultModalActionContainer').html(restoreActionHtml);

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConsultationArchiveDetails'));
            modal.show();
        } catch (e) {
            console.error('Error parsing consultation payload:', e);
        }
    });
});
</script>
