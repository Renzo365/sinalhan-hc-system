<?php
require_once __DIR__ . '/../app/Core/Database.php';
$db = App\Core\Database::getInstance()->getConnection();
$db->prepare("UPDATE patients SET deleted_at = '2026-09-30 16:10:00', archive_reason = 'Error' WHERE id = 97")->execute();
$db->prepare("UPDATE consultations SET deleted_at = '2026-10-01 13:50:52', archive_reason = 'Error' WHERE id = 181")->execute();
echo "RESET_OK\n";
