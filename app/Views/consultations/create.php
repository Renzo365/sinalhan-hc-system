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
    <a href="<?= url('/patients/' . $patient['id']) ?>" class="btn btn-outline-secondary">
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

<form action="<?= url('/consultations') ?>" method="POST" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

    <div class="row g-4">
        <!-- Left Column: SOAP Form Inputs -->
        <div class="col-12 col-lg-8">
            <!-- Patient Info Card Header -->
            <div class="card card-premium mb-4 bg-light border-0">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small">
                        <div><span class="text-muted">Patient Name:</span> <strong class="text-dark fs-6"><?= h($patient['last_name']) ?>, <?= h($patient['first_name']) ?></strong></div>
                        <div><span class="text-muted">ID:</span> <strong class="text-dark"><?= h($patient['patient_no']) ?></strong></div>
                        <div><span class="text-muted">Age/Sex:</span> <strong class="text-dark"><?= h($patient['age']) ?> yrs / <?= h($patient['sex']) ?></strong></div>
                        <div><span class="text-muted">Barangay:</span> <strong class="text-dark">Brgy. <?= h($patient['barangay']) ?></strong></div>
                    </div>
                </div>
            </div>

            <!-- SOAP note blocks -->
            <div class="card card-premium mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h3 class="card-title h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-journal-medical text-primary me-2"></i>Clinical Documentation (SOAP)
                    </h3>
                    <a href="<?= url('/patients/' . $patient['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;">
                        <i class="bi bi-person-badge me-1"></i> Open Full Patient Workstation <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-4">
                    <!-- CLINICAL SAFETY ALERTS & DECISION SUPPORT -->
                    <?php
                        $pmh = !empty($medicalHistory['past_medical_history']) ? (is_array($medicalHistory['past_medical_history']) ? $medicalHistory['past_medical_history'] : (json_decode($medicalHistory['past_medical_history'], true) ?: [])) : [];
                        $allergyAlert = $pmh['Allergy'] ?? $pmh['Allergies'] ?? '';
                        $hasHypertensionAlert = isset($pmh['Hypertension']);
                        $hasDiabetesAlert = isset($pmh['Diabetes Mellitus']);
                        $hasAsthmaAlert = isset($pmh['Asthma']) || isset($pmh['Bronchial Asthma']);
                    ?>

                    <?php if (!empty($allergyAlert)): ?>
                        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-3" style="border-radius: 10px; background-color: #fee2e2;">
                            <i class="bi bi-exclamation-octagon-fill text-danger fs-3"></i>
                            <div>
                                <strong class="text-danger d-block fs-6">⚠️ DRUG / FOOD ALLERGIES RECORDED</strong>
                                <span class="text-dark small">Patient is known allergic to: <strong><?= h($allergyAlert) ?></strong>. Double-check prescription safety.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($activePrenatal)): ?>
                        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-3" style="border-radius: 10px; background-color: #ffe4e6;">
                            <i class="bi bi-heart-pulse-fill text-pink fs-3"></i>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-danger fs-6">🤰 ACTIVE PREGNANCY EPISODE</strong>
                                    <span class="badge bg-pink text-white">G<?= h($activePrenatal['gravida']) ?>P<?= h($activePrenatal['para']) ?></span>
                                    <?php if (!empty($activePrenatal['pre_eclampsia'])): ?>
                                        <span class="badge bg-danger text-white">⚡ Pre-Eclampsia High-Risk</span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-dark small">
                                    LMP: <strong><?= date('M d, Y', strtotime($activePrenatal['lmp'])) ?></strong> &bull; 
                                    EDC: <strong><?= date('M d, Y', strtotime($activePrenatal['edc'])) ?></strong> &bull; 
                                    Current AOG: <strong><?= h($activePrenatal['aog_weeks'] ?? 'N/A') ?> wks</strong>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasHypertensionAlert || $hasDiabetesAlert || $hasAsthmaAlert): ?>
                        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 p-2 px-3 mb-3" style="border-radius: 10px;">
                            <i class="bi bi-activity text-warning fs-4"></i>
                            <div class="small">
                                <strong>CHRONIC ILLNESS HISTORY:</strong>
                                <?php if ($hasHypertensionAlert): ?><span class="badge bg-danger ms-1">Hypertension</span><?php endif; ?>
                                <?php if ($hasDiabetesAlert): ?><span class="badge bg-warning text-dark ms-1">Diabetes Mellitus</span><?php endif; ?>
                                <?php if ($hasAsthmaAlert): ?><span class="badge bg-info text-dark ms-1">Asthma</span><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Latest Vital Signs Quick Strip -->
                    <?php if (!empty($latestVitals)): ?>
                        <div class="p-2 px-3 bg-light rounded-3 mb-4 border d-flex flex-wrap align-items-center gap-3 small text-secondary">
                            <span class="fw-bold text-dark"><i class="bi bi-speedometer2 text-primary me-1"></i> Latest Vitals (<?= date('M d', strtotime($latestVitals['recorded_at'])) ?>):</span>
                            <span>BP: <strong class="<?= ((int)($latestVitals['bp_systolic'] ?? 0) >= 140 || (int)($latestVitals['bp_diastolic'] ?? 0) >= 90) ? 'text-danger fw-bold' : 'text-dark' ?>"><?= h($latestVitals['bp_systolic'] ?? '-') ?>/<?= h($latestVitals['bp_diastolic'] ?? '-') ?> mmHg</strong></span>
                            <span class="vr"></span>
                            <span>Temp: <strong class="text-dark"><?= h($latestVitals['temperature'] ?? '-') ?> °C</strong></span>
                            <span class="vr"></span>
                            <span>HR: <strong class="text-dark"><?= h($latestVitals['heart_rate'] ?? '-') ?> bpm</strong></span>
                            <span class="vr"></span>
                            <span>RR: <strong class="text-dark"><?= h($latestVitals['respiratory_rate'] ?? '-') ?> cpm</strong></span>
                            <?php if (!empty($latestVitals['oxygen_saturation'])): ?>
                                <span class="vr"></span>
                                <span>SpO2: <strong class="text-dark"><?= h($latestVitals['oxygen_saturation']) ?>%</strong></span>
                            <?php endif; ?>
                            <?php if (!empty($latestVitals['bmi'])): ?>
                                <span class="vr"></span>
                                <span>BMI: <strong class="text-dark"><?= h($latestVitals['bmi']) ?></strong></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Subjective (S) -->
                    <div class="mb-4">
                        <label for="subjective" class="form-label fw-bold text-primary-dark">Subjective (S) <span class="text-danger">*</span></label>
                        <div class="form-text text-muted small mb-2">Chief complaint, symptoms reported by the patient, and history of present illness.</div>
                        <textarea name="subjective" 
                                  id="subjective" 
                                  rows="3" 
                                  class="form-control" 
                                  placeholder="Patient reports headache, fever for 2 days..." 
                                  required><?= h($input['subjective'] ?? '') ?></textarea>
                    </div>

                    <!-- Objective (O) -->
                    <div class="mb-4">
                        <label for="objective" class="form-label fw-bold text-primary-dark">Objective (O) <span class="text-danger">*</span></label>
                        <div class="form-text text-muted small mb-2">Physical examination findings, observations, and vital signs indicators.</div>
                        <textarea name="objective" 
                                  id="objective" 
                                  rows="3" 
                                  class="form-control" 
                                  placeholder="Clear breath sounds, temp 38.5C, throat congested..." 
                                  required><?= h($input['objective'] ?? '') ?></textarea>
                    </div>

                    <!-- Assessment (A) -->
                    <div class="mb-4">
                        <label for="assessment" class="form-label fw-bold text-primary-dark">Assessment (A) <span class="text-danger">*</span></label>
                        <div class="form-text text-muted small mb-2">Clinical diagnosis, impressions, or condition analysis.</div>
                        <textarea name="assessment" 
                                  id="assessment" 
                                  rows="2" 
                                  class="form-control" 
                                  placeholder="Acute Tonsillitis / Suspected Viral Infection..." 
                                  required><?= h($input['assessment'] ?? '') ?></textarea>
                    </div>

                    <!-- Plan (P) -->
                    <div class="mb-0">
                        <label for="plan" class="form-label fw-bold text-primary-dark">Plan (P) <span class="text-danger">*</span></label>
                        <div class="form-text text-muted small mb-2">Treatment advice, prescription details, recommended diagnostic tests, and follow-up schedules.</div>
                        <textarea name="plan" 
                                  id="plan" 
                                  rows="3" 
                                  class="form-control" 
                                  placeholder="Prescribe Paracetamol 500mg, rest, fluid intake. Follow up in 3 days..." 
                                  required><?= h($input['plan'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side Panel: Settings & Meta details -->
        <div class="col-12 col-lg-4">
            <!-- Vitals Link Card -->
            <div class="card card-premium mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="card-title h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-activity text-primary me-2"></i>Link Vital Signs
                    </h3>
                </div>
                <div class="card-body p-4">
                    <label for="vital_signs_id" class="form-label fw-semibold text-secondary small">Select Vitals Entry</label>
                    <select name="vital_signs_id" id="vital_signs_id" class="form-select bg-light text-dark small">
                        <option value="">-- No Linked Vitals --</option>
                        <?php if (!empty($vitalsList)): ?>
                            <?php foreach ($vitalsList as $idx => $v): 
                                $vLabel = date('M d, Y h:i A', strtotime($v['recorded_at']));
                                if ($v['bp_systolic'] && $v['bp_diastolic']) {
                                    $vLabel .= " | BP: {$v['bp_systolic']}/{$v['bp_diastolic']}";
                                }
                                if ($v['temperature']) {
                                    $vLabel .= " | Temp: {$v['temperature']}°C";
                                }
                                if ($v['weight']) {
                                    $vLabel .= " | Wt: {$v['weight']}kg";
                                }
                                $isSelected = '';
                                // Preselect the latest vital signs by default
                                if (empty($input['vital_signs_id']) && $idx === 0) {
                                    $isSelected = 'selected';
                                } elseif (isset($input['vital_signs_id']) && (int)$input['vital_signs_id'] === (int)$v['id']) {
                                    $isSelected = 'selected';
                                }
                            ?>
                                <option value="<?= $v['id'] ?>" <?= $isSelected ?>>
                                    <?= h($vLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text text-muted small mt-2">
                        Associates the selected vital metrics with this consultation.
                    </div>
                </div>
            </div>

            <!-- Clinician & Timestamp details -->
            <div class="card card-premium mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="card-title h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-person-badge text-primary me-2"></i>Consultation Metadata
                    </h3>
                </div>
                <div class="card-body p-4">
                    <!-- Consulted By -->
                    <div class="mb-3">
                        <label for="consulted_by" class="form-label fw-semibold text-secondary small">Consulting Provider <span class="text-danger">*</span></label>
                        <select name="consulted_by" id="consulted_by" class="form-select bg-light" required>
                            <option value="" disabled>Select Provider</option>
                            <?php foreach ($clinicians as $c): 
                                $cName = $c['first_name'] . ' ' . $c['last_name'];
                                if ($c['job_title']) {
                                    $cName .= " ({$c['job_title']})";
                                }
                                // Default select currently logged-in user
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

                    <!-- Consulted At -->
                    <div class="mb-3">
                        <label for="consulted_at" class="form-label fw-semibold text-secondary small">Consultation Date & Time <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-clock"></i></span>
                            <input type="text" 
                                   name="consulted_at" 
                                   id="consulted_at" 
                                   class="form-control bg-white" 
                                   required>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="form-label fw-semibold text-secondary small">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select bg-light text-dark" required>
                            <option value="Completed" <?= ($input['status'] ?? 'Completed') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Open" <?= ($input['status'] ?? '') === 'Open' ? 'selected' : '' ?>>Open</option>
                            <option value="Cancelled" <?= ($input['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Save/Cancel Actions -->
            <div class="card card-premium bg-light border-0">
                <div class="card-body p-4">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            <i class="bi bi-save me-2"></i>Save Consultation
                        </button>
                        <a href="<?= url('/patients/' . $patient['id']) ?>" class="btn btn-outline-secondary py-2 fw-semibold">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize flatpickr on consulted_at with date/time support
    flatpickr("#consulted_at", {
        enableTime: true,
        dateFormat: "Y-m-d H:i:S",
        defaultDate: new Date(),
        maxDate: "today",
        allowInput: true
    });
});
</script>
