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
}
