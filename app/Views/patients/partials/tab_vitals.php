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
                                        <th>Recorded By</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($vitalsHistory)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-activity fs-3 d-block mb-2 text-secondary"></i>
                                                No vital signs records exist for this patient.
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
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y h:i A', strtotime($v['recorded_at'])) ?></td>
                                                <td class="fw-bold font-monospace"><?= h($v['bp_systolic'] ?? '--') ?>/<?= h($v['bp_diastolic'] ?? '--') ?></td>
                                                <td><?= h($v['heart_rate'] ?? '--') ?></td>
                                                <td><?= h($v['temperature'] ?? '--') ?></td>
                                                <td class="text-muted"><?= h($v['recorder_name'] ?? 'Clinician') ?></td>
                                                <td class="pe-3 text-end text-nowrap">
                                                    <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 btn-view-vitals" 
                                                            data-id="<?= $v['id'] ?>"
                                                            data-date="<?= date('M d, Y h:i A', strtotime($v['recorded_at'])) ?>"
                                                            data-bp="<?= h(($v['bp_systolic'] ?? '--') . '/' . ($v['bp_diastolic'] ?? '--')) ?>"
                                                            data-pulse="<?= h($v['heart_rate'] ?? '--') ?>"
                                                            data-temp="<?= h($v['temperature'] ?? '--') ?>"
                                                            data-resp="<?= h($v['respiratory_rate'] ?? '--') ?>"
                                                            data-spo2="<?= h($v['oxygen_saturation'] ?? '--') ?>"
                                                            data-weight="<?= h($v['weight'] ?? '--') ?>"
                                                            data-height="<?= h($v['height'] ?? '--') ?>"
                                                            data-bmi="<?= h($v['bmi'] ?? '--') ?>"
                                                            data-waist="<?= h($v['waist_circumference'] ?? '--') ?>"
                                                            data-notes="<?= h($v['notes'] ?? '') ?>"
                                                            data-recorder="<?= h($v['recorder_name'] ?? 'Clinician') ?>"
                                                            title="View Details">
                                                            <i class="bi bi-eye fs-6"></i>
                                                        </button>
                                                        <?php if ($canEditVital): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-vitals"
                                                                data-id="<?= $v['id'] ?>"
                                                                data-bp-systolic="<?= h($v['bp_systolic'] ?? '') ?>"
                                                                data-bp-diastolic="<?= h($v['bp_diastolic'] ?? '') ?>"
                                                                data-pulse="<?= h($v['heart_rate'] ?? '') ?>"
                                                                data-temp="<?= h($v['temperature'] ?? '') ?>"
                                                                data-resp="<?= h($v['respiratory_rate'] ?? '') ?>"
                                                                data-spo2="<?= h($v['oxygen_saturation'] ?? '') ?>"
                                                                data-weight="<?= h($v['weight'] ?? '') ?>"
                                                                data-height="<?= h($v['height'] ?? '') ?>"
                                                                data-waist="<?= h($v['waist_circumference'] ?? '') ?>"
                                                                data-notes="<?= h($v['notes'] ?? '') ?>"
                                                                title="Edit Vital Signs">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($canDeleteVital): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-delete-vital" data-id="<?= $v['id'] ?>" title="Delete Vital Signs">
                                                                <i class="bi bi-trash fs-6"></i>
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
