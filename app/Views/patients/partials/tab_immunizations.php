                    <!-- ==============================================================
                       TAB 5: UNIVERSAL IMMUNIZATIONS
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-immunizations" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="h6 fw-bold text-dark mb-0">Universal Immunization Records</h5>
                                <span class="text-muted small">Tracks vaccines administered across all life stages (EPI Routine Infant, HPV, COVID-19, Flu, Pneumococcal).</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recordImmunizationModal">
                                <i class="bi bi-plus-lg me-1"></i> Record Vaccine Dose
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="immunizationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Vaccine Name</th>
                                        <th>Dose #</th>
                                        <th>Administered Date</th>
                                        <th>Source / Status</th>
                                        <th>Remarks / Program</th>
                                        <th>Vaccinator</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($patientImmunizations)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-shield-slash fs-3 d-block mb-2 text-secondary"></i>
                                                No immunization records recorded for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                            $curUserId = (int)($_SESSION['user_id'] ?? 0);
                                            $curRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
                                        ?>
                                        <?php foreach ($patientImmunizations as $imm): 
                                            $canDeleteImm = is_admin();
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-bold text-primary">
                                                    <i class="bi bi-shield-check me-1 text-success"></i><?= h($imm['vaccine_name']) ?>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">Dose <?= h($imm['dose_number']) ?></span></td>
                                                <td class="fw-medium text-dark"><?= date('M d, Y', strtotime($imm['administered_date'])) ?></td>
                                                <td class="text-muted small">
                                                    <?= h($imm['source'] ?? 'Health Center') ?>
                                                    <span class="badge bg-light text-dark border"><?= h($imm['documentation_status'] ?? 'Administered') ?></span>
                                                </td>
                                                <td class="text-muted small"><?= h($imm['remarks'] ?? 'Routine') ?></td>
                                                <td class="text-muted"><?= h($imm['vaccinator_name'] ?? 'Healthcare Staff') ?></td>
                                                <td class="pe-3 text-end text-nowrap">
                                                    <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 btn-view-immunization"
                                                            data-id="<?= $imm['id'] ?>"
                                                            data-vaccine="<?= h($imm['vaccine_name']) ?>"
                                                            data-dose="Dose <?= h($imm['dose_number']) ?>"
                                                            data-date="<?= date('M d, Y', strtotime($imm['administered_date'])) ?>"
                                                            data-source="<?= h($imm['source'] ?? 'Barangay Sinalhan Health Center') ?>"
                                                            data-status="<?= h($imm['documentation_status'] ?? 'Administered') ?>"
                                                            data-remarks="<?= h($imm['remarks'] ?? 'Routine immunisation protocol') ?>"
                                                            data-vaccinator="<?= h($imm['vaccinator_name'] ?? 'Healthcare Staff') ?>"
                                                            title="View Immunization Details">
                                                            <i class="bi bi-eye fs-6"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-immunization"
                                                            data-id="<?= $imm['id'] ?>"
                                                            data-vaccine="<?= h($imm['vaccine_name']) ?>"
                                                            data-dose="<?= h($imm['dose_number']) ?>"
                                                            data-date="<?= h($imm['administered_date']) ?>"
                                                            data-source="<?= h($imm['source'] ?? 'Health Center') ?>"
                                                            data-status="<?= h($imm['documentation_status'] ?? 'Administered') ?>"
                                                            data-remarks="<?= h($imm['remarks'] ?? '') ?>"
                                                            title="Edit Immunization">
                                                            <i class="bi bi-pencil-square fs-6"></i>
                                                        </button>
                                                        <?php if ($canDeleteImm): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-delete-immunization" data-id="<?= $imm['id'] ?>" data-vaccine="<?= h($imm['vaccine_name']) ?>" data-dose="<?= h($imm['dose_number']) ?>" title="Delete Record">
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
