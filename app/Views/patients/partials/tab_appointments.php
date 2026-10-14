                    <!-- ==============================================================
                       TAB 7: APPOINTMENTS & QUEUE
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-appointments" role="tabpanel" aria-labelledby="tab-appointments-btn" tabindex="0">
                        <div class="row g-3">
                            <!-- Appointments List -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 p-3 shadow-xs h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h3 class="h6 fw-bold mb-0 text-dark" style="text-wrap: balance;">Scheduled Appointments</h3>
                                        <a href="<?= url('/appointments/create?patient_id=' . $patient['id']) ?>" class="btn btn-sm btn-outline-primary px-2 py-1 d-inline-flex align-items-center" style="min-height: 31px;">
                                            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Schedule Appointment
                                        </a>
                                    </div>
                                    <?php if (empty($appointmentsHistory)): ?>
                                        <div class="text-muted small text-center py-4">
                                            <div>No upcoming appointments scheduled.</div>
                                        </div>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($appointmentsHistory as $a): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <strong style="font-variant-numeric: tabular-nums;"><?= date('M d, Y', strtotime($a['appointment_date'])) ?></strong> at <span style="font-variant-numeric: tabular-nums;"><?= date('h:i A', strtotime($a['appointment_time'])) ?></span>
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis border px-1.5 py-0.5 ms-1" style="font-size: 0.7rem;"><?= h($a['program_type'] ?? 'General OPD') ?></span>
                                                        <span class="text-muted d-block" style="font-size: 0.75rem;"><?= h($a['purpose']) ?></span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php 
                                                            $todayDate = date('Y-m-d');
                                                            $isPastDue = ($a['status'] === 'Scheduled' && $a['appointment_date'] < $todayDate);
                                                            $daysPastDue = 0;
                                                            if ($isPastDue) {
                                                                $d1 = new DateTime($a['appointment_date']);
                                                                $d2 = new DateTime($todayDate);
                                                                $daysPastDue = $d2->diff($d1)->days;
                                                            }

                                                            $apptBadge = 'bg-light text-dark border';
                                                            if ($a['status'] === 'Completed') $apptBadge = 'bg-success-subtle text-success border border-success-subtle';
                                                            elseif ($a['status'] === 'Cancelled') $apptBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                                                            elseif ($a['status'] === 'Missed') $apptBadge = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                                            elseif ($isPastDue) $apptBadge = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                                            elseif ($a['status'] === 'Scheduled') $apptBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                                        ?>
                                                        <span class="badge <?= $apptBadge ?>">
                                                            <?php if ($isPastDue): ?>
                                                                <i class="bi bi-exclamation-triangle-fill me-1 text-warning" aria-hidden="true"></i>Past Due (<span style="font-variant-numeric: tabular-nums;"><?= $daysPastDue ?>d ago</span>)
                                                            <?php else: ?>
                                                                <?= h($a['status']) ?>
                                                            <?php endif; ?>
                                                        </span>
                                                        <div class="d-inline-flex gap-1 align-items-center">
                                                            <?php if ($a['status'] === 'Scheduled'): ?>
                                                                <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-light border px-2 py-1 <?= $isPastDue ? 'text-warning-emphasis' : 'text-primary' ?> shadow-2xs" title="<?= $isPastDue ? 'Reschedule Past Due Appointment' : 'Reschedule / Edit' ?>" aria-label="<?= $isPastDue ? 'Reschedule Past Due Appointment' : 'Reschedule or edit appointment' ?>" style="min-width: 32px; min-height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                                                    <i class="bi <?= $isPastDue ? 'bi-arrow-repeat' : 'bi-pencil-square' ?>" aria-hidden="true"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-danger shadow-2xs btn-cancel-appointment" data-id="<?= $a['id'] ?>" data-date="<?= date('M d, Y', strtotime($a['appointment_date'])) ?>" title="Cancel Appointment" aria-label="Cancel appointment" style="min-width: 32px; min-height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                                                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <?php if (is_admin()): ?>
                                                                <button type="button" class="btn btn-sm btn-light border px-2 py-1 text-danger shadow-2xs btn-void-appointment" data-id="<?= $a['id'] ?>" data-patient="<?= h($patient['first_name'] . ' ' . $patient['last_name']) ?>" data-date="<?= date('M d, Y', strtotime($a['appointment_date'])) ?>" title="Void (Booked in Error)" aria-label="Void appointment" style="min-width: 32px; min-height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                                                    <i class="bi bi-trash3" aria-hidden="true"></i>
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
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <h3 class="h6 fw-bold mb-0 text-dark" style="text-wrap: balance;">Daily Queue Visits</h3>
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2.5 py-1 d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#enqueuePatientModal" title="Check-in Patient to Today's Queue" style="min-height: 31px; font-size: 0.8125rem;">
                                            <i class="bi bi-person-check-fill me-1" aria-hidden="true"></i> Check-in to Queue
                                        </button>
                                    </div>
                                    <?php if (empty($queueHistory)): ?>
                                        <p class="text-muted small text-center py-3 mb-0">No daily queue visits recorded.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($queueHistory as $q): 
                                                $qStatus = $q['status'] ?? 'Waiting';
                                                $qBadge = 'bg-light text-dark border';
                                                if (in_array($qStatus, ['Waiting', 'Pending'], true)) {
                                                    $qBadge = 'bg-warning-subtle text-dark border border-warning-subtle';
                                                } elseif (in_array($qStatus, ['In Consultation', 'Called', 'Serving'], true)) {
                                                    $qBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                                } elseif ($qStatus === 'Completed') {
                                                    $qBadge = 'bg-success-subtle text-success border border-success-subtle';
                                                } elseif (in_array($qStatus, ['Cancelled', 'No Show', 'Skipped'], true)) {
                                                    $qBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                                                }
                                            ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <strong style="font-variant-numeric: tabular-nums;"><?= h($q['queue_date']) ?></strong> <span aria-hidden="true">&bull;</span> <span style="font-variant-numeric: tabular-nums;">Queue #<?= sprintf('%03d', $q['queue_no']) ?></span>
                                                        <?php if (!empty($q['service_type'])): ?>
                                                            <span class="badge bg-secondary-subtle text-secondary-emphasis border ms-1" style="font-size: 0.7rem;"><?= h($q['service_type']) ?></span>
                                                        <?php endif; ?>
                                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Time In: <span style="font-variant-numeric: tabular-nums;"><?= $q['time_in'] ? date('h:i A', strtotime($q['time_in'])) : '--' ?></span></span>
                                                    </div>
                                                    <span class="badge <?= $qBadge ?>"><?= h($qStatus) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
