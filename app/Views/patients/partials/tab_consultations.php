                    <!-- ==============================================================
                       TAB 3: CONSULTATIONS (SOAP)
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-consultations" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="h6 fw-bold text-dark mb-0">Consultations History (SOAP Notes)</h5>
                            <a href="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> New Consultation
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="consultationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Date</th>
                                        <th>Clinician</th>
                                        <th>Assessment / Diagnosis</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($consultationsHistory)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-clipboard2-x fs-3 d-block mb-2 text-secondary"></i>
                                                No consultation records exist for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                            $curUserId = (int)($_SESSION['user_id'] ?? 0);
                                            $curRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
                                        ?>
                                        <?php foreach ($consultationsHistory as $c): 
                                            $canArchiveConsultation = is_admin();
                                            $canEditRow = ($c['status'] !== 'Cancelled') && in_array($curRole, ['admin', 'super_admin', 'staff'], true);
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y h:i A', strtotime($c['consulted_at'])) ?></td>
                                                <td><?= h($c['clinician_name']) ?></td>
                                                <td class="text-start"><?= h(mb_strimwidth($c['assessment'], 0, 50, '...')) ?></td>
                                                <td>
                                                    <?php if ($c['status'] === 'Cancelled'): ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Cancelled</span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">&mdash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-3 text-end text-nowrap">
                                                    <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 view-consultation-btn" data-consultation-id="<?= $c['id'] ?>" title="View Full SOAP">
                                                            <i class="bi bi-eye fs-6"></i>
                                                        </button>
                                                        <?php if ($canEditRow): ?>
                                                            <a href="<?= url('/consultations/' . $c['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary border-0 p-1" title="Edit Consultation">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($canArchiveConsultation): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-archive-consultation" data-id="<?= $c['id'] ?>" data-patient-id="<?= $patient['id'] ?>" title="Archive Consultation">
                                                                <i class="bi bi-archive fs-6"></i>
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
