<?php if (isset($_SESSION['user_id']) && !isset($disable_layout)): ?>
        </div> <!-- Close app-content -->
    </div> <!-- Close app-main -->
</div> <!-- Close app-wrapper -->
<?php endif; ?>

<!-- Local JS Vendor Files -->
<script src="<?= asset('vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= asset('vendor/flatpickr/flatpickr.min.js') ?>"></script>
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

    // Inactivity Auto-Logout Monitor (15 Minutes)
    <?php if (isset($_SESSION['user_id'])): ?>
    (function() {
        const IDLE_TIMEOUT_MS = 15 * 60 * 1000; // 15 minutes of inactivity
        let idleTimer;

        function resetIdleTimer() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(onIdleTimeout, IDLE_TIMEOUT_MS);
        }

        function onIdleTimeout() {
            // Smoothly auto-logout and redirect to login page with timeout flag
            window.location.href = <?= json_encode(url('/login?timeout=1')) ?>;
        }

        // Reset timer on any user interaction
        ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function(eventType) {
            window.addEventListener(eventType, resetIdleTimer, { passive: true });
        });

        // Initialize timer
        resetIdleTimer();
    })();
    <?php endif; ?>
});
</script>
</body>
</html>
