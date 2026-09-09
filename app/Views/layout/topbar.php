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
        <?php 
        $userFullName = $_SESSION['user_fullname'] ?? 'User';
        $userRole = $_SESSION['user_role'] ?? 'staff';
        $topbarRoleDisplay = 'Staff';
        if ($userRole === 'admin') {
            $topbarRoleDisplay = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1) ? 'Admin' : 'Co-Admin';
        }
        $avatarInitial = strtoupper(mb_substr(trim($userFullName), 0, 1, 'UTF-8'));
        if ($avatarInitial === '') {
            $avatarInitial = 'U';
        }
        ?>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center text-dark dropdown-toggle dropdown-toggle-no-caret" 
                    type="button" 
                    id="userProfileDropdown" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false"
                    style="box-shadow: none;">
                <!-- Circular Avatar -->
                <div class="user-avatar-circle me-2 flex-shrink-0" 
                     style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #fff; background-color: #0d9488; font-size: 0.95rem; user-select: none;">
                    <?= h($avatarInitial) ?>
                </div>
                <!-- User name and role -->
                <div class="text-start me-2 d-none d-sm-block lh-sm">
                    <div class="fw-bold text-dark" style="font-size: 0.875rem;"><?= h($userFullName) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;"><?= h($topbarRoleDisplay) ?></div>
                </div>
                <!-- Subtle Chevron icon -->
                <i class="bi bi-chevron-down text-muted small ms-1"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2 mt-2" aria-labelledby="userProfileDropdown" style="min-width: 210px; border-radius: 8px;">
                <!-- Mobile only user summary -->
                <li class="d-sm-none px-3 py-2 border-bottom mb-1 bg-light">
                    <div class="fw-bold text-dark small"><?= h($userFullName) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;"><?= h($topbarRoleDisplay) ?></div>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex align-items-center text-secondary" href="<?= url('/profile') ?>">
                        <i class="bi bi-person me-2 fs-6 text-muted"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex align-items-center text-secondary" href="<?= url('/profile#password-settings') ?>">
                        <i class="bi bi-shield-lock me-2 fs-6 text-muted"></i>
                        <span>Password Settings</span>
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form action="<?= url('/logout') ?>" method="POST" class="m-0 p-0" id="topbarLogoutForm">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item py-2 text-danger d-flex align-items-center w-100 border-0 bg-transparent" data-confirm="Are you sure you want to log out of the system?">
                            <i class="bi bi-box-arrow-right me-2 fs-6"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

<style>
.dropdown-toggle-no-caret::after {
    display: none !important;
}
.dropdown-menu .dropdown-item:active,
.dropdown-menu .dropdown-item:focus {
    background-color: rgba(13, 115, 119, 0.08);
    color: var(--color-primary, #0D7377);
}
.dropdown-menu .dropdown-item.text-danger:active,
.dropdown-menu .dropdown-item.text-danger:focus {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545 !important;
}
</style>

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
