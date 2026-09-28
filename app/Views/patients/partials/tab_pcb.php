                    <!-- ==============================================================
                       TAB: PHIC / PCB PATIENT LEDGER (ANNEX A1 PAGE 3)
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-pcb" role="tabpanel">
                        
                        <!-- 1. Top PHIC Membership & Identity Card -->
                        <div class="card border rounded-3 p-3 shadow-xs mb-3 bg-white">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-shield-check fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h4 class="h6 mb-0 fw-bold text-dark">PHILIPPINE HEALTH INSURANCE CORPORATION</h4>
                                            <span class="badge bg-primary text-white fw-medium">PCB PATIENT LEDGER</span>
                                        </div>
                                        <div class="text-muted small">
                                            Santa Rosa City Health Office I &bull; Page 3 Primary Care Benefit (PCB1) Service Record
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="p-2 rounded bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase;">PhilHealth PIN</span>
                                        <span class="fw-bold text-primary font-monospace small"><?= !empty($patient['philhealth_no']) ? h($patient['philhealth_no']) : '<span class="text-muted fw-normal">No PIN Recorded</span>' ?></span>
                                    </div>
                                    <div class="p-2 rounded bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase;">Membership Status</span>
                                        <span class="badge <?= ($patient['phic_status'] ?? '') === 'Member' ? 'bg-success' : (($patient['phic_status'] ?? '') === 'Dependent' ? 'bg-info text-dark' : 'bg-secondary') ?>">
                                            <?= h($patient['phic_status'] ?? 'Non-Member') ?>
                                        </span>
                                    </div>
                                    <div class="p-2 rounded bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase;">PHIC Category</span>
                                        <span class="fw-semibold text-dark small"><?= !empty($patient['phic_type']) ? h($patient['phic_type']) : 'General / Standard' ?></span>
                                    </div>
                                    <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary py-2 px-3" title="Edit PhilHealth Demographics">
                                        <i class="bi bi-pencil me-1"></i> Edit PHIC Info
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Card 1: Obligated Services (Annual Tracking Grid) -->
                        <div class="card border rounded-3 shadow-xs mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                                <div>
                                    <h5 class="h6 mb-0 fw-bold text-dark">
                                        <i class="bi bi-calendar-check text-primary me-2"></i>1. Obligated Primary Preventive Services (PCB1)
                                    </h5>
                                    <span class="text-muted small">Annual and quarterly monitoring matrix for mandated preventive care services.</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm" style="width: auto;">
                                        <span class="input-group-text bg-light text-muted border-secondary-subtle">Year</span>
                                        <select name="pcb_year" id="pcb_year_select" class="form-select border-secondary-subtle fw-medium text-primary" style="min-width: 90px;" onchange="window.location.href='<?= url('/patients/' . $patient['id']) ?>?pcb_year=' + this.value + '#tab-pcb'">
                                            <?php 
                                            $currYr = (int)date('Y');
                                            // Show next year, current year, and up to 10 past years
                                            for ($yr = $currYr + 1; $yr >= $currYr - 10; $yr--): ?>
                                                <option value="<?= $yr ?>" <?= $pcbYear === $yr ? 'selected' : '' ?>><?= $yr ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#obligatedEditModal">
                                        <i class="bi bi-pencil-square me-1"></i> Update Dates
                                    </button>
                                </div>
                            </div>

                            <!-- Read-Only Table View matching Page 3 -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-center small">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start ps-3" style="width: 32%;">Primary Preventive Services</th>
                                            <th style="width: 18%;">Frequency</th>
                                            <th style="width: 12.5%;">1<sup>st</sup> Qtr</th>
                                            <th style="width: 12.5%;">2<sup>nd</sup> Qtr</th>
                                            <th style="width: 12.5%;">3<sup>rd</sup> Qtr</th>
                                            <th style="width: 12.5%;" class="pe-3">4<sup>th</sup> Qtr</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Service 1: BP Measurements -->
                                        <tr>
                                            <td class="text-start ps-3 fw-bold text-dark">
                                                1. BP Measurements
                                                <?php if (!empty($pcbObligated['is_hypertensive'])): ?>
                                                    <span class="badge bg-danger-subtle text-danger border ms-1">Hypertensive</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success border ms-1">Non-Hypertensive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= !empty($pcbObligated['is_hypertensive']) ? '<span class="text-danger fw-medium">Once a month</span>' : '<span class="text-muted">Once a year</span>' ?>
                                            </td>
                                            <td><?= !empty($pcbObligated['bp_q1']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['bp_q1'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td><?= !empty($pcbObligated['bp_q2']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['bp_q2'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td><?= !empty($pcbObligated['bp_q3']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['bp_q3'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td class="pe-3"><?= !empty($pcbObligated['bp_q4']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['bp_q4'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                        </tr>

                                        <!-- Service 2: Periodic Clinical Breast Exam -->
                                        <tr>
                                            <td class="text-start ps-3 fw-bold text-dark">2. Periodic Clinical Breast Examination</td>
                                            <td class="text-muted">Once a year</td>
                                            <td><?= !empty($pcbObligated['cbe_q1']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['cbe_q1'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td><?= !empty($pcbObligated['cbe_q2']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['cbe_q2'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td><?= !empty($pcbObligated['cbe_q3']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['cbe_q3'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td class="pe-3"><?= !empty($pcbObligated['cbe_q4']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['cbe_q4'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                        </tr>

                                        <!-- Service 3: Visual Inspection with Acetic Acid (Applicable to Females) -->
                                        <?php if ($isFemale): ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-bold text-dark">3. Visual Inspection with Acetic Acid (VIA)</td>
                                                <td class="text-muted">Once a year</td>
                                                <td><?= !empty($pcbObligated['via_q1']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['via_q1'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                                <td><?= !empty($pcbObligated['via_q2']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['via_q2'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                                <td><?= !empty($pcbObligated['via_q3']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['via_q3'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                                <td class="pe-3"><?= !empty($pcbObligated['via_q4']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['via_q4'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (!empty($pcbObligated['remarks'])): ?>
                                <div class="card-footer bg-light py-2 px-3 small border-top text-muted">
                                    <strong class="text-dark">Remarks:</strong> <?= h($pcbObligated['remarks']) ?>
                                    <?php if (!empty($pcbObligated['updater_name'])): ?>
                                        &bull; <span class="fst-italic">Last updated by <?= h($pcbObligated['updater_name']) ?> on <?= date('M d, Y h:i A', strtotime($pcbObligated['updated_at'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 3. Card 2: Diagnostic Examination, Other PCB1, & Other Services Encounter Ledger -->
                        <div class="card border rounded-3 shadow-xs">
                            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                                <div>
                                    <h5 class="h6 mb-0 fw-bold text-dark">
                                        <i class="bi bi-journal-medical text-primary me-2"></i>2. Diagnostic Examination & PCB Services Encounter Ledger
                                    </h5>
                                    <span class="text-muted small">Diagnostic tests, laboratory orders, and clinical services performed or referred under the PCB1 package.</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary px-3 fw-medium" data-bs-toggle="modal" data-bs-target="#recordPcbServiceModal">
                                    <i class="bi bi-plus-lg me-1"></i> Record Service / Test
                                </button>
                            </div>

                            <!-- Filter Pills -->
                            <div class="card-body bg-light py-2 px-3 border-bottom">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="btn-group btn-group-sm" role="group" id="pcbCategoryFilters">
                                        <button type="button" class="btn btn-outline-primary active" onclick="filterPcbRows('all', this)">All Encounters</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="filterPcbRows('Diagnostic', this)">Diagnostic Examinations</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="filterPcbRows('PCB1', this)">Other PCB1 Services</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="filterPcbRows('Other', this)">Other Services</button>
                                    </div>
                                    <span class="text-muted small">
                                        Total Records: <strong class="text-dark" id="pcbRowCount"><?= count($pcbServiceLogs) ?></strong>
                                    </span>
                                </div>
                            </div>

                            <!-- Encounter Records Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 small text-center" id="pcbServiceLogsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start ps-3" style="width: 14%;">Date</th>
                                            <th style="width: 15%;">Section / Category</th>
                                            <th class="text-start" style="width: 22%;">Service / Test Type</th>
                                            <th class="text-start" style="width: 18%;">Diagnosis</th>
                                            <th style="width: 11%;">Given</th>
                                            <th style="width: 12%;">Referred</th>
                                            <th class="pe-3 text-end" style="width: 8%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($pcbServiceLogs)): ?>
                                            <tr id="pcbNoRecordsRow">
                                                <td colspan="7" class="text-center py-5 text-muted">
                                                    <i class="bi bi-clipboard2-pulse fs-3 d-block mb-2 text-secondary"></i>
                                                    <p class="fw-medium mb-1">No diagnostic or PCB services recorded yet</p>
                                                    <p class="small text-muted mb-3">Click below to record lab examinations, diagnostic tests, or primary care benefit encounters.</p>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordPcbServiceModal">
                                                        <i class="bi bi-plus-lg me-1"></i> Record Service
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($pcbServiceLogs as $log): ?>
                                                <tr class="pcb-log-row" data-category="<?= h($log['service_category']) ?>">
                                                    <td class="text-start ps-3 fw-medium text-dark font-monospace">
                                                        <?= date('M d, Y', strtotime($log['service_date'])) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($log['service_category'] === 'Diagnostic'): ?>
                                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">Diagnostic Exam</span>
                                                        <?php elseif ($log['service_category'] === 'PCB1'): ?>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Other PCB1</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Other Services</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-start fw-bold text-dark">
                                                        <?= h($log['service_type']) ?>
                                                        <?php if (!empty($log['remarks'])): ?>
                                                            <div class="text-muted small fw-normal"><?= h($log['remarks']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-start text-secondary">
                                                        <?= !empty($log['diagnosis']) ? h($log['diagnosis']) : '<span class="text-muted fst-italic">&mdash;</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($log['status_given'])): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                                <i class="bi bi-check-lg me-1"></i>Given
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">&mdash;</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($log['status_referred'])): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1" title="<?= !empty($log['referred_to']) ? 'Referred to: ' . h($log['referred_to']) : 'Referred' ?>">
                                                                <i class="bi bi-arrow-up-right me-1"></i><?= !empty($log['referred_to']) ? h($log['referred_to']) : 'Referred' ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">&mdash;</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="pe-3 text-end text-nowrap">
                                                        <?php 
                                                            $curRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
                                                            $canDeletePcb = is_admin();
                                                            $canEditPcb = in_array($curRole, ['admin', 'super_admin', 'staff'], true);
                                                        ?>
                                                        <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 btn-view-pcb-service"
                                                                data-id="<?= $log['id'] ?>"
                                                                data-date="<?= date('M d, Y', strtotime($log['service_date'])) ?>"
                                                                data-category="<?= h($log['service_category']) ?>"
                                                                data-type="<?= h($log['service_type']) ?>"
                                                                data-diagnosis="<?= h($log['diagnosis'] ?? 'None specified') ?>"
                                                                data-given="<?= !empty($log['status_given']) ? 'Yes (Provided/Administered)' : 'No' ?>"
                                                                data-referred="<?= !empty($log['status_referred']) ? 'Yes (' . h($log['referred_to'] ?? 'External Provider') . ')' : 'No' ?>"
                                                                data-remarks="<?= h($log['remarks'] ?? 'None') ?>"
                                                                data-recorder="<?= h($log['recorder_name'] ?? 'System') ?>"
                                                                title="View Encounter Details">
                                                                <i class="bi bi-eye fs-6"></i>
                                                            </button>
                                                            <?php if ($canEditPcb): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-pcb-service"
                                                                    data-id="<?= $log['id'] ?>"
                                                                    data-date="<?= h($log['service_date']) ?>"
                                                                    data-category="<?= h($log['service_category']) ?>"
                                                                    data-type="<?= h($log['service_type']) ?>"
                                                                    data-diagnosis="<?= h($log['diagnosis'] ?? '') ?>"
                                                                    data-given="<?= !empty($log['status_given']) ? '1' : '0' ?>"
                                                                    data-referred="<?= !empty($log['status_referred']) ? '1' : '0' ?>"
                                                                    data-referred-to="<?= h($log['referred_to'] ?? '') ?>"
                                                                    data-remarks="<?= h($log['remarks'] ?? '') ?>"
                                                                    title="Edit Encounter">
                                                                    <i class="bi bi-pencil-square fs-6"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <?php if ($canDeletePcb): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-delete-pcb-service" 
                                                                    data-id="<?= $log['id'] ?>" 
                                                                    data-service="<?= h($log['service_type']) ?>" 
                                                                    title="Delete Entry">
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

                    </div>
