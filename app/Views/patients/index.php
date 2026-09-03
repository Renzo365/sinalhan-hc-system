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
        <form action="<?= url('/patients') ?>" method="GET" class="row g-3 align-items-end">
            <!-- Search Keyword -->
            <div class="col-12 col-md-4">
                <label for="search" class="form-label fw-semibold text-secondary small">Search Patient or Household</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           class="form-control bg-light border-start-0" 
                           placeholder="Name, Family #, PIN, contact..." 
                           value="<?= h($filters['search']) ?>">
                </div>
            </div>

            <!-- Program Category Filter -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="program_type" class="form-label fw-semibold text-secondary small">Program Category</label>
                <select name="program_type" id="program_type" class="form-select bg-light">
                    <option value="">-- All Programs --</option>
                    <option value="opd" <?= ($filters['program_type'] ?? '') === 'opd' ? 'selected' : '' ?>>General OPD</option>
                    <option value="prenatal" <?= ($filters['program_type'] ?? '') === 'prenatal' ? 'selected' : '' ?>>Maternal / Prenatal</option>
                    <option value="wellbaby" <?= ($filters['program_type'] ?? '') === 'wellbaby' ? 'selected' : '' ?>>Well Baby (0–5)</option>
                    <option value="senior" <?= ($filters['program_type'] ?? '') === 'senior' ? 'selected' : '' ?>>Senior Citizen</option>
                </select>
            </div>

            <!-- Age Group Filter -->
            <div class="col-12 col-sm-6 col-md-2">
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
            <div class="col-12 col-sm-6 col-md-1">
                <label for="sex" class="form-label fw-semibold text-secondary small">Sex</label>
                <select name="sex" id="sex" class="form-select bg-light">
                    <option value="">-- All --</option>
                    <option value="Male" <?= $filters['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $filters['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-sm-6 col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" title="Apply Filters">
                    <i class="bi bi-funnel"></i>
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
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h3 class="card-title h6 mb-0 fw-bold text-dark">
            <i class="bi bi-people-fill text-primary me-2"></i>Patient Records
        </h3>
        <span class="badge bg-light text-secondary border px-2 py-1">
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
                        <th>Age / Sex</th>
                        <th>Barangay</th>
                        <th>Contact No.</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($patients)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-3 text-secondary"></i>
                                    <h5 class="fw-bold mb-1">No patient records found</h5>
                                    <p class="small mb-3">Try adjusting your search criteria or register a new patient.</p>
                                    <a href="<?= url('/patients/create') ?>" class="btn btn-sm btn-primary">
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
                                <td class="small">
                                    <span class="fw-semibold"><?= h($p['age']) ?> yrs</span> 
                                    <span class="text-muted">/ <?= h($p['sex']) ?></span>
                                </td>
                                <td class="small">
                                    <span class="badge bg-light text-secondary border">
                                        <?= h($p['barangay']) ?>
                                    </span>
                                </td>
                                <td class="small font-monospace text-muted">
                                    <?= h($p['contact_no'] ?? '&mdash;') ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="<?= url('/patients/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary" title="View Profile">
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
