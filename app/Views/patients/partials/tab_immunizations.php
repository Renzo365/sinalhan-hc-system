                    <!-- ==============================================================
                       TAB 6: UNIVERSAL IMMUNIZATIONS
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

                        <?php if (!empty($wellbabyRecord)): ?>
                            <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 px-3 mb-3 border-success-subtle rounded-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-emoji-smile-fill fs-5 text-success"></i>
                                    <div>
                                        <strong class="text-success-emphasis">Enrolled in Well-Baby &amp; Under-5 EPI:</strong>
                                        <span class="text-muted small ms-1">View the 13-dose infant milestone schedule, supplementation tracker, and pediatric growth logs.</span>
                                    </div>
                                </div>
                                <a href="<?= url('/well-baby/' . $patient['id']) ?>" class="btn btn-sm btn-success text-white text-nowrap">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Open Well-Baby Workstation
                                </a>
                            </div>
                        <?php elseif (isset($patient['age']) && (int)$patient['age'] <= 5): ?>
                            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 px-3 mb-3 border-info-subtle rounded-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                                    <div>
                                        <strong class="text-primary">Child is Eligible for Well-Baby Care:</strong>
                                        <span class="text-muted small ms-1">This patient (aged &le; 5) can be enrolled for structured EPI milestone tracking and growth monitoring.</span>
                                    </div>
                                </div>
                                <a href="<?= url('/well-baby/register?patient_id=' . $patient['id']) ?>" class="btn btn-sm btn-primary text-nowrap">
                                    <i class="bi bi-plus-circle me-1"></i> Enroll in Well-Baby
                                </a>
                            </div>
                        <?php endif; ?>
                        
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
                                            $docStatus = $imm['documentation_status'] ?? 'Administered';
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-bold text-primary">
                                                    <i class="bi bi-shield-check me-1 text-success"></i><?= h($imm['vaccine_name']) ?>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">Dose <?= h($imm['dose_number']) ?></span></td>
                                                <td class="fw-medium text-dark"><?= date('M d, Y', strtotime($imm['administered_date'])) ?></td>
                                                <td class="text-muted small">
                                                    <div><?= h($imm['source'] ?? 'Health Center') ?></div>
                                                    <?php if ($docStatus === 'Administered'): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Administered</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Reported</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted small"><?= h($imm['remarks'] ?? 'Routine') ?></td>
                                                <td class="text-muted"><?= h($imm['vaccinator_name'] ?? 'Healthcare Staff') ?></td>
                                                <td class="pe-3 text-end text-nowrap">
                                                    <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                        <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-2xs btn-view-immunization"
                                                            data-id="<?= $imm['id'] ?>"
                                                            data-vaccine="<?= h($imm['vaccine_name']) ?>"
                                                            data-dose="Dose <?= h($imm['dose_number']) ?>"
                                                            data-date="<?= date('M d, Y', strtotime($imm['administered_date'])) ?>"
                                                            data-source="<?= h($imm['source'] ?? 'Barangay Sinalhan Health Center') ?>"
                                                            data-status="<?= h($imm['documentation_status'] ?? 'Administered') ?>"
                                                            data-remarks="<?= h($imm['remarks'] ?? 'Routine immunisation protocol') ?>"
                                                            data-vaccinator="<?= h($imm['vaccinator_name'] ?? 'Healthcare Staff') ?>"
                                                            title="View Immunization Details"
                                                            aria-label="View immunization details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-secondary shadow-2xs btn-edit-immunization"
                                                            data-id="<?= $imm['id'] ?>"
                                                            data-vaccine="<?= h($imm['vaccine_name']) ?>"
                                                            data-dose="<?= h($imm['dose_number']) ?>"
                                                            data-date="<?= h($imm['administered_date']) ?>"
                                                            data-source="<?= h($imm['source'] ?? 'Health Center') ?>"
                                                            data-status="<?= h($imm['documentation_status'] ?? 'Administered') ?>"
                                                            data-remarks="<?= h($imm['remarks'] ?? '') ?>"
                                                            title="Edit Immunization"
                                                            aria-label="Edit immunization">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <?php if ($canDeleteImm): ?>
                                                            <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-danger shadow-2xs btn-delete-immunization" data-id="<?= $imm['id'] ?>" data-vaccine="<?= h($imm['vaccine_name']) ?>" data-dose="<?= h($imm['dose_number']) ?>" title="Delete Record" aria-label="Delete immunization record">
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
