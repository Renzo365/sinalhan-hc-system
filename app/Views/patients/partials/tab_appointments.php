                    <!-- ==============================================================
                       TAB 8: APPOINTMENTS & QUEUE
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-appointments" role="tabpanel">
                        <div class="row g-3">
                            <!-- Appointments List -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 p-3 shadow-xs h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 text-dark small">Scheduled Appointments</h6>
                                        <a href="<?= url('/appointments/create?patient_id=' . $patient['id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                            <i class="bi bi-plus-circle me-1"></i> Book
                                        </a>
                                    </div>
                                    <?php if (empty($appointmentsHistory)): ?>
                                        <p class="text-muted small text-center py-3 mb-0">No upcoming appointments scheduled.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($appointmentsHistory as $a): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <strong><?= date('M d, Y', strtotime($a['appointment_date'])) ?></strong> at <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-0.5 ms-1" style="font-size: 0.7rem;"><?= h($a['program_type'] ?? 'General OPD') ?></span>
                                                        <span class="text-muted d-block" style="font-size: 0.75rem;"><?= h($a['purpose']) ?></span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php 
                                                            $apptBadge = 'bg-light text-dark border';
                                                            if ($a['status'] === 'Completed') $apptBadge = 'bg-success-subtle text-success border border-success-subtle';
                                                            elseif ($a['status'] === 'Cancelled') $apptBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                                                            elseif ($a['status'] === 'Missed') $apptBadge = 'bg-warning-subtle text-warning border border-warning-subtle';
                                                            elseif ($a['status'] === 'Scheduled') $apptBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                                        ?>
                                                        <span class="badge <?= $apptBadge ?>"><?= h($a['status']) ?></span>
                                                        <div class="d-inline-flex gap-1 align-items-center">
                                                            <?php if ($a['status'] === 'Scheduled'): ?>
                                                                <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary border-0 p-1" title="Reschedule / Edit">
                                                                    <i class="bi bi-pencil-square fs-6"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-cancel-appointment" data-id="<?= $a['id'] ?>" data-date="<?= date('M d, Y', strtotime($a['appointment_date'])) ?>" title="Cancel Appointment">
                                                                    <i class="bi bi-x-circle fs-6"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Queue Logs -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 p-3 shadow-xs h-100">
                                    <h6 class="fw-bold mb-2 text-dark small">Daily Queue Visits</h6>
                                    <?php if (empty($queueHistory)): ?>
                                        <p class="text-muted small text-center py-3 mb-0">No daily queue visits recorded.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($queueHistory as $q): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <strong><?= h($q['queue_date']) ?></strong> &bull; Queue #<?= sprintf('%03d', $q['queue_no']) ?>
                                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Time In: <?= $q['time_in'] ? date('h:i A', strtotime($q['time_in'])) : '--' ?></span>
                                                    </div>
                                                    <span class="badge bg-primary-subtle text-primary border"><?= h($q['status']) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
