<?php
$title = 'New Consultation';
$breadcrumbs = [
    'Patients' => '/patients',
    'Profile' => '/patients/' . $patient['id'],
    'New Consultation' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">New Consultation</h2>
        <p class="text-secondary small mb-0">Record SOAP checkup notes, link vital signs, and assign consulting clinicians.</p>
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
?>

<!-- 1. Patient Clinical Profile & Safety Card -->
<div class="card card-premium mb-4 bg-white border">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.1rem;">
                    <?= strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)) ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <strong class="text-dark fs-6"><?= h($patient['last_name']) ?>, <?= h($patient['first_name']) ?><?= !empty($patient['suffix']) ? ' ' . h($patient['suffix']) : '' ?></strong>
                        <span class="badge bg-light text-secondary border font-monospace"><?= h($patient['patient_no']) ?></span>
                    </div>
                    <div class="text-muted small">
                        <?= h($patient['age']) ?> yrs &bull; <?= h($patient['sex']) ?> &bull; Brgy. <?= h($patient['barangay']) ?>
                        <?php if (!empty($patient['blood_type'])): ?>
                            &bull; Blood: <strong class="text-danger"><?= h($patient['blood_type']) ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($patient['philhealth_no'])): ?>
                            &bull; PHIC: <span class="font-monospace text-dark"><?= h($patient['philhealth_no']) ?></span>
                        <?php endif; ?>
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

<form action="<?= url('/consultations') ?>" method="POST" autocomplete="off" id="consultationForm">
    <?= csrf_field() ?>
    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
    <input type="hidden" name="status" value="Completed">

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
                    <label for="consulted_by" class="form-label fw-semibold text-secondary small">Consulting Provider <span class="text-danger">*</span></label>
                    <select name="consulted_by" id="consulted_by" class="form-select bg-light" required>
                        <option value="" disabled>Select Provider</option>
                        <?php foreach ($clinicians as $c): 
                            $cName = $c['first_name'] . ' ' . $c['last_name'];
                            if (!empty($c['job_title'])) {
                                $cName .= " ({$c['job_title']})";
                            }
                            $selected = '';
                            if (empty($input['consulted_by']) && $c['id'] == $_SESSION['user_id']) {
                                $selected = 'selected';
                            } elseif (isset($input['consulted_by']) && (int)$input['consulted_by'] === (int)$c['id']) {
                                $selected = 'selected';
                            }
                        ?>
                            <option value="<?= $c['id'] ?>" <?= $selected ?>>
                                <?= h($cName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                                           value="<?= h($input['consulted_at'] ?? date('Y-m-d\TH:i')) ?>" 
                                           max="<?= date('Y-m-d\TH:i') ?>"
                                           required>
                    </div>
                </div>
            </div>

            <!-- Section A2: Vital Signs Status & Link -->
            <?php if (empty($vitalsList)): ?>
                <div class="card border border-warning-subtle bg-warning-subtle p-3 rounded-3 mb-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill text-warning fs-4"></i>
                            <div>
                                <strong class="text-dark small d-block">No Vital Signs Recorded Yet</strong>
                                <span class="text-muted small">The patient does not have triage vital signs logged for today's visit yet. BHWs or staff can record them now.</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary px-3 text-nowrap shadow-xs" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                            <i class="bi bi-heart-pulse-fill me-1"></i> Record Vital Signs
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="vital_signs_id" class="form-label fw-semibold text-secondary small mb-0">
                            Link Vital Signs Record
                        </label>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                            <i class="bi bi-plus-lg me-1"></i> Record New Vitals
                        </button>
                    </div>
                    <select name="vital_signs_id" id="vital_signs_id" class="form-select bg-light">
                        <option value="" data-empty="1">-- No Linked Vital Signs --</option>
                        <?php 
                            $selectedVitalId = isset($input['vital_signs_id']) ? (int)$input['vital_signs_id'] : ($latestVitals['id'] ?? 0);
                            foreach ($vitalsList as $v):
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
                                $isSelected = ($selectedVitalId === (int)$v['id']) ? 'selected' : '';
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
                    </select>
                </div>

                <!-- Dynamic Vitals Preview Box -->
                <div id="vitalsPreviewContainer" class="p-3 bg-light rounded-3 border mb-4">
                    <!-- Injected dynamically via JS -->
                </div>
            <?php endif; ?>

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
            <div class="mb-2">
                <label for="plan" class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">P</span>
                    <span>Plan &mdash; Treatment, Prescriptions & Recommendations <span class="text-danger">*</span></span>
                </label>
                <div class="form-text text-muted small mb-2">Prescribed medications (dosage/frequency), non-pharmacological advice, lab requests, and follow-up return schedule.</div>
                <textarea name="plan" 
                          id="plan" 
                          rows="3" 
                          class="form-control" 
                          placeholder="e.g. Paracetamol 500mg tab TID PRN for fever. Increase oral fluid intake. Return if symptoms persist after 3 days." 
                          required><?= h($input['plan'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- 5. Bottom Form Actions -->
    <div class="d-flex justify-content-end align-items-center gap-2 mb-5">
        <a href="<?= url('/patients/' . $patient['id'] . '#tab-consultations') ?>" class="btn btn-outline-secondary px-4 py-2">
            Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
            <i class="bi bi-check-circle-fill me-1"></i> Save Consultation
        </button>
    </div>
</form>

<!-- ==========================================================================
   RECORD VITAL SIGNS MODAL (BHW TRIAGE / ON-THE-FLY ENTRY)
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
                <input type="hidden" name="redirect_to" value="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>">

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

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. BMI Auto-calculation in Vitals Modal
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const bmiInput = document.getElementById('bmi');

    function calculateBMI() {
        const weight = parseFloat(weightInput?.value);
        const height = parseFloat(heightInput?.value);
        if (weight > 0 && height > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            if (bmiInput) bmiInput.value = bmi.toFixed(2);
        } else {
            if (bmiInput) bmiInput.value = '';
        }
    }

    if (weightInput && heightInput) {
        weightInput.addEventListener('input', calculateBMI);
        heightInput.addEventListener('input', calculateBMI);
    }

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
        updateVitalsPreview();
    }
});
</script>
