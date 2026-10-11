<?php
$title = 'System Audit Logs';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Audit Logs</h2>
        <p class="text-secondary small mb-0">Track system actions, changes, logins, and operations for security compliance.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="<?= url('/audit-logs/export?' . http_build_query($filters)) ?>" class="btn btn-outline-success d-inline-flex align-items-center gap-2 shadow-sm" title="Download filtered audit logs to CSV">
            <i class="bi bi-file-earmark-arrow-down"></i>
            <span>Export CSV</span>
        </a>
    </div>
</div>

<div class="card card-premium mb-4">
    <div class="card-body p-4 bg-white">
        <!-- Search and Filter Form -->
        <form action="<?= url('/audit-logs') ?>" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="user_id" class="form-label text-secondary small fw-semibold">Performed By</label>
                <select name="user_id" id="user_id" class="form-select bg-light">
                    <option value="">-- All Users --</option>
                    <?php foreach ($users as $u): 
                        $uRoleLabel = ($u['role'] === 'super_admin') ? 'Super Admin' : ($u['role'] === 'admin' ? 'Admin' : 'Staff');
                        $statusSuffix = ($u['status'] !== 'active') ? ' (' . ucfirst($u['status']) . ')' : '';
                    ?>
                        <option value="<?= $u['id'] ?>" <?= (isset($filters['user_id']) && $filters['user_id'] == $u['id']) ? 'selected' : '' ?>>
                            <?= h($u['last_name']) ?>, <?= h($u['first_name']) ?> (<?= h($u['username']) ?>) - <?= $uRoleLabel ?><?= $statusSuffix ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <label for="role" class="form-label text-secondary small fw-semibold">User Role</label>
                <select name="role" id="role" class="form-select bg-light">
                    <option value="">-- All Roles --</option>
                    <option value="super_admin" <?= (isset($filters['role']) && $filters['role'] === 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                    <option value="admin" <?= (isset($filters['role']) && $filters['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="staff" <?= (isset($filters['role']) && $filters['role'] === 'staff') ? 'selected' : '' ?>>Staff Personnel</option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <label for="module" class="form-label text-secondary small fw-semibold">System Module</label>
                <select name="module" id="module" class="form-select bg-light">
                    <option value="">-- All Modules --</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?= h($mod) ?>" <?= (isset($filters['module']) && $filters['module'] === $mod) ? 'selected' : '' ?>>
                            <?= h($mod) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12 col-md-4">
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
            
            <div class="col-12 col-sm-6 col-md-4">
                <label for="date_from" class="form-label text-secondary small fw-semibold">Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control bg-light" value="<?= h($filters['date_from']) ?>">
            </div>
            
            <div class="col-12 col-sm-6 col-md-4">
                <label for="date_to" class="form-label text-secondary small fw-semibold">Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control bg-light" value="<?= h($filters['date_to']) ?>">
            </div>
            
            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-funnel me-1"></i> Filter Logs
                </button>
                <a href="<?= url('/audit-logs') ?>" class="btn btn-outline-secondary px-3" title="Clear Filters">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($filters['date_from']) && empty($filters['date_to']) && count($logs) >= 500): ?>
    <div class="alert alert-light border py-2 px-3 small text-secondary d-flex align-items-center gap-2 mb-3 rounded shadow-xs">
        <i class="bi bi-info-circle text-primary fs-6"></i>
        <span>Showing the latest <strong>500 records</strong>. Specify a date range above to inspect older records or use <strong>Export CSV</strong> for complete regulatory archives.</span>
    </div>
<?php endif; ?>

<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center small" id="auditTable" style="table-layout: fixed; width: 100%;">
                <thead class="table-light">
                    <tr>
                        <th class="text-start ps-4" style="width: 18%;">Timestamp</th>
                        <th style="width: 15%;">User</th>
                        <th style="width: 18%;">Action</th>
                        <th style="width: 12%;">Module</th>
                        <th style="width: 12%;">IP Address</th>
                        <th class="pe-4 text-start" style="width: 25%;">Operation Details</th>
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
                            $act = $log['action'];
                            $isSecurityAlert = (bool)preg_match('/(FAIL|LOCK|BLOCK|REJECT|VIOLAT|ERROR|ABORT)/i', $act);
                            
                            if ($isSecurityAlert) {
                                $badgeClass = 'bg-danger-bg text-danger border border-danger-subtle';
                            } elseif (preg_match('/(CREATE|REGISTER|RESTORE)/i', $act)) {
                                $badgeClass = 'bg-success-bg text-success border border-success-subtle';
                            } elseif (preg_match('/(UPDATE|RESET|SYNC|EXPORT)/i', $act)) {
                                $badgeClass = 'bg-info-bg text-info border border-info-subtle';
                            } elseif (preg_match('/(DELETE|ARCHIVE|VOID)/i', $act)) {
                                $badgeClass = 'bg-danger-bg text-danger border border-danger-subtle';
                            } else {
                                $badgeClass = 'bg-secondary-bg text-secondary border';
                            }
                        ?>
                            <tr>
                                <td class="text-start ps-4 fw-medium text-dark" data-order="<?= h($log['created_at']) ?>">
                                    <?= date('Y-m-d h:i:s A', strtotime($log['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['username'])): ?>
                                        <span class="fw-bold"><?= h($log['username']) ?></span>
                                    <?php elseif (!empty($log['user_fullname'])): ?>
                                        <span class="fw-bold"><?= h($log['user_fullname']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border">Unauthenticated</span>
                                    <?php endif; ?>

                                    <?php if (!empty($log['user_fullname']) && !empty($log['username'])): ?>
                                        <div class="text-muted small" style="font-size: 0.72rem;"><?= h($log['user_fullname']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $badgeClass ?>"><?= h($log['action']) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= h($log['module']) ?></span></td>
                                <td><code class="text-primary small"><?= h($log['ip_address']) ?></code></td>
                                <td class="pe-4 text-start text-secondary" style="white-space: pre-wrap; word-break: break-word; font-size: 0.8rem;"><?= h($log['details']) ?></td>
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
