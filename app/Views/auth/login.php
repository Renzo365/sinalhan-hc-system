<?php
$title = 'Login';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="login-body">
    <div class="container d-flex flex-column justify-content-center align-items-center min-vh-100 py-4 position-relative" style="z-index: 2;">
        <div class="card login-card">
            <!-- Header Section -->
            <div class="login-header-section">
                <i class="bi bi-heart-pulse-fill text-info login-header-icon"></i>
                <h2 class="login-title mb-1 text-white">Barangay Sinalhan</h2>
                <p class="mb-0 login-subtitle">Health Center Patient Management System</p>
            </div>
            
            <!-- Form Section -->
            <div class="card-body bg-white">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4 py-2 px-3 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0"></i>
                        <div><?= h($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= url('/login') ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Username Field -->
                    <div class="mb-4">
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
                                   class="form-control bg-light border-start-0 border-end-0" 
                                   placeholder="Enter password" 
                                   required>
                            <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mt-4 mb-2">
                        <button type="submit" class="btn btn-primary fw-semibold shadow-sm">
                            Log In <i class="bi bi-box-arrow-in-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer outside login card -->
        <footer class="login-footer">
            <div class="login-footer-pill">
                &copy; <?= date('Y') ?> BHC SINALHAN CENTER. All rights reserved.
            </div>
        </footer>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Clean up address bar query parameters (e.g. ?timeout=1) immediately
    if (window.location.search) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    <?php if (!empty($timeoutMessage)): ?>
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
    <?php endif; ?>
});
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
