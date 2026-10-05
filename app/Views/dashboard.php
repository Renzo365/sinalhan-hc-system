<?php
$title = 'Dashboard';
require __DIR__ . '/layout/header.php';
?>

<div class="mb-4">
    <h2 class="h3 mb-1 fw-bold text-primary-dark">Dashboard</h2>
    <p class="text-secondary small mb-0">Welcome back, <strong class="text-dark"><?= h($userFullName ?? 'Staff') ?></strong>. Daily health center summary, active queue counts, and today's appointments schedule.</p>
</div>

<?php
$trafficToday = (int)($stats['today_traffic'] ?? 0);
$apptsTotal = (int)($apptStats['total_today'] ?? 0);
$apptsCompleted = (int)($apptStats['completed_today'] ?? 0);
$apptsPct = $apptsTotal > 0 ? round(($apptsCompleted / $apptsTotal) * 100) : 0;
$waitingLobby = (int)($queueStats['waiting'] ?? 0);
$totalPatients = (int)($censusMetrics['total_patients'] ?? $stats['total_patients']);
$phicCovered = (int)($censusMetrics['phic_covered'] ?? 0);
$phicPct = $totalPatients > 0 ? round(($phicCovered / $totalPatients) * 100, 1) : 0;
$households = (int)($censusMetrics['households'] ?? 0);
?>

<!-- Operational KPI Summary Cards -->
<div class="row g-3 mb-4" id="dashboardKpis">
    <!-- 1. Patient Traffic Today -->
    <div class="col-6 col-xl-3">
        <a href="<?= url('/queue') ?>" class="text-decoration-none">
            <div class="card card-premium shadow-sm h-100 border" style="transition: transform 0.15s ease, box-shadow 0.15s ease;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Traffic Today</div>
                            <div class="h3 mb-0 fw-bold text-primary-dark mt-1 font-monospace">
                                <?= number_format($trafficToday) ?>
                            </div>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-people-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <span><strong class="text-dark"><?= number_format($apptsCompleted) ?></strong> Appts &bull; <strong class="text-dark"><?= number_format($stats['today_visits'] ?? 0) ?></strong> Walk-ins</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 2. Today's Appointments -->
    <div class="col-6 col-xl-3">
        <a href="<?= url('/appointments') ?>" class="text-decoration-none">
            <div class="card card-premium shadow-sm h-100 border" style="transition: transform 0.15s ease, box-shadow 0.15s ease;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Appointments</div>
                            <div class="h3 mb-0 fw-bold text-info-emphasis mt-1 font-monospace">
                                <?= number_format($apptsTotal) ?>
                            </div>
                        </div>
                        <div class="bg-info-subtle text-info-emphasis rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-calendar2-check-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <span><strong class="text-dark"><?= $apptsCompleted ?></strong> Completed (<?= $apptsPct ?>% attendance)</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 3. Active Queue in Lobby -->
    <div class="col-6 col-xl-3">
        <a href="<?= url('/queue') ?>" class="text-decoration-none">
            <div class="card card-premium shadow-sm h-100 border" style="transition: transform 0.15s ease, box-shadow 0.15s ease;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Waiting in Lobby</div>
                            <div class="h3 mb-0 fw-bold text-warning-emphasis mt-1 font-monospace">
                                <?= number_format($waitingLobby) ?>
                            </div>
                        </div>
                        <div class="bg-warning-subtle text-warning-emphasis rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-ticket-perforated-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <span>Now Serving: <strong class="text-dark font-monospace"><?= !empty($queueStats['serving_no']) ? sprintf('%03d', $queueStats['serving_no']) : '--' ?></strong></span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 4. Community Census & PhilHealth -->
    <div class="col-6 col-xl-3">
        <a href="<?= url('/patients') ?>" class="text-decoration-none">
            <div class="card card-premium shadow-sm h-100 border" style="transition: transform 0.15s ease, box-shadow 0.15s ease;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Community Census</div>
                            <div class="h3 mb-0 fw-bold text-success mt-1 font-monospace">
                                <?= number_format($totalPatients) ?>
                            </div>
                        </div>
                        <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <span><strong class="text-dark"><?= $phicPct ?>%</strong> PhilHealth &bull; <strong class="text-dark"><?= number_format($households) ?></strong> families</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Today's Appointments Section -->
    <div class="col-12 col-lg-7">
        <div class="card card-premium h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-calendar3 me-2 text-primary"></i>Today's Scheduled Appointments
                </h3>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Today</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 95px;">Time</th>
                                <th style="min-width: 175px;">Patient Details</th>
                                <th>Program &amp; Purpose</th>
                                <th style="width: 110px;">Status</th>
                                <th class="pe-4 text-end" style="width: 115px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($todayAppointments)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-check fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <div class="fw-semibold text-dark mb-1">No appointments scheduled for today</div>
                                        <p class="small text-secondary mb-3">All clear! You can book new appointments in the calendar module.</p>
                                        <a href="<?= url('/appointments/create') ?>" class="btn btn-sm btn-outline-primary px-3">
                                            <i class="bi bi-calendar-plus me-1"></i> Book New Appointment
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($todayAppointments as $appt): 
                                    $prog = $appt['program_type'] ?? 'General OPD';
                                    $serviceVal = 'General OPD';
                                    if (in_array($prog, ['Maternal Care', 'Prenatal Care'])) $serviceVal = 'Prenatal Care';
                                    elseif (in_array($prog, ['Well-Baby Care', 'Well Baby Immunization'])) $serviceVal = 'Well Baby Immunization';
                                    elseif ($prog === 'Senior Care') $serviceVal = 'Senior Care';
                                    elseif ($prog === 'Dental Care') $serviceVal = 'Dental Care';
                                    elseif ($prog === 'Family Planning') $serviceVal = 'Family Planning';
                                    elseif ($prog === 'NCD / Hypertension') $serviceVal = 'NCD / Hypertension';

                                    $isEnqueued = isset($enqueuedMap[$appt['patient_id']]);
                                    $qNo = $isEnqueued ? $enqueuedMap[$appt['patient_id']] : null;
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-secondary font-monospace small" style="white-space: nowrap;">
                                            <?= date('h:i A', strtotime($appt['appointment_time'])) ?>
                                        </td>
                                        <td>
                                            <div>
                                                <a href="<?= url('/patients/' . $appt['patient_id']) ?>" class="link-primary-dark fw-bold text-decoration-none">
                                                    <?= h($appt['patient_last']) ?>, <?= h($appt['patient_first']) ?>
                                                </a>
                                            </div>
                                            <div class="text-muted small d-flex align-items-center gap-1.5 mt-0.5" style="font-size: 0.75rem; white-space: nowrap;">
                                                <a href="<?= url('/patients/' . $appt['patient_id']) ?>" class="font-monospace text-secondary text-decoration-none fw-semibold" title="View Patient Record">
                                                    <?= h($appt['patient_no']) ?>
                                                </a>
                                                <?php if (!empty($appt['contact_no'])): ?>
                                                    <span>&bull;</span>
                                                    <span><?= h($appt['contact_no']) ?></span>
                                                <?php else: ?>
                                                    <span>&bull;</span>
                                                    <span>Brgy. Sinalhan</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <span class="badge bg-light text-primary border" style="font-size: 0.72rem;">
                                                    <?= h($serviceVal) ?>
                                                </span>
                                                <span class="text-secondary small text-truncate d-inline-block align-middle" style="max-width: 140px;" title="<?= h($appt['purpose'] ?? '') ?>">
                                                    <?= h($appt['purpose'] ?? 'Clinical Visit') ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($appt['status'] === 'Scheduled'): ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                                    Scheduled
                                                </span>
                                            <?php elseif ($appt['status'] === 'Completed'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    Completed
                                                </span>
                                            <?php elseif ($appt['status'] === 'Cancelled'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                    Cancelled
                                                </span>
                                            <?php elseif ($appt['status'] === 'Missed'): ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                    Missed
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= h($appt['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <?php if ($isEnqueued): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1.5 px-2" title="Patient is already enqueued today">
                                                    Queue #<?= $qNo ?>
                                                </span>
                                            <?php elseif ($appt['status'] === 'Scheduled'): ?>
                                                <form action="<?= url('/queue') ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="patient_id" value="<?= (int)$appt['patient_id'] ?>">
                                                    <input type="hidden" name="service_type" value="<?= h($serviceVal) ?>">
                                                    <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                                                    <input type="hidden" name="redirect_to" value="<?= url('/dashboard') ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2.5 d-inline-flex align-items-center shadow-xs" title="Issue Queue Ticket for <?= h($appt['patient_first'] . ' ' . $appt['patient_last']) ?>">
                                                        <span>Check In</span>
                                                    </button>
                                                </form>
                                            <?php elseif ($appt['status'] === 'Completed'): ?>
                                                <span class="badge bg-light text-secondary border py-1.5 px-2">
                                                    Fulfilled
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">&mdash;</span>
                                            <?php endif; ?>
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
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Active</span>
            </div>
            <div class="card-body py-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="text-center py-3">
                        <div class="display-3 fw-bold text-primary mb-1 font-monospace"><?= !empty($queueStats['serving_no']) ? sprintf('%03d', $queueStats['serving_no']) : '000' ?></div>
                        <div class="text-muted small text-uppercase fw-semibold tracking-wider">Current Queue Number</div>
                        <?php if (!empty($queueStats['serving_service'])): ?>
                            <div class="mt-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">
                                    <i class="bi bi-hospital me-1"></i><?= h($queueStats['serving_service']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
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

                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <a href="<?= url('/queue') ?>" class="btn btn-primary fw-semibold flex-grow-1 py-2 d-inline-flex align-items-center justify-content-center shadow-sm">
                        <i class="bi bi-card-checklist me-2"></i> Manage Live Queue
                    </a>
                    <a href="<?= url('/queue/display') ?>" target="_blank" class="btn btn-outline-secondary fw-semibold py-2 px-3 d-inline-flex align-items-center justify-content-center" title="Open Public Display in New Tab">
                        <i class="bi bi-display me-1"></i> Display
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maternal Care & Child Health Radar (Row 4) -->
<div class="row g-4 mb-4" id="dashboardMchRadar">
    <!-- 1. Maternal Delivery Radar -->
    <div class="col-12 col-lg-6">
        <div class="card card-premium h-100 shadow-sm border">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-heart-pulse-fill fs-6"></i>
                    </div>
                    <div>
                        <h3 class="card-title h6 mb-0 fw-bold text-dark">Prenatal Delivery Radar</h3>
                        <div class="text-muted small" style="font-size: 0.72rem;">Active pregnancies &amp; EDC delivery countdown</div>
                    </div>
                </div>
                <a href="<?= url('/prenatal') ?>" class="btn btn-sm btn-outline-danger px-2.5 py-1 d-inline-flex align-items-center gap-1" title="Open Full Prenatal Registry">
                    <span class="small">Registry</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <!-- 3 Mini Counters -->
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="p-2.5 rounded-3 bg-light border text-center">
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Active Cases</div>
                                <div class="h4 mb-0 fw-bold text-danger mt-0.5 font-monospace">
                                    <?= number_format($maternalWatch['active_pregnancies'] ?? 0) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">Enrolled mothers</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2.5 rounded-3 bg-warning-subtle border border-warning-subtle text-center">
                                <div class="text-warning-emphasis small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Due This Month</div>
                                <div class="h4 mb-0 fw-bold text-warning-emphasis mt-0.5 font-monospace">
                                    <?= number_format($maternalWatch['due_this_month'] ?? 0) ?>
                                </div>
                                <div class="text-warning-emphasis" style="font-size: 0.7rem;">&le; 30 days left</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2.5 rounded-3 bg-light border text-center">
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Overdue Watch</div>
                                <div class="h4 mb-0 fw-bold text-dark mt-0.5 font-monospace">
                                    <?= number_format($maternalWatch['past_due'] ?? 0) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">Past EDC date</div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Deliveries Table / Feed -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            <i class="bi bi-clock-history me-1 text-danger"></i>Upcoming Deliveries (Next <?= count($upcomingDeliveries) ?> Due)
                        </span>
                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">EDC Tracking</span>
                    </div>

                    <?php if (empty($upcomingDeliveries)): ?>
                        <div class="text-center py-4 text-muted border rounded-3 bg-light-subtle">
                            <i class="bi bi-heart fs-3 d-block mb-1 text-secondary opacity-50"></i>
                            <div class="small fw-semibold text-dark">No upcoming deliveries scheduled</div>
                            <div class="small text-muted" style="font-size: 0.75rem;">All registered prenatal mothers are beyond the immediate delivery window.</div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                            <?php foreach ($upcomingDeliveries as $del): 
                                $daysLeft = (int)$del['days_to_delivery'];
                                $daysBadgeClass = 'bg-light text-secondary border';
                                $daysLabel = $daysLeft . ' days';
                                if ($daysLeft < 0) {
                                    $daysBadgeClass = 'bg-danger text-white';
                                    $daysLabel = abs($daysLeft) . 'd overdue';
                                } elseif ($daysLeft <= 7) {
                                    $daysBadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle fw-bold';
                                    $daysLabel = $daysLeft . 'd remaining';
                                } elseif ($daysLeft <= 30) {
                                    $daysBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold';
                                    $daysLabel = $daysLeft . 'd remaining';
                                }
                            ?>
                                <div class="list-group-item p-2.5 d-flex align-items-center justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <div>
                                            <a href="<?= url('/prenatal/' . $del['patient_id']) ?>" class="link-primary-dark fw-bold text-decoration-none small text-truncate d-inline-block" style="max-width: 220px;" title="View Prenatal Episode">
                                                <?= h($del['last_name']) ?>, <?= h($del['first_name']) ?>
                                            </a>
                                        </div>
                                        <div class="text-muted d-flex align-items-center gap-1.5 flex-wrap mt-0.5" style="font-size: 0.72rem;">
                                            <a href="<?= url('/patients/' . $del['patient_id']) ?>" class="font-monospace text-secondary text-decoration-none fw-semibold" title="View Patient Record">
                                                <?= h($del['patient_no']) ?>
                                            </a>
                                            <span>&bull;</span>
                                            <span>EDC: <strong><?= date('M d, Y', strtotime($del['edc'])) ?></strong></span>
                                            <?php if (!empty($del['contact_no'])): ?>
                                                <span>&bull;</span>
                                                <span><?= h($del['contact_no']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 text-end">
                                        <span class="badge <?= $daysBadgeClass ?>" style="font-size: 0.72rem;">
                                            <?= $daysLabel ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="pt-3 mt-3 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small" style="font-size: 0.75rem;">
                        <i class="bi bi-info-circle me-1 text-primary"></i>Calculated from Last Menstrual Period (LMP + 280d)
                    </span>
                    <a href="<?= url('/prenatal/register') ?>" class="btn btn-sm btn-link text-danger p-0 fw-semibold text-decoration-none" style="font-size: 0.75rem;">
                        <i class="bi bi-plus-circle me-0.5"></i>New Episode
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Child Health & Routine Immunization (EPI) -->
    <div class="col-12 col-lg-6">
        <div class="card card-premium h-100 shadow-sm border">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-shield-shaded fs-6"></i>
                    </div>
                    <div>
                        <h3 class="card-title h6 mb-0 fw-bold text-dark">Child Health &amp; Immunization (EPI)</h3>
                        <div class="text-muted small" style="font-size: 0.72rem;">Under-5 cohort census &amp; vaccine coverage</div>
                    </div>
                </div>
                <a href="<?= url('/well-baby') ?>" class="btn btn-sm btn-outline-primary px-2.5 py-1 d-inline-flex align-items-center gap-1" title="Open Full Well-Baby &amp; EPI Registry">
                    <span class="small">Registry</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <!-- 3 Mini Counters -->
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="p-2.5 rounded-3 bg-light border text-center">
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Under-5 Cohort</div>
                                <div class="h4 mb-0 fw-bold text-primary mt-0.5 font-monospace">
                                    <?= number_format($childHealth['total_under5'] ?? 0) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">Children 0-5 yrs</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2.5 rounded-3 bg-info-subtle border border-info-subtle text-center">
                                <div class="text-info-emphasis small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Infants (&lt; 1 yr)</div>
                                <div class="h4 mb-0 fw-bold text-info-emphasis mt-0.5 font-monospace">
                                    <?= number_format($childHealth['infants_under1'] ?? 0) ?>
                                </div>
                                <div class="text-info-emphasis" style="font-size: 0.7rem;">Primary EPI targets</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2.5 rounded-3 bg-success-subtle border border-success-subtle text-center">
                                <div class="text-success small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Doses Given</div>
                                <div class="h4 mb-0 fw-bold text-success mt-0.5 font-monospace">
                                    <?= number_format($childHealth['total_immunizations'] ?? 0) ?>
                                </div>
                                <div class="text-success" style="font-size: 0.7rem;">Vaccines logged</div>
                            </div>
                        </div>
                    </div>

                    <!-- EPI Program Focus Box -->
                    <div class="border rounded-3 p-3 bg-light-subtle mb-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-dark small fw-bold">
                                <i class="bi bi-eyedropper text-success me-1"></i>DOH Routine Vaccine Schedule
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">EPI Active</span>
                        </div>
                        <div class="text-muted small mb-2.5" style="font-size: 0.74rem;">
                            Target antigens for Barangay Sinalhan infants include: <strong>BCG</strong>, <strong>Hepatitis B</strong>, <strong>Pentavalent (DPT-HepB-HiB)</strong>, <strong>OPV / IPV</strong>, <strong>PCV</strong>, and <strong>Measles-Rubella (MMR)</strong>.
                        </div>
                        
                        <div class="row g-2 pt-2 border-top">
                            <div class="col-6">
                                <div class="lh-1">
                                    <div class="fw-bold text-dark small font-monospace"><?= number_format($childHealth['total_wellbaby'] ?? 0) ?> Folders</div>
                                    <span class="text-muted" style="font-size: 0.68rem;">Well-Baby Growth Logs</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="lh-1">
                                    <div class="fw-bold text-dark small font-monospace"><?= number_format($childHealth['infants_under1'] ?? 0) ?> Infants</div>
                                    <span class="text-muted" style="font-size: 0.68rem;">Under-1 Cohort Target</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-3 mt-3 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small" style="font-size: 0.75rem;">
                        <i class="bi bi-shield-check me-1 text-success"></i>DOH National Immunization Program (NIP) standards
                    </span>
                    <a href="<?= url('/well-baby/register') ?>" class="btn btn-sm btn-link text-primary p-0 fw-semibold text-decoration-none" style="font-size: 0.75rem;">
                        <i class="bi bi-plus-circle me-0.5"></i>Enroll Child
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 5: Recent Clinical Encounters Feed -->
<div class="row g-4 mt-1 mb-2">
    <div class="col-12">
        <div class="card card-premium shadow-sm border">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-journal-medical fs-6"></i>
                    </div>
                    <div>
                        <h3 class="card-title h6 mb-0 fw-bold text-dark">Recent Clinical Encounters</h3>
                        <div class="text-muted small" style="font-size: 0.72rem;">Latest outpatient consultations, chief complaints &amp; primary diagnoses</div>
                    </div>
                </div>
                <a href="<?= url('/patients') ?>" class="btn btn-sm btn-outline-primary px-2.5 py-1 d-inline-flex align-items-center gap-1" title="Open Full Patient Records Directory">
                    <span class="small">Patient Records</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th class="ps-4" style="width: 170px;">Date &amp; Time</th>
                                <th>Patient Details</th>
                                <th>Clinical Assessment &amp; Complaint</th>
                                <th>Attending Clinician</th>
                                <th class="pe-4 text-end" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentConsultations)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-journal-text fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <div class="fw-semibold text-dark mb-1">No clinical encounters recorded yet</div>
                                        <p class="small text-secondary mb-0">Consultations conducted will automatically appear in this operational log.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentConsultations as $enc): ?>
                                    <tr>
                                        <td class="ps-4" style="white-space: nowrap;">
                                            <div class="fw-semibold text-dark small">
                                                <?= date('M d, Y', strtotime($enc['consulted_at'])) ?>
                                            </div>
                                            <div class="text-muted small font-monospace mt-0.5" style="font-size: 0.72rem;">
                                                <?= date('h:i A', strtotime($enc['consulted_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <a href="<?= url('/patients/' . $enc['patient_id']) ?>" class="link-primary-dark fw-bold text-decoration-none">
                                                    <?= h($enc['last_name']) ?>, <?= h($enc['first_name']) ?>
                                                </a>
                                            </div>
                                            <div class="text-muted small d-flex align-items-center gap-1.5 mt-0.5" style="font-size: 0.75rem; white-space: nowrap;">
                                                <a href="<?= url('/patients/' . $enc['patient_id']) ?>" class="font-monospace text-secondary text-decoration-none fw-semibold" title="View Patient Profile">
                                                    <?= h($enc['patient_no']) ?>
                                                </a>
                                                <span>&bull;</span>
                                                <span><?= (int)$enc['age'] ?> yrs, <?= h($enc['sex'] ?? 'Unknown') ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark text-truncate" style="max-width: 380px;" title="<?= h($enc['assessment'] ?: 'Clinical Assessment Pending') ?>">
                                                <?= h($enc['assessment'] ?: 'Clinical Assessment Pending') ?>
                                            </div>
                                            <?php if (!empty($enc['subjective'])): ?>
                                                <div class="text-muted small text-truncate mt-0.5" style="max-width: 380px; font-size: 0.74rem;" title="Chief Complaint: <?= h($enc['subjective']) ?>">
                                                    <span class="text-secondary fw-semibold">Complaint:</span> <?= h($enc['subjective']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1.5 text-secondary">
                                                <span class="fw-medium text-dark small"><?= h($enc['clinician_name'] ?: 'Health Center Staff') ?></span>
                                            </div>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="<?= url('/patients/' . $enc['patient_id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2.5 d-inline-flex align-items-center shadow-xs" title="Open Patient Medical Chart">
                                                <span>View Chart</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light-subtle py-2.5 px-4 d-flex align-items-center justify-content-between border-top">
                <span class="text-muted small" style="font-size: 0.75rem;">
                    <i class="bi bi-info-circle me-1 text-secondary"></i>Showing 5 most recent outpatient consultations &bull; Synchronized with medical records
                </span>
                <a href="<?= url('/patients') ?>" class="btn btn-sm btn-link text-primary p-0 fw-semibold text-decoration-none" style="font-size: 0.75rem;">
                    Browse All Records <i class="bi bi-chevron-right ms-0.5"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>

