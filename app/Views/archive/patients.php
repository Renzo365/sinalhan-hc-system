<?php
$title = 'Archived Patient Records';
$breadcrumbs = [
    'Archive' => '/archive/patients',
    'Patients' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Archived Patients</h2>
        <p class="text-secondary small mb-0">Demographic records of patients that have been soft-deleted/archived by an administrator.</p>
    </div>
</div>

<div class="card card-premium mb-4">
    <div class="card-body p-4 bg-white">
        <!-- Search and Filter Form -->
        <form action="<?= url('/archive/patients') ?>" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label text-secondary small fw-semibold">Search Patient</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="search" class="form-control border-start-0 bg-light" placeholder="Search by name or patient number..." value="<?= h($filters['search']) ?>">
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-md-3">
                <label for="date_from" class="form-label text-secondary small fw-semibold">Archived Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control bg-light" value="<?= h($filters['date_from']) ?>">
            </div>
            
            <div class="col-12 col-sm-6 col-md-3">
                <label for="date_to" class="form-label text-secondary small fw-semibold">Archived Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control bg-light" value="<?= h($filters['date_to']) ?>">
            </div>
            
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="<?= url('/archive/patients') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center small" id="archivedTable">
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
                                No archived records match the criteria.
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
                                <td><span class="badge bg-secondary"><?= h($p['archiver_name']) ?></span></td>
                                <td class="text-secondary text-start text-truncate" style="max-width: 200px;" title="<?= h($p['archive_reason']) ?>">
                                    <?= h($p['archive_reason']) ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <form action="<?= url('/archive/patients/' . $p['id'] . '/restore') ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success border-0 px-2" title="Restore Patient Record" data-confirm="Are you sure you want to restore the patient record for <?= h($p['first_name']) ?> <?= h($p['last_name']) ?>?">
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

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($patients)): ?>
        $('#archivedTable').DataTable({
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
});
</script>
