<?php
$title = 'Queue Management';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Daily Operations Queue</h2>
        <p class="text-secondary small mb-0">Manage patient intake, calls, and service status for today: <strong><?= date('F d, Y') ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/queue/display') ?>" target="_blank" class="btn btn-outline-primary d-flex align-items-center px-3">
            <i class="bi bi-display me-2 fs-5"></i>
            <span>Open Public Display</span>
        </a>
        <a href="<?= url('/queue') ?>" class="btn btn-outline-secondary d-flex align-items-center px-2" title="Refresh Board">
            <i class="bi bi-arrow-clockwise fs-5"></i>
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Add Patient to Queue Form -->
    <div class="col-12 col-lg-4">
        <!-- Error Alert Banner -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px;">
                <h5 class="fw-bold mb-2 small"><i class="bi bi-exclamation-triangle-fill me-2"></i>Registration Failed:</h5>
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Queue Registration Form Card -->
        <div class="card card-premium">
            <div class="card-header bg-white py-3 border-bottom">
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-person-plus-fill text-primary me-2"></i>Intake & Enqueue Patient
                </h3>
            </div>
            
            <form action="<?= url('/queue') ?>" method="POST" id="enqueueForm">
                <?= csrf_field() ?>

                <div class="card-body p-4 bg-white">
                    <div class="mb-3">
                        <label for="patient_id" class="form-label fw-semibold text-secondary small">Select Patient <span class="text-danger">*</span></label>
                        <select name="patient_id" id="patient_id" class="form-select bg-light" required>
                            <option value="">-- Select Patient --</option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (isset($input['patient_id']) && $input['patient_id'] == $p['id']) ? 'selected' : '' ?>>
                                    <?= h($p['last_name']) ?>, <?= h($p['first_name']) ?> (<?= h($p['patient_no']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small mt-2">
                            Select a registered patient to issue today's queue number. 
                            If the patient is not yet registered, please <a href="<?= url('/patients/create') ?>" target="_blank">register them first</a>.
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light py-3 border-0 d-grid" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add to Queue
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Today's Queue Active List -->
    <div class="col-12 col-lg-8">
        <div class="card card-premium">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-list-ol text-primary me-2"></i>Today's Active Queue List
                </h3>
                <span class="badge bg-primary-soft text-primary small"><?= count($queueList) ?> total entries</span>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small" id="queueTable">
                        <thead class="table-light">
                            <tr>
                                <th>Queue No.</th>
                                <th class="text-start">Patient Name</th>
                                <th>Time In</th>
                                <th>Time Called</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($queueList)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-people d-block fs-3 mb-2 text-muted"></i>
                                        No patients are currently queued for today.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($queueList as $q): 
                                    $statusBadge = 'bg-secondary';
                                    if ($q['status'] === 'Waiting') $statusBadge = 'bg-warning text-dark';
                                    elseif ($q['status'] === 'Called') $statusBadge = 'bg-primary text-white';
                                    elseif ($q['status'] === 'Serving') $statusBadge = 'bg-info text-dark';
                                    elseif ($q['status'] === 'Completed') $statusBadge = 'bg-success text-white';
                                    elseif ($q['status'] === 'Cancelled') $statusBadge = 'bg-danger text-white';
                                ?>
                                    <tr>
                                        <td class="fw-bold fs-6 text-primary-dark">
                                            <?= sprintf('%03d', $q['queue_no']) ?>
                                        </td>
                                        <td class="text-start fw-bold">
                                            <a href="<?= url('/patients/' . $q['patient_id']) ?>" class="link-primary-dark">
                                                <?= h($q['patient_last']) ?>, <?= h($q['patient_first']) ?>
                                                <span class="text-muted fw-normal font-monospace fs-7">(<?= h($q['patient_no']) ?>)</span>
                                            </a>
                                        </td>
                                        <td class="text-secondary"><?= date('h:i A', strtotime($q['time_in'])) ?></td>
                                        <td class="text-secondary"><?= $q['time_called'] ? date('h:i A', strtotime($q['time_called'])) : '<span class="text-muted">-</span>' ?></td>
                                        <td>
                                            <span class="badge <?= $statusBadge ?>"><?= h($q['status']) ?></span>
                                        </td>
                                        <td class="pe-4 text-end" style="white-space: nowrap;">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ($q['status'] === 'Waiting'): ?>
                                                    <!-- Transition to Called -->
                                                    <form action="<?= url('/queue/' . $q['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Called">
                                                        <button type="submit" class="btn btn-sm btn-primary px-2 py-1" title="Call Patient" data-confirm="Call Queue No: <?= sprintf('%03d', $q['queue_no']) ?>?">
                                                            <i class="bi bi-megaphone me-1"></i> Call
                                                        </button>
                                                    </form>
                                                <?php elseif ($q['status'] === 'Called'): ?>
                                                    <!-- Transition to Serving -->
                                                    <form action="<?= url('/queue/' . $q['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Serving">
                                                        <button type="submit" class="btn btn-sm btn-info px-2 py-1" title="Start Serving" data-confirm="Mark Queue No: <?= sprintf('%03d', $q['queue_no']) ?> as Currently Serving?">
                                                            <i class="bi bi-play-fill me-1"></i> Serve
                                                        </button>
                                                    </form>
                                                <?php elseif ($q['status'] === 'Serving'): ?>
                                                    <!-- Transition to Completed -->
                                                    <form action="<?= url('/queue/' . $q['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Completed">
                                                        <button type="submit" class="btn btn-sm btn-success px-2 py-1" title="Complete Service" data-confirm="Mark Queue No: <?= sprintf('%03d', $q['queue_no']) ?> as Completed?">
                                                            <i class="bi bi-check-lg me-1"></i> Complete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (in_array($q['status'], ['Waiting', 'Called', 'Serving'])): ?>
                                                    <!-- Transition to Cancelled -->
                                                    <form action="<?= url('/queue/' . $q['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Cancelled">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2" title="Cancel Queue Number" data-confirm="Are you sure you want to cancel Queue No: <?= sprintf('%03d', $q['queue_no']) ?>?">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </form>
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
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable only if table is not empty
    <?php if (!empty($queueList)): ?>
        $('#queueTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "asc"]], // Order by Queue No ascending
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Disable sorting on action buttons
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
