<?php
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

date_default_timezone_set('Asia/Manila');

echo "PHP Date: " . date('Y-m-d H:i:s') . " | timestamp: " . time() . "\n";

$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("SELECT NOW() as db_now, UNIX_TIMESTAMP(NOW()) as db_ts, @@session.time_zone as tz");
$row = $stmt->fetch();
echo "DB NOW:   " . $row['db_now'] . " | db_ts:     " . $row['db_ts'] . " | tz: " . $row['tz'] . "\n";
