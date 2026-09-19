<?php
$title = 'Patients';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Patient Directory</h2>
        <p class="text-secondary small mb-0">Search, filter, and view patient profiles, household clusters, and program records.</p>
    </div>
    <a href="<?= url('/patients/create') ?>" class="btn btn-primary d-flex align-items-center py-2 px-3 shadow-sm">
        <i class="bi bi-person-plus-fill me-2 fs-5"></i>
        <span>Register New Patient</span>
    </a>
</div>

<!-- Filters Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-4">
        <form action="<?= url('/patients') ?>" method="GET" class="row g-3 align-items-end" id="filtersForm">
            <!-- Search Keyword -->
            <div class="col-12 col-md-4">
                <label for="search" class="form-label fw-semibold text-secondary small">Search Patient</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           class="form-control bg-light border-start-0" 
                           placeholder="Search name, ID, envelope, family..." 
                           value="<?= h($filters['search'] ?? '') ?>">
                </div>
            </div>

            <!-- Age Bracket -->
            <div class="col-12 col-sm-6 col-md-3">
                <label for="age_group" class="form-label fw-semibold text-secondary small">Age Bracket</label>
                <select name="age_group" id="age_group" class="form-select bg-light">
                    <option value="">-- All Ages --</option>
                    <option value="infant" <?= ($filters['age_group'] ?? '') === 'infant' ? 'selected' : '' ?>>Infant (0–1)</option>
                    <option value="toddler" <?= ($filters['age_group'] ?? '') === 'toddler' ? 'selected' : '' ?>>Toddler (2–5)</option>
                    <option value="child" <?= ($filters['age_group'] ?? '') === 'child' ? 'selected' : '' ?>>Child (6–12)</option>
                    <option value="teen" <?= ($filters['age_group'] ?? '') === 'teen' ? 'selected' : '' ?>>Teen (13–19)</option>
                    <option value="adult" <?= ($filters['age_group'] ?? '') === 'adult' ? 'selected' : '' ?>>Adult (20–59)</option>
                    <option value="senior" <?= ($filters['age_group'] ?? '') === 'senior' ? 'selected' : '' ?>>Senior (60+)</option>
                </select>
            </div>

            <!-- Biological Sex -->
            <div class="col-12 col-sm-6 col-md-3">
                <label for="sex" class="form-label fw-semibold text-secondary small">Biological Sex</label>
                <select name="sex" id="sex" class="form-select bg-light">
                    <option value="">-- All --</option>
                    <option value="Male" <?= ($filters['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($filters['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="<?= url('/patients') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Patient List Table -->
<div class="card card-premium shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h3 class="card-title h6 mb-0 fw-bold text-dark">
            <i class="bi bi-people-fill text-primary me-2"></i>Patient Records
        </h3>
        <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-monospace">
            Total Patients: <strong class="text-dark"><?= count($patients) ?></strong>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="patientsTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Patient No.</th>
                        <th>Full Name</th>
                        <th>Envelope No.</th>
                        <th>Family No.</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($patients)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                    <h5 class="fw-bold mb-1 text-dark">No patient records found</h5>
                                    <p class="small mb-3 text-secondary">Try adjusting your search criteria or register a new patient.</p>
                                    <a href="<?= url('/patients/create') ?>" class="btn btn-sm btn-primary px-3">
                                        <i class="bi bi-plus-circle me-1"></i> Register New Patient
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients as $p): ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-primary font-monospace small">
                                    <?= h($p['patient_no']) ?>
                                </td>
                                <td>
                                    <a href="<?= url('/patients/' . $p['id']) ?>" class="fw-bold text-dark text-decoration-none text-hover-primary">
                                        <?= h($p['last_name']) ?>, <?= h($p['first_name']) ?> <?= h($p['middle_name'] ? mb_substr($p['middle_name'], 0, 1) . '.' : '') ?> <?= h($p['suffix'] ?? '') ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($p['envelope_no'])): ?>
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.75rem;" title="Physical Envelope No.">
                                            <i class="bi bi-folder2-open text-primary me-1"></i>Env #<?= h($p['envelope_no']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($p['family_no'])): ?>
                                        <a href="<?= url('/patients?search=' . urlencode($p['family_no'])) ?>" 
                                           class="badge bg-light text-dark border text-decoration-none" 
                                           title="Filter all household members in <?= h($p['family_no']) ?>">
                                            <i class="bi bi-house-door-fill text-primary me-1"></i><?= h($p['family_no']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark font-monospace small"><?= h($p['age']) ?> <span class="text-muted fw-normal">yrs</span></span>
                                </td>
                                <td>
                                    <?php if (($p['sex'] ?? '') === 'Male'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-medium px-2 py-1">
                                            <i class="bi bi-gender-male me-1"></i>Male
                                        </span>
                                    <?php elseif (($p['sex'] ?? '') === 'Female'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-medium px-2 py-1">
                                            <i class="bi bi-gender-female me-1"></i>Female
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="<?= url('/patients/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center" title="View Patient Workstation">
                                        <i class="bi bi-eye me-1"></i> View Profile
                                    </a>
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
        $('#patientsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "order": [[1, "asc"]], // Sort by Full Name ascending by default
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Action button column (0-indexed: 6 is Action)
            ],
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Quick filter table...",
                "lengthMenu": "Show _MENU_ entries",
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>
});
</script>

