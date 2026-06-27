<header class="app-topbar">
    <div class="d-flex align-items-center">
        <!-- Sidebar Toggle for Mobile -->
        <button class="btn btn-outline-primary d-lg-none me-3" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="topbar-title h4 mb-0 d-none d-sm-block">
            Barangay Sinalhan Health Center
        </h1>
    </div>
    
    <div class="d-flex align-items-center">
        <!-- User Profile Info -->
        <div class="text-end me-3">
            <span class="fw-bold d-block text-dark"><?= h($_SESSION['user_fullname'] ?? 'User') ?></span>
            <span class="badge bg-light text-primary border border-primary-subtle text-capitalize small">
                <?= h($_SESSION['user_role'] ?? 'Staff') ?>
            </span>
        </div>
        
        <!-- Secure POST Logout Form -->
        <form action="<?= url('/logout') ?>" method="POST" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm d-flex align-items-center" data-confirm="Are you sure you want to log out of the system?">
                <i class="bi bi-box-arrow-right me-1"></i>
                <span class="d-none d-md-inline">Log Out</span>
            </button>
        </form>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.querySelector('.app-sidebar');
    
    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', function() {
            appSidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = appSidebar.contains(event.target);
            const isClickToggle = sidebarToggle.contains(event.target);
            
            if (!isClickInsideSidebar && !isClickToggle && appSidebar.classList.contains('show')) {
                appSidebar.classList.remove('show');
            }
        });
    }
});
</script>
