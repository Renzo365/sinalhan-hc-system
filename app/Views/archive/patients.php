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
        <p class="text-secondary small mb-0">Demographic records and clinical consultation records archived by administrators.</p>
    </div>
</div>

<!-- Tabbed Navigation -->
<ul class="nav nav-pills mb-4 gap-2" id="archiveTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'patients' ? 'active' : '' ?> px-4 py-2 fw-semibold" id="tab-patients-btn" data-bs-toggle="pill" data-bs-target="#tab-patients" type="button" role="tab">
            <i class="bi bi-people-fill me-2"></i> Archived Patients
            <span class="badge <?= $activeTab === 'patients' ? 'bg-white text-primary' : 'bg-light text-secondary border' ?> ms-2"><?= count($patients ?? []) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'consultations' ? 'active' : '' ?> px-4 py-2 fw-semibold" id="tab-consultations-btn" data-bs-toggle="pill" data-bs-target="#tab-consultations" type="button" role="tab">
            <i class="bi bi-clipboard2-pulse-fill me-2"></i> Archived Consultations
            <span class="badge <?= $activeTab === 'consultations' ? 'bg-white text-primary' : 'bg-light text-secondary border' ?> ms-2"><?= count($consultations ?? []) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'users' ? 'active' : '' ?> px-4 py-2 fw-semibold" id="tab-users-btn" data-bs-toggle="pill" data-bs-target="#tab-users" type="button" role="tab">
            <i class="bi bi-person-x-fill me-2"></i> Archived Staff Accounts
            <span class="badge <?= $activeTab === 'users' ? 'bg-white text-primary' : 'bg-light text-secondary border' ?> ms-2"><?= count($users ?? []) ?></span>
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
                                <th>Barangay</th>
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
                                <?php foreach ($patients as $p): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary"><?= h($p['patient_no']) ?></td>
                                        <td class="text-start fw-bold text-dark">
                                            <?= h($p['last_name']) ?>, <?= h($p['first_name']) ?> <?= h($p['middle_name'] ?? '') ?>
                                        </td>
                                        <td><?= h($p['age']) ?> yrs / <?= h($p['sex']) ?></td>
                                        <td><?= h($p['barangay']) ?></td>
                                        <td><?= date('Y-m-d h:i A', strtotime($p['deleted_at'])) ?></td>
                                        <td><span class="badge bg-secondary"><?= h(!empty(trim($p['archiver_name'] ?? '')) ? $p['archiver_name'] : 'System') ?></span></td>
                                        <td class="text-secondary text-start text-truncate" style="max-width: 200px;" title="<?= h($p['archive_reason']) ?>">
                                            <?= h($p['archive_reason']) ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <div class="d-inline-flex gap-1">
                                                <form action="<?= url('/archive/patients/' . $p['id'] . '/restore') ?>" method="POST" class="d-inline restore-patient-form">
                                                    <?= csrf_field() ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success border-0 px-2 btn-restore-patient" data-name="<?= h($p['first_name'] . ' ' . $p['last_name']) ?>" title="Restore Patient Record">
                                                        <i class="bi bi-arrow-counterclockwise fs-5"></i>
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
                                <th>Archived Date</th>
                                <th class="text-start">Patient Name & No.</th>
                                <th>Clinician</th>
                                <th class="text-start">Assessment / Diagnosis</th>
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
                                <?php foreach ($consultations as $c): ?>
                                    <tr>
                                        <td><?= date('Y-m-d h:i A', strtotime($c['deleted_at'])) ?></td>
                                        <td class="text-start">
                                            <div class="fw-bold text-dark"><?= h($c['pat_first'] . ' ' . $c['pat_last']) ?></div>
                                            <span class="badge bg-light text-secondary border font-monospace"><?= h($c['patient_no']) ?></span>
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
                                        <td class="pe-4 text-end">
                                            <div class="d-inline-flex gap-1">
                                                <form action="<?= url('/archive/consultations/' . $c['id'] . '/restore') ?>" method="POST" class="d-inline restore-consultation-form">
                                                    <?= csrf_field() ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success border-0 px-2 btn-restore-consultation" data-patient="<?= h($c['pat_first'] . ' ' . $c['pat_last']) ?>" title="Restore Consultation Record">
                                                        <i class="bi bi-arrow-counterclockwise fs-5"></i>
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
                    <div class="col-12 col-md-6">
                        <label for="search_users" class="form-label text-secondary small fw-semibold">Search User</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search_users" class="form-control border-start-0 bg-light" placeholder="Search by username, name, email, or department..." value="<?= h($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-4">
                        <label for="role_users" class="form-label text-secondary small fw-semibold">Role</label>
                        <select name="role" id="role_users" class="form-select bg-light">
                            <option value="">-- All Roles --</option>
                            <option value="admin" <?= (isset($filters['role']) && $filters['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
                            <option value="staff" <?= (isset($filters['role']) && $filters['role'] === 'staff') ? 'selected' : '' ?>>Staff Personnel</option>
                        </select>
                    </div>
                    
                    <div class="col-12 col-md-2 d-flex gap-2">
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
                                <th>Department & Title</th>
                                <th>Archived Date</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-person-x d-block fs-3 mb-2 text-muted"></i>
                                        No archived staff accounts match the criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): 
                                    $roleBadge = $u['role'] === 'admin' ? 'bg-light text-primary border border-primary-subtle fw-bold' : 'bg-light text-dark border';
                                    $roleDisplay = $u['role'] === 'admin' ? 'Admin' : 'Staff';
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
                                            <?php if (!empty($u['department'])): ?>
                                                <div class="text-muted small" style="font-size: 0.72rem;"><?= h($u['department']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-order="<?= $u['deleted_at'] ? h($u['deleted_at']) : '' ?>">
                                            <?= $u['deleted_at'] ? date('Y-m-d h:i A', strtotime($u['deleted_at'])) : '-' ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <form action="<?= url('/users/' . $u['id'] . '/restore') ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success border px-3 py-1 btn-restore-user"
                                                        data-username="<?= h($u['username']) ?>">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Account
                                                </button>
                                            </form>
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

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. DataTables initialization
    <?php if (!empty($patients)): ?>
        $('#archivedPatientsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
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
            "searching": true,
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
            "searching": true,
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

    // 3. Restore Patient Confirmation with SweetAlert
    document.querySelectorAll('.btn-restore-patient').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const name = this.getAttribute('data-name') || 'this patient';

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
    });

    // 4. Restore Consultation Confirmation with SweetAlert
    document.querySelectorAll('.btn-restore-consultation').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const patient = this.getAttribute('data-patient') || 'this patient';

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
    });

    // 5. Restore User Account Confirmation with SweetAlert
    document.querySelectorAll('.btn-restore-user').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const username = this.getAttribute('data-username') || 'this user';

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
    });
});
</script>
