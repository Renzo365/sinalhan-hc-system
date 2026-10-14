<?php
$title = 'Database Backup & Disaster Recovery';
require dirname(__DIR__) . '/layout/header.php';
?>

<!-- Page Header & Quick Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">
            <i class="bi bi-database-fill-gear text-primary me-2"></i>Database Backup & Disaster Recovery
        </h2>
        <p class="text-secondary small mb-0">Generate point-in-time SQL snapshots, restore data safely with step-up verification, or import offsite archives.</p>
    </div>
    
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Upload Backup Button -->
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center px-3 py-2" data-bs-toggle="modal" data-bs-target="#uploadBackupModal">
            <i class="bi bi-cloud-arrow-up me-2 fs-6"></i>
            <span>Upload Archive</span>
        </button>

        <!-- Create Backup Button / Dropdown -->
        <?php if ($hasGzip): ?>
            <div class="btn-group">
                <form action="<?= url('/backup') ?>" method="POST" class="d-inline m-0 p-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="format" value="sql">
                    <button type="submit" class="btn btn-primary d-flex align-items-center px-3 py-2" data-confirm="Generate a new standard SQL database snapshot now?">
                        <i class="bi bi-database-down me-2 fs-6"></i>
                        <span>Create Backup (.sql)</span>
                    </button>
                </form>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split px-2" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <form action="<?= url('/backup') ?>" method="POST" class="m-0 p-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="format" value="gzip">
                            <button type="submit" class="dropdown-item py-2 d-flex align-items-center" data-confirm="Generate a compressed GZIP database snapshot (.sql.gz) now?">
                                <i class="bi bi-file-earmark-zip text-primary me-2"></i>
                                <div>
                                    <span class="fw-semibold">Compressed Backup (.sql.gz)</span>
                                    <small class="d-block text-muted">Saves 85%+ disk space</small>
                                </div>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        <?php else: ?>
            <form action="<?= url('/backup') ?>" method="POST" class="m-0 p-0">
                <?= csrf_field() ?>
                <input type="hidden" name="format" value="sql">
                <button type="submit" class="btn btn-primary d-flex align-items-center px-3 py-2" data-confirm="Generate a new database snapshot now?">
                    <i class="bi bi-database-down me-2 fs-6"></i>
                    <span>Create Backup</span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Metrics & Health Status Cards -->
<div class="row g-3 mb-4">
    <!-- Total Backups -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-premium h-100 border-0 shadow-sm">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="rounded-3 p-3 bg-primary-subtle text-primary me-3">
                    <i class="bi bi-archive-fill fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Total Snapshots</span>
                    <h4 class="mb-0 fw-bold text-dark"><?= (int)$totalBackups ?></h4>
                    <small class="text-secondary"><?= (int)$validCount ?> ready to restore</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Disk Storage Used -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-premium h-100 border-0 shadow-sm">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="rounded-3 p-3 bg-info-subtle text-info me-3">
                    <i class="bi bi-hdd-fill fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Disk Usage</span>
                    <h4 class="mb-0 fw-bold text-dark"><?= h($totalDiskSize) ?></h4>
                    <small class="text-secondary font-monospace"><?= h($backupLocation) ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Backup -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-premium h-100 border-0 shadow-sm">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="rounded-3 p-3 bg-success-subtle text-success me-3">
                    <i class="bi bi-clock-history fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Latest Snapshot</span>
                    <h6 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 170px;" title="<?= h($lastBackupTime) ?>">
                        <?= h($lastBackupTime) ?>
                    </h6>
                    <small class="text-success fw-medium">Point-in-time ready</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Disaster Recovery Health -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-premium h-100 border-0 shadow-sm">
            <div class="card-body p-3 d-flex align-items-center">
                <?php 
                $quarantinedCount = ($corruptCount ?? 0) + ($incompatibleCount ?? 0);
                if ($quarantinedCount > 0): 
                ?>
                    <div class="rounded-3 p-3 bg-danger-subtle text-danger me-3">
                        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Snapshot Health</span>
                        <h6 class="mb-0 fw-bold text-danger"><?= (int)$quarantinedCount ?> Quarantined File(s)</h6>
                        <small class="text-danger">
                            <?= (int)($incompatibleCount ?? 0) ?> incompatible, <?= (int)($corruptCount ?? 0) ?> empty
                        </small>
                    </div>
                <?php else: ?>
                    <div class="rounded-3 p-3 bg-success-subtle text-success me-3">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Recovery Status</span>
                        <h6 class="mb-0 fw-bold text-success">Healthy & Intact</h6>
                        <small class="text-muted">All files verified</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Clinical Advisory Banner -->
<div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4 rounded-3">
    <i class="bi bi-shield-lock-fill fs-3 text-primary flex-shrink-0"></i>
    <div class="small text-secondary mb-0">
        <strong class="text-dark">Healthcare Data Safeguard (RA 10173 Compliance):</strong>
        Database snapshots contain confidential patient medical records. Download copies to encrypted, offline storage regularly. When restoring, the system enforces <strong>Super Admin password confirmation</strong> and <strong>automatically creates an emergency rollback snapshot</strong> before modifying live records.
    </div>
</div>

<!-- Backup History Card -->
<div class="card card-premium border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h3 class="card-title h6 mb-0 fw-bold text-dark d-flex align-items-center">
            <i class="bi bi-clock-history text-primary me-2"></i>Backup Snapshot History
        </h3>
        <span class="badge bg-light text-secondary border fw-normal small">
            <?= count($files) ?> total files
        </span>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="backupTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Snapshot Filename</th>
                        <th>Format</th>
                        <th>Created Date</th>
                        <th>File Size</th>
                        <th>Health Status</th>
                        <th class="pe-4 text-end">Disaster Recovery Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($files)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-database-exclamation d-block fs-2 mb-2 text-secondary"></i>
                                <span class="fw-semibold">No database backup files found on disk.</span>
                                <p class="text-muted small mb-0">Click "Create Backup" above to generate your first recovery snapshot.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                            <tr>
                                <!-- Filename & Tags -->
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <?php if ($file['format'] === 'GZ'): ?>
                                            <i class="bi bi-file-earmark-zip-fill text-indigo fs-5 me-2" style="color: #6f42c1;"></i>
                                        <?php else: ?>
                                            <i class="bi bi-filetype-sql text-primary fs-5 me-2"></i>
                                        <?php endif; ?>
                                        <div>
                                            <span class="fw-bold font-monospace text-dark d-block"><?= h($file['filename']) ?></span>
                                            <?php if ($file['is_safety']): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small py-0 px-2">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Pre-Restore Rollback Snapshot
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Format -->
                                <td>
                                    <?php if ($file['format'] === 'GZ'): ?>
                                        <span class="badge bg-purple-subtle text-purple border" style="background-color: #f3e8ff; color: #6b21a8; border-color: #e9d5ff !important;">GZIP (.gz)</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border">SQL</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Created Date -->
                                <td class="text-secondary"><?= h($file['created_at']) ?></td>

                                <!-- File Size -->
                                <td>
                                    <?php if ($file['raw_size'] === 0): ?>
                                        <span class="badge bg-danger text-white">0 B</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border font-monospace"><?= h($file['size']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Health Status -->
                                <td>
                                    <?php if ($file['status'] === 'valid'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center px-2 py-1" title="<?= h($file['status_reason'] ?? 'Verified SinalHealth Snapshot') ?>">
                                            <i class="bi bi-check-circle-fill me-1"></i> Valid Snapshot
                                        </span>
                                    <?php elseif ($file['status'] === 'incompatible'): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-inline-flex align-items-center px-2 py-1" title="<?= h($file['status_reason'] ?? 'Incompatible foreign schema') ?>">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Incompatible Schema
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center px-2 py-1" title="Corrupt or 0-byte file. Cannot be restored.">
                                            <i class="bi bi-exclamation-octagon-fill me-1"></i> Empty / Corrupt
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Action Buttons -->
                                <td class="pe-4 text-end" style="white-space: nowrap;">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <!-- Download -->
                                        <a href="<?= url('/backup/download?filename=' . urlencode($file['filename'])) ?>" 
                                           class="btn btn-sm btn-outline-primary px-2 py-1" 
                                           title="Download snapshot to local disk">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>

                                        <!-- Restore Button -->
                                        <?php if ($file['status'] === 'valid'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning text-dark px-2 py-1 btn-restore-trigger" 
                                                    data-filename="<?= h($file['filename']) ?>"
                                                    data-size="<?= h($file['size']) ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#restoreModal"
                                                    title="Restore database from this snapshot">
                                                <i class="bi bi-arrow-repeat me-1 text-warning-emphasis"></i>Restore
                                            </button>
                                        <?php elseif ($file['status'] === 'incompatible'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary px-2 py-1 disabled" 
                                                    disabled 
                                                    title="Cannot restore: Foreign database schema (missing SinalHealth tables)">
                                                <i class="bi bi-slash-circle me-1"></i>Restore
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary px-2 py-1 disabled" 
                                                    disabled 
                                                    title="Cannot restore empty or corrupt file">
                                                <i class="bi bi-slash-circle me-1"></i>Restore
                                            </button>
                                        <?php endif; ?>

                                        <!-- Delete -->
                                        <form action="<?= url('/backup/delete') ?>" method="POST" class="d-inline m-0 p-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="filename" value="<?= h($file['filename']) ?>">
                                            <button type="submit" 
                                                    class="btn btn-sm btn-outline-danger border-0 px-2 py-1" 
                                                    title="Delete this snapshot from disk" 
                                                    data-confirm="Are you sure you want to permanently delete backup file: <?= h($file['filename']) ?>? This action cannot be undone.">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
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

<!-- ========================================== -->
<!-- 1. Secure Database Restore Modal (3-Layer) -->
<!-- ========================================== -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning-subtle text-dark border-bottom border-warning-subtle">
                <h5 class="modal-title fw-bold d-flex align-items-center" id="restoreModalLabel">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>
                    Confirm Database Restore
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="<?= url('/backup/restore') ?>" method="POST" id="restoreForm">
                <?= csrf_field() ?>
                <input type="hidden" name="filename" id="restoreFilenameInput" value="">

                <div class="modal-body p-4">
                    <!-- Target Snapshot Info -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="small text-muted mb-1 text-uppercase fw-semibold">Target Snapshot to Restore</div>
                        <div class="fw-bold font-monospace text-primary-dark fs-6" id="restoreFilenameDisplay">filename.sql</div>
                        <div class="text-secondary small mt-1">Snapshot Size: <span class="fw-semibold text-dark" id="restoreSizeDisplay">0 MB</span></div>
                    </div>

                    <!-- Warning Callout -->
                    <div class="alert alert-danger border-0 small d-flex gap-2 p-3 mb-3">
                        <i class="bi bi-radioactive fs-5 flex-shrink-0 text-danger"></i>
                        <div>
                            <strong>Critical Warning:</strong> Restoring this backup will replace current patient records, consultations, and appointments with data from this snapshot.
                        </div>
                    </div>

                    <!-- Pre-restore Automatic Safety Guarantee -->
                    <div class="bg-success-subtle text-success-emphasis p-3 rounded-3 small border border-success-subtle mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check fs-5 flex-shrink-0 text-success"></i>
                        <div>
                            <strong>Automatic Rollback Safety Net:</strong> The system will automatically generate a full safety backup of the current database before starting the restore.
                        </div>
                    </div>

                    <!-- Security Layer 1: Super Admin Password -->
                    <div class="mb-3">
                        <label for="admin_password" class="form-label fw-bold small text-dark">
                            <i class="bi bi-key-fill text-primary me-1"></i>1. Enter Super Admin Password:
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="admin_password" 
                               name="admin_password" 
                               placeholder="Enter your login password" 
                               required 
                               autocomplete="current-password">
                        <div class="form-text small">Re-authenticate your identity to authorize this destructive operation.</div>
                    </div>

                    <!-- Security Layer 2: Intentional Word Confirmation -->
                    <div class="mb-0">
                        <label for="confirmation_phrase" class="form-label fw-bold small text-dark">
                            <i class="bi bi-input-cursor-text text-danger me-1"></i>2. Type <span class="text-danger fw-bolder font-monospace">RESTORE</span> to unlock:
                        </label>
                        <input type="text" 
                               class="form-control font-monospace" 
                               id="confirmation_phrase" 
                               name="confirmation_phrase" 
                               placeholder="RESTORE" 
                               required 
                               autocomplete="off">
                        <div class="form-text small">Type the word <strong>RESTORE</strong> in all caps to enable the restore button.</div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger d-flex align-items-center" id="btnExecuteRestore" disabled>
                        <i class="bi bi-arrow-repeat me-2"></i>
                        <span id="btnExecuteRestoreText">Execute Database Restore</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. Upload Offsite Backup Archive Modal    -->
<!-- ========================================== -->
<div class="modal fade" id="uploadBackupModal" tabindex="-1" aria-labelledby="uploadBackupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-white border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="uploadBackupModalLabel">
                    <i class="bi bi-cloud-arrow-up-fill text-primary me-2 fs-5"></i>
                    Upload Offsite Backup Archive
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="<?= url('/backup/upload') ?>" method="POST" enctype="multipart/form-data" id="uploadBackupForm">
                <?= csrf_field() ?>

                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">
                        Import an existing backup archive from an offsite USB or cloud drive into the SinalHealth recovery repository.
                    </p>

                    <div class="mb-3">
                        <label for="backup_file" class="form-label fw-bold small text-dark">Select Backup File (.sql or .sql.gz):</label>
                        <input type="file" 
                               class="form-control" 
                               id="backup_file" 
                               name="backup_file" 
                               accept=".sql,.gz,.sql.gz" 
                               required>
                        <div class="form-text small">Maximum upload size: 50 MB. Accepted formats: Standard SQL (<code>.sql</code>) or GZIP SQL (<code>.sql.gz</code>).</div>
                    </div>

                    <div class="alert alert-secondary border-0 small mb-0 p-3 rounded-3">
                        <i class="bi bi-info-circle me-1 text-primary"></i>
                        Uploaded files will undergo automatic structural header validation before being registered in the backup history table.
                    </div>
                </div>

                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center" id="btnUploadSubmit">
                        <i class="bi bi-upload me-2"></i>
                        <span>Upload & Validate</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DataTable
    <?php if (!empty($files)): ?>
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#backupTable').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "order": [[2, "desc"]], // Sort by created date descending
                "columnDefs": [
                    { "orderable": false, "targets": [1, 4, 5] } // Non-sortable format, status, actions
                ],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Filter backups by filename...",
                    "paginate": {
                        "previous": "<i class='bi bi-chevron-left'></i>",
                        "next": "<i class='bi bi-chevron-right'></i>"
                    }
                }
            });
        }
    <?php endif; ?>

    // 2. Restore Modal Handling
    const restoreModal = document.getElementById('restoreModal');
    const restoreFilenameInput = document.getElementById('restoreFilenameInput');
    const restoreFilenameDisplay = document.getElementById('restoreFilenameDisplay');
    const restoreSizeDisplay = document.getElementById('restoreSizeDisplay');
    const adminPasswordInput = document.getElementById('admin_password');
    const confirmationPhraseInput = document.getElementById('confirmation_phrase');
    const btnExecuteRestore = document.getElementById('btnExecuteRestore');
    const btnExecuteRestoreText = document.getElementById('btnExecuteRestoreText');
    const restoreForm = document.getElementById('restoreForm');

    // Trigger buttons
    document.querySelectorAll('.btn-restore-trigger').forEach(function(button) {
        button.addEventListener('click', function() {
            const filename = this.getAttribute('data-filename');
            const size = this.getAttribute('data-size');

            restoreFilenameInput.value = filename;
            restoreFilenameDisplay.textContent = filename;
            restoreSizeDisplay.textContent = size;

            // Reset inputs
            adminPasswordInput.value = '';
            confirmationPhraseInput.value = '';
            btnExecuteRestore.disabled = true;
            btnExecuteRestoreText.textContent = 'Execute Database Restore';
        });
    });

    // Realtime confirmation validation (typing RESTORE)
    function validateRestoreReadiness() {
        const isPhraseMatched = confirmationPhraseInput.value.trim().toUpperCase() === 'RESTORE';
        const hasPassword = adminPasswordInput.value.length > 0;
        btnExecuteRestore.disabled = !(isPhraseMatched && hasPassword);
    }

    confirmationPhraseInput.addEventListener('input', validateRestoreReadiness);
    adminPasswordInput.addEventListener('input', validateRestoreReadiness);

    // Form submission spinner & unclosable safety overlay
    restoreForm.addEventListener('submit', function() {
        btnExecuteRestore.disabled = true;
        btnExecuteRestoreText.textContent = 'Restoring Database... Please wait.';
        btnExecuteRestore.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');

        // Close bootstrap modal to prevent layered modal stacking
        const modalInstance = bootstrap.Modal.getInstance(restoreModal);
        if (modalInstance) {
            modalInstance.hide();
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Restoring Database Snapshot',
                html: 'Restoring tables and clinical records.<br><strong class="text-danger mt-2 d-inline-block"><i class="bi bi-exclamation-triangle-fill me-1"></i> Do not close, reload, or navigate away from this window.</strong>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        if (window.SinalHealthLoader) {
            window.SinalHealthLoader.start();
        }
    });

    // Upload form submission feedback
    const uploadBackupForm = document.getElementById('uploadBackupForm');
    const btnUploadSubmit = document.getElementById('btnUploadSubmit');
    if (uploadBackupForm && btnUploadSubmit) {
        uploadBackupForm.addEventListener('submit', function() {
            btnUploadSubmit.disabled = true;
            btnUploadSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Uploading & Validating...';
        });
    }
});
</script>
