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
            <div class="col-12 col-md-5">
                <label for="search" class="form-label text-secondary small fw-semibold">Search Directory</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="search" class="form-control border-start-0 bg-light" placeholder="Search by username, name, email, employee ID, or department..." value="<?= h($filters['search']) ?>">
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-md-3">
                <label for="role" class="form-label text-secondary small fw-semibold">Filter by Role</label>
                <select name="role" id="role" class="form-select bg-light">
                    <option value="">-- All Roles --</option>
                    <option value="main_admin" <?= (isset($filters['role']) && $filters['role'] === 'main_admin') ? 'selected' : '' ?>>Main Admin</option>
                    <option value="co_admin" <?= (isset($filters['role']) && $filters['role'] === 'co_admin') ? 'selected' : '' ?>>Co-Admin</option>
                    <option value="staff" <?= (isset($filters['role']) && $filters['role'] === 'staff') ? 'selected' : '' ?>>Staff Personnel</option>
                </select>
            </div>
            
            <div class="col-12 col-sm-6 col-md-2">
                <label for="status" class="form-label text-secondary small fw-semibold">Status</label>
                <select name="status" id="status" class="form-select bg-light">
                    <option value="">-- All Statuses --</option>
                    <option value="active" <?= (isset($filters['status']) && $filters['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= (isset($filters['status']) && $filters['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
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
                        <th>Job Title & Unit</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people d-block fs-3 mb-2 text-muted"></i>
                                No user accounts match the search criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $userModel = new \App\Models\User();
                        foreach ($users as $u): 
                            $statusBadge = ($u['status'] === 'active') 
                                ? 'bg-success-bg text-success border border-success-subtle' 
                                : 'bg-secondary text-white';
                            
                            $roleBadge = $u['role'] === 'admin' ? 'bg-light text-primary border border-primary-subtle fw-bold' : 'bg-light text-dark border';
                            $lockoutInfo = $userModel->isLockedOut($u);
                            $isLocked = $lockoutInfo['is_locked'];
                        ?>
                            <tr>
                                <td class="text-start ps-4 fw-bold font-monospace text-dark"><?= h($u['username']) ?></td>
                                <td class="text-start">
                                    <span class="fw-bold text-dark"><?= h($u['last_name']) ?>, <?= h($u['first_name']) ?></span>
                                    <?php if (!empty($u['employee_id'])): ?>
                                        <span class="badge bg-light text-secondary border font-monospace ms-1" style="font-size: 0.68rem;"><?= h($u['employee_id']) ?></span>
                                    <?php endif; ?>
                                    <div class="text-muted small" style="font-size: 0.72rem;"><?= h($u['email'] ?: '-') ?></div>
                                </td>
                                 <?php 
                                 $roleDisplay = h(ucfirst($u['role']));
                                 if ($u['role'] === 'admin') {
                                     $roleDisplay = ($u['id'] == 1) ? 'Main Admin' : 'Co-Admin';
                                 }
                                 ?>
                                 <td><span class="badge <?= $roleBadge ?>"><?= $roleDisplay ?></span></td>
                                <td>
                                    <div class="fw-medium"><?= h($u['job_title'] ?: '-') ?></div>
                                    <?php if (!empty($u['department'])): ?>
                                        <div class="text-muted small" style="font-size: 0.72rem;"><?= h($u['department']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isLocked): ?>
                                        <span class="badge bg-warning text-dark border border-warning-subtle" title="Temporarily locked due to failed attempts">
                                            <i class="bi bi-clock-history me-1"></i> Locked (<?= h($lockoutInfo['remaining_formatted']) ?>)
                                        </span>
                                    <?php elseif ($u['status'] === 'active'): ?>
                                        <span class="badge bg-success-bg text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-white">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                 <td data-order="<?= $u['last_login_at'] ? h($u['last_login_at']) : '1970-01-01 00:00:00' ?>">
                                     <?= $u['last_login_at'] ? date('Y-m-d h:i A', strtotime($u['last_login_at'])) : '<span class="text-muted small">Never</span>' ?>
                                 </td>
                                 <td class="pe-4 text-end">
                                     <div class="d-inline-flex gap-1 align-items-center">
                                         <?php if ($u['role'] === 'admin' && $u['id'] != $_SESSION['user_id'] && $_SESSION['user_id'] != 1): ?>
                                             <!-- Protected Administrator Badge -->
                                             <span class="badge bg-light text-secondary border py-2 px-2.5" title="Administrator account is system-protected.">
                                                 <i class="bi bi-lock-fill text-muted me-1"></i> Protected
                                             </span>
                                         <?php else: ?>
                                             <!-- Clear Lockout Button -->
                                             <?php if ($isLocked): ?>
                                                 <form action="<?= url('/users/' . $u['id'] . '/reset-lockout') ?>" method="POST" class="d-inline">
                                                     <?= csrf_field() ?>
                                                     <button type="submit" 
                                                             class="btn btn-sm btn-outline-warning border-0 px-2 text-dark" 
                                                             title="Clear 15-Minute Lockout" 
                                                             data-confirm="Are you sure you want to clear the 15-minute login lockout for user '<?= h($u['username']) ?>'? They will be able to log in immediately.">
                                                         <i class="bi bi-unlock-fill fs-6"></i>
                                                     </button>
                                                 </form>
                                             <?php endif; ?>

                                             <!-- Edit Profile -->
                                             <a href="<?= url('/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary border-0 px-2" title="Edit Profile Details">
                                                 <i class="bi bi-pencil-square fs-6"></i>
                                             </a>
                                             
                                             <!-- Password Reset Button Triggering Modal -->
                                             <button type="button" 
                                                     class="btn btn-sm btn-outline-warning border-0 px-2" 
                                                     title="Reset User Password" 
                                                     data-bs-toggle="modal" 
                                                     data-bs-target="#resetPasswordModal" 
                                                     data-user-id="<?= $u['id'] ?>" 
                                                     data-username="<?= h($u['username']) ?>">
                                                 <i class="bi bi-key-fill fs-6"></i>
                                             </button>

                                             <!-- Activate / Deactivate Action Button -->
                                             <?php if ($u['id'] != $_SESSION['user_id'] && $u['id'] != 1): ?>
                                                 <form action="<?= url('/users/' . $u['id'] . '/toggle-status') ?>" method="POST" class="d-inline">
                                                     <?= csrf_field() ?>
                                                     <?php if ($u['status'] === 'active'): ?>
                                                         <button type="submit" 
                                                                 class="btn btn-sm btn-outline-danger border-0 px-2" 
                                                                 title="Deactivate Account" 
                                                                 data-confirm="Are you sure you want to deactivate user account '<?= h($u['username']) ?>'? They will not be able to log in to the system until reactivated.">
                                                             <i class="bi bi-person-x-fill fs-6"></i>
                                                         </button>
                                                     <?php else: ?>
                                                         <button type="submit" 
                                                                 class="btn btn-sm btn-outline-success border-0 px-2" 
                                                                 title="Activate Account" 
                                                                 data-confirm="Are you sure you want to activate user account '<?= h($u['username']) ?>'? They will regain system access immediately.">
                                                             <i class="bi bi-person-check-fill fs-6"></i>
                                                         </button>
                                                     <?php endif; ?>
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-warning py-3 text-dark" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold d-flex align-items-center" id="resetPasswordModalLabel">
                    <i class="bi bi-shield-fill-check me-2 fs-5"></i> Reset User Password
                </h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resetPasswordForm" method="POST" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4 bg-white">
                    <p class="text-secondary small mb-3">
                        You are resetting the password for account: <strong id="resetTargetUsername" class="text-primary font-monospace"></strong>
                    </p>
                    
                    <!-- Admin Password field -->
                    <div class="mb-3">
                        <label for="admin_password" class="form-label fw-semibold text-secondary small">Your Administrator Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key-fill"></i></span>
                            <input type="password" name="admin_password" id="admin_password" class="form-control bg-light border-start-0 border-end-0" placeholder="Enter your current password to authorize" required>
                            <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- New Password field -->
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold text-secondary small">New Temporary Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="new_password" id="new_password" class="form-control bg-light border-start-0 border-end-0" placeholder="Minimum 8 characters" minlength="8" required>
                            <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password field -->
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label fw-semibold text-secondary small">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control bg-light border-start-0 border-end-0" placeholder="Repeat new password" minlength="8" required>
                            <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 d-flex justify-content-end gap-2" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($users)): ?>
        $('#usersTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[1, "asc"]], // Sort by name ascending
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Actions
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>

    // Handle Reset Password Modal data population
    const resetModal = document.getElementById('resetPasswordModal');
    if (resetModal) {
        resetModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const username = button.getAttribute('data-username');
            
            const targetUsername = document.getElementById('resetTargetUsername');
            const form = document.getElementById('resetPasswordForm');
            
            targetUsername.textContent = '@' + username;
            form.action = '<?= url('/users/') ?>' + userId + '/reset-password';
            
            // Clear inputs and reset visibility states
            ['admin_password', 'new_password', 'confirm_password'].forEach(function(id) {
                const el = document.getElementById(id);
                if (el) {
                    el.value = '';
                    el.type = 'password';
                }
            });
            resetModal.querySelectorAll('.btn-toggle-password').forEach(function(btn) {
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
                btn.setAttribute('title', 'Show password');
                btn.setAttribute('aria-label', 'Show password');
            });
        });
    }
});
</script>
