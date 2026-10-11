<?php
$title = 'Appointments';
require dirname(__DIR__) . '/layout/header.php';

$todayMetrics = $todayMetrics ?? [
    'total_today' => 0,
    'scheduled_today' => 0,
    'completed_today' => 0,
    'missed_cancelled_today' => 0
];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-primary-dark" style="text-wrap: balance;">Appointment Directory</h1>
        <p class="text-secondary small mb-0" style="text-wrap: pretty;">Manage and filter upcoming visits, schedule follow-ups, and update booking status.</p>
    </div>
    <a href="<?= url('/appointments/create') ?>" class="btn btn-primary d-flex align-items-center py-2 px-3 shadow-xs fw-semibold">
        <i class="bi bi-calendar-plus me-1" aria-hidden="true"></i> Schedule Appointment
    </a>
</div>

<!-- Daily Triage KPI Summary Cards -->
<div class="row g-3 mb-4" id="dailyTriageKpiCards">
    <div class="col-6 col-lg-3">
        <div class="card card-premium shadow-sm h-100 border">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Today's Visits</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1" style="font-variant-numeric: tabular-nums;"><?= (int)$todayMetrics['total_today'] ?></div>
                    </div>
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;" aria-hidden="true">
                        <i class="bi bi-calendar2-day fs-5"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                    <span class="fw-semibold text-primary" style="font-variant-numeric: tabular-nums;"><?= date('M d, Y') ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-premium shadow-sm h-100 border">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pending / Scheduled</div>
                        <div class="h3 mb-0 fw-bold text-info-emphasis mt-1" style="font-variant-numeric: tabular-nums;"><?= (int)$todayMetrics['scheduled_today'] ?></div>
                    </div>
                    <div class="bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;" aria-hidden="true">
                        <i class="bi bi-hourglass-split fs-5"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                    <span>Awaiting triage / check-in</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-premium shadow-sm h-100 border">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Completed Today</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1" style="font-variant-numeric: tabular-nums;"><?= (int)$todayMetrics['completed_today'] ?></div>
                    </div>
                    <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;" aria-hidden="true">
                        <i class="bi bi-check-circle fs-5"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                    <span>Successfully served</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-premium shadow-sm h-100 border">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Missed / Cancelled</div>
                        <div class="h3 mb-0 fw-bold text-secondary mt-1" style="font-variant-numeric: tabular-nums;"><?= (int)$todayMetrics['missed_cancelled_today'] ?></div>
                    </div>
                    <div class="bg-light text-secondary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;" aria-hidden="true">
                        <i class="bi bi-x-circle fs-5"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                    <span>Require follow-up</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-4">
        <!-- Quick Date Filter Chips -->
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-3 border-bottom">
            <span class="small fw-semibold text-muted me-1">Quick Filters:</span>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="all">
                All Dates
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="today">
                Today
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="tomorrow">
                Tomorrow
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="this_week">
                Next 7 Days
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="pending_today">
                Pending Today
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn text-warning-emphasis" data-preset="overdue">
                <i class="bi bi-exclamation-triangle-fill me-1 text-warning" aria-hidden="true"></i>Overdue<?= !empty($todayMetrics['overdue_count']) ? ' <span class="badge bg-danger ms-1" style="font-variant-numeric: tabular-nums;">' . $todayMetrics['overdue_count'] . '</span>' : '' ?>
            </button>
        </div>

        <form action="<?= url('/appointments') ?>" method="GET" class="row g-3 align-items-end" id="filtersForm">
            <!-- Search Keyword -->
            <div class="col-12 col-md-3">
                <label for="search" class="form-label fw-semibold text-secondary small">Search Patient</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0" aria-hidden="true"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           class="form-control bg-light border-start-0" 
                           placeholder="Name or Patient No…" 
                           autocomplete="off"
                           spellcheck="false"
                           inputmode="search"
                           value="<?= h($filters['search']) ?>">
                </div>
            </div>

            <!-- Date From -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="date_from" class="form-label fw-semibold text-secondary small">Scheduled Date From</label>
                <input type="date" 
                       name="date_from" 
                       id="date_from" 
                       class="form-control bg-light" 
                       value="<?= h($filters['date_from']) ?>">
            </div>

            <!-- Date To -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="date_to" class="form-label fw-semibold text-secondary small">Scheduled Date To</label>
                <input type="date" 
                       name="date_to" 
                       id="date_to" 
                       class="form-control bg-light" 
                       value="<?= h($filters['date_to']) ?>">
            </div>

            <!-- Category Filter -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="program_type" class="form-label fw-semibold text-secondary small">Category</label>
                <select name="program_type" id="program_type" class="form-select bg-light">
                    <option value="">-- All Categories --</option>
                    <option value="General OPD" <?= ($filters['program_type'] ?? '') === 'General OPD' ? 'selected' : '' ?>>General OPD</option>
                    <option value="Maternal Care" <?= ($filters['program_type'] ?? '') === 'Maternal Care' ? 'selected' : '' ?>>Maternal Care</option>
                    <option value="Well-Baby Care" <?= ($filters['program_type'] ?? '') === 'Well-Baby Care' ? 'selected' : '' ?>>Well-Baby Care</option>
                    <option value="Senior Care" <?= ($filters['program_type'] ?? '') === 'Senior Care' ? 'selected' : '' ?>>Senior Care</option>
                    <option value="Family Planning" <?= ($filters['program_type'] ?? '') === 'Family Planning' ? 'selected' : '' ?>>Family Planning</option>
                    <option value="NCD / Hypertension" <?= ($filters['program_type'] ?? '') === 'NCD / Hypertension' ? 'selected' : '' ?>>NCD / Hypertension</option>
                    <option value="Dental Care" <?= ($filters['program_type'] ?? '') === 'Dental Care' ? 'selected' : '' ?>>Dental Care</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="status" class="form-label fw-semibold text-secondary small">Status</label>
                <select name="status" id="status" class="form-select bg-light">
                    <option value="">-- All Statuses --</option>
                    <option value="Scheduled" <?= $filters['status'] === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="Completed" <?= $filters['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= $filters['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="Missed" <?= $filters['status'] === 'Missed' ? 'selected' : '' ?>>Missed</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-sm-6 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary flex-grow-1 d-inline-flex align-items-center justify-content-center" title="Filter Records" aria-label="Filter records" style="min-width: 38px; min-height: 38px;">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                </button>
                <a href="<?= url('/appointments') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Clear Filters" aria-label="Clear filters" style="min-width: 38px; min-height: 38px;">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Appointments List Table -->
<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="appointmentsTable">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-start ps-4">Scheduled Date</th>
                        <th scope="col" class="text-center">Time</th>
                        <th scope="col" class="text-center">Category</th>
                        <th scope="col" class="text-start">Patient Name</th>
                        <th scope="col" class="text-start">Purpose</th>
                        <th scope="col" class="text-center">Status</th>
                        <th scope="col" class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x d-block fs-3 mb-2 text-muted" aria-hidden="true"></i>
                                No appointments found matching the selected filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $todayDateStr = date('Y-m-d');
                        foreach ($appointments as $a): 
                            $isPastDue = ($a['status'] === 'Scheduled' && $a['appointment_date'] < $todayDateStr);
                            $daysPastDue = 0;
                            if ($isPastDue) {
                                $diffSec = strtotime($todayDateStr) - strtotime($a['appointment_date']);
                                $daysPastDue = max(1, (int)round($diffSec / 86400));
                            }

                            // Format status badges with accessible contrast
                            $badgeClass = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle';
                            if ($isPastDue) {
                                $badgeClass = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                            } elseif ($a['status'] === 'Scheduled') {
                                $badgeClass = 'badge bg-info-subtle text-info-emphasis border border-info-subtle';
                            } elseif ($a['status'] === 'Completed') {
                                $badgeClass = 'badge bg-success-subtle text-success-emphasis border border-success-subtle';
                            } elseif ($a['status'] === 'Cancelled') {
                                $badgeClass = 'badge bg-danger-subtle text-danger-emphasis border border-danger-subtle';
                            } elseif ($a['status'] === 'Missed') {
                                $badgeClass = 'badge bg-dark-subtle text-dark-emphasis border border-dark-subtle';
                            }

                            // Format category badges with high contrast tokens
                            $prog = $a['program_type'] ?? 'General OPD';
                            $catClass = 'badge-category-default';
                            $catIcon = 'bi-folder';

                            if ($prog === 'Prenatal Care' || $prog === 'Maternal Care') {
                                $catClass = 'badge-category-maternal';
                                $catIcon = 'bi-heart-pulse-fill';
                            } elseif ($prog === 'Well Baby Immunization' || $prog === 'Well-Baby Care') {
                                $catClass = 'badge-category-wellbaby';
                                $catIcon = 'bi-balloon-fill';
                            } elseif ($prog === 'General OPD' || $prog === 'Consultation') {
                                $catClass = 'badge-category-opd';
                                $catIcon = 'bi-clipboard2-pulse';
                            } elseif ($prog === 'Senior Care') {
                                $catClass = 'badge-category-senior';
                                $catIcon = 'bi-person-heart';
                            } elseif ($prog === 'NCD / Hypertension') {
                                $catClass = 'badge-category-ncd';
                                $catIcon = 'bi-activity';
                            } elseif ($prog === 'Family Planning') {
                                $catClass = 'badge-category-family-planning';
                                $catIcon = 'bi-people-fill';
                            } elseif ($prog === 'Dental Care') {
                                $catClass = 'badge-category-dental';
                                $catIcon = 'bi-emoji-smile-fill';
                            }
                        ?>
                            <tr>
                                <td class="text-start ps-4 <?= $isPastDue ? 'text-warning-emphasis' : 'text-secondary' ?> fw-semibold" data-order="<?= h($a['appointment_date']) ?>" style="white-space: nowrap; font-variant-numeric: tabular-nums;">
                                     <?= date('M d, Y', strtotime($a['appointment_date'])) ?>
                                </td>
                                <td class="fw-semibold text-dark text-center" data-order="<?= date('H:i:s', strtotime($a['appointment_time'])) ?>" style="white-space: nowrap; font-variant-numeric: tabular-nums;">
                                     <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge-category-pill <?= $catClass ?>">
                                        <?= h($prog) ?>
                                    </span>
                                </td>
                                <td class="text-start text-dark fw-bold">
                                    <a href="<?= url('/patients/' . $a['patient_id']) ?>" class="link-primary-dark text-decoration-none">
                                        <?= h($a['patient_last']) ?>, <?= h($a['patient_first']) ?> 
                                        <span class="text-muted fw-normal font-monospace fs-7" style="font-variant-numeric: tabular-nums;">(<?= h($a['patient_no']) ?>)</span>
                                    </a>
                                </td>
                                <td class="text-start text-truncate text-secondary" style="max-width: 220px;" title="<?= h($a['purpose']) ?>">
                                    <?= h($a['purpose']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isPastDue): ?>
                                        <span class="<?= $badgeClass ?> px-2 py-1" title="Scheduled date was <?= date('M d, Y', strtotime($a['appointment_date'])) ?> (<?= $daysPastDue ?> days ago)" style="font-variant-numeric: tabular-nums;">
                                            <i class="bi bi-exclamation-triangle-fill me-1 text-warning" aria-hidden="true"></i>Past Due (<?= $daysPastDue ?>d ago)
                                        </span>
                                    <?php else: ?>
                                        <span class="<?= $badgeClass ?> px-2 py-1"><?= h($a['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end" style="white-space: nowrap;">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-secondary px-2.5 py-1 dropdown-toggle d-inline-flex align-items-center justify-content-center shadow-2xs" 
                                                type="button" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false" 
                                                aria-label="Actions for appointment with <?= h($a['patient_first'] . ' ' . $a['patient_last']) ?>"
                                                title="Appointment actions"
                                                style="min-width: 86px;">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" style="min-width: 220px;">
                                            <?php if ($isPastDue): ?>
                                                <!-- Past Due Scheduled Appointment -->
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Past Due Actions</h6>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="dropdown-item small text-warning-emphasis fw-medium d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-calendar-event text-warning fs-6" aria-hidden="true"></i>
                                                        <span>Reschedule for New Date</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Missed">
                                                        <button type="submit" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Mark appointment as Missed? This flags the patient as a no-show for this past date.">
                                                            <i class="bi bi-clock-history text-secondary fs-6" aria-hidden="true"></i>
                                                            <span>Mark as Missed (No-Show)</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Completed">
                                                        <button type="submit" class="dropdown-item small text-success d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Mark this past appointment as Completed?">
                                                            <i class="bi bi-check2-circle text-success fs-6" aria-hidden="true"></i>
                                                            <span>Mark as Completed (Late)</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Cancelled">
                                                        <button type="submit" class="dropdown-item small text-danger d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Are you sure you want to Cancel this appointment?">
                                                            <i class="bi bi-x-circle text-danger fs-6" aria-hidden="true"></i>
                                                            <span>Cancel Appointment</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Patient Record</h6>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/patients/' . $a['patient_id']) ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-folder2-open text-primary fs-6" aria-hidden="true"></i>
                                                        <span>Open Patient Chart</span>
                                                    </a>
                                                </li>

                                            <?php elseif ($a['status'] === 'Scheduled'): ?>
                                                <!-- Active Scheduled Appointment -->
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Encounter Actions</h6>
                                                </li>
                                                <li>
                                                    <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Completed">
                                                        <button type="submit" class="dropdown-item small text-success fw-medium d-flex align-items-center gap-2 py-2 action-confirm-btn" title="Complete appointment" data-confirm="Mark this appointment for <?= h($a['patient_first'] . ' ' . $a['patient_last']) ?> as Completed?">
                                                            <i class="bi bi-check2-circle text-success fs-6" aria-hidden="true"></i>
                                                            <span>Mark as Completed</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-pencil-square text-primary fs-6" aria-hidden="true"></i>
                                                        <span>Reschedule / Edit</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/queue?patient_id=' . $a['patient_id']) ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-ticket-perforated text-info fs-6" aria-hidden="true"></i>
                                                        <span>Check In to Queue</span>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Exceptions</h6>
                                                </li>
                                                <li>
                                                    <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Missed">
                                                        <button type="submit" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Mark appointment as Missed? This flags the patient as a no-show for this consultation date.">
                                                            <i class="bi bi-clock-history text-secondary fs-6" aria-hidden="true"></i>
                                                            <span>Mark as Missed (No-Show)</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Cancelled">
                                                        <button type="submit" class="dropdown-item small text-danger d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Are you sure you want to Cancel this appointment? Cancelled appointments cannot be restored directly.">
                                                            <i class="bi bi-x-circle text-danger fs-6" aria-hidden="true"></i>
                                                            <span>Cancel Appointment</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Patient Record</h6>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/patients/' . $a['patient_id']) ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-folder2-open text-primary fs-6" aria-hidden="true"></i>
                                                        <span>Open Patient Chart</span>
                                                    </a>
                                                </li>

                                            <?php elseif ($a['status'] === 'Completed'): ?>
                                                <!-- Completed Appointment -->
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Completed Encounter</h6>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-file-earmark-text text-primary fs-6" aria-hidden="true"></i>
                                                        <span>View / Edit Details</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/appointments/create?patient_id=' . $a['patient_id']) ?>" class="dropdown-item small text-success d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-calendar-plus text-success fs-6" aria-hidden="true"></i>
                                                        <span>Book Follow-up</span>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Patient Record</h6>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/patients/' . $a['patient_id']) ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-folder2-open text-primary fs-6" aria-hidden="true"></i>
                                                        <span>Open Patient Chart</span>
                                                    </a>
                                                </li>

                                            <?php elseif ($a['status'] === 'Missed'): ?>
                                                <!-- Missed Appointment -->
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Missed Appointment</h6>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/appointments/create?patient_id=' . $a['patient_id']) ?>" class="dropdown-item small text-primary fw-medium d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-calendar-plus text-primary fs-6" aria-hidden="true"></i>
                                                        <span>Reschedule (Book New)</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-pencil-square text-secondary fs-6" aria-hidden="true"></i>
                                                        <span>View / Edit Record</span>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Patient Record</h6>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/patients/' . $a['patient_id']) ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-folder2-open text-primary fs-6" aria-hidden="true"></i>
                                                        <span>Open Patient Chart</span>
                                                    </a>
                                                </li>

                                            <?php else: ?>
                                                <!-- Cancelled Appointment -->
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Cancelled Appointment</h6>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/appointments/create?patient_id=' . $a['patient_id']) ?>" class="dropdown-item small text-primary fw-medium d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-calendar-plus text-primary fs-6" aria-hidden="true"></i>
                                                        <span>Book New Appointment</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-file-earmark-text text-secondary fs-6" aria-hidden="true"></i>
                                                        <span>View / Edit Record</span>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-secondary small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Patient Record</h6>
                                                </li>
                                                <li>
                                                    <a href="<?= url('/patients/' . $a['patient_id']) ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                        <i class="bi bi-folder2-open text-primary fs-6" aria-hidden="true"></i>
                                                        <span>Open Patient Chart</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <?php if (is_admin()): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <h6 class="dropdown-header text-uppercase text-danger small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Administration</h6>
                                                </li>
                                                <li>
                                                    <button type="button" 
                                                            class="dropdown-item small text-danger d-flex align-items-center gap-2 py-2 btn-void-appointment" 
                                                            data-id="<?= $a['id'] ?>" 
                                                            data-patient="<?= h($a['patient_first'] . ' ' . $a['patient_last']) ?>"
                                                            data-date="<?= date('M d, Y', strtotime($a['appointment_date'])) . ' ' . date('h:i A', strtotime($a['appointment_time'])) ?>">
                                                        <i class="bi bi-trash3 text-danger fs-6" aria-hidden="true"></i>
                                                        <span>Void (Booked in Error)</span>
                                                    </button>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
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

<!-- Hidden Form for Voiding Appointment -->
<form id="voidAppointmentGlobalForm" method="POST" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="void_reason" id="voidReasonInput" value="">
</form>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtersForm = document.getElementById('filtersForm');
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    const statusSelect = document.getElementById('status');
    const quickButtons = document.querySelectorAll('.quick-dir-btn');

    // Format YYYY-MM-DD
    function formatDateStr(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    const todayDate = new Date();
    const todayStr = formatDateStr(todayDate);

    const yestDate = new Date();
    yestDate.setDate(yestDate.getDate() - 1);
    const yestStr = formatDateStr(yestDate);

    const tomDate = new Date();
    tomDate.setDate(tomDate.getDate() + 1);
    const tomStr = formatDateStr(tomDate);

    const weekDate = new Date();
    weekDate.setDate(weekDate.getDate() + 7);
    const weekStr = formatDateStr(weekDate);

    // Highlight active quick filter
    const curFrom = dateFromInput ? dateFromInput.value : '';
    const curTo = dateToInput ? dateToInput.value : '';
    const curStatus = statusSelect ? statusSelect.value : '';

    quickButtons.forEach(btn => {
        const preset = btn.dataset.preset;
        let isActive = false;
        if (preset === 'all' && !curFrom && !curTo && !curStatus) {
            isActive = true;
        } else if (preset === 'today' && curFrom === todayStr && curTo === todayStr && !curStatus) {
            isActive = true;
        } else if (preset === 'tomorrow' && curFrom === tomStr && curTo === tomStr && !curStatus) {
            isActive = true;
        } else if (preset === 'this_week' && curFrom === todayStr && curTo === weekStr && !curStatus) {
            isActive = true;
        } else if (preset === 'pending_today' && curFrom === todayStr && curTo === todayStr && curStatus === 'Scheduled') {
            isActive = true;
        } else if (preset === 'overdue' && !curFrom && curTo && curTo <= yestStr && curStatus === 'Scheduled') {
            isActive = true;
        }

        if (isActive) {
            btn.classList.remove('btn-light', 'border');
            if (preset === 'overdue') {
                btn.classList.add('btn-warning', 'text-dark', 'fw-semibold');
            } else {
                btn.classList.add('btn-primary', 'text-white', 'fw-semibold');
            }
        }

        btn.addEventListener('click', function() {
            if (preset === 'all') {
                if (dateFromInput) dateFromInput.value = '';
                if (dateToInput) dateToInput.value = '';
                if (statusSelect) statusSelect.value = '';
            } else if (preset === 'today') {
                if (dateFromInput) dateFromInput.value = todayStr;
                if (dateToInput) dateToInput.value = todayStr;
                if (statusSelect) statusSelect.value = '';
            } else if (preset === 'tomorrow') {
                if (dateFromInput) dateFromInput.value = tomStr;
                if (dateToInput) dateToInput.value = tomStr;
                if (statusSelect) statusSelect.value = '';
            } else if (preset === 'this_week') {
                if (dateFromInput) dateFromInput.value = todayStr;
                if (dateToInput) dateToInput.value = weekStr;
                if (statusSelect) statusSelect.value = '';
            } else if (preset === 'pending_today') {
                if (dateFromInput) dateFromInput.value = todayStr;
                if (dateToInput) dateToInput.value = todayStr;
                if (statusSelect) statusSelect.value = 'Scheduled';
            } else if (preset === 'overdue') {
                if (dateFromInput) dateFromInput.value = '';
                if (dateToInput) dateToInput.value = yestStr;
                if (statusSelect) statusSelect.value = 'Scheduled';
            }
            if (filtersForm) {
                filtersForm.submit();
            }
        });
    });

    // Delegated confirmation handling for table actions (persists across DataTables pagination)
    document.addEventListener('click', function(e) {
        const confirmBtn = e.target.closest('.action-confirm-btn, [data-confirm]');
        if (!confirmBtn) return;

        // If inside appointmentsTable or has data-confirm
        if (confirmBtn.closest('#appointmentsTable')) {
            e.preventDefault();
            e.stopPropagation();

            const message = confirmBtn.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            const form = confirmBtn.closest('form');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Confirm Action',
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0D7377',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, proceed',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (form) form.submit();
                        else if (confirmBtn.href) window.location.href = confirmBtn.href;
                    }
                });
            } else {
                if (confirm(message)) {
                    if (form) form.submit();
                    else if (confirmBtn.href) window.location.href = confirmBtn.href;
                }
            }
        }
    }, true);

    // Admin Void Appointment Confirmation with SweetAlert
    $(document).on('click', '.btn-void-appointment', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const apptId = $(this).attr('data-id');
        const patient = $(this).attr('data-patient') || 'this patient';
        const date = $(this).attr('data-date') || 'this date';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Void Appointment Record?',
                html: `
                    <div class="text-start small">
                        <p class="text-secondary mb-2">
                            You are about to void the appointment for <strong>${patient}</strong> scheduled on <strong>${date}</strong>.
                        </p>
                        <div class="alert alert-warning py-2 px-3 small border-0 mb-3" style="background-color: #fffbeb; color: #b45309;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <strong>Administrative Void:</strong> This removes the appointment from active schedules and capacity counters without penalizing the patient's record.
                        </div>
                        <label class="form-label fw-semibold text-secondary mb-1">Reason for Voiding:</label>
                        <select id="swalVoidReason" class="form-select form-select-sm mb-2">
                            <option value="Created in error / wrong patient selected" selected>Created in error / wrong patient selected</option>
                            <option value="Duplicate booking entry">Duplicate booking entry</option>
                            <option value="Test / system training entry">Test / system training entry</option>
                            <option value="Other administrative mistake">Other administrative mistake</option>
                        </select>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Void Appointment',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const reasonSelect = document.getElementById('swalVoidReason');
                    const reason = reasonSelect ? reasonSelect.value : 'Created in error';
                    const form = document.getElementById('voidAppointmentGlobalForm');
                    if (form) {
                        form.action = `<?= url('/appointments/') ?>${apptId}/void`;
                        document.getElementById('voidReasonInput').value = reason;
                        form.submit();
                    }
                }
            });
        } else {
            if (confirm(`Void appointment #${apptId} for ${patient}?`)) {
                const form = document.getElementById('voidAppointmentGlobalForm');
                if (form) {
                    form.action = `<?= url('/appointments/') ?>${apptId}/void`;
                    document.getElementById('voidReasonInput').value = 'Voided by administrator (Created in error)';
                    form.submit();
                }
            }
        }
    });

    // Initialize DataTable
    <?php if (!empty($appointments)): ?>
        $('#appointmentsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": false, // Handled by custom filter card
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "asc"], [1, "asc"]], // Order by date then time ascending
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Disable sorting on action column (index 6)
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>
});
</script>
