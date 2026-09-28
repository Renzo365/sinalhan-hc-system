                    <!-- ==============================================================
                       TAB 2: ANNEX A1 INDIVIDUAL HEALTH PROFILE (IHP) FORM
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-ihp" role="tabpanel">
                        <?php 
                        $pmhSaved = $medicalHistory['past_medical_history'] ?? [];
                        $familySaved = $medicalHistory['family_history'] ?? [];
                        $famLineageSaved = $medicalHistory['family_history_lineage'] ?? [];
                        $surgicalSaved = $medicalHistory['surgical_history'] ?? [];
                        $peSaved = $medicalHistory['physical_examination'] ?? [];
                        $immSaved = $medicalHistory['external_immunizations'] ?? [];

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
                                    <i class="bi bi-pencil-square me-1"></i>Edit IHP Record
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
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                7. Pertinent Physical Examination Findings (Annex A1)
                                            </h5>
                                            <?php if ($hasPeFindings): ?>
                                                <div class="row g-2 small pt-1">
                                                    <?php foreach ($peSystems as $sKey => $sLabel): ?>
                                                        <div class="col-12 col-sm-6 col-md-4">
                                                            <div class="p-2 rounded bg-light border h-100">
                                                                <span class="fw-bold text-secondary d-block mb-1" style="font-size: 0.75rem;"><?= $sLabel ?>:</span>
                                                                <?php if (!empty($peSaved[$sKey]) && is_array($peSaved[$sKey])): ?>
                                                                    <div class="d-flex flex-wrap gap-1">
                                                                        <?php foreach ($peSaved[$sKey] as $finding): ?>
                                                                            <span class="badge bg-white text-dark border px-2 py-1"><?= h($finding) ?></span>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="text-muted fst-italic" style="font-size: 0.75rem;">Normal / Unremarkable</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if (!empty($peSaved['remarks'])): ?>
                                                        <div class="col-12 mt-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Physical Exam Remarks / Notes:</span>
                                                            <span class="text-dark fw-medium"><?= h($peSaved['remarks']) ?></span>
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
                        <div id="ihp-edit-mode" class="d-none">
                            <form action="<?= url('/patients/' . $patient['id'] . '/medical-history') ?>" method="POST" id="ihpForm">
                                <?= csrf_field() ?>

                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <div>
                                        <h4 class="h6 mb-0 fw-bold text-dark">PhilHealth Annex A1: Individual Health Profile (IHP)</h4>
                                        <span class="text-muted small">Update past chronic illnesses, surgeries, family heredity, social habits, immunizations, physical exam, and reproductive health.</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="cancelIhpEditMode()">
                                            Cancel
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-xs">
                                            Save IHP Record
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <!-- 1. Past Medical History Checklist (with Proximity Inputs) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h5 class="h6 fw-bold text-primary-dark mb-0">
                                                    1. Past Medical History (Illnesses)
                                                </h5>
                                                <span class="text-muted small">Check condition and provide details where applicable</span>
                                            </div>

                                            <!-- Group 1A: Conditions with Specific Details (2 Columns) -->
                                            <div class="row g-3 small mb-3">
                                                <!-- Allergy -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Allergy" id="pmh_allergy" <?= (isset($pmhSaved['Allergy']) || in_array('Allergy', $pmhSaved) || isset($pmhSaved['Allergies']) || in_array('Allergies', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_allergy">Allergy</label>
                                                        </div>
                                                        <input type="text" name="allergy_specifics" class="form-control form-control-sm bg-white" placeholder="Specify allergens (e.g. Penicillin, Seafood)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Allergy'] ?? $pmhSaved['Allergies'] ?? '') : '') ?>">
                                                    </div>
                                                </div>

                                                <!-- Hypertension -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Hypertension" id="pmh_hypertension" <?= (isset($pmhSaved['Hypertension']) || in_array('Hypertension', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_hypertension">Hypertension</label>
                                                        </div>
                                                        <input type="text" name="hypertension_highest_bp" class="form-control form-control-sm bg-white" placeholder="Highest BP (e.g. 160/100)" value="<?= h(is_array($pmhSaved) ? str_replace('Highest BP: ', '', $pmhSaved['Hypertension'] ?? '') : '') ?>">
                                                    </div>
                                                </div>

                                                <!-- Cancer -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Cancer" id="pmh_cancer" <?= (isset($pmhSaved['Cancer']) || in_array('Cancer', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_cancer">Cancer</label>
                                                        </div>
                                                        <input type="text" name="cancer_organ" class="form-control form-control-sm bg-white" placeholder="Specify organ (e.g. Breast, Colon)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Cancer'] ?? '') : '') ?>">
                                                    </div>
                                                </div>

                                                <!-- Hepatitis -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Hepatitis" id="pmh_hepatitis" <?= (isset($pmhSaved['Hepatitis']) || in_array('Hepatitis', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_hepatitis">Hepatitis</label>
                                                        </div>
                                                        <input type="text" name="hepatitis_type" class="form-control form-control-sm bg-white" placeholder="Specify type (e.g. Hepatitis B)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Hepatitis'] ?? '') : '') ?>">
                                                    </div>
                                                </div>

                                                <!-- Tuberculosis & PTB Category -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Pulmonary Tuberculosis (PTB)" id="pmh_ptb" <?= (isset($pmhSaved['Pulmonary Tuberculosis (PTB)']) || isset($pmhSaved['PTB']) || isset($pmhSaved['Tuberculosis']) || in_array('Pulmonary Tuberculosis (PTB)', $pmhSaved) || in_array('PTB', $pmhSaved) || in_array('Tuberculosis', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_ptb">Tuberculosis / PTB</label>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <input type="text" name="tuberculosis_organ" class="form-control form-control-sm bg-white" placeholder="Organ (e.g. Lungs, Spine)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Tuberculosis'] ?? '') : '') ?>">
                                                            </div>
                                                            <div class="col-6">
                                                                <input type="text" name="ptb_details" class="form-control form-control-sm bg-white" placeholder="PTB Category (e.g. Cat 1)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Pulmonary Tuberculosis (PTB)'] ?? $pmhSaved['PTB'] ?? '') : '') ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Others -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Others" id="pmh_others" <?= (isset($pmhSaved['Others']) || in_array('Others', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_others">Others (Specify)</label>
                                                        </div>
                                                        <input type="text" name="pmh_other_specify" class="form-control form-control-sm bg-white" placeholder="Specify other illnesses..." value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Others'] ?? '') : '') ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Group 1B: Common Illnesses Checklist (4 Columns) -->
                                            <div class="border-top pt-2">
                                                <span class="text-secondary fw-semibold small d-block mb-2">Other Chronic & Systemic Illnesses:</span>
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
                                                        <div class="col-12 col-sm-6 col-md-3">
                                                            <div class="p-2 border rounded bg-light h-100 d-flex align-items-center">
                                                                <div class="form-check mb-0">
                                                                    <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="<?= $gKey ?>" id="pmh_g_<?= md5($gKey) ?>" <?= $gChecked ? 'checked' : '' ?>>
                                                                    <label class="form-check-label fw-semibold text-dark small" for="pmh_g_<?= md5($gKey) ?>"><?= $gLabel ?></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Family History (Hereditary Diseases) Checklist (Matching Section 1 Layout) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h5 class="h6 fw-bold text-primary-dark mb-0">
                                                    2. Family History (Hereditary Diseases)
                                                </h5>
                                                <span class="text-muted small">Check hereditary condition and provide details where applicable</span>
                                            </div>

                                            <!-- Group 2A: Hereditary Conditions with Specific Details (2 Columns) -->
                                             <div class="row g-3 small mb-3">
                                                 <!-- Allergy -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Allergy" id="fam_allergy" <?= (isset($familySaved['Allergy']) || in_array('Allergy', $familySaved) || isset($familySaved['Allergies']) || in_array('Allergies', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_allergy">Allergy</label>
                                                         </div>
                                                         <input type="text" name="fam_allergy_specifics" class="form-control form-control-sm bg-white mb-1" placeholder="Specify allergens (e.g. Asthma, Eczema, Food)" value="<?= h(is_array($familySaved) ? ($familySaved['Allergy'] ?? $familySaved['Allergies'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Allergy]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Allergy'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Allergy'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Allergy'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Allergy'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Hypertension -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Hypertension" id="fam_hypertension" <?= (isset($familySaved['Hypertension']) || in_array('Hypertension', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_hypertension">Hypertension</label>
                                                         </div>
                                                         <input type="text" name="fam_hypertension_highest_bp" class="form-control form-control-sm bg-white mb-1" placeholder="Highest BP / Complication (e.g. 180/100, Stroke)" value="<?= h(is_array($familySaved) ? str_replace('Highest BP: ', '', $familySaved['Hypertension'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Hypertension]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Hypertension'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Hypertension'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Hypertension'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Hypertension'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Cancer -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Cancer" id="fam_cancer" <?= (isset($familySaved['Cancer']) || in_array('Cancer', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_cancer">Cancer</label>
                                                         </div>
                                                         <input type="text" name="fam_cancer_organ" class="form-control form-control-sm bg-white mb-1" placeholder="Specify organ (e.g. Breast, Colon)" value="<?= h(is_array($familySaved) ? ($familySaved['Cancer'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Cancer]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Cancer'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Cancer'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Cancer'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Cancer'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Hepatitis -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Hepatitis" id="fam_hepatitis" <?= (isset($familySaved['Hepatitis']) || in_array('Hepatitis', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_hepatitis">Hepatitis</label>
                                                         </div>
                                                         <input type="text" name="fam_hepatitis_type" class="form-control form-control-sm bg-white mb-1" placeholder="Specify type (e.g. Hepatitis B)" value="<?= h(is_array($familySaved) ? ($familySaved['Hepatitis'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Hepatitis]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Hepatitis'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Hepatitis'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Hepatitis'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Hepatitis'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Tuberculosis & PTB Category -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Tuberculosis" id="fam_ptb" <?= (isset($familySaved['Tuberculosis']) || isset($familySaved['PTB Category']) || in_array('Tuberculosis', $familySaved) || in_array('PTB Category', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_ptb">Tuberculosis / PTB</label>
                                                         </div>
                                                         <div class="row g-2 mb-1">
                                                             <div class="col-6">
                                                                 <input type="text" name="fam_tuberculosis_organ" class="form-control form-control-sm bg-white" placeholder="Organ (e.g. Pulmonary)" value="<?= h(is_array($familySaved) ? ($familySaved['Tuberculosis'] ?? '') : '') ?>">
                                                             </div>
                                                             <div class="col-6">
                                                                 <input type="text" name="fam_ptb_details" class="form-control form-control-sm bg-white" placeholder="PTB Category (e.g. Active)" value="<?= h(is_array($familySaved) ? ($familySaved['PTB Category'] ?? '') : '') ?>">
                                                             </div>
                                                         </div>
                                                         <select name="family_history_lineage[Tuberculosis]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Tuberculosis'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Tuberculosis'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Tuberculosis'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Tuberculosis'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Others -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Others" id="fam_others" <?= (isset($familySaved['Others']) || in_array('Others', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_others">Others (Specify)</label>
                                                         </div>
                                                         <input type="text" name="family_other" class="form-control form-control-sm bg-white mb-1" placeholder="Specify other hereditary illnesses..." value="<?= h(is_array($familySaved) ? ($familySaved['Others'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Others]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Others'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Others'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Others'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Others'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>
                                             </div>

                                            <!-- Group 2B: Hereditary Illnesses Checklist (4 Columns) -->
                                            <div class="border-top pt-2">
                                                <span class="text-secondary fw-semibold small d-block mb-2">Other Hereditary & Familial Conditions:</span>
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
                                                        <div class="col-12 col-sm-6 col-md-3">
                                                            <div class="p-2 border rounded bg-light h-100">
                                                                <div class="form-check mb-1">
                                                                    <input class="form-check-input" type="checkbox" name="family_history[]" value="<?= $fKey ?>" id="fam_g_<?= md5($fKey) ?>" <?= $fChecked ? 'checked' : '' ?>>
                                                                    <label class="form-check-label fw-semibold text-dark small" for="fam_g_<?= md5($fKey) ?>"><?= $fLabel ?></label>
                                                                </div>
                                                                <select name="family_history_lineage[<?= $fKey ?>]" class="form-select form-select-sm bg-white py-0 px-1" style="font-size: 0.72rem;">
                                                                    <option value="Unknown" <?= ($famLineageSaved[$fKey] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage --</option>
                                                                    <option value="Mother" <?= ($famLineageSaved[$fKey] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother</option>
                                                                    <option value="Father" <?= ($famLineageSaved[$fKey] ?? '') === 'Father' ? 'selected' : '' ?>>Father</option>
                                                                    <option value="Both" <?= ($famLineageSaved[$fKey] ?? '') === 'Both' ? 'selected' : '' ?>>Both</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. Past Surgical History & Hospitalization -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                3. Past Surgical History & Hospitalization
                                            </h5>
                                            <div class="row g-2 small">
                                                <div class="col-12 col-sm-5">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 1 Name</label>
                                                    <input type="text" name="operation_1_name" class="form-control form-control-sm" placeholder="e.g. Appendectomy" value="<?= h($surgicalSaved[0]['operation'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-3">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Date</label>
                                                    <input type="text" name="operation_1_date" class="form-control form-control-sm" placeholder="YYYY or YYYY-MM-DD" value="<?= h($surgicalSaved[0]['date'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-4">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Hospital / Clinic</label>
                                                    <input type="text" name="operation_1_hospital" class="form-control form-control-sm" placeholder="e.g. Sta. Rosa Hospital" value="<?= h($surgicalSaved[0]['hospital'] ?? '') ?>">
                                                </div>

                                                <div class="col-12 col-sm-5 mt-2">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 2 Name</label>
                                                    <input type="text" name="operation_2_name" class="form-control form-control-sm" placeholder="e.g. CS Delivery" value="<?= h($surgicalSaved[1]['operation'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-3 mt-2">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Date</label>
                                                    <input type="text" name="operation_2_date" class="form-control form-control-sm" placeholder="YYYY or YYYY-MM-DD" value="<?= h($surgicalSaved[1]['date'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-4 mt-2">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Hospital / Clinic</label>
                                                    <input type="text" name="operation_2_hospital" class="form-control form-control-sm" placeholder="e.g. Health Center" value="<?= h($surgicalSaved[1]['hospital'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4. Personal / Social History -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                4. Personal / Social History
                                            </h5>
                                            <div class="row g-2 small">
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Smoking Status</label>
                                                    <select name="smoking_status" class="form-select form-select-sm">
                                                        <option value="Never" <?= ($medicalHistory['smoking_status'] ?? 'Never') === 'Never' ? 'selected' : '' ?>>Never (No)</option>
                                                        <option value="Yes" <?= ($medicalHistory['smoking_status'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes (Active)</option>
                                                        <option value="Quit" <?= ($medicalHistory['smoking_status'] ?? '') === 'Quit' ? 'selected' : '' ?>>Quit</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">No. of Pack Years</label>
                                                    <input type="number" step="0.1" name="smoking_pack_years" class="form-control form-control-sm" placeholder="e.g. 5.0" value="<?= h($medicalHistory['smoking_pack_years'] ?? '') ?>">
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Alcohol Drinking</label>
                                                    <select name="alcohol_status" class="form-select form-select-sm">
                                                        <option value="Never" <?= ($medicalHistory['alcohol_status'] ?? 'Never') === 'Never' ? 'selected' : '' ?>>Never (No)</option>
                                                        <option value="Yes" <?= ($medicalHistory['alcohol_status'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes (Regular/Occasional)</option>
                                                        <option value="Quit" <?= ($medicalHistory['alcohol_status'] ?? '') === 'Quit' ? 'selected' : '' ?>>Quit</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">No. of Bottles / Day</label>
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

                                    <!-- 5. Lifetime Immunizations (Annex A1) -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
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
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                6. Baseline Vitals & Anthropometrics (Annex A1)
                                            </h5>
                                            <div class="row g-2 small">
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Baseline Blood Pressure</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="baseline_bp_systolic" class="form-control" placeholder="Systolic (e.g. 120)" min="50" max="300" value="<?= h($medicalHistory['baseline_bp_systolic'] ?? '') ?>">
                                                        <span class="input-group-text">/</span>
                                                        <input type="number" name="baseline_bp_diastolic" class="form-control" placeholder="Diastolic (e.g. 80)" min="30" max="200" value="<?= h($medicalHistory['baseline_bp_diastolic'] ?? '') ?>">
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
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Height</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.1" name="baseline_height" class="form-control" placeholder="e.g. 165" min="30" max="250" value="<?= h($medicalHistory['baseline_height'] ?? '') ?>">
                                                        <span class="input-group-text">cm</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Weight</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.1" name="baseline_weight" class="form-control" placeholder="e.g. 60" min="1" max="300" value="<?= h($medicalHistory['baseline_weight'] ?? '') ?>">
                                                        <span class="input-group-text">kg</span>
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
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                7. Pertinent Physical Examination Findings Checklist (Annex A1)
                                            </h5>
                                            <div class="row g-3 small">
                                                <!-- Skin -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-person me-1 text-primary"></i>Skin</span>
                                                        <?php 
                                                        $skinItems = ['Pallor', 'Rashes', 'Jaundice', 'Good skin turgor'];
                                                        $savedSkin = $peSaved['skin'] ?? [];
                                                        foreach ($skinItems as $si): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_skin[]" value="<?= $si ?>" id="pe_skin_<?= md5($si) ?>" <?= in_array($si, $savedSkin, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_skin_<?= md5($si) ?>"><?= $si ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- HEENT -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-eye me-1 text-primary"></i>HEENT</span>
                                                        <?php 
                                                        $heentItems = [
                                                            'Anicteric sclerae', 'Intact tympanic membrane', 'Tonsillopharyngeal congestion',
                                                            'Exudates', 'Pupils briskly reactive to light', 'Alar flaring',
                                                            'Hypertrophic tonsils', 'Aural discharge', 'Nasal discharge', 'Palpable mass'
                                                        ];
                                                        $savedHeent = $peSaved['heent'] ?? [];
                                                        foreach ($heentItems as $hi): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_heent[]" value="<?= $hi ?>" id="pe_heent_<?= md5($hi) ?>" <?= in_array($hi, $savedHeent, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_heent_<?= md5($hi) ?>"><?= $hi ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Chest / Lungs -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-lungs me-1 text-primary"></i>Chest / Lungs</span>
                                                        <?php 
                                                        $chestItems = ['Symmetrical chest expansion', 'Retractions', 'Wheezes', 'Clear breath sounds', 'Crackles / rales'];
                                                        $savedChest = $peSaved['chest_lungs'] ?? [];
                                                        foreach ($chestItems as $ci): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_chest_lungs[]" value="<?= $ci ?>" id="pe_cl_<?= md5($ci) ?>" <?= in_array($ci, $savedChest, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_cl_<?= md5($ci) ?>"><?= $ci ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Heart -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-heart-pulse me-1 text-primary"></i>Heart</span>
                                                        <?php 
                                                        $heartItems = ['Adynamic precordium', 'Normal rate regular rhythm', 'Heaves / thrills', 'Murmurs'];
                                                        $savedHeart = $peSaved['heart'] ?? [];
                                                        foreach ($heartItems as $hti): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_heart[]" value="<?= $hti ?>" id="pe_ht_<?= md5($hti) ?>" <?= in_array($hti, $savedHeart, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_ht_<?= md5($hti) ?>"><?= $hti ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Abdomen -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-shield-shaded me-1 text-primary"></i>Abdomen</span>
                                                        <?php 
                                                        $abdoItems = ['Flat', 'Flabby', 'Tenderness', 'Globular', 'Muscle guarding', 'Palpable mass'];
                                                        $savedAbdo = $peSaved['abdomen'] ?? [];
                                                        foreach ($abdoItems as $ai): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_abdomen[]" value="<?= $ai ?>" id="pe_ab_<?= md5($ai) ?>" <?= in_array($ai, $savedAbdo, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_ab_<?= md5($ai) ?>"><?= $ai ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Extremities -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-activity me-1 text-primary"></i>Extremities</span>
                                                        <?php 
                                                        $extItems = ['Gross deformity', 'Normal gait', 'Full and equal pulses'];
                                                        $savedExt = $peSaved['extremities'] ?? [];
                                                        foreach ($extItems as $ei): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_extremities[]" value="<?= $ei ?>" id="pe_ext_<?= md5($ei) ?>" <?= in_array($ei, $savedExt, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_ext_<?= md5($ei) ?>"><?= $ei ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Remarks -->
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Physical Examination Remarks / Other Findings</label>
                                                    <textarea name="pe_remarks" class="form-control form-control-sm" rows="2" placeholder="Record other physical examination findings or clinical notes..."><?= h($peSaved['remarks'] ?? '') ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 8. Female Menstrual & Reproductive History (if Female) -->
                                    <?php if ($isFemale): ?>
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 h-100 shadow-xs">
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
                                                        <label class="form-label fw-semibold text-secondary small mb-1">LMP Date</label>
                                                        <input type="date" name="lmp" class="form-control form-control-sm " placeholder="YYYY-MM-DD" value="<?= h($medicalHistory['lmp'] ?? '') ?>">
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

                                        <!-- 9. Pregnancy History Card (if Female) -->
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 h-100 shadow-xs">
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
