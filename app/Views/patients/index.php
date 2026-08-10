<?php
$title = 'Patients';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Patient Directory</h2>
        <p class="text-secondary small mb-0">Search, filter, and view patient profiles, demographics, and clinical histories.</p>
    </div>
    <a href="<?= url('/patients/create') ?>" class="btn btn-primary d-flex align-items-center py-2 px-3">
        <i class="bi bi-person-plus-fill me-2 fs-5"></i>
        <span>Register New Patient</span>
    </a>
</div>

<!-- Filters Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-4">
        <form action="<?= url('/patients') ?>" method="GET" class="row g-3 align-items-end">
            <!-- Search Keyword -->
            <div class="col-12 col-md-4">
                <label for="search" class="form-label fw-semibold text-secondary small">Search Patient</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           class="form-control bg-light border-start-0" 
                           placeholder="Name, number, contact..." 
                           value="<?= h($filters['search']) ?>">
                </div>
            </div>

            <!-- Barangay Filter -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="barangay" class="form-label fw-semibold text-secondary small">Barangay</label>
                <select name="barangay" id="barangay" class="form-select bg-light">
                    <option value="">-- All Barangays --</option>
                    <?php foreach ($barangays as $brgy): ?>
                        <option value="<?= h($brgy) ?>" <?= $filters['barangay'] === $brgy ? 'selected' : '' ?>>
                            <?= h($brgy) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Sex Filter -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="sex" class="form-label fw-semibold text-secondary small">Sex</label>
                <select name="sex" id="sex" class="form-select bg-light">
                    <option value="">-- All Sexes --</option>
                    <option value="Male" <?= $filters['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $filters['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>

            <!-- Age Group Filter -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="age_group" class="form-label fw-semibold text-secondary small">Age Group</label>
                <select name="age_group" id="age_group" class="form-select bg-light">
                    <option value="">-- All Ages --</option>
                    <option value="child" <?= $filters['age_group'] === 'child' ? 'selected' : '' ?>>Child (0-12)</option>
                    <option value="teen" <?= $filters['age_group'] === 'teen' ? 'selected' : '' ?>>Teen (13-19)</option>
                    <option value="adult" <?= $filters['age_group'] === 'adult' ? 'selected' : '' ?>>Adult (20-59)</option>
                    <option value="senior" <?= $filters['age_group'] === 'senior' ? 'selected' : '' ?>>Senior (60+)</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
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
<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="patientsTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Patient No.</th>
                        <th>Full Name</th>
                        <th>Age / Sex</th>
                        <th>Barangay</th>
                        <th>Contact No.</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($patients)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people d-block fs-2 mb-3 text-muted"></i>
                                No active patient records found matching the filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients as $p): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary-dark"><?= h($p['patient_no']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= h($p['last_name']) ?>, <?= h($p['first_name']) ?> <?= h($p['middle_name'] ?? '') ?></div>
                                </td>
                                <td>
                                    <span><?= h($p['age']) ?> yrs</span>
                                    <span class="text-muted mx-1">|</span>
                                    <span class="text-secondary"><?= h($p['sex']) ?></span>
                                </td>
                                <td><?= h($p['barangay']) ?></td>
                                <td><?= h($p['contact_no'] ?? 'N/A') ?></td>
                                <td class="pe-4 text-end">
                                    <a href="<?= url('/patients/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary px-3">
                                        <i class="bi bi-folder2-open me-1"></i> View Profile
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
    // Only initialize DataTable if there are entries
    <?php if (!empty($patients)): ?>
        $('#patientsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": false, // Handled by our custom database query filter
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Disable sorting on action column
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
