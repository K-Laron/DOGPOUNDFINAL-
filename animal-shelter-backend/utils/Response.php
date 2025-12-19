<?php
/**
 * Standardized API Response Handler
 */

class Response {
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = "Success", $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Send error response
     */
    public static function error($message = "An error occurred", $code = 400, $errors = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response);
        exit;
    }

    /**
     * Send paginated response
     */
    public static function paginated($data, $page, $perPage, $total, $message = "Success") {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total_items' => (int)$total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => ($page * $perPage) < $total,
                'has_prev' => $page > 1
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}