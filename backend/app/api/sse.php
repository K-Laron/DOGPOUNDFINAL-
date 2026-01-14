<?php

/**
 * SSE Routes
 * Server-Sent Events endpoint for real-time updates
 */

// SSE stream endpoint (no auth required for connection, auth checked internally)
$router->get('/sse', 'SSEController@stream');
