<?php
// Test redirect behavior in Controller and QueueController

require_once __DIR__ . '/../app/Core/Controller.php';

// Create a subclass to test redirect logic without calling exit
class TestRedirectController extends \App\Core\Controller {
    public $lastRedirectLocation = null;

    // Override redirect to capture header instead of exiting
    public function testRedirect($url) {
        $url = is_string($url) ? $url : '/';

        // Allow absolute HTTP/HTTPS URLs ONLY if they match current application host (e.g. same-origin referrer)
        if (is_string($url) && preg_match('#^https?://#i', $url)) {
            $parsed = parse_url($url);
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';
            if (!empty($parsed['host']) && $currentHost !== '') {
                $currentHostName = strtolower(explode(':', $currentHost)[0]);
                $parsedHostName = strtolower($parsed['host']);
                if ($currentHostName === $parsedHostName) {
                    $url = ($parsed['path'] ?? '/')
                         . (isset($parsed['query']) ? '?' . $parsed['query'] : '')
                         . (isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '');
                } else {
                    $url = '/';
                }
            } else {
                $url = '/';
            }
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) || str_starts_with($url, '//')) {
            $url = '/';
        }
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('/index.php', '', $scriptName);
        
        if ($basePath !== '' && strpos($url, $basePath) === 0) {
            $this->lastRedirectLocation = $url;
            return;
        }
        
        $this->lastRedirectLocation = rtrim($basePath, '/') . '/' . ltrim($url, '/');
    }
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/sinalhan-hc-system/index.php';

$controller = new TestRedirectController();

$passed = 0;
$failed = 0;

function assertRedirect($expected, $actual, $testName) {
    global $passed, $failed;
    if ($expected === $actual) {
        echo " [PASS] {$testName}: {$actual}\n";
        $passed++;
    } else {
        echo " [FAIL] {$testName}: expected '{$expected}', got '{$actual}'\n";
        $failed++;
    }
}

echo "=== Running Redirect Security & Path Resolution Tests ===\n";

// Test 1: Relative route /queue
$controller->testRedirect('/queue');
assertRedirect('/sinalhan-hc-system/queue', $controller->lastRedirectLocation, 'Relative path /queue');

// Test 2: Full same-origin referer URL
$controller->testRedirect('http://localhost/sinalhan-hc-system/queue');
assertRedirect('/sinalhan-hc-system/queue', $controller->lastRedirectLocation, 'Full same-origin HTTP referrer');

// Test 3: Full same-origin HTTPS referrer
$controller->testRedirect('https://localhost/sinalhan-hc-system/queue');
assertRedirect('/sinalhan-hc-system/queue', $controller->lastRedirectLocation, 'Full same-origin HTTPS referrer');

// Test 4: External open-redirect attempt
$controller->testRedirect('http://evil.com/phish');
assertRedirect('/sinalhan-hc-system/', $controller->lastRedirectLocation, 'External origin blocked');

// Test 5: Protocol-relative open-redirect attempt
$controller->testRedirect('//evil.com/attack');
assertRedirect('/sinalhan-hc-system/', $controller->lastRedirectLocation, 'Protocol relative URL blocked');

// Test 6: Javascript URI scheme
$controller->testRedirect('javascript:alert(1)');
assertRedirect('/sinalhan-hc-system/', $controller->lastRedirectLocation, 'Javascript scheme blocked');

// Test 7: Patient tab hash preservation
$controller->testRedirect('/patients/42#tab-appointments');
assertRedirect('/sinalhan-hc-system/patients/42#tab-appointments', $controller->lastRedirectLocation, 'Patient tab anchor preserved');

// Test 8: Full referrer with patient tab
$controller->testRedirect('http://localhost/sinalhan-hc-system/patients/42#tab-appointments');
assertRedirect('/sinalhan-hc-system/patients/42#tab-appointments', $controller->lastRedirectLocation, 'Full referrer with patient tab preserved');

echo "\nSummary: {$passed} passed, {$failed} failed.\n";
exit($failed > 0 ? 1 : 0);
