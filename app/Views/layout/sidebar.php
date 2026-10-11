<?php
$requestUri = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME'] ?? '');
$uri = '/';
if ($basePath !== '' && strpos($requestUri, $basePath) === 0) {
    $uri = substr($requestUri, strlen($basePath));
}
$uri = explode('?', $uri)[0];
if ($uri === '') {
    $uri = '/';
}

if (!function_exists('isActive')) {
    function isActive($path, $currentUri) {
        if ($path === '/dashboard') {
            return ($currentUri === '/' || $currentUri === '/dashboard') ? 'active' : '';
        }
        return (strpos($currentUri, $path) === 0) ? 'active' : '';
    }
}
?>
<aside class="app-sidebar" aria-label="Sidebar Navigation">
    <nav class="sidebar-menu" aria-label="Main Navigation">
        <a href="<?= url('/dashboard') ?>" class="sidebar-item <?= isActive('/dashboard', $uri) ?>" style="min-height: 44px;">
            <i class="bi bi-speedometer2" aria-hidden="true"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="<?= url('/patients') ?>" class="sidebar-item <?= isActive('/patients', $uri) ?>" style="min-height: 44px;">
            <i class="bi bi-people" aria-hidden="true"></i>
            <span>Patients</span>
        </a>
        
        <a href="<?= url('/prenatal') ?>" class="sidebar-item <?= (isActive('/prenatal', $uri) || isActive('/maternal', $uri)) ? 'active' : '' ?>" style="min-height: 44px;">
            <i class="bi bi-heart-pulse-fill" aria-hidden="true"></i>
            <span>Prenatal Care</span>
        </a>

        <a href="<?= url('/well-baby') ?>" class="sidebar-item <?= isActive('/well-baby', $uri) ?>" style="min-height: 44px;">
            <i class="bi bi-emoji-smile-fill" aria-hidden="true"></i>
            <span>Well-Baby &amp; EPI</span>
        </a>
        
        <a href="<?= url('/appointments') ?>" class="sidebar-item <?= isActive('/appointments', $uri) ?>" style="min-height: 44px;">
            <i class="bi bi-calendar-event" aria-hidden="true"></i>
            <span>Appointments</span>
        </a>
        
        <a href="<?= url('/queue') ?>" class="sidebar-item <?= isActive('/queue', $uri) ?>" style="min-height: 44px;">
            <i class="bi bi-card-list" aria-hidden="true"></i>
            <span>Queue</span>
        </a>

        <a href="<?= url('/reports') ?>" class="sidebar-item <?= isActive('/reports', $uri) ?>" style="min-height: 44px;">
            <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i>
            <span>Reports</span>
        </a>

        <!-- Admin Only Section -->
        <?php if (is_admin()): ?>
            <div class="sidebar-section-title">Administration</div>
            
            <a href="<?= url('/archive') ?>" class="sidebar-item <?= isActive('/archive', $uri) ?>" style="min-height: 44px;">
                <i class="bi bi-archive" aria-hidden="true"></i>
                <span>Archive</span>
            </a>
            
            <a href="<?= url('/users') ?>" class="sidebar-item <?= isActive('/users', $uri) ?>" style="min-height: 44px;">
                <i class="bi bi-person-gear" aria-hidden="true"></i>
                <span>Users</span>
            </a>

            <!-- System Security & Infrastructure (Super Admin Exclusive) -->
            <?php if (is_super_admin()): ?>
                <a href="<?= url('/audit-logs') ?>" class="sidebar-item <?= isActive('/audit-logs', $uri) ?>" style="min-height: 44px;">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    <span>Audit Logs</span>
                </a>
                
                <a href="<?= url('/backup') ?>" class="sidebar-item <?= isActive('/backup', $uri) ?>" style="min-height: 44px;">
                    <i class="bi bi-database-down" aria-hidden="true"></i>
                    <span>Backup</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Account Section (All Staff & Admins) -->
        <div class="sidebar-section-title">Account</div>

        <a href="<?= url('/profile') ?>" class="sidebar-item <?= isActive('/profile', $uri) ?>" style="min-height: 44px;">
            <i class="bi bi-person-circle" aria-hidden="true"></i>
            <span>My Profile</span>
        </a>

        <?php if (is_admin()): ?>
            <a href="<?= url('/settings') ?>" class="sidebar-item <?= isActive('/settings', $uri) ?>" style="min-height: 44px;">
                <i class="bi bi-gear" aria-hidden="true"></i>
                <span>System Settings</span>
            </a>
        <?php endif; ?>

        <form action="<?= url('/logout') ?>" method="POST" class="m-0 p-0" id="sidebarLogoutForm">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar-item w-100 text-start bg-transparent border-0" data-confirm="Are you sure you want to log out of the system?" style="min-height: 44px; cursor: pointer;">
                <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                <span>Logout</span>
            </button>
        </form>
    </nav>
</aside>
