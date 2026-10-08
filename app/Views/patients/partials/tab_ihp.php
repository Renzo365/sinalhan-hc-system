                    <!-- ==============================================================
                       TAB 2: ANNEX A1 INDIVIDUAL HEALTH PROFILE (IHP) FORM
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-ihp" role="tabpanel">
                        <?php 
                        $pmhSaved = $medicalHistory['past_medical_history'] ?? [];
                        $familySaved = $medicalHistory['family_history'] ?? [];
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
                           READ-ONLY IHP SUMMARY VIEW
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
                                <?php if ($hasAnyIhpRecord): ?>
                                    <a href="<?= url('/patients/' . $patient['id'] . '/ihp/edit') ?>" class="btn btn-outline-primary btn-sm px-3 fw-medium">
                                        <i class="bi bi-pencil-square me-1"></i> Edit IHP Profile
                                    </a>
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
                                                        No chronic illnesses or allergies recorded
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
                                                        No hereditary family diseases declared
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
                                                        No prior surgeries or hospitalizations declared
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
                                                                    <?php
                                                                        $immDate = !empty($admImm['administered_date']) ? date('M d, Y', strtotime($admImm['administered_date'])) : 'Recorded';
                                                                        $immVaccinator = !empty($admImm['vaccinator_name']) ? (' by ' . h($admImm['vaccinator_name'])) : '';
                                                                    ?>
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="Given: <?= $immDate ?><?= $immVaccinator ?>">
                                                                        <?= h($admImm['vaccine_name'] ?? 'Vaccine') ?> (Dose <?= h($admImm['dose_number'] ?? '1') ?>)
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        No lifetime immunization history recorded
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
                                                        No baseline vitals or anthropometrics recorded
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
                                                        7. Pertinent Physical Examination Findings
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
                                                ?>
                                                <div class="row g-2.5 small pt-1">
                                                    <?php foreach ($peSystems as $sKey => $sLabel): 
                                                        $findings = !empty($peSaved[$sKey]) && is_array($peSaved[$sKey]) ? $peSaved[$sKey] : [];
                                                    ?>
                                                        <div class="col-12 col-sm-6 col-md-4">
                                                            <div class="p-2.5 rounded-2 bg-light border h-100 d-flex flex-column">
                                                                <div class="d-flex align-items-center justify-content-between mb-1.5 pb-1 border-bottom">
                                                                    <span class="fw-bold text-dark d-flex align-items-center" style="font-size: 0.78rem;">
                                                                        <?= $sLabel ?>
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
                                                                                Normal
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
                                                                                    <?= h($finding) ?>
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
                                                                            Normal / Unremarkable
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
                                                                    Doctor's Clinical Notes / Detailed Findings:
                                                                </span>
                                                                <p class="text-dark small mb-0 fst-italic">"<?= nl2br(h($peSaved['remarks'])) ?>"</p>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        No physical examination findings on record
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
                                                            No menstrual or reproductive history recorded
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
                                                            Nulliparous / No obstetric history recorded
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
                                        <a href="<?= url('/patients/' . $patient['id'] . '/ihp/edit') ?>" class="btn btn-primary btn-sm px-4 fw-medium">
                                            <i class="bi bi-plus-circle me-1"></i> Record IHP Medical History
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
