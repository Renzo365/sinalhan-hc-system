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
<script>
if (typeof $ !== 'undefined' && typeof $.fn.dataTable !== 'undefined') {
    $.extend(true, $.fn.dataTable.defaults, {
        pageLength: <?= (int)user_pref('default_page_size', 10) ?>,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        processing: true,
        language: {
            processing: '<div class="d-flex align-items-center justify-content-center p-3 text-secondary"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading records...</div>'
        }
    });
}

window.escapeHtml = function(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
};

// --- Global Top Slim Progress Bar Controller ---
window.SinalHealthLoader = (function() {
    let bar = null;
    let timer = null;
    let progress = 0;

    function getBar() {
        if (!bar) {
            bar = document.getElementById('sinalhealth-top-loader');
            if (!bar) {
                bar = document.createElement('div');
                bar.id = 'sinalhealth-top-loader';
                document.body.appendChild(bar);
            }
        }
        return bar;
    }

    function setProgress(p) {
        progress = p;
        const b = getBar();
        b.style.width = p + '%';
        if (p > 0) {
            b.classList.add('active');
        }
    }

    return {
        start: function() {
            if (timer) clearInterval(timer);
            setProgress(15);
            timer = setInterval(function() {
                if (progress < 65) {
                    setProgress(progress + 15);
                } else if (progress < 85) {
                    setProgress(progress + 5);
                } else if (progress < 95) {
                    setProgress(progress + 1);
                }
            }, 250);
        },
        done: function() {
            if (timer) clearInterval(timer);
            setProgress(100);
            setTimeout(function() {
                const b = getBar();
                b.classList.remove('active');
                setTimeout(function() {
                    b.style.width = '0%';
                }, 200);
            }, 300);
        }
    };
})();

// Global helpers to safely control button loading states
window.resetSubmitButton = function(btnOrForm) {
    if (!btnOrForm) return;
    let btn = btnOrForm;
    if (btnOrForm instanceof HTMLFormElement) {
        btn = btnOrForm.querySelector('button[type="submit"], input[type="submit"]');
    }
    if (!btn) return;
    if (btn.dataset.originalHtml) {
        btn.innerHTML = btn.dataset.originalHtml;
    }
    btn.disabled = false;
    btn.classList.remove('btn-loading');
    btn.removeAttribute('aria-busy');
    if (window.SinalHealthLoader) {
        window.SinalHealthLoader.done();
    }
};

window.setButtonLoading = function(btnOrForm, loadingText) {
    if (!btnOrForm) return;
    let btn = btnOrForm;
    if (btnOrForm instanceof HTMLFormElement) {
        btn = btnOrForm.querySelector('button[type="submit"]:not([data-no-loader]), input[type="submit"]:not([data-no-loader])');
    }
    if (!btn || btn.disabled || btn.classList.contains('btn-loading')) return;
    if (!btn.dataset.originalHtml) {
        btn.dataset.originalHtml = btn.innerHTML;
    }
    btn.classList.add('btn-loading');
    btn.setAttribute('aria-busy', 'true');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + escapeHtml(loadingText || 'Processing...');
    setTimeout(function() {
        btn.disabled = true;
    }, 0);
    if (window.SinalHealthLoader) {
        window.SinalHealthLoader.start();
    }
};

// Reset loader and submit buttons if restored from bfcache
window.addEventListener('pageshow', function() {
    if (window.SinalHealthLoader) {
        window.SinalHealthLoader.done();
    }
    document.querySelectorAll('button[type="submit"].btn-loading, input[type="submit"].btn-loading').forEach(window.resetSubmitButton);
});
</script>

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

    // Page navigation progress on internal link clicks
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:')) return;
        if (link.hasAttribute('download') || link.getAttribute('target') === '_blank') return;
        if (link.getAttribute('data-bs-toggle') || link.hasAttribute('data-no-loader') || link.hasAttribute('data-confirm')) return;

        try {
            const url = new URL(link.href, window.location.origin);
            if (url.origin === window.location.origin && url.pathname !== window.location.pathname) {
                window.SinalHealthLoader.start();
            }
        } catch (err) {}
    });

    // Universal Form Submission Loading & Double-Submit Protection
    document.addEventListener('submit', function(e) {
        // Critical safeguard: If custom validation, duplicate check, or confirmation modal called e.preventDefault(),
        // NEVER activate button loading or lock the form!
        if (e.defaultPrevented) {
            return;
        }

        const form = e.target;
        if (!form || form.hasAttribute('data-no-loader') || form.getAttribute('target') === '_blank') {
            return;
        }

        // Respect HTML5 client-side validation
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]:not([data-no-loader]), input[type="submit"]:not([data-no-loader])');
        if (!submitBtn || submitBtn.disabled || submitBtn.classList.contains('btn-loading')) {
            return;
        }

        // Determine smart verb based on context
        const btnText = (submitBtn.innerText || submitBtn.value || '').trim();
        let loadingVerb = 'Processing...';

        if (/log in|sign in|portal/i.test(btnText)) {
            loadingVerb = 'Authenticating...';
        } else if (/backup|snapshot/i.test(btnText)) {
            loadingVerb = 'Creating Snapshot...';
        } else if (/register|enroll/i.test(btnText)) {
            loadingVerb = 'Registering...';
        } else if (/save|record|create|add/i.test(btnText)) {
            loadingVerb = 'Saving...';
        } else if (/update|edit/i.test(btnText)) {
            loadingVerb = 'Updating...';
        } else if (/generate|filter|run/i.test(btnText)) {
            loadingVerb = 'Generating...';
        } else if (/restore/i.test(btnText)) {
            loadingVerb = 'Restoring Database...';
        } else if (/upload/i.test(btnText)) {
            loadingVerb = 'Uploading...';
        }

        window.setButtonLoading(submitBtn, loadingVerb);
    });

    // Automatically recover submit buttons if user interacts or changes inputs after a prevented submit
    document.addEventListener('input', function(e) {
        const form = e.target.closest('form');
        if (form) {
            const btn = form.querySelector('button[type="submit"].btn-loading, input[type="submit"].btn-loading');
            if (btn) {
                window.resetSubmitButton(btn);
            }
        }
    });

    document.addEventListener('change', function(e) {
        const form = e.target.closest('form');
        if (form) {
            const btn = form.querySelector('button[type="submit"].btn-loading, input[type="submit"].btn-loading');
            if (btn) {
                window.resetSubmitButton(btn);
            }
        }
    });

    // Reset stuck loading states when any bootstrap modal is closed
    document.addEventListener('hidden.bs.modal', function() {
        document.querySelectorAll('button[type="submit"].btn-loading, input[type="submit"].btn-loading').forEach(window.resetSubmitButton);
    });

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
                    Swal.showLoading();
                    if (window.SinalHealthLoader) window.SinalHealthLoader.start();
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

    // Inactivity Auto-Logout Monitor with Pre-Warning Modal (Configurable via system_settings & config/app.php)
    <?php if (isset($_SESSION['user_id'])): ?>
    <?php
        $idleTimeoutSec = (int)setting('session_idle_timeout', config('session.idle_timeout', 7200));
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

    // Global Web Audio Hospital Chime (Zero audio asset dependencies)
    window.playHospitalChime = function(force = false) {
        <?php if (function_exists('user_pref') && user_pref('queue_chime') === 'muted'): ?>
        if (!force) return;
        <?php endif; ?>
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            if (ctx.state === 'suspended') {
                ctx.resume();
            }
            
            function playTone(freq, startTime, duration) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, startTime);
                gain.gain.setValueAtTime(0, startTime);
                gain.gain.linearRampToValueAtTime(0.25, startTime + 0.04);
                gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(startTime);
                osc.stop(startTime + duration);
            }

            const now = ctx.currentTime;
            playTone(587.33, now, 0.20);        // D5
            playTone(880.00, now + 0.18, 0.40); // A5
        } catch (e) {
            console.warn('Web Audio chime could not play:', e);
        }
    };

    // Global 1-Click Clipboard Copy Handler
    document.addEventListener('click', function(e) {
        const copyBtn = e.target.closest('.copy-clipboard-btn');
        if (!copyBtn) return;
        const textToCopy = copyBtn.getAttribute('data-clipboard');
        if (!textToCopy) return;

        navigator.clipboard.writeText(textToCopy).then(() => {
            const originalHtml = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
            copyBtn.setAttribute('title', 'Copied!');
            setTimeout(() => {
                copyBtn.innerHTML = originalHtml;
                copyBtn.setAttribute('title', copyBtn.getAttribute('aria-label') || 'Copy');
            }, 1500);
        }).catch(err => {
            console.error('Failed to copy to clipboard: ', err);
        });
    });
});
</script>
</body>

</html>
