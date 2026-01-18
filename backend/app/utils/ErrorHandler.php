<?php
/**
 * Centralized Error Handler
 * Logs detailed errors and returns safe responses to clients
 */

class ErrorHandler
{
    /**
     * Handle a throwable and return a safe response
     *
     * @param Throwable $e
     * @param array|null $context Optional context information
     */
    public static function handle(Throwable $e, $context = null)
    {
        // Log full exception for server-side diagnostics
        $message = sprintf("[Exception] %s in %s on line %d\nStack: %s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
        error_log($message);

        // In development, include debug details in the response
        if (defined('APP_ENV') && APP_ENV === 'development') {
            $debug = [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ];

            Response::error($e->getMessage(), 500, null);
            // Attach debug info if Response::send allows modification in development
            // Response class already adds debug info for 500 when APP_ENV === 'development'
            return;
        }

        // For clients, never leak internal exception messages. Send a generic 500.
        Response::serverError("Internal server error");
    }
}
