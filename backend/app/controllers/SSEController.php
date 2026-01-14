<?php

/**
 * SSE Controller
 * Handles Server-Sent Events for real-time updates
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class SSEController extends BaseController
{

    /**
     * SSE connection timeout in seconds
     */
    private const CONNECTION_TIMEOUT = 30;

    /**
     * Poll interval in seconds
     */
    private const POLL_INTERVAL = 3;

    /**
     * Stream SSE events
     * GET /sse
     */
    public function stream()
    {
        // Disable time limit for this long-running script
        set_time_limit(self::CONNECTION_TIMEOUT + 10);

        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering

        // Get user ID from the injected user (if authenticated)
        $userId = $this->user['UserID'] ?? null;

        // Get last known timestamps from query params
        $lastCheck = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));

        $startTime = time();
        $lastEventTime = $lastCheck;

        // Send initial connection event
        $this->sendEvent('connected', [
            'message' => 'SSE connection established',
            'server_time' => date('c')
        ]);

        // Keep connection alive and poll for changes
        while ((time() - $startTime) < self::CONNECTION_TIMEOUT) {
            // Check for data changes
            $changes = $this->checkForChanges($lastEventTime);

            if (!empty($changes)) {
                foreach ($changes as $change) {
                    $this->sendEvent($change['type'], $change['data']);
                }
                $lastEventTime = date('Y-m-d H:i:s');
            }

            // Send heartbeat to keep connection alive
            $this->sendEvent('heartbeat', ['time' => date('c')]);

            // Flush output
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Check if client disconnected
            if (connection_aborted()) {
                break;
            }

            // Wait before next poll
            sleep(self::POLL_INTERVAL);
        }

        // Send reconnect instruction before closing
        $this->sendEvent('reconnect', [
            'last_check' => $lastEventTime,
            'message' => 'Connection timeout, please reconnect'
        ]);

        exit;
    }

    /**
     * Send an SSE event
     * 
     * @param string $eventType
     * @param array $data
     */
    private function sendEvent(string $eventType, array $data): void
    {
        echo "event: {$eventType}\n";
        echo "data: " . json_encode($data) . "\n\n";
    }

    /**
     * Check for data changes since last check
     * 
     * @param string $since
     * @return array
     */
    private function checkForChanges(string $since): array
    {
        $changes = [];

        try {
            // Check for animal updates
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM Animals 
                WHERE Updated_At > :since
            ");
            $stmt->execute(['since' => $since]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                $changes[] = [
                    'type' => 'animals_updated',
                    'data' => [
                        'count' => (int)$result['count'],
                        'timestamp' => date('c')
                    ]
                ];
            }

            // Check for adoption updates
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM Adoption_Requests 
                WHERE Updated_At > :since
            ");
            $stmt->execute(['since' => $since]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                $changes[] = [
                    'type' => 'adoptions_updated',
                    'data' => [
                        'count' => (int)$result['count'],
                        'timestamp' => date('c')
                    ]
                ];
            }

            // Check for inventory updates
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM Inventory 
                WHERE Last_Updated > :since
            ");
            $stmt->execute(['since' => $since]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                $changes[] = [
                    'type' => 'inventory_updated',
                    'data' => [
                        'count' => (int)$result['count'],
                        'timestamp' => date('c')
                    ]
                ];
            }

            // Check for medical record updates
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM Medical_Records 
                WHERE Updated_At > :since
            ");
            $stmt->execute(['since' => $since]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                $changes[] = [
                    'type' => 'medical_updated',
                    'data' => [
                        'count' => (int)$result['count'],
                        'timestamp' => date('c')
                    ]
                ];
            }

            // Check for invoice/billing updates
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM Invoices 
                WHERE Updated_At > :since
            ");
            $stmt->execute(['since' => $since]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                $changes[] = [
                    'type' => 'billing_updated',
                    'data' => [
                        'count' => (int)$result['count'],
                        'timestamp' => date('c')
                    ]
                ];
            }
        } catch (Exception $e) {
            // Log error but don't break the SSE connection
            error_log("SSE change check error: " . $e->getMessage());
        }

        return $changes;
    }
}
