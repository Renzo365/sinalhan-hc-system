<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use PDO;

class BackupController extends Controller {
    protected $backupDir;

    public function __construct() {
        $this->backupDir = dirname(dirname(__DIR__)) . '/storage/backups';
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    /**
     * Display database backup manager dashboard.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Scan storage/backups/ directory
        $files = [];
        $lastBackupTime = 'Never';

        if (file_exists($this->backupDir)) {
            $rawFiles = array_diff(scandir($this->backupDir), ['.', '..']);
            foreach ($rawFiles as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $filePath = $this->backupDir . '/' . $file;
                    $mtime = filemtime($filePath);
                    $files[] = [
                        'filename' => $file,
                        'created_at' => date('Y-m-d h:i A', $mtime),
                        'size' => $this->formatBytes(filesize($filePath)),
                        'timestamp' => $mtime
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

        $this->view('backup/index', [
            'files' => $files,
            'lastBackupTime' => $lastBackupTime,
            'backupLocation' => 'storage/backups'
        ]);
    }

    /**
     * Generate a new database backup SQL dump.
     */
    public function store() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            // Get all tables
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $sqlDump = "-- Barangay Sinalhan Health Center PMS Database Backup\n";
            $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sqlDump .= "-- --------------------------------------------------\n\n";
            $sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tables as $table) {
                // Drop table statement
                $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
                
                // Show Create Table
                $stmt = $db->query("SHOW CREATE TABLE `{$table}`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                $sqlDump .= $row[1] . ";\n\n";
                
                // Fetch data
                $stmtData = $db->query("SELECT * FROM `{$table}`");
                $columnCount = $stmtData->columnCount();
                
                while ($rowData = $stmtData->fetch(PDO::FETCH_NUM)) {
                    $sqlDump .= "INSERT INTO `{$table}` VALUES(";
                    for ($i = 0; $i < $columnCount; $i++) {
                        if (isset($rowData[$i])) {
                            // Escape values
                            $val = $db->quote($rowData[$i]);
                            $sqlDump .= $val;
                        } else {
                            $sqlDump .= 'NULL';
                        }
                        if ($i < ($columnCount - 1)) {
                            $sqlDump .= ',';
                        }
                    }
                    $sqlDump .= ");\n";
                }
                $sqlDump .= "\n";
            }

            $sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            // Save SQL dump file
            $filename = 'backup-' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = $this->backupDir . '/' . $filename;
            
            if (file_put_contents($filePath, $sqlDump) !== false) {
                AuditLog::log('BACKUP_CREATED', 'Backup', "Created database backup: {$filename}");
                $_SESSION['success_message'] = "Backup successfully created! ({$filename})";
            } else {
                $_SESSION['error_message'] = 'Failed to write backup file to disk.';
            }
        } catch (\Exception $e) {
            error_log("Database backup error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Database error occurred during backup: ' . $e->getMessage();
        }

        $this->redirect('/backup');
    }

    /**
     * Download an existing backup file.
     */
    public function download() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $filename = $_GET['filename'] ?? '';
        $filePath = $this->backupDir . '/' . basename($filename); // basename filters out path traversals

        if (!empty($filename) && file_exists($filePath)) {
            AuditLog::log('BACKUP_DOWNLOADED', 'Backup', "Downloaded database backup file: " . basename($filename));
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
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
     * Delete an existing backup file.
     */
    public function delete() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $filename = $_POST['filename'] ?? '';
        $filePath = $this->backupDir . '/' . basename($filename);

        if (!empty($filename) && file_exists($filePath)) {
            if (unlink($filePath)) {
                AuditLog::log('BACKUP_DELETED', 'Backup', "Deleted database backup file: " . basename($filename));
                $_SESSION['success_message'] = 'Backup file deleted successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to delete backup file from disk.';
            }
        } else {
            $_SESSION['error_message'] = 'Backup file does not exist or has already been deleted.';
        }

        $this->redirect('/backup');
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
