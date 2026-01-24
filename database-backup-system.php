<?php
/**
 * DATABASE BACKUP AND RECOVERY SYSTEM
 * 
 * Description: Comprehensive backup and recovery solution for JobPortal
 * Features:
 * - Automated daily backups
 * - Manual backup creation
 * - Database restoration
 * - Backup verification
 * - Multiple backup storage locations
 * 
 * Usage: Run via command line or web interface
 * Security: Admin access only
 */

class DatabaseBackupSystem {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'job_portal';
    private $backupDir = 'backups/database/';
    private $maxBackups = 30; // Keep 30 days of backups
    
    public function __construct() {
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Create a full database backup
     */
    public function createBackup($description = '') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "job_portal_backup_{$timestamp}.sql";
            $filepath = $this->backupDir . $filename;
            
            // Create mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($this->host),
                escapeshellarg($this->username),
                escapeshellarg($this->password),
                escapeshellarg($this->database),
                escapeshellarg($filepath)
            );
            
            // Execute backup
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($filepath)) {
                // Verify backup file
                $fileSize = filesize($filepath);
                if ($fileSize > 1000) { // Minimum 1KB for valid backup
                    
                    // Create backup info file
                    $infoFile = $this->backupDir . "backup_info_{$timestamp}.json";
                    $backupInfo = [
                        'filename' => $filename,
                        'timestamp' => $timestamp,
                        'date' => date('Y-m-d H:i:s'),
                        'size' => $fileSize,
                        'description' => $description,
                        'database' => $this->database,
                        'tables_count' => $this->getTableCount(),
                        'checksum' => md5_file($filepath)
                    ];
                    
                    file_put_contents($infoFile, json_encode($backupInfo, JSON_PRETTY_PRINT));
                    
                    // Clean old backups
                    $this->cleanOldBackups();
                    
                    return [
                        'success' => true,
                        'message' => 'Backup created successfully',
                        'filename' => $filename,
                        'size' => $this->formatBytes($fileSize),
                        'path' => $filepath
                    ];
                } else {
                    unlink($filepath); // Remove invalid backup
                    throw new Exception('Backup file is too small or corrupted');
                }
            } else {
                throw new Exception('Backup command failed: ' . implode("\n", $output));
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore database from backup file
     */
    public function restoreBackup($backupFilename) {
        try {
            $filepath = $this->backupDir . $backupFilename;
            
            if (!file_exists($filepath)) {
                throw new Exception('Backup file not found');
            }
            
            // Verify backup file integrity
            $infoFile = str_replace('.sql', '.json', $filepath);
            $infoFile = str_replace('job_portal_backup_', 'backup_info_', $infoFile);
            
            if (file_exists($infoFile)) {
                $backupInfo = json_decode(file_get_contents($infoFile), true);
                $currentChecksum = md5_file($filepath);
                
                if ($backupInfo['checksum'] !== $currentChecksum) {
                    throw new Exception('Backup file is corrupted (checksum mismatch)');
                }
            }
            
            // Create database connection
            $pdo = new PDO("mysql:host={$this->host}", $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Drop and recreate database
            $pdo->exec("DROP DATABASE IF EXISTS {$this->database}");
            $pdo->exec("CREATE DATABASE {$this->database} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE {$this->database}");
            
            // Execute restore command
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s',
                escapeshellarg($this->host),
                escapeshellarg($this->username),
                escapeshellarg($this->password),
                escapeshellarg($this->database),
                escapeshellarg($filepath)
            );
            
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                // Verify restoration
                $tableCount = $this->getTableCount();
                
                return [
                    'success' => true,
                    'message' => 'Database restored successfully',
                    'tables_restored' => $tableCount,
                    'backup_file' => $backupFilename
                ];
            } else {
                throw new Exception('Restore command failed: ' . implode("\n", $output));
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * List all available backups
     */
    public function listBackups() {
        $backups = [];
        $files = glob($this->backupDir . 'job_portal_backup_*.sql');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $timestamp = str_replace(['job_portal_backup_', '.sql'], '', $filename);
            
            // Get backup info if available
            $infoFile = $this->backupDir . "backup_info_{$timestamp}.json";
            $info = [];
            
            if (file_exists($infoFile)) {
                $info = json_decode(file_get_contents($infoFile), true);
            }
            
            $backups[] = [
                'filename' => $filename,
                'timestamp' => $timestamp,
                'date' => $info['date'] ?? date('Y-m-d H:i:s', filemtime($file)),
                'size' => $this->formatBytes(filesize($file)),
                'description' => $info['description'] ?? '',
                'tables_count' => $info['tables_count'] ?? 'Unknown'
            ];
        }
        
        // Sort by date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $backups;
    }
    
    /**
     * Create automated backup (for cron jobs)
     */
    public function createAutomatedBackup() {
        $description = 'Automated daily backup - ' . date('Y-m-d H:i:s');
        return $this->createBackup($description);
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $pdo = new PDO("mysql:host={$this->host};dbname={$this->database}", $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '{$this->database}'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'message' => 'Database connection successful',
                'tables' => $result['table_count']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get table count in database
     */
    private function getTableCount() {
        try {
            $pdo = new PDO("mysql:host={$this->host};dbname={$this->database}", $this->username, $this->password);
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '{$this->database}'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Clean old backup files
     */
    private function cleanOldBackups() {
        $files = glob($this->backupDir . 'job_portal_backup_*.sql');
        
        if (count($files) > $this->maxBackups) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $filesToRemove = array_slice($files, 0, count($files) - $this->maxBackups);
            
            foreach ($filesToRemove as $file) {
                unlink($file);
                
                // Also remove corresponding info file
                $infoFile = str_replace(['job_portal_backup_', '.sql'], ['backup_info_', '.json'], $file);
                if (file_exists($infoFile)) {
                    unlink($infoFile);
                }
            }
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $backup = new DatabaseBackupSystem();
    
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case 'create':
                $description = $argv[2] ?? 'Manual backup via CLI';
                $result = $backup->createBackup($description);
                echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
                break;
                
            case 'restore':
                if (!isset($argv[2])) {
                    echo "Usage: php database-backup-system.php restore <backup_filename>\n";
                    exit(1);
                }
                $result = $backup->restoreBackup($argv[2]);
                echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
                break;
                
            case 'list':
                $backups = $backup->listBackups();
                echo "Available Backups:\n";
                foreach ($backups as $backup) {
                    echo "- {$backup['filename']} ({$backup['size']}) - {$backup['date']}\n";
                }
                break;
                
            case 'test':
                $result = $backup->testConnection();
                echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
                break;
                
            case 'auto':
                $result = $backup->createAutomatedBackup();
                echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
                break;
                
            default:
                echo "Usage: php database-backup-system.php [create|restore|list|test|auto]\n";
                echo "  create [description] - Create a new backup\n";
                echo "  restore <filename>   - Restore from backup\n";
                echo "  list                 - List all backups\n";
                echo "  test                 - Test database connection\n";
                echo "  auto                 - Create automated backup\n";
                break;
        }
    } else {
        echo "JobPortal Database Backup System\n";
        echo "Usage: php database-backup-system.php [command]\n";
        echo "Commands: create, restore, list, test, auto\n";
    }
}

// Web interface (if accessed via browser)
if (php_sapi_name() !== 'cli' && isset($_POST['action'])) {
    session_start();
    
    // Check admin authentication
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    header('Content-Type: application/json');
    
    $backup = new DatabaseBackupSystem();
    
    switch ($_POST['action']) {
        case 'create':
            $description = $_POST['description'] ?? 'Manual backup via web interface';
            $result = $backup->createBackup($description);
            echo json_encode($result);
            break;
            
        case 'restore':
            if (!isset($_POST['filename'])) {
                echo json_encode(['success' => false, 'message' => 'Backup filename required']);
                break;
            }
            $result = $backup->restoreBackup($_POST['filename']);
            echo json_encode($result);
            break;
            
        case 'list':
            $backups = $backup->listBackups();
            echo json_encode(['success' => true, 'backups' => $backups]);
            break;
            
        case 'test':
            $result = $backup->testConnection();
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
?>