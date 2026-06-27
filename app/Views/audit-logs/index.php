<?php
$title = 'System Audit Logs';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Audit Logs</h2>
        <p class="text-secondary small mb-0">Track system actions, changes, logins, and operations for security compliance.</p>
    </div>
</div>

<div class="card card-premium mb-4">
    <div class="card-body p-4 bg-white">
        <!-- Search and Filter Form -->
        <form action="<?= url('/audit-logs') ?>" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-sm-6 col-md-3">
                <label for="user_id" class="form-label text-secondary small fw-semibold">Performed By</label>
                <select name="user_id" id="user_id" class="form-select bg-light">
                    <option value="">-- All Users --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (isset($filters['user_id']) && $filters['user_id'] == $u['id']) ? 'selected' : '' ?>>
                            <?= h($u['last_name']) ?>, <?= h($u['first_name']) ?> (<?= h($u['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12 col-sm-6 col-md-3">
                <label for="action" class="form-label text-secondary small fw-semibold">Action Type</label>
                <select name="action" id="action" class="form-select bg-light">
                    <option value="">-- All Actions --</option>
                    <?php foreach ($actions as $act): ?>
                        <option value="<?= h($act) ?>" <?= (isset($filters['action']) && $filters['action'] === $act) ? 'selected' : '' ?>>
                            <?= h($act) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12 col-sm-6 col-md-2">
                <label for="date_from" class="form-label text-secondary small fw-semibold">Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control bg-light" value="<?= h($filters['date_from']) ?>">
            </div>
            
            <div class="col-12 col-sm-6 col-md-2">
                <label for="date_to" class="form-label text-secondary small fw-semibold">Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control bg-light" value="<?= h($filters['date_to']) ?>">
            </div>
            
            <div class="col-12 col-md-2 d-grid gap-2 d-md-flex">
                <button type="submit" class="btn btn-primary w-100" title="Apply Filters">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="<?= url('/audit-logs') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center small" id="auditTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-start ps-4">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>IP Address</th>
                        <th class="pe-4 text-start">Operation Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-lock d-block fs-3 mb-2 text-muted"></i>
                                No audit log records exist matching criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $badgeClass = 'bg-secondary';
                            if (strpos($log['action'], 'CREATE') !== false || strpos($log['action'], 'REGISTER') !== false) {
                                $badgeClass = 'bg-success-bg text-success border border-success-subtle';
                            } elseif (strpos($log['action'], 'UPDATE') !== false) {
                                $badgeClass = 'bg-info-bg text-info border border-info-subtle';
                            } elseif (strpos($log['action'], 'DELETE') !== false || strpos($log['action'], 'ARCHIVE') !== false) {
                                $badgeClass = 'bg-danger-bg text-danger border border-danger-subtle';
                            }
                        ?>
                            <tr>
                                <td class="text-start ps-4 fw-medium text-dark"><?= date('Y-m-d h:i:A', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <span class="fw-bold"><?= h($log['username'] ?? 'System') ?></span>
                                    <?php if ($log['user_fullname']): ?>
                                        <div class="text-muted small" style="font-size: 0.72rem;"><?= h($log['user_fullname']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $badgeClass ?>"><?= h($log['action']) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= h($log['module']) ?></span></td>
                                <td><code class="text-primary small"><?= h($log['ip_address']) ?></code></td>
                                <td class="pe-4 text-start text-secondary" style="max-width: 300px; white-space: pre-wrap; font-size: 0.8rem;"><?= h($log['details']) ?></td>
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
    <?php if (!empty($logs)): ?>
        $('#auditTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "desc"]], // Sort by timestamp descending
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
