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

<div class="row g-4 mb-4">
    <!-- Total Patients Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-premium hover-action metric-card">
            <div class="metric-data">
                <span class="metric-label">Total Patients</span>
                <div class="metric-value"><?= number_format($stats['total_patients']) ?></div>
            </div>
            <div class="metric-icon-box primary">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>

    <!-- Today's Appointments Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-premium hover-action metric-card">
            <div class="metric-data">
                <span class="metric-label">Today's Appointments</span>
                <div class="metric-value"><?= number_format($stats['today_appointments']) ?></div>
            </div>
            <div class="metric-icon-box accent">
                <i class="bi bi-calendar2-check-fill"></i>
            </div>
        </div>
    </div>

    <!-- Today's Queue Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-premium hover-action metric-card">
            <div class="metric-data">
                <span class="metric-label">Today's Queue</span>
                <div class="metric-value"><?= number_format($stats['queue_now']) ?></div>
            </div>
            <div class="metric-icon-box warning">
                <i class="bi bi-ticket-perforated-fill"></i>
            </div>
        </div>
    </div>

    <!-- Completed Visits Today Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-premium hover-action metric-card">
            <div class="metric-data">
                <span class="metric-label">Visits Completed</span>
                <div class="metric-value"><?= number_format($stats['today_visits']) ?></div>
            </div>
            <div class="metric-icon-box success">
                <i class="bi bi-check-circle-fill"></i>
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
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-calendar-x d-block fs-3 mb-2 text-muted"></i>
                                        No appointments scheduled for today.
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
                                        <td class="ps-4 fw-semibold text-secondary" style="white-space: nowrap;">
                                            <?= date('h:i A', strtotime($appt['appointment_time'])) ?>
                                        </td>
                                        <td class="fw-bold">
                                            <a href="<?= url('/patients/' . $appt['patient_id']) ?>" class="link-primary-dark">
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
            <div class="card-body py-4">
                <div class="text-center py-4">
                    <div class="display-3 fw-bold text-primary mb-2"><?= $queueStats['serving_no'] ? sprintf('%03d', $queueStats['serving_no']) : '000' ?></div>
                    <div class="text-muted small uppercase fw-semibold tracking-wider">Current Queue Number</div>
                </div>
                
                <hr class="my-3 text-muted opacity-25">
                
                <div class="d-flex justify-content-around text-center mt-3">
                    <div>
                        <div class="fs-4 fw-bold text-warning"><?= number_format($queueStats['waiting']) ?></div>
                        <div class="text-muted small">Waiting</div>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-primary"><?= number_format($queueStats['called']) ?></div>
                        <div class="text-muted small">Called</div>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-success"><?= number_format($queueStats['completed']) ?></div>
                        <div class="text-muted small">Completed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
