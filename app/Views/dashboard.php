<?php
$title = 'Dashboard';
require __DIR__ . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Dashboard</h2>
        <p class="text-secondary small mb-0">Daily health center summary, active queue counts, and today's appointments schedule.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- New Patients Today Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-primary">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="text-secondary small fw-medium text-uppercase tracking-wider">New Patients Today</div>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-3 fw-bold text-dark"><?= number_format($stats['new_patients_today'] ?? 0) ?></span>
                        <span class="text-muted small" title="Total active registered patients in database">(<?= number_format($stats['total_patients']) ?> total)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Appointments Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-info">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-info-subtle text-info p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="bi bi-calendar2-check-fill fs-4"></i>
                </div>
                <div>
                    <div class="text-secondary small fw-medium text-uppercase tracking-wider">Today's Appointments</div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($stats['today_appointments']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Queue Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-warning">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-warning-subtle text-warning p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="bi bi-ticket-perforated-fill fs-4"></i>
                </div>
                <div>
                    <div class="text-secondary small fw-medium text-uppercase tracking-wider">Today's Active Queue</div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($stats['queue_now']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Visits Today Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-success">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-success-subtle text-success p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
                <div>
                    <div class="text-secondary small fw-medium text-uppercase tracking-wider">Visits Completed</div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($stats['today_visits']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Today's Appointments Section -->
    <div class="col-12 col-lg-7">
        <div class="card card-premium h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-calendar3 me-2 text-primary"></i>Today's Scheduled Appointments
                </h3>
                <span class="badge bg-primary-soft text-primary">Today</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Time</th>
                                <th>Patient Name</th>
                                <th>Purpose</th>
                                <th class="pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($todayAppointments)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-check fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <div class="fw-semibold text-dark mb-1">No appointments scheduled for today</div>
                                        <p class="small text-secondary mb-3">All clear! You can book new appointments in the calendar module.</p>
                                        <a href="<?= url('/appointments') ?>" class="btn btn-sm btn-outline-primary px-3">
                                            <i class="bi bi-calendar-plus me-1"></i> Go to Appointments
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($todayAppointments as $appt): 
                                    $badge = 'bg-secondary';
                                    if ($appt['status'] === 'Scheduled') $badge = 'bg-info text-dark';
                                    elseif ($appt['status'] === 'Completed') $badge = 'bg-success text-white';
                                    elseif ($appt['status'] === 'Cancelled') $badge = 'bg-danger text-white';
                                    elseif ($appt['status'] === 'Missed') $badge = 'bg-dark text-white';
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-secondary font-monospace small" style="white-space: nowrap;">
                                            <?= date('h:i A', strtotime($appt['appointment_time'])) ?>
                                        </td>
                                        <td class="fw-bold">
                                            <a href="<?= url('/patients/' . $appt['patient_id']) ?>" class="link-primary-dark text-decoration-none">
                                                <?= h($appt['patient_last']) ?>, <?= h($appt['patient_first']) ?>
                                            </a>
                                        </td>
                                        <td class="text-secondary text-truncate" style="max-width: 150px;" title="<?= h($appt['purpose']) ?>">
                                            <?= h($appt['purpose']) ?>
                                        </td>
                                        <td class="pe-4">
                                            <span class="badge <?= $badge ?>"><?= h($appt['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Summary Board -->
    <div class="col-12 col-lg-5">
        <div class="card card-premium h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-play-circle-fill me-2 text-warning"></i>Daily Queue Overview
                </h3>
                <span class="badge bg-warning-bg text-warning border border-warning-subtle">Active</span>
            </div>
            <div class="card-body py-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="text-center py-3">
                        <div class="display-3 fw-bold text-primary mb-1 font-monospace"><?= $queueStats['serving_no'] ? sprintf('%03d', $queueStats['serving_no']) : '000' ?></div>
                        <div class="text-muted small text-uppercase fw-semibold tracking-wider">Current Queue Number</div>
                    </div>
                    
                    <hr class="my-3 text-muted opacity-25">
                    
                    <div class="d-flex justify-content-around text-center mt-3">
                        <div>
                            <div class="fs-4 fw-bold text-warning font-monospace"><?= number_format($queueStats['waiting']) ?></div>
                            <div class="text-muted small">Waiting</div>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-primary font-monospace"><?= number_format($queueStats['called']) ?></div>
                            <div class="text-muted small">Called</div>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-success font-monospace"><?= number_format($queueStats['completed']) ?></div>
                            <div class="text-muted small">Completed</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-center">
                    <a href="<?= url('/queue') ?>" class="btn btn-light border text-primary fw-semibold w-100 py-2 d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-card-checklist me-2"></i> Manage Live Queue
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
