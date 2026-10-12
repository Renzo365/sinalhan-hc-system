                    <!-- ==============================================================
                       TAB 5: VITAL SIGNS HISTORY
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-vitals" role="tabpanel" aria-labelledby="tab-vitals-btn" tabindex="0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="h6 fw-bold text-dark mb-0">Vital Signs</h5>
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
                                        <th>SpO2 (%)</th>
                                        <th>BMI</th>
                                        <th>Recorded By</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($vitalsHistory)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="bi bi-activity fs-3 d-block mb-2 text-secondary"></i>
                                                <div class="mb-2">No vital signs recorded.</div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                                                    <i class="bi bi-plus-lg me-1"></i> Record Vitals
                                                </button>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                            $curUserId = (int)($_SESSION['user_id'] ?? 0);
                                            $curRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
                                        ?>
                                        <?php foreach ($vitalsHistory as $v): 
                                            $canDeleteVital = is_admin();
                                            $canEditVital = in_array($curRole, ['admin', 'super_admin', 'staff'], true);
                                            $bpInfo = classify_bp($v['bp_systolic'] ?? null, $v['bp_diastolic'] ?? null);
                                            $hrInfo = classify_heart_rate($v['heart_rate'] ?? null);
                                            $tempInfo = classify_temperature($v['temperature'] ?? null);
                                            $spo2Info = classify_spo2($v['oxygen_saturation'] ?? null);
                                            $bmiInfo = classify_bmi($v['bmi'] ?? null);
                                            $formattedTemp = format_temperature($v['temperature'] ?? null);
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-medium text-dark text-nowrap"><?= date('M d, Y h:i A', strtotime($v['recorded_at'])) ?></td>
                                                <td class="text-nowrap">
                                                    <?php if ($bpInfo['sys'] !== null): ?>
                                                        <div class="d-inline-flex align-items-center gap-1">
                                                            <span class="font-monospace fw-bold <?= $bpInfo['is_high'] ? 'text-danger' : ($bpInfo['is_abnormal'] ? 'text-warning-emphasis' : 'text-dark') ?>">
                                                                <?= $bpInfo['sys'] ?>/<?= $bpInfo['dia'] ?>
                                                            </span>
                                                            <?= $bpInfo['pill'] ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">&mdash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-nowrap">
                                                    <?php if ($hrInfo['value'] !== null): ?>
                                                        <div class="d-inline-flex align-items-center gap-1">
                                                            <span class="<?= $hrInfo['is_abnormal'] ? 'fw-bold text-warning-emphasis' : 'text-dark' ?>">
                                                                <?= $hrInfo['value'] ?>
                                                            </span>
                                                            <?= $hrInfo['pill'] ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">&mdash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-nowrap">
                                                    <?php if ($tempInfo['value'] !== null): ?>
                                                        <div class="d-inline-flex align-items-center gap-1">
                                                            <span class="<?= $tempInfo['is_abnormal'] ? 'fw-bold ' . ($tempInfo['label'] === 'Hypothermia' ? 'text-info' : 'text-danger') : 'text-dark' ?>">
                                                                <?= $tempInfo['formatted'] ?>
                                                            </span>
                                                            <?= $tempInfo['pill'] ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">&mdash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-nowrap">
                                                    <?php if ($spo2Info['value'] !== null): ?>
                                                        <div class="d-inline-flex align-items-center gap-1">
                                                            <span class="<?= $spo2Info['is_abnormal'] ? 'fw-bold text-danger' : 'text-dark' ?>">
                                                                <?= $spo2Info['formatted'] ?>
                                                            </span>
                                                            <?= $spo2Info['pill'] ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">&mdash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-nowrap">
                                                    <?php if (!empty($v['bmi']) && (float)$v['bmi'] > 0): ?>
                                                        <div class="d-inline-flex align-items-center gap-1">
                                                            <span class="fw-bold text-dark"><?= number_format((float)$v['bmi'], 1) ?></span>
                                                            <span class="badge <?= $bmiInfo['badge'] ?>" style="font-size: 0.7rem;"><?= $bmiInfo['label'] ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">&mdash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted"><?= h($v['recorder_name'] ?? 'Clinician') ?></td>
                                                <td class="pe-3 text-end text-nowrap">
                                                    <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                        <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-2xs btn-view-vitals" 
                                                            data-id="<?= $v['id'] ?>"
                                                            data-date="<?= date('M d, Y h:i A', strtotime($v['recorded_at'])) ?>"
                                                            data-bp="<?= h(($v['bp_systolic'] ?? '--') . '/' . ($v['bp_diastolic'] ?? '--')) ?>"
                                                            data-pulse="<?= h($v['heart_rate'] ?? '--') ?>"
                                                            data-temp="<?= h($formattedTemp ?? '--') ?>"
                                                            data-resp="<?= h($v['respiratory_rate'] ?? '--') ?>"
                                                            data-spo2="<?= h($v['oxygen_saturation'] ?? '--') ?>"
                                                            data-weight="<?= h($v['weight'] ?? '--') ?>"
                                                            data-height="<?= h($v['height'] ?? '--') ?>"
                                                            data-bmi="<?= h(!empty($v['bmi']) ? number_format((float)$v['bmi'], 1) : '--') ?>"
                                                            data-waist="<?= h($v['waist_circumference'] ?? '--') ?>"
                                                            data-notes="<?= h($v['notes'] ?? '') ?>"
                                                            data-recorder="<?= h($v['recorder_name'] ?? 'Clinician') ?>"
                                                            title="View Details"
                                                            aria-label="View vital signs details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <?php if ($canEditVital): ?>
                                                            <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-secondary shadow-2xs btn-edit-vitals"
                                                                data-id="<?= $v['id'] ?>"
                                                                data-bp-systolic="<?= h($v['bp_systolic'] ?? '') ?>"
                                                                data-bp-diastolic="<?= h($v['bp_diastolic'] ?? '') ?>"
                                                                data-pulse="<?= h($v['heart_rate'] ?? '') ?>"
                                                                data-temp="<?= h($formattedTemp ?? '') ?>"
                                                                data-resp="<?= h($v['respiratory_rate'] ?? '') ?>"
                                                                data-spo2="<?= h($v['oxygen_saturation'] ?? '') ?>"
                                                                data-weight="<?= h($v['weight'] ?? '') ?>"
                                                                data-height="<?= h($v['height'] ?? '') ?>"
                                                                data-waist="<?= h($v['waist_circumference'] ?? '') ?>"
                                                                data-notes="<?= h($v['notes'] ?? '') ?>"
                                                                title="Edit Vital Signs"
                                                                aria-label="Edit vital signs">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($canDeleteVital): ?>
                                                            <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-danger shadow-2xs btn-delete-vital" data-id="<?= $v['id'] ?>" title="Delete Vital Signs" aria-label="Delete vital signs">
                                                                <i class="bi bi-trash"></i>
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
