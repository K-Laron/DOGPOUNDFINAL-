<?php
/**
 * Standardized API Response Handler
 * 
 * @package AnimalShelter
 */

class Response {
    
    /**
     * Send success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $code HTTP status code
     */
    public static function success($data = null, $message = "Success", $code = 200) {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::send($response);
    }

    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array|null $errors Validation errors
     */
    public static function error($message = "An error occurred", $code = 400, $errors = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        // Add debug info in development mode
        if (defined('APP_ENV') && APP_ENV === 'development' && $code >= 500) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            if (isset($backtrace[1])) {
                $response['debug'] = [
                    'file' => $backtrace[1]['file'] ?? 'unknown',
                    'line' => $backtrace[1]['line'] ?? 'unknown',
                    'function' => $backtrace[1]['function'] ?? 'unknown'
                ];
            }
        }
        
        self::send($response);
    }

    /**
     * Send paginated response
     * 
     * @param array $data Response data
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param int $total Total items
     * @param string $message Success message
     */
    public static function paginated($data, $page, $perPage, $total, $message = "Success") {
        http_response_code(200);
        
        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total_items' => (int)$total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'prev_page' => $page > 1 ? $page - 1 : null
            ],
            'timestamp' => date('c')
        ];
        
        self::send($response);
    }

    /**
     * Send created response (201)
     * 
     * @param mixed $data Created resource data
     * @param string $message Success message
     */
    public static function created($data = null, $message = "Created successfully") {
        self::success($data, $message, 201);
    }

    /**
     * Send no content response (204)
     */
    public static function noContent() {
        http_response_code(204);
        exit;
    }

    /**
     * Send validation error response (422)
     * 
     * @param array $errors Validation errors
     * @param string $message Error message
     */
    public static function validationError($errors, $message = "Validation failed") {
        self::error($message, 422, $errors);
    }

    /**
     * Send unauthorized response (401)
     * 
     * @param string $message Error message
     */
    public static function unauthorized($message = "Unauthorized") {
        self::error($message, 401);
    }

    /**
     * Send forbidden response (403)
     * 
     * @param string $message Error message
     */
    public static function forbidden($message = "Access denied") {
        self::error($message, 403);
    }

    /**
     * Send not found response (404)
     * 
     * @param string $message Error message
     */
    public static function notFound($message = "Resource not found") {
        self::error($message, 404);
    }

    /**
     * Send method not allowed response (405)
     * 
     * @param string $message Error message
     */
    public static function methodNotAllowed($message = "Method not allowed") {
        self::error($message, 405);
    }

    /**
     * Send conflict response (409)
     * 
     * @param string $message Error message
     */
    public static function conflict($message = "Resource already exists") {
        self::error($message, 409);
    }

    /**
     * Send internal server error response (500)
     * 
     * @param string $message Error message
     */
    public static function serverError($message = "Internal server error") {
        self::error($message, 500);
    }

    /**
     * Send JSON response and exit
     * 
     * @param array $response Response data
     */
    private static function send($response) {
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}