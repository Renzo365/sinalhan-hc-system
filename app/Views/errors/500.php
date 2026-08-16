<?php
$title = '500 - Internal Server Error';
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

if (!function_exists('asset')) {
    function asset($path = '') {
        return url('assets/' . ltrim($path, '/'));
    }
}

// Render within layout if header exists, else fallback
$headerFile = dirname(__DIR__) . '/layout/header.php';
$hasHeader = false;
if (file_exists($headerFile)) {
    try {
        require $headerFile;
        $hasHeader = true;
    } catch (\Throwable $hdrEx) {
        $hasHeader = false;
    }
}

if (!$hasHeader) {
    echo '<!DOCTYPE html><html><head><title>500 - Internal Server Error</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></head><body class="bg-light py-5">';
}
?>

<div class="d-flex flex-column align-items-center justify-content-center text-center py-5">
    <div class="display-1 fw-bold text-danger mb-3">500</div>
    <h2 class="h4 fw-bold text-dark mb-2">Oops! Internal Server Error</h2>
    <p class="text-muted mb-4" style="max-width: 480px;">
        An unexpected issue occurred while processing your request. Your data remains safe. The details have been logged automatically for system maintenance.
    </p>
    
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
        <button onclick="window.location.reload()" class="btn btn-outline-secondary px-4 py-2 fw-semibold">
            <i class="bi bi-arrow-clockwise me-1"></i> Try Again
        </button>
        
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

    <?php if (!empty($debug) && !empty($exception)): ?>
        <div class="accordion text-start w-100 mt-3" id="debugAccordion" style="max-width: 750px;">
            <div class="accordion-item border-danger-subtle">
                <h2 class="accordion-header" id="headingDebug">
                    <button class="accordion-button collapsed bg-danger-bg text-danger small fw-semibold py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDebug" aria-expanded="false" aria-controls="collapseDebug">
                        <i class="bi bi-bug-fill me-2"></i> Technical Debug Information (Development Mode)
                    </button>
                </h2>
                <div id="collapseDebug" class="accordion-collapse collapse" aria-labelledby="headingDebug" data-bs-parent="#debugAccordion">
                    <div class="accordion-body bg-light text-dark font-monospace small">
                        <p class="mb-1"><strong>Exception:</strong> <?= h(get_class($exception)) ?></p>
                        <p class="mb-1"><strong>Message:</strong> <?= h($exception->getMessage()) ?></p>
                        <p class="mb-1"><strong>File:</strong> <?= h($exception->getFile()) ?>:<?= $exception->getLine() ?></p>
                        <hr class="my-2">
                        <p class="mb-1 fw-bold">Stack Trace:</p>
                        <pre class="bg-dark text-white p-3 rounded" style="max-height: 250px; overflow-y: auto; font-size: 0.75rem;"><?= h($exception->getTraceAsString()) ?></pre>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
$footerFile = dirname(__DIR__) . '/layout/footer.php';
if ($hasHeader && file_exists($footerFile)) {
    try {
        require $footerFile;
    } catch (\Throwable $ftrEx) {
        echo '</body></html>';
    }
} else {
    echo '</body></html>';
}
?>
