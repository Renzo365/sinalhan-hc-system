<?php
$title = 'Archived Records Hub';
$breadcrumbs = [
    'Archived Records' => null
];
require dirname(__DIR__) . '/layout/header.php';

$activeTab = $activeTab ?? 'patients';
$totalPatients = (int)($tabCounts['patients'] ?? count($patients ?? []));
$totalConsultations = (int)($tabCounts['consultations'] ?? count($consultations ?? []));
$totalUsers = (int)($tabCounts['users'] ?? count($users ?? []));
?>

<style>
/* Modern Unified Card & Tab Styles for Archive Hub */
.archive-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    transition: all 0.2s ease;
}
.archive-stat-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.archive-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

#archiveTabs {
    border-bottom: 2px solid #f1f5f9;
}
#archiveTabs .nav-link {
    color: #64748b;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    border-radius: 0;
    font-weight: 600;
    padding: 0.85rem 1.25rem;
    transition: all 0.2s ease;
}
#archiveTabs .nav-link:hover {
    color: #0d9488;
    background: rgba(13, 148, 136, 0.03);
}
#archiveTabs .nav-link.active {
    color: #0d9488 !important;
    background: transparent !important;
    border-bottom: 2px solid #0d9488 !important;
}
#archiveTabs .nav-link.active .badge {
    background-color: #0d9488 !important;
    color: #ffffff !important;
}
#archiveTabs .nav-link:not(.active) .badge {
    background-color: #f1f5f9 !important;
    color: #64748b !important;
}

.archive-filter-bar {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 1.25rem;
}

.table thead th {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 700;
    color: #64748b;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.archive-action-btn {
    min-width: 86px;
    font-size: 0.82rem;
    font-weight: 500;
}

.dropdown-item i {
    width: 1.25rem;
}
</style>

<!-- Page Header Title -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Archived Records Hub</h2>
        <p class="text-secondary small mb-0">Demographic patient records, clinical consultation logs, and inactive staff accounts.</p>
    </div>
</div>

<!-- 1. Top Situational Awareness Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="archive-stat-card d-flex align-items-center gap-3">
            <div class="archive-stat-icon bg-teal-subtle text-teal">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="text-secondary small fw-medium">Archived Patients</div>
                <div class="fs-4 fw-bold text-dark"><?= number_format($totalPatients) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="archive-stat-card d-flex align-items-center gap-3">
            <div class="archive-stat-icon bg-info-subtle text-info">
                <i class="bi bi-clipboard2-pulse-fill"></i>
            </div>
            <div>
                <div class="text-secondary small fw-medium">Archived Consultations</div>
                <div class="fs-4 fw-bold text-dark"><?= number_format($totalConsultations) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="archive-stat-card d-flex align-items-center gap-3">
            <div class="archive-stat-icon bg-warning-subtle text-warning">
                <i class="bi bi-person-x-fill"></i>
            </div>
            <div>
                <div class="text-secondary small fw-medium">Archived User Accounts</div>
                <div class="fs-4 fw-bold text-dark"><?= number_format($totalUsers) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- 2. Main Unified Archive Card -->
<div class="card card-premium shadow-sm border-0 mb-4 overflow-hidden">
    
    <!-- Unified Tab Navigation Header -->
    <div class="card-header bg-white px-3 pt-2 pb-0 border-bottom-0">
        <ul class="nav nav-tabs border-0 gap-1" id="archiveTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'patients' ? 'active' : '' ?>" id="tab-patients-btn" data-bs-toggle="tab" data-bs-target="#tab-patients" type="button" role="tab">
                    <i class="bi bi-people-fill me-1.5"></i> Archived Patients
                    <span class="badge rounded-pill ms-2"><?= $totalPatients ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'consultations' ? 'active' : '' ?>" id="tab-consultations-btn" data-bs-toggle="tab" data-bs-target="#tab-consultations" type="button" role="tab">
                    <i class="bi bi-clipboard2-pulse-fill me-1.5"></i> Archived Consultations
                    <span class="badge rounded-pill ms-2"><?= $totalConsultations ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'users' ? 'active' : '' ?>" id="tab-users-btn" data-bs-toggle="tab" data-bs-target="#tab-users" type="button" role="tab">
                    <i class="bi bi-person-x-fill me-1.5"></i> Archived User Accounts
                    <span class="badge rounded-pill ms-2"><?= $totalUsers ?></span>
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content" id="archiveTabsContent">

        <!-- ==============================================================
           TAB 1: ARCHIVED PATIENTS
           ============================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'patients' ? 'show active' : '' ?>" id="tab-patients" role="tabpanel">
            
            <!-- Compact Filter Toolbar for Patients -->
            <div class="archive-filter-bar">
                <form action="<?= url('/archive') ?>" method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="patients">
                    
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search_patients" class="form-control border-start-0 bg-white" placeholder="Search by name, patient no, envelope..." value="<?= h($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary small" style="font-size: 0.75rem;">From</span>
                            <input type="date" name="date_from" id="date_from_patients" class="form-control bg-white" value="<?= h($filters['date_from'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary small" style="font-size: 0.75rem;">To</span>
                            <input type="date" name="date_to" id="date_to_patients" class="form-control bg-white" value="<?= h($filters['date_to'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-2 d-flex gap-1.5 justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary px-3 flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="<?= url('/archive?tab=patients') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Archived Patients Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small" id="archivedPatientsTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="ps-3 text-nowrap">Patient No</th>
                            <th class="text-start">Name & Demographics</th>
                            <th>Address</th>
                            <th class="text-nowrap">Archival Audit</th>
                            <th>Reason for Archiving</th>
                            <th class="pe-3 text-end" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($patients)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-archive d-block fs-3 mb-2 text-muted"></i>
                                    No archived patient records match the criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($patients as $p): 
                                $fullName = trim($p['last_name'] . ', ' . $p['first_name'] . ' ' . ($p['middle_name'] ?? '') . ($p['suffix'] ? ' ' . $p['suffix'] : ''));
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
                                    'philhealth_status' => $p['phic_status'] ?? 'Non-Member',
                                    'philhealth_no' => $p['philhealth_no'] ?: '—',
                                    'created_at' => $p['created_at'] ? date('M d, Y h:i A', strtotime($p['created_at'])) : '—',
                                    'deleted_at' => $p['deleted_at'] ? date('M d, Y h:i A', strtotime($p['deleted_at'])) : '—',
                                    'archiver_name' => !empty(trim($p['archiver_name'] ?? '')) ? $p['archiver_name'] : 'System',
                                    'archive_reason' => $p['archive_reason'] ?: 'No archive reason recorded.'
                                ];
                            ?>
                                <tr>
                                    <td class="ps-3 text-nowrap">
                                        <span class="font-monospace fw-bold text-secondary"><?= h($p['patient_no']) ?></span>
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark"><?= h($fullName) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            <?= h($p['age']) ?> yrs &bull; <?= h($p['sex']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-secondary text-truncate" style="max-width: 260px;" title="<?= h($p['address'] ?: '—') ?>">
                                            <?= h(!empty(trim($p['address'] ?? '')) ? $p['address'] : '—') ?>
                                        </div>
                                    </td>
                                    <td class="text-nowrap" data-order="<?= h($p['deleted_at']) ?>">
                                        <div class="fw-medium text-dark"><?= date('Y-m-d h:i A', strtotime($p['deleted_at'])) ?></div>
                                        <div class="text-muted small" style="font-size: 0.73rem;">
                                            By: <span class="badge bg-light text-secondary border"><?= h(!empty(trim($p['archiver_name'] ?? '')) ? $p['archiver_name'] : 'System') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-secondary text-truncate" style="max-width: 220px;" title="<?= h($p['archive_reason']) ?>">
                                            <?= h($p['archive_reason']) ?>
                                        </div>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-outline-secondary px-2.5 py-1 dropdown-toggle archive-action-btn shadow-2xs" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown" 
                                                    aria-expanded="false" 
                                                    title="Patient archival actions">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="font-size: 0.85rem;">
                                                <li>
                                                    <button type="button" 
                                                            class="dropdown-item py-1.5 btn-view-patient-archive" 
                                                            data-patient='<?= htmlspecialchars(json_encode($patientPayload), ENT_QUOTES, 'UTF-8') ?>'>
                                                        <i class="bi bi-eye text-primary me-2"></i> View Profile Details
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <form action="<?= url('/archive/patients/' . $p['id'] . '/restore') ?>" method="POST" class="d-inline restore-patient-form">
                                                        <?= csrf_field() ?>
                                                        <button type="button" 
                                                                class="dropdown-item py-1.5 text-success btn-restore-patient" 
                                                                data-name="<?= h($patientPayload['name']) ?>">
                                                            <i class="bi bi-arrow-counterclockwise text-success me-2"></i> Restore Record
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==============================================================
           TAB 2: ARCHIVED CONSULTATIONS
           ============================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'consultations' ? 'show active' : '' ?>" id="tab-consultations" role="tabpanel">
            
            <!-- Compact Filter Toolbar for Consultations -->
            <div class="archive-filter-bar">
                <form action="<?= url('/archive') ?>" method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="consultations">
                    
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search_consultations" class="form-control border-start-0 bg-white" placeholder="Search by patient name, no., or diagnosis..." value="<?= h($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary small" style="font-size: 0.75rem;">From</span>
                            <input type="date" name="date_from" id="date_from_consultations" class="form-control bg-white" value="<?= h($filters['date_from'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary small" style="font-size: 0.75rem;">To</span>
                            <input type="date" name="date_to" id="date_to_consultations" class="form-control bg-white" value="<?= h($filters['date_to'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-2 d-flex gap-1.5 justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary px-3 flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="<?= url('/archive?tab=consultations') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Archived Consultations Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small" id="archivedConsultationsTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="ps-3 text-nowrap">Dates (Encounter / Archive)</th>
                            <th class="text-start">Patient Details</th>
                            <th>Clinician</th>
                            <th class="text-start">Assessment / Diagnosis</th>
                            <th>Archival Audit</th>
                            <th class="pe-3 text-end" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultations)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard2-x d-block fs-3 mb-2 text-muted"></i>
                                    No archived consultation records match the criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($consultations as $c): 
                                $patFullName = trim($c['pat_first'] . ' ' . ($c['pat_middle'] ? $c['pat_middle'] . ' ' : '') . $c['pat_last'] . ($c['pat_suffix'] ? ' ' . $c['pat_suffix'] : ''));
                                $isParentArchived = !empty($c['patient_deleted_at']);
                                $consultationPayload = [
                                    'id' => $c['id'],
                                    'patient_id' => $c['patient_id'],
                                    'patient_no' => $c['patient_no'],
                                    'patient_name' => $patFullName,
                                    'is_parent_archived' => $isParentArchived,
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
                                        'temperature' => !empty($c['temperature']) ? number_format((float)$c['temperature'], 1) . ' °C' : '—',
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
                                    <td class="ps-3 text-nowrap" data-order="<?= h($c['deleted_at']) ?>">
                                        <div class="fw-semibold text-dark">
                                            <i class="bi bi-calendar-check me-1 text-primary"></i><?= date('Y-m-d h:i A', strtotime($c['consulted_at'])) ?>
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.74rem;">
                                            <i class="bi bi-archive me-1 text-secondary"></i>Archived <?= date('Y-m-d h:i A', strtotime($c['deleted_at'])) ?>
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <span class="fw-bold text-dark"><?= h($patFullName) ?></span>
                                            <?php if ($isParentArchived): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.68rem;" title="Parent patient chart is also archived. Restore the patient first.">
                                                    <i class="bi bi-exclamation-triangle-fill me-0.5"></i> Patient Archived
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="font-monospace text-secondary small" style="font-size: 0.75rem;"><?= h($c['patient_no']) ?></div>
                                    </td>
                                    <td class="text-nowrap"><?= h($c['clinician_name'] ?? 'Clinician') ?></td>
                                    <td class="text-start">
                                        <div class="text-truncate" style="max-width: 250px;" title="<?= h($c['assessment']) ?>">
                                            <?= h($c['assessment']) ?>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="text-secondary text-truncate" style="max-width: 180px;" title="<?= h($c['archive_reason'] ?? 'Archived by staff') ?>">
                                            <?= h($c['archive_reason'] ?? 'Archived by staff') ?>
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.73rem;">
                                            By: <span class="badge bg-light text-secondary border"><?= h(!empty(trim($c['archiver_name'] ?? '')) ? $c['archiver_name'] : 'System') ?></span>
                                        </div>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-outline-secondary px-2.5 py-1 dropdown-toggle archive-action-btn shadow-2xs" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown" 
                                                    aria-expanded="false" 
                                                    title="Consultation actions">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="font-size: 0.85rem;">
                                                <li>
                                                    <button type="button" 
                                                            class="dropdown-item py-1.5 btn-view-consultation-archive" 
                                                            data-consultation='<?= htmlspecialchars(json_encode($consultationPayload), ENT_QUOTES, 'UTF-8') ?>'>
                                                        <i class="bi bi-journal-medical text-primary me-2"></i> Inspect Clinical Notes
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <?php if ($isParentArchived): ?>
                                                    <li>
                                                        <span class="d-inline-block w-100" title="Cannot restore encounter: The parent patient is archived. Please restore the patient first.">
                                                            <button type="button" class="dropdown-item py-1.5 text-muted disabled" disabled style="pointer-events: none;">
                                                                <i class="bi bi-lock me-2"></i> Patient Archived
                                                            </button>
                                                        </span>
                                                    </li>
                                                <?php else: ?>
                                                    <li>
                                                        <form action="<?= url('/archive/consultations/' . $c['id'] . '/restore') ?>" method="POST" class="d-inline restore-consultation-form">
                                                            <?= csrf_field() ?>
                                                            <button type="button" 
                                                                    class="dropdown-item py-1.5 text-success btn-restore-consultation" 
                                                                    data-patient="<?= h($patFullName) ?>">
                                                                <i class="bi bi-arrow-counterclockwise text-success me-2"></i> Restore Encounter
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==============================================================
           TAB 3: ARCHIVED USER ACCOUNTS
           ============================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'users' ? 'show active' : '' ?>" id="tab-users" role="tabpanel">
            
            <!-- Compact Filter Toolbar for Users -->
            <div class="archive-filter-bar">
                <form action="<?= url('/archive') ?>" method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="users">
                    
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search_users" class="form-control border-start-0 bg-white" placeholder="Search username, name, email, employee ID..." value="<?= h($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-4 col-md-2">
                        <select name="role" id="role_users" class="form-select form-select-sm bg-white">
                            <option value="">-- All Roles --</option>
                            <option value="admin" <?= (isset($filters['role']) && $filters['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
                            <option value="staff" <?= (isset($filters['role']) && $filters['role'] === 'staff') ? 'selected' : '' ?>>Staff Personnel</option>
                        </select>
                    </div>

                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary small" style="font-size: 0.75rem;">From</span>
                            <input type="date" name="date_from" id="date_from_users" class="form-control bg-white" value="<?= h($filters['date_from'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary small" style="font-size: 0.75rem;">To</span>
                            <input type="date" name="date_to" id="date_to_users" class="form-control bg-white" value="<?= h($filters['date_to'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-2 d-flex gap-1.5 justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary px-3 flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="<?= url('/archive?tab=users') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Archived Users Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small" id="archivedUsersTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="text-start ps-3 text-nowrap">Username</th>
                            <th class="text-start">Full Name & Email</th>
                            <th>Role</th>
                            <th>Job Title & Dept</th>
                            <th>Archived Date</th>
                            <th class="pe-3 text-end" style="width: 100px;">Action</th>
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
                                $roleBadge = $u['role'] === 'admin' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-secondary border';
                                $roleDisplay = $u['role'] === 'admin' ? 'Admin' : 'Staff';
                                $canRestoreUser = true;
                                $disabledUserReason = '';
                                if ($u['role'] === 'admin' && !is_super_admin()) {
                                    $canRestoreUser = false;
                                    $disabledUserReason = 'Only Super Admin can restore administrator accounts';
                                }

                                $userPayload = [
                                    'id' => $u['id'],
                                    'username' => $u['username'],
                                    'name' => trim($u['first_name'] . ' ' . ($u['middle_name'] ? $u['middle_name'] . ' ' : '') . $u['last_name']),
                                    'role' => $roleDisplay,
                                    'email' => $u['email'] ?: '—',
                                    'contact_no' => $u['contact_no'] ?: '—',
                                    'job_title' => $u['job_title'] ?: '—',
                                    'department' => $u['department'] ?: '—',
                                    'employee_id' => $u['employee_id'] ?: '—',
                                    'last_login_at' => $u['last_login_at'] ? date('M d, Y h:i A', strtotime($u['last_login_at'])) : 'Never',
                                    'deleted_at' => $u['deleted_at'] ? date('M d, Y h:i A', strtotime($u['deleted_at'])) : '—',
                                    'can_restore' => $canRestoreUser,
                                    'disabled_reason' => $disabledUserReason
                                ];
                            ?>
                                <tr>
                                    <td class="text-start ps-3 fw-bold font-monospace text-dark text-nowrap">
                                        <?= h($u['username']) ?>
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark"><?= h($u['last_name']) ?>, <?= h($u['first_name']) ?></div>
                                        <?php if (!empty($u['email'])): ?>
                                            <div class="text-muted small" style="font-size: 0.74rem;"><?= h($u['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= $roleBadge ?> px-2 py-1"><?= $roleDisplay ?></span></td>
                                    <td>
                                        <div class="fw-medium text-dark"><?= h($u['job_title'] ?: '—') ?></div>
                                        <?php if (!empty($u['department'])): ?>
                                            <div class="text-muted small" style="font-size: 0.72rem;"><?= h($u['department']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap" data-order="<?= $u['deleted_at'] ? h($u['deleted_at']) : '' ?>">
                                        <?= $u['deleted_at'] ? date('Y-m-d h:i A', strtotime($u['deleted_at'])) : '—' ?>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-outline-secondary px-2.5 py-1 dropdown-toggle archive-action-btn shadow-2xs" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown" 
                                                    aria-expanded="false" 
                                                    title="User account actions">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="font-size: 0.85rem;">
                                                <li>
                                                    <button type="button" 
                                                            class="dropdown-item py-1.5 btn-view-user-archive" 
                                                            data-user='<?= htmlspecialchars(json_encode($userPayload), ENT_QUOTES, 'UTF-8') ?>'>
                                                        <i class="bi bi-person-lines-fill text-primary me-2"></i> View Account Details
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <?php if ($canRestoreUser): ?>
                                                    <li>
                                                        <form action="<?= url('/archive/users/' . $u['id'] . '/restore') ?>" method="POST" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <button type="button" 
                                                                    class="dropdown-item py-1.5 text-success btn-restore-user" 
                                                                    data-username="<?= h($u['username']) ?>">
                                                                <i class="bi bi-arrow-counterclockwise text-success me-2"></i> Restore Account
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php else: ?>
                                                    <li>
                                                        <span class="d-inline-block w-100" title="<?= h($disabledUserReason) ?>">
                                                            <button type="button" class="dropdown-item py-1.5 text-muted disabled" disabled style="pointer-events: none;">
                                                                <i class="bi bi-lock me-2"></i> Super Admin Only
                                                            </button>
                                                        </span>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
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

<!-- ==========================================================================
   MODAL 1: PATIENT ARCHIVE DETAILS (Redesigned with Neutral Audit Card)
   ========================================================================== -->
<div class="modal fade" id="modalPatientArchiveDetails" tabindex="-1" aria-labelledby="modalPatientArchiveDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-teal-subtle text-teal p-2.5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-person-vcard fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="patientModalName">Patient Name</h5>
                        <div class="small font-monospace text-secondary" id="patientModalNo">P-XXXX-XXXXX</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <!-- Demographic Matrix -->
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
                        <div class="fw-bold text-dark font-monospace" id="patientModalContact">—</div>
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
                    <div class="col-12">
                        <div class="text-secondary small fw-semibold">Registration Date</div>
                        <div class="text-dark small" id="patientModalRegisteredAt">—</div>
                    </div>
                </div>

                <!-- Clean Neutral Archival Metadata Card -->
                <div class="card bg-light border-0 rounded-3 p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-secondary"></i>
                            <h6 class="fw-bold text-dark mb-0 small text-uppercase letter-spacing-1">Archival Audit Log</h6>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle small" id="patientModalArchivedAt">—</span>
                    </div>
                    <div class="small text-secondary mb-2">
                        Archived by: <strong class="text-dark" id="patientModalArchiver">—</strong>
                    </div>
                    <div class="bg-white p-2.5 rounded border text-secondary small">
                        <span class="fw-semibold text-dark d-block mb-1">Reason for Archiving:</span>
                        <div id="patientModalReason" class="text-dark" style="white-space: pre-line;">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
                <div id="patientModalActionContainer">
                    <!-- Dynamic Restore Button inserted here safely -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   MODAL 2: CONSULTATION ARCHIVE DETAILS
   ========================================================================== -->
<div class="modal fade" id="modalConsultationArchiveDetails" tabindex="-1" aria-labelledby="modalConsultationArchiveDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-teal-subtle text-teal p-2.5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
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
            <div class="modal-body p-4 bg-white">
                <!-- Encounter Metadata Bar -->
                <div class="row g-3 p-3 bg-light rounded-3 mb-4 border-0">
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
                        <div class="col-4 col-md-2 p-2 bg-light rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Blood Pressure</div>
                            <div class="fw-bold text-dark small" id="vitalBP">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-light rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Heart Rate</div>
                            <div class="fw-bold text-dark small" id="vitalHR">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-light rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Temp</div>
                            <div class="fw-bold text-dark small" id="vitalTemp">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-light rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Resp Rate</div>
                            <div class="fw-bold text-dark small" id="vitalRR">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-light rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">Weight / Height</div>
                            <div class="fw-bold text-dark small" id="vitalWeightHeight">—</div>
                        </div>
                        <div class="col-4 col-md-2 p-2 bg-light rounded">
                            <div class="text-secondary small" style="font-size: 0.72rem;">BMI</div>
                            <div class="fw-bold text-dark small" id="vitalBMI">—</div>
                        </div>
                    </div>
                </div>

                <!-- Clinical SOAP Notes -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary-dark mb-2"><i class="bi bi-journal-medical me-1 text-primary"></i> SOAP Encounter Notes</h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="p-3 bg-light rounded">
                            <div class="fw-bold text-primary small mb-1">Subjective (Chief Complaint & History)</div>
                            <div class="text-dark small" id="consultModalSubjective">—</div>
                        </div>
                        <div class="p-3 bg-light rounded">
                            <div class="fw-bold text-primary small mb-1">Objective (Physical Examination Findings)</div>
                            <div class="text-dark small" id="consultModalObjective">—</div>
                        </div>
                        <div class="p-3 bg-light rounded">
                            <div class="fw-bold text-primary small mb-1">Assessment (Clinical Diagnosis)</div>
                            <div class="text-dark small fw-semibold" id="consultModalAssessment">—</div>
                        </div>
                        <div class="p-3 bg-light rounded">
                            <div class="fw-bold text-primary small mb-1">Plan (Medical Management)</div>
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

                <!-- Clean Neutral Archival Metadata Card -->
                <div class="card bg-light border-0 rounded-3 p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-secondary"></i>
                            <h6 class="fw-bold text-dark mb-0 small text-uppercase letter-spacing-1">Archival Audit Log</h6>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle small" id="consultModalArchivedAt">—</span>
                    </div>
                    <div class="small text-secondary mb-2">
                        Archived by: <strong class="text-dark" id="consultModalArchiver">—</strong>
                    </div>
                    <div class="bg-white p-2.5 rounded border text-secondary small">
                        <span class="fw-semibold text-dark d-block mb-1">Reason for Archiving:</span>
                        <div id="consultModalReason" class="text-dark" style="white-space: pre-line;">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
                <div id="consultModalActionContainer">
                    <!-- Dynamic Restore Button inserted here safely -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   MODAL 3: USER ARCHIVE DETAILS (NEW)
   ========================================================================== -->
<div class="modal fade" id="modalUserArchiveDetails" tabindex="-1" aria-labelledby="modalUserArchiveDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning-subtle text-warning p-2.5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-person-x fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="userModalName">Staff Name</h5>
                        <div class="small font-monospace text-secondary" id="userModalUsername">@username</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="text-secondary small fw-semibold">Role</div>
                        <span class="badge bg-light text-dark border px-2 py-1" id="userModalRole">Staff</span>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small fw-semibold">Employee ID</div>
                        <div class="fw-bold font-monospace text-dark" id="userModalEmployeeId">—</div>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small fw-semibold">Job Title</div>
                        <div class="fw-medium text-dark" id="userModalJobTitle">—</div>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small fw-semibold">Department</div>
                        <div class="fw-medium text-dark" id="userModalDepartment">—</div>
                    </div>
                    <div class="col-12">
                        <div class="text-secondary small fw-semibold">Email Address</div>
                        <div class="text-dark" id="userModalEmail">—</div>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small fw-semibold">Contact No.</div>
                        <div class="text-dark font-monospace" id="userModalContact">—</div>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small fw-semibold">Last Login</div>
                        <div class="text-dark small" id="userModalLastLogin">—</div>
                    </div>
                </div>

                <div class="card bg-light border-0 rounded-3 p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-secondary small fw-semibold">Account Archival Date:</span>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" id="userModalArchivedAt">—</span>
                    </div>
                    <div class="text-muted small" style="font-size: 0.75rem;">
                        Archived staff accounts are restricted from accessing system workstations until restored by an administrator.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
                <div id="userModalActionContainer">
                    <!-- Dynamic Restore Button inserted here safely -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. DataTables initialization
    const dtConfig = {
        "paging": true,
        "lengthChange": true,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "language": {
            "paginate": {
                "previous": "<i class='bi bi-chevron-left'></i>",
                "next": "<i class='bi bi-chevron-right'></i>"
            }
        }
    };

    <?php if (!empty($patients)): ?>
        $('#archivedPatientsTable').DataTable(Object.assign({}, dtConfig, {
            "order": [[3, "desc"]], // Sort by archive date descending
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Actions
            ]
        }));
    <?php endif; ?>

    <?php if (!empty($consultations)): ?>
        $('#archivedConsultationsTable').DataTable(Object.assign({}, dtConfig, {
            "order": [[0, "desc"]], // Sort by archive date descending
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Actions
            ]
        }));
    <?php endif; ?>

    <?php if (!empty($users)): ?>
        $('#archivedUsersTable').DataTable(Object.assign({}, dtConfig, {
            "order": [[4, "desc"]], // Sort by archive date descending
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Actions
            ]
        }));
    <?php endif; ?>

    // 2. Tab switching URL & column adjustment fixes [BUG-ARCH-06 & BUG-ARCH-07]
    const archiveTabs = document.querySelectorAll('#archiveTabs button[data-bs-toggle="tab"]');
    archiveTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            let tabName = 'patients';
            if (target === '#tab-consultations') {
                tabName = 'consultations';
            } else if (target === '#tab-users') {
                tabName = 'users';
            }

            // Adjust DataTables column widths for the newly visible tab pane
            if (typeof $.fn.dataTable !== 'undefined') {
                $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
            }

            // Cleanly update URL without leaking previous tab's search queries
            const url = new URL(window.location);
            const currentTabInUrl = url.searchParams.get('tab');
            if (currentTabInUrl !== tabName) {
                url.search = ''; // Clear prior search/date parameters
                url.searchParams.set('tab', tabName);
                window.history.replaceState(null, '', url);
            }
        });
    });

    // 3. Restore Patient Confirmation with SweetAlert
    $(document).on('click', '.btn-restore-patient', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const name = $(this).attr('data-name') || 'this patient';

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

    // 4. Restore Consultation Confirmation with SweetAlert
    $(document).on('click', '.btn-restore-consultation', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const patient = $(this).attr('data-patient') || 'this patient';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Restore Consultation Encounter?',
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

    // 5. Restore User Account Confirmation with SweetAlert
    $(document).on('click', '.btn-restore-user', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const username = $(this).attr('data-username') || 'this user';

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

    // 6. View Archived Patient Details Modal [BUG-ARCH-01 & BUG-ARCH-02 SAFE ATTRIBUTE INJECTION]
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
            $('#patientModalPhilHealthStatus').text(p.philhealth_status || 'Non-Member');
            $('#patientModalPhilHealthNo').text(p.philhealth_no || '—');
            $('#patientModalRegisteredAt').text(p.created_at || '—');
            $('#patientModalArchiver').text(p.archiver_name || 'System');
            $('#patientModalArchivedAt').text(p.deleted_at || '—');
            $('#patientModalReason').text(p.archive_reason || 'No archive reason recorded.');

            // Setup Safe Restore Button in Modal Footer (prevents HTML quote injection)
            const restoreForm = $('<form>', {
                action: '<?= url('/archive/patients/') ?>' + encodeURIComponent(p.id) + '/restore',
                method: 'POST',
                class: 'd-inline'
            });
            restoreForm.append('<?= csrf_field() ?>');
            const restoreBtn = $('<button>', {
                type: 'button',
                class: 'btn btn-primary px-3 btn-restore-patient',
                html: '<i class="bi bi-arrow-counterclockwise me-1"></i> Restore Patient Record'
            }).attr('data-name', p.name || 'this patient');
            restoreForm.append(restoreBtn);

            $('#patientModalActionContainer').empty().append(restoreForm);

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

            // Setup Safe Restore Button or Disabled Locked Button
            $('#consultModalActionContainer').empty();
            if (c.is_parent_archived) {
                const lockedBtn = $('<button>', {
                    type: 'button',
                    class: 'btn btn-secondary px-3',
                    disabled: true,
                    title: 'Cannot restore: Parent patient chart is currently archived. Restore patient first.',
                    html: '<i class="bi bi-lock me-1"></i> Patient Record Archived'
                });
                $('#consultModalActionContainer').append(lockedBtn);
            } else {
                const restoreForm = $('<form>', {
                    action: '<?= url('/archive/consultations/') ?>' + encodeURIComponent(c.id) + '/restore',
                    method: 'POST',
                    class: 'd-inline'
                });
                restoreForm.append('<?= csrf_field() ?>');
                const restoreBtn = $('<button>', {
                    type: 'button',
                    class: 'btn btn-primary px-3 btn-restore-consultation',
                    html: '<i class="bi bi-arrow-counterclockwise me-1"></i> Restore Encounter'
                }).attr('data-patient', c.patient_name || 'this patient');
                restoreForm.append(restoreBtn);

                $('#consultModalActionContainer').append(restoreForm);
            }

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConsultationArchiveDetails'));
            modal.show();
        } catch (e) {
            console.error('Error parsing consultation payload:', e);
        }
    });

    // 8. View Archived User Details Modal [BUG-ARCH-10]
    $(document).on('click', '.btn-view-user-archive', function() {
        const rawData = $(this).attr('data-user');
        if (!rawData) return;
        try {
            const u = JSON.parse(rawData);
            $('#userModalName').text(u.name || 'Staff User');
            $('#userModalUsername').text('@' + (u.username || 'user'));
            $('#userModalRole').text(u.role || 'Staff');
            $('#userModalEmployeeId').text(u.employee_id || '—');
            $('#userModalJobTitle').text(u.job_title || '—');
            $('#userModalDepartment').text(u.department || '—');
            $('#userModalEmail').text(u.email || '—');
            $('#userModalContact').text(u.contact_no || '—');
            $('#userModalLastLogin').text(u.last_login_at || 'Never');
            $('#userModalArchivedAt').text(u.deleted_at || '—');

            // Setup Restore Button in Modal Footer
            $('#userModalActionContainer').empty();
            if (u.can_restore) {
                const restoreForm = $('<form>', {
                    action: '<?= url('/archive/users/') ?>' + encodeURIComponent(u.id) + '/restore',
                    method: 'POST',
                    class: 'd-inline'
                });
                restoreForm.append('<?= csrf_field() ?>');
                const restoreBtn = $('<button>', {
                    type: 'button',
                    class: 'btn btn-primary px-3 btn-restore-user',
                    html: '<i class="bi bi-arrow-counterclockwise me-1"></i> Restore Account'
                }).attr('data-username', u.username || 'this user');
                restoreForm.append(restoreBtn);
                $('#userModalActionContainer').append(restoreForm);
            } else {
                const lockedBtn = $('<button>', {
                    type: 'button',
                    class: 'btn btn-secondary px-3',
                    disabled: true,
                    title: u.disabled_reason || 'Access Denied',
                    html: '<i class="bi bi-lock me-1"></i> Super Admin Only'
                });
                $('#userModalActionContainer').append(lockedBtn);
            }

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUserArchiveDetails'));
            modal.show();
        } catch (e) {
            console.error('Error parsing user payload:', e);
        }
    });
});
</script>
