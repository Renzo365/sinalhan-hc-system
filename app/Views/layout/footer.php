<?php if (isset($_SESSION['user_id']) && !isset($disable_layout)): ?>
        </div> <!-- Close app-content -->
    </div> <!-- Close app-main -->
</div> <!-- Close app-wrapper -->
<?php endif; ?>

<!-- Local JS Vendor Files -->
<script src="<?= asset('vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= asset('vendor/chartjs/chart.umd.js') ?>"></script>
<script src="<?= asset('vendor/datatables/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= asset('vendor/datatables/js/dataTables.bootstrap5.min.js') ?>"></script>

<!-- SweetAlert Flash Messages & System-Wide Event Handlers -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show SweetAlert success notifications
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: <?= json_encode($_SESSION['success_message']) ?>,
            confirmButtonColor: '#0D7377',
            timer: 4000,
            timerProgressBar: true
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    // Show SweetAlert login welcome notification
    <?php if (isset($_SESSION['login_welcome'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Welcome Back!',
            text: 'You have successfully logged in as ' + <?= json_encode($_SESSION['user_fullname'] ?? $_SESSION['username'] ?? 'User') ?> + '.',
            confirmButtonColor: '#0D7377',
            timer: 3500,
            timerProgressBar: true
        });
        <?php unset($_SESSION['login_welcome']); ?>
    <?php endif; ?>

    // Show SweetAlert error notifications
    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: <?= json_encode($_SESSION['error_message']) ?>,
            confirmButtonColor: '#0D7377'
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    // Confirm Destructive Actions (Delete, Log Out, Database backup etc.)
    const confirmActions = document.querySelectorAll('[data-confirm]');
    confirmActions.forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
            const form = this.closest('form');
            
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0D7377',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (form) {
                        form.submit();
                    } else if (this.href) {
                        window.location.href = this.href;
                    }
                }
            });
        });
    });

    // Global Password Visibility Toggle
    document.querySelectorAll('.btn-toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const inputGroup = this.closest('.input-group');
            if (!inputGroup) return;
            const input = inputGroup.querySelector('input[type="password"], input[type="text"]');
            if (!input) return;
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
                this.setAttribute('title', 'Hide password');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
                this.setAttribute('title', 'Show password');
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });

    // Global Bootstrap 5 Modal Backdrop & Scroll Trap Safeguard
    document.addEventListener('hidden.bs.modal', function () {
        if (!document.querySelector('.modal.show')) {
            document.querySelectorAll('.modal-backdrop').forEach(function(el) {
                el.remove();
            });
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }
    });

    // Inactivity Auto-Logout Monitor with Pre-Warning Modal (Configurable via config/app.php)
    <?php if (isset($_SESSION['user_id'])): ?>
    <?php
        $idleTimeoutSec = (int)config('session.idle_timeout', 7200);
        $warningThresholdSec = max(60, $idleTimeoutSec - 120); // Show warning 2 minutes before timeout
    ?>
    (function() {
        const TOTAL_TIMEOUT_MS = <?= $idleTimeoutSec ?> * 1000;
        const WARNING_THRESHOLD_MS = <?= $warningThresholdSec ?> * 1000;
        const CHECK_INTERVAL_MS = 5 * 1000;             // Check wall-clock every 5 seconds
        const SERVER_SYNC_INTERVAL_MS = 4 * 60 * 1000;  // Sync server session every 4 minutes while user is active
        const PING_URL = <?= json_encode(url('/api/session-ping')) ?>;
        const TIMEOUT_URL = <?= json_encode(url('/logout?timeout=1')) ?>;

        let lastActivity = Date.now();
        let lastServerSyncTime = Date.now();
        let isWarningVisible = false;
        let warningCountdownInterval = null;

        function recordActivity() {
            // Only update local activity if warning modal is not currently open
            if (!isWarningVisible) {
                lastActivity = Date.now();
            }
        }

        // Throttled activity listener (records at most once every 1 second)
        let lastRecordedTime = 0;
        function throttledActivity() {
            const now = Date.now();
            if (now - lastRecordedTime > 1000) {
                lastRecordedTime = now;
                recordActivity();
            }
        }

        ['mousedown', 'keydown', 'touchstart', 'click'].forEach(function(eventType) {
            window.addEventListener(eventType, throttledActivity, { passive: true });
        });

        // Ping the server to refresh PHP session activity
        function keepSessionAlive(isBackground = false) {
            fetch(PING_URL, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    lastServerSyncTime = Date.now();
                    if (!isBackground) {
                        lastActivity = Date.now();
                        isWarningVisible = false;
                        if (warningCountdownInterval) {
                            clearInterval(warningCountdownInterval);
                            warningCountdownInterval = null;
                        }
                        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                            Swal.close();
                        }
                    }
                } else if (response.status === 401) {
                    // Session already expired on server
                    window.location.href = TIMEOUT_URL;
                }
            })
            .catch(() => {
                if (!isBackground) {
                    // If manual keep-alive network fails, still reset local activity once
                    lastActivity = Date.now();
                    isWarningVisible = false;
                }
            });
        }

        function showPreTimeoutWarning(remainingMs) {
            if (isWarningVisible || typeof Swal === 'undefined') return;
            isWarningVisible = true;

            let secondsLeft = Math.max(1, Math.round(remainingMs / 1000));

            Swal.fire({
                icon: 'warning',
                title: 'Session Expiring Soon',
                html: 'You have been inactive. For security, you will be logged out in <strong id="sessionTimeoutCountdown" class="text-danger">' + secondsLeft + '</strong> seconds.<br><span class="text-muted small">Click below to continue your current session.</span>',
                confirmButtonColor: '#0D7377',
                confirmButtonText: '<i class="bi bi-arrow-repeat me-1"></i> Stay Logged In',
                showCancelButton: true,
                cancelButtonColor: '#6c757d',
                cancelButtonText: 'Log Out Now',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    keepSessionAlive(false);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = <?= json_encode(url('/logout')) ?>;
                }
            });

            // Update countdown timer inside modal every second
            if (warningCountdownInterval) clearInterval(warningCountdownInterval);
            warningCountdownInterval = setInterval(() => {
                secondsLeft--;
                const countdownEl = document.getElementById('sessionTimeoutCountdown');
                if (countdownEl) {
                    countdownEl.textContent = secondsLeft;
                }
                if (secondsLeft <= 0) {
                    clearInterval(warningCountdownInterval);
                    warningCountdownInterval = null;
                    window.location.href = TIMEOUT_URL;
                }
            }, 1000);
        }

        // Active Wall-Clock Heartbeat Check
        setInterval(function() {
            const now = Date.now();
            const idleTime = now - lastActivity;

            if (idleTime >= TOTAL_TIMEOUT_MS) {
                // Time is up -> smoothly redirect to logout
                window.location.href = TIMEOUT_URL;
            } else if (idleTime >= WARNING_THRESHOLD_MS && !isWarningVisible) {
                // Within 2-minute warning window
                const remaining = TOTAL_TIMEOUT_MS - idleTime;
                showPreTimeoutWarning(remaining);
            } else if (idleTime < (2 * 60 * 1000) && (now - lastServerSyncTime >= SERVER_SYNC_INTERVAL_MS)) {
                // User has interacted recently; refresh server-side PHP session in background so it never expires during active use
                lastServerSyncTime = now;
                keepSessionAlive(true);
            }
        }, CHECK_INTERVAL_MS);
    })();
    <?php endif; ?>
});
</script>
</body>
</html>
