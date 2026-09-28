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
    <a href="<?= url('/patients/' . $patient['id'] . '#tab-consultations') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Profile
    </a>
</div>

<!-- Errors Alert Box -->
<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-danger mb-4 shadow-sm" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following:</div>
        <ul class="mb-0 ps-3 small">
            <?php foreach ($errors as $err): ?>
                <li><?= h($err) ?></li>
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
                                <i class="bi bi-folder2-open me-1"></i>Env #<?= h($patient['envelope_no']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($patient['family_no'])): ?>
                            <a href="<?= url('/patients?search=' . urlencode($patient['family_no'])) ?>" class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace fs-7 text-decoration-none" title="View household in directory">
                                <i class="bi bi-house-door-fill me-1"></i>Fam #<?= h($patient['family_no']) ?>
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
                        <i class="bi bi-geo-alt text-muted flex-shrink-0"></i>
                        <span><?= !empty(trim($patient['address'] ?? '')) ? h(trim($patient['address'])) : '<span class="text-muted fst-italic">No address recorded</span>' ?></span>
                    </div>
                </div>
            </div>

            <!-- Profile Context Action Buttons -->
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($activePrenatal)): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#pregnancyDetailsCollapse" aria-expanded="false" aria-controls="pregnancyDetailsCollapse">
                        <i class="bi bi-heart me-1 text-pink"></i> Obstetric Details <i class="bi bi-chevron-down ms-1"></i>
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#medicalBackgroundCollapse" aria-expanded="false" aria-controls="medicalBackgroundCollapse">
                    <i class="bi bi-file-medical me-1"></i> Medical Background <i class="bi bi-chevron-down ms-1"></i>
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
                        <i class="bi bi-heart-pulse-fill me-1 text-pink"></i> Active Pregnancy
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
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-clipboard-pulse me-1 text-primary"></i>Past Medical History:</div>
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
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-scissors me-1 text-primary"></i>Surgical History:</div>
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
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-people me-1 text-primary"></i>Family History:</div>
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
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-activity me-1 text-primary"></i>Social Habits:</div>
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

    <!-- 2. Unified Consultation & SOAP Encounter Card -->
    <div class="card card-premium mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h3 class="card-title h6 mb-0 fw-bold text-dark">
                <i class="bi bi-journal-medical text-primary me-2"></i>Consultation Encounter & SOAP Notes
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
                           class="form-control bg-light" 
                           list="providerSuggestions" 
                           value="<?= h($currentProvider) ?>" 
                           placeholder="e.g. Juana Dela Cruz, RM or Dr. Juan Dela Cruz, MD" 
                           required>
                    <datalist id="providerSuggestions">
                        <option value="Juana Dela Cruz, RM">
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
                        <i class="bi bi-info-circle me-1"></i>Enter the clinician or midwife who conducted the consultation.
                    </div>
                </div>

                <!-- Consultation Date & Time -->
                <div class="col-12 col-md-6">
                    <label for="consulted_at" class="form-label fw-semibold text-secondary small">Consultation Date & Time <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-clock"></i></span>
                                    <input type="datetime-local" 
                                           name="consulted_at" 
                                           id="consulted_at" 
                                           class="form-control bg-light border-start-0" 
                                           value="<?= date('Y-m-d\TH:i', strtotime($input['consulted_at'] ?? $consultation['consulted_at'] ?? 'now')) ?>" 
                                           max="<?= date('Y-m-d\TH:i') ?>"
                                           required>
                    </div>
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

            <!-- Section B: SOAP Clinical Notes Divider -->
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <span class="fw-bold text-dark fs-6"><i class="bi bi-file-earmark-medical text-primary me-2"></i>SOAP Clinical Documentation</span>
            </div>

            <!-- Subjective (S) -->
            <div class="mb-4">
                <label for="subjective" class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">S</span>
                    <span>Subjective &mdash; Chief Complaint & History of Present Illness <span class="text-danger">*</span></span>
                </label>
                <div class="form-text text-muted small mb-2">Patient's chief complaint, reported symptoms, timeline, and history of present illness.</div>
                <textarea name="subjective" 
                          id="subjective" 
                          rows="3" 
                          class="form-control" 
                          placeholder="e.g. Patient reports persistent dry cough and low-grade fever for 3 days. No difficulty breathing..." 
                          required><?= h($input['subjective'] ?? '') ?></textarea>
            </div>

            <!-- Objective (O) -->
            <div class="mb-4">
                <label for="objective" class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">O</span>
                    <span>Objective &mdash; Physical Examination & Clinical Findings <span class="text-danger">*</span></span>
                </label>
                <div class="form-text text-muted small mb-2">Physical examination observations, organ findings, and linked diagnostic indicators.</div>
                <textarea name="objective" 
                          id="objective" 
                          rows="3" 
                          class="form-control" 
                          placeholder="e.g. Mild pharyngeal congestion, tonsils not enlarged. Clear breath sounds bilaterally, no rales or wheezing..." 
                          required><?= h($input['objective'] ?? '') ?></textarea>
            </div>

            <!-- Assessment (A) -->
            <div class="mb-4">
                <label for="assessment" class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">A</span>
                    <span>Assessment &mdash; Clinical Impression / Diagnosis <span class="text-danger">*</span></span>
                </label>
                <div class="form-text text-muted small mb-2">Primary clinical diagnosis, secondary impressions, or differential diagnosis.</div>
                <textarea name="assessment" 
                          id="assessment" 
                          rows="2" 
                          class="form-control" 
                          placeholder="e.g. Acute Upper Respiratory Tract Infection (URTI) / Acute Nasopharyngitis" 
                          required><?= h($input['assessment'] ?? '') ?></textarea>
            </div>

            <!-- Plan (P) -->
            <div class="mb-4">
                <label for="plan" class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">P</span>
                    <span>Plan &mdash; General Treatment Plan & Recommendations <span class="text-danger">*</span></span>
                </label>
                <div class="form-text text-muted small mb-2">Non-pharmacological advice, lab recommendations, patient instructions, and follow-up return schedule.</div>
                <textarea name="plan" 
                          id="plan" 
                          rows="3" 
                          class="form-control" 
                          placeholder="e.g. Increase oral fluid intake. Return if symptoms persist after 3 days. Follow up next week for re-evaluation." 
                          required><?= h($input['plan'] ?? '') ?></textarea>
            </div>

            <!-- Structured Prescriptions (Medications) -->
            <div class="mt-4 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-0">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-capsule"></i> Rx</span>
                            <span>Prescribed Medications (Structured)</span>
                        </label>
                        <div class="form-text text-muted small">Add individual medications dispensed or prescribed for this consultation.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPrescription">
                        <i class="bi bi-plus-circle me-1"></i> Add Medication
                    </button>
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
                                    No structured medications added yet. Click <strong>Add Medication</strong> to attach prescriptions.
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
                <i class="bi bi-person-fill text-secondary me-1"></i>
                <strong>Originally Recorded By:</strong> <?= h($consultation['creator_name'] ?? 'Staff') ?>
                <span class="text-secondary">(<?= date('M d, Y h:i A', strtotime($consultation['created_at'])) ?>)</span>
            </div>
            <div>
                <i class="bi bi-pencil-square text-primary me-1"></i>
                <strong>This Update Will Be Recorded By:</strong> <?= h($_SESSION['user_name'] ?? 'Current User') ?>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end align-items-center gap-2 mb-5">
        <a href="<?= url('/patients/' . $patient['id'] . '#tab-consultations') ?>" class="btn btn-outline-secondary px-4 py-2">
            Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
            <i class="bi bi-check-circle-fill me-1"></i> Update Consultation
        </button>
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
                    <i class="bi bi-info-circle me-1"></i> No vital signs record linked to this consultation encounter.
                </div>
            `;
            return;
        }

        const bp = selectedOption.getAttribute('data-bp') || '-';
        const isHighBp = selectedOption.getAttribute('data-bp-high') === '1';
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

        previewContainer.innerHTML = `
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2 small">
                    <span class="fw-bold text-dark me-1"><i class="bi bi-speedometer2 text-primary me-1"></i> ${escapeHtml(date)}:</span>
                    <span class="badge ${isHighBp ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-white text-dark border'} py-2 px-2 fw-normal">
                        BP: <strong class="${isHighBp ? 'text-danger' : 'text-dark'}">${escapeHtml(bp)} mmHg</strong>
                    </span>
                    <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                        Temp: <strong class="text-dark">${escapeHtml(temp)} °C</strong>
                    </span>
                    <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                        HR: <strong class="text-dark">${escapeHtml(hr)} bpm</strong>
                    </span>
                    <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                        RR: <strong class="text-dark">${escapeHtml(rr)} cpm</strong>
                    </span>
                    ${spo2 ? `<span class="badge bg-white text-dark border py-2 px-2 fw-normal">SpO2: <strong class="text-dark">${escapeHtml(spo2)}%</strong></span>` : ''}
                    <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                        Weight: <strong class="text-dark">${escapeHtml(weight)} kg</strong>
                    </span>
                    <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                        Height: <strong class="text-dark">${escapeHtml(height)} cm</strong>
                    </span>
                    <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                        BMI: <strong class="text-dark">${escapeHtml(bmi)}</strong>
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

    // Dynamic Structured Prescriptions
    let prescriptionIndex = 0;
    const btnAddPrescription = document.getElementById('btnAddPrescription');
    const prescriptionsTableBody = document.getElementById('prescriptionsTableBody');
    const noPrescriptionsRow = document.getElementById('noPrescriptionsRow');

    function addPrescriptionRow(med = {}) {
        if (noPrescriptionsRow) {
            noPrescriptionsRow.style.display = 'none';
        }
        const idx = prescriptionIndex++;
        const tr = document.createElement('tr');
        tr.id = `prescription_row_${idx}`;
        tr.innerHTML = `
            <td>
                <input type="text" name="prescriptions[${idx}][medicine_name]" class="form-control form-control-sm" placeholder="e.g. Amoxicillin" value="${escapeHtml(med.medicine_name || '')}" required>
            </td>
            <td>
                <input type="text" name="prescriptions[${idx}][dosage]" class="form-control form-control-sm" placeholder="e.g. 500mg capsule" value="${escapeHtml(med.dosage || '')}">
            </td>
            <td>
                <input type="text" name="prescriptions[${idx}][frequency]" class="form-control form-control-sm" placeholder="e.g. 3x a day (every 8 hrs)" value="${escapeHtml(med.frequency || '')}">
            </td>
            <td>
                <input type="text" name="prescriptions[${idx}][duration]" class="form-control form-control-sm" placeholder="e.g. 7 days" value="${escapeHtml(med.duration || '')}">
            </td>
            <td>
                <input type="text" name="prescriptions[${idx}][instructions]" class="form-control form-control-sm" placeholder="e.g. Take after meals" value="${escapeHtml(med.instructions || '')}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger p-1 border-0" title="Remove" onclick="window.removePrescriptionRow(${idx})">
                    <i class="bi bi-trash fs-6"></i>
                </button>
            </td>
        `;
        prescriptionsTableBody.appendChild(tr);
    }

    window.removePrescriptionRow = function(idx) {
        const row = document.getElementById(`prescription_row_${idx}`);
        if (row) {
            row.remove();
        }
        const activeRows = prescriptionsTableBody.querySelectorAll('tr:not(#noPrescriptionsRow)');
        if (activeRows.length === 0 && noPrescriptionsRow) {
            noPrescriptionsRow.style.display = '';
        }
    };

    if (btnAddPrescription) {
        btnAddPrescription.addEventListener('click', function() {
            addPrescriptionRow();
        });
    }

    // Pre-populate existing medications if any
    const existingPrescriptions = <?= json_encode($prescriptions ?? []) ?>;
    if (Array.isArray(existingPrescriptions) && existingPrescriptions.length > 0) {
        existingPrescriptions.forEach(p => addPrescriptionRow(p));
    }
});
</script>
