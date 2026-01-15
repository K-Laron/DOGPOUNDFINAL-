<?php

/**
 * Request Logger Middleware
 * Logs all API requests in structured JSON format for audit and debugging
 * 
 * @package AnimalShelter
 */

class RequestLogger
{
    /**
     * @var string Log directory path
     */
    private static $logDir = null;

    /**
     * @var float Request start time
     */
    private static $startTime = null;

    /**
     * @var array Request context data
     */
    private static $context = [];

    /**
     * Initialize the logger and start timing
     * Call this at the beginning of the request lifecycle
     */
    public static function start(): void
    {
        self::$startTime = microtime(true);
        self::$context = [
            'request_id' => self::generateRequestId(),
            'timestamp' => date('Y-m-d\TH:i:s.vP'),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'path' => self::getRequestPath(),
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
        ];
    }

    /**
     * Set the authenticated user context
     * Call this after authentication is complete
     * 
     * @param array|null $user User data
     */
    public static function setUser(?array $user): void
    {
        if ($user) {
            self::$context['user_id'] = $user['UserID'] ?? null;
            self::$context['user_email'] = $user['Email'] ?? null;
            self::$context['user_role'] = $user['Role_Name'] ?? null;
        }
    }

    /**
     * Log the request completion
     * Call this at the end of the request lifecycle
     * 
     * @param int $statusCode HTTP status code
     * @param string|null $message Optional response message
     */
    public static function end(int $statusCode = 200, ?string $message = null): void
    {
        if (self::$startTime === null) {
            return; // Logger wasn't started
        }

        $duration = round((microtime(true) - self::$startTime) * 1000, 2);

        $logEntry = array_merge(self::$context, [
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ]);

        if ($message) {
            $logEntry['message'] = $message;
        }

        self::writeLog($logEntry);
        self::$startTime = null;
        self::$context = [];
    }

    /**
     * Log an error during request processing
     * 
     * @param string $error Error message
     * @param int $statusCode HTTP status code
     */
    public static function error(string $error, int $statusCode = 500): void
    {
        if (self::$startTime === null) {
            self::start(); // Start if not already started
        }

        self::$context['error'] = $error;
        self::end($statusCode);
    }

    /**
     * Add custom data to the log context
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     */
    public static function addContext(string $key, $value): void
    {
        self::$context[$key] = $value;
    }

    /**
     * Get the current request ID
     * 
     * @return string|null Request ID
     */
    public static function getRequestId(): ?string
    {
        return self::$context['request_id'] ?? null;
    }

    /**
     * Write log entry to file
     * 
     * @param array $entry Log entry data
     */
    private static function writeLog(array $entry): void
    {
        try {
            $logDir = self::getLogDir();

            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $filename = $logDir . '/requests_' . date('Y-m-d') . '.json';
            $line = json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n";

            file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Fallback to error log if file write fails
            error_log("RequestLogger write failed: " . $e->getMessage());
            error_log("Log entry: " . json_encode($entry));
        }
    }

    /**
     * Get the log directory path
     * 
     * @return string Log directory path
     */
    private static function getLogDir(): string
    {
        if (self::$logDir === null) {
            self::$logDir = defined('BASE_PATH')
                ? BASE_PATH . '/logs/requests'
                : sys_get_temp_dir() . '/dogpound_logs/requests';
        }
        return self::$logDir;
    }

    /**
     * Get the request path (without query string)
     * 
     * @return string Request path
     */
    private static function getRequestPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        if (strpos($uri, '?') !== false) {
            $uri = strstr($uri, '?', true);
        }

        return $uri;
    }

    /**
     * Get client IP address
     * 
     * @return string Client IP
     */
    private static function getClientIP(): string
    {
        // Use secure method - only trust REMOTE_ADDR by default
        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return 'unknown';
    }

    /**
     * Generate a unique request ID
     * 
     * @return string Request ID
     */
    private static function generateRequestId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Clean up old log files
     * 
     * @param int $daysToKeep Number of days to keep logs
     * @return int Number of files deleted
     */
    public static function cleanup(int $daysToKeep = 30): int
    {
        $logDir = self::getLogDir();

        if (!is_dir($logDir)) {
            return 0;
        }

        $deleted = 0;
        $cutoff = time() - ($daysToKeep * 86400);
        $files = glob($logDir . '/requests_*.json');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Read recent log entries
     * 
     * @param int $lines Number of lines to read
     * @param string|null $date Date to read (Y-m-d format), defaults to today
     * @return array Log entries
     */
    public static function getRecent(int $lines = 100, ?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $filename = self::getLogDir() . '/requests_' . $date . '.json';

        if (!file_exists($filename)) {
            return [];
        }

        $entries = [];
        $file = new SplFileObject($filename, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);

        while (!$file->eof()) {
            $line = $file->fgets();
            if (trim($line)) {
                $entry = json_decode($line, true);
                if ($entry) {
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    /**
     * Get request statistics for a date range
     * 
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Statistics
     */
    public static function getStatistics(string $startDate, string $endDate): array
    {
        $stats = [
            'total_requests' => 0,
            'by_status' => [],
            'by_method' => [],
            'avg_duration_ms' => 0,
            'errors' => 0,
        ];

        $totalDuration = 0;
        $logDir = self::getLogDir();
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);

        while ($current <= $end) {
            $filename = $logDir . '/requests_' . $current->format('Y-m-d') . '.json';

            if (file_exists($filename)) {
                $handle = fopen($filename, 'r');
                while (($line = fgets($handle)) !== false) {
                    $entry = json_decode($line, true);
                    if ($entry) {
                        $stats['total_requests']++;

                        $status = $entry['status_code'] ?? 'unknown';
                        $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;

                        $method = $entry['method'] ?? 'unknown';
                        $stats['by_method'][$method] = ($stats['by_method'][$method] ?? 0) + 1;

                        $totalDuration += $entry['duration_ms'] ?? 0;

                        if (isset($entry['error'])) {
                            $stats['errors']++;
                        }
                    }
                }
                fclose($handle);
            }

            $current->modify('+1 day');
        }

        if ($stats['total_requests'] > 0) {
            $stats['avg_duration_ms'] = round($totalDuration / $stats['total_requests'], 2);
        }

        return $stats;
    }
}
