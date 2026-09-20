<?php
/**
 * @var array $patient Patient demographic record
 * @var array|false $activePrenatal Active pregnancy episode
 * @var array $prenatalVisits Follow-up prenatal visits
 * @var array $pastDeliveries Past obstetric delivery histories
 * @var array $allPrenatalEpisodes All historical pregnancy episodes
 * @var array|false $medicalHistory Annex A1 IHP Medical History
 * @var array|false $latestVitals Latest vital signs
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

$title = 'Maternal Workstation: ' . $fullNameFormatted;
$breadcrumbs = [
    'Maternal Care' => '/maternal',
    $fullNameFormatted => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header (Title & Right Back Action) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="min-w-0">
            <h2 class="h3 mb-1 fw-bold text-primary-dark">
                <i class="bi bi-heart-pulse-fill text-pink me-2"></i>Maternal Care Workstation
            </h2>
            <p class="text-secondary small mb-0">Manage pregnancy episodes, serial checkups, EDC tracking, and delivery outcomes.</p>
        </div>
        <a href="<?= url('/maternal') ?>" class="btn btn-outline-secondary text-nowrap flex-shrink-0">
            <i class="bi bi-arrow-left me-1"></i> Back to Maternal Roster
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
                    <div class="avatar-circle bg-danger-subtle text-danger fw-bold d-flex align-items-center justify-content-center rounded-circle shadow-xs flex-shrink-0" style="width: 48px; height: 48px; font-size: 1.15rem;">
                        <?php if (!empty($avatarInitials)): ?>
                            <?= h($avatarInitials) ?>
                        <?php else: ?>
                            <i class="bi bi-person-fill fs-4"></i>
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
                            <?php if ($activePrenatal): ?>
                                <span class="badge bg-pink text-white font-monospace fs-7">Active Pregnancy</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2 text-secondary small">
                            <span><strong><?= h($patient['age'] ?? 'Adult') ?></strong> yrs &bull; <?= h($patient['sex'] ?? 'Female') ?></span>
                            <span>DOB: <strong class="text-dark"><?= !empty($patient['dob']) ? date('M d, Y', strtotime($patient['dob'])) : 'Unspecified' ?></strong></span>
                            <?php if (!empty($patient['blood_type']) && strtolower(trim($patient['blood_type'])) !== 'unknown'): ?>
                                <span>Blood: <strong class="text-danger"><?= h($patient['blood_type']) ?></strong></span>
                            <?php endif; ?>
                            <?php if (!empty($patient['contact_no'])): ?>
                                <span><i class="bi bi-telephone text-muted me-1"></i><?= h($patient['contact_no']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($patient['barangay'])): ?>
                                <span><i class="bi bi-geo-alt text-muted me-1"></i>Brgy. <?= h($patient['barangay']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Action Buttons -->
                <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                    <a href="<?= url('/patients/' . $patient['id']) ?>" class="btn btn-outline-primary btn-sm px-3 shadow-xs">
                        <i class="bi bi-person-badge me-1"></i> Full Patient Profile
                    </a>
                    <?php if (!$activePrenatal): ?>
                        <a href="<?= url('/maternal/register?patient_id=' . $patient['id']) ?>" class="btn btn-pink text-white btn-sm px-3 shadow-xs fw-semibold">
                            <i class="bi bi-plus-circle me-1"></i> Start Pregnancy Episode
                        </a>
                    <?php endif; ?>
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

    <!-- 1. Active Pregnancy Episode Header & Cards -->
    <?php if ($activePrenatal): ?>
        <div class="card border-pink rounded-4 p-4 mb-4 bg-light-subtle shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="h6 fw-bold text-pink mb-0">
                            <i class="bi bi-heart-pulse-fill me-2"></i>Active Pregnancy Episode (CHO I Record)
                        </h5>
                        <span class="badge bg-pink text-white">Gravida <?= $activePrenatal['gravida'] ?> Para <?= $activePrenatal['para'] ?></span>
                        <?php if (!empty($medicalHistory['pre_eclampsia'])): ?>
                            <span class="badge bg-danger text-white"><i class="bi bi-shield-exclamation me-1"></i>Pre-Eclampsia Risk (IHP)</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-muted small">Enrolled on <?= date('M d, Y', strtotime($activePrenatal['created_at'])) ?> by <?= h($activePrenatal['creator_name'] ?? 'Clinician') ?></span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= url('/maternal/episode/' . $activePrenatal['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Edit Details
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#concludePrenatalModal">
                        <i class="bi bi-check2-circle me-1"></i> Conclude Episode
                    </button>
                    <?php if (empty($prenatalVisits)): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelPrenatalModal" title="Cancel episode started in error">
                            <i class="bi bi-x-circle me-1"></i> Cancel Episode
                        </button>
                    <?php else: ?>
                        <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Cannot cancel: Checkup visits already exist. Delete all visits first to cancel this episode, or conclude it instead.">
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled style="pointer-events: none;">
                                <i class="bi bi-x-circle me-1"></i> Cancel Episode
                            </button>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Key Metrics Row -->
            <div class="row g-3 small">
                <!-- LMP -->
                <div class="col-6 col-sm-3">
                    <div class="p-3 bg-white rounded-3 border">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Last Menstrual Period (LMP)</span>
                        <span class="fw-bold text-dark fs-6"><?= date('M d, Y', strtotime($activePrenatal['lmp'])) ?></span>
                    </div>
                </div>

                <!-- EDC -->
                <div class="col-6 col-sm-3">
                    <div class="p-3 bg-white rounded-3 border">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Expected Date of Delivery (EDC)</span>
                        <span class="fw-bold text-pink fs-6"><?= date('M d, Y', strtotime($activePrenatal['edc'])) ?></span>
                    </div>
                </div>

                <!-- AOG -->
                <div class="col-6 col-sm-3">
                    <div class="p-3 bg-white rounded-3 border">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Age of Gestation (AOG)</span>
                        <span class="fw-bold text-primary fs-6"><?= h($activePrenatal['calculated_aog']['formatted'] ?? '--') ?></span>
                    </div>
                </div>

                <!-- GTPAL Details -->
                <div class="col-6 col-sm-3">
                    <div class="p-3 bg-white rounded-3 border">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Obstetric Score (GTPAL)</span>
                        <span class="fw-bold text-dark fs-6">
                            G:<?= $activePrenatal['gravida'] ?> P:<?= $activePrenatal['para'] ?> <span class="text-muted fs-7">(T:<?= $activePrenatal['term_births'] ?> P:<?= $activePrenatal['preterm_births'] ?> A:<?= $activePrenatal['abortions'] ?> L:<?= $activePrenatal['living_children'] ?>)</span>
                        </span>
                    </div>
                </div>

                <!-- Secondary Info Row -->
                <div class="col-12 col-sm-6">
                    <span class="text-muted">Husband / Partner:</span>
                    <strong class="text-dark ms-1"><?= h($activePrenatal['husband_name'] ?? 'Not specified') ?></strong>
                </div>
                <div class="col-12 col-sm-6 text-sm-end">
                    <span class="text-muted">FP Counselling (IHP):</span>
                    <span class="badge <?= (!empty($medicalHistory['fp_counselling']) && (int)$medicalHistory['fp_counselling'] === 1) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> ms-1">
                        <?= (!empty($medicalHistory['fp_counselling']) && (int)$medicalHistory['fp_counselling'] === 1) ? 'Counseled' : 'Pending' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Serial Follow-up Prenatal Visits Table -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-calendar2-check text-primary me-2"></i>Serial Prenatal Checkup Follow-up Visits
                    </h5>
                    <small class="text-muted">Monitor vital signs, fetal presentation, heart tones, and fundic height</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addPrenatalVisitModal">
                    <i class="bi bi-plus-lg me-1"></i> Log Checkup Visit
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center small" id="prenatalVisitsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3">Visit Date</th>
                            <th>AOG</th>
                            <th>Maternal BP</th>
                            <th>Weight (kg)</th>
                            <th>FHT (bpm)</th>
                            <th>Fundic Ht (cm)</th>
                            <th>Presentation</th>
                            <th>TCB / Tetanus</th>
                            <th>Remarks</th>
                            <th class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prenatalVisits)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-heartbreak fs-4 d-block mb-1 text-secondary opacity-50"></i>
                                    No follow-up prenatal visits logged yet for this pregnancy.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                                $curUserId = (int)($_SESSION['user_id'] ?? 0);
                                $curRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
                            ?>
                            <?php foreach ($prenatalVisits as $pv): 
                                $fht = (int)($pv['fetal_heart_tone'] ?? 0);
                                $fhtBadge = 'badge bg-light text-dark border';
                                if ($fht > 0) {
                                    if ($fht >= 120 && $fht <= 160) {
                                        $fhtBadge = 'badge bg-success-subtle text-success border border-success-subtle';
                                    } else {
                                        $fhtBadge = 'badge bg-danger-subtle text-danger border border-danger-subtle fw-bold';
                                    }
                                }
                                $canDeleteVisit = is_admin();
                            ?>
                                <tr>
                                    <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y', strtotime($pv['visit_date'])) ?></td>
                                    <td class="font-monospace fw-semibold"><?= h($pv['aog_weeks']) ?> wks</td>
                                    <td class="font-monospace"><?= h($pv['bp_systolic'] ?? '--') ?>/<?= h($pv['bp_diastolic'] ?? '--') ?></td>
                                    <td><?= h($pv['weight_kg'] ?? '--') ?></td>
                                    <td><span class="<?= $fhtBadge ?>"><?= $fht > 0 ? $fht . ' bpm' : '--' ?></span></td>
                                    <td><?= h($pv['fundal_height_cm'] ?? '--') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= h($pv['fetal_presentation'] ?? 'Cephalic') ?></span></td>
                                    <td class="text-muted"><?= h($pv['tcb'] ?? '--') ?></td>
                                    <td class="text-start small">
                                        <?php if (!empty($pv['remarks'])): ?>
                                            <?= h($pv['remarks']) ?>
                                        <?php elseif ($fht === 0): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="Awaiting Midwife clinical assessment"><i class="bi bi-clock me-1"></i>Awaiting Midwife</span>
                                        <?php else: ?>
                                            <span class="text-muted small">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-info border-0 p-1 btn-view-prenatal-visit" 
                                                    data-id="<?= $pv['id'] ?>" 
                                                    data-date="<?= !empty($pv['visit_date']) ? date('M d, Y', strtotime($pv['visit_date'])) : '--' ?>" 
                                                    data-aog="<?= h($pv['aog_weeks']) ?>" 
                                                    data-systolic="<?= h($pv['bp_systolic'] ?? '--') ?>" 
                                                    data-diastolic="<?= h($pv['bp_diastolic'] ?? '--') ?>" 
                                                    data-weight="<?= h($pv['weight_kg'] ?? '--') ?>" 
                                                    data-height="<?= h($pv['height_cm'] ?? '--') ?>" 
                                                    data-fht="<?= $fht > 0 ? $fht . ' bpm' : '--' ?>" 
                                                    data-fundic="<?= h($pv['fundal_height_cm'] ?? '--') ?>" 
                                                    data-presentation="<?= h($pv['fetal_presentation'] ?? 'Cephalic') ?>" 
                                                    data-tcb="<?= h($pv['tcb'] ?? '--') ?>" 
                                                    data-complaint="<?= h($pv['chief_complaint'] ?? '--') ?>" 
                                                    data-remarks="<?= h($pv['remarks'] ?? '--') ?>" 
                                                    data-attendant="<?= h($pv['attendant_name'] ?? 'Midwife') ?>" 
                                                    title="View Visit Details">
                                                <i class="bi bi-eye fs-6"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary border-0 p-1 btn-edit-prenatal-visit" 
                                                    data-id="<?= $pv['id'] ?>" 
                                                    data-date="<?= h($pv['visit_date']) ?>" 
                                                    data-aog="<?= h($pv['aog_weeks']) ?>" 
                                                    data-systolic="<?= h($pv['bp_systolic'] ?? '') ?>" 
                                                    data-diastolic="<?= h($pv['bp_diastolic'] ?? '') ?>" 
                                                    data-weight="<?= h($pv['weight_kg'] ?? '') ?>" 
                                                    data-height="<?= h($pv['height_cm'] ?? '') ?>" 
                                                    data-fht="<?= h($pv['fetal_heart_tone'] ?? '') ?>" 
                                                    data-fundic="<?= h($pv['fundal_height_cm'] ?? '') ?>" 
                                                    data-presentation="<?= h($pv['fetal_presentation'] ?? 'Cephalic') ?>" 
                                                    data-tcb="<?= h($pv['tcb'] ?? '') ?>" 
                                                    data-complaint="<?= h($pv['chief_complaint'] ?? '') ?>" 
                                                    data-remarks="<?= h($pv['remarks'] ?? '') ?>" 
                                                    title="Edit Visit">
                                                <i class="bi bi-pencil-square fs-6"></i>
                                            </button>
                                            <?php if ($canDeleteVisit): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-delete-prenatal-visit" data-id="<?= $pv['id'] ?>" data-date="<?= date('M d, Y', strtotime($pv['visit_date'])) ?>" title="Delete Visit">
                                                    <i class="bi bi-trash fs-6"></i>
                                                </button>
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
        <!-- Empty State for Non-Active Episode -->
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center mb-4 bg-white" style="border: 2px dashed #f3c2db !important;">
            <div class="d-inline-flex align-items-center justify-content-center bg-pink bg-opacity-10 text-pink rounded-circle mx-auto mb-3" style="width: 72px; height: 72px;">
                <i class="bi bi-heart-pulse fs-1"></i>
            </div>
            <h5 class="h6 fw-bold text-dark mb-1">No Active Maternal / Prenatal Care Episode</h5>
            <p class="text-muted small mb-3 mx-auto" style="max-width: 500px;">
                Enrolling this patient into the CHO I Maternal Record tracks gestational progress (LMP, EDC, AOG), serial fetal heart tones, and delivery outcomes.
            </p>
            <div>
                <a href="<?= url('/maternal/register?patient_id=' . $patient['id']) ?>" class="btn btn-pink text-white px-4 py-2 rounded-pill shadow-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i> Start Pregnancy Episode
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. Past Obstetric History Matrix (G1–G5) -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-clock-history text-primary me-2"></i>Past Obstetric Deliveries Matrix (G1, G2, G3...)
                </h5>
                <span class="text-muted small">Historical delivery outcomes, birth places, attendants, and maternal TT vaccination status.</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addPastObstetricModal">
                <i class="bi bi-plus-lg me-1"></i> Add Past Delivery
            </button>
        </div>

        <?php if (empty($pastDeliveries)): ?>
            <div class="text-center py-4 text-muted bg-light rounded-3 border border-dashed my-2">
                <i class="bi bi-clock-history fs-3 d-block mb-1 text-secondary opacity-50"></i>
                <p class="mb-1 text-secondary fw-medium small">No previous delivery records logged for this patient.</p>
                <span class="text-muted" style="font-size: 0.75rem;">Click "+ Add Past Delivery" above if the patient has previous child deliveries.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center small" id="pastObstetricTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3">Gravida</th>
                            <th>Year</th>
                            <th>Sex</th>
                            <th>Delivery Type</th>
                            <th class="text-start">Place of Delivery</th>
                            <th>Attendant</th>
                            <th>Child Status</th>
                            <th>Maternal TT</th>
                            <th class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pastDeliveries as $poh): ?>
                            <tr>
                                <td class="text-start ps-3 fw-bold text-primary font-monospace">
                                    Gravida <?= h($poh['gravida_no']) ?>
                                </td>
                                <td class="font-monospace">
                                    <?= !empty($poh['year_delivered']) ? h($poh['year_delivered']) : '<span class="text-muted">&mdash;</span>' ?>
                                </td>
                                <td>
                                    <?= !empty($poh['infant_sex']) ? h($poh['infant_sex']) : '<span class="text-muted">&mdash;</span>' ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= h($poh['delivery_type']) ?></span>
                                </td>
                                <td class="text-start">
                                    <span class="text-dark fw-medium"><?= h($poh['place_of_delivery'] ?: 'Unspecified Facility') ?></span>
                                </td>
                                <td>
                                    <?= !empty($poh['attended_by']) ? h($poh['attended_by']) : '<span class="text-muted">&mdash;</span>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $poh['status'] === 'Alive' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?>">
                                        <?= h($poh['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($poh['tt_status'])): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace"><?= h($poh['tt_status']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 text-end text-nowrap">
                                    <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                        <button type="button" class="btn btn-xs btn-outline-secondary border-0 py-1 px-2 btn-edit-past-obstetric"
                                            data-id="<?= $poh['id'] ?>"
                                            data-gravida="<?= h($poh['gravida_no']) ?>"
                                            data-year="<?= h($poh['year_delivered'] ?? '') ?>"
                                            data-delivery-type="<?= h($poh['delivery_type']) ?>"
                                            data-place="<?= h($poh['place_of_delivery'] ?? '') ?>"
                                            data-attendant="<?= h($poh['attended_by'] ?? '') ?>"
                                            data-status="<?= h($poh['status'] ?? 'Alive') ?>"
                                            data-sex="<?= h($poh['infant_sex'] ?? 'Unknown') ?>"
                                            data-tt="<?= h($poh['tt_status'] ?? '') ?>"
                                            title="Edit Past Delivery">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <?php if (is_admin()): ?>
                                            <form action="<?= url('/past-obstetric/' . $poh['id'] . '/delete') ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                                                <button type="submit" class="btn btn-xs btn-outline-danger border-0 py-1 px-2" title="Delete Entry" data-confirm="Are you sure you want to delete this past delivery record?">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 4. Past / Archived Pregnancy Episodes Table (CHO I Records) -->
    <?php 
        $pastEpisodes = array_filter($allPrenatalEpisodes ?? [], function($ep) {
            return empty($ep['is_active']);
        });
    ?>
    <?php if (!empty($pastEpisodes)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-archive text-pink me-2"></i>Past Maternal Pregnancy Episodes (Archived CHO I Records)
                    </h5>
                    <span class="text-muted small">Historical completed pregnancy episodes managed at this facility with outcomes and visits logged.</span>
                </div>
                <span class="badge bg-light text-secondary border"><?= count($pastEpisodes) ?> Concluded Episode(s)</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center small">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3">Episode (Gravida / Para)</th>
                            <th>Gestational Window (LMP &bull; EDC)</th>
                            <th>Outcome &amp; Delivery Date</th>
                            <th>Visits</th>
                            <th class="text-start">Clinical Notes</th>
                            <th>Recorded By</th>
                            <th class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pastEpisodes as $pe): 
                            $outcomeClass = 'bg-secondary-subtle text-secondary';
                            if ($pe['delivery_outcome'] === 'Live Birth') {
                                $outcomeClass = 'bg-success-subtle text-success border border-success-subtle fw-semibold';
                            } elseif (in_array($pe['delivery_outcome'], ['Miscarriage', 'Ectopic'], true)) {
                                $outcomeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                            } elseif ($pe['delivery_outcome'] === 'Stillbirth') {
                                $outcomeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                            }
                        ?>
                            <tr>
                                <td class="text-start ps-3">
                                    <span class="fw-bold text-pink font-monospace">Gravida <?= h($pe['gravida']) ?> Para <?= h($pe['para']) ?></span>
                                    <div class="text-muted small">Episode #<?= h($pe['id']) ?></div>
                                </td>
                                <td>
                                    <div class="small"><span class="text-muted">LMP:</span> <?= !empty($pe['lmp']) ? date('M d, Y', strtotime($pe['lmp'])) : '--' ?></div>
                                    <div class="small font-monospace"><span class="text-muted">EDC:</span> <strong><?= !empty($pe['edc']) ? date('M d, Y', strtotime($pe['edc'])) : '--' ?></strong></div>
                                </td>
                                <td>
                                    <span class="badge <?= $outcomeClass ?>"><?= h($pe['delivery_outcome'] ?? 'Concluded') ?></span>
                                    <?php if (!empty($pe['delivery_date'])): ?>
                                        <div class="text-muted small font-monospace mt-1"><i class="bi bi-calendar-check me-1"></i><?= date('M d, Y', strtotime($pe['delivery_date'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-calendar2-check me-1"></i><?= (int)($pe['visit_count'] ?? 0) ?> visit(s)</span>
                                </td>
                                <td class="text-start text-truncate" style="max-width: 250px;" title="<?= h($pe['notes'] ?? '') ?>">
                                    <?= h($pe['notes'] ?: '--') ?>
                                </td>
                                <td class="text-muted small"><?= h($pe['creator_name'] ?? 'Clinician') ?></td>
                                <td class="pe-3 text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill shadow-2xs" data-bs-toggle="modal" data-bs-target="#pastVisitsModal-<?= $pe['id'] ?>" title="View Checkup Visits">
                                        <i class="bi bi-clock-history me-1"></i> View Visits (<?= (int)($pe['visit_count'] ?? 0) ?>)
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ==========================================================================
   PRENATAL MODALS
   ========================================================================== -->

<?php if ($activePrenatal): ?>
    <!-- 2. LOG SERIAL PRENATAL VISIT MODAL -->
    <div class="modal fade" id="addPrenatalVisitModal" tabindex="-1" aria-labelledby="addPrenatalVisitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="addPrenatalVisitModalLabel">
                        <i class="bi bi-journal-medical me-2"></i>Record Serial Prenatal Follow-Up Visit
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="<?= url('/prenatal/' . $activePrenatal['id'] . '/visit') ?>" method="POST" id="prenatalVisitForm">
                    <?= csrf_field() ?>

                    <div class="modal-body p-4 bg-white">
                        <div class="row g-3 small">
                            <!-- Visit Date -->
                            <div class="col-12 col-sm-6">
                                <label for="pv_visit_date" class="form-label fw-semibold text-secondary">Date of Visit <span class="text-danger">*</span></label>
                                <input type="date" name="visit_date" id="pv_visit_date" class="form-control bg-white" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- AOG in Weeks -->
                            <div class="col-12 col-sm-6">
                                <label for="pv_aog_weeks" class="form-label fw-semibold text-secondary">Age of Gestation (AOG in Weeks) <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" name="aog_weeks" id="pv_aog_weeks" class="form-control font-monospace bg-white" value="<?= h($activePrenatal['calculated_aog']['weeks'] ?? '12') ?>" required>
                            </div>

                            <!-- Maternal Blood Pressure -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="pv_bp_systolic" class="form-label fw-semibold text-secondary">Systolic BP (mmHg)</label>
                                <input type="number" name="bp_systolic" id="pv_bp_systolic" class="form-control bg-white" placeholder="120" min="40" max="250">
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="pv_bp_diastolic" class="form-label fw-semibold text-secondary">Diastolic BP (mmHg)</label>
                                <input type="number" name="bp_diastolic" id="pv_bp_diastolic" class="form-control bg-white" placeholder="80" min="30" max="150">
                            </div>

                            <!-- Weight & Height -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="pv_weight_kg" class="form-label fw-semibold text-secondary">Weight (kg)</label>
                                <input type="number" step="0.1" name="weight_kg" id="pv_weight_kg" class="form-control bg-white" placeholder="55.0">
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="pv_height_cm" class="form-label fw-semibold text-secondary">Height (cm)</label>
                                <input type="number" step="0.1" name="height_cm" id="pv_height_cm" class="form-control bg-white" placeholder="158.0">
                            </div>

                            <!-- Fetal Heart Tone -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="pv_fht" class="form-label fw-semibold text-secondary">Fetal Heart Tone (FHT bpm)</label>
                                <input type="number" name="fetal_heart_tone" id="pv_fht" class="form-control bg-white" placeholder="140">
                                <div class="form-text text-muted" style="font-size: 0.7rem;">Normal range: 120-160 bpm.</div>
                            </div>

                            <!-- Fundic Height -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="pv_fundic" class="form-label fw-semibold text-secondary">Fundic Height (cm)</label>
                                <input type="number" step="0.5" name="fundal_height_cm" id="pv_fundic" class="form-control bg-white" placeholder="e.g. 24.0">
                            </div>

                            <!-- Fetal Presentation -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="pv_presentation" class="form-label fw-semibold text-secondary">Fetal Presentation</label>
                                <select name="fetal_presentation" id="pv_presentation" class="form-select bg-white">
                                    <option value="Cephalic" selected>Cephalic</option>
                                    <option value="Breech">Breech</option>
                                    <option value="Transverse">Transverse</option>
                                    <option value="Undetermined">Variable / Not Determined</option>
                                </select>
                            </div>

                            <!-- Chief Complaint / Symptoms -->
                            <div class="col-12">
                                <label for="pv_chief_complaint" class="form-label fw-semibold text-secondary">Chief Complaint / Findings</label>
                                <input type="text" name="chief_complaint" id="pv_chief_complaint" class="form-control bg-white" placeholder="Routine checkup, mild backache, etc.">
                            </div>

                            <!-- TCB / Maternal TT Dose -->
                            <div class="col-12">
                                <label for="pv_tcb" class="form-label fw-semibold text-secondary">TCB / Maternal TT Immunization Given</label>
                                <input type="text" name="tcb" id="pv_tcb" class="form-control bg-white" placeholder="e.g. TT3 Administered, Iron/Folic acid given">
                            </div>

                            <!-- Remarks / Advice -->
                            <div class="col-12">
                                <label for="pv_remarks" class="form-label fw-semibold text-secondary">Remarks / Advice / Plan</label>
                                <textarea name="remarks" id="pv_remarks" rows="2" class="form-control bg-white" placeholder="Clinical remarks, health education given, return visit date..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Prenatal Visit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2b. EDIT SERIAL PRENATAL VISIT MODAL -->
    <div class="modal fade" id="editPrenatalVisitModal" tabindex="-1" aria-labelledby="editPrenatalVisitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="editPrenatalVisitModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>Edit Serial Prenatal Follow-Up Visit
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="" method="POST" id="editPrenatalVisitForm">
                    <?= csrf_field() ?>

                    <div class="modal-body p-4 bg-white">
                        <div class="row g-3 small">
                            <!-- Visit Date -->
                            <div class="col-12 col-sm-6">
                                <label for="edit_pv_visit_date" class="form-label fw-semibold text-secondary">Date of Visit <span class="text-danger">*</span></label>
                                <input type="date" name="visit_date" id="edit_pv_visit_date" class="form-control bg-white" required>
                            </div>

                            <!-- AOG in Weeks -->
                            <div class="col-12 col-sm-6">
                                <label for="edit_pv_aog_weeks" class="form-label fw-semibold text-secondary">Age of Gestation (AOG in Weeks) <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" name="aog_weeks" id="edit_pv_aog_weeks" class="form-control font-monospace bg-white" required>
                            </div>

                            <!-- Maternal Blood Pressure -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="edit_pv_bp_systolic" class="form-label fw-semibold text-secondary">Systolic BP (mmHg)</label>
                                <input type="number" name="bp_systolic" id="edit_pv_bp_systolic" class="form-control bg-white" placeholder="120" min="40" max="250">
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="edit_pv_bp_diastolic" class="form-label fw-semibold text-secondary">Diastolic BP (mmHg)</label>
                                <input type="number" name="bp_diastolic" id="edit_pv_bp_diastolic" class="form-control bg-white" placeholder="80" min="30" max="150">
                            </div>

                            <!-- Weight & Height -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="edit_pv_weight_kg" class="form-label fw-semibold text-secondary">Weight (kg)</label>
                                <input type="number" step="0.1" name="weight_kg" id="edit_pv_weight_kg" class="form-control bg-white" placeholder="55.0">
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="edit_pv_height_cm" class="form-label fw-semibold text-secondary">Height (cm)</label>
                                <input type="number" step="0.1" name="height_cm" id="edit_pv_height_cm" class="form-control bg-white" placeholder="158.0">
                            </div>

                            <!-- Fetal Heart Tone -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="edit_pv_fht" class="form-label fw-semibold text-secondary">Fetal Heart Tone (FHT bpm)</label>
                                <input type="number" name="fetal_heart_tone" id="edit_pv_fht" class="form-control bg-white" placeholder="140">
                                <div class="form-text text-muted" style="font-size: 0.7rem;">Normal range: 120-160 bpm.</div>
                            </div>

                            <!-- Fundic Height -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="edit_pv_fundic" class="form-label fw-semibold text-secondary">Fundic Height (cm)</label>
                                <input type="number" step="0.5" name="fundal_height_cm" id="edit_pv_fundic" class="form-control bg-white" placeholder="e.g. 24.0">
                            </div>

                            <!-- Fetal Presentation -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="edit_pv_presentation" class="form-label fw-semibold text-secondary">Fetal Presentation</label>
                                <select name="fetal_presentation" id="edit_pv_presentation" class="form-select bg-white">
                                    <option value="Cephalic">Cephalic</option>
                                    <option value="Breech">Breech</option>
                                    <option value="Transverse">Transverse</option>
                                    <option value="Undetermined">Variable / Not Determined</option>
                                </select>
                            </div>

                            <!-- Chief Complaint / Symptoms -->
                            <div class="col-12">
                                <label for="edit_pv_chief_complaint" class="form-label fw-semibold text-secondary">Chief Complaint / Findings</label>
                                <input type="text" name="chief_complaint" id="edit_pv_chief_complaint" class="form-control bg-white" placeholder="Routine checkup, mild backache, etc.">
                            </div>

                            <!-- TCB / Maternal TT Dose -->
                            <div class="col-12">
                                <label for="edit_pv_tcb" class="form-label fw-semibold text-secondary">TCB / Maternal TT Immunization Given</label>
                                <input type="text" name="tcb" id="edit_pv_tcb" class="form-control bg-white" placeholder="e.g. TT3 Administered, Iron/Folic acid given">
                            </div>

                            <!-- Remarks / Advice -->
                            <div class="col-12">
                                <label for="edit_pv_remarks" class="form-label fw-semibold text-secondary">Remarks / Advice / Plan</label>
                                <textarea name="remarks" id="edit_pv_remarks" rows="2" class="form-control bg-white" placeholder="Clinical remarks, health education given, return visit date..."></textarea>
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

    <!-- 2c. VIEW SERIAL PRENATAL VISIT DETAILS MODAL -->
    <div class="modal fade" id="viewPrenatalVisitModal" tabindex="-1" aria-labelledby="viewPrenatalVisitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-info bg-gradient text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="viewPrenatalVisitModalLabel">
                        <i class="bi bi-eye-fill me-2"></i>Prenatal Visit Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3 small">
                        <div class="col-12 col-sm-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Date of Visit</span>
                                <span class="fw-bold fs-6 text-dark" id="view_pv_date">--</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Age of Gestation (AOG)</span>
                                <span class="fw-bold fs-6 text-primary" id="view_pv_aog">--</span>
                            </div>
                        </div>

                        <!-- Clinical Vitals -->
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Blood Pressure</span>
                                <span class="fw-semibold text-dark" id="view_pv_bp">--</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Weight</span>
                                <span class="fw-semibold text-dark" id="view_pv_weight">--</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Height</span>
                                <span class="fw-semibold text-dark" id="view_pv_height">--</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Fetal Heart Tone</span>
                                <span class="fw-semibold text-pink" id="view_pv_fht">--</span>
                            </div>
                        </div>

                        <!-- Obstetric Exam -->
                        <div class="col-6 col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Fundic Height</span>
                                <span class="fw-semibold text-dark" id="view_pv_fundic">--</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Fetal Presentation</span>
                                <span class="fw-semibold text-dark" id="view_pv_presentation">--</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">TCB / Maternal TT Immunization</span>
                                <span class="fw-semibold text-dark" id="view_pv_tcb">--</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Chief Complaint / Findings</span>
                                <div class="text-dark" id="view_pv_complaint">--</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block">Remarks / Advice / Plan</span>
                                <div class="text-dark" id="view_pv_remarks">--</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Attended By:</span>
                                <span class="fw-bold text-dark" id="view_pv_attendant">--</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. CONCLUDE PREGNANCY EPISODE MODAL -->
    <div class="modal fade" id="concludePrenatalModal" tabindex="-1" aria-labelledby="concludePrenatalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-danger text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="concludePrenatalModalLabel">
                        <i class="bi bi-check2-circle me-2"></i>Conclude Pregnancy Episode
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="<?= url('/prenatal/' . $activePrenatal['id'] . '/conclude') ?>" method="POST" id="concludePrenatalForm">
                    <?= csrf_field() ?>

                    <div class="modal-body p-4 bg-white small">
                        <div class="alert alert-warning border-0 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Concluding this episode will close the active pregnancy and automatically update the patient's <strong>Master Obstetric Score (GTPAL)</strong> in their Individual Health Profile (IHP).
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label for="conclude_delivery_date" class="form-label fw-semibold text-secondary">Delivery / Outcome Date <span class="text-danger">*</span></label>
                                <input type="date" name="delivery_date" id="conclude_delivery_date" class="form-control bg-white" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="col-12 col-sm-6">
                                <label for="conclude_delivery_outcome" class="form-label fw-semibold text-secondary">Delivery Outcome <span class="text-danger">*</span></label>
                                <select name="delivery_outcome" id="conclude_delivery_outcome" class="form-select" required>
                                    <option value="Live Birth" selected>Live Birth</option>
                                    <option value="Stillbirth">Stillbirth</option>
                                    <option value="Miscarriage">Miscarriage / Abortion</option>
                                    <option value="Ectopic">Ectopic Pregnancy</option>
                                    <option value="Other">Other / Transferred</option>
                                </select>
                            </div>

                            <!-- Delivery Details Container (Shown for Live Birth & Stillbirth) -->
                            <div id="conclude_delivery_details" class="col-12">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                        <span class="fw-bold text-dark"><i class="bi bi-file-earmark-medical me-1 text-danger"></i>Delivery Details & IHP Sync</span>
                                        <span class="badge bg-secondary-subtle text-secondary">Auto-syncs to IHP & Past Deliveries</span>
                                    </div>

                                    <div class="row g-3">
                                        <!-- Delivery Classification (Term vs Preterm) -->
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fw-semibold text-secondary d-block mb-1">Delivery Classification <span class="text-danger">*</span></label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="delivery_classification" id="classification_term" value="Term" checked>
                                                <label class="btn btn-outline-primary btn-sm py-2" for="classification_term">
                                                    <i class="bi bi-check-circle me-1"></i>Term (≥37 wks)
                                                </label>

                                                <input type="radio" class="btn-check" name="delivery_classification" id="classification_preterm" value="Preterm">
                                                <label class="btn btn-outline-warning btn-sm py-2" for="classification_preterm">
                                                    <i class="bi bi-clock-history me-1"></i>Preterm (&lt;37 wks)
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Delivery Type -->
                                        <div class="col-12 col-sm-6">
                                            <label for="conclude_delivery_type" class="form-label fw-semibold text-secondary">Delivery Type <span class="text-danger">*</span></label>
                                            <select name="delivery_type" id="conclude_delivery_type" class="form-select form-select-sm">
                                                <option value="NSD" selected>NSD (Normal Spontaneous Delivery)</option>
                                                <option value="CS">CS (Caesarean Section)</option>
                                                <option value="Other">Vacuum / Forceps / Other</option>
                                            </select>
                                        </div>

                                        <!-- Living Children Count -->
                                        <div class="col-12 col-sm-4">
                                            <label for="conclude_living_children" class="form-label fw-semibold text-secondary">Living Children From Delivery</label>
                                            <input type="number" name="living_children" id="conclude_living_children" class="form-control form-control-sm text-center font-monospace" value="1" min="0" max="10">
                                            <small class="text-muted" style="font-size: 0.7rem;">Added to IHP Living Children (L)</small>
                                        </div>

                                        <!-- Infant Sex -->
                                        <div class="col-12 col-sm-4">
                                            <label for="conclude_infant_sex" class="form-label fw-semibold text-secondary">Infant Sex</label>
                                            <select name="infant_sex" id="conclude_infant_sex" class="form-select form-select-sm">
                                                <option value="Male" selected>Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Unknown">Unknown / Undetermined</option>
                                            </select>
                                        </div>

                                        <!-- Maternal TT Status at Delivery -->
                                        <div class="col-12 col-sm-4">
                                            <label for="conclude_tt_status" class="form-label fw-semibold text-secondary">Maternal TT Status</label>
                                            <input type="text" name="tt_status" id="conclude_tt_status" class="form-control form-control-sm" placeholder="e.g. TT2 / Fully Immunized">
                                        </div>

                                        <!-- Place of Delivery -->
                                        <div class="col-12 col-sm-6">
                                            <label for="conclude_place_of_delivery" class="form-label fw-semibold text-secondary">Place of Delivery</label>
                                            <input type="text" name="place_of_delivery" id="conclude_place_of_delivery" class="form-control form-control-sm" value="Health Center / Lying-in Clinic" placeholder="Clinic or Hospital Name">
                                        </div>

                                        <!-- Attended By -->
                                        <div class="col-12 col-sm-6">
                                            <label for="conclude_attended_by" class="form-label fw-semibold text-secondary">Attended By</label>
                                            <input type="text" name="attended_by" id="conclude_attended_by" class="form-control form-control-sm" value="<?= h($_SESSION['user_name'] ?? 'Midwife') ?>" placeholder="Name of Midwife / Doctor">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="conclude_notes" class="form-label fw-semibold text-secondary">Clinical Outcome Notes / Remarks</label>
                                <textarea name="notes" id="conclude_notes" rows="2" class="form-control" placeholder="Infant weight, APGAR score, complications, maternal recovery condition..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i>Confirm Conclusion & Sync IHP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 5. CANCEL PREGNANCY EPISODE MODAL -->
    <div class="modal fade" id="cancelPrenatalModal" tabindex="-1" aria-labelledby="cancelPrenatalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-dark text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="cancelPrenatalModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Cancel Pregnancy Episode
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="<?= url('/prenatal/' . $activePrenatal['id'] . '/cancel') ?>" method="POST" id="cancelPrenatalForm">
                    <?= csrf_field() ?>

                    <div class="modal-body p-4 bg-white small">
                        <div class="alert alert-warning border-0 small mb-3">
                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                            Are you sure you want to cancel this pregnancy episode?
                            <div class="mt-1 text-muted">
                                This action will remove the active episode (e.g. started in error) and automatically revert the patient's <strong>Gravida count</strong> in their IHP.
                            </div>
                        </div>

                        <p class="text-secondary mb-2">
                            <strong>Safety Check:</strong> This episode has <strong>0 checkup visits</strong> logged, so it can be safely cancelled.
                        </p>

                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label fw-semibold text-secondary">Reason for Cancellation</label>
                            <select name="cancel_reason" id="cancel_reason" class="form-select">
                                <option value="Enrolled in error / wrong patient record" selected>Enrolled in error / wrong patient record</option>
                                <option value="Duplicate pregnancy episode">Duplicate pregnancy episode</option>
                                <option value="Patient not pregnant (false positive / amended)">Patient not pregnant (false positive / amended)</option>
                                <option value="Other / Staff cancellation">Other / Staff cancellation</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Episode</button>
                        <button type="submit" class="btn btn-danger px-4 fw-semibold">
                            <i class="bi bi-trash3 me-1"></i>Cancel & Remove Episode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- 6. ADD PAST OBSTETRIC DELIVERY MODAL -->
<div class="modal fade" id="addPastObstetricModal" tabindex="-1" aria-labelledby="addPastObstetricModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="addPastObstetricModalLabel">
                    <i class="bi bi-clock-history me-2"></i>Add Past Delivery Record (G1, G2...)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/past-obstetric') ?>" method="POST" id="pastObstetricForm">
                <?= csrf_field() ?>

                <div class="modal-body p-4 bg-white small">
                    <div class="row g-3">
                        <!-- Gravida No -->
                        <div class="col-12 col-sm-4">
                            <label for="gravida_no" class="form-label fw-semibold text-secondary">Gravida No. (Pregnancy #) <span class="text-danger">*</span></label>
                            <input type="number" name="gravida_no" class="form-control font-monospace" placeholder="e.g. 1" min="1" max="25" value="<?= count($pastDeliveries) + 1 ?>" required>
                        </div>

                        <!-- Delivery Type -->
                        <div class="col-12 col-sm-4">
                            <label for="delivery_type" class="form-label fw-semibold text-secondary">Delivery Type <span class="text-danger">*</span></label>
                            <select name="delivery_type" class="form-select" required>
                                <option value="NSD" selected>NSD (Normal Spontaneous)</option>
                                <option value="CS">CS (Caesarean Section)</option>
                                <option value="Other">Vacuum / Forceps / Other</option>
                                <option value="Abortion">Abortion / Miscarriage</option>
                            </select>
                        </div>

                        <!-- Infant Sex -->
                        <div class="col-12 col-sm-4">
                            <label for="infant_sex" class="form-label fw-semibold text-secondary">Infant Sex</label>
                            <select name="infant_sex" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Unknown">Unknown / Undetermined</option>
                            </select>
                        </div>

                        <!-- Place of Delivery -->
                        <div class="col-12 col-sm-6">
                            <label for="place_of_delivery" class="form-label fw-semibold text-secondary">Place of Delivery</label>
                            <input type="text" name="place_of_delivery" class="form-control" placeholder="e.g. Sta. Rosa Lying-in, SRCH, Home">
                        </div>

                        <!-- Year Delivered -->
                        <div class="col-12 col-sm-6">
                            <label for="year_delivered" class="form-label fw-semibold text-secondary">Year Delivered</label>
                            <input type="number" name="year_delivered" class="form-control font-monospace" placeholder="e.g. 2022" min="1970" max="<?= date('Y') ?>">
                        </div>

                        <!-- Attended By -->
                        <div class="col-12 col-sm-6">
                            <label for="attended_by" class="form-label fw-semibold text-secondary">Birth Attendant</label>
                            <input type="text" name="attended_by" class="form-control" placeholder="e.g. Midwife Ramos, Dr. Santos">
                        </div>

                        <!-- Child Status -->
                        <div class="col-12 col-sm-6">
                            <label for="status" class="form-label fw-semibold text-secondary">Child Status</label>
                            <select name="status" class="form-select">
                                <option value="Alive" selected>Alive</option>
                                <option value="Not Alive">Not Alive</option>
                            </select>
                        </div>

                        <!-- Maternal TT Status -->
                        <div class="col-12">
                            <label for="tt_status" class="form-label fw-semibold text-secondary">Maternal TT (Tetanus Toxoid) Injections During Pregnancy</label>
                            <input type="text" name="tt_status" class="form-control" placeholder="e.g. TT2 Given in 2022">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Past Delivery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 6b. EDIT PAST OBSTETRIC DELIVERY MODAL -->
<div class="modal fade" id="editPastObstetricModal" tabindex="-1" aria-labelledby="editPastObstetricModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="editPastObstetricModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Past Delivery Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="" method="POST" id="editPastObstetricForm">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

                <div class="modal-body p-4 bg-white small">
                    <div class="row g-3">
                        <!-- Gravida No -->
                        <div class="col-12 col-sm-4">
                            <label for="edit_poh_gravida" class="form-label fw-semibold text-secondary">Gravida No. (Pregnancy #) <span class="text-danger">*</span></label>
                            <input type="number" name="gravida_no" id="edit_poh_gravida" class="form-control font-monospace" placeholder="e.g. 1" min="1" max="25" required>
                        </div>

                        <!-- Delivery Type -->
                        <div class="col-12 col-sm-4">
                            <label for="edit_poh_delivery_type" class="form-label fw-semibold text-secondary">Delivery Type <span class="text-danger">*</span></label>
                            <select name="delivery_type" id="edit_poh_delivery_type" class="form-select" required>
                                <option value="NSD">NSD (Normal Spontaneous)</option>
                                <option value="CS">CS (Caesarean Section)</option>
                                <option value="Other">Vacuum / Forceps / Other</option>
                                <option value="Abortion">Abortion / Miscarriage</option>
                            </select>
                        </div>

                        <!-- Infant Sex -->
                        <div class="col-12 col-sm-4">
                            <label for="edit_poh_sex" class="form-label fw-semibold text-secondary">Infant Sex</label>
                            <select name="infant_sex" id="edit_poh_sex" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Unknown">Unknown / Undetermined</option>
                            </select>
                        </div>

                        <!-- Place of Delivery -->
                        <div class="col-12 col-sm-6">
                            <label for="edit_poh_place" class="form-label fw-semibold text-secondary">Place of Delivery</label>
                            <input type="text" name="place_of_delivery" id="edit_poh_place" class="form-control" placeholder="e.g. Sta. Rosa Lying-in, SRCH, Home">
                        </div>

                        <!-- Year Delivered -->
                        <div class="col-12 col-sm-6">
                            <label for="edit_poh_year" class="form-label fw-semibold text-secondary">Year Delivered</label>
                            <input type="number" name="year_delivered" id="edit_poh_year" class="form-control font-monospace" placeholder="e.g. 2022" min="1970" max="<?= date('Y') ?>">
                        </div>

                        <!-- Attended By -->
                        <div class="col-12 col-sm-6">
                            <label for="edit_poh_attendant" class="form-label fw-semibold text-secondary">Birth Attendant</label>
                            <input type="text" name="attended_by" id="edit_poh_attendant" class="form-control" placeholder="e.g. Midwife Ramos, Dr. Santos">
                        </div>

                        <!-- Child Status -->
                        <div class="col-12 col-sm-6">
                            <label for="edit_poh_status" class="form-label fw-semibold text-secondary">Child Status</label>
                            <select name="status" id="edit_poh_status" class="form-select">
                                <option value="Alive">Alive</option>
                                <option value="Not Alive">Not Alive</option>
                            </select>
                        </div>

                        <!-- Maternal TT Status -->
                        <div class="col-12">
                            <label for="edit_poh_tt" class="form-label fw-semibold text-secondary">Maternal TT (Tetanus Toxoid) Injections During Pregnancy</label>
                            <input type="text" name="tt_status" id="edit_poh_tt" class="form-control" placeholder="e.g. TT2 Given in 2022">
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

<!-- 5. PAST EPISODE VISITS MODALS -->
<?php if (!empty($pastEpisodes)): ?>
    <?php foreach ($pastEpisodes as $pe): 
        $pVisits = $pastEpisodesVisits[$pe['id']] ?? [];
    ?>
    <div class="modal fade" id="pastVisitsModal-<?= $pe['id'] ?>" tabindex="-1" aria-labelledby="pastVisitsModalLabel-<?= $pe['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-pink text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="pastVisitsModalLabel-<?= $pe['id'] ?>">
                            <i class="bi bi-clock-history me-2"></i>Past Prenatal Checkup Visits &bull; Episode #<?= h($pe['id']) ?>
                        </h5>
                        <div class="small opacity-75">
                            Gravida <?= h($pe['gravida']) ?> Para <?= h($pe['para']) ?> &bull; Concluded: <?= h($pe['delivery_outcome'] ?? 'Delivered') ?> <?= !empty($pe['delivery_date']) ? '(' . date('M d, Y', strtotime($pe['delivery_date'])) . ')' : '' ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 bg-light rounded-3 mb-3 border small">
                        <div>
                            <span class="text-muted">LMP:</span> <strong><?= !empty($pe['lmp']) ? date('M d, Y', strtotime($pe['lmp'])) : '--' ?></strong>
                            <span class="mx-2 text-muted">&bull;</span>
                            <span class="text-muted">EDC:</span> <strong><?= !empty($pe['edc']) ? date('M d, Y', strtotime($pe['edc'])) : '--' ?></strong>
                            <?php if (!empty($pe['notes'])): ?>
                                <span class="mx-2 text-muted">&bull;</span>
                                <span class="text-muted">Notes:</span> <em><?= h($pe['notes']) ?></em>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="badge bg-secondary-subtle text-dark border"><?= count($pVisits) ?> total checkup visit(s)</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center small">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start ps-3">Visit Date</th>
                                    <th>AOG</th>
                                    <th>Maternal BP</th>
                                    <th>Weight (kg)</th>
                                    <th>Height (cm)</th>
                                    <th>FHT (bpm)</th>
                                    <th>Fundic Ht (cm)</th>
                                    <th>Presentation</th>
                                    <th>TCB / Tetanus</th>
                                    <th class="text-start">Chief Complaint / Remarks</th>
                                    <th class="pe-3 text-end">Attended By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pVisits)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4 text-muted">
                                            <i class="bi bi-calendar-x fs-3 d-block mb-1 opacity-50"></i>
                                            No prenatal checkup visits were recorded for this completed pregnancy episode.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pVisits as $pv): 
                                        $fht = (int)($pv['fetal_heart_tone'] ?? 0);
                                        $fhtBadge = 'badge bg-light text-dark border';
                                        if ($fht > 0) {
                                            if ($fht >= 120 && $fht <= 160) {
                                                $fhtBadge = 'badge bg-success-subtle text-success border border-success-subtle';
                                            } else {
                                                $fhtBadge = 'badge bg-danger-subtle text-danger border border-danger-subtle fw-bold';
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y', strtotime($pv['visit_date'])) ?></td>
                                            <td class="font-monospace fw-semibold"><?= h($pv['aog_weeks']) ?> wks</td>
                                            <td class="font-monospace"><?= h($pv['bp_systolic'] ?? '--') ?>/<?= h($pv['bp_diastolic'] ?? '--') ?></td>
                                            <td><?= h($pv['weight_kg'] ?? '--') ?></td>
                                            <td><?= h($pv['height_cm'] ?? '--') ?></td>
                                            <td><span class="<?= $fhtBadge ?>"><?= $fht > 0 ? $fht . ' bpm' : '--' ?></span></td>
                                            <td><?= h($pv['fundal_height_cm'] ?? '--') ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= h($pv['fetal_presentation'] ?? 'Cephalic') ?></span></td>
                                            <td class="text-muted"><?= h($pv['tcb'] ?? '--') ?></td>
                                            <td class="text-start small">
                                                <?php if (!empty($pv['chief_complaint'])): ?>
                                                    <div><strong>CC:</strong> <?= h($pv['chief_complaint']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($pv['remarks'])): ?>
                                                    <div><?= h($pv['remarks']) ?></div>
                                                <?php endif; ?>
                                                <?php if (empty($pv['chief_complaint']) && empty($pv['remarks'])): ?>
                                                    <span class="text-muted">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-3 text-end text-muted small"><?= h($pv['attendant_name'] ?? 'Midwife') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Hidden Delete Prenatal Visit Form -->
<form id="deletePrenatalVisitForm" method="POST" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
</form>

<style>
.bg-pink {
    background-color: #d63384 !important;
}
.text-pink {
    color: #d63384 !important;
}
.border-pink {
    border: 1px solid #f3c2db !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Delete Prenatal Visit
    document.querySelectorAll('.btn-delete-prenatal-visit').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const date = this.getAttribute('data-date') || '';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Prenatal Visit?',
                    text: `Are you sure you want to remove the prenatal checkup visit dated ${date}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete visit',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deletePrenatalVisitForm');
                        form.action = `<?= url('/prenatal/visit/') ?>${id}/delete`;
                        form.submit();
                    }
                });
            } else if (confirm(`Remove prenatal checkup visit dated ${date}?`)) {
                const form = document.getElementById('deletePrenatalVisitForm');
                form.action = `<?= url('/prenatal/visit/') ?>${id}/delete`;
                form.submit();
            }
        });
    });

    // 1b. Edit / Clinical Assessment Prenatal Visit
    document.querySelectorAll('.btn-edit-prenatal-visit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editPrenatalVisitForm');
            if (form) {
                form.action = `<?= url('/prenatal/visit/') ?>${id}/update`;
            }
            const setVal = (fieldId, attr) => {
                const el = document.getElementById(fieldId);
                if (el) el.value = this.getAttribute(attr) || '';
            };
            setVal('edit_pv_visit_date', 'data-date');
            setVal('edit_pv_aog_weeks', 'data-aog');
            setVal('edit_pv_bp_systolic', 'data-systolic');
            setVal('edit_pv_bp_diastolic', 'data-diastolic');
            setVal('edit_pv_weight_kg', 'data-weight');
            setVal('edit_pv_height_cm', 'data-height');
            setVal('edit_pv_fht', 'data-fht');
            setVal('edit_pv_fundic', 'data-fundic');
            setVal('edit_pv_presentation', 'data-presentation');
            setVal('edit_pv_tcb', 'data-tcb');
            setVal('edit_pv_chief_complaint', 'data-complaint');
            setVal('edit_pv_remarks', 'data-remarks');

            const modalEl = document.getElementById('editPrenatalVisitModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    });

    // 1c. View Prenatal Visit Details
    document.querySelectorAll('.btn-view-prenatal-visit').forEach(btn => {
        btn.addEventListener('click', function() {
            const setTxt = (fieldId, attr) => {
                const el = document.getElementById(fieldId);
                if (el) el.textContent = this.getAttribute(attr) || '--';
            };
            setTxt('view_pv_date', 'data-date');
            const aog = this.getAttribute('data-aog');
            const aogEl = document.getElementById('view_pv_aog');
            if (aogEl) aogEl.textContent = aog ? `${aog} weeks` : '--';

            const sys = this.getAttribute('data-systolic');
            const dia = this.getAttribute('data-diastolic');
            const bpEl = document.getElementById('view_pv_bp');
            if (bpEl) {
                bpEl.textContent = (sys && sys !== '--' && dia && dia !== '--') ? `${sys}/${dia} mmHg` : '--';
            }

            const wt = this.getAttribute('data-weight');
            const wtEl = document.getElementById('view_pv_weight');
            if (wtEl) wtEl.textContent = (wt && wt !== '--') ? `${wt} kg` : '--';

            const ht = this.getAttribute('data-height');
            const htEl = document.getElementById('view_pv_height');
            if (htEl) htEl.textContent = (ht && ht !== '--') ? `${ht} cm` : '--';

            setTxt('view_pv_fht', 'data-fht');
            const fundic = this.getAttribute('data-fundic');
            const fundicEl = document.getElementById('view_pv_fundic');
            if (fundicEl) fundicEl.textContent = (fundic && fundic !== '--') ? `${fundic} cm` : '--';

            setTxt('view_pv_presentation', 'data-presentation');
            setTxt('view_pv_tcb', 'data-tcb');
            setTxt('view_pv_complaint', 'data-complaint');
            setTxt('view_pv_remarks', 'data-remarks');
            setTxt('view_pv_attendant', 'data-attendant');

            const modalEl = document.getElementById('viewPrenatalVisitModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    });

    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Conclude Episode Modal: Dynamic Delivery Detail Toggle
    const concludeOutcomeSelect = document.getElementById('conclude_delivery_outcome');
    const concludeDeliveryDetails = document.getElementById('conclude_delivery_details');
    const concludeLivingChildren = document.getElementById('conclude_living_children');

    if (concludeOutcomeSelect && concludeDeliveryDetails) {
        concludeOutcomeSelect.addEventListener('change', function() {
            const outcome = this.value;
            if (outcome === 'Live Birth' || outcome === 'Stillbirth') {
                concludeDeliveryDetails.style.display = '';
                if (outcome === 'Stillbirth' && concludeLivingChildren) {
                    concludeLivingChildren.value = '0';
                } else if (outcome === 'Live Birth' && concludeLivingChildren && parseInt(concludeLivingChildren.value) === 0) {
                    concludeLivingChildren.value = '1';
                }
            } else {
                concludeDeliveryDetails.style.display = 'none';
                if (concludeLivingChildren) {
                    concludeLivingChildren.value = '0';
                }
            }
        });
    }

    // 3. Edit Past Obstetric Delivery Handler
    document.querySelectorAll('.btn-edit-past-obstetric').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editPastObstetricForm');
            if (form) {
                form.action = '<?= url('/past-obstetric/') ?>' + id + '/update';
            }
            const setVal = (fieldId, val) => {
                const el = document.getElementById(fieldId);
                if (el) el.value = val || '';
            };
            setVal('edit_poh_gravida', this.getAttribute('data-gravida'));
            setVal('edit_poh_delivery_type', this.getAttribute('data-delivery-type'));
            setVal('edit_poh_sex', this.getAttribute('data-sex'));
            setVal('edit_poh_place', this.getAttribute('data-place'));
            setVal('edit_poh_year', this.getAttribute('data-year'));
            setVal('edit_poh_attendant', this.getAttribute('data-attendant'));
            setVal('edit_poh_status', this.getAttribute('data-status'));
            setVal('edit_poh_tt', this.getAttribute('data-tt'));

            const modalEl = document.getElementById('editPastObstetricModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    });
});
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
