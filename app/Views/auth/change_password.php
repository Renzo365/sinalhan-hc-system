<?php
$title = 'Change Password';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="login-body">
    <!-- Glow Decorative Elements -->
    <div class="login-glow-1"></div>
    <div class="login-glow-2"></div>

    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card login-card" style="max-width: 500px;">
            <!-- Header Section -->
            <div class="login-header-section">
                <i class="bi bi-shield-lock-fill text-warning fs-1 mb-2"></i>
                <h2 class="h4 mb-1 fw-bold text-white">First-Time Password Update</h2>
                <p class="mb-0 text-white-50 small">For security, you must update your default password before accessing the system.</p>
            </div>
            
            <!-- Form Section -->
            <div class="card-body p-4 bg-white">
                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger mb-3" role="alert">
                        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following:</div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= url('/change-password') ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Current Password -->
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold text-secondary">Current Temporary Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                            <input type="password" 
                                   name="current_password" 
                                   id="current_password" 
                                   class="form-control bg-light border-start-0 border-end-0" 
                                   placeholder="Enter current password" 
                                   required>
                            <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold text-secondary">New Secure Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" 
                                   name="new_password" 
                                   id="new_password" 
                                   class="form-control bg-light border-start-0 border-end-0" 
                                   placeholder="Min 8 characters" 
                                   required>
                            <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text text-muted small">Must be different from your current temporary password.</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold text-secondary">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-shield-check"></i></span>
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password" 
                                   class="form-control bg-light border-start-0 border-end-0" 
                                   placeholder="Confirm new password" 
                                   required>
                            <button class="btn btn-light border border-start-0 text-muted btn-toggle-password" type="button" tabindex="-1" title="Show password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            Save & Proceed <i class="bi bi-arrow-right-short ms-1 fs-5"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="card-footer bg-light text-center py-3 border-0">
                <form action="<?= url('/logout') ?>" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-link text-decoration-none text-muted small p-0 m-0 border-0" data-confirm="Are you sure you want to cancel and log out?">
                        Cancel & Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
