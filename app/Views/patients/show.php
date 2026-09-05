<?php
/**
 * @var array $patient Patient demographic record
 * @var array $vitalsHistory Vital signs history
 * @var array|false $latestVitals Latest vital signs
 * @var array $consultationsHistory Consultation records
 * @var array $appointmentsHistory Appointment history
 * @var array $queueHistory Daily queue history logs
 * @var array|false $medicalHistory Annex A1 IHP Medical History
 * @var array $familyMembers Household members sharing same family_no
 * @var array|false $activePrenatal Active pregnancy episode
 * @var array $prenatalVisits Follow-up prenatal visits
 * @var array $pastDeliveries Past obstetric delivery histories
 * @var array $allPrenatalEpisodes All historical pregnancy episodes
 * @var array|false $wellbabyRecord Well Baby child profile
 * @var array $growthLogs Pediatric growth anthropometrics logs
 * @var array $patientImmunizations All immunizations administered
 * @var array $vaccineMap Vaccine name+dose lookup map
 * @var array $potentialMothers Potential mother profiles in directory
 * @var array $programBadge Program classification badge
 */

$title = 'Patient Profile';
$breadcrumbs = [
    'Patients' => '/patients',
    'Profile' => null
];
require dirname(__DIR__) . '/layout/header.php';

// Clinical Safety Flags Detection
$hasAllergy = false;
$allergyText = '';
$pastMedical = $medicalHistory['past_medical_history'] ?? [];
if (is_array($pastMedical)) {
    if (!empty($pastMedical['Allergy'])) {
        $hasAllergy = true;
        $allergyText = $pastMedical['Allergy'];
    } elseif (in_array('Allergy', $pastMedical) || !empty($pastMedical['allergy'])) {
        $hasAllergy = true;
        $allergyText = 'Allergy Recorded';
    }
} elseif (is_string($pastMedical) && stripos($pastMedical, 'allergy') !== false) {
    $hasAllergy = true;
    $allergyText = $pastMedical;
}

// Pre-Eclampsia Risk
$isPreEclampsia = !empty($activePrenatal['pre_eclampsia']) && (int)$activePrenatal['pre_eclampsia'] === 1;

// Hypertension Alert (BP >= 140/90 or recorded history)
$isHypertensive = false;
if (!empty($latestVitals['bp_systolic']) && (int)$latestVitals['bp_systolic'] >= 140) {
    $isHypertensive = true;
} elseif (!empty($latestVitals['bp_diastolic']) && (int)$latestVitals['bp_diastolic'] >= 90) {
    $isHypertensive = true;
} elseif (is_array($pastMedical) && (!empty($pastMedical['Hypertension']) || in_array('Hypertension', $pastMedical))) {
    $isHypertensive = true;
}

$isFemale = (strtolower($patient['sex']) === 'female');
$isChild = ((int)$patient['age'] <= 5) || !empty($wellbabyRecord);
?>

<!-- ==========================================================================
   PAGE HEADER (Title, Subtitle & Action)
   ========================================================================== -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Clinical Care Workstation</h2>
        <p class="text-secondary small mb-0">Manage check-ups, PhilHealth IHP profile, maternal/child care, vitals, and appointments.</p>
    </div>
    <a href="<?= url('/patients') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Directory
    </a>
</div>

<!-- ==========================================================================
   PATIENT MASTER HEADER (Compact Universal Identity & Actions Banner)
   ========================================================================== -->
<div class="card card-premium shadow-sm border-0 mb-3">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            
            <!-- Left: Avatar, Name, Badges & Baseline Demographics -->
            <div class="d-flex align-items-center gap-3 min-w-0">
                <div class="avatar-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle shadow-xs flex-shrink-0" style="width: 48px; height: 48px; font-size: 1.15rem;">
                    <?= strtoupper(mb_substr($patient['first_name'], 0, 1) . mb_substr($patient['last_name'], 0, 1)) ?>
                </div>

                <div class="min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h3 class="h5 fw-bold text-primary-dark mb-0 lh-1">
                            <?= h($patient['last_name']) ?>, <?= h($patient['first_name']) ?> <?= h($patient['middle_name'] ? mb_substr($patient['middle_name'], 0, 1) . '.' : '') ?> <?= h($patient['suffix'] ?? '') ?>
                        </h3>
                        <span class="badge bg-light text-dark border font-monospace fs-7">
                            <?= h($patient['patient_no']) ?>
                        </span>
                        <span class="badge <?= $programBadge['class'] ?? 'bg-primary text-white' ?> fs-7">
                            <i class="bi <?= $programBadge['icon'] ?? 'bi-tag' ?> me-1"></i><?= $programBadge['label'] ?? 'General OPD' ?>
                        </span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 small text-secondary">
                        <span><strong><?= h($patient['age']) ?></strong> yrs &bull; <?= h($patient['sex']) ?></span>
                        <span class="text-muted">&bull;</span>
                        <span>DOB: <strong><?= date('M d, Y', strtotime($patient['dob'])) ?></strong></span>
                        <span class="text-muted">&bull;</span>
                        <span>Blood: <strong class="text-danger"><?= h($patient['blood_type'] ?? 'Unknown') ?></strong></span>
                        <?php if (!empty($patient['family_no'])): ?>
                            <span class="text-muted">&bull;</span>
                            <a href="<?= url('/patients?search=' . urlencode($patient['family_no'])) ?>" class="text-decoration-none text-secondary" title="View household in directory">
                                <i class="bi bi-house-door-fill text-primary"></i> Fam # <?= h($patient['family_no']) ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($patient['philhealth_no'])): ?>
                            <span class="text-muted">&bull;</span>
                            <span>PHIC: <span class="font-monospace text-dark"><?= h($patient['philhealth_no']) ?></span></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Patient-Level Action Controls -->
            <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 ms-auto ms-md-0">
                <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs" title="Edit Patient Identity & Demographics">
                    <i class="bi bi-pencil-square me-1"></i> Edit Profile
                </a>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs" title="Print Patient Profile">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs" data-bs-toggle="modal" data-bs-target="#archivePatientModal" title="Archive Patient Record">
                        <i class="bi bi-archive me-1"></i> Archive
                    </button>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Clinical Safety Alert Banners (Universal across all tabs) -->
<?php if ($hasAllergy || $isPreEclampsia || $isHypertensive): ?>
    <div class="d-flex flex-column gap-2 mb-3">
        <?php if ($hasAllergy): ?>
            <div class="alert alert-danger d-flex align-items-center py-2 px-3 mb-0 shadow-xs border-0" role="alert">
                <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
                <div>
                    <strong>CLINICAL SAFETY ALLERGY ALERT:</strong> <?= h($allergyText) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isPreEclampsia): ?>
            <div class="alert alert-danger d-flex align-items-center py-2 px-3 mb-0 shadow-xs border-0" role="alert">
                <i class="bi bi-heart-pulse-fill fs-5 me-2"></i>
                <div>
                    <strong>HIGH-RISK PREGNANCY ALERT:</strong> Patient flagged for Pre-Eclampsia monitoring.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isHypertensive): ?>
            <div class="alert alert-warning d-flex align-items-center py-2 px-3 mb-0 shadow-xs border-0 text-dark" role="alert">
                <i class="bi bi-speedometer2 fs-5 me-2 text-danger"></i>
                <div>
                    <strong>HYPERTENSION ALERT:</strong> Recorded BP is elevated (&ge;140/90 mmHg) or chronic history recorded.
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- ==========================================================================
   CLINICAL WORKSTATION (FULL 100% WIDTH MODULAR TABS)
   ========================================================================== -->
<div class="card card-premium shadow-sm border-0 mb-5">
    
    <!-- Sleek Single-Line Tab Bar -->
    <div class="card-header bg-white p-0 border-0">
        <ul class="nav nav-tabs m-0 border-0 flex-nowrap" id="workstationTabs" role="tablist">
            <!-- Tab 1: Overview -->
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold text-nowrap" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
                            <i class="bi bi-grid-1x2-fill me-1"></i> Overview
                        </button>
                    </li>
                    
                    <!-- Tab 2: IHP Medical History -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-ihp-btn" data-bs-toggle="tab" data-bs-target="#tab-ihp" type="button" role="tab">
                            <i class="bi bi-file-earmark-medical-fill me-1"></i> IHP History
                        </button>
                    </li>

                    <!-- Tab 3: Consultations (SOAP) -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-consultations-btn" data-bs-toggle="tab" data-bs-target="#tab-consultations" type="button" role="tab">
                            <i class="bi bi-clipboard2-pulse-fill me-1"></i> Consultations
                            <span class="badge bg-light text-secondary border ms-1"><?= count($consultationsHistory) ?></span>
                        </button>
                    </li>

                    <!-- Tab 4: Vital Signs History -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-vitals-btn" data-bs-toggle="tab" data-bs-target="#tab-vitals" type="button" role="tab">
                            <i class="bi bi-activity me-1"></i> Vitals Log
                            <span class="badge bg-light text-secondary border ms-1"><?= count($vitalsHistory) ?></span>
                        </button>
                    </li>

                    <!-- Tab 5: Universal Immunizations -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-immunizations-btn" data-bs-toggle="tab" data-bs-target="#tab-immunizations" type="button" role="tab">
                            <i class="bi bi-shield-plus me-1"></i> Immunizations
                            <span class="badge bg-light text-secondary border ms-1"><?= count($patientImmunizations) ?></span>
                        </button>
                    </li>

                    <!-- Dynamic Tab 6: Maternal / Prenatal (Rendered for females) -->
                    <?php if ($isFemale): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link tab-prenatal fw-semibold text-nowrap" id="tab-prenatal-btn" data-bs-toggle="tab" data-bs-target="#tab-prenatal" type="button" role="tab">
                                <i class="bi bi-heart-pulse-fill me-1"></i> Prenatal Care
                                <?php if ($activePrenatal): ?>
                                    <span class="badge bg-pink text-white ms-1">Active (<?= h($activePrenatal['calculated_aog']['weeks'] ?? '--') ?>w)</span>
                                <?php endif; ?>
                            </button>
                        </li>
                    <?php endif; ?>

                    <!-- Dynamic Tab 7: Well Baby & Growth (Rendered for infants/children) -->
                    <?php if ($isChild): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link tab-wellbaby fw-semibold text-nowrap" id="tab-wellbaby-btn" data-bs-toggle="tab" data-bs-target="#tab-wellbaby" type="button" role="tab">
                                <i class="bi bi-emoji-smile-fill me-1"></i> Well Baby
                                <?php if ($wellbabyRecord): ?>
                                    <span class="badge bg-success text-white ms-1"><?= count($growthLogs) ?> logs</span>
                                <?php endif; ?>
                            </button>
                        </li>
                    <?php endif; ?>

                    <!-- Tab 8: Appointments & Queue -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-appointments-btn" data-bs-toggle="tab" data-bs-target="#tab-appointments" type="button" role="tab">
                            <i class="bi bi-calendar3 me-1"></i> Appointments
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Panels Body -->
            <div class="card-body p-4">
                <div class="tab-content" id="workstationTabsContent">
                    
                    <!-- ==============================================================
                       TAB 1: OVERVIEW & CLINICAL SNAPSHOT
                       ============================================================== -->
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                        <div class="row g-3">
                            
                            <!-- 1. Demographic & Civil Profile Card -->
                            <div class="col-12 col-lg-6">
                                <div class="card border rounded-3 h-100 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-person-lines-fill text-primary me-2"></i>Demographic & Civil Profile
                                        </h5>
                                        <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-xs btn-outline-primary py-1 px-2" title="Edit Demographics">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </a>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Civil Status</span>
                                                <span class="fw-semibold text-dark"><?= h($patient['civil_status']) ?></span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Religion</span>
                                                <span class="text-dark"><?= h($patient['religion'] ?? 'Unspecified') ?></span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Residential Address</span>
                                                <span class="fw-medium text-dark"><i class="bi bi-geo-alt text-secondary me-1"></i><?= h($patient['address']) ?>, Brgy. <?= h($patient['barangay']) ?></span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Contact Number</span>
                                                <span class="font-monospace fw-semibold text-primary"><i class="bi bi-telephone text-secondary me-1"></i><?= h($patient['contact_no'] ?? 'None registered') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">PhilHealth Status</span>
                                                <span class="badge bg-light text-dark border"><?= h($patient['phic_status'] ?? 'Non-Member') ?></span>
                                                <?php if (!empty($patient['phic_type'])): ?>
                                                    <span class="text-muted d-block" style="font-size: 0.7rem;"><?= h($patient['phic_type']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">PhilHealth PIN</span>
                                                <span class="font-monospace text-dark"><?= h($patient['philhealth_no'] ?? 'Unregistered') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Educational Attainment</span>
                                                <span class="text-dark"><?= h($patient['education_attainment'] ?? 'Unspecified') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Occupation</span>
                                                <span class="text-dark"><?= h($patient['occupation'] ?? 'Unspecified') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Emergency & Family Contacts Card -->
                            <div class="col-12 col-lg-6">
                                <div class="card border rounded-3 h-100 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-person-exclamation text-danger me-2"></i>Emergency & Family Information
                                        </h5>
                                        <?php if (!empty($patient['family_no'])): ?>
                                            <span class="badge bg-light text-dark border">Fam # <?= h($patient['family_no']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Emergency Contact</span>
                                                <span class="fw-semibold text-dark"><?= h($patient['emergency_name'] ?? 'None registered') ?></span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Relationship</span>
                                                <span class="text-dark"><?= h($patient['emergency_relationship'] ?? 'N/A') ?></span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Emergency Phone</span>
                                                <span class="font-monospace fw-semibold text-danger"><i class="bi bi-telephone-fill text-danger me-1"></i><?= h($patient['emergency_no'] ?? 'None provided') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Spouse / Partner Name</span>
                                                <span class="text-dark"><?= !empty($patient['spouse_name']) ? h($patient['spouse_name']) : '<span class="text-muted">None on record</span>' ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Mother\'s Maiden Name</span>
                                                <span class="text-dark"><?= !empty($patient['mother_name']) ? h($patient['mother_name']) : '<span class="text-muted">None on record</span>' ?></span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Household Code</span>
                                                <?php if (!empty($patient['family_no'])): ?>
                                                    <a href="<?= url('/patients?search=' . urlencode($patient['family_no'])) ?>" class="text-decoration-none fw-medium text-primary">
                                                        <i class="bi bi-house-door me-1"></i>Family Group # <?= h($patient['family_no']) ?> (Click to view members)
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No household number assigned</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Latest Vital Signs Snapshot Card (Full Width) -->
                            <div class="col-12">
                                <div class="card border rounded-3 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-heart-pulse text-danger me-2"></i>Latest Vital Signs Snapshot
                                        </h5>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                                            <i class="bi bi-plus-lg me-1"></i> Record Vitals
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <?php if ($latestVitals): ?>
                                            <div class="row g-2 text-center mb-2">
                                                <!-- BP -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Blood Pressure</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['bp_systolic'] ?? '--') ?>/<?= h($latestVitals['bp_diastolic'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">mmHg</span>
                                                    </div>
                                                </div>

                                                <!-- Pulse -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Heart Rate</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['heart_rate'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">bpm</span>
                                                    </div>
                                                </div>

                                                <!-- Temp -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Temperature</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['temperature'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">°C</span>
                                                    </div>
                                                </div>

                                                <!-- Weight / Height -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Weight / Height</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['weight'] ?? '--') ?> kg</span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;"><?= h($latestVitals['height'] ?? '--') ?> cm</span>
                                                    </div>
                                                </div>

                                                <!-- BMI -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">BMI</span>
                                                        <span class="fw-bold fs-6 text-primary"><?= h($latestVitals['bmi'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">
                                                            <?php 
                                                             $bmi = (float)($latestVitals['bmi'] ?? 0);
                                                            if ($bmi > 0 && $bmi < 18.5) echo '<span class="text-warning">Underweight</span>';
                                                            elseif ($bmi >= 18.5 && $bmi <= 24.9) echo '<span class="text-success">Normal</span>';
                                                            elseif ($bmi >= 25.0 && $bmi <= 29.9) echo '<span class="text-warning">Overweight</span>';
                                                            elseif ($bmi >= 30.0) echo '<span class="text-danger">Obese</span>';
                                                            else echo '--';
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Waist -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Waistline</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['waist_circumference'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">cm</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-muted small d-flex justify-content-between pt-1" style="font-size: 0.75rem;">
                                                <span>Recorded: <strong><?= date('M d, Y h:i A', strtotime($latestVitals['recorded_at'])) ?></strong></span>
                                                <span>Recorded By: <?= h($latestVitals['recorder_name'] ?? 'Clinician') ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-3 text-muted">
                                                <p class="mb-2 small">No vital signs recorded yet.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Household Members Card -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 h-100 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-people-fill text-primary me-2"></i>Household Members
                                        </h5>
                                        <?php if (!empty($patient['family_no'])): ?>
                                            <span class="badge bg-light text-dark border small">Family # <?= h($patient['family_no']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <?php if (!empty($familyMembers)): ?>
                                            <div class="list-group list-group-flush">
                                                <?php foreach ($familyMembers as $member): ?>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                        <div>
                                                            <i class="bi bi-person me-1 text-primary"></i>
                                                            <strong><?= h($member['last_name']) ?>, <?= h($member['first_name']) ?> <?= h($member['suffix'] ?? '') ?></strong>
                                                            <span class="text-muted ms-1">(<?= h($member['age']) ?> yrs / <?= h($member['sex']) ?>)</span>
                                                        </div>
                                                        <a href="<?= url('/patients/' . $member['id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                                            View Profile <i class="bi bi-arrow-right"></i>
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted mb-0 py-2 text-center">
                                                No other relatives registered under this household code.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. IHP Summary Card -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 h-100 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-clipboard2-check text-primary me-2"></i>IHP Health Summary
                                        </h5>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="editIhpFromOverview();">
                                            <i class="bi bi-pencil me-1"></i> Edit IHP
                                        </button>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <?php if ($medicalHistory): ?>
                                            <div class="mb-2">
                                                <strong>Past Illnesses:</strong> 
                                                <?php if (!empty($medicalHistory['past_medical_history'])): ?>
                                                    <span class="text-dark"><?= is_array($medicalHistory['past_medical_history']) ? implode(', ', array_map(function($k, $v) { return is_string($k) ? "$k: $v" : $v; }, array_keys($medicalHistory['past_medical_history']), $medicalHistory['past_medical_history'])) : h($medicalHistory['past_medical_history']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">None declared.</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Social Habits:</strong> 
                                                <span class="text-dark">Smoking: <?= h($medicalHistory['smoking_status'] ?? 'Never') ?> &bull; Alcohol: <?= h($medicalHistory['alcohol_status'] ?? 'Never') ?></span>
                                            </div>
                                            <div>
                                                <strong>Family Heredity:</strong> 
                                                <?php if (!empty($medicalHistory['family_history'])): ?>
                                                    <span class="text-dark"><?= is_array($medicalHistory['family_history']) ? implode(', ', $medicalHistory['family_history']) : h($medicalHistory['family_history']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">None declared.</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted mb-0 py-2 text-center">
                                                No IHP medical history recorded. <a href="javascript:void(0)" onclick="editIhpFromOverview();">Complete IHP Form</a>.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- 6. Metadata Footer -->
                            <div class="col-12 text-center text-muted pt-2" style="font-size: 0.75rem;">
                                <span>Patient chart registered on <?= date('M d, Y \a\t h:i A', strtotime($patient['created_at'])) ?> <?= !empty($patient['creator_name']) ? 'by ' . h($patient['creator_name']) : '' ?></span>
                            </div>

                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB 2: ANNEX A1 INDIVIDUAL HEALTH PROFILE (IHP) FORM
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-ihp" role="tabpanel">
                        <?php 
                        $pmhSaved = $medicalHistory['past_medical_history'] ?? [];
                        $familySaved = $medicalHistory['family_history'] ?? [];
                        $surgicalSaved = $medicalHistory['surgical_history'] ?? [];

                        // Ensure JSON string arrays are decoded if they came as raw strings
                        if (is_string($pmhSaved)) {
                            $decoded = json_decode($pmhSaved, true);
                            $pmhSaved = is_array($decoded) ? $decoded : ($pmhSaved === '[]' || $pmhSaved === '{}' ? [] : [$pmhSaved]);
                        }
                        if (is_string($familySaved)) {
                            $decoded = json_decode($familySaved, true);
                            $familySaved = is_array($decoded) ? $decoded : ($familySaved === '[]' || $familySaved === '{}' ? [] : [$familySaved]);
                        }
                        if (is_string($surgicalSaved)) {
                            $decoded = json_decode($surgicalSaved, true);
                            $surgicalSaved = is_array($decoded) ? $decoded : ($surgicalSaved === '[]' || $surgicalSaved === '{}' ? [] : [$surgicalSaved]);
                        }

                        // Build display list for Past Medical History
                        $pmhDisplay = [];
                        if (!empty($pmhSaved) && is_array($pmhSaved)) {
                            foreach ($pmhSaved as $k => $v) {
                                if (is_string($k) && !is_numeric($k) && trim($k) !== '' && trim($k) !== '[]') {
                                    $pmhDisplay[] = ['condition' => trim($k), 'detail' => is_string($v) ? trim($v) : ''];
                                } elseif (!empty($v) && is_string($v) && trim($v) !== '' && trim($v) !== '[]') {
                                    $pmhDisplay[] = ['condition' => trim($v), 'detail' => ''];
                                }
                            }
                        }

                        // Build display list for Family History
                        $familyDisplay = [];
                        if (!empty($familySaved) && is_array($familySaved)) {
                            foreach ($familySaved as $k => $v) {
                                if (is_string($k) && !is_numeric($k) && trim($k) !== '' && trim($k) !== '[]') {
                                    $familyDisplay[] = trim($k) . (!empty($v) && $v !== $k ? ': ' . trim($v) : '');
                                } elseif (!empty($v) && is_string($v) && trim($v) !== '' && trim($v) !== '[]') {
                                    $familyDisplay[] = trim($v);
                                }
                            }
                        }

                        // Build display list for Surgical History
                        $surgicalDisplay = [];
                        if (!empty($surgicalSaved) && is_array($surgicalSaved)) {
                            foreach ($surgicalSaved as $surg) {
                                if (is_array($surg) && (!empty($surg['operation']) || !empty($surg['date']))) {
                                    $surgicalDisplay[] = $surg;
                                } elseif (is_string($surg) && trim($surg) !== '' && trim($surg) !== '[]') {
                                    $surgicalDisplay[] = ['operation' => trim($surg), 'date' => '', 'hospital' => ''];
                                }
                            }
                        }

                        $hasPmhData = !empty($pmhDisplay);
                        $hasSurgicalData = !empty($surgicalDisplay);
                        $hasFamilyData = !empty($familyDisplay);
                        $hasLifestyleData = !empty($medicalHistory) && (
                            ($medicalHistory['smoking_status'] ?? 'Never') !== 'Never' ||
                            ($medicalHistory['alcohol_status'] ?? 'Never') !== 'Never' ||
                            !empty($medicalHistory['illicit_drugs']) ||
                            !empty($medicalHistory['smoking_pack_years']) ||
                            !empty($medicalHistory['alcohol_bottles_per_day'])
                        );
                        $hasReproductiveData = $isFemale && !empty($medicalHistory) && (
                            !empty($medicalHistory['menarche_age']) ||
                            !empty($medicalHistory['sexual_onset_age']) ||
                            !empty($medicalHistory['lmp']) ||
                            !empty($medicalHistory['period_duration_days']) ||
                            !empty($medicalHistory['cycle_interval_days']) ||
                            !empty($medicalHistory['pads_per_day']) ||
                            !empty($medicalHistory['is_menopausal']) ||
                            !empty($medicalHistory['birth_control_method'])
                        );

                        $hasAnyIhpRecord = !empty($medicalHistory) && (
                            $hasPmhData ||
                            $hasSurgicalData ||
                            $hasFamilyData ||
                            $hasLifestyleData ||
                            $hasReproductiveData ||
                            !empty($medicalHistory['updated_at'])
                        );
                        ?>

                        <!-- -----------------------------------------------------------
                           MODE A: READ-ONLY VIEW (DEFAULT)
                           ----------------------------------------------------------- -->
                        <div id="ihp-view-mode">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3 pb-2 border-bottom">
                                <div>
                                    <h4 class="h6 mb-0 fw-bold text-dark">PhilHealth Annex A1: Individual Health Profile (IHP)</h4>
                                    <span class="text-muted small">
                                        <?php if (!empty($medicalHistory['updated_at'])): ?>
                                            Last updated on <?= date('M d, Y \a\t h:i A', strtotime($medicalHistory['updated_at'])) ?>
                                            <?= !empty($medicalHistory['updater_name']) ? 'by ' . h($medicalHistory['updater_name']) : '' ?>
                                        <?php else: ?>
                                            Baseline patient medical history and PhilHealth Annex A1 profile
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-medium" onclick="enterIhpEditMode()">
                                    Edit IHP Record
                                </button>
                            </div>

                            <?php if ($hasAnyIhpRecord): ?>
                                <div class="row g-3">
                                    <!-- 1. Past Medical Illnesses Card -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                1. Past Medical Illnesses
                                            </h5>
                                            <?php if ($hasPmhData): ?>
                                                <div class="d-flex flex-wrap gap-2 pt-1">
                                                    <?php foreach ($pmhDisplay as $item): ?>
                                                        <?php 
                                                        $cond = $item['condition'];
                                                        $det = $item['detail'];
                                                        ?>
                                                        <?php if (stripos($cond, 'Allergy') !== false || stripos($det, 'Allergy') !== false): ?>
                                                            <span class="badge bg-danger text-white px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ': ' . h($det) : '' ?>
                                                            </span>
                                                        <?php elseif (stripos($cond, 'Hypertension') !== false): ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ' (' . h($det) . ')' : '' ?>
                                                            </span>
                                                        <?php elseif (stripos($cond, 'Cancer') !== false): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ' (' . h($det) . ')' : '' ?>
                                                            </span>
                                                        <?php elseif (stripos($cond, 'PTB') !== false || stripos($cond, 'Tuberculosis') !== false): ?>
                                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ' (' . h($det) . ')' : '' ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-dark border px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ': ' . h($det) : '' ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted small fst-italic mb-0 py-1">
                                                    No chronic illnesses or allergies recorded.
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 2. Surgical History Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                2. Surgical Operations & Hospitalization
                                            </h5>
                                            <?php if ($hasSurgicalData): ?>
                                                <ul class="list-group list-group-flush small">
                                                    <?php foreach ($surgicalDisplay as $surg): ?>
                                                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-bottom-subtle">
                                                            <div>
                                                                <span class="fw-semibold text-dark"><?= h($surg['operation'] ?? 'Surgical Procedure') ?></span>
                                                                <?php if (!empty($surg['hospital'])): ?>
                                                                    <span class="text-muted small ms-1">(<?= h($surg['hospital']) ?>)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <span class="badge bg-light text-secondary border"><?= !empty($surg['date']) ? h($surg['date']) : 'Year N/A' ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="text-muted small fst-italic mb-0 py-2">
                                                    No prior surgeries or hospitalizations declared.
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 3. Family Hereditary Diseases Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                3. Family Hereditary Diseases
                                            </h5>
                                            <?php if ($hasFamilyData): ?>
                                                <div class="d-flex flex-wrap gap-2 pt-1">
                                                    <?php foreach ($familyDisplay as $fam): ?>
                                                        <span class="badge bg-light text-dark border px-2.5 py-1.5 fs-7">
                                                            <?= h($fam) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted small fst-italic mb-0 py-2">
                                                    No family hereditary conditions declared.
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 4. Personal & Social Lifestyle Card -->
                                    <div class="col-12 <?= $isFemale ? 'col-md-6' : 'col-12' ?>">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                4. Personal & Social Lifestyle
                                            </h5>
                                            <div class="row g-2 small pt-1">
                                                <div class="col-12 col-sm-6">
                                                    <div class="p-2 rounded bg-light border">
                                                        <span class="text-muted d-block small mb-1">Smoking Status:</span>
                                                        <?php 
                                                        $smk = $medicalHistory['smoking_status'] ?? 'Never';
                                                        if ($smk === 'Yes'): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Active Smoker</span>
                                                            <?php if (!empty($medicalHistory['smoking_pack_years'])): ?>
                                                                <span class="text-dark small ms-1">(<?= h($medicalHistory['smoking_pack_years']) ?> pk-yrs)</span>
                                                            <?php endif; ?>
                                                        <?php elseif ($smk === 'Quit'): ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border">Quit Smoking</span>
                                                            <?php if (!empty($medicalHistory['smoking_pack_years'])): ?>
                                                                <span class="text-dark small ms-1">(<?= h($medicalHistory['smoking_pack_years']) ?> pk-yrs)</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-dark">Never Smoked</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <div class="p-2 rounded bg-light border">
                                                        <span class="text-muted d-block small mb-1">Alcohol Drinking:</span>
                                                        <?php 
                                                        $alc = $medicalHistory['alcohol_status'] ?? 'Never';
                                                        if ($alc === 'Yes'): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Regular / Occasional</span>
                                                            <?php if (!empty($medicalHistory['alcohol_bottles_per_day'])): ?>
                                                                <span class="text-dark small ms-1">(<?= h($medicalHistory['alcohol_bottles_per_day']) ?> btls/day)</span>
                                                            <?php endif; ?>
                                                        <?php elseif ($alc === 'Quit'): ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border">Quit Drinking</span>
                                                        <?php else: ?>
                                                            <span class="text-dark">Non-Drinker</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center">
                                                        <span class="text-muted small">Illicit Drug Use:</span>
                                                        <?php if (!empty($medicalHistory['illicit_drugs'])): ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Reported History</span>
                                                        <?php else: ?>
                                                            <span class="text-muted">None Declared</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 5. Female Reproductive History Card (if Female) -->
                                    <?php if ($isFemale): ?>
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 shadow-xs h-100">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    5. Female Menstrual & Reproductive History
                                                </h5>
                                                <div class="row g-2 small pt-1">
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Menarche Age:</span>
                                                        <span class="fw-semibold text-dark"><?= !empty($medicalHistory['menarche_age']) ? h($medicalHistory['menarche_age']) . ' yrs old' : '<span class="text-muted">Unspecified</span>' ?></span>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Sexual Onset Age:</span>
                                                        <span class="fw-semibold text-dark"><?= !empty($medicalHistory['sexual_onset_age']) ? h($medicalHistory['sexual_onset_age']) . ' yrs old' : '<span class="text-muted">Unspecified</span>' ?></span>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Last Menstrual Period (LMP):</span>
                                                        <span class="fw-bold text-primary"><?= !empty($medicalHistory['lmp']) ? date('M d, Y', strtotime($medicalHistory['lmp'])) : '<span class="text-muted fw-normal">None recorded</span>' ?></span>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Menopausal:</span>
                                                        <?php if (!empty($medicalHistory['is_menopausal'])): ?>
                                                            <span class="badge bg-warning-subtle text-dark border">Yes <?= !empty($medicalHistory['menopause_age']) ? '(Age ' . h($medicalHistory['menopause_age']) . ')' : '' ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border">No</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-4">
                                                        <span class="text-muted d-block">Duration:</span>
                                                        <span class="fw-medium text-dark"><?= !empty($medicalHistory['period_duration_days']) ? h($medicalHistory['period_duration_days']) . ' days' : '—' ?></span>
                                                    </div>
                                                    <div class="col-4">
                                                        <span class="text-muted d-block">Cycle:</span>
                                                        <span class="fw-medium text-dark"><?= !empty($medicalHistory['cycle_interval_days']) ? h($medicalHistory['cycle_interval_days']) . ' days' : '—' ?></span>
                                                    </div>
                                                    <div class="col-4">
                                                        <span class="text-muted d-block">Pads/Day:</span>
                                                        <span class="fw-medium text-dark"><?= !empty($medicalHistory['pads_per_day']) ? h($medicalHistory['pads_per_day']) : '—' ?></span>
                                                    </div>
                                                    <div class="col-12 border-top pt-2 mt-1">
                                                        <span class="text-muted d-block">Family Planning Method:</span>
                                                        <span class="fw-semibold text-dark"><?= !empty($medicalHistory['birth_control_method']) ? h($medicalHistory['birth_control_method']) : '<span class="text-muted fw-normal">None declared</span>' ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Clean Empty State Card -->
                                <div class="card border border-dashed rounded-3 p-4 text-center bg-light shadow-xs my-2">
                                    <h5 class="h6 fw-bold text-dark mb-1">No Individual Health Profile (IHP) On File</h5>
                                    <p class="text-muted small mb-3 mx-auto" style="max-width: 520px;">
                                        PhilHealth Annex A1 baseline medical history has not yet been recorded for this patient. Click below to record past chronic illnesses, surgical operations, family heredity, social history, and reproductive health.
                                    </p>
                                    <div>
                                        <button type="button" class="btn btn-primary btn-sm px-4 fw-medium" onclick="enterIhpEditMode()">
                                            Record IHP Medical History
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- -----------------------------------------------------------
                           MODE B: EDIT FORM (INITIALLY HIDDEN)
                           ----------------------------------------------------------- -->
                        <div id="ihp-edit-mode" class="d-none">
                            <form action="<?= url('/patients/' . $patient['id'] . '/medical-history') ?>" method="POST" id="ihpForm">
                                <?= csrf_field() ?>

                                <div class="mb-3 pb-2 border-bottom">
                                    <h4 class="h6 mb-0 fw-bold text-dark">PhilHealth Annex A1: Individual Health Profile (IHP)</h4>
                                    <span class="text-muted small">Update past chronic illnesses, surgeries, family heredity, social history, and reproductive health.</span>
                                </div>

                                <div class="row g-3">
                                    <!-- 1. Past Medical History Checklist -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                1. Past Medical Illnesses
                                            </h5>
                                            <div class="row g-2 small mb-3">
                                                <?php 
                                                $conditions = [
                                                    'Allergy' => 'Allergy',
                                                    'Asthma' => 'Asthma',
                                                    'Cancer' => 'Cancer',
                                                    'Coronary Artery Disease' => 'Coronary Artery Disease',
                                                    'Diabetes Mellitus' => 'Diabetes Mellitus',
                                                    'Emphysema / COPD' => 'Emphysema / COPD',
                                                    'Epilepsy / Seizure' => 'Epilepsy / Seizure',
                                                    'Hepatitis' => 'Hepatitis',
                                                    'Hyperlipidemia' => 'Hyperlipidemia',
                                                    'Hypertension' => 'Hypertension',
                                                    'Peptic Ulcer Disease' => 'Peptic Ulcer Disease',
                                                    'Pneumonia' => 'Pneumonia',
                                                    'Pulmonary Tuberculosis (PTB)' => 'Pulmonary Tuberculosis (PTB)',
                                                    'Thyroid Disease' => 'Thyroid Disease',
                                                    'Urinary Tract Infection' => 'Urinary Tract Infection',
                                                    'Kidney Disease' => 'Kidney Disease',
                                                    'Mental Disorder' => 'Mental Disorder'
                                                ];
                                                foreach ($conditions as $k => $label): 
                                                    $checked = is_array($pmhSaved) && (isset($pmhSaved[$k]) || in_array($k, $pmhSaved));
                                                ?>
                                                    <div class="col-6 col-md-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="<?= $k ?>" id="pmh_<?= md5($k) ?>" <?= $checked ? 'checked' : '' ?>>
                                                            <label class="form-check-label text-secondary" for="pmh_<?= md5($k) ?>"><?= $label ?></label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <!-- Specific Details Fields -->
                                            <div class="row g-2 small border-top pt-2">
                                                <div class="col-12 col-sm-6 col-md-3">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Allergy Specifics</label>
                                                    <input type="text" name="allergy_specifics" class="form-control form-control-sm" placeholder="e.g. Penicillin, Seafood" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Allergy'] ?? '') : '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-6 col-md-3">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Hypertension (Highest BP)</label>
                                                    <input type="text" name="hypertension_highest_bp" class="form-control form-control-sm" placeholder="e.g. 150/90" value="<?= h(is_array($pmhSaved) ? str_replace('Highest BP: ', '', $pmhSaved['Hypertension'] ?? '') : '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-6 col-md-3">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Cancer (Organ/Type)</label>
                                                    <input type="text" name="cancer_organ" class="form-control form-control-sm" placeholder="e.g. Breast, Colon" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Cancer'] ?? '') : '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-6 col-md-3">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">PTB Category / Details</label>
                                                    <input type="text" name="ptb_details" class="form-control form-control-sm" placeholder="e.g. Cat 1 Completed" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['PTB'] ?? '') : '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Surgical History & 3. Family Heredity -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                2. Surgical Operations & Hospitalization
                                            </h5>
                                            <div class="row g-2 small">
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 1 Name</label>
                                                    <input type="text" name="operation_1_name" class="form-control form-control-sm" placeholder="e.g. Appendectomy" value="<?= h($surgicalSaved[0]['operation'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 1 Date</label>
                                                    <input type="text" name="operation_1_date" class="form-control form-control-sm" placeholder="YYYY or Date" value="<?= h($surgicalSaved[0]['date'] ?? '') ?>">
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 2 Name</label>
                                                    <input type="text" name="operation_2_name" class="form-control form-control-sm" placeholder="e.g. CS Delivery" value="<?= h($surgicalSaved[1]['operation'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 2 Date</label>
                                                    <input type="text" name="operation_2_date" class="form-control form-control-sm" placeholder="YYYY or Date" value="<?= h($surgicalSaved[1]['date'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                3. Family Hereditary Diseases
                                            </h5>
                                            <div class="row g-2 small">
                                                <?php 
                                                $familyItems = ['Hypertension', 'Diabetes Mellitus', 'Cancer', 'Asthma', 'Kidney Disease', 'Coronary Artery Disease', 'Stroke', 'Mental Disorder', 'Bleeding Disorder'];
                                                foreach ($familyItems as $item):
                                                    $famChecked = is_array($familySaved) && (isset($familySaved[$item]) || in_array($item, $familySaved));
                                                ?>
                                                    <div class="col-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="family_history[]" value="<?= $item ?>" id="fam_<?= md5($item) ?>" <?= $famChecked ? 'checked' : '' ?>>
                                                            <label class="form-check-label text-secondary" for="fam_<?= md5($item) ?>"><?= $item ?></label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4. Personal & Social History -->
                                    <div class="col-12 <?= $isFemale ? 'col-md-6' : 'col-12' ?>">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                4. Personal & Social Lifestyle
                                            </h5>
                                            <div class="row g-2 small">
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Smoking Status</label>
                                                    <select name="smoking_status" class="form-select form-select-sm">
                                                        <option value="Never" <?= ($medicalHistory['smoking_status'] ?? 'Never') === 'Never' ? 'selected' : '' ?>>Never</option>
                                                        <option value="Yes" <?= ($medicalHistory['smoking_status'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes (Active)</option>
                                                        <option value="Quit" <?= ($medicalHistory['smoking_status'] ?? '') === 'Quit' ? 'selected' : '' ?>>Quit</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Smoking Pack Years</label>
                                                    <input type="number" step="0.1" name="smoking_pack_years" class="form-control form-control-sm" placeholder="e.g. 5.0" value="<?= h($medicalHistory['smoking_pack_years'] ?? '') ?>">
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Alcohol Drinking Status</label>
                                                    <select name="alcohol_status" class="form-select form-select-sm">
                                                        <option value="Never" <?= ($medicalHistory['alcohol_status'] ?? 'Never') === 'Never' ? 'selected' : '' ?>>Never</option>
                                                        <option value="Yes" <?= ($medicalHistory['alcohol_status'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes (Regular/Occasional)</option>
                                                        <option value="Quit" <?= ($medicalHistory['alcohol_status'] ?? '') === 'Quit' ? 'selected' : '' ?>>Quit</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Alcohol Bottles / Day</label>
                                                    <input type="number" step="0.1" name="alcohol_bottles_per_day" class="form-control form-control-sm" placeholder="e.g. 2.0" value="<?= h($medicalHistory['alcohol_bottles_per_day'] ?? '') ?>">
                                                </div>

                                                <div class="col-12">
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="illicit_drugs" value="1" id="illicit_drugs" <?= !empty($medicalHistory['illicit_drugs']) ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-secondary fw-semibold small" for="illicit_drugs">History of Illicit Drug Use</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 5. Female Reproductive History (if Female) -->
                                    <?php if ($isFemale): ?>
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    5. Female Menstrual & Reproductive History
                                                </h5>
                                                <div class="row g-2 small">
                                                    <div class="col-6 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Menarche Age</label>
                                                        <input type="number" name="menarche_age" class="form-control form-control-sm" placeholder="e.g. 13" min="8" max="25" value="<?= h($medicalHistory['menarche_age'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Sexual Onset</label>
                                                        <input type="number" name="sexual_onset_age" class="form-control form-control-sm" placeholder="e.g. 20" min="10" max="60" value="<?= h($medicalHistory['sexual_onset_age'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-12 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">LMP Date</label>
                                                        <input type="text" name="lmp" class="form-control form-control-sm dob-picker" placeholder="YYYY-MM-DD" value="<?= h($medicalHistory['lmp'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-6 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Duration (days)</label>
                                                        <input type="number" name="period_duration_days" class="form-control form-control-sm" placeholder="e.g. 5" min="1" max="15" value="<?= h($medicalHistory['period_duration_days'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Cycle (days)</label>
                                                        <input type="number" name="cycle_interval_days" class="form-control form-control-sm" placeholder="e.g. 28" min="15" max="60" value="<?= h($medicalHistory['cycle_interval_days'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-12 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Pads / Day</label>
                                                        <input type="number" name="pads_per_day" class="form-control form-control-sm" placeholder="e.g. 3" min="1" max="20" value="<?= h($medicalHistory['pads_per_day'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-12 col-sm-6">
                                                        <div class="form-check mt-1">
                                                            <input class="form-check-input" type="checkbox" name="is_menopausal" value="1" id="is_menopausal" <?= !empty($medicalHistory['is_menopausal']) ? 'checked' : '' ?>>
                                                            <label class="form-check-label text-secondary fw-semibold small" for="is_menopausal">Menopausal</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-sm-6">
                                                        <input type="number" name="menopause_age" class="form-control form-control-sm" placeholder="Menopause Age (e.g. 50)" min="30" max="70" value="<?= h($medicalHistory['menopause_age'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Family Planning Method in Use</label>
                                                        <input type="text" name="birth_control_method" class="form-control form-control-sm" placeholder="e.g. Pills, BTL, IUD, Injectable, Condom, None" value="<?= h($medicalHistory['birth_control_method'] ?? '') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 me-2" onclick="cancelIhpEditMode()">
                                        Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-xs">
                                        Save IHP Record
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB 3: CONSULTATIONS (SOAP)
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-consultations" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="h6 fw-bold text-dark mb-0">Consultations History (SOAP Notes)</h5>
                            <a href="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> New Consultation
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="consultationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Date</th>
                                        <th>Clinician</th>
                                        <th>Assessment / Diagnosis</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($consultationsHistory)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-clipboard2-x fs-3 d-block mb-2 text-secondary"></i>
                                                No consultation records exist for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                            $curUserId = (int)($_SESSION['user_id'] ?? 0);
                                            $curRole = $_SESSION['role'] ?? 'staff';
                                        ?>
                                        <?php foreach ($consultationsHistory as $c): 
                                            $canEditRow = ($curRole === 'admin' || $curUserId === (int)($c['created_by'] ?? 0) || $curUserId === (int)($c['consulted_by'] ?? 0)) && ($c['status'] !== 'Cancelled');
                                            
                                            $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                            if ($c['status'] === 'Cancelled') {
                                                $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                            } elseif ($c['status'] === 'Open') {
                                                $badgeClass = 'bg-secondary-subtle text-secondary border';
                                            }
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y h:i A', strtotime($c['consulted_at'])) ?></td>
                                                <td><?= h($c['clinician_name']) ?></td>
                                                <td class="text-start"><?= h(mb_strimwidth($c['assessment'], 0, 50, '...')) ?></td>
                                                <td><span class="badge <?= $badgeClass ?>"><?= h($c['status']) ?></span></td>
                                                <td class="pe-3 text-end">
                                                    <?php if ($canEditRow): ?>
                                                        <a href="<?= url('/consultations/' . $c['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary py-1 px-2 me-1" title="Edit Consultation">
                                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                                        </a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 view-consultation-btn" data-consultation-id="<?= $c['id'] ?>">
                                                        <i class="bi bi-eye me-1"></i> View SOAP
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB 4: VITAL SIGNS HISTORY
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-vitals" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="h6 fw-bold text-dark mb-0">Vital Signs Log</h5>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                                <i class="bi bi-plus-lg me-1"></i> Record Vitals
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="vitalsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Recorded Date</th>
                                        <th>BP (mmHg)</th>
                                        <th>Pulse (bpm)</th>
                                        <th>Temp (°C)</th>
                                        <th>Resp (cpm)</th>
                                        <th>SpO2</th>
                                        <th>Wt / Ht</th>
                                        <th>BMI</th>
                                        <th>Waist (cm)</th>
                                        <th class="pe-3">Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($vitalsHistory)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-5 text-muted">
                                                <i class="bi bi-activity fs-3 d-block mb-2 text-secondary"></i>
                                                No vital signs records exist for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($vitalsHistory as $v): ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y h:i A', strtotime($v['recorded_at'])) ?></td>
                                                <td class="fw-bold font-monospace"><?= h($v['bp_systolic'] ?? '--') ?>/<?= h($v['bp_diastolic'] ?? '--') ?></td>
                                                <td><?= h($v['heart_rate'] ?? '--') ?></td>
                                                <td><?= h($v['temperature'] ?? '--') ?></td>
                                                <td><?= h($v['respiratory_rate'] ?? '--') ?></td>
                                                <td><?= h($v['oxygen_saturation'] ?? '--') ?>%</td>
                                                <td><?= h($v['weight'] ?? '--') ?>kg / <?= h($v['height'] ?? '--') ?>cm</td>
                                                <td><span class="badge bg-light text-dark border"><?= h($v['bmi'] ?? '--') ?></span></td>
                                                <td><?= h($v['waist_circumference'] ?? '--') ?></td>
                                                <td class="pe-3 text-muted"><?= h($v['recorder_name'] ?? 'Clinician') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB 5: UNIVERSAL IMMUNIZATIONS
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-immunizations" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="h6 fw-bold text-dark mb-0">Universal Immunization Records</h5>
                                <span class="text-muted small">Tracks vaccines administered across all life stages (EPI Routine Infant, HPV, COVID-19, Flu, Pneumococcal).</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recordImmunizationModal">
                                <i class="bi bi-plus-lg me-1"></i> Record Vaccine Dose
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="immunizationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Vaccine Name</th>
                                        <th>Dose #</th>
                                        <th>Administered Date</th>
                                        <th>Remarks / Program</th>
                                        <th class="pe-3">Vaccinator</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($patientImmunizations)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-shield-slash fs-3 d-block mb-2 text-secondary"></i>
                                                No immunization records recorded for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($patientImmunizations as $imm): ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-bold text-primary">
                                                    <i class="bi bi-shield-check me-1 text-success"></i><?= h($imm['vaccine_name']) ?>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">Dose <?= h($imm['dose_number']) ?></span></td>
                                                <td class="fw-medium text-dark"><?= date('M d, Y', strtotime($imm['administered_date'])) ?></td>
                                                <td class="text-muted small"><?= h($imm['remarks'] ?? 'Routine') ?></td>
                                                <td class="pe-3 text-muted"><?= h($imm['vaccinator_name'] ?? 'Healthcare Staff') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB 6: MATERNAL / PRENATAL CARE WORKSTATION (If Female)
                       ============================================================== -->
                    <?php if ($isFemale): ?>
                        <div class="tab-pane fade" id="tab-prenatal" role="tabpanel">
                            
                            <!-- 1. Active Pregnancy Episode Header & Cards -->
                            <?php if ($activePrenatal): ?>
                                <div class="card border-pink rounded-3 p-4 mb-4 bg-light-subtle shadow-xs">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 pb-2 border-bottom">
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <h5 class="h6 fw-bold text-pink mb-0">
                                                    <i class="bi bi-heart-pulse-fill me-2"></i>Active Pregnancy Episode (CHO I Record)
                                                </h5>
                                                <span class="badge bg-pink text-white">Gravida <?= $activePrenatal['gravida'] ?> Para <?= $activePrenatal['para'] ?></span>
                                                <?php if (!empty($activePrenatal['pre_eclampsia'])): ?>
                                                    <span class="badge bg-danger text-white"><i class="bi bi-shield-exclamation me-1"></i>Pre-Eclampsia Risk</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-muted small">Enrolled on <?= date('M d, Y', strtotime($activePrenatal['created_at'])) ?> by <?= h($activePrenatal['creator_name'] ?? 'Clinician') ?></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPrenatalModal">
                                                <i class="bi bi-pencil me-1"></i> Edit Details
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#concludePrenatalModal">
                                                <i class="bi bi-check2-circle me-1"></i> Conclude Episode
                                            </button>
                                            <button type="button" class="btn btn-sm btn-pink text-white shadow-xs fw-semibold" data-bs-toggle="modal" data-bs-target="#addPrenatalVisitModal">
                                                <i class="bi bi-plus-lg me-1"></i> + Log Prenatal Visit
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Key Metrics Row -->
                                    <div class="row g-3 small">
                                        <!-- LMP -->
                                        <div class="col-6 col-sm-3">
                                            <div class="p-2 bg-white rounded border">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Last Menstrual Period (LMP)</span>
                                                <span class="fw-bold text-dark fs-7"><?= date('M d, Y', strtotime($activePrenatal['lmp'])) ?></span>
                                            </div>
                                        </div>

                                        <!-- EDC -->
                                        <div class="col-6 col-sm-3">
                                            <div class="p-2 bg-white rounded border">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Expected Date of Delivery (EDC)</span>
                                                <span class="fw-bold text-pink fs-7"><?= date('M d, Y', strtotime($activePrenatal['edc'])) ?></span>
                                            </div>
                                        </div>

                                        <!-- AOG -->
                                        <div class="col-6 col-sm-3">
                                            <div class="p-2 bg-white rounded border">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Age of Gestation (AOG)</span>
                                                <span class="fw-bold text-primary fs-7"><?= h($activePrenatal['calculated_aog']['formatted'] ?? '--') ?></span>
                                            </div>
                                        </div>

                                        <!-- GTPAL Details -->
                                        <div class="col-6 col-sm-3">
                                            <div class="p-2 bg-white rounded border">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Obstetric Score (GTPAL)</span>
                                                <span class="fw-bold text-dark fs-7">
                                                    G:<?= $activePrenatal['gravida'] ?> P:<?= $activePrenatal['para'] ?> (T:<?= $activePrenatal['term_births'] ?> P:<?= $activePrenatal['preterm_births'] ?> A:<?= $activePrenatal['abortions'] ?> L:<?= $activePrenatal['living_children'] ?>)
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Secondary Info Row -->
                                        <div class="col-12 col-sm-6">
                                            <span class="text-muted">Husband / Partner:</span>
                                            <strong class="text-dark ms-1"><?= h($activePrenatal['husband_name'] ?? 'Not specified') ?></strong>
                                        </div>
                                        <div class="col-12 col-sm-6 text-sm-end">
                                            <span class="text-muted">Family Planning Counselling:</span>
                                            <span class="badge <?= !empty($activePrenatal['fp_counselling']) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> ms-1">
                                                <?= !empty($activePrenatal['fp_counselling']) ? 'Counseled' : 'Pending' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Serial Follow-up Prenatal Visits Table -->
                                <div class="card border rounded-3 p-3 mb-4 shadow-xs">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-calendar2-check text-primary me-2"></i>Serial Prenatal Checkup Follow-up Visits
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPrenatalVisitModal">
                                            <i class="bi bi-plus-circle me-1"></i> Log Checkup Visit
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
                                                    <th class="pe-3">Attendant</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($prenatalVisits)): ?>
                                                    <tr>
                                                        <td colspan="10" class="text-center py-4 text-muted">
                                                            <i class="bi bi-heartbreak fs-4 d-block mb-1 text-secondary"></i>
                                                            No follow-up prenatal visits logged yet for this pregnancy.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
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
                                                            <td class="text-start small"><?= h($pv['remarks'] ?? '--') ?></td>
                                                            <td class="pe-3 text-muted"><?= h($pv['attendant_name'] ?? 'Midwife') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            <?php else: ?>
                                <!-- Empty State for Non-Active Episode -->
                                <div class="card border rounded-3 p-5 text-center mb-4 bg-light shadow-xs" style="border-style: dashed !important; border-width: 2px !important; border-color: #f3c2db !important;">
                                    <div class="d-inline-flex align-items-center justify-content-center bg-pink bg-opacity-10 text-pink rounded-circle mx-auto mb-3" style="width: 72px; height: 72px;">
                                        <i class="bi bi-heart-pulse fs-1"></i>
                                    </div>
                                    <h5 class="h6 fw-bold text-dark mb-1">No Active Maternal / Prenatal Care Episode</h5>
                                    <p class="text-muted small mb-3 mx-auto" style="max-width: 500px;">
                                        Enrolling this female patient into the CHO I Maternal Record tracks gestational progress (LMP, EDC, AOG), serial fetal heart tones, and delivery outcomes.
                                    </p>
                                    <div>
                                        <button type="button" class="btn btn-pink text-white px-4 py-2 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#startPrenatalModal">
                                            <i class="bi bi-plus-circle me-1"></i> Start Pregnancy Episode
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- 3. Past Obstetric History Matrix (G1–G5) -->
                            <div class="card border rounded-3 p-3 shadow-xs">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-clock-history text-primary me-2"></i>Past Obstetric Deliveries Matrix (G1, G2, G3...)
                                        </h6>
                                        <span class="text-muted small">Historical delivery outcomes, birth places, attendants, and maternal TT vaccination status.</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPastObstetricModal">
                                        <i class="bi bi-plus-circle me-1"></i> Add Past Delivery
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 text-center small" id="pastObstetricTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-start ps-3">Gravida #</th>
                                                <th>Delivery Type</th>
                                                <th>Infant Sex</th>
                                                <th>Place of Delivery</th>
                                                <th>Year</th>
                                                <th>Attendant</th>
                                                <th>Status</th>
                                                <th>Maternal TT</th>
                                                <th class="pe-3 text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($pastDeliveries)): ?>
                                                <tr>
                                                    <td colspan="9" class="text-center py-4 text-muted">
                                                        No previous delivery records logged for this patient.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($pastDeliveries as $poh): ?>
                                                    <tr>
                                                        <td class="text-start ps-3 fw-bold text-primary font-monospace">Gravida <?= h($poh['gravida_no']) ?></td>
                                                        <td><span class="badge bg-light text-dark border"><?= h($poh['delivery_type']) ?></span></td>
                                                        <td><?= h($poh['infant_sex']) ?></td>
                                                        <td><?= h($poh['place_of_delivery'] ?? '--') ?></td>
                                                        <td><?= h($poh['year_delivered'] ?? '--') ?></td>
                                                        <td><?= h($poh['attended_by'] ?? '--') ?></td>
                                                        <td>
                                                            <span class="badge <?= $poh['status'] === 'Alive' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                                                <?= h($poh['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= h($poh['tt_status'] ?? '--') ?></td>
                                                        <td class="pe-3 text-end">
                                                            <form action="<?= url('/past-obstetric/' . $poh['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this past delivery record?');">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                                                                <button type="submit" class="btn btn-xs btn-outline-danger border-0 py-1 px-2" title="Delete Entry">
                                                                    <i class="bi bi-trash"></i>
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
                    <?php endif; ?>

                    <!-- ==============================================================
                       TAB 7: WELL BABY & PEDIATRIC GROWTH MONITORING WORKSTATION (If Child)
                       ============================================================== -->
                    <?php if ($isChild): ?>
                        <div class="tab-pane fade" id="tab-wellbaby" role="tabpanel">
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
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#wellbabyBirthModal">
                                                <i class="bi bi-pencil me-1"></i> Edit Birth Record
                                            </button>
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
                                            </div>
                                        </div>

                                        <!-- Newborn Screening -->
                                        <div class="col-6 col-sm-3">
                                            <div class="p-2 bg-white rounded border">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Newborn Screening (NBS)</span>
                                                <span class="badge <?= !empty($wellbabyRecord['newborn_screening_done']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                                                    <?= !empty($wellbabyRecord['newborn_screening_done']) ? 'Done' : 'Pending' ?>
                                                </span>
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
                                                                        <input type="text" name="epi[<?= $v['key'] ?>]" class="form-control dob-picker bg-white" placeholder="Administered Date" value="<?= h($administeredDate) ?>">
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
                                                    <th>Age (Mos)</th>
                                                    <th>Weight (kg)</th>
                                                    <th>Height (cm)</th>
                                                    <th>Head Circ (cm)</th>
                                                    <th>Chest Circ (cm)</th>
                                                    <th>Temp (°C)</th>
                                                    <th>Feeding Method</th>
                                                    <th>Supplements</th>
                                                    <th>TCB / Milestones</th>
                                                    <th class="pe-3 text-end">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($growthLogs)): ?>
                                                    <tr>
                                                        <td colspan="11" class="text-center py-4 text-muted">
                                                            No periodic growth checkups logged yet for this infant.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($growthLogs as $gl): ?>
                                                        <tr>
                                                            <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y', strtotime($gl['log_date'])) ?></td>
                                                            <td class="font-monospace fw-bold text-primary"><?= h($gl['age_months']) ?> mos</td>
                                                            <td class="fw-bold text-dark"><?= h($gl['weight_kg']) ?> kg</td>
                                                            <td><?= h($gl['height_cm']) ?> cm</td>
                                                            <td><?= h($gl['head_circumference_cm'] ?? '--') ?></td>
                                                            <td><?= h($gl['chest_circumference_cm'] ?? '--') ?></td>
                                                            <td><?= h($gl['temperature'] ?? '--') ?></td>
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
                                                            <td class="text-start small"><?= h($gl['tcb_notes'] ?? '--') ?></td>
                                                            <td class="pe-3 text-end">
                                                                <form action="<?= url('/wellbaby/growth-log/' . $gl['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this growth visit entry?');">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                                                                    <button type="submit" class="btn btn-xs btn-outline-danger border-0 py-1 px-2" title="Delete Entry">
                                                                        <i class="bi bi-trash"></i>
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

                            <?php else: ?>
                                <!-- Empty State for Non-Initialized Well Baby Record -->
                                <div class="card border rounded-3 p-5 text-center mb-4 bg-light-subtle shadow-xs">
                                    <i class="bi bi-emoji-smile fs-1 d-block mb-2 text-success"></i>
                                    <h5 class="h6 fw-bold text-dark mb-1">No Well Baby Infant Health Record</h5>
                                    <p class="text-muted small mb-3">Initializing the Well Baby Record (CHO Santa Rosa / Brgy. Ibaba) registers birth circumstances, Newborn Screening (NBS) certification, mother link, and mandatory EPI childhood vaccines.</p>
                                    <div>
                                        <button type="button" class="btn btn-success text-white px-4 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#wellbabyBirthModal">
                                            <i class="bi bi-plus-circle me-1"></i> Initialize Well Baby Record
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- ==============================================================
                       TAB 8: APPOINTMENTS & QUEUE
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-appointments" role="tabpanel">
                        <div class="row g-3">
                            <!-- Appointments List -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 p-3 shadow-xs h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 text-dark small">Scheduled Appointments</h6>
                                        <a href="<?= url('/appointments/create?patient_id=' . $patient['id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                            <i class="bi bi-plus-circle me-1"></i> Book
                                        </a>
                                    </div>
                                    <?php if (empty($appointmentsHistory)): ?>
                                        <p class="text-muted small text-center py-3 mb-0">No upcoming appointments scheduled.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($appointmentsHistory as $a): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <strong><?= date('M d, Y', strtotime($a['appointment_date'])) ?></strong> at <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                                                        <span class="text-muted d-block" style="font-size: 0.75rem;"><?= h($a['purpose']) ?></span>
                                                    </div>
                                                    <span class="badge bg-light text-dark border"><?= h($a['status']) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Queue Logs -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 p-3 shadow-xs h-100">
                                    <h6 class="fw-bold mb-2 text-dark small">Daily Queue Visits</h6>
                                    <?php if (empty($queueHistory)): ?>
                                        <p class="text-muted small text-center py-3 mb-0">No daily queue visits recorded.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($queueHistory as $q): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <strong><?= h($q['queue_date']) ?></strong> &bull; Queue #<?= sprintf('%03d', $q['queue_no']) ?>
                                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Time In: <?= $q['time_in'] ? date('h:i A', strtotime($q['time_in'])) : '--' ?></span>
                                                    </div>
                                                    <span class="badge bg-primary-subtle text-primary border"><?= h($q['status']) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

<!-- ==========================================================================
   WELL BABY & PEDIATRIC MODALS (If Child)
   ========================================================================== -->
<?php if ($isChild): ?>

    <!-- 1. INITIALIZE / EDIT WELL BABY RECORD MODAL -->
    <div class="modal fade" id="wellbabyBirthModal" tabindex="-1" aria-labelledby="wellbabyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-success text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="wellbabyModalLabel">
                        <i class="bi bi-emoji-smile-fill me-2"></i><?= $wellbabyRecord ? 'Edit Well Baby Birth Record' : 'Initialize Well Baby Infant Record' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="<?= url('/patients/' . $patient['id'] . '/wellbaby/birth-record') ?>" method="POST" id="wellbabyBirthForm">
                    <?= csrf_field() ?>

                    <div class="modal-body p-4 bg-white small">
                        <div class="alert alert-info border-0 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Official CHO Santa Rosa Well Baby Record: Record infant birth circumstances, newborn screening certificate, and maternal link.
                        </div>

                        <div class="row g-3">
                            <!-- Link Registered Mother -->
                            <div class="col-12 col-sm-6">
                                <label for="mother_patient_id" class="form-label fw-semibold text-secondary">Link Registered Mother Profile</label>
                                <select name="mother_patient_id" id="mother_patient_id" class="form-select">
                                    <option value="">-- Select Registered Mother (Optional) --</option>
                                    <?php foreach ($potentialMothers as $mom): ?>
                                        <option value="<?= $mom['id'] ?>" <?= (!empty($wellbabyRecord['mother_patient_id']) && (int)$wellbabyRecord['mother_patient_id'] === (int)$mom['id']) ? 'selected' : '' ?>>
                                            <?= h($mom['last_name']) ?>, <?= h($mom['first_name']) ?> (<?= h($mom['patient_no']) ?> <?= !empty($mom['family_no']) ? '• Fam: ' . h($mom['family_no']) : '' ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Maternal CPAB TT Status -->
                            <div class="col-12 col-sm-6">
                                <label for="mother_cpab_tt" class="form-label fw-semibold text-secondary">Maternal CPAB TT Status (Protected at Birth)</label>
                                <input type="text" name="mother_cpab_tt" id="mother_cpab_tt" class="form-control" placeholder="e.g. TT2 Given in 2024, TT3 Complete" value="<?= h($wellbabyRecord['mother_cpab_tt'] ?? 'Protected at Birth') ?>">
                            </div>

                            <!-- Birth Weight -->
                            <div class="col-12 col-sm-4">
                                <label for="birth_weight_kg" class="form-label fw-semibold text-secondary">Birth Weight (kg) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="birth_weight_kg" id="birth_weight_kg" class="form-control" placeholder="e.g. 3.20" value="<?= h($wellbabyRecord['birth_weight_kg'] ?? '') ?>" required>
                            </div>

                            <!-- Birth Length -->
                            <div class="col-12 col-sm-4">
                                <label for="birth_length_cm" class="form-label fw-semibold text-secondary">Birth Length (cm) <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" name="birth_length_cm" id="birth_length_cm" class="form-control" placeholder="e.g. 50.0" value="<?= h($wellbabyRecord['birth_length_cm'] ?? '') ?>" required>
                            </div>

                            <!-- Birth Time -->
                            <div class="col-12 col-sm-4">
                                <label for="birth_time" class="form-label fw-semibold text-secondary">Time of Birth</label>
                                <input type="time" name="birth_time" id="birth_time" class="form-control" value="<?= h($wellbabyRecord['birth_time'] ?? '') ?>">
                            </div>

                            <!-- Place of Delivery -->
                            <div class="col-12 col-sm-4">
                                <label for="place_of_delivery" class="form-label fw-semibold text-secondary">Place of Delivery</label>
                                <select name="place_of_delivery" class="form-select">
                                    <option value="Lying-in Clinic" <?= ($wellbabyRecord['place_of_delivery'] ?? '') === 'Lying-in Clinic' ? 'selected' : '' ?>>Lying-in Clinic</option>
                                    <option value="Hospital (SRCH / Public)" <?= ($wellbabyRecord['place_of_delivery'] ?? '') === 'Hospital (SRCH / Public)' ? 'selected' : '' ?>>Hospital (SRCH / Public)</option>
                                    <option value="Hospital (Private)" <?= ($wellbabyRecord['place_of_delivery'] ?? '') === 'Hospital (Private)' ? 'selected' : '' ?>>Hospital (Private)</option>
                                    <option value="Home" <?= ($wellbabyRecord['place_of_delivery'] ?? '') === 'Home' ? 'selected' : '' ?>>Home</option>
                                    <option value="Other" <?= ($wellbabyRecord['place_of_delivery'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <!-- Delivery Type -->
                            <div class="col-12 col-sm-4">
                                <label for="delivery_type" class="form-label fw-semibold text-secondary">Delivery Type</label>
                                <select name="delivery_type" class="form-select">
                                    <option value="Normal Spontaneous Delivery (NSD)" selected>Normal Spontaneous (NSD)</option>
                                    <option value="Caesarean Section (CS)">Caesarean Section (CS)</option>
                                    <option value="Vacuum Extraction">Vacuum Extraction</option>
                                    <option value="Breech Delivery">Breech Delivery</option>
                                </select>
                            </div>

                            <!-- Attended By -->
                            <div class="col-12 col-sm-4">
                                <label for="attended_by" class="form-label fw-semibold text-secondary">Attended By</label>
                                <input type="text" name="attended_by" class="form-control" placeholder="e.g. Midwife Ramos, Dr. Santos" value="<?= h($wellbabyRecord['attended_by'] ?? 'Midwife') ?>">
                            </div>

                            <hr class="my-2 text-muted opacity-25">

                            <!-- Newborn Screening (NBS) Section -->
                            <div class="col-12">
                                <h6 class="fw-bold text-dark mb-0">Newborn Screening (NBS) Certificate</h6>
                            </div>
                            
                            <div class="col-12 col-sm-4 d-flex align-items-center">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="newborn_screening_done" value="1" id="nbs_done_check" <?= !empty($wellbabyRecord['newborn_screening_done']) ? 'checked' : '' ?>>
                                    <label class="form-check-label text-success fw-semibold" for="nbs_done_check">
                                        NBS Screening Done
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 col-sm-4">
                                <label for="newborn_screening_date" class="form-label fw-semibold text-secondary">NBS Date Screened</label>
                                <input type="text" name="newborn_screening_date" class="form-control dob-picker bg-white" placeholder="YYYY-MM-DD" value="<?= h($wellbabyRecord['newborn_screening_date'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-sm-4">
                                <label for="newborn_screening_result" class="form-label fw-semibold text-secondary">NBS Result / Cert #</label>
                                <input type="text" name="newborn_screening_result" class="form-control" placeholder="e.g. Normal (Cert # NBS-2026-09)" value="<?= h($wellbabyRecord['newborn_screening_result'] ?? 'Normal') ?>">
                            </div>

                            <!-- Infant Feeding Method -->
                            <div class="col-12">
                                <label for="feeding_method" class="form-label fw-semibold text-secondary">Initial Infant Feeding Practice</label>
                                <select name="feeding_method" class="form-select">
                                    <option value="LAM / Exclusive Breastfeeding" <?= ($wellbabyRecord['feeding_method'] ?? '') === 'LAM / Exclusive Breastfeeding' ? 'selected' : '' ?>>LAM / Exclusive Breastfeeding</option>
                                    <option value="Bottle Feeding (Formula)" <?= ($wellbabyRecord['feeding_method'] ?? '') === 'Bottle Feeding (Formula)' ? 'selected' : '' ?>>Bottle Feeding (Formula)</option>
                                    <option value="Mixed Feeding" <?= ($wellbabyRecord['feeding_method'] ?? '') === 'Mixed Feeding' ? 'selected' : '' ?>>Mixed Feeding</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success text-white px-4 fw-semibold">Save Well Baby Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. RECORD MONTHLY GROWTH VISIT MODAL -->
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
                                    <input type="text" name="log_date" class="form-control dob-picker bg-white" value="<?= date('Y-m-d') ?>" required>
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
                                        <option value="Bottle Feeding (Formula)">Bottle Feeding (Formula)</option>
                                        <option value="Mixed Feeding">Mixed Feeding</option>
                                    </select>
                                </div>

                                <hr class="my-2 text-muted opacity-25">

                                <!-- Vaccines Administered Today -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary">Vaccines Administered Today</label>
                                    <input type="text" name="vaccines_administered" class="form-control" placeholder="e.g. Pentavalent 1, OPV 1, Rota 1">
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

<?php endif; ?>

<!-- ==========================================================================
   UNIVERSAL IMMUNIZATION MODAL (Any Patient)
   ========================================================================== -->
<div class="modal fade" id="recordImmunizationModal" tabindex="-1" aria-labelledby="recordImmunizationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="recordImmunizationModalLabel">
                    <i class="bi bi-shield-plus me-2"></i>Record Vaccine Dose
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/immunizations/record') ?>" method="POST" id="singleImmunizationForm">
                <?= csrf_field() ?>

                <div class="modal-body p-4 bg-white small">
                    <div class="mb-3">
                        <label for="vaccine_name" class="form-label fw-semibold text-secondary">Vaccine Name <span class="text-danger">*</span></label>
                        <select name="vaccine_name" class="form-select" required>
                            <option value="">-- Select Vaccine --</option>
                            <optgroup label="Routine Infant EPI">
                                <option value="BCG">BCG</option>
                                <option value="Hepatitis B">Hepatitis B</option>
                                <option value="Pentavalent">Pentavalent (DTP-HepB-Hib)</option>
                                <option value="OPV">Oral Polio Vaccine (OPV)</option>
                                <option value="IPV">Inactivated Polio (IPV)</option>
                                <option value="Rotavirus">Rotavirus</option>
                                <option value="PCV">Pneumococcal Conjugate (PCV)</option>
                                <option value="MCV">Measles / MMR (MCV)</option>
                            </optgroup>
                            <optgroup label="Adolescent & Adult Vaccines">
                                <option value="HPV">HPV (Human Papillomavirus)</option>
                                <option value="Tetanus Toxoid">Tetanus Toxoid (TT / Td)</option>
                                <option value="Influenza">Influenza (Flu)</option>
                                <option value="Pneumococcal Polysaccharide">Pneumococcal (PPV23 / Senior)</option>
                                <option value="COVID-19">COVID-19</option>
                                <option value="Hepatitis A">Hepatitis A</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="dose_number" class="form-label fw-semibold text-secondary">Dose Number <span class="text-danger">*</span></label>
                            <input type="number" name="dose_number" class="form-control font-monospace" value="1" min="1" max="10" required>
                        </div>
                        <div class="col-6">
                            <label for="administered_date" class="form-label fw-semibold text-secondary">Administered Date <span class="text-danger">*</span></label>
                            <input type="text" name="administered_date" class="form-control dob-picker bg-white" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label fw-semibold text-secondary">Remarks / Lot No. / Site</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Lot #ABC-123, Left Deltoid, Bakuna Eskwela">
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Record Immunization</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   MATERNAL PRENATAL CARE MODALS (If Female)
   ========================================================================== -->
<?php if ($isFemale): ?>

    <!-- 1. START PREGNANCY EPISODE MODAL -->
    <div class="modal fade" id="startPrenatalModal" tabindex="-1" aria-labelledby="startPrenatalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-pink text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="startPrenatalModalLabel">
                        <i class="bi bi-heart-pulse-fill me-2"></i>Start Maternal Pregnancy Episode (CHO I Record)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="<?= url('/patients/' . $patient['id'] . '/prenatal/episode') ?>" method="POST" id="startPrenatalForm">
                    <?= csrf_field() ?>

                    <div class="modal-body p-4 bg-white">
                        <div class="alert alert-info border-0 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Select the patient's <strong>Last Menstrual Period (LMP)</strong> to automatically calculate the <strong>EDC (Due Date)</strong> via Naegele's Rule and dynamic <strong>AOG</strong>.
                        </div>

                        <div class="row g-3 small">
                            <!-- LMP Picker -->
                            <div class="col-12 col-sm-6">
                                <label for="lmp" class="form-label fw-semibold text-secondary">Last Menstrual Period (LMP) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                    <input type="text" name="lmp" id="prenatal_lmp_input" class="form-control dob-picker bg-white" placeholder="YYYY-MM-DD" required>
                                </div>
                            </div>

                            <!-- Live Calculated EDC Preview -->
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold text-secondary">Auto-calculated EDC (Naegele's Rule)</label>
                                <div class="p-2 bg-light rounded border fw-bold text-pink font-monospace" id="live_edc_preview">
                                    Select LMP to compute EDC...
                                </div>
                            </div>

                            <!-- Husband / Partner Name -->
                            <div class="col-12 col-sm-6">
                                <label for="husband_name" class="form-label fw-semibold text-secondary">Husband / Partner Name</label>
                                <input type="text" name="husband_name" id="husband_name" class="form-control" placeholder="Full Name of Partner" value="<?= h($patient['spouse_name'] ?? '') ?>">
                            </div>

                            <!-- Pre-Eclampsia High Risk Toggle -->
                            <div class="col-12 col-sm-6 d-flex align-items-center">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="pre_eclampsia" value="1" id="pre_eclampsia_check">
                                    <label class="form-check-label text-danger fw-semibold" for="pre_eclampsia_check">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Flag as High-Risk (Pre-Eclampsia)
                                    </label>
                                </div>
                            </div>

                            <hr class="my-2 text-muted opacity-25">

                            <!-- Obstetric GTPAL Matrix -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark">Obstetric History (GTPAL Score)</label>
                            </div>
                            <div class="col-4 col-sm-2">
                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Gravida (G)</label>
                                <input type="number" name="gravida" class="form-control form-control-sm text-center font-monospace" value="1" min="1" max="20" required>
                            </div>
                            <div class="col-4 col-sm-2">
                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Para (P)</label>
                                <input type="number" name="para" class="form-control form-control-sm text-center font-monospace" value="0" min="0" max="20">
                            </div>
                            <div class="col-4 col-sm-2">
                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Term (T)</label>
                                <input type="number" name="term_births" class="form-control form-control-sm text-center font-monospace" value="0" min="0" max="20">
                            </div>
                            <div class="col-4 col-sm-2">
                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Preterm (P)</label>
                                <input type="number" name="preterm_births" class="form-control form-control-sm text-center font-monospace" value="0" min="0" max="20">
                            </div>
                            <div class="col-4 col-sm-2">
                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Abortion (A)</label>
                                <input type="number" name="abortions" class="form-control form-control-sm text-center font-monospace" value="0" min="0" max="20">
                            </div>
                            <div class="col-4 col-sm-2">
                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Living (L)</label>
                                <input type="number" name="living_children" class="form-control form-control-sm text-center font-monospace" value="0" min="0" max="20">
                            </div>

                            <hr class="my-2 text-muted opacity-25">

                            <!-- Family Planning Counselling -->
                            <div class="col-12 col-sm-6">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="fp_counselling" value="1" id="fp_counselling_check" checked>
                                    <label class="form-check-label text-secondary" for="fp_counselling_check">Family Planning Counselling Provided</label>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label for="prenatal_notes" class="form-label fw-semibold text-secondary">Clinical Notes / Midwife Remarks</label>
                                <textarea name="notes" id="prenatal_notes" rows="2" class="form-control" placeholder="Special pregnancy instructions, high-risk notes..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-pink text-white px-4 fw-semibold">Enroll Pregnancy Episode</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. LOG SERIAL PRENATAL VISIT MODAL -->
    <?php if ($activePrenatal): ?>
        <div class="modal fade" id="addPrenatalVisitModal" tabindex="-1" aria-labelledby="addPrenatalVisitModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                    <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                        <h5 class="modal-title fw-bold" id="addPrenatalVisitModalLabel">
                            <i class="bi bi-journal-medical me-2"></i>Log Serial Prenatal Follow-up Visit
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <form action="<?= url('/prenatal/' . $activePrenatal['id'] . '/visit') ?>" method="POST" id="prenatalVisitForm">
                        <?= csrf_field() ?>

                        <div class="modal-body p-4 bg-white">
                            <div class="row g-3 small">
                                <!-- Visit Date -->
                                <div class="col-12 col-sm-6">
                                    <label for="visit_date" class="form-label fw-semibold text-secondary">Visit Date <span class="text-danger">*</span></label>
                                    <input type="text" name="visit_date" class="form-control dob-picker bg-white" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <!-- AOG in Weeks -->
                                <div class="col-12 col-sm-6">
                                    <label for="aog_weeks" class="form-label fw-semibold text-secondary">AOG (Weeks) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.1" name="aog_weeks" class="form-control font-monospace" value="<?= h($activePrenatal['calculated_aog']['weeks'] ?? '12') ?>" required>
                                </div>

                                <!-- Maternal Blood Pressure -->
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label fw-semibold text-secondary">Systolic BP (mmHg)</label>
                                    <input type="number" name="bp_systolic" class="form-control" placeholder="120" min="40" max="250">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label fw-semibold text-secondary">Diastolic BP (mmHg)</label>
                                    <input type="number" name="bp_diastolic" class="form-control" placeholder="80" min="30" max="150">
                                </div>

                                <!-- Weight & Height -->
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label fw-semibold text-secondary">Weight (kg)</label>
                                    <input type="number" step="0.1" name="weight_kg" class="form-control" placeholder="55.0">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label fw-semibold text-secondary">Height (cm)</label>
                                    <input type="number" step="0.1" name="height_cm" class="form-control" placeholder="158.0">
                                </div>

                                <hr class="my-2 text-muted opacity-25">

                                <!-- FHT (bpm) -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <label class="form-label fw-semibold text-pink">Fetal Heart Tone - FHT (bpm)</label>
                                    <input type="number" name="fetal_heart_tone" class="form-control" placeholder="140 (Normal 120-160)">
                                    <div class="form-text" style="font-size: 0.7rem;">Normal physiological range: 120 to 160 bpm.</div>
                                </div>

                                <!-- Fundal Height (cm) -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <label class="form-label fw-semibold text-secondary">Fundal Height (FH cm)</label>
                                    <input type="number" step="0.5" name="fundal_height_cm" class="form-control" placeholder="e.g. 24.0">
                                </div>

                                <!-- Fetal Presentation -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <label class="form-label fw-semibold text-secondary">Fetal Presentation</label>
                                    <select name="fetal_presentation" class="form-select">
                                        <option value="Cephalic" selected>Cephalic (Head down)</option>
                                        <option value="Breech">Breech</option>
                                        <option value="Transverse">Transverse</option>
                                        <option value="Variable">Variable / Not Determined</option>
                                    </select>
                                </div>

                                <!-- TCB / Tetanus Status -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary">TCB / Maternal TT Dose Given</label>
                                    <input type="text" name="tcb" class="form-control" placeholder="e.g. TT3 Administered, Iron/Folic given">
                                </div>

                                <!-- Chief Complaint -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary">Chief Complaint / Symptoms</label>
                                    <input type="text" name="chief_complaint" class="form-control" placeholder="e.g. Routine checkup, mild backache">
                                </div>

                                <!-- Remarks -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary">Midwife / Clinician Remarks</label>
                                    <textarea name="remarks" rows="2" class="form-control" placeholder="Advised rest, iron supplements prescribed, next follow up in 4 weeks..."></textarea>
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

        <!-- 3. EDIT PREGNANCY EPISODE MODAL -->
        <div class="modal fade" id="editPrenatalModal" tabindex="-1" aria-labelledby="editPrenatalModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                    <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                        <h5 class="modal-title fw-bold" id="editPrenatalModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>Edit Pregnancy Episode Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <form action="<?= url('/prenatal/' . $activePrenatal['id'] . '/update') ?>" method="POST" id="editPrenatalForm">
                        <?= csrf_field() ?>

                        <div class="modal-body p-4 bg-white small">
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary">LMP Date</label>
                                    <input type="text" name="lmp" class="form-control dob-picker bg-white" value="<?= h($activePrenatal['lmp']) ?>" required>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary">Husband / Partner Name</label>
                                    <input type="text" name="husband_name" class="form-control" value="<?= h($activePrenatal['husband_name'] ?? '') ?>">
                                </div>

                                <div class="col-4 col-sm-2">
                                    <label class="form-label text-secondary" style="font-size: 0.75rem;">Gravida</label>
                                    <input type="number" name="gravida" class="form-control form-control-sm text-center font-monospace" value="<?= $activePrenatal['gravida'] ?>">
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="form-label text-secondary" style="font-size: 0.75rem;">Para</label>
                                    <input type="number" name="para" class="form-control form-control-sm text-center font-monospace" value="<?= $activePrenatal['para'] ?>">
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="form-label text-secondary" style="font-size: 0.75rem;">Term</label>
                                    <input type="number" name="term_births" class="form-control form-control-sm text-center font-monospace" value="<?= $activePrenatal['term_births'] ?>">
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="form-label text-secondary" style="font-size: 0.75rem;">Preterm</label>
                                    <input type="number" name="preterm_births" class="form-control form-control-sm text-center font-monospace" value="<?= $activePrenatal['preterm_births'] ?>">
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="form-label text-secondary" style="font-size: 0.75rem;">Abortion</label>
                                    <input type="number" name="abortions" class="form-control form-control-sm text-center font-monospace" value="<?= $activePrenatal['abortions'] ?>">
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="form-label text-secondary" style="font-size: 0.75rem;">Living</label>
                                    <input type="number" name="living_children" class="form-control form-control-sm text-center font-monospace" value="<?= $activePrenatal['living_children'] ?>">
                                </div>

                                <div class="col-12 col-sm-6">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="pre_eclampsia" value="1" id="edit_pre_eclampsia" <?= !empty($activePrenatal['pre_eclampsia']) ? 'checked' : '' ?>>
                                        <label class="form-check-label text-danger fw-semibold" for="edit_pre_eclampsia">High-Risk Pre-Eclampsia</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="fp_counselling" value="1" id="edit_fp_counselling" <?= !empty($activePrenatal['fp_counselling']) ? 'checked' : '' ?>>
                                        <label class="form-check-label text-secondary" for="edit_fp_counselling">FP Counselling Provided</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary">Notes</label>
                                    <textarea name="notes" rows="2" class="form-control"><?= h($activePrenatal['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold">Update Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 4. CONCLUDE PREGNANCY EPISODE MODAL -->
        <div class="modal fade" id="concludePrenatalModal" tabindex="-1" aria-labelledby="concludePrenatalModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
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
                                Concluding this episode will mark the maternal care record as completed and record the delivery outcome.
                            </div>

                            <div class="mb-3">
                                <label for="delivery_date" class="form-label fw-semibold text-secondary">Delivery / Outcome Date <span class="text-danger">*</span></label>
                                <input type="text" name="delivery_date" class="form-control dob-picker bg-white" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="delivery_outcome" class="form-label fw-semibold text-secondary">Delivery Outcome <span class="text-danger">*</span></label>
                                <select name="delivery_outcome" class="form-select" required>
                                    <option value="Live Birth (Single)" selected>Live Birth (Single)</option>
                                    <option value="Live Birth (Multiple/Twins)">Live Birth (Multiple / Twins)</option>
                                    <option value="Stillbirth">Stillbirth</option>
                                    <option value="Miscarriage">Miscarriage / Abortion</option>
                                    <option value="Delivered Elsewhere">Delivered in Another Hospital / Clinic</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="conclude_notes" class="form-label fw-semibold text-secondary">Outcome Notes</label>
                                <textarea name="notes" id="conclude_notes" rows="2" class="form-control" placeholder="Baby boy delivered via NSD, 3.2kg..."></textarea>
                            </div>
                        </div>
                        
                        <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger px-4 fw-semibold">Confirm Conclusion</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 5. ADD PAST OBSTETRIC DELIVERY MODAL -->
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
                                    <option value="Vacuum/Forceps">Vacuum / Forceps</option>
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
                                    <option value="Deceased">Deceased</option>
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

<?php endif; ?>

<!-- ==========================================================================
   VITAL SIGNS RECORDING MODAL
   ========================================================================== -->
<div class="modal fade" id="addVitalsModal" tabindex="-1" aria-labelledby="addVitalsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="addVitalsModalLabel">
                    <i class="bi bi-heart-pulse-fill me-2"></i>Record Vital Signs
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/vital-signs') ?>" method="POST" id="vitalsForm">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

                <div class="modal-body p-4 bg-white">
                    <div class="text-muted small mb-3">
                        Patient: <strong><?= h($patient['last_name']) ?>, <?= h($patient['first_name']) ?></strong> &bull; DOB: <?= h($patient['dob']) ?>
                    </div>
                    
                    <div class="row g-3">
                        <!-- Blood Pressure Systolic -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="bp_systolic" class="form-label fw-semibold text-secondary small">Blood Pressure - Systolic</label>
                            <div class="input-group">
                                <input type="number" name="bp_systolic" id="bp_systolic" class="form-control" placeholder="120" min="40" max="300">
                                <span class="input-group-text small">mmHg</span>
                            </div>
                        </div>

                        <!-- Blood Pressure Diastolic -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="bp_diastolic" class="form-label fw-semibold text-secondary small">Blood Pressure - Diastolic</label>
                            <div class="input-group">
                                <input type="number" name="bp_diastolic" id="bp_diastolic" class="form-control" placeholder="80" min="30" max="200">
                                <span class="input-group-text small">mmHg</span>
                            </div>
                        </div>

                        <!-- Heart Rate -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="heart_rate" class="form-label fw-semibold text-secondary small">Heart Rate / Pulse</label>
                            <div class="input-group">
                                <input type="number" name="heart_rate" id="heart_rate" class="form-control" placeholder="72" min="20" max="250">
                                <span class="input-group-text small">bpm</span>
                            </div>
                        </div>

                        <!-- Temperature -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="temperature" class="form-label fw-semibold text-secondary small">Body Temperature</label>
                            <div class="input-group">
                                <input type="number" name="temperature" id="temperature" class="form-control" placeholder="36.5" step="0.1" min="30" max="45">
                                <span class="input-group-text small">°C</span>
                            </div>
                        </div>

                        <!-- Respiratory Rate -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="respiratory_rate" class="form-label fw-semibold text-secondary small">Respiratory Rate</label>
                            <div class="input-group">
                                <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" placeholder="18" min="5" max="80">
                                <span class="input-group-text small">cpm</span>
                            </div>
                        </div>

                        <!-- Oxygen Saturation -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="oxygen_saturation" class="form-label fw-semibold text-secondary small">Oxygen Saturation (SpO2)</label>
                            <div class="input-group">
                                <input type="number" name="oxygen_saturation" id="oxygen_saturation" class="form-control" placeholder="98" min="50" max="100">
                                <span class="input-group-text small">%</span>
                            </div>
                        </div>

                        <hr class="my-3 text-muted opacity-25">

                        <!-- Weight -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="weight" class="form-label fw-semibold text-secondary small">Weight</label>
                            <div class="input-group">
                                <input type="number" name="weight" id="weight" class="form-control" placeholder="60" step="0.01" min="1" max="500">
                                <span class="input-group-text small">kg</span>
                            </div>
                        </div>

                        <!-- Height -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="height" class="form-label fw-semibold text-secondary small">Height</label>
                            <div class="input-group">
                                <input type="number" name="height" id="height" class="form-control" placeholder="165" step="0.1" min="30" max="300">
                                <span class="input-group-text small">cm</span>
                            </div>
                        </div>

                        <!-- BMI (Auto-calculated) -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="bmi" class="form-label fw-semibold text-secondary small">Calculated BMI</label>
                            <input type="text" name="bmi" id="bmi" class="form-control bg-light" placeholder="BMI auto-calc" readonly>
                        </div>

                        <!-- Waist Circumference -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="waist_circumference" class="form-label fw-semibold text-secondary small">Waist Circumference</label>
                            <div class="input-group">
                                <input type="number" name="waist_circumference" id="waist_circumference" class="form-control" placeholder="75" step="0.1" min="10" max="250">
                                <span class="input-group-text small">cm</span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label for="notes" class="form-label fw-semibold text-secondary small">Clinical Notes / Symptoms</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Patient states feeling dizzy, etc."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Vitals</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VIEW CONSULTATION DETAILS MODAL
   ========================================================================== -->
<div class="modal fade" id="viewConsultationModal" tabindex="-1" aria-labelledby="viewConsultationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-white py-3 border-bottom" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold text-dark" id="viewConsultationModalLabel">
                    <i class="bi bi-journal-medical text-primary me-2"></i>Consultation Details (SOAP Notes)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-white" id="consultationDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 small">Fetching consultation record...</p>
                </div>
            </div>
            
            <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
                <div class="d-flex align-items-center gap-2" id="consultationModalFooterRight">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
<!-- ==========================================================================
   ARCHIVE PATIENT MODAL
   ========================================================================== -->
<div class="modal fade" id="archivePatientModal" tabindex="-1" aria-labelledby="archivePatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="archivePatientModalLabel">
                    <i class="bi bi-archive-fill me-2"></i>Archive Patient Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/archive') ?>" method="POST" id="archiveForm">
                <?= csrf_field() ?>
                <div class="modal-body p-4 bg-white">
                    <div class="alert alert-warning border-0 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Warning:</strong> Archiving will hide this patient from active directories, daily queues, and scheduling lists. Only administrators can view and restore archived records.
                    </div>
                    <div class="mb-3">
                        <label for="archive_reason" class="form-label fw-semibold text-secondary small">Reason for Archiving <span class="text-danger">*</span></label>
                        <textarea name="archive_reason" id="archive_reason" class="form-control bg-light" rows="3" placeholder="e.g. Patient moved, deceased, or record duplicate..." required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Archive</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Client-side Scripts -->
<script>
// Global IHP Edit / View Mode Transition Functions
let ihpFormInitialData = '';

window.enterIhpEditMode = function() {
    const viewMode = document.getElementById('ihp-view-mode');
    const editMode = document.getElementById('ihp-edit-mode');
    const form = document.getElementById('ihpForm');
    if (viewMode && editMode) {
        viewMode.classList.add('d-none');
        editMode.classList.remove('d-none');
        if (form) {
            ihpFormInitialData = new URLSearchParams(new FormData(form)).toString();
        }
        const tabEl = document.getElementById('tab-ihp');
        if (tabEl) {
            tabEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
};

window.cancelIhpEditMode = function() {
    const form = document.getElementById('ihpForm');
    const viewMode = document.getElementById('ihp-view-mode');
    const editMode = document.getElementById('ihp-edit-mode');
    if (!form || !viewMode || !editMode) return;

    const currentData = new URLSearchParams(new FormData(form)).toString();
    const isDirty = (currentData !== ihpFormInitialData);

    if (isDirty) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Discard Changes?',
                text: 'You have modified fields in this IHP record. Are you sure you want to discard your changes?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Discard Changes',
                cancelButtonText: 'Keep Editing'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.reset();
                    const lmpInput = form.querySelector('input[name="lmp"]');
                    if (lmpInput && lmpInput._flatpickr) {
                        lmpInput._flatpickr.setDate(lmpInput.defaultValue || '', false);
                    }
                    editMode.classList.add('d-none');
                    viewMode.classList.remove('d-none');
                }
            });
        } else {
            if (confirm('Discard unsaved changes to this IHP record?')) {
                form.reset();
                editMode.classList.add('d-none');
                viewMode.classList.remove('d-none');
            }
        }
    } else {
        editMode.classList.add('d-none');
        viewMode.classList.remove('d-none');
    }
};

window.editIhpFromOverview = function() {
    const ihpBtn = document.getElementById('tab-ihp-btn');
    if (ihpBtn) {
        ihpBtn.click();
        setTimeout(function() {
            enterIhpEditMode();
        }, 150);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // 1. BMI Auto-calculation in Vitals Modal
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const bmiInput = document.getElementById('bmi');

    function calculateBMI() {
        const weight = parseFloat(weightInput.value);
        const height = parseFloat(heightInput.value);
        if (weight > 0 && height > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            bmiInput.value = bmi.toFixed(2);
        } else {
            bmiInput.value = '';
        }
    }

    if (weightInput && heightInput) {
        weightInput.addEventListener('input', calculateBMI);
        heightInput.addEventListener('input', calculateBMI);
    }

    // 2. Tab switching based on URL hash (e.g. #tab-wellbaby, #appointments-tab, #consultations-tab)
    if (window.location.hash) {
        const hash = window.location.hash;
        if (hash === '#ihp-history' || hash === '#tab-ihp' || hash === '#ihp-tab') {
            const ihpBtn = document.getElementById('tab-ihp-btn');
            if (ihpBtn) ihpBtn.click();
        } else if (hash === '#edit-ihp') {
            editIhpFromOverview();
        } else if (hash === '#tab-consultations' || hash === '#consultations-tab') {
            const btn = document.getElementById('tab-consultations-btn');
            if (btn) btn.click();
        } else if (hash === '#tab-vitals' || hash === '#vitals-tab') {
            const btn = document.getElementById('tab-vitals-btn');
            if (btn) btn.click();
        } else if (hash === '#tab-prenatal' || hash === '#prenatal-tab') {
            const btn = document.getElementById('tab-prenatal-btn');
            if (btn) btn.click();
        } else if (hash === '#tab-wellbaby' || hash === '#wellbaby-tab') {
            const btn = document.getElementById('tab-wellbaby-btn');
            if (btn) btn.click();
        } else if (hash === '#tab-immunizations' || hash === '#immunizations-tab') {
            const btn = document.getElementById('tab-immunizations-btn');
            if (btn) btn.click();
        } else if (hash === '#tab-appointments' || hash === '#appointments-tab' || hash === '#queue-tab') {
            const btn = document.getElementById('tab-appointments-btn');
            if (btn) btn.click();
        } else if (hash === '#tab-overview' || hash === '#overview-tab') {
            const btn = document.getElementById('tab-overview-btn');
            if (btn) btn.click();
        }
    }

    // 3. Consultation SOAP Modal AJAX Loader
    const viewConsultationModal = document.getElementById('viewConsultationModal');
    const consultationDetailsContent = document.getElementById('consultationDetailsContent');

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.querySelectorAll('.view-consultation-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const consultationId = this.getAttribute('data-consultation-id');
            const modal = new bootstrap.Modal(viewConsultationModal);
            modal.show();

            const footerRight = document.getElementById('consultationModalFooterRight');
            if (footerRight) {
                footerRight.innerHTML = `<button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>`;
            }

            consultationDetailsContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 small">Fetching consultation record...</p>
                </div>
            `;

            fetch(`<?= url('/consultations/') ?>${consultationId}`)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    if (data.error) {
                        consultationDetailsContent.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(data.error)}</div>`;
                        return;
                    }

                    // Vitals strip formatting
                    let vitalsHtml = '';
                    if (data.bp_systolic || data.temperature || data.heart_rate || data.weight || data.height) {
                        const isHighBp = (parseInt(data.bp_systolic) >= 140 || parseInt(data.bp_diastolic) >= 90);
                        vitalsHtml = `
                            <div class="p-3 bg-light rounded-3 mb-3 border d-flex flex-wrap align-items-center gap-3 small text-secondary">
                                <span class="fw-bold text-dark"><i class="bi bi-speedometer2 text-primary me-1"></i> Linked Vital Signs:</span>
                                ${data.bp_systolic && data.bp_diastolic ? `<span>BP: <strong class="${isHighBp ? 'text-danger fw-bold' : 'text-dark'}">${escapeHtml(data.bp_systolic)}/${escapeHtml(data.bp_diastolic)} mmHg</strong></span><span class="vr"></span>` : ''}
                                ${data.temperature ? `<span>Temp: <strong class="text-dark">${escapeHtml(data.temperature)} °C</strong></span><span class="vr"></span>` : ''}
                                ${data.heart_rate ? `<span>HR: <strong class="text-dark">${escapeHtml(data.heart_rate)} bpm</strong></span><span class="vr"></span>` : ''}
                                ${data.respiratory_rate ? `<span>RR: <strong class="text-dark">${escapeHtml(data.respiratory_rate)} cpm</strong></span><span class="vr"></span>` : ''}
                                ${data.oxygen_saturation ? `<span>SpO2: <strong class="text-dark">${escapeHtml(data.oxygen_saturation)}%</strong></span><span class="vr"></span>` : ''}
                                ${data.weight ? `<span>Weight: <strong class="text-dark">${escapeHtml(data.weight)} kg</strong></span><span class="vr"></span>` : ''}
                                ${data.height ? `<span>Height: <strong class="text-dark">${escapeHtml(data.height)} cm</strong></span><span class="vr"></span>` : ''}
                                ${data.bmi ? `<span>BMI: <strong class="text-dark">${escapeHtml(data.bmi)}</strong></span>` : ''}
                            </div>
                        `;
                    }

                    let statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Completed</span>';
                    if (data.status === 'Open') {
                        statusBadge = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Open</span>';
                    } else if (data.status === 'Cancelled') {
                        statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Cancelled</span>';
                    }

                    let lastEditedHtml = '';
                    if (data.formatted_updated && data.updater_name) {
                        lastEditedHtml = `
                            <div class="col-12 text-muted small mt-1">
                                <i class="bi bi-pencil me-1"></i>Last edited on ${escapeHtml(data.formatted_updated)} by ${escapeHtml(data.updater_name)}
                            </div>
                        `;
                    }

                    let cancellationHtml = '';
                    if (data.status === 'Cancelled' && data.archive_reason) {
                        cancellationHtml = `
                            <div class="col-12 alert alert-danger py-2 px-3 small mt-2 mb-0">
                                <i class="bi bi-x-octagon-fill me-1"></i><strong>Cancellation Reason:</strong> ${escapeHtml(data.archive_reason)}
                            </div>
                        `;
                    }

                    consultationDetailsContent.innerHTML = `
                        <!-- Metadata Header Strip -->
                        <div class="row g-2 pb-3 mb-3 border-bottom align-items-center">
                            <div class="col-12 col-md-5">
                                <span class="text-muted small d-block">Consultation Date & Time:</span>
                                <strong class="text-dark"><i class="bi bi-calendar-event me-1 text-primary"></i>${escapeHtml(data.formatted_date || data.consulted_at)}</strong>
                            </div>
                            <div class="col-12 col-md-4">
                                <span class="text-muted small d-block">Attending Clinician:</span>
                                <strong class="text-dark"><i class="bi bi-person-badge me-1 text-primary"></i>${escapeHtml(data.clinician_name || 'Unassigned Clinician')}</strong>
                            </div>
                            <div class="col-12 col-md-3 text-md-end">
                                <span class="text-muted small d-block">Status:</span>
                                ${statusBadge}
                            </div>
                            ${lastEditedHtml}
                            ${cancellationHtml}
                        </div>

                        ${vitalsHtml}

                        <!-- SOAP Note Clean Neutral Cards -->
                        <div class="d-flex flex-column gap-3">
                            <!-- Subjective (S) -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom">
                                    <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-chat-left-text me-2 text-primary"></i>Subjective (S) &mdash; Chief Complaint & History of Illness</span>
                                </div>
                                <div class="card-body p-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.subjective || 'No subjective complaint recorded.')}</div>
                            </div>

                            <!-- Objective (O) -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom">
                                    <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-clipboard2-pulse me-2 text-primary"></i>Objective (O) &mdash; Physical Examination & Clinical Findings</span>
                                </div>
                                <div class="card-body p-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.objective || 'No physical examination findings recorded.')}</div>
                            </div>

                            <!-- Assessment (A) -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom">
                                    <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-heart-pulse me-2 text-primary"></i>Assessment (A) &mdash; Clinical Impression / Diagnosis</span>
                                </div>
                                <div class="card-body p-3 text-dark small fw-medium" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.assessment || 'No clinical diagnosis recorded.')}</div>
                            </div>

                            <!-- Plan (P) -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom">
                                    <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-prescription2 me-2 text-primary"></i>Plan (P) &mdash; Treatment, Prescriptions & Recommendations</span>
                                </div>
                                <div class="card-body p-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.plan || 'No treatment plan or prescriptions recorded.')}</div>
                            </div>
                        </div>
                    `;

                    // Update modal footer actions (Edit button if authorized)
                    const footerRight = document.getElementById('consultationModalFooterRight');
                    if (footerRight) {
                        let footerBtns = '';
                        if (data.can_edit) {
                            footerBtns += `<a href="<?= url('/consultations/') ?>${data.id}/edit" class="btn btn-primary btn-sm px-3"><i class="bi bi-pencil-square me-1"></i> Edit Consultation</a>`;
                        }
                        footerBtns += `<button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>`;
                        footerRight.innerHTML = footerBtns;
                    }
                })
                .catch(err => {
                    consultationDetailsContent.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Failed to load consultation details. Please try again.
                        </div>
                    `;
                });
        });
    });

    // 4. Flatpickr initialization
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".dob-picker", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            allowInput: true
        });
    }

    // 5. Live Naegele Rule EDC Calculator Preview
    const prenatalLmpInput = document.getElementById('prenatal_lmp_input');
    const liveEdcPreview = document.getElementById('live_edc_preview');

    if (prenatalLmpInput && liveEdcPreview) {
        prenatalLmpInput.addEventListener('change', function() {
            const lmpVal = this.value;
            if (lmpVal) {
                const lmpDate = new Date(lmpVal);
                if (!isNaN(lmpDate.getTime())) {
                    // Naegele's rule: +1 year, -3 months, +7 days
                    const edc = new Date(lmpDate);
                    edc.setFullYear(edc.getFullYear() + 1);
                    edc.setMonth(edc.getMonth() - 3);
                    edc.setDate(edc.getDate() + 7);
                    
                    const options = { year: 'numeric', month: 'short', day: 'numeric' };
                    liveEdcPreview.innerText = edc.toLocaleDateString('en-US', options) + ' (' + edc.toISOString().split('T')[0] + ')';
                }
            } else {
                liveEdcPreview.innerText = 'Select LMP to compute EDC...';
            }
        });
    }
});
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
