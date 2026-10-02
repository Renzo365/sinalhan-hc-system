<?php
$title = 'User Accounts';
require dirname(__DIR__) . '/layout/header.php';
?>


<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">User Accounts</h2>
        <p class="text-secondary small mb-0">Create and manage administrative and operational staff credentials and permissions.</p>
    </div>
    
    <a href="<?= url('/users/create') ?>" class="btn btn-primary d-flex align-items-center px-3 py-2">
        <i class="bi bi-person-plus-fill me-2 fs-5"></i>
        <span>Add New User</span>
    </a>
</div>

<!-- Search/Filter Cards -->
<div class="card card-premium mb-4">
    <div class="card-body p-4 bg-white">
        <form action="<?= url('/users') ?>" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
                <label for="search" class="form-label text-secondary small fw-semibold">Search Directory</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="search" class="form-control border-start-0 bg-light" placeholder="Search by username, name, email, or job title..." value="<?= h($filters['search']) ?>">
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-md-4">
                <label for="role" class="form-label text-secondary small fw-semibold">Filter by Role</label>
                <select name="role" id="role" class="form-select bg-light">
                    <option value="">-- All Roles --</option>
                    <option value="super_admin" <?= (isset($filters['role']) && $filters['role'] === 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                    <option value="admin" <?= (isset($filters['role']) && $filters['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="staff" <?= (isset($filters['role']) && $filters['role'] === 'staff') ? 'selected' : '' ?>>Staff Personnel</option>
                </select>
            </div>
            
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="<?= url('/users') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users List Card -->
<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center small" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-start ps-4">Username</th>
                        <th class="text-start">Full Name</th>
                        <th>Role</th>
                        <th>Job Title</th>
                        <th>Last Login</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people d-block fs-3 mb-2 text-muted"></i>
                                No user accounts match the search criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        foreach ($users as $u): 
                            $lockoutInfo = $u['lockout_info'] ?? ['is_locked' => false, 'remaining_seconds' => 0, 'remaining_formatted' => ''];
                            $isLocked = !empty($lockoutInfo['is_locked']);
                            
                            $isTargetSuperAdmin = ($u['role'] === 'super_admin');
                            $isTargetAdmin = ($u['role'] === 'admin');
                            $isSelf = ($u['id'] == $_SESSION['user_id']);
                            
                            $canManage = false;
                            if (is_super_admin()) {
                                $canManage = true;
                            } elseif ($isSelf) {
                                $canManage = true;
                            } elseif (!$isTargetSuperAdmin && !$isTargetAdmin) {
                                $canManage = true; // Standard admin can manage staff
                            }
                            
                            $disabledTooltipReason = 'You do not have permission to manage this protected account.';
                            if ($isTargetSuperAdmin) {
                                $disabledTooltipReason = 'The Super Admin account is permanently protected.';
                            } elseif ($isTargetAdmin) {
                                $disabledTooltipReason = 'You cannot modify peer administrator accounts. Only Super Admin can manage Administrators.';
                            }

                            if ($u['role'] === 'super_admin') {
                                $roleBadgeStyle = 'background-color: #0f766e; color: #ffffff;';
                                $roleBadgeClass = 'badge shadow-sm';
                                $roleDisplay = 'Super Admin';
                            } elseif ($u['role'] === 'admin') {
                                $roleBadgeStyle = 'background-color: #e6fffa; color: #0d9488; border: 1px solid #99f6e4;';
                                $roleBadgeClass = 'badge fw-semibold';
                                $roleDisplay = 'Admin';
                            } else {
                                $roleBadgeStyle = 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
                                $roleBadgeClass = 'badge fw-medium';
                                $roleDisplay = 'Staff';
                            }
                        ?>
                            <tr>
                                <td class="text-start ps-4 fw-bold font-monospace text-dark"><?= h($u['username']) ?></td>
                                <td class="text-start">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-dark"><?= h($u['last_name']) ?>, <?= h($u['first_name']) ?></span>
                                        <?php if ($isLocked): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-0" style="font-size: 0.68rem;" title="Temporarily locked out due to failed attempts">
                                                <i class="bi bi-clock-history me-1"></i> Locked (<?= h($lockoutInfo['remaining_formatted']) ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small mt-1" style="font-size: 0.75rem;">
                                        <?php if (!empty($u['email'])): ?>
                                            <span class="text-muted"><i class="bi bi-envelope me-1"></i><?= h($u['email']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">No email recorded</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><span class="<?= $roleBadgeClass ?>" style="<?= $roleBadgeStyle ?>"><?= $roleDisplay ?></span></td>
                                <td>
                                    <span class="fw-medium text-dark"><?= h($u['job_title'] ?: 'Staff Member') ?></span>
                                </td>
                                 <td data-order="<?= $u['last_login_at'] ? h($u['last_login_at']) : '1970-01-01 00:00:00' ?>">
                                     <?= $u['last_login_at'] ? date('Y-m-d h:i A', strtotime($u['last_login_at'])) : '<span class="text-muted small">Never</span>' ?>
                                 </td>
                                 <td class="pe-4 text-end">
                                     <div class="d-inline-flex gap-2 align-items-center">
                                         <?php if (!$canManage): ?>
                                             <!-- Disabled Action Buttons with Tooltip for Protected Accounts -->
                                             <span class="d-inline-block" style="cursor: not-allowed;" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= h($disabledTooltipReason) ?>">
                                                 <button type="button" 
                                                         class="btn btn-sm btn-light border text-muted opacity-50 d-inline-flex align-items-center justify-content-center" 
                                                         style="min-width: 34px; min-height: 34px; padding: 0.25rem; pointer-events: none;" 
                                                         aria-label="Edit Profile (Disabled)"
                                                         tabindex="-1"
                                                         disabled>
                                                     <i class="bi bi-pencil-square fs-6"></i>
                                                 </button>
                                             </span>

                                             <span class="d-inline-block" style="cursor: not-allowed;" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= h($disabledTooltipReason) ?>">
                                                 <button type="button" 
                                                         class="btn btn-sm btn-light border text-muted opacity-50 d-inline-flex align-items-center justify-content-center" 
                                                         style="min-width: 34px; min-height: 34px; padding: 0.25rem; pointer-events: none;" 
                                                         aria-label="Archive Account (Disabled)"
                                                         tabindex="-1"
                                                         disabled>
                                                     <i class="bi bi-archive-fill fs-6"></i>
                                                 </button>
                                             </span>
                                         <?php else: ?>
                                             <!-- Clear Lockout Button -->
                                             <?php if ($isLocked): ?>
                                                 <form action="<?= url('/users/' . $u['id'] . '/reset-lockout') ?>" method="POST" class="d-inline">
                                                     <?= csrf_field() ?>
                                                     <button type="submit" 
                                                             class="btn btn-sm btn-outline-warning border text-dark d-inline-flex align-items-center justify-content-center" 
                                                             style="min-width: 34px; min-height: 34px; padding: 0.25rem;"
                                                             title="Clear 15-Minute Lockout" 
                                                             aria-label="Clear Lockout for <?= h($u['username']) ?>"
                                                             data-bs-toggle="tooltip"
                                                             data-bs-placement="top"
                                                             data-confirm="Are you sure you want to clear the 15-minute login lockout for user '<?= h($u['username']) ?>'? They will be able to log in immediately.">
                                                         <i class="bi bi-unlock-fill fs-6"></i>
                                                     </button>
                                                 </form>
                                             <?php endif; ?>

                                             <!-- Edit Profile -->
                                             <a href="<?= url('/users/' . $u['id'] . '/edit') ?>" 
                                                class="btn btn-sm btn-outline-primary border d-inline-flex align-items-center justify-content-center" 
                                                style="min-width: 34px; min-height: 34px; padding: 0.25rem;"
                                                title="Edit Profile Details"
                                                aria-label="Edit Profile Details for <?= h($u['username']) ?>"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top">
                                                 <i class="bi bi-pencil-square fs-6"></i>
                                             </a>

                                             <!-- Archive Action Button -->
                                             <?php if (!$isSelf && !$isTargetSuperAdmin && (!$isTargetAdmin || is_super_admin())): ?>
                                                 <form action="<?= url('/users/' . $u['id'] . '/archive') ?>" method="POST" class="d-inline">
                                                     <?= csrf_field() ?>
                                                     <button type="submit" 
                                                             class="btn btn-sm btn-outline-danger border d-inline-flex align-items-center justify-content-center" 
                                                             style="min-width: 34px; min-height: 34px; padding: 0.25rem;"
                                                             title="Archive Account" 
                                                             aria-label="Archive Account <?= h($u['username']) ?>"
                                                             data-bs-toggle="tooltip"
                                                             data-bs-placement="top"
                                                             data-confirm="Are you sure you want to archive user account '<?= h($u['username']) ?>'? This account will be deactivated and moved to the Archived Records Hub.">
                                                         <i class="bi bi-archive-fill fs-6"></i>
                                                     </button>
                                                 </form>
                                             <?php endif; ?>
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
    function initTooltips() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            }
        });
    }

    <?php if (!empty($users)): ?>
        const usersTable = $('#usersTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[1, "asc"]], // Sort by name ascending
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Actions column index 5
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });

        usersTable.on('draw', function() {
            initTooltips();
        });
    <?php endif; ?>

    initTooltips();
});
</script>
