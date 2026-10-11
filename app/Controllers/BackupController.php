<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\User;
use PDO;

class BackupController extends Controller {
    protected $backupDir;
    protected $userModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (!is_super_admin()) {
            $_SESSION['error_message'] = 'Access Denied: Database backup and disaster recovery tools are restricted to Super Administrators.';
            $this->redirect('/dashboard');
            exit;
        }

        $this->userModel = new User();
        $this->backupDir = dirname(dirname(__DIR__)) . '/storage/backups';

        if (!file_exists($this->backupDir)) {
            if (!mkdir($this->backupDir, 0750, true) && !is_dir($this->backupDir)) {
                throw new \RuntimeException('Unable to create the database backup directory.');
            }
        }
        @chmod($this->backupDir, 0750);

        // Ensure .htaccess protection exists inside storage/backups/
        $htaccessPath = $this->backupDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            @file_put_contents($htaccessPath, "Deny from all\n");
        }
    }

    /**
     * Display database backup manager dashboard.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $files = [];
        $lastBackupTime = 'Never';
        $totalBytes = 0;
        $validCount = 0;
        $corruptCount = 0;
        $incompatibleCount = 0;

        if (file_exists($this->backupDir)) {
            $rawFiles = array_diff(scandir($this->backupDir), ['.', '..', '.htaccess']);
            foreach ($rawFiles as $file) {
                $isSql = str_ends_with(strtolower($file), '.sql');
                $isGz = str_ends_with(strtolower($file), '.sql.gz');

                if ($isSql || $isGz) {
                    $filePath = $this->backupDir . '/' . $file;
                    $mtime = filemtime($filePath);
                    $fsize = filesize($filePath);
                    $totalBytes += $fsize;

                    // Integrity and schema check
                    $isEmpty = ($fsize === 0);
                    $format = $isGz ? 'GZ' : 'SQL';
                    
                    if ($isEmpty) {
                        $corruptCount++;
                        $status = 'corrupt';
                        $statusLabel = 'Empty / Corrupt (0 B)';
                        $reason = 'File is empty (0 Bytes).';
                    } else {
                        $schemaCheck = $this->validateBackupSchema($filePath);
                        if ($schemaCheck['valid']) {
                            $validCount++;
                            $status = 'valid';
                            $statusLabel = 'Valid Snapshot';
                            $reason = $schemaCheck['reason'];
                        } else {
                            $incompatibleCount++;
                            $status = 'incompatible';
                            $statusLabel = 'Incompatible Schema';
                            $reason = $schemaCheck['reason'];
                        }
                    }

                    $files[] = [
                        'filename' => $file,
                        'format' => $format,
                        'created_at' => date('Y-m-d h:i A', $mtime),
                        'raw_size' => $fsize,
                        'size' => $this->formatBytes($fsize),
                        'timestamp' => $mtime,
                        'status' => $status,
                        'status_label' => $statusLabel,
                        'status_reason' => $reason,
                        'is_safety' => str_starts_with($file, 'pre_restore_safety_backup_')
                    ];
                }
            }
        }

        // Sort backups by timestamp descending (newest first)
        usort($files, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        if (!empty($files)) {
            $lastBackupTime = $files[0]['created_at'];
        }

        $hasGzip = extension_loaded('zlib');

        $this->view('backup/index', [
            'files' => $files,
            'lastBackupTime' => $lastBackupTime,
            'backupLocation' => 'storage/backups',
            'totalBackups' => count($files),
            'validCount' => $validCount,
            'corruptCount' => $corruptCount,
            'incompatibleCount' => $incompatibleCount,
            'totalDiskSize' => $this->formatBytes($totalBytes),
            'hasGzip' => $hasGzip
        ]);
    }

    /**
     * Generate a new database backup SQL dump file on disk using streaming memory architecture.
     * 
     * @param string $prefix Filename prefix (e.g. 'backup-' or 'pre_restore_safety_backup_')
     * @param bool $compress Whether to compress via gzip (.sql.gz)
     * @return string|false Generated file path or false on error
     */
    public function generateBackupSql($prefix = 'backup-', $compress = false) {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $isGzip = $compress && extension_loaded('zlib');
            
            // Get all tables
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $ext = $isGzip ? '.sql.gz' : '.sql';
            $filename = $prefix . date('Y-m-d_H-i-s') . $ext;
            $filePath = $this->backupDir . '/' . $filename;
            
            $temporaryPath = tempnam($this->backupDir, 'tmp_dump_');
            if ($temporaryPath === false) {
                throw new \RuntimeException('Unable to create a temporary backup file.');
            }

            $fp = $isGzip ? @gzopen($temporaryPath, 'wb9') : @fopen($temporaryPath, 'wb');
            if (!$fp) {
                @unlink($temporaryPath);
                throw new \RuntimeException('Unable to open stream for backup writing.');
            }

            $write = function($str) use ($fp, $isGzip) {
                if ($isGzip) {
                    gzwrite($fp, $str);
                } else {
                    fwrite($fp, $str);
                }
            };

            // Consistent point-in-time snapshot if supported
            try {
                $db->exec("START TRANSACTION /*!40108 WITH CONSISTENT SNAPSHOT */");
            } catch (\Exception $e) {
                // Non-fatal if engine or privileges do not support
            }

            $write("-- Barangay Sinalhan Health Center PMS Database Backup\n");
            $write("-- Generated: " . date('Y-m-d H:i:s') . "\n");
            $write("-- Server Host: " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\n");
            $write("-- PHP Version: " . PHP_VERSION . "\n");
            $write("-- Format: " . ($isGzip ? "GZIP Compressed SQL (.sql.gz)" : "Plaintext SQL (.sql)") . "\n");
            $write("-- --------------------------------------------------\n\n");
            $write("SET FOREIGN_KEY_CHECKS = 0;\n\n");

            foreach ($tables as $table) {
                $write("DROP TABLE IF EXISTS `{$table}`;\n");
                
                $stmt = $db->query("SHOW CREATE TABLE `{$table}`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                $write($row[1] . ";\n\n");
                
                // Get non-generated column names to prevent Error 3105 on restore
                $colStmt = $db->prepare("
                    SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = :table 
                      AND EXTRA NOT LIKE '%GENERATED%' 
                    ORDER BY ORDINAL_POSITION
                ");
                $colStmt->execute(['table' => $table]);
                $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);

                if (empty($columns)) {
                    continue;
                }

                $escapedColumns = array_map(function($col) {
                    return '`' . str_replace('`', '``', $col) . '`';
                }, $columns);
                $columnList = implode(', ', $escapedColumns);

                $stmtData = $db->query("SELECT {$columnList} FROM `{$table}`");
                $columnCount = count($columns);
                
                // Chunked multi-row inserts (100 rows per statement for fast execution & small size)
                $batchSize = 100;
                $rowBatch = [];

                while ($rowData = $stmtData->fetch(PDO::FETCH_NUM)) {
                    $vals = [];
                    for ($i = 0; $i < $columnCount; $i++) {
                        if (isset($rowData[$i])) {
                            $vals[] = $db->quote($rowData[$i]);
                        } else {
                            $vals[] = 'NULL';
                        }
                    }
                    $rowBatch[] = '(' . implode(', ', $vals) . ')';

                    if (count($rowBatch) >= $batchSize) {
                        $write("INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $rowBatch) . ";\n");
                        $rowBatch = [];
                    }
                }

                if (!empty($rowBatch)) {
                    $write("INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $rowBatch) . ";\n");
                    $rowBatch = [];
                }

                $write("\n");
            }

            $write("SET FOREIGN_KEY_CHECKS = 1;\n");

            // Commit snapshot
            try {
                $db->exec("COMMIT");
            } catch (\Exception $e) {
                // Non-fatal
            }

            if ($isGzip) {
                gzclose($fp);
            } else {
                fclose($fp);
            }

            chmod($temporaryPath, 0640);
            if (rename($temporaryPath, $filePath)) {
                $fileSizeBytes = filesize($filePath);
                $formattedSize = $this->formatBytes($fileSizeBytes);
                AuditLog::log('BACKUP_CREATED', 'Backup', "Created database backup: {$filename} ({$formattedSize})");
                return $filePath;
            } else {
                @unlink($temporaryPath);
                return false;
            }
        } catch (\Exception $e) {
            error_log("Database backup error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a new database backup SQL dump and redirect.
     */
    public function store() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!verify_csrf_token()) {
            $_SESSION['error_message'] = 'Security validation failed (CSRF token expired). Please try again.';
            $this->redirect('/backup');
            return;
        }

        $format = $_POST['format'] ?? 'sql';
        $compress = ($format === 'gzip');

        $filePath = $this->generateBackupSql('backup-', $compress);
        if ($filePath) {
            $filename = basename($filePath);
            $size = $this->formatBytes(filesize($filePath));
            $_SESSION['success_message'] = "Backup successfully generated! ({$filename} - {$size})";
        } else {
            $_SESSION['error_message'] = 'Failed to generate database backup. Please check server error logs.';
        }

        $this->redirect('/backup');
    }

    /**
     * Download an existing backup file.
     */
    public function download() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $filename = basename($_GET['filename'] ?? '');

        // Security check: validate filename format strictly
        if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+\.(sql|sql\.gz)$/', $filename)) {
            $_SESSION['error_message'] = 'Invalid backup filename specified.';
            $this->redirect('/backup');
            return;
        }

        $filePath = $this->backupDir . '/' . $filename;

        if (file_exists($filePath)) {
            AuditLog::log('BACKUP_DOWNLOADED', 'Backup', "Downloaded database backup file: {$filename}");
            
            $isGz = str_ends_with(strtolower($filename), '.gz');
            $contentType = $isGz ? 'application/gzip' : 'application/octet-stream';

            header('Content-Description: File Transfer');
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            $_SESSION['error_message'] = 'Requested backup file does not exist.';
            $this->redirect('/backup');
        }
    }

    /**
     * Restore database from an existing backup snapshot.
     * Enforces:
     * 1. Super Admin Password Re-Authentication
     * 2. 'RESTORE' text confirmation phrase
     * 3. Automatic Pre-Restore Safety Snapshot
     * 4. Statement-by-statement incremental execution
     */
    public function restore() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!verify_csrf_token()) {
            $_SESSION['error_message'] = 'Security validation failed (CSRF token expired). Please try again.';
            $this->redirect('/backup');
            return;
        }

        $filename = basename($_POST['filename'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $phrase = trim($_POST['confirmation_phrase'] ?? '');

        // 1. Validate filename
        if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+\.(sql|sql\.gz)$/', $filename)) {
            $_SESSION['error_message'] = 'Invalid backup filename specified.';
            $this->redirect('/backup');
            return;
        }

        $filePath = $this->backupDir . '/' . $filename;
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            $_SESSION['error_message'] = 'Cannot restore: Selected backup file does not exist or is empty (0 Bytes).';
            $this->redirect('/backup');
            return;
        }

        // 2. Validate schema compatibility before touching the database
        $schemaCheck = $this->validateBackupSchema($filePath);
        if (!$schemaCheck['valid']) {
            AuditLog::log('RESTORE_ABORTED', 'Backup', "Aborted restore for {$filename}: Incompatible schema - {$schemaCheck['reason']}");
            $_SESSION['error_message'] = "Restore Aborted: The selected backup file is incompatible with SinalHealth ({$schemaCheck['reason']}).";
            $this->redirect('/backup');
            return;
        }

        // 3. Validate confirmation phrase
        if (strtoupper($phrase) !== 'RESTORE') {
            $_SESSION['error_message'] = 'Restore aborted: You must type the word "RESTORE" exactly to confirm.';
            $this->redirect('/backup');
            return;
        }

        // 3. Step-Up Authentication: Verify Super Admin password
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $currentUser = $this->userModel->findById($userId);

        if (!$currentUser || empty($password) || !password_verify($password, $currentUser['password_hash'])) {
            AuditLog::log('RESTORE_AUTH_FAILED', 'Backup', "Failed restore attempt: Incorrect Super Admin password entered for file: {$filename}");
            $_SESSION['error_message'] = 'Authentication Failed: Incorrect Super Admin password. Database restore was aborted.';
            $this->redirect('/backup');
            return;
        }

        // 4. Create Automatic Pre-Restore Safety Snapshot
        $safetyFilePath = $this->generateBackupSql('pre_restore_safety_backup_', false);
        if (!$safetyFilePath) {
            AuditLog::log('RESTORE_ABORTED', 'Backup', "Aborted restore for {$filename}: Pre-restore safety backup could not be created.");
            $_SESSION['error_message'] = 'Emergency Pre-Restore Safety Snapshot failed. Restore operation was aborted to protect current data.';
            $this->redirect('/backup');
            return;
        }

        $safetyFilename = basename($safetyFilePath);

        // 5. Execute Incremental Restore
        try {
            $startTime = microtime(true);
            $db = \App\Core\Database::getInstance()->getConnection();
            $stats = [];

            $this->executeSqlFileIncremental($db, $filePath, $stats);

            $duration = round(microtime(true) - $startTime, 2);
            $stmtCount = $stats['statements_executed'] ?? 0;

            AuditLog::log('BACKUP_RESTORED', 'Backup', "Successfully restored database from: {$filename} ({$stmtCount} statements in {$duration}s). Pre-restore rollback snapshot saved: {$safetyFilename}");

            $_SESSION['success_message'] = "Database successfully restored from <strong>{$filename}</strong>! ({$stmtCount} statements executed in {$duration}s). An emergency rollback snapshot (<code>{$safetyFilename}</code>) was automatically saved.";
        } catch (\Exception $e) {
            error_log("Database restore fatal error: " . $e->getMessage());
            AuditLog::log('RESTORE_FAILED', 'Backup', "Fatal error during database restore of {$filename}: " . $e->getMessage());
            $_SESSION['error_message'] = "Restore Error: " . htmlspecialchars($e->getMessage()) . " — You can roll back using the safety snapshot: {$safetyFilename}";
        }

        $this->redirect('/backup');
    }

    /**
     * Upload an external SQL or SQL.GZ backup file from offsite storage.
     */
    public function upload() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!verify_csrf_token()) {
            $_SESSION['error_message'] = 'Security validation failed (CSRF token expired). Please try again.';
            $this->redirect('/backup');
            return;
        }

        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['backup_file']['error'] ?? 'missing';
            $_SESSION['error_message'] = "File upload failed with error code: {$errCode}";
            $this->redirect('/backup');
            return;
        }

        $uploaded = $_FILES['backup_file'];
        $originalName = basename($uploaded['name']);
        $size = $uploaded['size'];

        // Maximum size: 50MB
        if ($size > 52428800) {
            $_SESSION['error_message'] = 'Uploaded file exceeds the maximum allowed size (50 MB).';
            $this->redirect('/backup');
            return;
        }

        $isSql = str_ends_with(strtolower($originalName), '.sql');
        $isGz = str_ends_with(strtolower($originalName), '.sql.gz');

        if (!$isSql && !$isGz) {
            $_SESSION['error_message'] = 'Invalid file format. Only .sql and .sql.gz backup archives are accepted.';
            $this->redirect('/backup');
            return;
        }

        // Validate file header
        $fp = fopen($uploaded['tmp_name'], 'rb');
        $headerBytes = fread($fp, 256);
        fclose($fp);

        if ($isGz) {
            // Check GZIP magic bytes: \x1f\x8b
            if (strlen($headerBytes) < 2 || substr($headerBytes, 0, 2) !== "\x1f\x8b") {
                $_SESSION['error_message'] = 'Upload rejected: The file does not appear to be a valid GZIP compressed archive.';
                $this->redirect('/backup');
                return;
            }
        } else {
            // Check SQL dump markers
            $headerText = trim($headerBytes);
            if (!str_starts_with($headerText, '--') && 
                !str_starts_with($headerText, '/*') && 
                !str_starts_with($headerText, 'SET') && 
                !str_starts_with($headerText, 'DROP') && 
                !str_starts_with($headerText, 'CREATE')) {
                $_SESSION['error_message'] = 'Upload rejected: The file does not contain recognized SQL dump headers.';
                $this->redirect('/backup');
                return;
            }
        }

        // Deep schema fingerprinting: verify file belongs to SinalHealth
        $schemaCheck = $this->validateBackupSchema($uploaded['tmp_name']);
        if (!$schemaCheck['valid']) {
            @unlink($uploaded['tmp_name']);
            AuditLog::log('BACKUP_UPLOAD_REJECTED', 'Backup', "Rejected foreign/incompatible backup upload: {$originalName} ({$schemaCheck['reason']})");
            $_SESSION['error_message'] = "Upload Rejected: Incompatible Database Schema. This backup does not belong to SinalHealth ({$schemaCheck['reason']}). Only verified SinalHealth backups can be imported.";
            $this->redirect('/backup');
            return;
        }

        // Sanitize clean target filename
        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName);
        if (!str_starts_with($safeName, 'imported-') && !str_starts_with($safeName, 'backup-')) {
            $safeName = 'imported-' . $safeName;
        }

        $targetPath = $this->backupDir . '/' . $safeName;
        if (file_exists($targetPath)) {
            $safeName = 'imported-' . date('Y-m-d_H-i-s') . '-' . preg_replace('/^imported-/', '', $safeName);
            $targetPath = $this->backupDir . '/' . $safeName;
        }

        if (move_uploaded_file($uploaded['tmp_name'], $targetPath)) {
            chmod($targetPath, 0640);
            AuditLog::log('BACKUP_UPLOADED', 'Backup', "Uploaded external backup archive: {$safeName} (" . $this->formatBytes($size) . ")");
            $_SESSION['success_message'] = "Backup file '{$safeName}' successfully uploaded and validated!";
        } else {
            $_SESSION['error_message'] = 'Failed to save uploaded backup file to storage.';
        }

        $this->redirect('/backup');
    }

    /**
     * Delete an existing backup file.
     */
    public function delete() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!verify_csrf_token()) {
            $_SESSION['error_message'] = 'Security validation failed (CSRF token expired). Please try again.';
            $this->redirect('/backup');
            return;
        }

        $filename = basename($_POST['filename'] ?? '');

        // Security check: validate filename format strictly
        if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+\.(sql|sql\.gz)$/', $filename)) {
            $_SESSION['error_message'] = 'Invalid backup filename specified.';
            $this->redirect('/backup');
            return;
        }

        $filePath = $this->backupDir . '/' . $filename;

        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                AuditLog::log('BACKUP_DELETED', 'Backup', "Deleted database backup file: {$filename}");
                $_SESSION['success_message'] = "Backup file '{$filename}' deleted successfully.";
            } else {
                $_SESSION['error_message'] = 'Failed to delete backup file from disk.';
            }
        } else {
            $_SESSION['error_message'] = 'Backup file does not exist or has already been deleted.';
        }

        $this->redirect('/backup');
    }

    /**
     * Validate that an SQL file belongs to the SinalHealth system schema
     * and does not contain dangerous server-level commands.
     * 
     * @param string $filePath Absolute path to .sql or .sql.gz
     * @return array ['valid' => bool, 'reason' => string, 'tables' => array]
     */
    public function validateBackupSchema(string $filePath): array {
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            return ['valid' => false, 'reason' => 'File is empty or missing on disk.', 'tables' => []];
        }

        $isGz = str_ends_with(strtolower($filePath), '.gz');
        $handle = $isGz ? @gzopen($filePath, 'rb') : @fopen($filePath, 'rb');
        if (!$handle) {
            return ['valid' => false, 'reason' => 'Unable to read backup file.', 'tables' => []];
        }

        $gets = $isGz ? 'gzgets' : 'fgets';
        $close = $isGz ? 'gzclose' : 'fclose';

        $tablesFound = [];
        $hasDangerousCommand = false;
        $dangerousReason = '';
        $hasSinalHealthHeader = false;
        $usersTableColumns = [];
        $recordingUsersTable = false;
        $lineCount = 0;

        while (($line = $gets($handle, 8192)) !== false) {
            $lineCount++;
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // Check for system header signature in top lines
            if ($lineCount <= 100) {
                if (stripos($line, 'SinalHealth Database Backup') !== false ||
                    stripos($line, 'Barangay Sinalhan Health Center') !== false) {
                    $hasSinalHealthHeader = true;
                }
            }

            // Check for dangerous cross-database operations
            if (preg_match('/^\s*(DROP|CREATE)\s+DATABASE\b/i', $trimmed) ||
                preg_match('/^\s*USE\s+[`\'"]?[a-zA-Z0-9_]+[`\'"]?\s*;/i', $trimmed)) {
                $hasDangerousCommand = true;
                $dangerousReason = 'Contains disallowed cross-database statement (e.g. DROP/CREATE DATABASE or USE database).';
                break;
            }

            // Extract CREATE TABLE statements
            if (preg_match('/CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+[`\'"]?([a-zA-Z0-9_]+)[`\'"]?/i', $line, $matches)) {
                $tableName = strtolower($matches[1]);
                $tablesFound[$tableName] = true;
                if ($tableName === 'users') {
                    $recordingUsersTable = true;
                } else {
                    $recordingUsersTable = false;
                }
            }

            // Inspect columns of the 'users' table if present
            if ($recordingUsersTable) {
                if (preg_match('/^\s*[`\'"]?([a-zA-Z0-9_]+)[`\'"]?\s+[a-zA-Z]/', $trimmed, $colMatch)) {
                    $colName = strtolower($colMatch[1]);
                    $usersTableColumns[$colName] = true;
                }
                if (str_contains($trimmed, ';') || str_contains($trimmed, 'ENGINE=')) {
                    $recordingUsersTable = false;
                }
            }
        }

        $close($handle);

        if ($hasDangerousCommand) {
            return [
                'valid' => false,
                'reason' => $dangerousReason,
                'tables' => array_keys($tablesFound)
            ];
        }

        // Required core clinical and security tables
        $coreTables = ['patients', 'consultations', 'users', 'appointments', 'audit_logs'];
        $matchedCore = [];
        foreach ($coreTables as $ct) {
            if (isset($tablesFound[$ct])) {
                $matchedCore[] = $ct;
            }
        }

        // Must define at least 4 of the 5 core SinalHealth tables
        if (count($matchedCore) < 4) {
            $missing = array_diff($coreTables, $matchedCore);
            return [
                'valid' => false,
                'reason' => 'Missing essential SinalHealth core tables (missing: ' . implode(', ', $missing) . ')',
                'tables' => array_keys($tablesFound),
                'matched_core' => $matchedCore
            ];
        }

        // If 'users' table exists, verify it belongs to SinalHealth staff schema
        if (isset($tablesFound['users'])) {
            $hasUsername = isset($usersTableColumns['username']);
            $hasPasswordHash = isset($usersTableColumns['password_hash']);
            $hasRole = isset($usersTableColumns['role']);

            if (!$hasUsername && !$hasPasswordHash && !$hasRole) {
                return [
                    'valid' => false,
                    'reason' => 'Incompatible users table schema (missing staff credentials/roles). Restoring this file would cause total system lockout.',
                    'tables' => array_keys($tablesFound),
                    'matched_core' => $matchedCore
                ];
            }
        }

        return [
            'valid' => true,
            'reason' => 'Verified SinalHealth Database Snapshot (' . count($tablesFound) . ' tables verified).',
            'tables' => array_keys($tablesFound),
            'matched_core' => $matchedCore
        ];
    }

    /**
     * Robust incremental SQL statement executor.
     * Safely processes large files line-by-line while tracking string literals,
     * quote doubling, backslash escaping, and comments to avoid packet limits.
     * 
     * @param PDO $pdo
     * @param string $filePath
     * @param array $stats
     * @return bool
     */
    private function executeSqlFileIncremental(PDO $pdo, $filePath, array &$stats = []) {
        $isGzip = str_ends_with(strtolower($filePath), '.gz');
        $handle = $isGzip ? @gzopen($filePath, 'rb') : @fopen($filePath, 'rb');
        
        if (!$handle) {
            throw new \RuntimeException("Unable to open backup file for reading: " . basename($filePath));
        }

        $gets = $isGzip ? 'gzgets' : 'fgets';
        $close = $isGzip ? 'gzclose' : 'fclose';

        $inString = false;
        $isEscaped = false;
        $currentStatement = '';
        $stmtCount = 0;

        // Temporarily disable foreign key checks during batch execution
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        while (($line = $gets($handle, 65536)) !== false) {
            $trimmed = trim($line);
            // Skip top-level comment lines when not inside a quoted literal
            if ($inString === false && (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#'))) {
                continue;
            }

            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $char = $line[$i];
                $currentStatement .= $char;

                if ($inString !== false) {
                    if ($isEscaped) {
                        $isEscaped = false;
                    } elseif ($char === '\\') {
                        $isEscaped = true;
                    } elseif ($char === $inString) {
                        // Check for standard SQL double-quote escaping e.g. ''
                        if ($i + 1 < $len && $line[$i + 1] === $inString) {
                            $currentStatement .= $line[++$i];
                        } else {
                            $inString = false;
                        }
                    }
                } else {
                    if ($char === "'" || $char === '"') {
                        $inString = $char;
                    } elseif ($char === ';') {
                        $statementToExecute = trim($currentStatement);
                        if ($statementToExecute !== '' && $statementToExecute !== ';') {
                            $pdo->exec($statementToExecute);
                            $stmtCount++;
                        }
                        $currentStatement = '';
                    }
                }
            }
        }

        // Execute any trailing statement without a final newline
        $remaining = trim($currentStatement);
        if ($remaining !== '' && $remaining !== ';') {
            $pdo->exec($remaining);
            $stmtCount++;
        }

        $close($handle);

        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $stats['statements_executed'] = $stmtCount;
        return true;
    }

    /**
     * Format file size helper.
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
