<?php
/**
 * System Controller
 * Handles system health checks and info
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class SystemController extends BaseController
{
    /**
     * Health check endpoint
     * Returns system status for monitoring
     * 
     * GET /health
     */
    public function health(): void
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
            'version' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
            'checks' => []
        ];

        // Database check
        try {
            $stmt = $this->db->query('SELECT 1');
            $health['checks']['database'] = [
                'status' => 'up',
                'latency_ms' => 0
            ];
        } catch (Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = [
                'status' => 'down',
                'error' => 'Connection failed'
            ];
        }

        // Disk space check
        $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : '/tmp';
        $freeSpace = @disk_free_space($uploadPath);
        $totalSpace = @disk_total_space($uploadPath);
        
        if ($freeSpace !== false && $totalSpace !== false) {
            $freePercent = round(($freeSpace / $totalSpace) * 100, 1);
            $health['checks']['disk'] = [
                'status' => $freePercent > 10 ? 'ok' : 'warning',
                'free_percent' => $freePercent,
                'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2)
            ];
        }

        // Memory check
        $memoryLimit = ini_get('memory_limit');
        $memoryUsed = memory_get_usage(true);
        $health['checks']['memory'] = [
            'status' => 'ok',
            'limit' => $memoryLimit,
            'used_mb' => round($memoryUsed / 1024 / 1024, 2)
        ];

        // PHP version check
        $health['checks']['php'] = [
            'version' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '8.0.0', '>=') ? 'ok' : 'warning'
        ];

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;
        Response::json($health, $statusCode);
    }

    /**
     * System info endpoint (admin only)
     * Returns detailed system information
     * 
     * GET /system/info
     */
    public function info(): void
    {
        $info = [
            'application' => [
                'name' => defined('APP_NAME') ? APP_NAME : 'Catarman Dog Pound',
                'version' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
                'environment' => defined('APP_ENV') ? APP_ENV : 'production'
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
                'timezone' => date_default_timezone_get()
            ],
            'database' => $this->getDatabaseInfo(),
            'statistics' => $this->getSystemStatistics()
        ];

        Response::success($info);
    }

    /**
     * Get database information
     */
    private function getDatabaseInfo(): array
    {
        try {
            $version = $this->db->query('SELECT VERSION() as version')->fetch();
            
            // Get table counts
            $tables = $this->db->query("
                SELECT TABLE_NAME, TABLE_ROWS 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
            ")->fetchAll();

            return [
                'version' => $version['version'] ?? 'Unknown',
                'tables' => count($tables),
                'table_rows' => array_reduce($tables, function($carry, $table) {
                    return $carry + ($table['TABLE_ROWS'] ?? 0);
                }, 0)
            ];
        } catch (Exception $e) {
            return ['error' => 'Unable to fetch database info'];
        }
    }

    /**
     * Get system statistics
     */
    private function getSystemStatistics(): array
    {
        try {
            $stats = [];
            
            // User count
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM Users WHERE Is_Deleted = FALSE");
            $stats['total_users'] = (int)$stmt->fetch()['count'];
            
            // Animal count
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM Animals WHERE Is_Deleted = FALSE");
            $stats['total_animals'] = (int)$stmt->fetch()['count'];
            
            // Active adoptions
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM Adoption_Requests WHERE Status IN ('Pending', 'Interview Scheduled', 'Seminar Scheduled', 'Approved')");
            $stats['active_adoptions'] = (int)$stmt->fetch()['count'];
            
            // Today's activity
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM Activity_Logs WHERE DATE(Log_Date) = CURDATE()");
            $stats['today_activities'] = (int)$stmt->fetch()['count'];

            return $stats;
        } catch (Exception $e) {
            return ['error' => 'Unable to fetch statistics'];
        }
    }

    /**
     * Settings file path
     */
    private function getSettingsFilePath(): string
    {
        return dirname(dirname(APP_PATH)) . '/settings.json';
    }

    /**
     * Get system settings
     * GET /settings
     */
    public function getSettings(): void
    {
        $settingsFile = $this->getSettingsFilePath();
        
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true);
            if ($settings === null) {
                $settings = $this->getDefaultSettings();
            }
        } else {
            $settings = $this->getDefaultSettings();
        }
        
        Response::success($settings, "Settings retrieved successfully");
    }

    /**
     * Update system settings
     * PUT /settings
     */
    public function updateSettings(): void
    {
        $category = $this->input('category');
        $data = $this->input('data');
        
        if (!$category || !$data) {
            Response::error("Category and data are required", 400);
            return;
        }
        
        $settingsFile = $this->getSettingsFilePath();
        
        // Load existing settings
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true);
            if ($settings === null) {
                $settings = $this->getDefaultSettings();
            }
        } else {
            $settings = $this->getDefaultSettings();
        }
        
        // Update the specific category
        $settings[$category] = array_merge($settings[$category] ?? [], $data);
        $settings['last_updated'] = date('Y-m-d H:i:s');
        
        // Save settings
        $result = file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        
        if ($result === false) {
            Response::error("Failed to save settings", 500);
            return;
        }
        
        $this->logActivity('UPDATE_SETTINGS', "Updated {$category} settings");
        
        Response::success($settings, "Settings updated successfully");
    }

    /**
     * Get default settings
     */
    private function getDefaultSettings(): array
    {
        return [
            'general' => [
                'site_name' => 'Catarman Dog Pound',
                'site_description' => 'Animal shelter management system',
                'timezone' => 'Asia/Manila',
                'date_format' => 'MM/DD/YYYY',
                'currency' => 'PHP'
            ],
            'shelter' => [
                'name' => 'Catarman Dog Pound',
                'address' => 'Catarman, Northern Samar',
                'phone' => '',
                'email' => '',
                'operating_hours' => '8:00 AM - 5:00 PM',
                'about' => ''
            ],
            'adoption' => [
                'require_approval' => true,
                'require_interview' => true,
                'minimum_age' => 18,
                'adoption_fee_enabled' => true,
                'cooldown_days' => 30
            ],
            'fees' => [
                'adoption_fee_dog' => 500,
                'adoption_fee_cat' => 300,
                'adoption_fee_other' => 200,
                'reclaim_fee_base' => 300,
                'reclaim_fee_per_day' => 50,
                'vet_service_fee' => 0,
                'surrender_fee' => 0
            ],
            'notifications' => [
                'sms_enabled' => false,
                'notify_new_adoption' => true,
                'notify_animal_status' => true,
                'notify_low_inventory' => true,
                'notify_medical_reminders' => true,
                'notify_daily_summary' => false
            ],
            'email' => [
                'smtp_host' => '',
                'smtp_port' => 587,
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'from_email' => '',
                'from_name' => 'Catarman Dog Pound'
            ],
            'last_backup' => null,
            'last_updated' => null
        ];
    }

    /**
     * Create database backup
     * POST /settings/backup
     */
    public function createBackup(): void
    {
        try {
            // Get database credentials from environment
            $host = Env::get('DB_HOST', 'localhost');
            $dbname = Env::get('DB_NAME', 'catarman_dog_pound_db');
            $user = Env::get('DB_USER', 'root');
            $pass = Env::get('DB_PASS', '');
            
            $backupDir = dirname(dirname(APP_PATH)) . '/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $filename = "backup_" . date('Y-m-d_His') . ".sql";
            $filepath = $backupDir . '/' . $filename;
            
            // Export all tables to SQL file
            $tables = [];
            $result = $this->db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $sql = "-- Catarman Dog Pound Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: {$dbname}\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            require_once APP_PATH . '/utils/DBHelper.php';
            foreach ($tables as $table) {
                // Validate table name
                if (!DBHelper::isValidIdentifier($this->db, $table)) {
                    error_log("Skipping invalid or unknown table during backup: " . $table);
                    continue;
                }

                $qTable = DBHelper::quoteIdentifier($table);

                // Get CREATE TABLE statement
                $createStmt = $this->db->query("SHOW CREATE TABLE " . $qTable)->fetch();
                $sql .= "DROP TABLE IF EXISTS " . $qTable . ";\n";
                $sql .= $createStmt['Create Table'] . ";\n\n";
                
                // Get table data
                $rows = $this->db->query("SELECT * FROM " . $qTable)->fetchAll(PDO::FETCH_ASSOC);
                if (count($rows) > 0) {
                    $columns = array_keys($rows[0]);
                    $columnList = '`' . implode('`, `', $columns) . '`';
                    
                    foreach ($rows as $row) {
                        $values = array_map(function($val) {
                            if ($val === null) return 'NULL';
                            return "'" . addslashes($val) . "'";
                        }, array_values($row));
                        $sql .= "INSERT INTO " . $qTable . " ({$columnList}) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Save backup file
            file_put_contents($filepath, $sql);
            
            // Update last backup timestamp in settings
            $settingsFile = $this->getSettingsFilePath();
            if (file_exists($settingsFile)) {
                $settings = json_decode(file_get_contents($settingsFile), true);
            } else {
                $settings = $this->getDefaultSettings();
            }
            $settings['last_backup'] = date('Y-m-d H:i:s');
            file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
            
            $this->logActivity('CREATE_BACKUP', "Created database backup: {$filename}");
            
            // Return file for download
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
            
        } catch (Exception $e) {
            error_log("SystemController::createBackup failed: " . $e->getMessage());
            require_once APP_PATH . '/utils/ErrorHandler.php';
            ErrorHandler::handle($e);
        }
    }

    /**
     * Export data as CSV
     * GET /settings/export/{type}
     */
    public function exportData($type): void
    {
        try {
            $validTypes = ['animals', 'adoptions', 'users', 'medical', 'inventory', 'invoices'];
            
            if (!in_array($type, $validTypes)) {
                Response::error("Invalid export type. Valid types: " . implode(', ', $validTypes), 400);
                return;
            }
            
            $filename = "{$type}_export_" . date('Y-m-d_His') . ".csv";
            
            switch ($type) {
                case 'animals':
                    $sql = "SELECT AnimalID, Name, Type, Breed, Gender, Age_Group, Weight, 
                            Intake_Date, Intake_Status, Current_Status, Image_URL, Created_At 
                            FROM Animals WHERE Is_Deleted = FALSE ORDER BY AnimalID";
                    break;
                case 'adoptions':
                    $sql = "SELECT ar.RequestID, a.Name as Animal_Name, 
                            CONCAT(u.FirstName, ' ', u.LastName) as Adopter_Name,
                            u.Email as Adopter_Email, ar.Request_Date, ar.Status, 
                            ar.Interview_Date, ar.Seminar_Date, ar.Staff_Comments, ar.Created_At
                            FROM Adoption_Requests ar
                            JOIN Animals a ON ar.AnimalID = a.AnimalID
                            JOIN Users u ON ar.Adopter_UserID = u.UserID
                            ORDER BY ar.RequestID";
                    break;
                case 'users':
                    $sql = "SELECT u.UserID, u.FirstName, u.LastName, u.Username, u.Email, 
                            u.Contact_Number, u.Address, r.Role_Name, u.Account_Status, u.Created_At
                            FROM Users u
                            JOIN Roles r ON u.RoleID = r.RoleID
                            WHERE u.Is_Deleted = FALSE ORDER BY u.UserID";
                    break;
                case 'medical':
                    $sql = "SELECT mr.RecordID, a.Name as Animal_Name, 
                            CONCAT(u.FirstName, ' ', u.LastName) as Veterinarian,
                            mr.Date_Performed, mr.Diagnosis_Type, mr.Vaccine_Name, 
                            mr.Treatment_Notes, mr.Next_Due_Date, mr.Created_At
                            FROM Medical_Records mr
                            JOIN Animals a ON mr.AnimalID = a.AnimalID
                            JOIN Veterinarians v ON mr.VetID = v.VetID
                            JOIN Users u ON v.UserID = u.UserID
                            ORDER BY mr.RecordID";
                    break;
                case 'inventory':
                    $sql = "SELECT ItemID, Item_Name, Category, Quantity_On_Hand, 
                            Reorder_Level, Expiration_Date, Supplier_Name, Last_Updated
                            FROM Inventory ORDER BY ItemID";
                    break;
                case 'invoices':
                    $sql = "SELECT i.InvoiceID, CONCAT(u.FirstName, ' ', u.LastName) as Payer_Name,
                            i.Transaction_Type, i.Total_Amount, i.Status, 
                            a.Name as Animal_Name, i.Created_At
                            FROM Invoices i
                            JOIN Users u ON i.Payer_UserID = u.UserID
                            LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
                            WHERE i.Is_Deleted = FALSE
                            ORDER BY i.InvoiceID";
                    break;
            }
            
            $stmt = $this->db->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($data)) {
                Response::error("No data to export", 404);
                return;
            }
            
            $this->logActivity('EXPORT_DATA', "Exported {$type} data (" . count($data) . " records)");
            
            // Output CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            error_log("SystemController::exportData failed: " . $e->getMessage());
            require_once APP_PATH . '/utils/ErrorHandler.php';
            ErrorHandler::handle($e);
        }
    }

    /**
     * Export activity logs
     * GET /settings/export-logs
     */
    public function exportLogs(): void
    {
        try {
            $sql = "SELECT al.LogID, CONCAT(u.FirstName, ' ', u.LastName) as User_Name,
                    u.Email, al.Action_Type, al.Description, al.IP_Address, al.Log_Date
                    FROM Activity_Logs al
                    LEFT JOIN Users u ON al.UserID = u.UserID
                    ORDER BY al.Log_Date DESC
                    LIMIT 10000";
            
            $stmt = $this->db->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($data)) {
                Response::error("No logs to export", 404);
                return;
            }
            
            $filename = "activity_logs_" . date('Y-m-d_His') . ".csv";
            
            $this->logActivity('EXPORT_LOGS', "Exported activity logs (" . count($data) . " records)");
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            error_log("SystemController::exportLogs failed: " . $e->getMessage());
            require_once APP_PATH . '/utils/ErrorHandler.php';
            ErrorHandler::handle($e);
        }
    }

    /**
     * Clear activity logs
     * DELETE /settings/clear-logs
     */
    public function clearLogs(): void
    {
        try {
            // Keep last 30 days of logs
            $stmt = $this->db->prepare("DELETE FROM Activity_Logs WHERE Log_Date < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            
            $this->logActivity('CLEAR_LOGS', "Cleared {$deleted} old activity logs (kept last 30 days)");
            
            Response::success(['deleted' => $deleted], "Cleared {$deleted} old activity logs");
            
        } catch (Exception $e) {
            error_log("SystemController::clearLogs failed: " . $e->getMessage());
            require_once APP_PATH . '/utils/ErrorHandler.php';
            ErrorHandler::handle($e);
        }
    }

    /**
     * Reset settings to defaults
     * POST /settings/reset
     */
    public function resetSettings(): void
    {
        try {
            $settingsFile = $this->getSettingsFilePath();
            $defaultSettings = $this->getDefaultSettings();
            $defaultSettings['last_updated'] = date('Y-m-d H:i:s');
            
            // Save default settings
            $result = file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT));
            
            if ($result === false) {
                Response::error("Failed to reset settings", 500);
                return;
            }
            
            $this->logActivity('RESET_SETTINGS', "Reset all settings to defaults");
            
            Response::success($defaultSettings, "Settings reset to defaults successfully");
            
        } catch (Exception $e) {
            error_log("SystemController::resetSettings failed: " . $e->getMessage());
            require_once APP_PATH . '/utils/ErrorHandler.php';
            ErrorHandler::handle($e);
        }
    }

    /**
     * Clear system cache
     * POST /system/clear-cache
     */
    public function clearCache(): void
    {
        try {
            $cacheCleared = [];
            
            // Clear PHP opcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $cacheCleared[] = 'opcache';
            }
            
            // Clear any temp files in uploads/temp
            $tempDir = defined('UPLOAD_PATH') ? UPLOAD_PATH . '/temp' : dirname(dirname(APP_PATH)) . '/uploads/temp';
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '/*');
                $deletedCount = 0;
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < strtotime('-1 hour')) {
                        unlink($file);
                        $deletedCount++;
                    }
                }
                if ($deletedCount > 0) {
                    $cacheCleared[] = "temp files ({$deletedCount})";
                }
            }
            
            // Clear any session files older than 24 hours (if using file sessions)
            $sessionPath = session_save_path();
            if (!empty($sessionPath) && is_dir($sessionPath)) {
                // Only clear if we have access and it's a custom session path
                // Skip default system paths for safety
                if (strpos($sessionPath, 'dogpound') !== false) {
                    $files = glob($sessionPath . '/sess_*');
                    $deletedSessions = 0;
                    foreach ($files as $file) {
                        if (is_file($file) && filemtime($file) < strtotime('-24 hours')) {
                            @unlink($file);
                            $deletedSessions++;
                        }
                    }
                    if ($deletedSessions > 0) {
                        $cacheCleared[] = "old sessions ({$deletedSessions})";
                    }
                }
            }
            
            $message = count($cacheCleared) > 0 
                ? "Cache cleared: " . implode(', ', $cacheCleared)
                : "Cache cleared successfully (no cached items found)";
            
            $this->logActivity('CLEAR_CACHE', $message);
            
            Response::success([
                'cleared' => $cacheCleared,
                'timestamp' => date('Y-m-d H:i:s')
            ], $message);
            
        } catch (Exception $e) {
            error_log("SystemController::clearCache failed: " . $e->getMessage());
            require_once APP_PATH . '/utils/ErrorHandler.php';
            ErrorHandler::handle($e);
        }
    }
}
