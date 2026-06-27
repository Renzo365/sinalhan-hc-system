<?php
$title = 'User Accounts Management';
require dirname(__DIR__) . '/layout/header.php';

// Old input retrieval for form reload on validation failure
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">User Accounts</h2>
        <p class="text-secondary small mb-0">Create and manage administrative and operational staff credentials and permissions.</p>
    </div>
    
    <button class="btn btn-primary d-flex align-items-center px-3 py-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus-fill me-2 fs-5"></i>
        <span>Add New User</span>
    </button>
</div>

<!-- Search/Filter Cards -->
<div class="card card-premium mb-4">
    <div class="card-body p-4 bg-white">
        <form action="<?= url('/users') ?>" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-8">
                <label for="search" class="form-label text-secondary small fw-semibold">Search User</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="search" class="form-control border-start-0 bg-light" placeholder="Search by username, name, or email..." value="<?= h($filters['search']) ?>">
                </div>
            </div>
            
            <div class="col-12 col-sm-8 col-md-3">
                <label for="role" class="form-label text-secondary small fw-semibold">Filter by Role</label>
                <select name="role" id="role" class="form-select bg-light">
                    <option value="">-- All Roles --</option>
                    <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    <option value="staff" <?= $filters['role'] === 'staff' ? 'selected' : '' ?>>Staff Personnel</option>
                </select>
            </div>
            
            <div class="col-12 col-sm-4 col-md-1 d-grid">
                <button type="submit" class="btn btn-primary" title="Apply Filters">
                    <i class="bi bi-funnel"></i>
                </button>
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
                                No user accounts match the criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): 
                            $statusBadge = 'bg-secondary';
                            if ($u['status'] === 'active') {
                                $statusBadge = 'bg-success-bg text-success border border-success-subtle';
                            } elseif ($u['status'] === 'suspended') {
                                $statusBadge = 'bg-danger-bg text-danger border border-danger-subtle';
                            }
                            
                            $roleBadge = $u['role'] === 'admin' ? 'bg-primary-bg text-primary fw-bold' : 'bg-light text-dark border';
                        ?>
                            <tr>
                                <td class="text-start ps-4 fw-bold font-monospace text-dark"><?= h($u['username']) ?></td>
                                <td class="text-start">
                                    <span class="fw-bold text-dark"><?= h($u['last_name']) ?>, <?= h($u['first_name']) ?></span>
                                    <div class="text-muted small" style="font-size: 0.72rem;"><?= h($u['email'] ?: '-') ?></div>
                                </td>
                                <td><span class="badge <?= $roleBadge ?>"><?= h(ucfirst($u['role'])) ?></span></td>
                                <td><?= h($u['job_title'] ?: '-') ?></td>
                                <td><span class="badge <?= $statusBadge ?>"><?= h(ucfirst($u['status'])) ?></span></td>
                                <td><?= $u['last_login_at'] ? date('Y-m-d h:i A', strtotime($u['last_login_at'])) : '<span class="text-muted small">Never</span>' ?></td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= url('/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary border-0 px-2" title="Edit Profile Details">
                                            <i class="bi bi-pencil-square fs-6"></i>
                                        </a>
                                        
                                        <!-- Password Reset Form -->
                                        <form action="<?= url('/users/' . $u['id'] . '/reset-password') ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-warning border-0 px-2" title="Reset User Password" data-confirm="Are you sure you want to reset password for user '<?= h($u['username']) ?>'? It will be set back to temporary default: SinalhanStaff@123.">
                                                <i class="bi bi-key-fill fs-6"></i>
                                            </button>
                                        </form>

                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <!-- Soft-Delete Form -->
                                            <form action="<?= url('/users/' . $u['id'] . '/toggle-status') ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2" title="Deactivate User Account" data-confirm="Are you sure you want to suspend and deactivate user account: '<?= h($u['username']) ?>'? They will not be able to log in.">
                                                    <i class="bi bi-person-x-fill fs-6"></i>
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

<!-- ==========================================================================
   ADD USER MODAL
   ========================================================================== -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="addUserModalLabel">
                    <i class="bi bi-person-plus-fill me-2"></i>Add Staff Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/users') ?>" method="POST" id="userForm">
                <?= csrf_field() ?>
                
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <!-- Username -->
                        <div class="col-12 col-md-6">
                            <label for="new_username" class="form-label fw-semibold text-secondary small">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="new_username" class="form-control" placeholder="e.g. jdoe" required pattern="^[a-zA-Z0-9_]{3,20}$" title="Username must be alphanumeric, between 3 to 20 characters.">
                        </div>
                        
                        <!-- Initial Password -->
                        <div class="col-12 col-md-6">
                            <label for="new_password" class="form-label fw-semibold text-secondary small">Temporary Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="new_password" class="form-control" placeholder="Minimum 8 characters" required minlength="8">
                        </div>

                        <!-- First Name -->
                        <div class="col-12 col-md-4">
                            <label for="new_first_name" class="form-label fw-semibold text-secondary small">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="new_first_name" class="form-control" required placeholder="John">
                        </div>

                        <!-- Middle Name -->
                        <div class="col-12 col-md-4">
                            <label for="new_middle_name" class="form-label fw-semibold text-secondary small">Middle Name</label>
                            <input type="text" name="middle_name" id="new_middle_name" class="form-control" placeholder="Optional">
                        </div>

                        <!-- Last Name -->
                        <div class="col-12 col-md-4">
                            <label for="new_last_name" class="form-label fw-semibold text-secondary small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="new_last_name" class="form-control" required placeholder="Doe">
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-md-6">
                            <label for="new_email" class="form-label fw-semibold text-secondary small">Email Address</label>
                            <input type="email" name="email" id="new_email" class="form-control" placeholder="e.g. jdoe@example.com">
                        </div>

                        <!-- Contact No -->
                        <div class="col-12 col-md-6">
                            <label for="new_contact_no" class="form-label fw-semibold text-secondary small">Contact Number</label>
                            <input type="text" name="contact_no" id="new_contact_no" class="form-control" placeholder="e.g. 09171234567">
                        </div>

                        <!-- Job Title -->
                        <div class="col-12 col-md-6">
                            <label for="new_job_title" class="form-label fw-semibold text-secondary small">Clinic Job Title</label>
                            <input type="text" name="job_title" id="new_job_title" class="form-control" placeholder="e.g. BHW, Nurse, Midwife, Doctor">
                        </div>

                        <!-- Role dropdown -->
                        <div class="col-12 col-md-3">
                            <label for="new_role" class="form-label fw-semibold text-secondary small">Access Privilege <span class="text-danger">*</span></label>
                            <select name="role" id="new_role" class="form-select" required>
                                <option value="staff" selected>Staff Personnel</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>

                        <!-- Status dropdown -->
                        <div class="col-12 col-md-3">
                            <label for="new_status" class="form-label fw-semibold text-secondary small">Initial Status <span class="text-danger">*</span></label>
                            <select name="status" id="new_status" class="form-select" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Account</button>
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
});
</script>
