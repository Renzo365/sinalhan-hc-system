<?php
$title = 'Edit Consultation';
$breadcrumbs = [
    'Patients' => '/patients',
    'Profile' => '/patients/' . $patient['id'],
    'Edit Consultation' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Edit Consultation</h2>
        <p class="text-secondary small mb-0">
            Consultation record originally recorded on <?= date('M d, Y h:i A', strtotime($consultation['consulted_at'] ?? $consultation['created_at'])) ?><?= !empty($consultation['creator_name']) ? ' by ' . h($consultation['creator_name']) : '' ?>.
            <?php if (!empty($consultation['updated_at']) && !empty($consultation['updater_name'])): ?>
                &bull; <span class="text-muted">Last edited on <?= date('M d, Y h:i A', strtotime($consultation['updated_at'])) ?> by <?= h($consultation['updater_name']) ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="<?= url('/patients/' . $patient['id'] . '#tab-consultations') ?>" class="btn btn-outline-secondary btn-cancel-consultation">
            &larr; Back to Profile
        </a>
    </div>
</div>

<?php
    $fieldErrorMap = [
        'subjective' => 'History of Present Illness',
        'objective' => 'Physical Exam',
        'assessment' => 'Assessment / Impression',
        'plan' => 'Treatment',
        'consulting_provider' => 'Consulting Provider',
        'consulted_at' => 'Date & Time'
    ];

    $hasFieldError = function($field) use ($errors, $fieldErrorMap) {
        if (empty($errors)) return false;
        $needle = $fieldErrorMap[$field] ?? $field;
        foreach ($errors as $e) {
            if (stripos($e, $needle) !== false) return true;
        }
        return false;
    };

    $getFieldError = function($field) use ($errors, $fieldErrorMap) {
        if (empty($errors)) return '';
        $needle = $fieldErrorMap[$field] ?? $field;
        foreach ($errors as $e) {
            if (stripos($e, $needle) !== false) return $e;
        }
        return '';
    };
?>

<!-- Errors Alert Box with Accessible In-Page Anchors -->
<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-danger mb-4 shadow-sm" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following:</div>
        <ul class="mb-0 ps-3 small">
            <?php foreach ($errors as $err): 
                $targetId = '';
                foreach ($fieldErrorMap as $fieldKey => $labelNeedle) {
                    if (stripos($err, $labelNeedle) !== false) {
                        $targetId = $fieldKey;
                        break;
                    }
                }
            ?>
                <li>
                    <?php if ($targetId): ?>
                        <a href="#<?= $targetId ?>" class="text-danger text-decoration-underline fw-semibold error-jump-link"><?= h($err) ?></a>
                    <?php else: ?>
                        <?= h($err) ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php
    $alerts = $cdsAlerts ?? \App\Services\ClinicalDecisionService::getAlertsForPatient($patient['id']);
    $pmh = $medicalHistory['past_medical_history'] ?? [];
    $surg = $medicalHistory['surgical_history'] ?? [];
    $fam = $medicalHistory['family_history'] ?? [];

    $pLastName = trim($patient['last_name'] ?? '');
    $pFirstName = trim($patient['first_name'] ?? '');
    $pMiddleName = trim($patient['middle_name'] ?? '');
    $pSuffix = trim($patient['suffix'] ?? '');

    $pFullName = $pLastName . ', ' . $pFirstName;
    if (!empty($pMiddleName)) {
        $pFullName .= ' ' . mb_substr($pMiddleName, 0, 1) . '.';
    }
    if (!empty($pSuffix)) {
        $pFullName .= ' ' . $pSuffix;
    }

    $pInitials = strtoupper(
        (!empty($pFirstName) ? mb_substr($pFirstName, 0, 1) : '') .
        (!empty($pLastName) ? mb_substr($pLastName, 0, 1) : '')
    );
    $pDobFormatted = (!empty($patient['dob']) && $patient['dob'] !== '0000-00-00') ? date('M d, Y', strtotime($patient['dob'])) : 'Unspecified';
    $hasBlood = (!empty($patient['blood_type']) && strtolower(trim($patient['blood_type'])) !== 'unknown');
?>

<!-- 1. Patient Clinical Profile & Safety Card -->
<div class="card card-premium mb-4 bg-white border">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.1rem;">
                    <?= !empty($pInitials) ? h($pInitials) : '<i class="bi bi-person-fill"></i>' ?>
                </div>
                <div class="min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <strong class="text-dark fs-6 mb-0 lh-1"><?= h($pFullName) ?></strong>
                        <span class="badge bg-light text-dark border font-monospace fs-7"><?= h($patient['patient_no']) ?></span>
                        <?php if (!empty($patient['envelope_no'])): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-7" title="Physical Logbook Envelope No.">
                                Env #<?= h($patient['envelope_no']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($patient['family_no'])): ?>
                            <a href="<?= url('/patients?search=' . urlencode($patient['family_no'])) ?>" class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace fs-7 text-decoration-none" title="View household in directory">
                                Fam #<?= h($patient['family_no']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 text-secondary small">
                        <span><strong><?= h($patient['age']) ?></strong> yrs &bull; <?= h($patient['sex']) ?></span>
                        <span class="text-muted">&bull;</span>
                        <span>DOB: <strong class="text-dark"><?= h($pDobFormatted) ?></strong></span>
                        <span class="text-muted">&bull;</span>
                        <?php if ($hasBlood): ?>
                            <span>Blood: <strong class="text-danger"><?= h($patient['blood_type']) ?></strong></span>
                        <?php else: ?>
                            <span>Blood: <span class="text-muted">Unknown</span></span>
                        <?php endif; ?>
                        <?php if (!empty($patient['philhealth_no'])): ?>
                            <span class="text-muted">&bull;</span>
                            <span>PHIC: <span class="font-monospace text-dark"><?= h($patient['philhealth_no']) ?></span></span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1 small text-secondary d-flex align-items-center gap-1">
                        <span><?= !empty(trim($patient['address'] ?? '')) ? h(trim($patient['address'])) : '<span class="text-muted fst-italic">No address recorded</span>' ?></span>
                    </div>
                </div>
            </div>

            <!-- Profile Context Action Buttons -->
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($activePrenatal)): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#pregnancyDetailsCollapse" aria-expanded="false" aria-controls="pregnancyDetailsCollapse">
                        Obstetric Details <i class="bi bi-chevron-down ms-1"></i>
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#medicalBackgroundCollapse" aria-expanded="false" aria-controls="medicalBackgroundCollapse">
                    Medical Background <i class="bi bi-chevron-down ms-1"></i>
                </button>
            </div>
        </div>

        <!-- Clinical Safety Flags (Allergies & Chronic Diseases) -->
        <?php if (!empty($alerts['has_alerts'])): ?>
            <div class="d-flex flex-wrap align-items-center gap-2 pt-2 mt-2 border-top small">
                <?php foreach ($alerts['flags'] as $flag): ?>
                    <span class="badge <?= $flag['class'] ?> px-2 py-1">
                        <i class="bi <?= $flag['icon'] ?> me-1"></i><?= h($flag['label']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Active Pregnancy Context Strip (Integrated Option B) -->
        <?php if (!empty($activePrenatal)): ?>
            <div class="pt-2 mt-2 border-top small">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-light text-pink border border-pink-subtle px-2 py-1 fw-semibold">
                        Active Pregnancy
                    </span>
                    <span class="fw-bold text-dark">G<?= h($activePrenatal['gravida']) ?>P<?= h($activePrenatal['para']) ?></span>
                    <span class="text-muted">&bull;</span>
                    <span class="text-secondary">LMP: <strong class="text-dark"><?= date('M d, Y', strtotime($activePrenatal['lmp'])) ?></strong></span>
                    <span class="text-muted">&bull;</span>
                    <span class="text-secondary">EDC: <strong class="text-dark"><?= date('M d, Y', strtotime($activePrenatal['edc'])) ?></strong></span>
                    <span class="text-muted">&bull;</span>
                    <span class="text-secondary">AOG: <strong class="text-dark"><?= h($activePrenatal['aog_weeks'] ?? 'N/A') ?> wks</strong></span>
                    <?php if (!empty($activePrenatal['pre_eclampsia'])): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">Pre-Eclampsia Risk</span>
                    <?php endif; ?>
                </div>

                <!-- Expandable Obstetric Drawer -->
                <div class="collapse pt-2 mt-2 border-top" id="pregnancyDetailsCollapse">
                    <div class="row g-2 text-secondary">
                        <div class="col-6 col-md-3">Term Births: <strong class="text-dark"><?= (int)($activePrenatal['term_births'] ?? 0) ?></strong></div>
                        <div class="col-6 col-md-3">Preterm Births: <strong class="text-dark"><?= (int)($activePrenatal['preterm_births'] ?? 0) ?></strong></div>
                        <div class="col-6 col-md-3">Abortions: <strong class="text-dark"><?= (int)($activePrenatal['abortions'] ?? 0) ?></strong></div>
                        <div class="col-6 col-md-3">Living Children: <strong class="text-dark"><?= (int)($activePrenatal['living_children'] ?? 0) ?></strong></div>
                        <?php if (!empty($activePrenatal['notes'])): ?>
                            <div class="col-12 mt-1">Obstetric Notes: <span class="text-dark fst-italic"><?= h($activePrenatal['notes']) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Collapsible IHP Medical & Social Background Drawer -->
    <div class="collapse border-top bg-light" id="medicalBackgroundCollapse">
        <div class="card-body p-3">
            <div class="row g-3 small">
                <!-- Chronic Illnesses -->
                <div class="col-12 col-md-3">
                    <div class="fw-bold text-dark mb-1">Past Medical History:</div>
                    <?php if (!empty($pmh)): ?>
                        <ul class="mb-0 ps-3 text-muted">
                            <?php foreach ($pmh as $cond => $detail): 
                                if (empty($cond)) continue;
                            ?>
                                <li><strong><?= h($cond) ?></strong><?= !empty($detail) ? ': ' . h($detail) : '' ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-muted fst-italic">No chronic illnesses recorded.</span>
                    <?php endif; ?>
                </div>

                <!-- Surgical History -->
                <div class="col-12 col-md-3">
                    <div class="fw-bold text-dark mb-1">Surgical History:</div>
                    <?php if (!empty($surg)): ?>
                        <ul class="mb-0 ps-3 text-muted">
                            <?php foreach ($surg as $s): 
                                $op = is_array($s) ? ($s['operation'] ?? '') : (string)$s;
                                $date = is_array($s) ? ($s['date'] ?? '') : '';
                                $hosp = is_array($s) ? ($s['hospital'] ?? '') : '';
                                $details = array_filter([$date, $hosp]);
                                if (empty($op)) continue;
                            ?>
                                <li><?= h($op) ?><?= !empty($details) ? ' (' . h(implode(', ', $details)) . ')' : '' ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-muted fst-italic">No past surgeries recorded.</span>
                    <?php endif; ?>
                </div>

                <!-- Family Hereditary History -->
                <div class="col-12 col-md-3">
                    <div class="fw-bold text-dark mb-1">Family History:</div>
                    <?php if (!empty($fam)): ?>
                        <ul class="mb-0 ps-3 text-muted">
                            <?php foreach ($fam as $cond => $detail): 
                                if (empty($cond)) continue;
                            ?>
                                <li><?= h($cond) ?><?= !empty($detail) ? ': ' . h($detail) : '' ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-muted fst-italic">No family hereditary history recorded.</span>
                    <?php endif; ?>
                </div>

                <!-- Habits & Lifestyle -->
                <div class="col-12 col-md-3">
                    <div class="fw-bold text-dark mb-1">Social Habits:</div>
                    <div class="text-muted">
                        <div>Smoking: <strong class="text-dark"><?= h($medicalHistory['smoking_status'] ?? 'Never') ?></strong><?= !empty($medicalHistory['smoking_pack_years']) ? ' (' . h($medicalHistory['smoking_pack_years']) . ' pack-years)' : '' ?></div>
                        <div>Alcohol: <strong class="text-dark"><?= h($medicalHistory['alcohol_status'] ?? 'Never') ?></strong><?= !empty($medicalHistory['alcohol_bottles_per_day']) ? ' (' . h($medicalHistory['alcohol_bottles_per_day']) . ' btls/day)' : '' ?></div>
                        <?php if (!empty($medicalHistory['birth_control_method'])): ?>
                            <div>Family Planning: <strong class="text-dark"><?= h($medicalHistory['birth_control_method']) ?></strong></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="<?= url('/consultations/' . $consultation['id']) ?>" method="POST" autocomplete="off" id="consultationForm">
    <?= csrf_field() ?>
    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
    <input type="hidden" name="status" value="<?= h($consultation['status'] ?? 'Completed') ?>">

    <!-- 2. Unified Clinical Consultation Ledger Entry Card -->
    <div class="card card-premium mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h3 class="card-title h6 mb-0 fw-bold text-dark">
                Clinical Consultation Ledger Entry
            </h3>
        </div>
        <div class="card-body p-4">
            <!-- Section A: Encounter Details & Vital Signs -->
            <div class="row g-3 mb-3">
                <!-- Consulting Provider -->
                <div class="col-12 col-md-6">
                    <label for="consulting_provider" class="form-label fw-semibold text-secondary small">
                        Consulting Provider <span class="text-danger">*</span>
                    </label>
                    <?php 
                        $currentProvider = $input['consulting_provider'] ?? $consultation['consulting_provider'] ?? $consultation['clinician_name'] ?? '';
                    ?>
                    <input type="text" 
                           name="consulting_provider" 
                           id="consulting_provider" 
                           class="form-control bg-light <?= $hasFieldError('consulting_provider') ? 'is-invalid' : '' ?>" 
                           list="providerSuggestions" 
                           value="<?= h($currentProvider) ?>" 
                           placeholder="Search active clinical staff or enter provider name" 
                           required>
                    <?php if ($hasFieldError('consulting_provider')): ?>
                        <div class="invalid-feedback d-block"><?= h($getFieldError('consulting_provider')) ?></div>
                    <?php endif; ?>
                    <datalist id="providerSuggestions">
                        <?php foreach ($clinicians as $c): 
                            $cName = $c['first_name'] . ' ' . $c['last_name'];
                            if (!empty($c['job_title'])) {
                                $cName .= " ({$c['job_title']})";
                            }
                        ?>
                            <option value="<?= h($cName) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-text text-muted" style="font-size: 0.75rem;">
                        Enter the clinician or midwife who conducted the consultation.
                    </div>
                </div>

                <!-- Consultation Date & Time -->
                <div class="col-12 col-md-6">
                    <label for="consulted_at" class="form-label fw-semibold text-secondary small">Consultation Date & Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" 
                           name="consulted_at" 
                           id="consulted_at" 
                           class="form-control bg-light <?= $hasFieldError('consulted_at') ? 'is-invalid' : '' ?>" 
                           value="<?= date('Y-m-d\TH:i', strtotime($input['consulted_at'] ?? $consultation['consulted_at'] ?? 'now')) ?>" 
                           max="<?= date('Y-m-d\TH:i') ?>"
                           required>
                    <?php if ($hasFieldError('consulted_at')): ?>
                        <div class="invalid-feedback d-block"><?= h($getFieldError('consulted_at')) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vital Signs Dropdown Selector -->
            <div class="mb-3">
                <label for="vital_signs_id" class="form-label fw-semibold text-secondary small d-flex justify-content-between">
                    <span>Link Vital Signs Record</span>
                    <span class="text-muted fw-normal">Select triage entry to associate with this checkup</span>
                </label>
                <select name="vital_signs_id" id="vital_signs_id" class="form-select bg-light">
                    <?php 
                        $currentVitalSignsId = isset($input['vital_signs_id']) ? (int)$input['vital_signs_id'] : (int)($consultation['vital_signs_id'] ?? 0);
                    ?>
                    <option value="" data-empty="1" <?= empty($currentVitalSignsId) ? 'selected' : '' ?>>-- No Linked Vital Signs --</option>
                    <?php if (!empty($vitalsList)): ?>
                        <?php foreach ($vitalsList as $idx => $v): 
                            $vLabel = date('M d, Y h:i A', strtotime($v['recorded_at']));
                            if (!empty($v['bp_systolic']) && !empty($v['bp_diastolic'])) {
                                $vLabel .= " | BP: {$v['bp_systolic']}/{$v['bp_diastolic']} mmHg";
                            }
                            if (!empty($v['temperature'])) {
                                $vLabel .= " | Temp: " . number_format((float)$v['temperature'], 1) . "°C";
                            }
                            if (!empty($v['weight'])) {
                                $vLabel .= " | Wt: {$v['weight']}kg";
                            }
                            $isHighBp = ((int)($v['bp_systolic'] ?? 0) >= 140 || (int)($v['bp_diastolic'] ?? 0) >= 90);
                            $isSelected = ($currentVitalSignsId === (int)$v['id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $v['id'] ?>" 
                                    data-bp="<?= h(($v['bp_systolic'] ?? '-') . '/' . ($v['bp_diastolic'] ?? '-')) ?>"
                                    data-bp-high="<?= $isHighBp ? '1' : '0' ?>"
                                    data-temp="<?= !empty($v['temperature']) ? number_format((float)$v['temperature'], 1) : '-' ?>"
                                    data-hr="<?= h($v['heart_rate'] ?? '-') ?>"
                                    data-rr="<?= h($v['respiratory_rate'] ?? '-') ?>"
                                    data-spo2="<?= h($v['oxygen_saturation'] ?? '') ?>"
                                    data-weight="<?= h($v['weight'] ?? '-') ?>"
                                    data-height="<?= h($v['height'] ?? '-') ?>"
                                    data-bmi="<?= h($v['bmi'] ?? '-') ?>"
                                    data-waist="<?= h($v['waist_circumference'] ?? '') ?>"
                                    data-notes="<?= h($v['notes'] ?? '') ?>"
                                    data-date="<?= date('M d, Y h:i A', strtotime($v['recorded_at'])) ?>"
                                    <?= $isSelected ?>>
                                <?= h($vLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Dynamic Vitals Preview Box -->
            <div id="vitalsPreviewContainer" class="p-3 bg-light rounded-3 border mb-4">
                <!-- Injected dynamically via JS -->
            </div>

            <!-- Section B: Clinical Consultation Ledger Documentation -->
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <span class="fw-bold text-dark fs-6">Clinical Consultation Documentation</span>
            </div>

            <!-- History of Present Illness -->
            <div class="mb-4">
                <label for="subjective" class="form-label fw-bold text-dark mb-1">
                    History of Present Illness <span class="text-danger">*</span>
                </label>
                <div class="form-text text-muted small mb-2">Patient's chief complaint, reported symptoms, timeline, and history of present illness.</div>
                <textarea name="subjective" 
                          id="subjective" 
                          rows="3" 
                          class="form-control <?= $hasFieldError('subjective') ? 'is-invalid' : '' ?>" 
                          placeholder="e.g. Patient reports persistent dry cough and low-grade fever for 3 days. No difficulty breathing..." 
                          required><?= h($input['subjective'] ?? '') ?></textarea>
                <?php if ($hasFieldError('subjective')): ?>
                    <div class="invalid-feedback"><?= h($getFieldError('subjective')) ?></div>
                <?php endif; ?>
            </div>

            <!-- Physical Exam -->
            <div class="mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                    <label for="objective" class="form-label fw-bold text-dark mb-0">
                        Physical Exam <span class="text-danger">*</span>
                    </label>
                </div>
                <div class="form-text text-muted small mb-2">Physical examination observations, organ findings, and linked diagnostic indicators.</div>
                
                <!-- Quick PE Snippets Ribbon -->
                <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                    <span class="text-secondary small me-1">Quick PE:</span>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="objective" data-snippet="Alert, conscious, not in cardiorespiratory distress. Clear breath sounds bilaterally, no rales or wheezing. Normal rate, regular rhythm. Soft, non-tender abdomen. Warm extremities, good capillary refill (<2s).">
                        ✓ Normal PE
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="objective" data-snippet="Clear breath sounds bilaterally, symmetrical chest expansion, no rales, wheezes, or rhonchi.">
                        + Clear Lungs
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="objective" data-snippet="Erythematous posterior pharyngeal wall, tonsils not enlarged, no exudates, clear breath sounds bilaterally.">
                        + URTI Signs
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="objective" data-snippet="Moist buccal mucosa, good skin turgor, dynamic capillary refill (<2s), not dehydrated.">
                        + Well Hydrated
                    </button>
                </div>

                <textarea name="objective" 
                          id="objective" 
                          rows="3" 
                          class="form-control <?= $hasFieldError('objective') ? 'is-invalid' : '' ?>" 
                          placeholder="e.g. Mild pharyngeal congestion, tonsils not enlarged. Clear breath sounds bilaterally, no rales or wheezing..." 
                          required><?= h($input['objective'] ?? '') ?></textarea>
                <?php if ($hasFieldError('objective')): ?>
                    <div class="invalid-feedback"><?= h($getFieldError('objective')) ?></div>
                <?php endif; ?>
            </div>

            <!-- Assessment / Impression -->
            <div class="mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                    <label for="assessment" class="form-label fw-bold text-dark mb-0">
                        Assessment / Impression <span class="text-danger">*</span>
                    </label>
                </div>
                <div class="form-text text-muted small mb-2">Primary clinical diagnosis, secondary impressions, or differential diagnosis.</div>
                
                <!-- Quick Diagnoses Chips Ribbon -->
                <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                    <span class="text-secondary small me-1">Diagnoses:</span>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="assessment" data-snippet="Acute Upper Respiratory Tract Infection (URTI)">
                        + URTI
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="assessment" data-snippet="Essential Hypertension - Stage 1">
                        + HTN Stage 1
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="assessment" data-snippet="Essential Hypertension - Stage 2">
                        + HTN Stage 2
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="assessment" data-snippet="Type 2 Diabetes Mellitus">
                        + Type 2 DM
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="assessment" data-snippet="Acute Gastroenteritis (AGE) with mild dehydration">
                        + AGE / Diarrhea
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="assessment" data-snippet="Bronchial Asthma in acute exacerbation">
                        + Asthma
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="assessment" data-snippet="Urinary Tract Infection (UTI), uncomplicated">
                        + UTI
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="assessment" data-snippet="Tension-type Headache">
                        + Headache
                    </button>
                </div>

                <textarea name="assessment" 
                          id="assessment" 
                          rows="3" 
                          class="form-control <?= $hasFieldError('assessment') ? 'is-invalid' : '' ?>" 
                          placeholder="e.g. Acute Upper Respiratory Tract Infection (URTI) / Acute Nasopharyngitis" 
                          required><?= h($input['assessment'] ?? '') ?></textarea>
                <?php if ($hasFieldError('assessment')): ?>
                    <div class="invalid-feedback"><?= h($getFieldError('assessment')) ?></div>
                <?php endif; ?>
            </div>

            <!-- Treatment -->
            <div class="mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                    <label for="plan" class="form-label fw-bold text-dark mb-0">
                        Treatment <span class="text-danger">*</span>
                    </label>
                </div>
                <div class="form-text text-muted small mb-2">Non-pharmacological advice, lab recommendations, patient instructions, and follow-up return schedule.</div>
                
                <!-- Quick Advice Snippets Ribbon -->
                <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                    <span class="text-secondary small me-1">Quick Advice:</span>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="plan" data-snippet="Increase oral fluid intake. Adequate bed rest. Steam inhalation as needed. Paracetamol for fever or body malaise. Return if high fever persists >3 days or shortness of breath develops.">
                        + URTI Care & Hydration
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="plan" data-snippet="Low salt, low fat diet. Regular 30-minute moderate aerobic exercise. Continue maintenance medications strictly. Daily home BP monitoring. Follow up in 2 weeks.">
                        + HTN Lifestyle & Diet
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="plan" data-snippet="Oral Rehydration Salts (ORS) 1 sachet per loose bowel movement. Soft, non-greasy, bland diet (BRAT). Avoid dairy and sugary drinks. Monitor hydration. Return if persistent vomiting.">
                        + AGE Rehydration Protocol
                    </button>
                    <button type="button" class="btn btn-xs consultation-snippet-btn rounded-pill" data-target="plan" data-snippet="Follow up after 1 week for clinical re-evaluation, or seek immediate emergency consult if symptoms worsen.">
                        + 1-Week Follow Up
                    </button>
                </div>

                <textarea name="plan" 
                          id="plan" 
                          rows="3" 
                          class="form-control <?= $hasFieldError('plan') ? 'is-invalid' : '' ?>" 
                          placeholder="e.g. Increase oral fluid intake. Return if symptoms persist after 3 days. Follow up next week for re-evaluation." 
                          required><?= h($input['plan'] ?? '') ?></textarea>
                <?php if ($hasFieldError('plan')): ?>
                    <div class="invalid-feedback"><?= h($getFieldError('plan')) ?></div>
                <?php endif; ?>
            </div>

            <!-- Structured Prescriptions (Medications) -->
            <div class="mt-4 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-0">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Rx</span>
                            <span>Prescribed Medications (Structured)</span>
                        </label>
                        <div class="form-text text-muted small">Add individual medications dispensed or prescribed for this consultation.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPrescription">
                        + Add Medication
                    </button>
                </div>

                <!-- Formulary Datalist Autocomplete -->
                <datalist id="commonMedicinesDatalist">
                    <option value="Amoxicillin 500mg capsule">
                    <option value="Amoxicillin 250mg/5mL suspension (60mL)">
                    <option value="Co-amoxiclav 625mg tablet">
                    <option value="Cefalexin 500mg capsule">
                    <option value="Ciprofloxacin 500mg tablet">
                    <option value="Cotrimoxazole 800mg/160mg (Bactrim Forte) tablet">
                    <option value="Azithromycin 500mg tablet">
                    <option value="Metronidazole 500mg tablet">
                    <option value="Paracetamol 500mg tablet">
                    <option value="Paracetamol 250mg/5mL syrup (60mL)">
                    <option value="Paracetamol 100mg/mL drops (15mL)">
                    <option value="Mefenamic Acid 500mg capsule">
                    <option value="Ibuprofen 400mg tablet">
                    <option value="Ibuprofen 100mg/5mL suspension (60mL)">
                    <option value="Losartan Potassium 50mg tablet">
                    <option value="Amlodipine Besylate 5mg tablet">
                    <option value="Amlodipine Besylate 10mg tablet">
                    <option value="Metoprolol Tartrate 50mg tablet">
                    <option value="Metformin HCl 500mg tablet">
                    <option value="Gliclazide 30mg MR tablet">
                    <option value="Salbutamol 2mg tablet">
                    <option value="Salbutamol 2mg/5mL syrup (60mL)">
                    <option value="Salbutamol 1mg/mL nebule">
                    <option value="Cetirizine HCl 10mg tablet">
                    <option value="Cetirizine 5mg/5mL syrup (30mL)">
                    <option value="Lagundi 600mg tablet">
                    <option value="Oral Rehydration Salts (ORS) sachet">
                    <option value="Omeprazole 20mg capsule">
                    <option value="Aluminum Hydroxide + Magnesium Hydroxide tablet">
                    <option value="Zinc Sulfate 20mg tablet">
                    <option value="Ferrous Sulfate + Folic Acid tablet">
                    <option value="Vitamin B Complex tablet">
                    <option value="Ascorbic Acid (Vitamin C) 500mg tablet">
                </datalist>

                <!-- SIG Frequency Quick-Chips Strip -->
                <div class="d-flex flex-wrap align-items-center gap-1 py-1.5 mb-2 bg-light rounded border px-2">
                    <span class="text-secondary small me-1">SIG Chips:</span>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill sig-chip" data-sig="OD (Once daily)" title="Once daily">OD</button>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill sig-chip" data-sig="BID (Twice daily)" title="Twice daily">BID</button>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill sig-chip" data-sig="TID (3x a day)" title="Three times a day">TID</button>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill sig-chip" data-sig="QID (4x a day)" title="Four times a day">QID</button>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill sig-chip" data-sig="q8h (Every 8 hrs)" title="Every 8 hours">q8h</button>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill sig-chip" data-sig="PRN (As needed)" title="As needed">PRN</button>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill sig-chip" data-sig="HS (At bedtime)" title="At bedtime">HS</button>
                    <span class="text-muted ms-1 me-1">|</span>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill sig-instruction-chip" data-instruction="Take after meals" title="Take after meals">After meals</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill sig-instruction-chip" data-instruction="Take before meals" title="Take before meals">Before meals</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill sig-instruction-chip" data-instruction="Take with plenty of water" title="Take with plenty of water">With water</button>
                </div>

                <!-- Allergy Conflict Alert Banner -->
                <div id="rxAllergyAlertBanner" class="alert alert-danger py-2 px-3 mb-2 small d-none" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-octagon-fill fs-5 text-danger flex-shrink-0"></i>
                        <div>
                            <strong>Allergy Conflict Alert:</strong> 
                            <span id="rxAllergyAlertText">One or more prescribed medications conflict with the patient's recorded allergies.</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-1" id="prescriptionsTable">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th style="width: 28%;">Medicine Name <span class="text-danger">*</span></th>
                                <th style="width: 18%;">Dosage</th>
                                <th style="width: 18%;">Frequency</th>
                                <th style="width: 16%;">Duration</th>
                                <th style="width: 15%;">Instructions / Sig</th>
                                <th style="width: 5%;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="prescriptionsTableBody">
                            <tr id="noPrescriptionsRow">
                                <td colspan="6" class="text-center text-muted py-3 small">
                                    No structured medications added yet. Click <strong>+ Add Medication</strong> to attach prescriptions.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Audit Attribution & Bottom Form Actions -->
    <div class="card border rounded-3 bg-light p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center text-muted small gap-2">
            <div>
                <strong>Originally Recorded By:</strong> <?= h($consultation['creator_name'] ?? 'Staff') ?>
                <span class="text-secondary">(<?= date('M d, Y h:i A', strtotime($consultation['created_at'])) ?>)</span>
            </div>
            <div>
                <strong>This Update Will Be Recorded By:</strong> <?= h($_SESSION['user_name'] ?? 'Current User') ?>
            </div>
        </div>
    </div>

    <!-- 6. Sticky Bottom Action Dock with Backdrop Blur -->
    <div id="consultationBottomBar" class="card card-premium sticky-form-action-bar mb-4 shadow">
        <div class="card-body p-3 px-md-4 py-md-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary-emphasis small d-none d-md-inline">
                    Fields marked with (<span class="text-danger">*</span>) are mandatory.
                </span>
                <span id="consultationDirtyIndicator" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small d-none align-items-center gap-1">
                    <i class="bi bi-circle-fill text-warning" style="font-size: 0.5rem;"></i> Unsaved changes
                </span>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('/patients/' . $patient['id'] . '#tab-consultations') ?>" class="btn btn-light border px-4 py-2 btn-cancel-consultation">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm" id="btnSubmitConsultation">
                    Update Consultation
                </button>
            </div>
        </div>
    </div>
</form>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // 2. Dynamic Vital Signs Preview Handler
    const vitalsSelect = document.getElementById('vital_signs_id');
    const previewContainer = document.getElementById('vitalsPreviewContainer');

    function updateVitalsPreview() {
        if (!vitalsSelect || !previewContainer) return;

        const selectedOption = vitalsSelect.options[vitalsSelect.selectedIndex];
        if (!selectedOption || selectedOption.getAttribute('data-empty') === '1' || !selectedOption.value) {
            previewContainer.innerHTML = `
                <div class="text-muted small text-center py-1">
                    No vital signs record linked to this consultation encounter.
                </div>
            `;
            return;
        }

        const bp = selectedOption.getAttribute('data-bp') || '-';
        const temp = selectedOption.getAttribute('data-temp') || '-';
        const hr = selectedOption.getAttribute('data-hr') || '-';
        const rr = selectedOption.getAttribute('data-rr') || '-';
        const spo2 = selectedOption.getAttribute('data-spo2') || '';
        const weight = selectedOption.getAttribute('data-weight') || '-';
        const height = selectedOption.getAttribute('data-height') || '-';
        const bmi = selectedOption.getAttribute('data-bmi') || '-';
        const waist = selectedOption.getAttribute('data-waist') || '';
        const notes = selectedOption.getAttribute('data-notes') || '';
        const date = selectedOption.getAttribute('data-date') || '';

        // Multi-threshold BP evaluation
        let bpBadgeClass = 'bg-white text-dark border';
        let bpStatusLabel = '';
        const bpParts = bp.split('/');
        const sys = parseInt(bpParts[0] || '0', 10);
        const dia = parseInt(bpParts[1] || '0', 10);
        if (sys >= 140 || dia >= 90) {
            bpBadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
            bpStatusLabel = '<span class="badge bg-danger text-white ms-1 px-1">Hypertension</span>';
        } else if ((sys >= 120 && sys <= 139) || (dia >= 80 && dia <= 89)) {
            bpBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            bpStatusLabel = '<span class="badge bg-warning text-dark ms-1 px-1">Pre-HTN</span>';
        } else if (sys > 0 && sys < 90) {
            bpBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            bpStatusLabel = '<span class="badge bg-warning text-dark ms-1 px-1">Hypotension</span>';
        }

        // Multi-threshold Temperature evaluation
        let tempBadgeClass = 'bg-white text-dark border';
        let tempStatusLabel = '';
        const tVal = parseFloat(temp);
        if (!isNaN(tVal) && tVal > 0) {
            if (tVal >= 38.0) {
                tempBadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                tempStatusLabel = '<span class="badge bg-danger text-white ms-1 px-1">Fever</span>';
            } else if (tVal >= 37.6) {
                tempBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                tempStatusLabel = '<span class="badge bg-warning text-dark ms-1 px-1">Low Fever</span>';
            } else if (tVal < 35.5) {
                tempBadgeClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                tempStatusLabel = '<span class="badge bg-info text-white ms-1 px-1">Hypothermia</span>';
            }
        }

        // Multi-threshold Heart Rate evaluation
        let hrBadgeClass = 'bg-white text-dark border';
        let hrStatusLabel = '';
        const hrVal = parseInt(hr, 10);
        if (!isNaN(hrVal) && hrVal > 0) {
            if (hrVal > 100) {
                hrBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                hrStatusLabel = '<span class="badge bg-warning text-dark ms-1 px-1">Tachycardia</span>';
            } else if (hrVal < 60) {
                hrBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                hrStatusLabel = '<span class="badge bg-warning text-dark ms-1 px-1">Bradycardia</span>';
            }
        }

        // Multi-threshold Respiratory Rate evaluation
        let rrBadgeClass = 'bg-white text-dark border';
        let rrStatusLabel = '';
        const rrVal = parseInt(rr, 10);
        if (!isNaN(rrVal) && rrVal > 0) {
            if (rrVal > 20) {
                rrBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                rrStatusLabel = '<span class="badge bg-warning text-dark ms-1 px-1">Tachypnea</span>';
            } else if (rrVal < 12) {
                rrBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                rrStatusLabel = '<span class="badge bg-warning text-dark ms-1 px-1">Bradypnea</span>';
            }
        }

        // Multi-threshold SpO2 evaluation
        let spo2BadgeClass = 'bg-white text-dark border';
        let spo2StatusLabel = '';
        const spo2Val = parseInt(spo2, 10);
        if (!isNaN(spo2Val) && spo2Val > 0) {
            if (spo2Val < 95) {
                spo2BadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                spo2StatusLabel = '<span class="badge bg-danger text-white ms-1 px-1">Hypoxia</span>';
            }
        }

        // Multi-threshold BMI evaluation (WHO Asian Criteria)
        let bmiBadgeClass = 'bg-white text-dark border';
        let bmiStatusLabel = '';
        const bmiVal = parseFloat(bmi);
        if (!isNaN(bmiVal) && bmiVal > 0) {
            if (bmiVal >= 25.0) {
                bmiBadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                bmiStatusLabel = '<span class="badge bg-danger text-white ms-1 px-1">Obese</span>';
            } else if (bmiVal >= 23.0) {
                bmiBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                bmiStatusLabel = '<span class="badge bg-warning text-dark ms-1 px-1">Overweight</span>';
            } else if (bmiVal < 18.5) {
                bmiBadgeClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                bmiStatusLabel = '<span class="badge bg-info text-white ms-1 px-1">Underweight</span>';
            }
        }

        previewContainer.innerHTML = `
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2 small">
                    <span class="fw-bold text-dark me-1">${escapeHtml(date)}:</span>
                    <span class="badge ${bpBadgeClass} py-2 px-2 fw-normal">
                        BP: <strong>${escapeHtml(bp)} mmHg</strong>${bpStatusLabel}
                    </span>
                    <span class="badge ${tempBadgeClass} py-2 px-2 fw-normal">
                        Temp: <strong>${escapeHtml(temp)} °C</strong>${tempStatusLabel}
                    </span>
                    <span class="badge ${hrBadgeClass} py-2 px-2 fw-normal">
                        HR: <strong>${escapeHtml(hr)} bpm</strong>${hrStatusLabel}
                    </span>
                    <span class="badge ${rrBadgeClass} py-2 px-2 fw-normal">
                        RR: <strong>${escapeHtml(rr)} cpm</strong>${rrStatusLabel}
                    </span>
                    ${spo2 ? `<span class="badge ${spo2BadgeClass} py-2 px-2 fw-normal">SpO2: <strong>${escapeHtml(spo2)}%</strong>${spo2StatusLabel}</span>` : ''}
                    <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                        Weight: <strong>${escapeHtml(weight)} kg</strong>
                    </span>
                    <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                        Height: <strong>${escapeHtml(height)} cm</strong>
                    </span>
                    <span class="badge ${bmiBadgeClass} py-2 px-2 fw-normal">
                        BMI: <strong>${escapeHtml(bmi)}</strong>${bmiStatusLabel}
                    </span>
                </div>
                <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-primary small" data-bs-toggle="collapse" data-bs-target="#vitalsDetailsCollapse">
                    Full Details <i class="bi bi-chevron-down ms-1"></i>
                </button>
            </div>

            <div class="collapse border-top mt-2 pt-2" id="vitalsDetailsCollapse">
                <div class="row g-2 small text-secondary">
                    ${waist ? `<div class="col-6 col-md-3">Waist Circumference: <strong class="text-dark">${escapeHtml(waist)} cm</strong></div>` : ''}
                    ${notes ? `<div class="col-12 mt-1">Triage Notes: <span class="text-dark fst-italic">${escapeHtml(notes)}</span></div>` : '<div class="col-12 text-muted fst-italic">No additional triage notes recorded for this entry.</div>'}
                </div>
            </div>
        `;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    if (vitalsSelect) {
        vitalsSelect.addEventListener('change', updateVitalsPreview);
        // Initialize preview on page load
        updateVitalsPreview();
    }

    // Dynamic Structured Prescriptions & Real-time Allergy Cross-Checking
    const patientAllergy = <?= json_encode($alerts['allergy_alert'] ?? '') ?>;

    const ALLERGY_GROUPS = [
        {
            name: 'Penicillins / Beta-lactams',
            triggers: ['penicillin', 'amoxicillin', 'amox', 'ampicillin', 'cloxacillin', 'co-amoxiclav', 'augmentin', 'piperacillin', 'beta-lactam', 'pen'],
            meds: ['penicillin', 'amoxicillin', 'amox', 'ampicillin', 'cloxacillin', 'co-amoxiclav', 'augmentin', 'piperacillin', 'cefalexin', 'cephalexin', 'cefuroxime', 'ceftriaxone', 'cefixime', 'cefaclor']
        },
        {
            name: 'Cephalosporins',
            triggers: ['cephalosporin', 'cefalexin', 'cephalexin', 'cefuroxime', 'ceftriaxone', 'cefixime', 'cefaclor'],
            meds: ['cefalexin', 'cephalexin', 'cefuroxime', 'ceftriaxone', 'cefixime', 'cefaclor', 'penicillin', 'amoxicillin']
        },
        {
            name: 'NSAIDs / Aspirin',
            triggers: ['nsaid', 'aspirin', 'ibuprofen', 'mefenamic', 'naproxen', 'ketorolac', 'diclofenac', 'celecoxib', 'ponstan', 'advil', 'alaxan'],
            meds: ['aspirin', 'ibuprofen', 'mefenamic', 'naproxen', 'ketorolac', 'diclofenac', 'celecoxib', 'ponstan', 'advil', 'alaxan']
        },
        {
            name: 'Sulfonamides (Sulfa drugs)',
            triggers: ['sulfa', 'sulfonamide', 'cotrimoxazole', 'bactrim', 'sulfamethoxazole', 'trimethoprim', 'sulfadiazine'],
            meds: ['sulfa', 'cotrimoxazole', 'bactrim', 'sulfamethoxazole', 'trimethoprim', 'sulfadiazine']
        },
        {
            name: 'Paracetamol / Acetaminophen',
            triggers: ['paracetamol', 'acetaminophen', 'biogesic', 'tempra', 'calpol'],
            meds: ['paracetamol', 'acetaminophen', 'biogesic', 'tempra', 'calpol']
        },
        {
            name: 'Macrolides',
            triggers: ['azithromycin', 'clarithromycin', 'erythromycin', 'macrolide'],
            meds: ['azithromycin', 'clarithromycin', 'erythromycin']
        },
        {
            name: 'Fluoroquinolones',
            triggers: ['ciprofloxacin', 'cipro', 'levofloxacin', 'ofloxacin', 'quinolone'],
            meds: ['ciprofloxacin', 'cipro', 'levofloxacin', 'ofloxacin']
        }
    ];

    function checkAllergyConflict(medicineName, allergyStr) {
        if (!allergyStr || !medicineName) return { conflict: false };
        const cleanAllergy = allergyStr.toLowerCase();
        const cleanMed = medicineName.toLowerCase().trim();
        if (cleanMed.length < 2) return { conflict: false };

        // 1. Check clinical cross-reactivity groups
        for (const group of ALLERGY_GROUPS) {
            const matchesTrigger = group.triggers.some(t => cleanAllergy.includes(t));
            if (matchesTrigger) {
                const matchesMed = group.meds.some(m => cleanMed.includes(m));
                if (matchesMed) {
                    return {
                        conflict: true,
                        reason: `Cross-reactivity risk with allergy (${allergyStr}) [${group.name}]`
                    };
                }
            }
        }

        // 2. Direct string / substring comparison
        const tokens = cleanAllergy.split(/[,;\/]+/).map(s => s.trim()).filter(s => s.length >= 3);
        for (const token of tokens) {
            if (cleanMed.includes(token) || (cleanMed.length >= 4 && token.includes(cleanMed))) {
                return {
                    conflict: true,
                    reason: `Direct match with patient allergy "${token}"`
                };
            }
        }

        return { conflict: false };
    }

    let prescriptionIndex = 0;
    const btnAddPrescription = document.getElementById('btnAddPrescription');
    const prescriptionsTableBody = document.getElementById('prescriptionsTableBody');
    const noPrescriptionsRow = document.getElementById('noPrescriptionsRow');

    function validateRowAllergy(tr) {
        const medInput = tr.querySelector('input[name*="[medicine_name]"]');
        const warningPill = tr.querySelector('.allergy-warning-pill');
        const warningText = warningPill ? warningPill.querySelector('span') : null;
        if (!medInput || !warningPill) return;

        const result = checkAllergyConflict(medInput.value, patientAllergy);
        if (result.conflict) {
            medInput.classList.add('border-danger');
            warningPill.classList.remove('d-none');
            if (warningText) warningText.textContent = result.reason;
        } else {
            medInput.classList.remove('border-danger');
            warningPill.classList.add('d-none');
            if (warningText) warningText.textContent = '';
        }
        updateAllergyBanner();
    }

    function updateAllergyBanner() {
        const banner = document.getElementById('rxAllergyAlertBanner');
        const bannerText = document.getElementById('rxAllergyAlertText');
        if (!banner) return;

        const activeConflicts = [];
        document.querySelectorAll('#prescriptionsTableBody tr:not(#noPrescriptionsRow)').forEach(tr => {
            const medInput = tr.querySelector('input[name*="[medicine_name]"]');
            if (medInput && medInput.value.trim()) {
                const res = checkAllergyConflict(medInput.value, patientAllergy);
                if (res.conflict) {
                    activeConflicts.push(medInput.value.trim());
                }
            }
        });

        if (activeConflicts.length > 0) {
            banner.classList.remove('d-none');
            if (bannerText) {
                bannerText.innerHTML = `Prescribed medication <strong>${escapeHtml(activeConflicts.join(', '))}</strong> conflicts with the patient's recorded allergy: <em>${escapeHtml(patientAllergy)}</em>. Verify with clinician before administering or dispensing.`;
            }
        } else {
            banner.classList.add('d-none');
        }
    }

    let lastFocusedRow = null;

    function getTargetPrescriptionRow() {
        if (lastFocusedRow && document.body.contains(lastFocusedRow)) {
            return lastFocusedRow;
        }
        const rows = prescriptionsTableBody.querySelectorAll('tr:not(#noPrescriptionsRow)');
        if (rows.length > 0) {
            return rows[rows.length - 1];
        }
        addPrescriptionRow();
        const newRows = prescriptionsTableBody.querySelectorAll('tr:not(#noPrescriptionsRow)');
        return newRows[newRows.length - 1];
    }

    function addPrescriptionRow(med = {}) {
        if (noPrescriptionsRow) {
            noPrescriptionsRow.style.display = 'none';
        }
        const idx = prescriptionIndex++;
        const tr = document.createElement('tr');
        tr.id = `prescription_row_${idx}`;
        tr.innerHTML = `
            <td>
                <input type="text" name="prescriptions[${idx}][medicine_name]" list="commonMedicinesDatalist" class="form-control form-control-sm" placeholder="e.g. Amoxicillin" value="${escapeHtml(med.medicine_name || '')}" aria-label="Medicine Name" required>
                <div class="allergy-warning-pill text-danger small mt-1 fw-semibold d-none" style="font-size: 0.75rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i><span></span>
                </div>
            </td>
            <td>
                <input type="text" name="prescriptions[${idx}][dosage]" class="form-control form-control-sm" placeholder="e.g. 500mg capsule" value="${escapeHtml(med.dosage || '')}" aria-label="Dosage">
            </td>
            <td>
                <input type="text" name="prescriptions[${idx}][frequency]" class="form-control form-control-sm" placeholder="e.g. 3x a day (every 8 hrs)" value="${escapeHtml(med.frequency || '')}" aria-label="Frequency">
            </td>
            <td>
                <input type="text" name="prescriptions[${idx}][duration]" class="form-control form-control-sm" placeholder="e.g. 7 days" value="${escapeHtml(med.duration || '')}" aria-label="Duration">
            </td>
            <td>
                <input type="text" name="prescriptions[${idx}][instructions]" class="form-control form-control-sm" placeholder="e.g. Take after meals" value="${escapeHtml(med.instructions || '')}" aria-label="Instructions / Sig">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger p-1 border-0" title="Remove" aria-label="Remove medication row" onclick="window.removePrescriptionRow(${idx})">
                    <i class="bi bi-trash fs-6"></i>
                </button>
            </td>
        `;
        prescriptionsTableBody.appendChild(tr);

        // Track focus for SIG quick-chips targeting
        tr.addEventListener('focusin', function() {
            lastFocusedRow = tr;
        });

        // Bind real-time allergy cross-check & auto-fill dosage on input
        const medInput = tr.querySelector('input[name*="[medicine_name]"]');
        if (medInput) {
            medInput.addEventListener('input', () => validateRowAllergy(tr));
            medInput.addEventListener('blur', () => validateRowAllergy(tr));
            medInput.addEventListener('change', function() {
                validateRowAllergy(tr);
                const val = this.value.trim();
                const dosageInput = tr.querySelector('input[name*="[dosage]"]');
                if (dosageInput && !dosageInput.value.trim()) {
                    const match = val.match(/(\d+(?:\.\d+)?\s*(?:mg|g|mcg|mL|%)(?:\/\d+(?:\.\d+)?\s*(?:mL|mg))?\s*(?:capsule|tablet|syrup|suspension|drops|nebule|sachet)?)/i);
                    if (match) {
                        dosageInput.value = match[0].trim();
                        dosageInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });
            if (med.medicine_name) {
                validateRowAllergy(tr);
            }
        }

        return tr;
    }

    window.removePrescriptionRow = function(idx) {
        const row = document.getElementById(`prescription_row_${idx}`);
        if (row) {
            if (lastFocusedRow === row) {
                lastFocusedRow = null;
            }
            row.remove();
        }
        const activeRows = prescriptionsTableBody.querySelectorAll('tr:not(#noPrescriptionsRow)');
        if (activeRows.length === 0 && noPrescriptionsRow) {
            noPrescriptionsRow.style.display = '';
        }
        updateAllergyBanner();
        if (typeof updateDirtyBadge === 'function') {
            updateDirtyBadge();
        }
    };

    if (btnAddPrescription) {
        btnAddPrescription.addEventListener('click', function() {
            const newRow = addPrescriptionRow();
            if (newRow) {
                const medInput = newRow.querySelector('input[name*="[medicine_name]"]');
                if (medInput) {
                    medInput.focus();
                }
            }
            if (typeof updateDirtyBadge === 'function') {
                updateDirtyBadge();
            }
        });
    }

    // SIG Frequency Quick-Chips Handlers
    document.querySelectorAll('.sig-chip').forEach(btn => {
        btn.addEventListener('click', function() {
            const sig = this.getAttribute('data-sig');
            const row = getTargetPrescriptionRow();
            if (row) {
                const freqInput = row.querySelector('input[name*="[frequency]"]');
                if (freqInput) {
                    freqInput.value = sig;
                    freqInput.classList.add('bg-primary-subtle');
                    setTimeout(() => freqInput.classList.remove('bg-primary-subtle'), 400);
                    freqInput.focus();
                    freqInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        });
    });

    // SIG Instructions Quick-Chips Handlers
    document.querySelectorAll('.sig-instruction-chip').forEach(btn => {
        btn.addEventListener('click', function() {
            const inst = this.getAttribute('data-instruction');
            const row = getTargetPrescriptionRow();
            if (row) {
                const instInput = row.querySelector('input[name*="[instructions]"]');
                if (instInput) {
                    if (instInput.value.trim()) {
                        if (!instInput.value.includes(inst)) {
                            instInput.value += ', ' + inst;
                        }
                    } else {
                        instInput.value = inst;
                    }
                    instInput.classList.add('bg-secondary-subtle');
                    setTimeout(() => instInput.classList.remove('bg-secondary-subtle'), 400);
                    instInput.focus();
                    instInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        });
    });

    // Clinical Documentation Snippets Handlers (PE, Diagnoses, Treatment Advice)
    document.querySelectorAll('.consultation-snippet-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const snippet = this.getAttribute('data-snippet');
            const targetEl = document.getElementById(targetId);
            if (!targetEl) return;

            if (targetEl.value.trim() === '') {
                targetEl.value = snippet;
            } else {
                if (!targetEl.value.includes(snippet)) {
                    targetEl.value = targetEl.value.trim() + (targetId === 'assessment' ? '; ' : "\n\n") + snippet;
                }
            }

            targetEl.classList.add('border-primary');
            setTimeout(() => targetEl.classList.remove('border-primary'), 400);
            targetEl.focus();
            targetEl.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    // Pre-populate existing medications if any
    const existingPrescriptions = <?= json_encode($prescriptions ?? []) ?>;
    if (Array.isArray(existingPrescriptions) && existingPrescriptions.length > 0) {
        existingPrescriptions.forEach(p => addPrescriptionRow(p));
    }

    // Accessible in-page anchor jump links & smooth-focus
    const firstInvalid = document.querySelector('.is-invalid');
    if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus({ preventScroll: true });
    }

    document.querySelectorAll('.error-jump-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').substring(1);
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                e.preventDefault();
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetEl.focus();
            }
        });
    });

    // =========================================================================
    // Robust Unsaved Changes Guard (Snapshot-based check)
    // =========================================================================
    const mainForm = document.getElementById('consultationForm') || document.querySelector('form[action*="consultations"]');
    let initialConsultationData = '';
    let isSubmitting = false;

    function getConsultationFormData() {
        if (!mainForm) return '';
        return new URLSearchParams(new FormData(mainForm)).toString();
    }

    window.isConsultationDirty = function() {
        if (!mainForm || !initialConsultationData) return false;
        return getConsultationFormData() !== initialConsultationData;
    };

    // Snapshot form state AFTER initial population of fields and prescriptions
    initialConsultationData = getConsultationFormData();

    const dirtyBadge = document.getElementById('consultationDirtyIndicator');
    window.updateConsultationDirtyBadge = function() {
        if (dirtyBadge) {
            if (window.isConsultationDirty()) {
                dirtyBadge.classList.remove('d-none');
                dirtyBadge.classList.add('d-inline-flex');
            } else {
                dirtyBadge.classList.remove('d-inline-flex');
                dirtyBadge.classList.add('d-none');
            }
        }
    };
    window.updateDirtyBadge = window.updateConsultationDirtyBadge;

    if (mainForm) {
        mainForm.addEventListener('input', window.updateConsultationDirtyBadge);
        mainForm.addEventListener('change', window.updateConsultationDirtyBadge);
        mainForm.addEventListener('submit', function() {
            isSubmitting = true;
        });
    }

    // Intercept any link clicks navigating away from the consultation editor
    document.addEventListener('click', function(e) {
        if (isSubmitting) return;

        const link = e.target.closest('a');
        if (!link || !link.href) return;

        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (link.getAttribute('target') === '_blank') return;
        if (link.hasAttribute('data-bs-toggle') || link.hasAttribute('data-bs-target') || link.hasAttribute('data-bs-dismiss')) return;

        if (window.isConsultationDirty()) {
            e.preventDefault();
            e.stopPropagation();
            const targetUrl = link.href;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Discard Unsaved Changes?',
                    text: 'You have modified consultation documentation or medications. If you leave now, all unsaved edits will be permanently lost.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, discard and leave',
                    cancelButtonText: 'Stay and continue editing'
                }).then((result) => {
                    if (result.isConfirmed) {
                        isSubmitting = true;
                        window.location.href = targetUrl;
                    }
                });
            } else {
                if (confirm('Discard unsaved changes? Your edits will be lost.')) {
                    isSubmitting = true;
                    window.location.href = targetUrl;
                }
            }
        }
    }, true);

    // Native beforeunload fallback for tab closing / page reload
    window.addEventListener('beforeunload', function(e) {
        if (isSubmitting) return;
        if (window.isConsultationDirty()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});
</script>
