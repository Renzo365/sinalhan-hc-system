<?php
$title = 'Patients';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-primary-dark" style="text-wrap: balance;">Patient Directory</h1>
        <p class="text-secondary small mb-0" style="text-wrap: pretty;">Search, filter, and view patient profiles, household clusters, and program records.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('/patients/export?' . http_build_query($filters)) ?>" class="btn btn-outline-success d-flex align-items-center py-2 px-3 shadow-sm" style="min-height: 40px;" title="Export Filtered Patient Masterlist to CSV">
            <i class="bi bi-file-earmark-excel me-2 fs-5" aria-hidden="true"></i>
            <span>Export CSV</span>
        </a>
        <a href="<?= url('/patients/create') ?>" class="btn btn-primary d-flex align-items-center py-2 px-3 shadow-sm" style="min-height: 40px;">
            <i class="bi bi-person-plus-fill me-2 fs-5" aria-hidden="true"></i>
            <span>Register New Patient</span>
        </a>
    </div>
</div>

<?php
$totalPatients = (int)($censusMetrics['total_patients'] ?? 0);
$seniorPatients = (int)($censusMetrics['seniors'] ?? 0);
$under5Patients = (int)($censusMetrics['under5'] ?? 0);
$phicCovered = (int)($censusMetrics['phic_covered'] ?? 0);
$households = (int)($censusMetrics['households'] ?? 0);

$seniorPct = $totalPatients > 0 ? round(($seniorPatients / $totalPatients) * 100, 1) : 0;
$phicPct = $totalPatients > 0 ? round(($phicCovered / $totalPatients) * 100, 1) : 0;
$isAllActive = empty($filters['age_group']) && empty($filters['phic_status']) && empty($filters['sex']) && empty($filters['search']);
?>

<!-- Demographic Census KPI Summary Cards -->
<div class="row g-3 mb-4" id="patientCensusCards">
    <!-- 1. Total Registered Patients -->
    <div class="col-6 col-lg-3">
        <a href="<?= url('/patients') ?>" class="text-decoration-none">
            <div class="card card-premium shadow-sm h-100 border <?= $isAllActive ? 'border-primary ring-1' : '' ?>" style="transition: transform 0.15s ease, box-shadow 0.15s ease;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Registered</div>
                            <div class="h3 mb-0 fw-bold text-primary-dark mt-1 font-monospace" style="font-variant-numeric: tabular-nums;">
                                <?= number_format($totalPatients) ?>
                            </div>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-people-fill fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <strong class="text-dark" style="font-variant-numeric: tabular-nums;"><?= number_format($households) ?></strong> household folders
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 2. Senior Citizens (60+) -->
    <div class="col-6 col-lg-3">
        <a href="<?= url('/patients?age_group=senior') ?>" class="text-decoration-none">
            <div class="card card-premium shadow-sm h-100 border <?= ($filters['age_group'] ?? '') === 'senior' ? 'border-warning ring-1' : '' ?>" style="transition: transform 0.15s ease, box-shadow 0.15s ease;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Senior Citizens (60+)</div>
                            <div class="h3 mb-0 fw-bold text-warning-emphasis mt-1 font-monospace" style="font-variant-numeric: tabular-nums;">
                                <?= number_format($seniorPatients) ?>
                            </div>
                        </div>
                        <div class="bg-warning-subtle text-warning-emphasis rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-person-heart fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <strong class="text-dark" style="font-variant-numeric: tabular-nums;"><?= $seniorPct ?>%</strong> of patient population
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 3. Under-5 & Pediatric -->
    <div class="col-6 col-lg-3">
        <a href="<?= url('/patients?age_group=under5') ?>" class="text-decoration-none">
            <div class="card card-premium shadow-sm h-100 border <?= ($filters['age_group'] ?? '') === 'under5' ? 'border-info ring-1' : '' ?>" style="transition: transform 0.15s ease, box-shadow 0.15s ease;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pediatric & Under-5</div>
                            <div class="h3 mb-0 fw-bold text-info-emphasis mt-1 font-monospace" style="font-variant-numeric: tabular-nums;">
                                <?= number_format($under5Patients) ?>
                            </div>
                        </div>
                        <div class="bg-info-subtle text-info-emphasis rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-balloon-heart fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        Growth & EPI eligible
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 4. PhilHealth Konsulta Coverage -->
    <div class="col-6 col-lg-3">
        <a href="<?= url('/patients?phic_status=covered') ?>" class="text-decoration-none">
            <div class="card card-premium shadow-sm h-100 border <?= ($filters['phic_status'] ?? '') === 'covered' ? 'border-success ring-1' : '' ?>" style="transition: transform 0.15s ease, box-shadow 0.15s ease;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">PhilHealth Konsulta</div>
                            <div class="h3 mb-0 fw-bold text-success mt-1 font-monospace" style="font-variant-numeric: tabular-nums;">
                                <?= number_format($phicCovered) ?>
                            </div>
                        </div>
                        <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-shield-check fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <strong class="text-dark" style="font-variant-numeric: tabular-nums;"><?= $phicPct ?>%</strong> coverage rate
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-4">
        <!-- Quick Demographic Preset Filter Chips -->
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-3 border-bottom">
            <span class="text-secondary small fw-semibold text-uppercase me-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                Demographic Presets:
            </span>
            <a href="<?= url('/patients') ?>" class="filter-pill <?= $isAllActive ? 'active' : '' ?>">
                All Patients (<span style="font-variant-numeric: tabular-nums;"><?= number_format($totalPatients) ?></span>)
            </a>
            <a href="<?= url('/patients?age_group=senior') ?>" class="filter-pill <?= ($filters['age_group'] ?? '') === 'senior' ? 'active' : '' ?>">
                <i class="bi bi-person-heart" aria-hidden="true"></i> Seniors 60+ (<span style="font-variant-numeric: tabular-nums;"><?= number_format($seniorPatients) ?></span>)
            </a>
            <a href="<?= url('/patients?age_group=under5') ?>" class="filter-pill <?= ($filters['age_group'] ?? '') === 'under5' ? 'active' : '' ?>">
                <i class="bi bi-emoji-smile" aria-hidden="true"></i> Under-5 (<span style="font-variant-numeric: tabular-nums;"><?= number_format($under5Patients) ?></span>)
            </a>
            <a href="<?= url('/patients?phic_status=covered') ?>" class="filter-pill <?= ($filters['phic_status'] ?? '') === 'covered' ? 'active' : '' ?>">
                <i class="bi bi-shield-check" aria-hidden="true"></i> PhilHealth Covered (<span style="font-variant-numeric: tabular-nums;"><?= number_format($phicCovered) ?></span>)
            </a>
        </div>

        <form action="<?= url('/patients') ?>" method="GET" class="row g-3 align-items-end" id="filtersForm">
            <!-- Search Keyword -->
            <div class="col-12 col-md-4">
                <label for="search" class="form-label fw-semibold text-secondary small">Search Patient</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0" aria-hidden="true"><i class="bi bi-search"></i></span>
                    <input type="search" 
                           name="search" 
                           id="search" 
                           class="form-control bg-light border-start-0" 
                           placeholder="Search name, ID, envelope, family, address…" 
                           value="<?= h($filters['search'] ?? '') ?>"
                           inputmode="search"
                           autocomplete="off"
                           spellcheck="false">
                </div>
            </div>

            <!-- Age Bracket -->
            <div class="col-12 col-sm-6 col-md-3">
                <label for="age_group" class="form-label fw-semibold text-secondary small">Age Bracket</label>
                <select name="age_group" id="age_group" class="form-select bg-light">
                    <option value="">-- All Ages --</option>
                    <option value="under5" <?= ($filters['age_group'] ?? '') === 'under5' ? 'selected' : '' ?>>Under-5 (0–5 yrs)</option>
                    <option value="infant" <?= ($filters['age_group'] ?? '') === 'infant' ? 'selected' : '' ?>>Infant (0–1 yr)</option>
                    <option value="toddler" <?= ($filters['age_group'] ?? '') === 'toddler' ? 'selected' : '' ?>>Toddler (2–5 yrs)</option>
                    <option value="child" <?= ($filters['age_group'] ?? '') === 'child' ? 'selected' : '' ?>>Child (6–12 yrs)</option>
                    <option value="teen" <?= ($filters['age_group'] ?? '') === 'teen' ? 'selected' : '' ?>>Teen (13–19 yrs)</option>
                    <option value="adult" <?= ($filters['age_group'] ?? '') === 'adult' ? 'selected' : '' ?>>Adult (20–59 yrs)</option>
                    <option value="senior" <?= ($filters['age_group'] ?? '') === 'senior' ? 'selected' : '' ?>>Senior (60+ yrs)</option>
                </select>
            </div>

            <!-- PhilHealth Coverage -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="phic_status" class="form-label fw-semibold text-secondary small">PhilHealth</label>
                <select name="phic_status" id="phic_status" class="form-select bg-light">
                    <option value="">-- All --</option>
                    <option value="covered" <?= ($filters['phic_status'] ?? '') === 'covered' ? 'selected' : '' ?>>Covered (Any)</option>
                    <option value="Member" <?= ($filters['phic_status'] ?? '') === 'Member' ? 'selected' : '' ?>>Member</option>
                    <option value="Dependent" <?= ($filters['phic_status'] ?? '') === 'Dependent' ? 'selected' : '' ?>>Dependent</option>
                    <option value="none" <?= ($filters['phic_status'] ?? '') === 'none' ? 'selected' : '' ?>>Unenrolled</option>
                </select>
            </div>

            <!-- Biological Sex -->
            <div class="col-12 col-sm-6 col-md-1">
                <label for="sex" class="form-label fw-semibold text-secondary small">Sex</label>
                <select name="sex" id="sex" class="form-select bg-light">
                    <option value="">All</option>
                    <option value="Male" <?= ($filters['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($filters['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-center" style="min-height: 38px;">
                    <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filter
                </button>
                <a href="<?= url('/patients') ?>" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" style="min-width: 38px; min-height: 38px;" title="Clear Filters" aria-label="Clear all applied filters">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Patient List Table -->
<div class="card card-premium shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h2 class="card-title h6 mb-0 fw-bold text-dark" style="text-wrap: balance;">
            Patient Records
        </h2>
        <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-monospace" style="font-variant-numeric: tabular-nums;">
            Total Patients: <strong class="text-dark"><?= count($patients) ?></strong>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="patientsTable">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-4">Patient No.</th>
                        <th scope="col">Full Name &amp; Contact</th>
                        <th scope="col">Envelope No.</th>
                        <th scope="col">Family No.</th>
                        <th scope="col">Age &amp; DOB</th>
                        <th scope="col">Sex</th>
                        <th scope="col">PhilHealth</th>
                        <th scope="col" class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($patients)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-3 text-secondary opacity-50" aria-hidden="true"></i>
                                    <h5 class="fw-bold mb-1 text-dark" style="text-wrap: balance;">No patient records found</h5>
                                    <p class="small mb-3 text-secondary" style="text-wrap: pretty;">Try adjusting your search criteria or register a new patient.</p>
                                    <a href="<?= url('/patients/create') ?>" class="btn btn-sm btn-primary px-3 d-inline-flex align-items-center" style="min-height: 38px;">
                                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Register New Patient
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="<?= url('/patients/' . $p['id']) ?>" 
                                       class="badge-patient-id text-hover-primary" 
                                       title="Open <?= h($p['patient_no']) ?> workstation">
                                        <?= h($p['patient_no']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="<?= url('/patients/' . $p['id']) ?>" class="fw-bold text-dark text-decoration-none text-hover-primary">
                                            <?= h($p['last_name']) ?>, <?= h($p['first_name']) ?> <?= h($p['middle_name'] ? mb_substr($p['middle_name'], 0, 1) . '.' : '') ?> <?= h($p['suffix'] ?? '') ?>
                                        </a>
                                        <div class="small text-muted d-flex align-items-center flex-wrap gap-1 mt-0.5" style="font-size: 0.76rem;">
                                            <?php if (!empty($p['address'])): ?>
                                                <span class="text-truncate" style="max-width: 250px;" title="<?= h($p['address']) ?>">
                                                    <?= h($p['address']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($p['address']) && !empty($p['contact_no'])): ?>
                                                <span class="text-secondary opacity-50" aria-hidden="true">&bull;</span>
                                            <?php endif; ?>
                                            <?php if (!empty($p['contact_no'])): ?>
                                                <span style="font-variant-numeric: tabular-nums;">
                                                    <?= h($p['contact_no']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (empty($p['address']) && empty($p['contact_no'])): ?>
                                                <span class="text-muted fst-italic">No contact or address recorded</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($p['envelope_no'])): ?>
                                        <span class="badge-envelope-no" title="Physical Folder Envelope No.">
                                            #<?= h(ltrim($p['envelope_no'], '#')) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($p['family_no'])): ?>
                                        <a href="<?= url('/patients?search=' . urlencode($p['family_no'])) ?>" 
                                           class="badge-family-no" 
                                           title="Filter all household members in folder <?= h($p['family_no']) ?>">
                                           <i class="bi bi-people-fill opacity-75" aria-hidden="true"></i>
                                           <?= h($p['family_no']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td data-order="<?= (int)$p['age'] ?>">
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold text-dark font-monospace small" style="font-variant-numeric: tabular-nums;"><?= h($p['age']) ?> <span class="text-muted fw-normal">yrs</span></span>
                                        <?php if (!empty($p['dob'])): ?>
                                            <span class="text-muted small" style="font-size: 0.72rem; font-variant-numeric: tabular-nums;">
                                                <?= date('M d, Y', strtotime($p['dob'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (($p['sex'] ?? '') === 'Male'): ?>
                                        <span class="badge badge-sex-male">
                                            Male
                                        </span>
                                    <?php elseif (($p['sex'] ?? '') === 'Female'): ?>
                                        <span class="badge badge-sex-female">
                                            Female
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($p['phic_status'] ?? '') === 'Member'): ?>
                                        <span class="badge badge-phic-member" <?= !empty($p['philhealth_no']) ? 'title="PhilHealth PIN: ' . h($p['philhealth_no']) . '"' : '' ?>>
                                            Member
                                        </span>
                                    <?php elseif (($p['phic_status'] ?? '') === 'Dependent'): ?>
                                        <span class="badge badge-phic-dependent" <?= !empty($p['philhealth_no']) ? 'title="PhilHealth PIN: ' . h($p['philhealth_no']) . '"' : '' ?>>
                                            Dependent
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-phic-none">
                                            None
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <a href="<?= url('/patients/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center" style="min-height: 36px; min-width: 44px;" title="Open Patient Workstation">
                                            View
                                        </a>
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-outline-secondary px-2 dropdown-toggle d-inline-flex align-items-center justify-content-center" style="min-height: 36px; min-width: 36px;" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Intake shortcuts & options" aria-label="Intake shortcuts & options for <?= h($p['first_name'] . ' ' . $p['last_name']) ?>">
                                                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" style="min-width: 190px;">
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Clinic Intake</h6>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2" href="<?= url('/queue?patient_id=' . $p['id']) ?>">
                                                        <i class="bi bi-ticket-perforated text-success fs-6" aria-hidden="true"></i>
                                                        <span>Check In to Queue</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2" href="<?= url('/appointments/create?patient_id=' . $p['id']) ?>">
                                                        <i class="bi bi-calendar-plus text-info fs-6" aria-hidden="true"></i>
                                                        <span>Book Appointment</span>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <a class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2" href="<?= url('/patients/' . $p['id']) ?>">
                                                        <i class="bi bi-folder2-open text-primary fs-6" aria-hidden="true"></i>
                                                        <span>Full Health Record</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
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
        $('#patientsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": <?= (int)user_pref('default_page_size', 10) ?>,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "order": [[1, "asc"]], // Sort by Full Name ascending by default
            "columnDefs": [
                { "orderable": false, "targets": 7 } // Action button column (0-indexed: 7 is Action)
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

