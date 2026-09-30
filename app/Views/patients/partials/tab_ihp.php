                    <!-- ==============================================================
                       TAB 2: ANNEX A1 INDIVIDUAL HEALTH PROFILE (IHP) FORM
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-ihp" role="tabpanel">
                        <?php 
                        // Detect validation error flashing from PatientMedicalHistoryController
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
                            } elseif (!empty($ihpInput['operation_1_name']) || !empty($ihpInput['operation_2_name'])) {
                                if (!empty($ihpInput['operation_1_name'])) {
                                    $surgicalSaved[] = [
                                        'operation' => trim($ihpInput['operation_1_name']),
                                        'date' => trim($ihpInput['operation_1_date'] ?? ''),
                                        'hospital' => trim($ihpInput['operation_1_hospital'] ?? '')
                                    ];
                                }
                                if (!empty($ihpInput['operation_2_name'])) {
                                    $surgicalSaved[] = [
                                        'operation' => trim($ihpInput['operation_2_name']),
                                        'date' => trim($ihpInput['operation_2_date'] ?? ''),
                                        'hospital' => trim($ihpInput['operation_2_hospital'] ?? '')
                                    ];
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

                        // Build display list for Past Medical History (Deduplicated)
                        $pmhDisplay = [];
                        $seenConditions = [];
                        if (!empty($pmhSaved) && is_array($pmhSaved)) {
                            foreach ($pmhSaved as $k => $v) {
                                $cond = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
                                $det = (is_string($k) && !is_numeric($k) && is_string($v)) ? trim($v) : '';

                                if (in_array($cond, ['PTB', 'Tuberculosis', 'Pulmonary Tuberculosis'], true)) {
                                    $cond = 'Pulmonary Tuberculosis (PTB)';
                                } elseif ($cond === 'Allergies') {
                                    $cond = 'Allergy';
                                }

                                if ($cond === '' || $cond === '[]' || $cond === '{}') {
                                    continue;
                                }

                                if (isset($seenConditions[$cond])) {
                                    if (!empty($det)) {
                                        foreach ($pmhDisplay as &$item) {
                                            if ($item['condition'] === $cond && empty($item['detail'])) {
                                                $item['detail'] = $det;
                                            }
                                        }
                                        unset($item);
                                    }
                                    continue;
                                }

                                $seenConditions[$cond] = true;
                                $pmhDisplay[] = ['condition' => $cond, 'detail' => $det];
                            }
                        }

                        // Build display list for Family History (Deduplicated)
                        $familyDisplay = [];
                        $seenFamily = [];
                        if (!empty($familySaved) && is_array($familySaved)) {
                            foreach ($familySaved as $k => $v) {
                                $cond = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
                                $det = (is_string($k) && !is_numeric($k) && is_string($v) && $v !== 'Yes' && $v !== $k) ? trim($v) : '';

                                if (in_array($cond, ['PTB', 'Tuberculosis', 'Pulmonary Tuberculosis'], true)) {
                                    $cond = 'Pulmonary Tuberculosis (PTB)';
                                } elseif ($cond === 'Allergies') {
                                    $cond = 'Allergy';
                                }

                                if ($cond === '' || $cond === '[]' || $cond === '{}' || $cond === 'Yes') {
                                    continue;
                                }

                                if (isset($seenFamily[$cond])) {
                                    if (!empty($det)) {
                                        foreach ($familyDisplay as &$item) {
                                            if ($item['condition'] === $cond && empty($item['detail'])) {
                                                $item['detail'] = $det;
                                            }
                                        }
                                        unset($item);
                                    }
                                    continue;
                                }

                                $seenFamily[$cond] = true;
                                $familyDisplay[] = ['condition' => $cond, 'detail' => $det];
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

                        // Check physical examination findings
                        $peSystems = [
                            'skin' => 'Skin',
                            'heent' => 'HEENT',
                            'chest_lungs' => 'Chest / Lungs',
                            'heart' => 'Heart',
                            'abdomen' => 'Abdomen',
                            'extremities' => 'Extremities'
                        ];
                        $hasPeFindings = false;
                        foreach ($peSystems as $sKey => $sLabel) {
                            if (!empty($peSaved[$sKey]) && is_array($peSaved[$sKey])) {
                                $hasPeFindings = true;
                                break;
                            }
                        }
                        if (!empty($peSaved['remarks'])) {
                            $hasPeFindings = true;
                        }

                        // Check lifetime immunizations
                        $hasImmData = !empty($immSaved['children']) || !empty($immSaved['young_women']) || !empty($immSaved['pregnant']) || !empty($immSaved['elderly']) || !empty($immSaved['others']) || !empty($patientImmunizations);

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
                        $hasObstetricData = $isFemale && !empty($medicalHistory) && (
                            (!empty($medicalHistory['gravida']) && (int)$medicalHistory['gravida'] > 0) ||
                            (!empty($medicalHistory['para']) && (int)$medicalHistory['para'] > 0) ||
                            !empty($medicalHistory['delivery_type']) ||
                            !empty($medicalHistory['pre_eclampsia'])
                        );
                        $hasBaselineVitals = !empty($medicalHistory) && (
                            !empty($medicalHistory['baseline_bp_systolic']) ||
                            !empty($medicalHistory['baseline_heart_rate']) ||
                            !empty($medicalHistory['baseline_height']) ||
                            !empty($medicalHistory['baseline_weight']) ||
                            !empty($medicalHistory['baseline_respiratory_rate']) ||
                            !empty($medicalHistory['baseline_waist_circumference'])
                        );

                        // Baseline Vitals BMI computation
                        $baselineHeight = !empty($medicalHistory['baseline_height']) ? (float)$medicalHistory['baseline_height'] : null;
                        $baselineWeight = !empty($medicalHistory['baseline_weight']) ? (float)$medicalHistory['baseline_weight'] : null;
                        $baselineBmi = null;
                        $baselineBmiClass = '';
                        $baselineBmiLabel = '';

                        if ($baselineHeight && $baselineWeight && $baselineHeight > 0 && $baselineWeight > 0) {
                            $heightInM = $baselineHeight / 100;
                            $baselineBmi = round($baselineWeight / ($heightInM * $heightInM), 1);
                            $bmiClassified = classify_bmi($baselineBmi);
                            $baselineBmiLabel = $bmiClassified['label'];
                            $baselineBmiClass = $bmiClassified['badge'];
                        }

                        $hasAnyIhpRecord = !empty($medicalHistory) && (
                            $hasPmhData ||
                            $hasSurgicalData ||
                            $hasFamilyData ||
                            $hasLifestyleData ||
                            $hasReproductiveData ||
                            $hasObstetricData ||
                            $hasPeFindings ||
                            $hasImmData ||
                            $hasBaselineVitals ||
                            !empty($medicalHistory['updated_at'])
                        );
                        ?>

                        <!-- -----------------------------------------------------------
                           MODE A: READ-ONLY VIEW (DEFAULT)
                           ----------------------------------------------------------- -->
                        <div id="ihp-view-mode" class="<?= $hasIhpFormErrors ? 'd-none' : '' ?>">
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
                                <?php if ($hasAnyIhpRecord): ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-medium" onclick="enterIhpEditMode()">
                                        <i class="bi bi-pencil-square me-1"></i>Edit IHP Record
                                    </button>
                                <?php endif; ?>
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
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-check-circle text-success me-1"></i>No chronic illnesses or allergies recorded
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 2. Family History (Hereditary Diseases) Card (Full Width with Color-Coded Badges) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                2. Family History (Hereditary Diseases)
                                            </h5>
                                            <?php if ($hasFamilyData): ?>
                                                <div class="d-flex flex-wrap gap-2 pt-1">
                                                    <?php foreach ($familyDisplay as $item): ?>
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
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-check-circle text-success me-1"></i>No hereditary family diseases declared
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 3. Past Surgical History & Hospitalization Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                3. Past Surgical History & Hospitalization
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
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-check-circle text-success me-1"></i>No prior surgeries or hospitalizations declared
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 4. Personal & Social History Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                4. Personal & Social History
                                            </h5>
                                            <div class="row g-2 small pt-1">
                                                <div class="col-12 col-sm-6">
                                                    <div class="p-2 rounded bg-light border h-100">
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
                                                    <div class="p-2 rounded bg-light border h-100">
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

                                    <!-- 5. Lifetime Immunization Record (Annex A1) Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                5. Lifetime Immunization Record (Annex A1)
                                            </h5>
                                            <?php if ($hasImmData): ?>
                                                <div class="small pt-1">
                                                    <?php if (!empty($immSaved['children'])): ?>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Children Vaccines:</span>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <?php foreach ($immSaved['children'] as $v): ?>
                                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><?= h($v) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($immSaved['young_women'])): ?>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Young Women Vaccines:</span>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <?php foreach ($immSaved['young_women'] as $v): ?>
                                                                    <span class="badge bg-pink-subtle text-pink border border-pink-subtle px-2 py-1"><?= h($v) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($immSaved['pregnant'])): ?>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Pregnant Vaccines:</span>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <?php foreach ($immSaved['pregnant'] as $v): ?>
                                                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><?= h($v) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($immSaved['elderly'])): ?>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Elderly / Immunocompromised:</span>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <?php foreach ($immSaved['elderly'] as $v): ?>
                                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1"><?= h($v) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($immSaved['others'])): ?>
                                                        <div>
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Others:</span>
                                                            <span class="fw-medium text-dark"><?= h($immSaved['others']) ?></span>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($patientImmunizations)): ?>
                                                        <div class="mt-2 pt-2 border-top">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="text-muted d-block fw-semibold" style="font-size: 0.75rem;">Health Center Administered Doses:</span>
                                                                <a href="#tab-immunizations" class="small text-decoration-none" style="font-size: 0.7rem;" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('button[data-bs-target=\'#tab-immunizations\']')).show();">View Ledger &rarr;</a>
                                                            </div>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                <?php foreach ($patientImmunizations as $admImm): ?>
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="Given: <?= h($admImm['date_administered']) ?><?= !empty($admImm['administered_by_name']) ? ' by ' . h($admImm['administered_by_name']) : '' ?>">
                                                                        <i class="bi bi-shield-check me-1"></i><?= h($admImm['vaccine_name']) ?> (Dose <?= h($admImm['dose_number']) ?>)
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-shield-slash text-muted me-1"></i>No lifetime immunization history recorded
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 6. Baseline Vitals & Anthropometrics (Annex A1) Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                6. Baseline Vitals & Anthropometrics (Annex A1)
                                            </h5>
                                            <?php if ($hasBaselineVitals): ?>
                                                <div class="row g-2 small pt-1">
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Blood Pressure:</span>
                                                            <?php if (!empty($medicalHistory['baseline_bp_systolic']) || !empty($medicalHistory['baseline_bp_diastolic'])): ?>
                                                                <span class="fw-bold text-dark fs-7">
                                                                    <?= h($medicalHistory['baseline_bp_systolic'] ?? '—') ?>/<?= h($medicalHistory['baseline_bp_diastolic'] ?? '—') ?>
                                                                    <span class="text-muted fw-normal fs-8">mmHg</span>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Heart Rate:</span>
                                                            <?php if (!empty($medicalHistory['baseline_heart_rate'])): ?>
                                                                <span class="fw-bold text-dark fs-7">
                                                                    <?= h($medicalHistory['baseline_heart_rate']) ?>
                                                                    <span class="text-muted fw-normal fs-8">bpm</span>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Respiratory Rate:</span>
                                                            <?php if (!empty($medicalHistory['baseline_respiratory_rate'])): ?>
                                                                <span class="fw-bold text-dark fs-7">
                                                                    <?= h($medicalHistory['baseline_respiratory_rate']) ?>
                                                                    <span class="text-muted fw-normal fs-8">cpm</span>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Waist Circumference:</span>
                                                            <?php if (!empty($medicalHistory['baseline_waist_circumference'])): ?>
                                                                <span class="fw-bold text-dark fs-7">
                                                                    <?= h($medicalHistory['baseline_waist_circumference']) ?>
                                                                    <span class="text-muted fw-normal fs-8">cm</span>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Height & Weight:</span>
                                                            <div class="fw-semibold text-dark">
                                                                <span><?= !empty($medicalHistory['baseline_height']) ? h($medicalHistory['baseline_height']) . ' cm' : '—' ?></span>
                                                                <span class="text-muted mx-1">&bull;</span>
                                                                <span><?= !empty($medicalHistory['baseline_weight']) ? h($medicalHistory['baseline_weight']) . ' kg' : '—' ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Body Mass Index (BMI):</span>
                                                            <?php if ($baselineBmi !== null): ?>
                                                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                                    <span class="fw-bold text-dark fs-7"><?= number_format($baselineBmi, 1) ?></span>
                                                                    <span class="<?= $baselineBmiClass ?>"><?= $baselineBmiLabel ?></span>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted">None recorded</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-activity text-muted me-1"></i>No baseline vitals or anthropometrics recorded
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 7. Pertinent Physical Examination Findings (Annex A1) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mb-3 pb-2 border-bottom">
                                                <div>
                                                    <h5 class="h6 fw-bold text-primary-dark mb-0">
                                                        <i class="bi bi-clipboard2-pulse me-1.5 text-primary"></i>7. Pertinent Physical Examination Findings
                                                    </h5>
                                                    <span class="text-muted small">PhilHealth Annex A1: Systematic organ-systems physical examination</span>
                                                </div>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                                                    Annex A1 Section 10
                                                </span>
                                            </div>
                                            <?php if ($hasPeFindings): ?>
                                                <?php
                                                $acuteFindingsMap = [
                                                    'Pallor', 'Rashes', 'Jaundice',
                                                    'Tonsillopharyngeal congestion', 'Exudates', 'Hypertrophic tonsils', 'Alar flaring', 'Nasal discharge', 'Aural discharge', 'Palpable mass',
                                                    'Retractions', 'Wheezes', 'Crackles / rales',
                                                    'Heaves / thrills', 'Murmurs',
                                                    'Tenderness', 'Muscle guarding',
                                                    'Gross deformity', 'Cyanosis'
                                                ];
                                                $normalFindingsMap = [
                                                    'Good skin turgor',
                                                    'Anicteric sclerae', 'Pupils briskly reactive to light', 'Intact tympanic membrane',
                                                    'Symmetrical chest expansion', 'Clear breath sounds',
                                                    'Adynamic precordium', 'Normal rate regular rhythm',
                                                    'Flat',
                                                    'Full and equal pulses', 'Normal gait'
                                                ];
                                                $peSystemIcons = [
                                                    'skin' => 'bi-person',
                                                    'heent' => 'bi-eye',
                                                    'chest_lungs' => 'bi-lungs',
                                                    'heart' => 'bi-heart-pulse',
                                                    'abdomen' => 'bi-shield-shaded',
                                                    'extremities' => 'bi-person-walking'
                                                ];
                                                ?>
                                                <div class="row g-2.5 small pt-1">
                                                    <?php foreach ($peSystems as $sKey => $sLabel): 
                                                        $findings = !empty($peSaved[$sKey]) && is_array($peSaved[$sKey]) ? $peSaved[$sKey] : [];
                                                        $sysIcon = $peSystemIcons[$sKey] ?? 'bi-clipboard-check';
                                                    ?>
                                                        <div class="col-12 col-sm-6 col-md-4">
                                                            <div class="p-2.5 rounded-2 bg-light border h-100 d-flex flex-column">
                                                                <div class="d-flex align-items-center justify-content-between mb-1.5 pb-1 border-bottom">
                                                                    <span class="fw-bold text-dark d-flex align-items-center" style="font-size: 0.78rem;">
                                                                        <i class="bi <?= $sysIcon ?> me-1.5 text-primary"></i><?= $sLabel ?>
                                                                    </span>
                                                                    <?php if (!empty($findings)): ?>
                                                                        <?php 
                                                                        $hasAcuteItem = false;
                                                                        foreach ($findings as $f) {
                                                                            if (in_array($f, $acuteFindingsMap, true)) { $hasAcuteItem = true; break; }
                                                                        }
                                                                        ?>
                                                                        <?php if ($hasAcuteItem): ?>
                                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">
                                                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Abnormal
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">
                                                                                <i class="bi bi-check-circle-fill me-1"></i>Normal
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <?php if (!empty($findings)): ?>
                                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                                        <?php foreach ($findings as $finding): 
                                                                            $isAcute = in_array($finding, $acuteFindingsMap, true);
                                                                            $isNormal = in_array($finding, $normalFindingsMap, true);
                                                                        ?>
                                                                            <?php if ($isAcute): ?>
                                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                                                    <i class="bi bi-exclamation-circle me-1"></i><?= h($finding) ?>
                                                                                </span>
                                                                            <?php elseif ($isNormal): ?>
                                                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                                                    <i class="bi bi-check2 me-1"></i><?= h($finding) ?>
                                                                                </span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-white text-dark border px-2 py-1">
                                                                                    <?= h($finding) ?>
                                                                                </span>
                                                                            <?php endif; ?>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="mt-1">
                                                                        <span class="text-muted fst-italic" style="font-size: 0.75rem;">
                                                                            <i class="bi bi-check-circle text-success me-1"></i>Normal / Unremarkable
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>

                                                    <?php if (!empty($peSaved['remarks'])): ?>
                                                        <div class="col-12 mt-2">
                                                            <div class="p-2.5 rounded-2 bg-light border">
                                                                <span class="text-secondary fw-bold d-block small mb-1">
                                                                    <i class="bi bi-chat-square-quote me-1 text-primary"></i>Doctor's Clinical Notes / Detailed Findings:
                                                                </span>
                                                                <p class="text-dark small mb-0 fst-italic">"<?= nl2br(h($peSaved['remarks'])) ?>"</p>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-clipboard2-check text-muted me-1"></i>No physical examination findings on record
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 8. Female Menstrual & Reproductive History (if Female) -->
                                    <?php if ($isFemale): ?>
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 shadow-xs h-100">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    8. Female Menstrual & Reproductive History
                                                </h5>
                                                <?php if ($hasReproductiveData): ?>
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
                                                <?php else: ?>
                                                    <div class="pt-1">
                                                        <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                            <i class="bi bi-person text-pink me-1"></i>No menstrual or reproductive history recorded
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- 9. Pregnancy & Obstetric History (if Female) -->
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 shadow-xs h-100">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    9. Pregnancy & Obstetric History
                                                </h5>
                                                <?php if ($hasObstetricData): ?>
                                                    <div class="row g-2 small pt-1">
                                                        <div class="col-6">
                                                            <span class="text-muted d-block">Gravida / Para (G/P):</span>
                                                            <span class="fw-bold text-dark">G<?= h($medicalHistory['gravida'] ?? '0') ?> P<?= h($medicalHistory['para'] ?? '0') ?> (T:<?= h($medicalHistory['term_births'] ?? '0') ?> P:<?= h($medicalHistory['preterm_births'] ?? '0') ?> A:<?= h($medicalHistory['abortions'] ?? '0') ?> L:<?= h($medicalHistory['living_children'] ?? '0') ?>)</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <span class="text-muted d-block">Type of Delivery:</span>
                                                            <span class="fw-semibold text-dark"><?= !empty($medicalHistory['delivery_type']) ? h($medicalHistory['delivery_type']) : 'Unspecified' ?></span>
                                                        </div>
                                                        <div class="col-6 border-top pt-2 mt-1">
                                                            <span class="text-muted d-block">Pre-eclampsia / PIH History:</span>
                                                            <?php if (!empty($medicalHistory['pre_eclampsia'])): ?>
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Yes (Reported)</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light text-muted border">No</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-6 border-top pt-2 mt-1">
                                                            <span class="text-muted d-block">Access to FP Counselling:</span>
                                                            <span class="badge bg-light text-dark border"><?= (!empty($medicalHistory['fp_counselling']) && (int)$medicalHistory['fp_counselling'] === 1) ? 'Yes' : 'No' ?></span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="pt-1">
                                                        <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                            <i class="bi bi-person text-pink me-1"></i>Nulliparous / No obstetric history recorded
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Clean Empty State Card -->
                                <div class="card border border-dashed rounded-3 p-4 text-center bg-light shadow-xs my-2">
                                    <h5 class="h6 fw-bold text-dark mb-1">No Individual Health Profile (IHP) On File</h5>
                                    <p class="text-muted small mb-3 mx-auto" style="max-width: 520px;">
                                        PhilHealth Annex A1 baseline medical history has not yet been recorded for this patient. Click below to record past chronic illnesses, surgical operations, family heredity, social history, immunizations, physical exam, and reproductive health.
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
                        <div id="ihp-edit-mode" class="<?= $hasIhpFormErrors ? '' : 'd-none' ?>">
                            <form action="<?= url('/patients/' . $patient['id'] . '/medical-history') ?>" method="POST" id="ihpForm">
                                <?= csrf_field() ?>

                                <?php if ($hasIhpFormErrors): ?>
                                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3 shadow-xs" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                                        <div class="flex-grow-1">
                                            <strong>Validation Error:</strong> <?= h($_SESSION['error_message'] ?? 'Please correct the highlighted inputs and resubmit.') ?>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3 pb-2 border-bottom">
                                    <h4 class="h6 mb-0 fw-bold text-dark">PhilHealth Annex A1: Individual Health Profile (IHP)</h4>
                                    <span class="text-muted small">Update past chronic illnesses, surgeries, family heredity, social habits, immunizations, physical exam, and reproductive health.</span>
                                </div>

                                <!-- Quick Navigation Anchors -->
                                <div class="d-flex flex-wrap align-items-center gap-1 p-2 bg-light rounded border mb-3 small" id="ihpSectionNav">
                                    <span class="text-muted fw-semibold me-1 d-flex align-items-center"><i class="bi bi-compass me-1"></i>Jump to:</span>
                                    <a href="#ihp-sec-pmh" class="badge bg-white text-primary border text-decoration-none py-1.5 px-2">1. Illnesses</a>
                                    <a href="#ihp-sec-family" class="badge bg-white text-primary border text-decoration-none py-1.5 px-2">2. Family</a>
                                    <a href="#ihp-sec-surgical" class="badge bg-white text-primary border text-decoration-none py-1.5 px-2">3. Surgeries</a>
                                    <a href="#ihp-sec-lifestyle" class="badge bg-white text-primary border text-decoration-none py-1.5 px-2">4. Habits</a>
                                    <a href="#ihp-sec-imm" class="badge bg-white text-primary border text-decoration-none py-1.5 px-2">5. Vaccines</a>
                                    <a href="#ihp-sec-vitals" class="badge bg-white text-primary border text-decoration-none py-1.5 px-2">6. Vitals</a>
                                    <a href="#ihp-sec-pe" class="badge bg-white text-primary border text-decoration-none py-1.5 px-2">7. Physical Exam</a>
                                    <?php if ($isFemale): ?>
                                        <a href="#ihp-sec-reproductive" class="badge bg-white text-primary border text-decoration-none py-1.5 px-2">8. Menstrual/FP</a>
                                        <a href="#ihp-sec-obstetric" class="badge bg-white text-primary border text-decoration-none py-1.5 px-2">9. Obstetric</a>
                                    <?php endif; ?>
                                </div>

                                <div class="row g-3">
                                    <!-- 1. Past Medical History (Annex A1 Section 4) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs" id="ihp-sec-pmh">
                                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mb-3 pb-2 border-bottom">
                                                <div>
                                                    <h5 class="h6 fw-bold text-primary-dark mb-0">
                                                        <i class="bi bi-file-earmark-medical me-1.5 text-primary"></i>1. Past Medical History
                                                    </h5>
                                                    <span class="text-muted small">PhilHealth Annex A1: Individual Health Profile illness checklist</span>
                                                </div>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                                                    Annex A1 Section 4
                                                </span>
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
                                                        <i class="bi bi-info-circle me-1"></i>Check condition to unlock detail input
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

                                    <!-- 2. Family History (Hereditary Diseases) (Annex A1 Section 6) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs" id="ihp-sec-family">
                                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mb-3 pb-2 border-bottom">
                                                <div>
                                                    <h5 class="h6 fw-bold text-primary-dark mb-0">
                                                        <i class="bi bi-diagram-3 me-1.5 text-primary"></i>2. Family History (Hereditary Diseases)
                                                    </h5>
                                                    <span class="text-muted small">PhilHealth Annex A1: Hereditary conditions in patient's family</span>
                                                </div>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                                                    Annex A1 Section 6
                                                </span>
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
                                                        <i class="bi bi-info-circle me-1"></i>Check condition to unlock detail input
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
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs" id="ihp-sec-surgical">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h5 class="h6 fw-bold text-primary-dark mb-0">
                                                    3. Past Surgical History & Hospitalization
                                                </h5>
                                                <button type="button" class="btn btn-outline-primary btn-sm fw-medium shadow-xs" id="btnAddSurgeryRow" onclick="addIhpSurgeryRow()">
                                                    <i class="bi bi-plus-circle me-1"></i>Add Surgery
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
                                                        <div class="ihp-surgery-row p-2 border rounded-2 bg-light-subtle position-relative">
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
                                                <i class="bi bi-info-circle me-1"></i>No surgical procedures recorded. Click <strong>+ Add Surgery</strong> if the patient has past operations.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4. Personal / Social History -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs" id="ihp-sec-lifestyle">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                4. Personal / Social History
                                            </h5>
                                            <div class="row g-2 small">
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
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="illicit_drugs" value="1" id="illicit_drugs" <?= !empty($medicalHistory['illicit_drugs']) ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-secondary fw-semibold small" for="illicit_drugs">History of Illicit Drug Use</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 5. Lifetime Immunizations (Annex A1) -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs" id="ihp-sec-imm">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                5. Lifetime Immunizations (Annex A1)
                                            </h5>
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
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs" id="ihp-sec-vitals">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                6. Baseline Vitals & Anthropometrics (Annex A1)
                                            </h5>
                                            <div class="row g-2 small">
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Baseline Blood Pressure</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="baseline_bp_systolic" id="ihp_baseline_bp_systolic" class="form-control" placeholder="Systolic (e.g. 120)" min="50" max="300" value="<?= h($medicalHistory['baseline_bp_systolic'] ?? '') ?>">
                                                        <span class="input-group-text">/</span>
                                                        <input type="number" name="baseline_bp_diastolic" id="ihp_baseline_bp_diastolic" class="form-control" placeholder="Diastolic (e.g. 80)" min="30" max="200" value="<?= h($medicalHistory['baseline_bp_diastolic'] ?? '') ?>">
                                                        <span class="input-group-text">mmHg</span>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Heart Rate</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="baseline_heart_rate" class="form-control" placeholder="e.g. 72" min="30" max="250" value="<?= h($medicalHistory['baseline_heart_rate'] ?? '') ?>">
                                                        <span class="input-group-text">bpm</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Respiratory Rate</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="baseline_respiratory_rate" class="form-control" placeholder="e.g. 18" min="8" max="60" value="<?= h($medicalHistory['baseline_respiratory_rate'] ?? '') ?>">
                                                        <span class="input-group-text">cpm</span>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1" for="ihp_baseline_height">Height</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.1" name="baseline_height" id="ihp_baseline_height" class="form-control" placeholder="e.g. 165" min="30" max="250" value="<?= h($medicalHistory['baseline_height'] ?? '') ?>">
                                                        <span class="input-group-text">cm</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1" for="ihp_baseline_weight">Weight</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.1" name="baseline_weight" id="ihp_baseline_weight" class="form-control" placeholder="e.g. 60" min="1" max="300" value="<?= h($medicalHistory['baseline_weight'] ?? '') ?>">
                                                        <span class="input-group-text">kg</span>
                                                    </div>
                                                </div>

                                                <!-- Real-Time Baseline BMI Calculator & WHO Asian Classification -->
                                                <div class="col-12">
                                                    <div class="p-2.5 border rounded-2 bg-light-subtle d-flex align-items-center justify-content-between flex-wrap gap-2" id="ihpBmiWrapper">
                                                        <div>
                                                            <span class="text-secondary small fw-semibold d-block">
                                                                <i class="bi bi-calculator me-1 text-primary"></i>Calculated Baseline BMI:
                                                            </span>
                                                            <span class="fw-bold text-dark fs-6" id="ihpBmiValue">
                                                                <?php
                                                                $h = !empty($medicalHistory['baseline_height']) ? (float)$medicalHistory['baseline_height'] : 0;
                                                                $w = !empty($medicalHistory['baseline_weight']) ? (float)$medicalHistory['baseline_weight'] : 0;
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
                                                        <input type="number" step="0.1" name="baseline_waist_circumference" class="form-control" placeholder="e.g. 78" min="20" max="200" value="<?= h($medicalHistory['baseline_waist_circumference'] ?? '') ?>">
                                                        <span class="input-group-text">cm</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 7. Pertinent Physical Examination Findings Checklist (Annex A1) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs" id="ihp-sec-pe">
                                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-primary text-white rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem;">7</span>
                                                        <h5 class="h6 fw-bold text-primary-dark mb-0">
                                                            Pertinent Physical Examination Checklist (PhilHealth Annex A1)
                                                        </h5>
                                                    </div>
                                                    <span class="text-muted small">Rapid body-systems organ checklist. Mark abnormalities systematically.</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button type="button" class="btn btn-success btn-sm px-3 fw-semibold shadow-xs d-inline-flex align-items-center" id="btnMarkAllNormal">
                                                        <i class="bi bi-check2-circle me-1.5 fs-6"></i>Mark All Unremarkable / Normal
                                                    </button>
                                                </div>
                                            </div>

                                            <?php
                                            $peConfig = [
                                                'skin' => [
                                                    'title' => 'Skin / Integument',
                                                    'icon' => 'bi-person',
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
                                                    'icon' => 'bi-eye',
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
                                                    'icon' => 'bi-lungs',
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
                                                    'icon' => 'bi-heart-pulse',
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
                                                    'icon' => 'bi-shield-shaded',
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
                                                    'icon' => 'bi-person-walking',
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
                                                        $badgeIcon = 'bi-exclamation-triangle-fill';
                                                        $badgeText = 'Abnormal Findings';
                                                    } elseif ($hasNormal) {
                                                        $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                                        $badgeIcon = 'bi-check-circle-fill';
                                                        $badgeText = $conf['normal_text'];
                                                    } elseif (!empty($savedSys)) {
                                                        $badgeClass = 'bg-info-subtle text-primary border border-info-subtle';
                                                        $badgeIcon = 'bi-info-circle';
                                                        $badgeText = 'Recorded';
                                                    } else {
                                                        $badgeClass = 'bg-light text-muted border';
                                                        $badgeIcon = 'bi-dash-circle';
                                                        $badgeText = 'Unspecified';
                                                    }
                                                ?>
                                                <div class="col-12 col-md-4">
                                                    <div class="p-3 pe-system-card h-100 d-flex flex-column" data-system-card="<?= $sysKey ?>">
                                                        <div class="d-flex align-items-center justify-content-between mb-2.5 pb-2 border-bottom">
                                                            <span class="fw-bold text-dark d-flex align-items-center" style="font-size: 0.82rem;">
                                                                <i class="bi <?= $conf['icon'] ?> me-1.5 text-primary"></i><?= $conf['title'] ?>
                                                            </span>
                                                            <span id="<?= $conf['badge_id'] ?>" class="badge <?= $badgeClass ?> px-2 py-1 pe-system-badge" style="font-size: 0.7rem;">
                                                                <i class="bi <?= $badgeIcon ?> me-1"></i><?= $badgeText ?>
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
                                                                        <label class="form-check-label text-dark flex-grow-1" for="<?= $chkId ?>" style="font-size: 0.78rem;">
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
                                                            <i class="bi bi-chat-square-text me-1 text-primary"></i>Doctor's Clinical Notes / Detailed Findings
                                                        </label>
                                                        <textarea name="pe_remarks" id="pe_remarks" class="form-control form-control-sm rounded-2" rows="3" placeholder="Patient is well-nourished, alert and ambulatory. Record other physical examination findings or clinical notes..."><?= h($peSaved['remarks'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 8. Female Menstrual & Reproductive History (if Female) -->
                                    <?php if ($isFemale): ?>
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 h-100 shadow-xs" id="ihp-sec-reproductive">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    8. Female Menstrual & Reproductive History
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
                                                        <div class="form-check mt-3">
                                                            <input class="form-check-input" type="checkbox" name="is_menopausal" value="1" id="is_menopausal" <?= !empty($medicalHistory['is_menopausal']) ? 'checked' : '' ?>>
                                                            <label class="form-check-label text-secondary fw-semibold small cursor-pointer" for="is_menopausal">Menopausal</label>
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
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 h-100 shadow-xs" id="ihp-sec-obstetric">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    9. Pregnancy & Obstetric History (Annex A1)
                                                </h5>
                                                <div class="row g-2 small">
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
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Full Term</label>
                                                        <input type="number" name="term_births" class="form-control form-control-sm" min="0" max="25" placeholder="F" value="<?= h($medicalHistory['term_births'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Premature</label>
                                                        <input type="number" name="preterm_births" class="form-control form-control-sm" min="0" max="25" placeholder="P" value="<?= h($medicalHistory['preterm_births'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Abortion</label>
                                                        <input type="number" name="abortions" class="form-control form-control-sm" min="0" max="25" placeholder="A" value="<?= h($medicalHistory['abortions'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Living Children</label>
                                                        <input type="number" name="living_children" class="form-control form-control-sm" min="0" max="25" placeholder="L" value="<?= h($medicalHistory['living_children'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-12 col-sm-6 border-top pt-2 mt-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="pre_eclampsia" value="1" id="pre_eclampsia" <?= !empty($medicalHistory['pre_eclampsia']) ? 'checked' : '' ?>>
                                                            <label class="form-check-label text-secondary fw-semibold small" for="pre_eclampsia">Pregnancy-Induced Hypertension (Pre-eclampsia)</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-sm-6 border-top pt-2 mt-2">
                                                        <label class="form-label fw-semibold text-secondary small mb-1 d-block">Access to Family Planning Counselling</label>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="fp_counselling" id="fp_counselling_yes" value="1" <?= (!isset($medicalHistory['fp_counselling']) || (int)$medicalHistory['fp_counselling'] === 1) ? 'checked' : '' ?>>
                                                            <label class="form-check-label small" for="fp_counselling_yes">Yes</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="fp_counselling" id="fp_counselling_no" value="0" <?= (isset($medicalHistory['fp_counselling']) && (int)$medicalHistory['fp_counselling'] === 0) ? 'checked' : '' ?>>
                                                            <label class="form-check-label small" for="fp_counselling_no">No</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end sticky-bottom bg-white py-2 px-3 shadow-sm rounded-bottom" style="z-index: 10;">
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
