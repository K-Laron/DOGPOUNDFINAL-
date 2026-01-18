<?php
/**
 * CSRF Protection Middleware
 * Validates CSRF tokens on state-changing requests (POST, PUT, DELETE, PATCH)
 * 
 * @package AnimalShelter
 */

require_once __DIR__ . '/../utils/CsrfToken.php';
require_once __DIR__ . '/../utils/Response.php';

class CsrfMiddleware
{
    /**
     * Routes that should be excluded from CSRF protection
     * These are typically public endpoints or ones that have their own security
     */
    private static array $excludedRoutes = [
        '/auth/login',
        '/auth/register',
        '/auth/forgot-password',
        '/auth/reset-password',
        '/auth/refresh',
        '/sse/stream',  // SSE uses different authentication
    ];
    
    /**
     * Validate CSRF token for the current request
     * 
     * @param string $requestUri The current request URI
     * @param string $method The HTTP method
     * @return bool True if valid or not required, false if invalid
     */
    public static function validate(string $requestUri, string $method): bool
    {
        // Check if CSRF protection is required for this method
        if (!CsrfToken::requiresProtection($method)) {
            return true;
        }
        
        // Check if route is excluded
        if (self::isExcluded($requestUri)) {
            return true;
        }
        
        // Get token from request
        $token = CsrfToken::getFromRequest();
        
        if (!$token) {
            Response::error('CSRF token missing', 403);
            return false;
        }
        
        // Validate the token
        if (!CsrfToken::validate($token)) {
            Response::error('Invalid or expired CSRF token', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if a route is excluded from CSRF protection
     * 
     * @param string $requestUri The request URI
     * @return bool True if excluded
     */
    private static function isExcluded(string $requestUri): bool
    {
        // Normalize URI (remove query string and trailing slash)
        $uri = strtok($requestUri, '?');
        $uri = rtrim($uri, '/');
        
        // Remove /api/v1 prefix if present
        $uri = preg_replace('#^/api/v\d+#', '', $uri);
        
        foreach (self::$excludedRoutes as $excluded) {
            // Exact match
            if ($uri === $excluded) {
                return true;
            }
            
            // Wildcard match (e.g., /auth/* would match /auth/login)
            if (str_ends_with($excluded, '*')) {
                $prefix = rtrim($excluded, '*');
                if (str_starts_with($uri, $prefix)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Add a route to the exclusion list
     * 
     * @param string $route Route to exclude
     */
    public static function excludeRoute(string $route): void
    {
        if (!in_array($route, self::$excludedRoutes, true)) {
            self::$excludedRoutes[] = $route;
        }
    }
    
    /**
     * Get all excluded routes
     * 
     * @return array List of excluded routes
     */
    public static function getExcludedRoutes(): array
    {
        return self::$excludedRoutes;
    }
}
