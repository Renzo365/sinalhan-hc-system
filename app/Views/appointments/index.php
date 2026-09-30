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
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Appointment Directory</h2>
        <p class="text-secondary small mb-0">Manage and filter upcoming visits, schedule follow-ups, and update booking status.</p>
    </div>
    <a href="<?= url('/appointments/create') ?>" class="btn btn-primary d-flex align-items-center py-2 px-3 shadow-xs">
        <i class="bi bi-calendar-plus me-2 fs-5"></i>
        <span class="fw-semibold">Schedule Appointment</span>
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
                        <div class="h3 mb-0 fw-bold text-dark mt-1"><?= (int)$todayMetrics['total_today'] ?></div>
                    </div>
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-calendar2-day fs-5"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2 d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                    <i class="bi bi-calendar-check text-primary"></i>
                    <span class="fw-semibold text-primary"><?= date('M d, Y') ?></span>
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
                        <div class="h3 mb-0 fw-bold text-info-emphasis mt-1"><?= (int)$todayMetrics['scheduled_today'] ?></div>
                    </div>
                    <div class="bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
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
                        <div class="h3 mb-0 fw-bold text-success mt-1"><?= (int)$todayMetrics['completed_today'] ?></div>
                    </div>
                    <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
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
                        <div class="h3 mb-0 fw-bold text-secondary mt-1"><?= (int)$todayMetrics['missed_cancelled_today'] ?></div>
                    </div>
                    <div class="bg-light text-secondary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
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
            <span class="small fw-semibold text-muted me-1"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Quick Filters:</span>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="all">
                <i class="bi bi-asterisk me-1 text-muted"></i>All Dates
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="today">
                <i class="bi bi-calendar-check me-1 text-primary"></i>Today
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="tomorrow">
                <i class="bi bi-calendar-plus me-1 text-info"></i>Tomorrow
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="this_week">
                <i class="bi bi-calendar-range me-1 text-secondary"></i>Next 7 Days
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn" data-preset="pending_today">
                <i class="bi bi-clock-history me-1 text-warning"></i>Pending Today
            </button>
            <button type="button" class="btn btn-sm btn-light border quick-dir-btn text-warning-emphasis" data-preset="overdue">
                <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>Overdue<?= !empty($todayMetrics['overdue_count']) ? ' <span class="badge bg-danger ms-1">' . $todayMetrics['overdue_count'] . '</span>' : '' ?>
            </button>
        </div>

        <form action="<?= url('/appointments') ?>" method="GET" class="row g-3 align-items-end" id="filtersForm">
            <!-- Search Keyword -->
            <div class="col-12 col-md-3">
                <label for="search" class="form-label fw-semibold text-secondary small">Search Patient</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           class="form-control bg-light border-start-0" 
                           placeholder="Name or Patient No..." 
                           value="<?= h($filters['search']) ?>">
                </div>
            </div>

            <!-- Date From -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="date_from" class="form-label fw-semibold text-secondary small">Scheduled Date From</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar3"></i></span>
                    <input type="date" 
                           name="date_from" 
                           id="date_from" 
                           class="form-control bg-light border-start-0" 
                           placeholder="YYYY-MM-DD" 
                           value="<?= h($filters['date_from']) ?>">
                </div>
            </div>

            <!-- Date To -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="date_to" class="form-label fw-semibold text-secondary small">Scheduled Date To</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar3"></i></span>
                    <input type="date" 
                           name="date_to" 
                           id="date_to" 
                           class="form-control bg-light border-start-0" 
                           placeholder="YYYY-MM-DD" 
                           value="<?= h($filters['date_to']) ?>">
                </div>
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
                <button type="submit" class="btn btn-primary flex-grow-1" title="Filter Records">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="<?= url('/appointments') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
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
                        <th class="text-start ps-4">Scheduled Date</th>
                        <th>Time</th>
                        <th>Category</th>
                        <th class="text-start">Patient Name</th>
                        <th class="text-start">Purpose</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x d-block fs-3 mb-2 text-muted"></i>
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
                                <td class="text-start ps-4 <?= $isPastDue ? 'text-warning-emphasis' : 'text-secondary' ?> fw-semibold" data-order="<?= h($a['appointment_date']) ?>" style="white-space: nowrap;">
                                    <i class="bi <?= $isPastDue ? 'bi-clock-history text-warning' : 'bi-calendar3 text-muted' ?> me-1"></i><?= date('M d, Y', strtotime($a['appointment_date'])) ?>
                                </td>
                                <td class="fw-semibold text-dark text-center" data-order="<?= date('H:i:s', strtotime($a['appointment_time'])) ?>" style="white-space: nowrap;">
                                    <i class="bi bi-clock me-1 text-muted"></i><?= date('h:i A', strtotime($a['appointment_time'])) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge-category-pill <?= $catClass ?>">
                                        <i class="bi <?= $catIcon ?> me-1"></i><?= h($prog) ?>
                                    </span>
                                </td>
                                <td class="text-start text-dark fw-bold">
                                    <a href="<?= url('/patients/' . $a['patient_id']) ?>" class="link-primary-dark text-decoration-none">
                                        <?= h($a['patient_last']) ?>, <?= h($a['patient_first']) ?> 
                                        <span class="text-muted fw-normal font-monospace fs-7">(<?= h($a['patient_no']) ?>)</span>
                                    </a>
                                </td>
                                <td class="text-start text-truncate text-secondary" style="max-width: 220px;" title="<?= h($a['purpose']) ?>">
                                    <?= h($a['purpose']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isPastDue): ?>
                                        <span class="<?= $badgeClass ?> px-2 py-1" title="Scheduled date was <?= date('M d, Y', strtotime($a['appointment_date'])) ?> (<?= $daysPastDue ?> days ago)">
                                            <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>Past Due (<?= $daysPastDue ?>d ago)
                                        </span>
                                    <?php else: ?>
                                        <span class="<?= $badgeClass ?> px-2 py-1"><?= h($a['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end" style="white-space: nowrap;">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <?php if ($isPastDue): ?>
                                            <!-- Past Due Scheduled Appointment: Primary action is Reschedule -->
                                            <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-warning text-dark px-2 py-1" title="Reschedule patient for a new date">
                                                <i class="bi bi-arrow-repeat me-1"></i>Reschedule
                                            </a>

                                            <!-- Secondary Actions Dropdown for Overdue -->
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-secondary px-2 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More actions">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                                                    <li>
                                                        <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="status" value="Missed">
                                                            <button type="submit" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Mark appointment as Missed? This flags the patient as a no-show for this past date.">
                                                                <i class="bi bi-clock-history text-secondary"></i> Mark as Missed (No-Show)
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li>
                                                        <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="status" value="Completed">
                                                            <button type="submit" class="dropdown-item small text-success d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Mark this past appointment as Completed?">
                                                                <i class="bi bi-check2-circle text-success"></i> Mark as Completed (Late)
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li>
                                                        <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="status" value="Cancelled">
                                                            <button type="submit" class="dropdown-item small text-danger d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Are you sure you want to Cancel this appointment?">
                                                                <i class="bi bi-x-circle text-danger"></i> Cancel Appointment
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php elseif ($a['status'] === 'Scheduled'): ?>
                                            <!-- Primary Action: Complete -->
                                            <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="Completed">
                                                <button type="submit" class="btn btn-sm btn-outline-success px-2 py-1 action-confirm-btn" title="Complete appointment" data-confirm="Mark this appointment for <?= h($a['patient_first'] . ' ' . $a['patient_last']) ?> as Completed?">
                                                    <i class="bi bi-check2-circle me-1"></i>Complete
                                                </button>
                                            </form>

                                            <!-- More Actions Dropdown -->
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-secondary px-2 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More actions">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                                                    <li>
                                                        <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2">
                                                            <i class="bi bi-pencil-square text-primary"></i> Edit Details
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li>
                                                        <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="status" value="Missed">
                                                            <button type="submit" class="dropdown-item small text-dark d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Mark appointment as Missed? This flags the patient as a no-show for this consultation date.">
                                                                <i class="bi bi-clock-history text-secondary"></i> Mark as Missed (No-Show)
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li>
                                                        <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="status" value="Cancelled">
                                                            <button type="submit" class="dropdown-item small text-danger d-flex align-items-center gap-2 py-2 action-confirm-btn" data-confirm="Are you sure you want to Cancel this appointment? Cancelled appointments cannot be restored directly.">
                                                                <i class="bi bi-x-circle text-danger"></i> Cancel Appointment
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php elseif ($a['status'] === 'Completed'): ?>
                                            <!-- Completed: View / Edit details -->
                                            <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary px-2 py-1" title="View or edit appointment details">
                                                <i class="bi bi-pencil-square me-1"></i>View / Edit
                                            </a>
                                        <?php else: ?>
                                            <!-- Missed or Cancelled: Allow Staff to Reschedule -->
                                            <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary px-2 py-1" title="Reschedule patient for a new date">
                                                <i class="bi bi-arrow-repeat me-1"></i>Reschedule
                                            </a>
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
