<?php
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$db = \App\Core\Database::getInstance()->getConnection();
$adminUser = $db->query("SELECT id, username, password_hash, role FROM users WHERE role = 'admin' LIMIT 1")->fetch();

if (!$adminUser) {
    die("No admin user found\n");
}

echo "Found admin user: " . $adminUser['username'] . PHP_EOL;

// Get an existing registered well-baby patient
$wb = $db->query("SELECT patient_id FROM wellbaby_records LIMIT 1")->fetch();
$wbPatientId = $wb ? $wb['patient_id'] : null;

// Get an existing unregistered child patient (age <= 5)
$unreg = $db->query("SELECT id FROM patients WHERE TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) <= 5 AND id NOT IN (SELECT patient_id FROM wellbaby_records) AND deleted_at IS NULL LIMIT 1")->fetch();
$unregPatientId = $unreg ? $unreg['id'] : null;

echo "Registered child patient ID: " . ($wbPatientId ?? 'none') . PHP_EOL;
echo "Unregistered child patient ID: " . ($unregPatientId ?? 'none') . PHP_EOL;

$cookieJar = tempnam(sys_get_temp_dir(), 'sinal_cookie_');
$baseUrl = 'http://localhost/sinalhan-hc-system/public';

// Step 1: GET /login to retrieve CSRF token
$ch = curl_init("{$baseUrl}/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$loginHtml = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginHtml, $matches);
$csrfToken = $matches[1] ?? '';
echo "CSRF Token for login: " . ($csrfToken ? 'found' : 'NOT found') . PHP_EOL;

// Step 2: POST /login
$ch = curl_init("{$baseUrl}/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrfToken,
    'username' => $adminUser['username'],
    'password' => 'admin123' // default development password
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login POST HTTP Code: {$httpCode}" . PHP_EOL;

function testUrl($url, $cookieJar, $expectedCode = 200, $label = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $hasPhpError = (stripos($body, 'Fatal error') !== false || stripos($body, 'Parse error') !== false || stripos($body, 'Notice:') !== false);
    $pass = ($code === $expectedCode) && !$hasPhpError;

    echo "  [" . ($pass ? "PASS" : "FAIL") . "] {$label} ({$url}) -> HTTP {$code}" . ($hasPhpError ? " [CONTAINS PHP ERROR]" : "") . PHP_EOL;
    return ['pass' => $pass, 'body' => $body, 'code' => $code];
}

echo PHP_EOL . "--- Testing Live HTTP Endpoints ---" . PHP_EOL;
testUrl("{$baseUrl}/well-baby", $cookieJar, 200, "Well-Baby Registry Roster Index");
testUrl("{$baseUrl}/well-baby/register", $cookieJar, 200, "Well-Baby Infant Registration Page");
testUrl("{$baseUrl}/well-baby/search-infant?q=a", $cookieJar, 200, "AJAX Infant Search API");
testUrl("{$baseUrl}/api/patients/search/female?q=a", $cookieJar, 200, "AJAX Female/Mother Search API");

if ($wbPatientId) {
    testUrl("{$baseUrl}/well-baby/{$wbPatientId}", $cookieJar, 200, "Well-Baby Workstation Page");
    testUrl("{$baseUrl}/well-baby/{$wbPatientId}/edit", $cookieJar, 200, "Well-Baby Edit Birth Record Page");
    testUrl("{$baseUrl}/patients/{$wbPatientId}", $cookieJar, 200, "Registered Child Patient Master Profile");
}

if ($unregPatientId) {
    testUrl("{$baseUrl}/well-baby/register?patient_id={$unregPatientId}", $cookieJar, 200, "Well-Baby Pre-filled Registration for Eligible Child");
    testUrl("{$baseUrl}/patients/{$unregPatientId}", $cookieJar, 200, "Unregistered Child Patient Master Profile (with eligibility)");
}

@unlink($cookieJar);
echo PHP_EOL . "--- HTTP Endpoint Testing Complete ---" . PHP_EOL;
