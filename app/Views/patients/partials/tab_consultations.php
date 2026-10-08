<!-- ==============================================================
   TAB 4: CLINICAL CONSULTATION LEDGER
   ============================================================== -->
<div class="tab-pane fade" id="tab-consultations" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="h6 fw-bold text-dark mb-0">Consultations</h5>
        <a href="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Consultation
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-center small" id="consultationsTable">
            <thead class="table-light">
                <tr>
                    <th class="text-start ps-3">Date</th>
                    <th class="text-start">History of Present Illness</th>
                    <th class="text-start">Assessment / Impression</th>
                    <th>Clinician</th>
                    <th class="pe-3 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($consultationsHistory)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard2-x fs-3 d-block mb-2 text-secondary"></i>
                            <div class="mb-2">No consultations recorded.</div>
                            <a href="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> Add Consultation
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                        $curUserId = (int)($_SESSION['user_id'] ?? 0);
                        $curRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
                    ?>
                    <?php foreach ($consultationsHistory as $c): 
                        $canArchiveConsultation = is_admin();
                        $canEditRow = in_array($curRole, ['admin', 'super_admin', 'staff'], true);
                    ?>
                        <tr>
                            <td class="text-start ps-3 fw-medium text-dark text-nowrap"><?= date('M d, Y h:i A', strtotime($c['consulted_at'])) ?></td>
                            <td class="text-start text-secondary text-truncate" style="max-width: 220px;" title="<?= h($c['subjective']) ?>">
                                <?= h(mb_strimwidth($c['subjective'], 0, 55, '...')) ?>
                            </td>
                            <td class="text-start fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?= h($c['assessment']) ?>">
                                <?= h(mb_strimwidth($c['assessment'], 0, 55, '...')) ?>
                            </td>
                            <td class="text-nowrap"><?= h($c['clinician_name']) ?></td>
                            <td class="pe-3 text-end text-nowrap">
                                <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                    <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-2xs view-consultation-btn" data-consultation-id="<?= $c['id'] ?>" title="View Consultation Record" aria-label="View consultation record">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if ($canEditRow): ?>
                                        <a href="<?= url('/consultations/' . $c['id'] . '/edit') ?>" class="btn btn-sm btn-light border px-2 py-1 text-secondary shadow-2xs" title="Edit Consultation" aria-label="Edit consultation">
                                             <i class="bi bi-pencil-square"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canArchiveConsultation): ?>
                                        <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-danger shadow-2xs btn-archive-consultation" data-id="<?= $c['id'] ?>" data-patient-id="<?= $patient['id'] ?>" title="Archive Consultation" aria-label="Archive consultation">
                                            <i class="bi bi-archive"></i>
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
