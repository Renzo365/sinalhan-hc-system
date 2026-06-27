<?php
$requestUri = explode('?', $_SERVER['REQUEST_URI'])[0];
$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$uri = '/';
if (strpos($requestUri, $basePath) === 0) {
    $uri = substr($requestUri, strlen($basePath));
}
$uri = explode('?', $uri)[0];
if ($uri === '') {
    $uri = '/';
}

function isActive($path, $currentUri) {
    if ($path === '/dashboard') {
        return ($currentUri === '/' || $currentUri === '/dashboard') ? 'active' : '';
    }
    return (strpos($currentUri, $path) === 0) ? 'active' : '';
}
?>
<aside class="app-sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-heart-pulse-fill text-info me-2 fs-4"></i>
        <span>SINALHAN PMS</span>
    </div>
    
    <div class="sidebar-menu">
        <a href="<?= url('/dashboard') ?>" class="sidebar-item <?= isActive('/dashboard', $uri) ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="<?= url('/patients') ?>" class="sidebar-item <?= isActive('/patients', $uri) ?>">
            <i class="bi bi-people"></i>
            <span>Patients</span>
        </a>
        
        <a href="<?= url('/appointments') ?>" class="sidebar-item <?= isActive('/appointments', $uri) ?>">
            <i class="bi bi-calendar-event"></i>
            <span>Appointments</span>
        </a>
        
        <a href="<?= url('/queue') ?>" class="sidebar-item <?= isActive('/queue', $uri) ?>">
            <i class="bi bi-card-list"></i>
            <span>Queue</span>
        </a>

        <a href="<?= url('/reports') ?>" class="sidebar-item <?= isActive('/reports', $uri) ?>">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <span>Reports</span>
        </a>

        <!-- Admin Only Section -->
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <div class="sidebar-section-title">Administration</div>
            
            <a href="<?= url('/archive/patients') ?>" class="sidebar-item <?= isActive('/archive/patients', $uri) ?>">
                <i class="bi bi-archive"></i>
                <span>Archive</span>
            </a>
            
            <a href="<?= url('/audit-logs') ?>" class="sidebar-item <?= isActive('/audit-logs', $uri) ?>">
                <i class="bi bi-shield-lock"></i>
                <span>Audit Logs</span>
            </a>
            
            <a href="<?= url('/backup') ?>" class="sidebar-item <?= isActive('/backup', $uri) ?>">
                <i class="bi bi-database-down"></i>
                <span>Backup</span>
            </a>
            
            <a href="<?= url('/users') ?>" class="sidebar-item <?= isActive('/users', $uri) ?>">
                <i class="bi bi-person-gear"></i>
                <span>Users</span>
            </a>
        <?php endif; ?>
    </div>
</aside>
