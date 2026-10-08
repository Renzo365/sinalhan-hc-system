<?php
/**
 * Dedicated PhilHealth Annex A1: Individual Health Profile (IHP) Medical History Edit View
 * 
 * @var array $patient Demographic patient record
 * @var array $medicalHistory Existing baseline medical history / IHP data
 * @var array|null $latestVitals Latest vital signs record if available
 * @var array $patientImmunizations Specific immunization history records
 */

$isFemale = strtolower($patient['sex'] ?? '') === 'female';
$lastName = trim($patient['last_name'] ?? '');
$firstName = trim($patient['first_name'] ?? '');
$middleName = trim($patient['middle_name'] ?? '');
$suffix = trim($patient['suffix'] ?? '');

$fullNameFormatted = $lastName . ', ' . $firstName;
if (!empty($middleName)) {
    $fullNameFormatted .= ' ' . mb_substr($middleName, 0, 1) . '.';
}
if (!empty($suffix)) {
    $fullNameFormatted .= ' ' . $suffix;
}

$initials = '';
if (!empty($firstName)) $initials .= mb_substr($firstName, 0, 1);
if (!empty($lastName)) $initials .= mb_substr($lastName, 0, 1);
$initials = strtoupper($initials ?: 'PT');

$patientDob = $patient['dob'] ?? $patient['date_of_birth'] ?? '';
$patientAge = !empty($patientDob) ? calculate_age($patientDob) : ($patient['age'] ?? '');
$fullAddress = !empty(trim($patient['address'] ?? '')) ? trim($patient['address']) : 'Barangay Sinalhan, Santa Rosa, Laguna';

$isExistingRecord = !empty($medicalHistory) && !empty($medicalHistory['id']);
$actionTitle = $isExistingRecord ? 'Edit Individual Health Profile (IHP)' : 'Record Individual Health Profile (IHP)';
$actionSubtitle = $isExistingRecord 
    ? 'PhilHealth Annex A1: Update baseline clinical illnesses, family heredity, surgical history, social habits, immunizations, and reproductive profile.'
    : 'PhilHealth Annex A1: Record baseline clinical illnesses, family heredity, surgical history, social habits, immunizations, and reproductive profile.';
$breadcrumbAction = $isExistingRecord ? 'Edit IHP Record' : 'Record IHP Medical History';

$title = $actionTitle . ': ' . $fullNameFormatted;
$breadcrumbs = [
    'Patients' => '/patients',
    $fullNameFormatted => '/patients/' . $patient['id'] . '#tab-ihp',
    $breadcrumbAction => null
];

// Handle validation errors flashed from controller
$hasIhpFormErrors = !empty($_SESSION['ihp_form_input']) && is_array($_SESSION['ihp_form_input']);
$ihpInput = $hasIhpFormErrors ? $_SESSION['ihp_form_input'] : [];
if ($hasIhpFormErrors) {
    unset($_SESSION['ihp_form_input']);
    if (!is_array($medicalHistory)) {
        $medicalHistory = [];
    }
    foreach ($ihpInput as $key => $val) {
        if ($key !== 'past_medical_history' && $key !== 'family_history' && $key !== 'surgical_procedures' && !str_starts_with($key, 'pe_') && !str_starts_with($key, 'imm_')) {
            $medicalHistory[$key] = $val;
        }
    }
}

$pmhSaved = $medicalHistory['past_medical_history'] ?? [];
$familySaved = $medicalHistory['family_history'] ?? [];
$surgicalSaved = $medicalHistory['surgical_history'] ?? [];
$peSaved = $medicalHistory['physical_examination'] ?? [];
$immSaved = $medicalHistory['external_immunizations'] ?? [];

// If returning from validation error, hydrate structured checklists from submitted input
if ($hasIhpFormErrors) {
    $rawPmh = $ihpInput['past_medical_history'] ?? [];
    if (!is_array($rawPmh)) $rawPmh = !empty($rawPmh) ? [$rawPmh] : [];
    $pmhSaved = [];
    foreach ($rawPmh as $k => $v) {
        $cond = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
        if ($cond === '' || $cond === '[]' || $cond === '{}') continue;
        $det = (is_string($k) && !is_numeric($k) && is_string($v)) ? trim($v) : '';
        $pmhSaved[$cond] = $det;
    }
    if (!empty($ihpInput['allergy_specifics'])) $pmhSaved['Allergy'] = trim($ihpInput['allergy_specifics']);
    if (!empty($ihpInput['cancer_organ'])) $pmhSaved['Cancer'] = trim($ihpInput['cancer_organ']);
    if (!empty($ihpInput['hepatitis_type'])) $pmhSaved['Hepatitis'] = trim($ihpInput['hepatitis_type']);
    if (!empty($ihpInput['hypertension_highest_bp'])) $pmhSaved['Hypertension'] = 'Highest BP: ' . trim($ihpInput['hypertension_highest_bp']);
    if (!empty($ihpInput['tuberculosis_organ'])) $pmhSaved['Tuberculosis'] = trim($ihpInput['tuberculosis_organ']);
    if (!empty($ihpInput['ptb_details'])) $pmhSaved['Pulmonary Tuberculosis (PTB)'] = trim($ihpInput['ptb_details']);
    if (!empty($ihpInput['pmh_other_specify'])) $pmhSaved['Others'] = trim($ihpInput['pmh_other_specify']);

    $rawFam = $ihpInput['family_history'] ?? [];
    if (!is_array($rawFam)) $rawFam = !empty($rawFam) ? [$rawFam] : [];
    $familySaved = [];
    foreach ($rawFam as $k => $v) {
        $cond = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
        if ($cond === '' || $cond === '[]' || $cond === '{}') continue;
        $det = (is_string($k) && !is_numeric($k) && is_string($v)) ? trim($v) : '';
        $familySaved[$cond] = $det;
    }
    if (!empty($ihpInput['fam_allergy_specifics'])) $familySaved['Allergy'] = trim($ihpInput['fam_allergy_specifics']);
    if (!empty($ihpInput['fam_cancer_organ'])) $familySaved['Cancer'] = trim($ihpInput['fam_cancer_organ']);
    if (!empty($ihpInput['fam_hepatitis_type'])) $familySaved['Hepatitis'] = trim($ihpInput['fam_hepatitis_type']);
    if (!empty($ihpInput['fam_hypertension_highest_bp'])) $familySaved['Hypertension'] = 'Highest BP: ' . trim($ihpInput['fam_hypertension_highest_bp']);
    if (!empty($ihpInput['fam_tuberculosis_organ'])) $familySaved['Tuberculosis'] = trim($ihpInput['fam_tuberculosis_organ']);
    if (!empty($ihpInput['fam_ptb_details'])) $familySaved['PTB Category'] = trim($ihpInput['fam_ptb_details']);
    if (!empty($ihpInput['family_other'])) $familySaved['Others'] = trim($ihpInput['family_other']);

    $surgicalSaved = [];
    if (!empty($ihpInput['surgical_procedures']) && is_array($ihpInput['surgical_procedures'])) {
        foreach ($ihpInput['surgical_procedures'] as $proc) {
            if (!empty($proc['operation']) || !empty($proc['name'])) {
                $surgicalSaved[] = [
                    'operation' => trim($proc['operation'] ?? $proc['name'] ?? ''),
                    'date' => trim($proc['date'] ?? ''),
                    'hospital' => trim($proc['hospital'] ?? '')
                ];
            }
        }
    }

    $peSaved = [];
    foreach (['skin', 'heent', 'chest_lungs', 'heart', 'abdomen', 'extremities'] as $sys) {
        $peSaved[$sys] = !empty($ihpInput['pe_' . $sys]) && is_array($ihpInput['pe_' . $sys]) ? $ihpInput['pe_' . $sys] : [];
    }
    $peSaved['remarks'] = $ihpInput['pe_remarks'] ?? '';

    $immSaved = [];
    foreach (['children', 'young_women', 'pregnant', 'elderly'] as $cat) {
        $immSaved[$cat] = !empty($ihpInput['imm_' . $cat]) && is_array($ihpInput['imm_' . $cat]) ? $ihpInput['imm_' . $cat] : [];
    }
    $immSaved['others'] = $ihpInput['imm_others_specify'] ?? '';
}

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
if (is_string($peSaved)) {
    $decoded = json_decode($peSaved, true);
    $peSaved = is_array($decoded) ? $decoded : [];
}
$peSaved = \App\Models\PatientMedicalHistory::normalizePhysicalExamination($peSaved);

if (is_string($immSaved)) {
    $decoded = json_decode($immSaved, true);
    $immSaved = is_array($decoded) ? $decoded : [];
}
$immSaved = \App\Models\PatientMedicalHistory::normalizeImmunizations($immSaved);

// Prefill baseline vitals from latest vitals if baseline vitals are completely empty
$defaultBpSystolic = $medicalHistory['baseline_bp_systolic'] ?? ($latestVitals['bp_systolic'] ?? '');
$defaultBpDiastolic = $medicalHistory['baseline_bp_diastolic'] ?? ($latestVitals['bp_diastolic'] ?? '');
$defaultHeartRate = $medicalHistory['baseline_heart_rate'] ?? ($latestVitals['heart_rate'] ?? '');
$defaultRespiratoryRate = $medicalHistory['baseline_respiratory_rate'] ?? ($latestVitals['respiratory_rate'] ?? '');
$defaultHeight = $medicalHistory['baseline_height'] ?? ($latestVitals['height'] ?? '');
$defaultWeight = $medicalHistory['baseline_weight'] ?? ($latestVitals['weight'] ?? '');
$defaultWaist = $medicalHistory['baseline_waist_circumference'] ?? '';

// Format demographics items identically to Clinical Care Workstation
$demographicsItems = [];
if (!empty($patientAge)) {
    $demographicsItems[] = '<span><strong>' . h($patientAge) . '</strong> yrs &bull; ' . h(ucfirst($patient['sex'] ?? 'Unknown')) . '</span>';
} else {
    $demographicsItems[] = '<span>' . h(ucfirst($patient['sex'] ?? 'Unknown')) . '</span>';
}

$dobFormatted = !empty($patientDob) ? date('M d, Y', strtotime($patientDob)) : 'Not Specified';
$demographicsItems[] = '<span>DOB: <strong class="' . (!empty($patientDob) ? 'text-dark' : 'text-muted') . '">' . h($dobFormatted) . '</strong></span>';

if (!empty($patient['blood_type'])) {
    $demographicsItems[] = '<span>Blood: <strong class="text-danger">' . h($patient['blood_type']) . '</strong></span>';
} else {
    $demographicsItems[] = '<span>Blood: <span class="text-muted">Unknown</span></span>';
}

if (!empty($patient['philhealth_no'])) {
    $demographicsItems[] = '<span>PhilHealth: <strong class="text-dark font-monospace">' . h($patient['philhealth_no']) . '</strong></span>';
}

require dirname(__DIR__) . '/layout/header.php';
?>

<style>
.ihp-section-card {
    scroll-margin-top: 5.5rem;
}
.hover-primary:hover {
    background-color: var(--color-primary-soft) !important;
    color: var(--color-primary) !important;
    border-color: var(--color-primary-light) !important;
}
</style>

<div class="container-fluid py-4">
    <!-- Page Header & Action Controls -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-dark">
                <?= h($actionTitle) ?>
            </h2>
            <p class="text-secondary small mb-0">
                <?= h($actionSubtitle) ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="confirmCancelIhpEdit()">
                <i class="bi bi-arrow-left me-1"></i> Back to Patient Record
            </button>
        </div>
    </div>

    <!-- Flash Error Alert -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-xs mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                <div class="flex-grow-1">
                    <strong>Validation Error:</strong> <?= h($_SESSION['error_message']) ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">

            <!-- Patient Master Header (Identical to Clinical Care Workstation Design) -->
            <div class="card card-premium shadow-sm border-0 mb-4">
                <div class="card-body p-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                        
                        <!-- Left: Avatar, Name, Badges & Baseline Demographics -->
                        <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                            <div class="avatar-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle shadow-xs flex-shrink-0" style="width: 48px; height: 48px; font-size: 1.15rem;">
                                <?= h($initials) ?>
                            </div>

                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h3 class="h5 fw-bold text-primary-dark mb-0 lh-1">
                                        <?= h($fullNameFormatted) ?>
                                    </h3>
                                    <span class="badge bg-light text-dark border font-monospace fs-7">
                                        <?= h($patient['patient_no'] ?? 'N/A') ?>
                                    </span>
                                    <?php if (!empty($patient['envelope_no'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-7" title="Physical Logbook Envelope No.">
                                            Env #<?= h($patient['envelope_no']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($patient['family_no'])): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace fs-7" title="Family No.">
                                            Fam #<?= h($patient['family_no']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($activePrenatal)): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-7">
                                            <i class="bi bi-heart-pulse-fill me-1"></i> Pregnant
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-2 small text-secondary">
                                    <?= implode('<span class="text-muted">&bull;</span>', $demographicsItems) ?>
                                </div>

                                <?php if (!empty($cdsAlerts['has_alerts'])): ?>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2 pt-2 border-top small">
                                        <span class="text-muted small fw-semibold me-1"><i class="bi bi-shield-exclamation text-danger me-1"></i>Clinical Alerts:</span>
                                        <?php foreach ($cdsAlerts['flags'] as $flag): ?>
                                            <span class="badge <?= $flag['class'] ?> px-2 py-1">
                                                <i class="bi <?= $flag['icon'] ?> me-1"></i><?= h($flag['label']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right: Context link back to profile -->
                        <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto ms-lg-0 no-print">
                            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs text-nowrap" onclick="confirmCancelIhpEdit()" title="Return to Clinical Care Workstation">
                                <i class="bi bi-person-badge me-1"></i> View Patient Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Section Jump Bar -->
            <div class="sticky-top bg-white border rounded-3 p-2 shadow-xs mb-4" style="top: 1rem; z-index: 1020;" id="ihpSectionNav">
                <div class="d-flex align-items-center gap-1.5 overflow-auto py-1 px-1 text-nowrap">
                    <span class="text-muted fw-semibold small me-1 d-flex align-items-center flex-shrink-0">
                        <i class="bi bi-compass me-1"></i> Jump:
                    </span>
                    <a href="#ihp-sec-pmh" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2.5 hover-primary flex-shrink-0">1. Past Medical</a>
                    <a href="#ihp-sec-family" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2.5 hover-primary flex-shrink-0">2. Family Heredity</a>
                    <a href="#ihp-sec-surgical" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2.5 hover-primary flex-shrink-0">3. Surgeries</a>
                    <a href="#ihp-sec-lifestyle" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2.5 hover-primary flex-shrink-0">4. Habits</a>
                    <a href="#ihp-sec-imm" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2.5 hover-primary flex-shrink-0">5. Vaccines</a>
                    <a href="#ihp-sec-vitals" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2.5 hover-primary flex-shrink-0">6. Baseline Vitals</a>
                    <a href="#ihp-sec-pe" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2.5 hover-primary flex-shrink-0">7. Physical Exam</a>
                    <?php if ($isFemale): ?>
                        <a href="#ihp-sec-reproductive" class="badge bg-light text-pink border text-decoration-none py-1.5 px-2.5 hover-primary flex-shrink-0">8. Menstrual / FP</a>
                        <a href="#ihp-sec-obstetric" class="badge bg-light text-pink border text-decoration-none py-1.5 px-2.5 hover-primary flex-shrink-0">9. Obstetric Score</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- The Main IHP Form -->
            <form action="<?= url('/patients/' . $patient['id'] . '/ihp/edit') ?>" method="POST" id="ihpForm">
                <?= csrf_field() ?>

                <div class="row g-4">
                    <!-- 1. Past Medical History -->
                    <div class="col-12">
                        <div class="card card-premium shadow-sm border-0 p-4 mb-4 ihp-section-card" id="ihp-sec-pmh">
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mb-3 pb-2.5 border-bottom">
                                <div>
                                    <h5 class="h6 fw-bold text-primary-dark mb-0">
                                        1. Past Medical History
                                    </h5>
                                    <span class="text-muted small">PhilHealth Annex A1: Individual Health Profile illness checklist</span>
                                </div>
                            </div>

                            <?php
                            // Condition states and values for PMH
                            $pmhAllergyChecked = (isset($pmhSaved['Allergy']) || in_array('Allergy', $pmhSaved) || isset($pmhSaved['Allergies']) || in_array('Allergies', $pmhSaved));
                            $pmhAllergyVal = is_array($pmhSaved) ? ($pmhSaved['Allergy'] ?? $pmhSaved['Allergies'] ?? '') : '';
                            if ($pmhAllergyVal === 'Yes' || $pmhAllergyVal === '1') $pmhAllergyVal = '';

                            $pmhHtnChecked = (isset($pmhSaved['Hypertension']) || in_array('Hypertension', $pmhSaved));
                            $pmhHtnVal = is_array($pmhSaved) ? str_replace('Highest BP: ', '', $pmhSaved['Hypertension'] ?? '') : '';
                            if ($pmhHtnVal === 'Yes' || $pmhHtnVal === '1') $pmhHtnVal = '';

                            $pmhCancerChecked = (isset($pmhSaved['Cancer']) || in_array('Cancer', $pmhSaved));
                            $pmhCancerVal = is_array($pmhSaved) ? ($pmhSaved['Cancer'] ?? '') : '';
                            if ($pmhCancerVal === 'Yes' || $pmhCancerVal === '1') $pmhCancerVal = '';

                            $pmhHepChecked = (isset($pmhSaved['Hepatitis']) || in_array('Hepatitis', $pmhSaved));
                            $pmhHepVal = is_array($pmhSaved) ? ($pmhSaved['Hepatitis'] ?? '') : '';
                            if ($pmhHepVal === 'Yes' || $pmhHepVal === '1') $pmhHepVal = '';

                            $pmhPtbChecked = (isset($pmhSaved['Pulmonary Tuberculosis (PTB)']) || isset($pmhSaved['PTB']) || isset($pmhSaved['Tuberculosis']) || in_array('Pulmonary Tuberculosis (PTB)', $pmhSaved) || in_array('PTB', $pmhSaved) || in_array('Tuberculosis', $pmhSaved));
                            $pmhTbOrganVal = is_array($pmhSaved) ? ($pmhSaved['Tuberculosis'] ?? '') : '';
                            if ($pmhTbOrganVal === 'Yes' || $pmhTbOrganVal === '1') $pmhTbOrganVal = '';
                            $pmhPtbCatVal = is_array($pmhSaved) ? ($pmhSaved['Pulmonary Tuberculosis (PTB)'] ?? $pmhSaved['PTB'] ?? '') : '';
                            if ($pmhPtbCatVal === 'Yes' || $pmhPtbCatVal === '1') $pmhPtbCatVal = '';

                            $pmhOtherChecked = (isset($pmhSaved['Others']) || in_array('Others', $pmhSaved));
                            $pmhOtherVal = is_array($pmhSaved) ? ($pmhSaved['Others'] ?? '') : '';
                            if ($pmhOtherVal === 'Yes' || $pmhOtherVal === '1') $pmhOtherVal = '';
                            ?>

                            <!-- Conditions with Specific Clinical Details -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                        Conditions Requiring Clinical Specifics
                                    </span>
                                    <span class="text-muted" style="font-size: 0.72rem;">
                                        Check condition to unlock detail input
                                    </span>
                                </div>
                                <div class="row g-3 small">
                                    <!-- Allergy -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $pmhAllergyChecked ? 'active-condition' : '' ?>" data-parent-check="#pmh_allergy">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="past_medical_history[]" value="Allergy" id="pmh_allergy" data-target="#pmh_allergy_specifics" <?= $pmhAllergyChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="pmh_allergy">
                                                        Allergy
                                                    </label>
                                                </div>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Specifics</span>
                                            </div>
                                            <div class="mt-1">
                                                <input type="text" id="pmh_allergy_specifics" name="allergy_specifics" class="form-control form-control-sm ihp-specifics-input <?= $pmhAllergyChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Specify allergens (e.g. Penicillin, Seafood, Dust)" value="<?= h($pmhAllergyVal) ?>" <?= $pmhAllergyChecked ? '' : 'disabled' ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hypertension -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $pmhHtnChecked ? 'active-condition' : '' ?>" data-parent-check="#pmh_hypertension">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="past_medical_history[]" value="Hypertension" id="pmh_hypertension" data-target="#pmh_hypertension_highest_bp" <?= $pmhHtnChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="pmh_hypertension">
                                                        Hypertension
                                                    </label>
                                                </div>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Highest BP</span>
                                            </div>
                                            <div class="mt-1">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light text-secondary px-2.5 fw-medium" style="font-size: 0.75rem;">Highest BP</span>
                                                    <input type="text" id="pmh_hypertension_highest_bp" name="hypertension_highest_bp" class="form-control form-control-sm ihp-specifics-input <?= $pmhHtnChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="e.g. 160/100 mmHg" value="<?= h($pmhHtnVal) ?>" <?= $pmhHtnChecked ? '' : 'disabled' ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cancer -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $pmhCancerChecked ? 'active-condition' : '' ?>" data-parent-check="#pmh_cancer">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="past_medical_history[]" value="Cancer" id="pmh_cancer" data-target="#pmh_cancer_organ" <?= $pmhCancerChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="pmh_cancer">
                                                        Cancer
                                                    </label>
                                                </div>
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Organ Site</span>
                                            </div>
                                            <div class="mt-1">
                                                <input type="text" id="pmh_cancer_organ" name="cancer_organ" class="form-control form-control-sm ihp-specifics-input <?= $pmhCancerChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Specify organ (e.g. Breast, Colon, Cervix)" value="<?= h($pmhCancerVal) ?>" <?= $pmhCancerChecked ? '' : 'disabled' ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hepatitis -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $pmhHepChecked ? 'active-condition' : '' ?>" data-parent-check="#pmh_hepatitis">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="past_medical_history[]" value="Hepatitis" id="pmh_hepatitis" data-target="#pmh_hepatitis_type" <?= $pmhHepChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="pmh_hepatitis">
                                                        Hepatitis
                                                    </label>
                                                </div>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Viral Type</span>
                                            </div>
                                            <div class="mt-1">
                                                <input type="text" id="pmh_hepatitis_type" name="hepatitis_type" class="form-control form-control-sm ihp-specifics-input <?= $pmhHepChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Specify type (e.g. Hepatitis B, Hepatitis A)" value="<?= h($pmhHepVal) ?>" <?= $pmhHepChecked ? '' : 'disabled' ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tuberculosis & PTB Category -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $pmhPtbChecked ? 'active-condition' : '' ?>" data-parent-check="#pmh_ptb">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="past_medical_history[]" value="Pulmonary Tuberculosis (PTB)" id="pmh_ptb" data-target="#pmh_tb_organ,#pmh_ptb_cat" <?= $pmhPtbChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="pmh_ptb">
                                                        Tuberculosis / PTB
                                                    </label>
                                                </div>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Organ & Category</span>
                                            </div>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <input type="text" id="pmh_tb_organ" name="tuberculosis_organ" class="form-control form-control-sm ihp-specifics-input <?= $pmhPtbChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Organ (e.g. Lungs, Spine)" value="<?= h($pmhTbOrganVal) ?>" <?= $pmhPtbChecked ? '' : 'disabled' ?>>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" id="pmh_ptb_cat" name="ptb_details" class="form-control form-control-sm ihp-specifics-input <?= $pmhPtbChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="PTB Category (e.g. Cat 1, Cat 2)" value="<?= h($pmhPtbCatVal) ?>" <?= $pmhPtbChecked ? '' : 'disabled' ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Others -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $pmhOtherChecked ? 'active-condition' : '' ?>" data-parent-check="#pmh_others">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="past_medical_history[]" value="Others" id="pmh_others" data-target="#pmh_others_specify" <?= $pmhOtherChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="pmh_others">
                                                        Others
                                                    </label>
                                                </div>
                                                <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Specify Illness</span>
                                            </div>
                                            <div class="mt-1">
                                                <input type="text" id="pmh_others_specify" name="pmh_other_specify" class="form-control form-control-sm ihp-specifics-input <?= $pmhOtherChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Specify other illnesses or chronic conditions..." value="<?= h($pmhOtherVal) ?>" <?= $pmhOtherChecked ? '' : 'disabled' ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Other Chronic & Systemic Illnesses (Annex A1 Checklist) -->
                            <div class="pt-2 border-top">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                        Other Chronic & Systemic Illnesses (Annex A1 Checklist)
                                    </span>
                                    <span class="text-muted" style="font-size: 0.72rem;">Check all that apply to the patient</span>
                                </div>
                                <div class="row g-2 small">
                                    <?php
                                    $pmhGeneral = [
                                        'Asthma' => 'Asthma',
                                        'Cerebrovascular Disease' => 'Cerebrovascular Disease (Stroke)',
                                        'Coronary Artery Disease' => 'Coronary Artery Disease',
                                        'Diabetes Mellitus' => 'Diabetes Mellitus',
                                        'Emphysema' => 'Emphysema / COPD',
                                        'Epilepsy / Seizure Disease' => 'Epilepsy / Seizure Disease',
                                        'Hyperlipidemia' => 'Hyperlipidemia',
                                        'Peptic Ulcer Disease' => 'Peptic Ulcer Disease',
                                        'Pneumonia' => 'Pneumonia',
                                        'Thyroid Disease' => 'Thyroid Disease',
                                        'Urinary Tract Infection' => 'Urinary Tract Infection (UTI)'
                                    ];
                                    foreach ($pmhGeneral as $gKey => $gLabel):
                                        $gChecked = is_array($pmhSaved) && (isset($pmhSaved[$gKey]) || in_array($gKey, $pmhSaved) || ($gKey === 'Cerebrovascular Disease' && in_array('Stroke', $pmhSaved)) || ($gKey === 'Emphysema' && in_array('Emphysema / COPD', $pmhSaved)) || ($gKey === 'Epilepsy / Seizure Disease' && in_array('Epilepsy / Seizure', $pmhSaved)));
                                    ?>
                                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                            <div class="p-2 border rounded-2 bg-light-subtle h-100 d-flex align-items-center ihp-checklist-tile <?= $gChecked ? 'active-condition' : '' ?>">
                                                <div class="form-check mb-0 w-100">
                                                    <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="<?= $gKey ?>" id="pmh_g_<?= md5($gKey) ?>" <?= $gChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-semibold text-dark small cursor-pointer d-block" for="pmh_g_<?= md5($gKey) ?>"><?= $gLabel ?></label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Family History (Hereditary Diseases) -->
                    <div class="col-12">
                        <div class="card card-premium shadow-sm border-0 p-4 mb-4 ihp-section-card" id="ihp-sec-family">
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mb-3 pb-2.5 border-bottom">
                                <div>
                                    <h5 class="h6 fw-bold text-primary-dark mb-0">
                                        2. Family History (Hereditary Diseases)
                                    </h5>
                                    <span class="text-muted small">PhilHealth Annex A1: Hereditary conditions in patient's family</span>
                                </div>
                            </div>

                            <?php
                            // Condition states and values for Family History
                            $famAllergyChecked = (isset($familySaved['Allergy']) || in_array('Allergy', $familySaved) || isset($familySaved['Allergies']) || in_array('Allergies', $familySaved));
                            $famAllergyVal = is_array($familySaved) ? ($familySaved['Allergy'] ?? $familySaved['Allergies'] ?? '') : '';
                            if ($famAllergyVal === 'Yes' || $famAllergyVal === '1') $famAllergyVal = '';

                            $famHtnChecked = (isset($familySaved['Hypertension']) || in_array('Hypertension', $familySaved));
                            $famHtnVal = is_array($familySaved) ? str_replace('Highest BP: ', '', $familySaved['Hypertension'] ?? '') : '';
                            if ($famHtnVal === 'Yes' || $famHtnVal === '1') $famHtnVal = '';

                            $famCancerChecked = (isset($familySaved['Cancer']) || in_array('Cancer', $familySaved));
                            $famCancerVal = is_array($familySaved) ? ($familySaved['Cancer'] ?? '') : '';
                            if ($famCancerVal === 'Yes' || $famCancerVal === '1') $famCancerVal = '';

                            $famHepChecked = (isset($familySaved['Hepatitis']) || in_array('Hepatitis', $familySaved));
                            $famHepVal = is_array($familySaved) ? ($familySaved['Hepatitis'] ?? '') : '';
                            if ($famHepVal === 'Yes' || $famHepVal === '1') $famHepVal = '';

                            $famPtbChecked = (isset($familySaved['Tuberculosis']) || isset($familySaved['PTB Category']) || isset($familySaved['Pulmonary Tuberculosis (PTB)']) || in_array('Tuberculosis', $familySaved) || in_array('PTB Category', $familySaved) || in_array('Pulmonary Tuberculosis (PTB)', $familySaved));
                            $famTbOrganVal = is_array($familySaved) ? ($familySaved['Tuberculosis'] ?? '') : '';
                            if ($famTbOrganVal === 'Yes' || $famTbOrganVal === '1') $famTbOrganVal = '';
                            $famPtbCatVal = is_array($familySaved) ? ($familySaved['PTB Category'] ?? $familySaved['Pulmonary Tuberculosis (PTB)'] ?? '') : '';
                            if ($famPtbCatVal === 'Yes' || $famPtbCatVal === '1') $famPtbCatVal = '';

                            $famOtherChecked = (isset($familySaved['Others']) || in_array('Others', $familySaved));
                            $famOtherVal = is_array($familySaved) ? ($familySaved['Others'] ?? '') : '';
                            if ($famOtherVal === 'Yes' || $famOtherVal === '1') $famOtherVal = '';
                            ?>

                            <!-- Hereditary Conditions with Required Specifics -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                        Hereditary Conditions Requiring Specifics
                                    </span>
                                    <span class="text-muted" style="font-size: 0.72rem;">
                                        Check condition to unlock detail input
                                    </span>
                                </div>
                                <div class="row g-3 small">
                                    <!-- Allergy -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $famAllergyChecked ? 'active-condition' : '' ?>" data-parent-check="#fam_allergy">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="family_history[]" value="Allergy" id="fam_allergy" data-target="#fam_allergy_specifics" <?= $famAllergyChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="fam_allergy">
                                                        Allergy
                                                    </label>
                                                </div>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Specifics</span>
                                            </div>
                                            <div class="mt-1">
                                                <input type="text" id="fam_allergy_specifics" name="fam_allergy_specifics" class="form-control form-control-sm ihp-specifics-input <?= $famAllergyChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Specify allergens (e.g. Asthma, Eczema, Food)" value="<?= h($famAllergyVal) ?>" <?= $famAllergyChecked ? '' : 'disabled' ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hypertension -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $famHtnChecked ? 'active-condition' : '' ?>" data-parent-check="#fam_hypertension">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="family_history[]" value="Hypertension" id="fam_hypertension" data-target="#fam_hypertension_highest_bp" <?= $famHtnChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="fam_hypertension">
                                                        Hypertension
                                                    </label>
                                                </div>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Highest BP</span>
                                            </div>
                                            <div class="mt-1">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light text-secondary px-2.5 fw-medium" style="font-size: 0.75rem;">Highest BP</span>
                                                    <input type="text" id="fam_hypertension_highest_bp" name="fam_hypertension_highest_bp" class="form-control form-control-sm ihp-specifics-input <?= $famHtnChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="e.g. 180/100, Stroke" value="<?= h($famHtnVal) ?>" <?= $famHtnChecked ? '' : 'disabled' ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cancer -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $famCancerChecked ? 'active-condition' : '' ?>" data-parent-check="#fam_cancer">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="family_history[]" value="Cancer" id="fam_cancer" data-target="#fam_cancer_organ" <?= $famCancerChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="fam_cancer">
                                                        Cancer
                                                    </label>
                                                </div>
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Organ Site</span>
                                            </div>
                                            <div class="mt-1">
                                                <input type="text" id="fam_cancer_organ" name="fam_cancer_organ" class="form-control form-control-sm ihp-specifics-input <?= $famCancerChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Specify organ (e.g. Breast, Colon)" value="<?= h($famCancerVal) ?>" <?= $famCancerChecked ? '' : 'disabled' ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hepatitis -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $famHepChecked ? 'active-condition' : '' ?>" data-parent-check="#fam_hepatitis">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="family_history[]" value="Hepatitis" id="fam_hepatitis" data-target="#fam_hepatitis_type" <?= $famHepChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="fam_hepatitis">
                                                        Hepatitis
                                                    </label>
                                                </div>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Viral Type</span>
                                            </div>
                                            <div class="mt-1">
                                                <input type="text" id="fam_hepatitis_type" name="fam_hepatitis_type" class="form-control form-control-sm ihp-specifics-input <?= $famHepChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Specify type (e.g. Hepatitis B)" value="<?= h($famHepVal) ?>" <?= $famHepChecked ? '' : 'disabled' ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tuberculosis & PTB Category -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $famPtbChecked ? 'active-condition' : '' ?>" data-parent-check="#fam_ptb">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="family_history[]" value="Tuberculosis" id="fam_ptb" data-target="#fam_tb_organ,#fam_ptb_cat" <?= $famPtbChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="fam_ptb">
                                                        Tuberculosis / PTB
                                                    </label>
                                                </div>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Organ & Category</span>
                                            </div>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <input type="text" id="fam_tb_organ" name="fam_tuberculosis_organ" class="form-control form-control-sm ihp-specifics-input <?= $famPtbChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Organ (e.g. Pulmonary)" value="<?= h($famTbOrganVal) ?>" <?= $famPtbChecked ? '' : 'disabled' ?>>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" id="fam_ptb_cat" name="fam_ptb_details" class="form-control form-control-sm ihp-specifics-input <?= $famPtbChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="PTB Category (e.g. Active)" value="<?= h($famPtbCatVal) ?>" <?= $famPtbChecked ? '' : 'disabled' ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Others -->
                                    <div class="col-12 col-md-6">
                                        <div class="ihp-condition-box h-100 <?= $famOtherChecked ? 'active-condition' : '' ?>" data-parent-check="#fam_others">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input condition-toggle" type="checkbox" name="family_history[]" value="Others" id="fam_others" data-target="#fam_others_specify" <?= $famOtherChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="fam_others">
                                                        Others
                                                    </label>
                                                </div>
                                                <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.7rem; font-weight: 600;">Specify Illness</span>
                                            </div>
                                            <div class="mt-1">
                                                <input type="text" id="fam_others_specify" name="family_other" class="form-control form-control-sm ihp-specifics-input <?= $famOtherChecked ? 'bg-white' : 'bg-light text-muted' ?>" placeholder="Specify other hereditary illnesses..." value="<?= h($famOtherVal) ?>" <?= $famOtherChecked ? '' : 'disabled' ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Other Hereditary Illnesses (Annex A1 Checklist) -->
                            <div class="pt-2 border-top">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                        Other Hereditary & Familial Conditions (Annex A1 Checklist)
                                    </span>
                                    <span class="text-muted" style="font-size: 0.72rem;">Check all that run in patient's family</span>
                                </div>
                                <div class="row g-2 small">
                                    <?php
                                    $familyGeneral = [
                                        'Asthma' => 'Asthma',
                                        'Cerebrovascular Disease' => 'Cerebrovascular Disease (Stroke)',
                                        'Coronary Artery Disease' => 'Coronary Artery Disease',
                                        'Diabetes Mellitus' => 'Diabetes Mellitus',
                                        'Emphysema' => 'Emphysema / COPD',
                                        'Epilepsy / Seizure Disease' => 'Epilepsy / Seizure Disease',
                                        'Hyperlipidemia' => 'Hyperlipidemia',
                                        'Kidney Disease' => 'Kidney Disease',
                                        'Mental Disorder' => 'Mental Disorder',
                                        'Peptic Ulcer Disease' => 'Peptic Ulcer Disease',
                                        'Pneumonia' => 'Pneumonia',
                                        'Thyroid Disease' => 'Thyroid Disease'
                                    ];
                                    foreach ($familyGeneral as $fKey => $fLabel):
                                        $fChecked = is_array($familySaved) && (isset($familySaved[$fKey]) || in_array($fKey, $familySaved));
                                    ?>
                                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                            <div class="p-2 border rounded-2 bg-light-subtle h-100 d-flex align-items-center ihp-checklist-tile <?= $fChecked ? 'active-condition' : '' ?>">
                                                <div class="form-check mb-0 w-100">
                                                    <input class="form-check-input" type="checkbox" name="family_history[]" value="<?= $fKey ?>" id="fam_g_<?= md5($fKey) ?>" <?= $fChecked ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-semibold text-dark small cursor-pointer d-block" for="fam_g_<?= md5($fKey) ?>"><?= $fLabel ?></label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Past Surgical History & Hospitalization -->
                    <div class="col-12 col-lg-6">
                        <div class="card card-premium shadow-sm border-0 p-4 h-100 mb-4 ihp-section-card" id="ihp-sec-surgical">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2.5 border-bottom">
                                <div>
                                    <h5 class="h6 fw-bold text-primary-dark mb-0">
                                        3. Past Surgical History
                                    </h5>
                                    <span class="text-muted small">Hospital operations & procedures</span>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm fw-medium shadow-xs" id="btnAddSurgeryRow" onclick="addIhpSurgeryRow()">
                                    <i class="bi bi-plus-circle me-1"></i> Add Surgery
                                </button>
                            </div>
                            <div id="ihpSurgeriesContainer" class="d-flex flex-column gap-2 mb-2">
                                <?php 
                                $hasSurgeryRows = false;
                                if (!empty($surgicalSaved) && is_array($surgicalSaved)): 
                                    $sIdx = 0;
                                    foreach ($surgicalSaved as $surg): 
                                        $opName = is_array($surg) ? ($surg['operation'] ?? '') : (string)$surg;
                                        $opDate = is_array($surg) ? ($surg['date'] ?? '') : '';
                                        $opHosp = is_array($surg) ? ($surg['hospital'] ?? '') : '';
                                        if (empty($opName) && empty($opDate) && empty($opHosp)) continue;
                                        $hasSurgeryRows = true;
                                ?>
                                        <div class="ihp-surgery-row p-2.5 border rounded-2 bg-light-subtle position-relative">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-12 col-sm-5">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation / Procedure</label>
                                                    <input type="text" name="surgical_procedures[<?= $sIdx ?>][operation]" class="form-control form-control-sm bg-white" placeholder="e.g. Appendectomy" value="<?= h($opName) ?>" required>
                                                </div>
                                                <div class="col-6 col-sm-3">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Date / Year</label>
                                                    <input type="text" name="surgical_procedures[<?= $sIdx ?>][date]" class="form-control form-control-sm bg-white" placeholder="YYYY or Date" value="<?= h($opDate) ?>">
                                                </div>
                                                <div class="col-6 col-sm-3">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Hospital / Clinic</label>
                                                    <input type="text" name="surgical_procedures[<?= $sIdx ?>][hospital]" class="form-control form-control-sm bg-white" placeholder="e.g. Health Center" value="<?= h($opHosp) ?>">
                                                </div>
                                                <div class="col-12 col-sm-1 text-end text-sm-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeIhpSurgeryRow(this)" title="Remove procedure">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                <?php 
                                        $sIdx++;
                                    endforeach; 
                                endif; 
                                ?>
                            </div>
                            <div id="ihpSurgeriesEmpty" class="text-muted small p-3 bg-light rounded text-center <?= $hasSurgeryRows ? 'd-none' : '' ?>">
                                <i class="bi bi-journal-medical fs-5 d-block mb-1 text-secondary"></i>
                                No surgical procedures recorded. Click <strong>+ Add Surgery</strong> if the patient has past operations.
                            </div>
                        </div>
                    </div>

                    <!-- 4. Personal / Social History -->
                    <div class="col-12 col-lg-6">
                        <div class="card card-premium shadow-sm border-0 p-4 h-100 mb-4 ihp-section-card" id="ihp-sec-lifestyle">
                            <div class="mb-3 pb-2.5 border-bottom">
                                <h5 class="h6 fw-bold text-primary-dark mb-0">
                                    4. Personal / Social History
                                </h5>
                                <span class="text-muted small">Smoking, alcohol drinking, and substance use</span>
                            </div>
                            <div class="row g-2.5 small">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1" for="smoking_status">Smoking Status</label>
                                    <select name="smoking_status" id="smoking_status" class="form-select form-select-sm">
                                        <option value="Never" <?= ($medicalHistory['smoking_status'] ?? 'Never') === 'Never' ? 'selected' : '' ?>>Never (No)</option>
                                        <option value="Yes" <?= ($medicalHistory['smoking_status'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes (Active)</option>
                                        <option value="Quit" <?= ($medicalHistory['smoking_status'] ?? '') === 'Quit' ? 'selected' : '' ?>>Quit</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1" for="smoking_pack_years">No. of Pack Years</label>
                                    <input type="number" step="0.1" name="smoking_pack_years" id="smoking_pack_years" class="form-control form-control-sm <?= ($medicalHistory['smoking_status'] ?? 'Never') === 'Never' ? 'bg-light text-muted' : 'bg-white' ?>" placeholder="e.g. 5.0" value="<?= h($medicalHistory['smoking_pack_years'] ?? '') ?>" <?= ($medicalHistory['smoking_status'] ?? 'Never') === 'Never' ? 'disabled' : '' ?>>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1" for="alcohol_status">Alcohol Drinking</label>
                                    <select name="alcohol_status" id="alcohol_status" class="form-select form-select-sm">
                                        <option value="Never" <?= ($medicalHistory['alcohol_status'] ?? 'Never') === 'Never' ? 'selected' : '' ?>>Never (No)</option>
                                        <option value="Yes" <?= ($medicalHistory['alcohol_status'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes (Regular/Occasional)</option>
                                        <option value="Quit" <?= ($medicalHistory['alcohol_status'] ?? '') === 'Quit' ? 'selected' : '' ?>>Quit</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1" for="alcohol_bottles_per_day">No. of Bottles / Day</label>
                                    <input type="number" step="0.1" name="alcohol_bottles_per_day" id="alcohol_bottles_per_day" class="form-control form-control-sm <?= ($medicalHistory['alcohol_status'] ?? 'Never') === 'Never' ? 'bg-light text-muted' : 'bg-white' ?>" placeholder="e.g. 2.0" value="<?= h($medicalHistory['alcohol_bottles_per_day'] ?? '') ?>" <?= ($medicalHistory['alcohol_status'] ?? 'Never') === 'Never' ? 'disabled' : '' ?>>
                                </div>

                                <div class="col-12">
                                    <div class="form-check mt-1 p-2 bg-light-subtle rounded border">
                                        <input class="form-check-input ms-1" type="checkbox" name="illicit_drugs" value="1" id="illicit_drugs" <?= !empty($medicalHistory['illicit_drugs']) ? 'checked' : '' ?>>
                                        <label class="form-check-label text-dark fw-semibold small cursor-pointer ms-2" for="illicit_drugs">History of Illicit Drug Use</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Lifetime Immunizations (Annex A1) -->
                    <div class="col-12 col-lg-6">
                        <div class="card card-premium shadow-sm border-0 p-4 h-100 mb-4 ihp-section-card" id="ihp-sec-imm">
                            <div class="mb-3 pb-2.5 border-bottom">
                                <h5 class="h6 fw-bold text-primary-dark mb-0">
                                    5. Lifetime Immunizations (Annex A1)
                                </h5>
                                <span class="text-muted small">PhilHealth Annex A1 age-specific vaccination history</span>
                            </div>
                            <div class="small">
                                <!-- For children -->
                                <label class="fw-semibold text-secondary small d-block mb-1">For Children:</label>
                                <div class="row g-1 mb-2">
                                    <?php 
                                    $childVax = ['BCG', 'OPV1 / IPV1', 'OPV2 / IPV2', 'OPV3 / IPV3', 'DPT1', 'DPT2', 'DPT3', 'Measles', 'Hepatitis B1', 'Hepatitis B2', 'Hepatitis B3', 'Hepatitis A', 'Varicella (Chicken Pox)'];
                                    $savedChild = $immSaved['children'] ?? [];
                                    foreach ($childVax as $cv): 
                                    ?>
                                        <div class="col-6 col-sm-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="imm_children[]" value="<?= $cv ?>" id="imm_c_<?= md5($cv) ?>" <?= in_array($cv, $savedChild, true) ? 'checked' : '' ?>>
                                                <label class="form-check-label text-dark" for="imm_c_<?= md5($cv) ?>" style="font-size: 0.75rem;"><?= $cv ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- For young women -->
                                <label class="fw-semibold text-secondary small d-block mb-1 border-top pt-2">For Young Women:</label>
                                <div class="row g-1 mb-2">
                                    <?php 
                                    $ywVax = ['HPV', 'MMR'];
                                    $savedYw = $immSaved['young_women'] ?? [];
                                    foreach ($ywVax as $yv): 
                                    ?>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="imm_young_women[]" value="<?= $yv ?>" id="imm_yw_<?= md5($yv) ?>" <?= in_array($yv, $savedYw, true) ? 'checked' : '' ?>>
                                                <label class="form-check-label text-dark" for="imm_yw_<?= md5($yv) ?>" style="font-size: 0.75rem;"><?= $yv ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- For pregnant women -->
                                <label class="fw-semibold text-secondary small d-block mb-1 border-top pt-2">For Pregnant Women:</label>
                                <div class="row g-1 mb-2">
                                    <?php 
                                    $pregVax = ['Tetanus Toxoid'];
                                    $savedPreg = $immSaved['pregnant'] ?? [];
                                    foreach ($pregVax as $pv): 
                                    ?>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="imm_pregnant[]" value="<?= $pv ?>" id="imm_p_<?= md5($pv) ?>" <?= in_array($pv, $savedPreg, true) ? 'checked' : '' ?>>
                                                <label class="form-check-label text-dark" for="imm_p_<?= md5($pv) ?>" style="font-size: 0.75rem;"><?= $pv ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- For elderly and immunocompromised -->
                                <label class="fw-semibold text-secondary small d-block mb-1 border-top pt-2">For Elderly & Immunocompromised:</label>
                                <div class="row g-1 mb-2">
                                    <?php 
                                    $eldVax = ['Pneumococcal Vaccine', 'Flu Vaccine'];
                                    $savedEld = $immSaved['elderly'] ?? [];
                                    foreach ($eldVax as $ev): 
                                    ?>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="imm_elderly[]" value="<?= $ev ?>" id="imm_e_<?= md5($ev) ?>" <?= in_array($ev, $savedEld, true) ? 'checked' : '' ?>>
                                                <label class="form-check-label text-dark" for="imm_e_<?= md5($ev) ?>" style="font-size: 0.75rem;"><?= $ev ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Others specify -->
                                <div class="border-top pt-2">
                                    <label class="form-label fw-semibold text-secondary small mb-1">Others (Specify)</label>
                                    <input type="text" name="imm_others_specify" class="form-control form-control-sm" placeholder="Specify other vaccines received..." value="<?= h($immSaved['others'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Baseline Vitals & Anthropometrics (Annex A1) -->
                    <div class="col-12 col-lg-6">
                        <div class="card card-premium shadow-sm border-0 p-4 h-100 mb-4 ihp-section-card" id="ihp-sec-vitals">
                            <div class="mb-3 pb-2.5 border-bottom">
                                <h5 class="h6 fw-bold text-primary-dark mb-0">
                                    6. Baseline Vitals & Anthropometrics
                                </h5>
                                <span class="text-muted small">Baseline vitals with live WHO Asian BMI classification</span>
                            </div>
                            <div class="row g-2.5 small">
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small mb-1">Baseline Blood Pressure</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="baseline_bp_systolic" id="ihp_baseline_bp_systolic" class="form-control" placeholder="Systolic (e.g. 120)" min="50" max="300" value="<?= h($defaultBpSystolic) ?>">
                                        <span class="input-group-text">/</span>
                                        <input type="number" name="baseline_bp_diastolic" id="ihp_baseline_bp_diastolic" class="form-control" placeholder="Diastolic (e.g. 80)" min="30" max="200" value="<?= h($defaultBpDiastolic) ?>">
                                        <span class="input-group-text">mmHg</span>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1">Heart Rate</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="baseline_heart_rate" class="form-control" placeholder="e.g. 72" min="30" max="250" value="<?= h($defaultHeartRate) ?>">
                                        <span class="input-group-text">bpm</span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1">Respiratory Rate</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="baseline_respiratory_rate" class="form-control" placeholder="e.g. 18" min="8" max="60" value="<?= h($defaultRespiratoryRate) ?>">
                                        <span class="input-group-text">cpm</span>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1" for="ihp_baseline_height">Height</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.1" name="baseline_height" id="ihp_baseline_height" class="form-control" placeholder="e.g. 165" min="30" max="250" value="<?= h($defaultHeight) ?>">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1" for="ihp_baseline_weight">Weight</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.1" name="baseline_weight" id="ihp_baseline_weight" class="form-control" placeholder="e.g. 60" min="1" max="300" value="<?= h($defaultWeight) ?>">
                                        <span class="input-group-text">kg</span>
                                    </div>
                                </div>

                                <!-- Real-Time Baseline BMI Calculator & WHO Asian Classification -->
                                <div class="col-12">
                                    <div class="p-2.5 border rounded-2 bg-light-subtle d-flex align-items-center justify-content-between flex-wrap gap-2" id="ihpBmiWrapper">
                                        <div>
                                            <span class="text-secondary small fw-semibold d-block">
                                                Calculated Baseline BMI:
                                            </span>
                                            <span class="fw-bold text-dark fs-6" id="ihpBmiValue">
                                                <?php
                                                $h = !empty($defaultHeight) ? (float)$defaultHeight : 0;
                                                $w = !empty($defaultWeight) ? (float)$defaultWeight : 0;
                                                $initBmi = ($h > 0 && $w > 0) ? round($w / (($h / 100) * ($h / 100)), 2) : 0;
                                                echo $initBmi > 0 ? $initBmi . ' kg/m²' : '<span class="text-muted fs-7 fw-normal">Enter height & weight</span>';
                                                ?>
                                            </span>
                                        </div>
                                        <div id="ihpBmiBadgeContainer" class="d-flex align-items-center gap-1.5">
                                            <?php if ($initBmi > 0): 
                                                $bClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
                                                $bCat = 'Normal';
                                                if ($initBmi < 18.5) { 
                                                    $bClass = 'bg-info-subtle text-info border border-info-subtle'; 
                                                    $bCat = 'Underweight'; 
                                                } elseif ($initBmi <= 22.9) { 
                                                    $bClass = 'bg-success-subtle text-success border border-success-subtle'; 
                                                    $bCat = 'Normal'; 
                                                } elseif ($initBmi <= 27.4) { 
                                                    $bClass = 'bg-warning-subtle text-dark border border-warning-subtle'; 
                                                    $bCat = 'Overweight'; 
                                                } else { 
                                                    $bClass = 'bg-danger-subtle text-danger border border-danger-subtle'; 
                                                    $bCat = 'Obese'; 
                                                }
                                            ?>
                                                <span class="badge <?= $bClass ?> px-2.5 py-1.5 fs-7" id="ihpBmiBadge"><?= $bCat ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border px-2.5 py-1.5 fs-7" id="ihpBmiBadge">—</span>
                                            <?php endif; ?>
                                            <span class="text-muted" style="font-size: 0.72rem;">(WHO Asian Criteria)</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small mb-1">Waist Circumference</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.1" name="baseline_waist_circumference" class="form-control" placeholder="e.g. 78" min="20" max="200" value="<?= h($defaultWaist) ?>">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Pertinent Physical Examination Findings Checklist (Annex A1) -->
                    <div class="col-12">
                        <div class="card card-premium shadow-sm border-0 p-4 mb-4 ihp-section-card" id="ihp-sec-pe">
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3 pb-2.5 border-bottom">
                                <div>
                                    <h5 class="h6 fw-bold text-primary-dark mb-0">
                                        7. Pertinent Physical Examination Checklist (PhilHealth Annex A1)
                                    </h5>
                                    <span class="text-muted small">Rapid body-systems organ checklist. Mark abnormalities systematically.</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-success btn-sm px-3 fw-semibold shadow-xs d-inline-flex align-items-center" id="btnMarkAllNormal">
                                        <i class="bi bi-check-all me-1"></i> Mark All Unremarkable / Normal
                                    </button>
                                </div>
                            </div>

                            <?php
                            $peConfig = [
                                'skin' => [
                                    'title' => 'Skin / Integument',
                                    'badge_id' => 'pe_badge_skin',
                                    'normal_text' => 'Good Turgor',
                                    'normal_vals' => ['Good skin turgor'],
                                    'acute_vals' => ['Pallor', 'Rashes', 'Jaundice'],
                                    'items' => [
                                        ['val' => 'Pallor', 'label' => 'Pallor / Conjunctival Bleaching', 'normal' => false, 'acute' => true],
                                        ['val' => 'Rashes', 'label' => 'Rashes / Active Lesions', 'normal' => false, 'acute' => true],
                                        ['val' => 'Jaundice', 'label' => 'Jaundice / Icterus', 'normal' => false, 'acute' => true],
                                        ['val' => 'Good skin turgor', 'label' => 'Good Skin Turgor (Normal)', 'normal' => true, 'acute' => false],
                                    ]
                                ],
                                'heent' => [
                                    'title' => 'HEENT',
                                    'badge_id' => 'pe_badge_heent',
                                    'normal_text' => 'Normal',
                                    'normal_vals' => ['Anicteric sclerae', 'Pupils briskly reactive to light', 'Intact tympanic membrane'],
                                    'acute_vals' => ['Tonsillopharyngeal congestion', 'Exudates', 'Hypertrophic tonsils', 'Alar flaring', 'Nasal discharge', 'Aural discharge', 'Palpable mass'],
                                    'items' => [
                                        ['val' => 'Anicteric sclerae', 'label' => 'Anicteric Sclerae', 'normal' => true, 'acute' => false],
                                        ['val' => 'Pupils briskly reactive to light', 'label' => 'Pupils Briskly Reactive (PERRLA)', 'normal' => true, 'acute' => false],
                                        ['val' => 'Intact tympanic membrane', 'label' => 'Intact Tympanic Membrane', 'normal' => true, 'acute' => false],
                                        ['val' => 'Tonsillopharyngeal congestion', 'label' => 'Tonsillopharyngeal Congestion', 'normal' => false, 'acute' => true],
                                        ['val' => 'Exudates', 'label' => 'Exudates', 'normal' => false, 'acute' => true],
                                        ['val' => 'Hypertrophic tonsils', 'label' => 'Hypertrophic Tonsils', 'normal' => false, 'acute' => true],
                                        ['val' => 'Alar flaring', 'label' => 'Alar Flaring', 'normal' => false, 'acute' => true],
                                        ['val' => 'Nasal discharge', 'label' => 'Nasal Discharge', 'normal' => false, 'acute' => true],
                                        ['val' => 'Aural discharge', 'label' => 'Aural Discharge', 'normal' => false, 'acute' => true],
                                        ['val' => 'Palpable mass', 'label' => 'Palpable Cervical Mass', 'normal' => false, 'acute' => true],
                                    ]
                                ],
                                'chest_lungs' => [
                                    'title' => 'Chest & Lungs',
                                    'badge_id' => 'pe_badge_chest',
                                    'normal_text' => 'Clear',
                                    'normal_vals' => ['Symmetrical chest expansion', 'Clear breath sounds'],
                                    'acute_vals' => ['Retractions', 'Wheezes', 'Crackles / rales'],
                                    'items' => [
                                        ['val' => 'Symmetrical chest expansion', 'label' => 'Symmetrical Chest Expansion', 'normal' => true, 'acute' => false],
                                        ['val' => 'Clear breath sounds', 'label' => 'Clear Breath Sounds (CBS)', 'normal' => true, 'acute' => false],
                                        ['val' => 'Retractions', 'label' => 'Intercostal Retractions', 'normal' => false, 'acute' => true],
                                        ['val' => 'Wheezes', 'label' => 'Wheezes / Rhonchi', 'normal' => false, 'acute' => true],
                                        ['val' => 'Crackles / rales', 'label' => 'Crackles / Basal Rales', 'normal' => false, 'acute' => true],
                                    ]
                                ],
                                'heart' => [
                                    'title' => 'Heart (CVS)',
                                    'badge_id' => 'pe_badge_heart',
                                    'normal_text' => 'Normal Rhythm',
                                    'normal_vals' => ['Adynamic precordium', 'Normal rate regular rhythm'],
                                    'acute_vals' => ['Heaves / thrills', 'Murmurs'],
                                    'items' => [
                                        ['val' => 'Adynamic precordium', 'label' => 'Adynamic Precordium', 'normal' => true, 'acute' => false],
                                        ['val' => 'Normal rate regular rhythm', 'label' => 'Normal Rate, Regular Rhythm', 'normal' => true, 'acute' => false],
                                        ['val' => 'Heaves / thrills', 'label' => 'Heaves / Thrills', 'normal' => false, 'acute' => true],
                                        ['val' => 'Murmurs', 'label' => 'Murmurs (Graded S3/S4)', 'normal' => false, 'acute' => true],
                                    ]
                                ],
                                'abdomen' => [
                                    'title' => 'Abdomen',
                                    'badge_id' => 'pe_badge_abdo',
                                    'normal_text' => 'Soft, Non-tender',
                                    'normal_vals' => ['Flat'],
                                    'acute_vals' => ['Tenderness', 'Muscle guarding', 'Palpable mass'],
                                    'items' => [
                                        ['val' => 'Flat', 'label' => 'Flat / Soft Abdomen', 'normal' => true, 'acute' => false],
                                        ['val' => 'Flabby', 'label' => 'Flabby', 'normal' => false, 'acute' => false],
                                        ['val' => 'Globular', 'label' => 'Globular', 'normal' => false, 'acute' => false],
                                        ['val' => 'Tenderness', 'label' => 'Tenderness / Rebound', 'normal' => false, 'acute' => true],
                                        ['val' => 'Muscle guarding', 'label' => 'Muscle Guarding', 'normal' => false, 'acute' => true],
                                        ['val' => 'Palpable mass', 'label' => 'Palpable Mass / Organomegaly', 'normal' => false, 'acute' => true],
                                    ]
                                ],
                                'extremities' => [
                                    'title' => 'Extremities',
                                    'badge_id' => 'pe_badge_ext',
                                    'normal_text' => 'Equal Pulses',
                                    'normal_vals' => ['Full and equal pulses', 'Normal gait'],
                                    'acute_vals' => ['Gross deformity', 'Cyanosis'],
                                    'items' => [
                                        ['val' => 'Full and equal pulses', 'label' => 'Full & Equal Peripheral Pulses', 'normal' => true, 'acute' => false],
                                        ['val' => 'Normal gait', 'label' => 'Normal Gait, No Gross Deformity', 'normal' => true, 'acute' => false],
                                        ['val' => 'Gross deformity', 'label' => 'Gross Deformity / Bipedal Edema', 'normal' => false, 'acute' => true],
                                        ['val' => 'Cyanosis', 'label' => 'Cyanosis / Clubbing', 'normal' => false, 'acute' => true],
                                    ]
                                ],
                            ];
                            ?>
                            <div class="row g-3 small">
                                <?php foreach ($peConfig as $sysKey => $conf): 
                                    $savedSys = $peSaved[$sysKey] ?? [];
                                    if (!is_array($savedSys)) $savedSys = [];
                                    
                                    $hasAcute = false;
                                    $hasNormal = false;
                                    foreach ($savedSys as $f) {
                                        if (in_array($f, $conf['acute_vals'], true)) $hasAcute = true;
                                        if (in_array($f, $conf['normal_vals'], true)) $hasNormal = true;
                                    }

                                    if ($hasAcute) {
                                        $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                        $badgeText = 'Abnormal Findings';
                                    } elseif ($hasNormal) {
                                        $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                        $badgeText = $conf['normal_text'];
                                    } elseif (!empty($savedSys)) {
                                        $badgeClass = 'bg-info-subtle text-primary border border-info-subtle';
                                        $badgeText = 'Recorded';
                                    } else {
                                        $badgeClass = 'bg-light text-muted border';
                                        $badgeText = 'Unspecified';
                                    }
                                ?>
                                <div class="col-12 col-md-4">
                                    <div class="p-3 pe-system-card h-100 d-flex flex-column" data-system-card="<?= $sysKey ?>">
                                        <div class="d-flex align-items-center justify-content-between mb-2.5 pb-2 border-bottom">
                                            <span class="fw-bold text-dark d-flex align-items-center" style="font-size: 0.82rem;">
                                                <?= $conf['title'] ?>
                                            </span>
                                            <span id="<?= $conf['badge_id'] ?>" class="badge <?= $badgeClass ?> px-2 py-1 pe-system-badge" style="font-size: 0.7rem;">
                                                <?= $badgeText ?>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 d-flex flex-column gap-1">
                                            <?php foreach ($conf['items'] as $item): 
                                                $isChecked = in_array($item['val'], $savedSys, true);
                                                $chkId = 'pe_' . $sysKey . '_' . substr(md5($item['val']), 0, 8);
                                            ?>
                                                <div class="pe-item-tile">
                                                    <div class="form-check d-flex align-items-center mb-0">
                                                        <input class="form-check-input pe-checkbox pe-sys-<?= $sysKey ?> me-2 mt-0" 
                                                               type="checkbox" 
                                                               name="pe_<?= $sysKey ?>[]" 
                                                               value="<?= h($item['val']) ?>" 
                                                               id="<?= $chkId ?>" 
                                                               data-system="<?= $sysKey ?>" 
                                                               data-is-normal="<?= $item['normal'] ? '1' : '0' ?>" 
                                                               data-is-acute="<?= $item['acute'] ? '1' : '0' ?>" 
                                                               data-normal-label="<?= h($conf['normal_text']) ?>"
                                                               <?= $isChecked ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-dark flex-grow-1 cursor-pointer" for="<?= $chkId ?>" style="font-size: 0.78rem;">
                                                            <?= h($item['label']) ?>
                                                            <?php if ($item['normal']): ?>
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.65rem;">Normal</span>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>

                                <!-- Doctor's Clinical Notes / Remarks -->
                                <div class="col-12 mt-2">
                                    <div class="p-2.5 rounded-2 bg-light-subtle border">
                                        <label class="form-label fw-bold text-dark small mb-1" for="pe_remarks">
                                            Doctor's Clinical Notes / Detailed Findings
                                        </label>
                                        <textarea name="pe_remarks" id="pe_remarks" class="form-control form-control-sm rounded-2" rows="3" placeholder="Patient is well-nourished, alert and ambulatory. Record other physical examination findings or clinical notes..."><?= h($peSaved['remarks'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 8. Female Menstrual & Reproductive History (if Female) -->
                    <?php if ($isFemale): ?>
                        <div class="col-12 col-lg-6">
                            <div class="card card-premium shadow-sm border-0 p-4 h-100 mb-4 ihp-section-card" id="ihp-sec-reproductive">
                                <div class="mb-3 pb-2.5 border-bottom">
                                    <h5 class="h6 fw-bold text-pink mb-0">
                                        8. Female Menstrual & Reproductive History
                                    </h5>
                                    <span class="text-muted small">Menarche, LMP, flow characteristics, and contraception</span>
                                </div>
                                <div class="row g-2.5 small">
                                    <div class="col-6 col-sm-4">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Menarche Age</label>
                                        <input type="number" name="menarche_age" class="form-control form-control-sm" placeholder="e.g. 13" min="8" max="25" value="<?= h($medicalHistory['menarche_age'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-sm-4">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Sexual Onset</label>
                                        <input type="number" name="sexual_onset_age" class="form-control form-control-sm" placeholder="e.g. 20" min="10" max="60" value="<?= h($medicalHistory['sexual_onset_age'] ?? '') ?>">
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <label class="form-label fw-semibold text-secondary small mb-1" for="lmp_date">LMP Date</label>
                                        <input type="date" name="lmp" id="lmp_date" class="form-control form-control-sm" placeholder="YYYY-MM-DD" max="<?= date('Y-m-d') ?>" value="<?= h($medicalHistory['lmp'] ?? '') ?>">
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
                                        <div class="form-check mt-3 p-2 bg-light-subtle rounded border">
                                            <input class="form-check-input ms-1" type="checkbox" name="is_menopausal" value="1" id="is_menopausal" <?= !empty($medicalHistory['is_menopausal']) ? 'checked' : '' ?>>
                                            <label class="form-check-label text-dark fw-semibold small cursor-pointer ms-2" for="is_menopausal">Menopausal</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fw-semibold text-secondary small mb-1" for="menopause_age">Menopause Age (years)</label>
                                        <input type="number" name="menopause_age" id="menopause_age" class="form-control form-control-sm <?= empty($medicalHistory['is_menopausal']) ? 'bg-light text-muted' : 'bg-white' ?>" placeholder="Menopause Age (e.g. 50)" min="30" max="70" value="<?= h($medicalHistory['menopause_age'] ?? '') ?>" <?= empty($medicalHistory['is_menopausal']) ? 'disabled' : '' ?>>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Family Planning Method in Use</label>
                                        <input type="text" name="birth_control_method" class="form-control form-control-sm" placeholder="e.g. Pills, BTL, IUD, Injectable, Condom, None" value="<?= h($medicalHistory['birth_control_method'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 9. Pregnancy History Card (if Female) -->
                        <div class="col-12 col-lg-6">
                            <div class="card card-premium shadow-sm border-0 p-4 h-100 mb-4 ihp-section-card" id="ihp-sec-obstetric">
                                <div class="mb-3 pb-2.5 border-bottom">
                                    <h5 class="h6 fw-bold text-pink mb-0">
                                        9. Pregnancy & Obstetric History (Annex A1)
                                    </h5>
                                    <span class="text-muted small">GTPAL obstetric score, pre-eclampsia, and delivery mode</span>
                                </div>
                                <div class="row g-2.5 small">
                                    <div class="col-6 col-sm-3">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Gravida</label>
                                        <input type="number" name="gravida" class="form-control form-control-sm" min="0" max="25" placeholder="G" value="<?= h($medicalHistory['gravida'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Parity</label>
                                        <input type="number" name="para" class="form-control form-control-sm" min="0" max="25" placeholder="P" value="<?= h($medicalHistory['para'] ?? '') ?>">
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Type of Delivery</label>
                                        <input type="text" name="delivery_type" class="form-control form-control-sm" placeholder="e.g. NSD, CS, Forceps" value="<?= h($medicalHistory['delivery_type'] ?? '') ?>">
                                    </div>

                                    <div class="col-6 col-sm-3">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Full Term (F)</label>
                                        <input type="number" name="term_births" class="form-control form-control-sm" min="0" max="25" placeholder="F" value="<?= h($medicalHistory['term_births'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Premature (P)</label>
                                        <input type="number" name="preterm_births" class="form-control form-control-sm" min="0" max="25" placeholder="P" value="<?= h($medicalHistory['preterm_births'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Abortion (A)</label>
                                        <input type="number" name="abortions" class="form-control form-control-sm" min="0" max="25" placeholder="A" value="<?= h($medicalHistory['abortions'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Living Children (L)</label>
                                        <input type="number" name="living_children" class="form-control form-control-sm" min="0" max="25" placeholder="L" value="<?= h($medicalHistory['living_children'] ?? '') ?>">
                                    </div>

                                    <div class="col-12 col-sm-6 border-top pt-2 mt-2">
                                        <div class="form-check p-2 bg-light-subtle rounded border">
                                            <input class="form-check-input ms-1" type="checkbox" name="pre_eclampsia" value="1" id="pre_eclampsia" <?= !empty($medicalHistory['pre_eclampsia']) ? 'checked' : '' ?>>
                                            <label class="form-check-label text-dark fw-semibold small cursor-pointer ms-2" for="pre_eclampsia">Pre-eclampsia / PIH</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6 border-top pt-2 mt-2">
                                        <label class="form-label fw-semibold text-secondary small mb-1 d-block">Access to Family Planning Counselling</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="fp_counselling" id="fp_counselling_yes" value="1" <?= (!isset($medicalHistory['fp_counselling']) || (int)$medicalHistory['fp_counselling'] === 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label small cursor-pointer" for="fp_counselling_yes">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="fp_counselling" id="fp_counselling_no" value="0" <?= (isset($medicalHistory['fp_counselling']) && (int)$medicalHistory['fp_counselling'] === 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label small cursor-pointer" for="fp_counselling_no">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sticky Bottom Action Bar -->
                <div class="sticky-bottom sticky-bottom-action-bar py-3 px-4 border rounded-3 mt-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <span class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            <?= $isExistingRecord 
                                ? 'Changes will update the PhilHealth Annex A1 baseline profile for this patient.' 
                                : 'Saving will create the PhilHealth Annex A1 baseline profile for this patient.' ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary px-3" onclick="confirmCancelIhpEdit()">
                            Cancel
                        </button>
                        <button type="submit" id="ihpSubmitBtn" class="btn btn-primary px-4 fw-semibold shadow-xs">
                            <i class="bi bi-check2-circle me-1"></i> <?= $isExistingRecord ? 'Save Changes' : 'Save IHP Profile' ?>
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ihpForm = document.getElementById('ihpForm');
    let ihpFormInitialData = '';
    let isSubmitting = false;

    if (ihpForm) {
        ihpFormInitialData = new URLSearchParams(new FormData(ihpForm)).toString();
    }

    window.isIhpDirty = function() {
        if (!ihpForm) return false;
        const currentData = new URLSearchParams(new FormData(ihpForm)).toString();
        return currentData !== ihpFormInitialData;
    };

    window.confirmCancelIhpEdit = function() {
        const returnUrl = '<?= url('/patients/' . $patient['id'] . '#tab-ihp') ?>';
        if (window.isIhpDirty()) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Discard Unsaved Changes?',
                    text: 'You have unsaved changes in this IHP record. Are you sure you want to discard them?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Discard Changes',
                    cancelButtonText: 'Keep Editing'
                }).then((result) => {
                    if (result.isConfirmed) {
                        isSubmitting = true;
                        window.location.href = returnUrl;
                    }
                });
            } else {
                if (confirm('You have unsaved changes. Discard and return to patient profile?')) {
                    isSubmitting = true;
                    window.location.href = returnUrl;
                }
            }
        } else {
            window.location.href = returnUrl;
        }
    };

    // Warn on accidental tab close or page reload if dirty
    window.addEventListener('beforeunload', function(e) {
        if (isSubmitting) return;
        if (window.isIhpDirty()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Anchor smooth jump with highlight feedback
    document.querySelectorAll('#ihpSectionNav a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetCard = document.querySelector(targetId);
            if (targetCard) {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetCard.classList.add('border-primary', 'shadow');
                setTimeout(() => {
                    targetCard.classList.remove('border-primary', 'shadow');
                }, 1600);
            }
        });
    });

    // Section 3: Dynamic Surgical Procedures Repeater
    let surgeryIndex = <?= !empty($surgicalSaved) && is_array($surgicalSaved) ? count($surgicalSaved) + 10 : 100 ?>;

    window.addIhpSurgeryRow = function() {
        const container = document.getElementById('ihpSurgeriesContainer');
        const emptyMsg = document.getElementById('ihpSurgeriesEmpty');
        if (!container) return;

        if (emptyMsg) emptyMsg.classList.add('d-none');

        const idx = surgeryIndex++;
        const rowDiv = document.createElement('div');
        rowDiv.className = 'ihp-surgery-row p-2.5 border rounded-2 bg-light-subtle position-relative';
        rowDiv.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-5">
                    <label class="form-label fw-semibold text-secondary small mb-1">Operation / Procedure</label>
                    <input type="text" name="surgical_procedures[${idx}][operation]" class="form-control form-control-sm bg-white" placeholder="e.g. Appendectomy" required>
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label fw-semibold text-secondary small mb-1">Date / Year</label>
                    <input type="text" name="surgical_procedures[${idx}][date]" class="form-control form-control-sm bg-white" placeholder="YYYY or Date">
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label fw-semibold text-secondary small mb-1">Hospital / Clinic</label>
                    <input type="text" name="surgical_procedures[${idx}][hospital]" class="form-control form-control-sm bg-white" placeholder="e.g. Health Center">
                </div>
                <div class="col-12 col-sm-1 text-end text-sm-center">
                    <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeIhpSurgeryRow(this)" title="Remove procedure">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(rowDiv);
        const opInput = rowDiv.querySelector('input[name*="[operation]"]');
        if (opInput) opInput.focus();
    };

    window.removeIhpSurgeryRow = function(btn) {
        const row = btn.closest('.ihp-surgery-row');
        if (row) {
            row.remove();
            const container = document.getElementById('ihpSurgeriesContainer');
            const emptyMsg = document.getElementById('ihpSurgeriesEmpty');
            if (container && container.querySelectorAll('.ihp-surgery-row').length === 0) {
                if (emptyMsg) emptyMsg.classList.remove('d-none');
            }
        }
    };

    // Section 6: Real-Time Baseline BMI & WHO Asian Criteria Calculator
    window.updateIhpBmi = function() {
        const hInput = document.getElementById('ihp_baseline_height');
        const wInput = document.getElementById('ihp_baseline_weight');
        const valSpan = document.getElementById('ihpBmiValue');
        const badgeSpan = document.getElementById('ihpBmiBadge');
        if (!hInput || !wInput || !valSpan || !badgeSpan) return;

        const h = parseFloat(hInput.value);
        const w = parseFloat(wInput.value);

        if (h > 0 && w > 0) {
            const hm = h / 100;
            const bmi = w / (hm * hm);
            valSpan.innerHTML = `${bmi.toFixed(2)} <span class="text-muted fs-7 fw-normal">kg/m²</span>`;

            let cat = 'Normal';
            let badgeClass = 'bg-success-subtle text-success border border-success-subtle';

            if (bmi < 18.5) {
                cat = 'Underweight';
                badgeClass = 'bg-info-subtle text-info border border-info-subtle';
            } else if (bmi <= 22.9) {
                cat = 'Normal';
                badgeClass = 'bg-success-subtle text-success border border-success-subtle';
            } else if (bmi <= 27.4) {
                cat = 'Overweight';
                badgeClass = 'bg-warning-subtle text-dark border border-warning-subtle';
            } else {
                cat = 'Obese';
                badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
            }

            badgeSpan.className = `badge ${badgeClass} px-2.5 py-1.5 fs-7`;
            badgeSpan.textContent = cat;
        } else {
            valSpan.innerHTML = '<span class="text-muted fs-7 fw-normal">Enter height & weight</span>';
            badgeSpan.className = 'badge bg-light text-muted border px-2.5 py-1.5 fs-7';
            badgeSpan.textContent = '—';
        }
    };

    const ihpHeightInput = document.getElementById('ihp_baseline_height');
    const ihpWeightInput = document.getElementById('ihp_baseline_weight');
    if (ihpHeightInput && ihpWeightInput) {
        ihpHeightInput.addEventListener('input', window.updateIhpBmi);
        ihpWeightInput.addEventListener('input', window.updateIhpBmi);
        window.updateIhpBmi();
    }

    // Reactive Controls & Condition Toggles
    window.initIhpReactiveControls = function() {
        // 1. Condition Toggles
        document.querySelectorAll('.condition-toggle').forEach(chk => {
            const targetSelectors = chk.dataset.target ? chk.dataset.target.split(',') : [];
            const targets = targetSelectors.map(sel => document.querySelector(sel.trim())).filter(Boolean);
            const parentBox = chk.closest('.ihp-condition-box');

            function updateConditionState(isUserAction) {
                const isChecked = chk.checked;
                if (parentBox) {
                    if (isChecked) {
                        parentBox.classList.add('active-condition');
                    } else {
                        parentBox.classList.remove('active-condition');
                    }
                }
                targets.forEach((inp, idx) => {
                    inp.disabled = !isChecked;
                    if (isChecked) {
                        inp.classList.remove('bg-light', 'text-muted');
                        inp.classList.add('bg-white');
                        if (isUserAction && idx === 0) {
                            inp.focus();
                        }
                    } else {
                        if (isUserAction) {
                            inp.value = '';
                        }
                        inp.classList.remove('bg-white');
                        inp.classList.add('bg-light', 'text-muted');
                    }
                });
            }

            chk.addEventListener('change', function() {
                updateConditionState(true);
            });

            targets.forEach(inp => {
                inp.addEventListener('input', function() {
                    if (this.value.trim().length > 0 && !chk.checked) {
                        chk.checked = true;
                        updateConditionState(false);
                    }
                });
            });

            updateConditionState(false);
        });

        // 2. Checklist Tile Active Highlight
        document.querySelectorAll('.ihp-checklist-tile').forEach(tile => {
            const chk = tile.querySelector('input[type="checkbox"]');
            if (chk) {
                chk.addEventListener('change', function() {
                    if (this.checked) {
                        tile.classList.add('active-condition');
                    } else {
                        tile.classList.remove('active-condition');
                    }
                });
            }
        });

        // 3. Smoking status coupling
        const smokingSelect = document.getElementById('smoking_status');
        const packYearsInput = document.getElementById('smoking_pack_years');
        if (smokingSelect && packYearsInput) {
            function syncSmoking(isUserAction) {
                const isNever = (smokingSelect.value === 'Never');
                packYearsInput.disabled = isNever;
                if (isNever) {
                    if (isUserAction) packYearsInput.value = '';
                    packYearsInput.classList.remove('bg-white');
                    packYearsInput.classList.add('bg-light', 'text-muted');
                } else {
                    packYearsInput.classList.remove('bg-light', 'text-muted');
                    packYearsInput.classList.add('bg-white');
                    if (isUserAction) packYearsInput.focus();
                }
            }
            smokingSelect.addEventListener('change', function() {
                syncSmoking(true);
            });
            syncSmoking(false);
        }

        // 4. Alcohol status coupling
        const alcoholSelect = document.getElementById('alcohol_status');
        const bottlesInput = document.getElementById('alcohol_bottles_per_day');
        if (alcoholSelect && bottlesInput) {
            function syncAlcohol(isUserAction) {
                const isNever = (alcoholSelect.value === 'Never');
                bottlesInput.disabled = isNever;
                if (isNever) {
                    if (isUserAction) bottlesInput.value = '';
                    bottlesInput.classList.remove('bg-white');
                    bottlesInput.classList.add('bg-light', 'text-muted');
                } else {
                    bottlesInput.classList.remove('bg-light', 'text-muted');
                    bottlesInput.classList.add('bg-white');
                    if (isUserAction) bottlesInput.focus();
                }
            }
            alcoholSelect.addEventListener('change', function() {
                syncAlcohol(true);
            });
            syncAlcohol(false);
        }

        // 5. Menopause coupling
        const menopauseCheck = document.getElementById('is_menopausal');
        const menopauseAgeInput = document.getElementById('menopause_age');
        if (menopauseCheck && menopauseAgeInput) {
            function syncMenopause(isUserAction) {
                const isMeno = menopauseCheck.checked;
                menopauseAgeInput.disabled = !isMeno;
                if (isMeno) {
                    menopauseAgeInput.classList.remove('bg-light', 'text-muted');
                    menopauseAgeInput.classList.add('bg-white');
                    if (isUserAction) menopauseAgeInput.focus();
                } else {
                    if (isUserAction) menopauseAgeInput.value = '';
                    menopauseAgeInput.classList.remove('bg-white');
                    menopauseAgeInput.classList.add('bg-light', 'text-muted');
                }
            }
            menopauseCheck.addEventListener('change', function() {
                syncMenopause(true);
            });
            syncMenopause(false);
        }

        // 6. Physical Exam System Badges & Mark All Normal
        function updatePeSystemBadge(sys) {
            const badgeMap = {
                'skin': { id: 'pe_badge_skin', normalText: 'Good Turgor' },
                'heent': { id: 'pe_badge_heent', normalText: 'Normal' },
                'chest_lungs': { id: 'pe_badge_chest', normalText: 'Clear' },
                'heart': { id: 'pe_badge_heart', normalText: 'Normal Rhythm' },
                'abdomen': { id: 'pe_badge_abdo', normalText: 'Soft, Non-tender' },
                'extremities': { id: 'pe_badge_ext', normalText: 'Equal Pulses' }
            };
            const conf = badgeMap[sys];
            if (!conf) return;
            const badgeEl = document.getElementById(conf.id);
            if (!badgeEl) return;

            const systemCheckboxes = Array.from(document.querySelectorAll(`.pe-sys-${sys}`));
            const checkedBoxes = systemCheckboxes.filter(c => c.checked);

            if (checkedBoxes.length === 0) {
                badgeEl.className = 'badge bg-light text-muted border px-2 py-1 pe-system-badge';
                badgeEl.textContent = 'Unspecified';
                return;
            }

            const hasAcute = checkedBoxes.some(c => c.dataset.isAcute === '1');
            const hasNormal = checkedBoxes.some(c => c.dataset.isNormal === '1');

            if (hasAcute) {
                badgeEl.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 pe-system-badge';
                badgeEl.textContent = 'Abnormal Findings';
            } else if (hasNormal) {
                badgeEl.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1 pe-system-badge';
                badgeEl.textContent = conf.normalText;
            } else {
                badgeEl.className = 'badge bg-info-subtle text-primary border border-info-subtle px-2 py-1 pe-system-badge';
                badgeEl.textContent = 'Recorded';
            }
        }

        document.querySelectorAll('.pe-checkbox').forEach(chk => {
            chk.addEventListener('change', function() {
                const sys = this.dataset.system;
                if (sys) updatePeSystemBadge(sys);
            });
        });

        const btnMarkAllNormal = document.getElementById('btnMarkAllNormal');
        if (btnMarkAllNormal) {
            btnMarkAllNormal.addEventListener('click', function() {
                const allPeCheckboxes = document.querySelectorAll('.pe-checkbox');
                allPeCheckboxes.forEach(chk => {
                    chk.checked = (chk.dataset.isNormal === '1');
                });

                const remarksInput = document.getElementById('pe_remarks');
                if (remarksInput && !remarksInput.value.trim()) {
                    remarksInput.value = 'Patient is well-nourished, alert and ambulatory. Systemic physical examination findings unremarkable with no acute distress.';
                }

                ['skin', 'heent', 'chest_lungs', 'heart', 'abdomen', 'extremities'].forEach(sys => {
                    updatePeSystemBadge(sys);
                });

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'All physical findings marked unremarkable / normal',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                }
            });
        }

        ['skin', 'heent', 'chest_lungs', 'heart', 'abdomen', 'extremities'].forEach(sys => {
            updatePeSystemBadge(sys);
        });
    };

    window.initIhpReactiveControls();

    // Form submit validation & Blood Pressure Sanity Check
    if (ihpForm) {
        ihpForm.addEventListener('submit', function(e) {
            const sys = document.getElementById('ihp_baseline_bp_systolic');
            const dia = document.getElementById('ihp_baseline_bp_diastolic');
            if (sys && dia && sys.value.trim() && dia.value.trim()) {
                const sysVal = parseInt(sys.value, 10);
                const diaVal = parseInt(dia.value, 10);
                if (!isNaN(sysVal) && !isNaN(diaVal) && sysVal > 0 && diaVal > 0) {
                    if (sysVal <= diaVal) {
                        e.preventDefault();
                        const errMsg = `Physiological validation error: Systolic blood pressure (${sysVal} mmHg) must be strictly higher than Diastolic pressure (${diaVal} mmHg).`;
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid Blood Pressure Reading',
                                text: errMsg,
                                confirmButtonColor: '#0d6efd'
                            });
                        } else {
                            alert(errMsg);
                        }
                        sys.focus();
                        return;
                    }
                }
            }

            isSubmitting = true;
            const submitBtn = document.getElementById('ihpSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving IHP...';
            }
        });
    }
});
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
