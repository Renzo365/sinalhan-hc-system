<?php
$title = 'Page Not Found';
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('/index.php', '', $scriptName);
        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}
// Render within layout if logged in, otherwise show standalone page
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column align-items-center justify-content-center text-center py-5">
    <div class="display-1 fw-bold text-primary mb-3">404</div>
    <h2 class="h4 fw-bold text-dark mb-2">Oops! Page Not Found</h2>
    <p class="text-muted mb-4" style="max-width: 450px;">
        The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
    </p>
    <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= url('/dashboard') ?>" class="btn btn-primary px-4 py-2 fw-semibold">
                <i class="bi bi-house-door-fill me-1"></i> Back to Dashboard
            </a>
        <?php else: ?>
            <a href="<?= url('/login') ?>" class="btn btn-primary px-4 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Go to Sign In
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
