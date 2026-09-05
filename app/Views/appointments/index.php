<?php
$title = 'Appointments';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Appointment Directory</h2>
        <p class="text-secondary small mb-0">Manage and filter upcoming visits, schedule follow-ups, and update booking status.</p>
    </div>
    <a href="<?= url('/appointments/create') ?>" class="btn btn-primary d-flex align-items-center py-2 px-3">
        <i class="bi bi-calendar-plus me-2 fs-5"></i>
        <span>Schedule Appointment</span>
    </a>
</div>

<!-- Filters Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-4">
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
                    <input type="text" 
                           name="date_from" 
                           id="date_from" 
                           class="form-control bg-light border-start-0 flatpickr" 
                           placeholder="YYYY-MM-DD" 
                           value="<?= h($filters['date_from']) ?>">
                </div>
            </div>

            <!-- Date To -->
            <div class="col-12 col-sm-6 col-md-2">
                <label for="date_to" class="form-label fw-semibold text-secondary small">Scheduled Date To</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar3"></i></span>
                    <input type="text" 
                           name="date_to" 
                           id="date_to" 
                           class="form-control bg-light border-start-0 flatpickr" 
                           placeholder="YYYY-MM-DD" 
                           value="<?= h($filters['date_to']) ?>">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-12 col-sm-6 col-md-3">
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
            <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel"></i> Filter
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
            <table class="table table-hover align-middle mb-0 text-center small" id="appointmentsTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-start ps-4">Scheduled Date</th>
                        <th>Time</th>
                        <th class="text-start">Patient Name</th>
                        <th>Program</th>
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
                        <?php foreach ($appointments as $a): 
                            // Format status badges
                            $badgeClass = 'bg-secondary';
                            if ($a['status'] === 'Scheduled') $badgeClass = 'bg-info text-dark';
                            elseif ($a['status'] === 'Completed') $badgeClass = 'bg-success text-white';
                            elseif ($a['status'] === 'Cancelled') $badgeClass = 'bg-danger text-white';
                            elseif ($a['status'] === 'Missed') $badgeClass = 'bg-dark text-white';

                            $pt = $a['program_type'] ?? 'General OPD';
                            $ptBadge = 'badge bg-primary-soft text-primary';
                            if (stripos($pt, 'prenatal') !== false) $ptBadge = 'badge bg-pink text-white';
                            elseif (stripos($pt, 'baby') !== false || stripos($pt, 'immunization') !== false) $ptBadge = 'badge bg-success-soft text-success';
                            elseif (stripos($pt, 'senior') !== false) $ptBadge = 'badge bg-purple text-white';
                            elseif (stripos($pt, 'family') !== false) $ptBadge = 'badge bg-info text-dark';
                        ?>
                            <tr>
                                <td class="text-start ps-4 text-secondary fw-semibold" style="white-space: nowrap;">
                                    <?= date('M d, Y', strtotime($a['appointment_date'])) ?>
                                </td>
                                <td class="fw-semibold text-dark" style="white-space: nowrap;">
                                    <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                                </td>
                                <td class="text-start text-dark fw-bold">
                                    <a href="<?= url('/patients/' . $a['patient_id']) ?>" class="link-primary-dark">
                                        <?= h($a['patient_last']) ?>, <?= h($a['patient_first']) ?> 
                                        <span class="text-muted fw-normal font-monospace fs-7">(<?= h($a['patient_no']) ?>)</span>
                                    </a>
                                </td>
                                <td>
                                    <span class="<?= $ptBadge ?>"><?= h($pt) ?></span>
                                </td>
                                <td class="text-start text-truncate text-secondary" style="max-width: 200px;" title="<?= h($a['purpose']) ?>">
                                    <?= h($a['purpose']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= h($a['status']) ?></span>
                                </td>
                                <td class="pe-4 text-end" style="white-space: nowrap;">
                                    <div class="d-inline-flex gap-1">
                                        <?php if ($a['status'] === 'Scheduled'): ?>
                                            <!-- Quick complete action -->
                                            <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="Completed">
                                                <button type="submit" class="btn btn-sm btn-outline-success border-0 px-2" title="Mark as Completed" data-confirm="Mark this appointment as Completed?">
                                                    <i class="bi bi-check-lg fs-6"></i>
                                                </button>
                                            </form>
                                            
                                            <!-- Quick missed action -->
                                            <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="Missed">
                                                <button type="submit" class="btn btn-sm btn-outline-dark border-0 px-2" title="Mark as Missed" data-confirm="Mark this appointment as Missed?">
                                                    <i class="bi bi-clock-history fs-6"></i>
                                                </button>
                                            </form>

                                            <!-- Quick cancel action -->
                                            <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="Cancelled">
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2" title="Cancel Appointment" data-confirm="Are you sure you want to cancel this appointment?">
                                                    <i class="bi bi-x-lg fs-6"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Reschedule / Edit action -->
                                        <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary border-0 px-2" title="Reschedule / Edit">
                                            <i class="bi bi-pencil fs-6"></i>
                                        </a>
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
    // 1. Initialize Flatpickr for date filters
    flatpickr('.flatpickr', {
        dateFormat: 'Y-m-d',
        allowInput: true
    });

    // 2. Initialize DataTable
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
                { "orderable": false, "targets": 6 } // Disable sorting on action buttons (index 6)
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
