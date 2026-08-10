<?php
$title = 'Login';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="login-body">
    <!-- Glow Decorative Elements -->
    <div class="login-glow-1"></div>
    <div class="login-glow-2"></div>

    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card login-card">
            <!-- Header Section -->
            <div class="login-header-section">
                <i class="bi bi-heart-pulse-fill text-info fs-1 mb-2"></i>
                <h2 class="h4 mb-1 fw-bold text-white">Barangay Sinalhan</h2>
                <p class="mb-0 text-white-50 small">Health Center Patient Management System</p>
            </div>
            
            <!-- Form Section -->
            <div class="card-body p-4 bg-white">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= h($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= url('/login') ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Username Field -->
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold text-secondary">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                            <input type="text" 
                                   name="username" 
                                   id="username" 
                                   class="form-control bg-light border-start-0" 
                                   placeholder="Enter username" 
                                   value="<?= h($username ?? '') ?>" 
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control bg-light border-start-0" 
                                   placeholder="Enter password" 
                                   required>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            Log In <i class="bi bi-box-arrow-in-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="card-footer bg-light text-center py-3 border-0">
                <span class="text-muted small">&copy; <?= date('Y') ?> BHC SINALHAN CENTER</span>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($timeoutMessage)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Session Expired',
            text: <?= json_encode($timeoutMessage) ?>,
            confirmButtonColor: '#0D7377',
            confirmButtonText: 'OK',
            allowOutsideClick: false
        });
    }
});
</script>
<?php endif; ?>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
