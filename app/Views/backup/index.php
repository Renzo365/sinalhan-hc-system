<?php
$title = 'Database Backups';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Database Backups</h2>
        <p class="text-secondary small mb-0">Generate SQL dumps of the database system, download backups, or clean old records.</p>
    </div>
    
    <form action="<?= url('/backup') ?>" method="POST" class="m-0 p-0">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary d-flex align-items-center px-3 py-2" data-confirm="Are you sure you want to trigger a database backup now?">
            <i class="bi bi-database-down me-2 fs-5"></i>
            <span>Create Backup</span>
        </button>
    </form>
</div>

<div class="row g-4 mb-4">
    <!-- Info Stats Cards -->
    <div class="col-12 col-md-6">
        <div class="card card-premium h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-info-circle-fill text-primary me-2"></i>Backup Information
                </h3>
            </div>
            <div class="card-body p-4">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-3 d-flex justify-content-between">
                        <span class="text-muted">Last Backup Generated:</span>
                        <span class="fw-bold text-dark"><?= h($lastBackupTime) ?></span>
                    </li>
                    <li class="mb-3 d-flex justify-content-between">
                        <span class="text-muted">Storage Path:</span>
                        <span class="font-monospace text-secondary fw-semibold"><?= h($backupLocation) ?></span>
                    </li>
                    <li class="mb-0 d-flex justify-content-between">
                        <span class="text-muted">Server Time:</span>
                        <span class="fw-bold text-secondary"><?= date('Y-m-d h:i A') ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-6">
        <div class="card card-premium h-100 bg-light border-0">
            <div class="card-body p-4 d-flex flex-column justify-content-center text-center">
                <i class="bi bi-shield-check text-success fs-1 mb-2"></i>
                <h4 class="fw-bold text-dark mb-2">Data Protection</h4>
                <p class="text-secondary small mx-auto mb-0" style="max-width: 400px;">
                    It is recommended to generate backups weekly. Download generated SQL files to a secure offline hard drive to prevent data loss in case of hardware failures.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card card-premium">
    <div class="card-header bg-white py-3 border-bottom">
        <h3 class="card-title h6 mb-0 fw-bold text-dark">
            <i class="bi bi-list-nested text-primary me-2"></i>Backup History
        </h3>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center small" id="backupTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-start ps-4">Backup Filename</th>
                        <th>Created Date</th>
                        <th>File Size</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($files)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-database-exclamation d-block fs-3 mb-2 text-muted"></i>
                                No database backup files exist.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                            <tr>
                                <td class="text-start ps-4 fw-bold font-monospace text-primary-dark">
                                    <i class="bi bi-file-earmark-code-fill text-secondary me-2"></i><?= h($file['filename']) ?>
                                </td>
                                <td><?= h($file['created_at']) ?></td>
                                <td><span class="badge bg-secondary"><?= h($file['size']) ?></span></td>
                                <td class="pe-4 text-end" style="white-space: nowrap;">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= url('/backup/download?filename=' . urlencode($file['filename'])) ?>" class="btn btn-sm btn-outline-primary px-3 py-1" title="Download SQL File">
                                            <i class="bi bi-download me-1"></i> Download
                                        </a>
                                        
                                        <form action="<?= url('/backup/delete') ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="filename" value="<?= h($file['filename']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2" title="Delete Backup" data-confirm="Are you sure you want to delete backup file: <?= h($file['filename']) ?>? This action is permanent.">
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

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($files)): ?>
        $('#backupTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[1, "desc"]], // Sort by date descending
            "columnDefs": [
                { "orderable": false, "targets": 3 } // Actions
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
