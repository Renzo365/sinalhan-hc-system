<?php
/**
 * @var array $patient Child patient demographic record
 * @var array|false $wellbabyRecord Initialized well-baby record or false
 * @var array $growthLogs Historical pediatric growth visits
 * @var array $patientImmunizations All recorded immunizations
 * @var array $vaccineMap Mapped vaccine doses ['VAC_NAME:DOSE' => record]
 * @var array $potentialMothers List of registered female patients for maternal linking
 */

// Robust patient full name formatting
$lastName = trim($patient['last_name'] ?? '');
$firstName = trim($patient['first_name'] ?? '');
$middleName = trim($patient['middle_name'] ?? '');
$suffix = trim($patient['suffix'] ?? '');

if (!empty($lastName) && !empty($firstName)) {
    $fullNameFormatted = $lastName . ', ' . $firstName;
    if (!empty($middleName)) {
        $fullNameFormatted .= ' ' . mb_substr($middleName, 0, 1) . '.';
    }
    if (!empty($suffix)) {
        $fullNameFormatted .= ' ' . $suffix;
    }
} elseif (!empty($lastName)) {
    $fullNameFormatted = $lastName . (!empty($suffix) ? ' ' . $suffix : '');
} else {
    $fullNameFormatted = 'Unknown Patient';
}

// Avatar Initials
$avatarInitials = '';
if (!empty($firstName) || !empty($lastName)) {
    $avatarInitials = strtoupper(
        (!empty($firstName) ? mb_substr($firstName, 0, 1) : '') .
        (!empty($lastName) ? mb_substr($lastName, 0, 1) : '')
    );
}

$title = 'Well-Baby / EPI: ' . $fullNameFormatted;
$breadcrumbs = [
    'Well-Baby / EPI' => '/well-baby',
    $fullNameFormatted => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header (Title & Right Back Action) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="min-w-0">
            <h2 class="h3 mb-1 fw-bold text-primary-dark">
                <i class="bi bi-emoji-smile-fill text-success me-2"></i>Well-Baby &amp; EPI Workstation
            </h2>
            <p class="text-secondary small mb-0">Infant birth circumstances, DOH EPI immunization schedule tracking, and pediatric growth logs.</p>
        </div>
        <a href="<?= url('/well-baby') ?>" class="btn btn-outline-secondary text-nowrap flex-shrink-0">
            <i class="bi bi-arrow-left me-1"></i> Back to Well-Baby Registry
        </a>
    </div>

    <!-- ==========================================================================
       PATIENT MASTER HEADER (Compact Universal Identity & Actions Banner)
       ========================================================================== -->
    <div class="card card-premium shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                
                <!-- Left: Avatar, Name, Badges & Baseline Demographics -->
                <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                    <div class="avatar-circle bg-success-subtle text-success fw-bold d-flex align-items-center justify-content-center rounded-circle shadow-xs flex-shrink-0" style="width: 48px; height: 48px; font-size: 1.15rem;">
                        <?php if (!empty($avatarInitials)): ?>
                            <?= h($avatarInitials) ?>
                        <?php else: ?>
                            <i class="bi bi-emoji-smile fs-4"></i>
                        <?php endif; ?>
                    </div>

                    <div class="min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h3 class="h5 fw-bold text-primary-dark mb-0 lh-1">
                                <?= h($fullNameFormatted) ?>
                            </h3>
                            <span class="badge bg-light text-dark border font-monospace fs-7">
                                <?= h($patient['patient_no']) ?>
                            </span>
                            <?php if (!empty($patient['envelope_no'])): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-7" title="Physical Envelope No.">
                                    <i class="bi bi-folder2-open me-1"></i>Env #<?= h($patient['envelope_no']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($wellbabyRecord): ?>
                                <span class="badge bg-success text-white font-monospace fs-7">Registered Infant</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-dark border font-monospace fs-7">Unregistered</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2 text-secondary small">
                            <span><strong><?= h($patient['age'] ?? 'Infant') ?></strong> yrs &bull; <?= h($patient['gender'] ?? $patient['sex'] ?? 'Child') ?></span>
                            <span>DOB: <strong class="text-dark"><?= !empty($patient['dob']) ? date('M d, Y', strtotime($patient['dob'])) : 'Unspecified' ?></strong></span>
                            <?php if (!empty($patient['blood_type']) && strtolower(trim($patient['blood_type'])) !== 'unknown'): ?>
                                <span>Blood: <strong class="text-danger"><?= h($patient['blood_type']) ?></strong></span>
                            <?php endif; ?>
                            <?php if (!empty($patient['mother_name'])): ?>
                                <span><i class="bi bi-person-heart text-muted me-1"></i>Mother: <?= h($patient['mother_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($patient['barangay'])): ?>
                                <span><i class="bi bi-geo-alt text-muted me-1"></i>Brgy. <?= h($patient['barangay']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Action Buttons (Only Full Patient Profile, removing duplicate action buttons) -->
                <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                    <a href="<?= url('/patients/' . $patient['id']) ?>" class="btn btn-outline-primary btn-sm px-3 shadow-xs">
                        <i class="bi bi-person-badge me-1"></i> Full Patient Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-xs" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-xs" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if ($wellbabyRecord): ?>
        
        <!-- 1. Infant Birth Context & Newborn Screening Card -->
        <div class="card border-success rounded-3 p-4 mb-4 bg-light-subtle shadow-xs">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="h6 fw-bold text-success mb-0">
                            <i class="bi bi-emoji-smile-fill me-2"></i>Well Baby Infant Profile (CHO Santa Rosa Record)
                        </h5>
                        <span class="badge bg-success text-white">Birth Record</span>
                        <?php if (!empty($wellbabyRecord['mother_cpab_tt'])): ?>
                            <span class="badge bg-info text-white"><i class="bi bi-shield-check me-1"></i>CPAB: <?= h($wellbabyRecord['mother_cpab_tt']) ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="text-muted small">Registered on <?= date('M d, Y', strtotime($wellbabyRecord['created_at'])) ?> by <?= h($wellbabyRecord['creator_name'] ?? 'Midwife') ?></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/well-baby/' . $patient['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Edit Birth Record
                    </a>
                    <button type="button" class="btn btn-sm btn-success text-white shadow-xs fw-semibold" data-bs-toggle="modal" data-bs-target="#addGrowthLogModal">
                        <i class="bi bi-plus-lg me-1"></i> + Record Growth Visit
                    </button>
                </div>
            </div>

            <!-- Birth Metrics Row -->
            <div class="row g-3 small">
                <!-- Birth Wt / Length -->
                <div class="col-6 col-sm-3">
                    <div class="p-2 bg-white rounded border">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Birth Weight / Length</span>
                        <span class="fw-bold text-success fs-7"><?= h($wellbabyRecord['birth_weight_kg']) ?> kg / <?= h($wellbabyRecord['birth_length_cm']) ?> cm</span>
                    </div>
                </div>

                <!-- Delivery Place & Type -->
                <div class="col-6 col-sm-3">
                    <div class="p-2 bg-white rounded border">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Delivery Place & Type</span>
                        <span class="fw-bold text-dark fs-7"><?= h($wellbabyRecord['place_of_delivery']) ?> (<?= h($wellbabyRecord['delivery_type']) ?>)</span>
                        <?php if (!empty($wellbabyRecord['attended_by'])): ?>
                            <span class="text-muted d-block" style="font-size: 0.7rem;">Attended by: <?= h($wellbabyRecord['attended_by']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Newborn Screening -->
                <div class="col-6 col-sm-3">
                    <div class="p-2 bg-white rounded border">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Newborn Screening (NBS)</span>
                        <span class="badge <?= !empty($wellbabyRecord['newborn_screening_done']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                            <?= !empty($wellbabyRecord['newborn_screening_done']) ? 'Done' : 'Pending' ?>
                        </span>
                        <?php if (!empty($wellbabyRecord['newborn_screening_date'])): ?>
                            <span class="text-muted d-block" style="font-size: 0.7rem;">Screened: <?= date('M d, Y', strtotime($wellbabyRecord['newborn_screening_date'])) ?></span>
                        <?php endif; ?>
                        <span class="fw-semibold text-dark d-block mt-1" style="font-size: 0.75rem;">
                            <?= !empty($wellbabyRecord['newborn_screening_result']) ? h($wellbabyRecord['newborn_screening_result']) : 'No Cert #' ?>
                        </span>
                    </div>
                </div>

                <!-- Mother Link & Feeding Method -->
                <div class="col-6 col-sm-3">
                    <div class="p-2 bg-white rounded border">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Mother / Feeding Method</span>
                        <?php if (!empty($wellbabyRecord['mother_patient_id'])): ?>
                            <a href="<?= url('/patients/' . $wellbabyRecord['mother_patient_id']) ?>" class="fw-bold text-primary text-decoration-none d-block">
                                <i class="bi bi-person-fill"></i> <?= h($wellbabyRecord['mother_last_name']) ?>, <?= h($wellbabyRecord['mother_first_name']) ?>
                            </a>
                        <?php else: ?>
                            <span class="fw-bold text-dark d-block"><?= h($patient['mother_name'] ?? 'Not Linked') ?></span>
                        <?php endif; ?>
                        <span class="text-muted small" style="font-size: 0.7rem;"><?= h($wellbabyRecord['feeding_method']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. DOH Expanded Program on Immunization (EPI) Grid -->
        <div class="card border rounded-3 p-3 mb-4 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-shield-check text-primary me-2"></i>DOH Mandatory Routine Infant Immunization Schedule (EPI)
                    </h6>
                    <span class="text-muted small">Official schedule for infants aged 0–12 months. Dates save directly to the central registry.</span>
                </div>
                <button type="submit" form="epiScheduleForm" class="btn btn-sm btn-primary shadow-xs">
                    <i class="bi bi-check2-circle me-1"></i> Save EPI Schedule
                </button>
            </div>

            <form action="<?= url('/patients/' . $patient['id'] . '/wellbaby/epi-schedule') ?>" method="POST" id="epiScheduleForm">
                <?= csrf_field() ?>

                <?php
                // EPI Vaccine Milestones
                $epiSchedule = [
                    'At Birth' => [
                        ['key' => 'BCG__1', 'name' => 'BCG', 'dose' => 1, 'desc' => 'Tuberculosis (Right Deltoid)'],
                        ['key' => 'Hepatitis_B__1', 'name' => 'Hepatitis B', 'dose' => 1, 'desc' => 'Within 24 hours of birth']
                    ],
                    '1.5 Months (6 Weeks)' => [
                        ['key' => 'Pentavalent__1', 'name' => 'Pentavalent (DTP-HepB-Hib)', 'dose' => 1, 'desc' => 'Dose 1'],
                        ['key' => 'OPV__1', 'name' => 'Oral Polio Vaccine (OPV)', 'dose' => 1, 'desc' => 'Dose 1'],
                        ['key' => 'Rotavirus__1', 'name' => 'Rotavirus / PCV', 'dose' => 1, 'desc' => 'Dose 1']
                    ],
                    '2.5 Months (10 Weeks)' => [
                        ['key' => 'Pentavalent__2', 'name' => 'Pentavalent (DTP-HepB-Hib)', 'dose' => 2, 'desc' => 'Dose 2'],
                        ['key' => 'OPV__2', 'name' => 'Oral Polio Vaccine (OPV)', 'dose' => 2, 'desc' => 'Dose 2'],
                        ['key' => 'Rotavirus__2', 'name' => 'Rotavirus / PCV', 'dose' => 2, 'desc' => 'Dose 2']
                    ],
                    '3.5 Months (14 Weeks)' => [
                        ['key' => 'Pentavalent__3', 'name' => 'Pentavalent (DTP-HepB-Hib)', 'dose' => 3, 'desc' => 'Dose 3'],
                        ['key' => 'OPV__3', 'name' => 'Oral Polio Vaccine (OPV)', 'dose' => 3, 'desc' => 'Dose 3'],
                        ['key' => 'IPV__1', 'name' => 'Inactivated Polio (IPV)', 'dose' => 1, 'desc' => 'Dose 1']
                    ],
                    '9 Months' => [
                        ['key' => 'MCV__1', 'name' => 'Measles (MCV 1)', 'dose' => 1, 'desc' => 'Anti-Measles dose']
                    ],
                    '12 Months (1 Year)' => [
                        ['key' => 'MCV__2', 'name' => 'MMR Booster (MCV 2)', 'dose' => 2, 'desc' => 'Measles, Mumps, Rubella']
                    ]
                ];
                ?>

                <div class="row g-3">
                    <?php foreach ($epiSchedule as $milestone => $vaccines): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card border bg-light-subtle h-100 p-2 rounded-3">
                                <div class="fw-bold text-primary small mb-2 border-bottom pb-1">
                                    <i class="bi bi-clock-history me-1"></i><?= $milestone ?>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($vaccines as $v): 
                                        $lookupKey = strtoupper(trim(str_replace('_', ' ', explode('__', $v['key'])[0]))) . ':' . $v['dose'];
                                        $existingRecord = $vaccineMap[$lookupKey] ?? null;
                                        $isDone = !empty($existingRecord);
                                        $administeredDate = $isDone ? $existingRecord['administered_date'] : '';
                                    ?>
                                        <div class="p-2 bg-white rounded border small">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <strong class="text-dark"><?= h($v['name']) ?></strong>
                                                    <span class="text-muted d-block" style="font-size: 0.7rem;"><?= h($v['desc']) ?></span>
                                                </div>
                                                <?php if ($isDone): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="bi bi-check-circle-fill me-1"></i>Done
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-secondary border">Pending</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="input-group input-group-sm mt-1">
                                                <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                                <input type="date" name="epi[<?= $v['key'] ?>]" class="form-control bg-white" placeholder="Administered Date" value="<?= h($administeredDate) ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <!-- 3. Periodic Pediatric Growth Monitoring Log -->
        <div class="card border rounded-3 p-3 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-activity text-primary me-2"></i>Child Anthropometric & Growth Monitoring Log
                    </h6>
                    <span class="text-muted small">Serial measurements of weight, height, head circumference, chest circumference, and feeding practices.</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addGrowthLogModal">
                    <i class="bi bi-plus-circle me-1"></i> Record Growth Visit
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center small" id="childGrowthTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3">Visit Date</th>
                            <th>Age</th>
                            <th>Anthropometrics (Weight &bull; Height)</th>
                            <th>Feeding Method</th>
                            <th>Supplements</th>
                            <th class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($growthLogs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No periodic growth checkups logged yet for this infant.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($growthLogs as $gl): ?>
                                <tr>
                                    <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y', strtotime($gl['log_date'])) ?></td>
                                    <td class="font-monospace fw-bold text-primary"><?= h($gl['age_months']) ?> mos</td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= h($gl['weight_kg']) ?> kg</span>
                                        <span class="text-muted mx-1">&bull;</span>
                                        <span class="text-secondary"><?= h($gl['height_cm']) ?> cm</span>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= h($gl['feeding_method']) ?></span></td>
                                    <td>
                                        <?php if (!empty($gl['vitamin_a_dose'])): ?>
                                            <span class="badge bg-warning-subtle text-dark border me-1">Vit A</span>
                                        <?php endif; ?>
                                        <?php if (!empty($gl['deworming_dose'])): ?>
                                            <span class="badge bg-info-subtle text-info border">Dewormed</span>
                                        <?php endif; ?>
                                        <?php if (empty($gl['vitamin_a_dose']) && empty($gl['deworming_dose'])): ?>
                                            <span class="text-muted">--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <?php 
                                            $canDeleteGrowth = is_admin();
                                        ?>
                                        <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 btn-view-growth-log" 
                                                data-date="<?= date('M d, Y', strtotime($gl['log_date'])) ?>" 
                                                data-age="<?= h($gl['age_months']) ?> mos" 
                                                data-weight="<?= h($gl['weight_kg']) ?> kg" 
                                                data-height="<?= h($gl['height_cm']) ?> cm" 
                                                data-head="<?= h($gl['head_circumference_cm'] ?? '--') ?>" 
                                                data-chest="<?= h($gl['chest_circumference_cm'] ?? '--') ?>" 
                                                data-temp="<?= h($gl['temperature'] ?? '--') ?>" 
                                                data-feeding="<?= h($gl['feeding_method']) ?>" 
                                                data-supplements="<?= (!empty($gl['vitamin_a_dose']) ? 'Vitamin A' : '') . (!empty($gl['deworming_dose']) ? ' Deworming' : '') ?: 'None' ?>" 
                                                data-tcb="<?= h($gl['tcb_notes'] ?? '') ?>" 
                                                title="View Growth Details">
                                                <i class="bi bi-eye fs-6"></i>
                                            </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-growth-log"
                                                    data-id="<?= $gl['id'] ?>"
                                                    data-date="<?= h($gl['log_date']) ?>"
                                                    data-age="<?= h($gl['age_months']) ?>"
                                                    data-weight="<?= h($gl['weight_kg']) ?>"
                                                    data-height="<?= h($gl['height_cm']) ?>"
                                                    data-head="<?= h($gl['head_circumference_cm'] ?? '') ?>"
                                                    data-chest="<?= h($gl['chest_circumference_cm'] ?? '') ?>"
                                                    data-temp="<?= h($gl['temperature'] ?? '') ?>"
                                                    data-feeding="<?= h($gl['feeding_method']) ?>"
                                                    data-vita="<?= !empty($gl['vitamin_a_dose']) ? '1' : '0' ?>"
                                                    data-deworm="<?= !empty($gl['deworming_dose']) ? '1' : '0' ?>"
                                                    data-tcb="<?= h($gl['tcb_notes'] ?? '') ?>"
                                                    title="Edit Growth Checkup">
                                                    <i class="bi bi-pencil-square fs-6"></i>
                                                </button>
                                            <?php if ($canDeleteGrowth): ?>
                                                <form action="<?= url('/wellbaby/growth-log/' . $gl['id'] . '/delete') ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Delete Entry" data-confirm="Are you sure you want to delete this growth visit record?">
                                                        <i class="bi bi-trash fs-6"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- Empty State for Non-Initialized Well Baby Record -->
        <div class="card border rounded-3 p-5 text-center mb-4 bg-light-subtle shadow-xs">
            <i class="bi bi-emoji-smile fs-1 d-block mb-2 text-success"></i>
            <h5 class="h6 fw-bold text-dark mb-1">No Well Baby Infant Health Record</h5>
            <p class="text-muted small mb-3">Initializing the Well Baby Record (CHO Santa Rosa / Brgy. Sinalhan) registers birth circumstances, Newborn Screening (NBS) certification, mother link, and mandatory EPI childhood vaccines.</p>
            <div>
                <a href="<?= url('/well-baby/register?patient_id=' . $patient['id']) ?>" class="btn btn-success text-white px-4 shadow-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i> Initialize Well Baby Record
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ==========================================================================
   MODALS
   ========================================================================== -->

<!-- 1. RECORD MONTHLY GROWTH VISIT MODAL -->
<?php if ($wellbabyRecord): ?>
    <div class="modal fade" id="addGrowthLogModal" tabindex="-1" aria-labelledby="addGrowthLogModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-success text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="addGrowthLogModalLabel">
                        <i class="bi bi-activity me-2"></i>Record Pediatric Anthropometrics & Growth Visit
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="<?= url('/wellbaby/' . $wellbabyRecord['id'] . '/growth-log') ?>" method="POST" id="growthLogForm">
                    <?= csrf_field() ?>

                    <div class="modal-body p-4 bg-white small">
                        <div class="row g-3">
                            <!-- Checkup Date -->
                            <div class="col-12 col-sm-6">
                                <label for="log_date" class="form-label fw-semibold text-secondary">Checkup Date <span class="text-danger">*</span></label>
                                <input type="date" name="log_date" class="form-control bg-white" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Age in Months -->
                            <div class="col-12 col-sm-6">
                                <label for="age_months" class="form-label fw-semibold text-secondary">Exact Age in Months <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" name="age_months" class="form-control font-monospace" placeholder="e.g. 1.5" required>
                            </div>

                            <!-- Weight (kg) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label fw-semibold text-secondary">Weight (kg) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="weight_kg" class="form-control" placeholder="e.g. 4.5" required>
                            </div>

                            <!-- Height (cm) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label fw-semibold text-secondary">Height / Length (cm) <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" name="height_cm" class="form-control" placeholder="e.g. 54.0" required>
                            </div>

                            <!-- Head Circumference (cm) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label fw-semibold text-secondary">Head Circumference (cm)</label>
                                <input type="number" step="0.1" name="head_circumference_cm" class="form-control" placeholder="e.g. 37.5">
                            </div>

                            <!-- Chest Circumference (cm) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label fw-semibold text-secondary">Chest Circumference (cm)</label>
                                <input type="number" step="0.1" name="chest_circumference_cm" class="form-control" placeholder="e.g. 37.0">
                            </div>

                            <!-- Body Temp -->
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold text-secondary">Body Temperature (°C)</label>
                                <input type="number" step="0.1" name="temperature" class="form-control" placeholder="36.5">
                            </div>

                            <!-- Feeding Practice -->
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold text-secondary">Infant Feeding Practice</label>
                                <select name="feeding_method" class="form-select">
                                    <option value="LAM / Exclusive Breastfeeding" selected>LAM / Exclusive Breastfeeding</option>
                                    <option value="Bottle Feed">Bottle Feeding (Formula)</option>
                                    <option value="Mixed">Mixed Feeding</option>
                                </select>
                            </div>

                            <hr class="my-2 text-muted opacity-25">

                            <!-- Vaccines Administered Today -->
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold text-secondary">Vaccine / Intervention Note</label>
                                <input type="text" name="vaccines_administered" class="form-control" placeholder="Optional note; record actual vaccine doses in Immunization Schedule above">
                            </div>

                            <!-- Supplementation Toggles -->
                            <div class="col-12 col-sm-6 d-flex align-items-center gap-4 mt-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="vitamin_a_dose" value="1" id="vit_a_check">
                                    <label class="form-check-label text-dark fw-semibold" for="vit_a_check">Vitamin A Capsule Given</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="deworming_dose" value="1" id="deworming_check">
                                    <label class="form-check-label text-dark fw-semibold" for="deworming_check">Deworming Tablet Given</label>
                                </div>
                            </div>

                            <!-- TCB / Developmental Notes -->
                            <div class="col-12">
                                <label class="form-label fw-semibold text-secondary">Developmental Milestones & TCB Remarks</label>
                                <textarea name="tcb_notes" rows="2" class="form-control" placeholder="Holding head up, tracking sounds, advised next visit at 2.5 months..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success text-white px-4 fw-semibold">Save Growth Visit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- EDIT CHILD GROWTH VISIT DETAILS MODAL -->
<div class="modal fade" id="editGrowthLogModal" tabindex="-1" aria-labelledby="editGrowthLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="editGrowthLogModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Growth Monitoring Checkup
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editGrowthLogForm" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Date of Visit *</label>
                            <input type="date" name="log_date" id="editGrowthDate" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Age in Months *</label>
                            <input type="number" step="0.1" name="age_months" id="editGrowthAge" class="form-control" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold text-secondary">Weight (kg) *</label>
                            <input type="number" step="0.01" name="weight_kg" id="editGrowthWeight" class="form-control" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold text-secondary">Length / Height (cm) *</label>
                            <input type="number" step="0.1" name="height_cm" id="editGrowthHeight" class="form-control" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold text-secondary">Head Circ. (cm)</label>
                            <input type="number" step="0.1" name="head_circumference_cm" id="editGrowthHead" class="form-control">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold text-secondary">Chest Circ. (cm)</label>
                            <input type="number" step="0.1" name="chest_circumference_cm" id="editGrowthChest" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Temperature (°C)</label>
                            <input type="number" step="0.1" name="temperature" id="editGrowthTemp" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Feeding Method</label>
                            <select name="feeding_method" id="editGrowthFeeding" class="form-select">
                                <option value="LAM / Exclusive Breastfeeding">LAM / Exclusive Breastfeeding</option>
                                <option value="Bottle Feed">Bottle Feeding (Formula)</option>
                                <option value="Mixed">Mixed Feeding</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary d-block">Supplements Given</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="vitamin_a_dose" value="1" id="edit_vit_a_check">
                                    <label class="form-check-label text-dark fw-semibold" for="edit_vit_a_check">Vitamin A Capsule</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="deworming_dose" value="1" id="edit_deworming_check">
                                    <label class="form-check-label text-dark fw-semibold" for="edit_deworming_check">Deworming Tablet</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Developmental Milestones & TCB Remarks</label>
                            <textarea name="tcb_notes" id="editGrowthTcb" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. VIEW CHILD GROWTH VISIT DETAILS MODAL -->
<div class="modal fade" id="viewGrowthLogModal" tabindex="-1" aria-labelledby="viewGrowthLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-success text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="viewGrowthLogModalLabel">
                    <i class="bi bi-activity me-2"></i>Child Growth Visit Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white" id="growthLogModalContent">
                <!-- Recorded Meta -->
                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <div>
                        <span class="text-muted small d-block">Visit Date</span>
                        <strong class="text-dark" id="modalGrowthDate">--</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Age</span>
                        <strong class="text-success" id="modalGrowthAge">--</strong>
                    </div>
                </div>

                <!-- Grid of Anthropometric Cards -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Weight &amp; Height</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalGrowthWeight">--</span> / <span id="modalGrowthHeight">--</span></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Head Circumference</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalGrowthHead">--</span> <small class="text-muted fw-normal">cm</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Chest Circumference</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalGrowthChest">--</span> <small class="text-muted fw-normal">cm</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Temperature</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalGrowthTemp">--</span> <small class="text-muted fw-normal">°C</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Feeding Method</span>
                            <span class="fs-6 fw-bold text-dark" id="modalGrowthFeeding">--</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Supplements</span>
                            <span class="fs-6 fw-bold text-dark" id="modalGrowthSupplements">--</span>
                        </div>
                    </div>
                </div>

                <!-- TCB / Developmental Milestones & Remarks -->
                <div class="card border rounded bg-white">
                    <div class="card-header bg-light py-1.5 px-3 small fw-bold text-secondary">
                        <i class="bi bi-journal-text me-1 text-success"></i> TCB / Developmental Milestones &amp; Remarks
                    </div>
                    <div class="card-body p-3 small text-dark" id="modalGrowthTcb" style="white-space: pre-line; min-height: 50px;">
                        No developmental milestones or remarks recorded.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Page JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // View Growth Log Details
    const viewGrowthLogModalEl = document.getElementById('viewGrowthLogModal');
    document.querySelectorAll('.btn-view-growth-log').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const date = this.getAttribute('data-date') || '--';
            const age = this.getAttribute('data-age') || '--';
            const weight = this.getAttribute('data-weight') || '--';
            const height = this.getAttribute('data-height') || '--';
            const head = this.getAttribute('data-head') || '--';
            const chest = this.getAttribute('data-chest') || '--';
            const temp = this.getAttribute('data-temp') || '--';
            const feeding = this.getAttribute('data-feeding') || '--';
            const supplements = this.getAttribute('data-supplements') || 'None';
            const tcb = this.getAttribute('data-tcb') || '';

            const elDate = document.getElementById('modalGrowthDate');
            const elAge = document.getElementById('modalGrowthAge');
            const elWeight = document.getElementById('modalGrowthWeight');
            const elHeight = document.getElementById('modalGrowthHeight');
            const elHead = document.getElementById('modalGrowthHead');
            const elChest = document.getElementById('modalGrowthChest');
            const elTemp = document.getElementById('modalGrowthTemp');
            const elFeeding = document.getElementById('modalGrowthFeeding');
            const elSupplements = document.getElementById('modalGrowthSupplements');
            const elTcb = document.getElementById('modalGrowthTcb');

            if (elDate) elDate.textContent = date;
            if (elAge) elAge.textContent = age;
            if (elWeight) elWeight.textContent = weight;
            if (elHeight) elHeight.textContent = height;
            if (elHead) elHead.textContent = head;
            if (elChest) elChest.textContent = chest;
            if (elTemp) elTemp.textContent = temp;
            if (elFeeding) elFeeding.textContent = feeding;
            if (elSupplements) elSupplements.textContent = supplements;

            if (elTcb) {
                if (tcb && tcb.trim()) {
                    elTcb.textContent = tcb.trim();
                } else {
                    elTcb.innerHTML = '<span class="text-muted fst-italic">No developmental milestones or remarks recorded.</span>';
                }
            }

            if (viewGrowthLogModalEl) {
                bootstrap.Modal.getOrCreateInstance(viewGrowthLogModalEl).show();
            }
        });
    });

    // Edit Growth Log Details
    const editGrowthLogModalEl = document.getElementById('editGrowthLogModal');
    const editGrowthLogModal = editGrowthLogModalEl ? bootstrap.Modal.getOrCreateInstance(editGrowthLogModalEl) : null;
    document.querySelectorAll('.btn-edit-growth-log').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editGrowthLogForm');
            if (form) form.action = `<?= url('/wellbaby/growth-log/') ?>${id}/update`;

            document.getElementById('editGrowthDate').value = this.getAttribute('data-date') || '';
            document.getElementById('editGrowthAge').value = this.getAttribute('data-age') || '';
            document.getElementById('editGrowthWeight').value = this.getAttribute('data-weight') || '';
            document.getElementById('editGrowthHeight').value = this.getAttribute('data-height') || '';
            document.getElementById('editGrowthHead').value = this.getAttribute('data-head') || '';
            document.getElementById('editGrowthChest').value = this.getAttribute('data-chest') || '';
            document.getElementById('editGrowthTemp').value = this.getAttribute('data-temp') || '';
            document.getElementById('editGrowthFeeding').value = this.getAttribute('data-feeding') || 'LAM / Exclusive Breastfeeding';
            
            document.getElementById('edit_vit_a_check').checked = (this.getAttribute('data-vita') === '1');
            document.getElementById('edit_deworming_check').checked = (this.getAttribute('data-deworm') === '1');
            document.getElementById('editGrowthTcb').value = this.getAttribute('data-tcb') || '';

            if (editGrowthLogModal) {
                editGrowthLogModal.show();
            }
        });
    });
});
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
