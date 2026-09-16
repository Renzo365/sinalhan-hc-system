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
<div class="card card-premium mb-4 shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
        <?php 
        $hasAdvancedFilters = !empty($filters['program_type']) || !empty($filters['age_group']) || !empty($filters['sex']);
        $activeFilterCount = (!empty($filters['program_type']) ? 1 : 0) + (!empty($filters['age_group']) ? 1 : 0) + (!empty($filters['sex']) ? 1 : 0);
        ?>
        <form action="<?= url('/patients') ?>" method="GET">
            <!-- Primary Search Row -->
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-7 col-lg-8">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               class="form-control bg-light border-start-0" 
                               placeholder="Search by Patient Name, Family #, or Patient No..." 
                               value="<?= h($filters['search']) ?>">
                        <?php if (!empty($filters['search'])): ?>
                            <a href="<?= url('/patients' . ($hasAdvancedFilters ? '?program_type=' . urlencode($filters['program_type']) . '&age_group=' . urlencode($filters['age_group']) . '&sex=' . urlencode($filters['sex']) : '')) ?>" 
                               class="input-group-text bg-light text-muted text-decoration-none border-start-0" 
                               title="Clear search keyword">
                                <i class="bi bi-x-circle-fill"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-md-5 col-lg-4 d-flex gap-2">
                    <button class="btn btn-outline-secondary d-flex align-items-center justify-content-center flex-grow-1" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#advancedFilters" 
                            aria-expanded="<?= $hasAdvancedFilters ? 'true' : 'false' ?>" 
                            aria-controls="advancedFilters">
                        <i class="bi bi-sliders2 me-2"></i>
                        <span>Filters</span>
                        <?php if ($activeFilterCount > 0): ?>
                            <span class="badge bg-primary text-white rounded-pill ms-2"><?= $activeFilterCount ?></span>
                        <?php endif; ?>
                        <i class="bi bi-chevron-down ms-auto ms-md-2 small"></i>
                    </button>
                    
                    <button type="submit" class="btn btn-primary px-3" title="Apply Search">
                        <i class="bi bi-search me-1 d-none d-sm-inline"></i> Search
                    </button>
                    
                    <?php if (!empty($filters['search']) || $hasAdvancedFilters): ?>
                        <a href="<?= url('/patients') ?>" class="btn btn-light border text-secondary" title="Reset All Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Collapsible Advanced Filters -->
            <div class="collapse <?= $hasAdvancedFilters ? 'show' : '' ?> pt-3 mt-3 border-top" id="advancedFilters">
                <div class="row g-3">
                    <!-- Program Category Filter -->
                    <div class="col-12 col-sm-4">
                        <label for="program_type" class="form-label fw-semibold text-secondary small mb-1">Program Category</label>
                        <select name="program_type" id="program_type" class="form-select form-select-sm bg-light">
                            <option value="">-- All Programs --</option>
                            <option value="opd" <?= ($filters['program_type'] ?? '') === 'opd' ? 'selected' : '' ?>>General OPD</option>
                            <option value="prenatal" <?= ($filters['program_type'] ?? '') === 'prenatal' ? 'selected' : '' ?>>Maternal / Prenatal</option>
                            <option value="wellbaby" <?= ($filters['program_type'] ?? '') === 'wellbaby' ? 'selected' : '' ?>>Well Baby (0–5)</option>
                            <option value="senior" <?= ($filters['program_type'] ?? '') === 'senior' ? 'selected' : '' ?>>Senior Citizen</option>
                        </select>
                    </div>

                    <!-- Age Group Filter -->
                    <div class="col-12 col-sm-4">
                        <label for="age_group" class="form-label fw-semibold text-secondary small mb-1">Age Bracket</label>
                        <select name="age_group" id="age_group" class="form-select form-select-sm bg-light">
                            <option value="">-- All Ages --</option>
                            <option value="infant" <?= ($filters['age_group'] ?? '') === 'infant' ? 'selected' : '' ?>>Infant (0–1)</option>
                            <option value="toddler" <?= ($filters['age_group'] ?? '') === 'toddler' ? 'selected' : '' ?>>Toddler (2–5)</option>
                            <option value="child" <?= ($filters['age_group'] ?? '') === 'child' ? 'selected' : '' ?>>Child (6–12)</option>
                            <option value="teen" <?= ($filters['age_group'] ?? '') === 'teen' ? 'selected' : '' ?>>Teen (13–19)</option>
                            <option value="adult" <?= ($filters['age_group'] ?? '') === 'adult' ? 'selected' : '' ?>>Adult (20–59)</option>
                            <option value="senior" <?= ($filters['age_group'] ?? '') === 'senior' ? 'selected' : '' ?>>Senior (60+)</option>
                        </select>
                    </div>

                    <!-- Sex Filter -->
                    <div class="col-12 col-sm-4">
                        <label for="sex" class="form-label fw-semibold text-secondary small mb-1">Biological Sex</label>
                        <select name="sex" id="sex" class="form-select form-select-sm bg-light">
                            <option value="">-- All --</option>
                            <option value="Male" <?= ($filters['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($filters['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                </div>
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
                        <th>Family No.</th>
                        <th>Full Name</th>
                        <th>Program</th>
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
                                    <a href="<?= url('/patients/' . $p['id']) ?>" class="fw-bold text-dark text-decoration-none text-hover-primary">
                                        <?= h($p['last_name']) ?>, <?= h($p['first_name']) ?> <?= h($p['middle_name'] ? mb_substr($p['middle_name'], 0, 1) . '.' : '') ?> <?= h($p['suffix'] ?? '') ?>
                                    </a>
                                </td>
                                <td>
                                    <?php 
                                    $badge = $p['program_badge'] ?? [
                                        'tag' => 'opd',
                                        'class' => 'bg-primary text-white',
                                        'icon' => 'bi-clipboard2-pulse',
                                        'label' => 'General OPD'
                                    ];
                                    ?>
                                    <span class="badge <?= $badge['class'] ?> fw-medium px-2 py-1">
                                        <i class="bi <?= $badge['icon'] ?? 'bi-tag' ?> me-1"></i><?= $badge['label'] ?>
                                    </span>
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
            "order": [[2, "asc"]], // Sort by Full Name ascending by default
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

