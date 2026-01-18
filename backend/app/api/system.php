<?php
/**
 * System Health Routes
 * 
 * @package AnimalShelter
 */

// Health check endpoint (public)
$router->get('/health', 'SystemController@health');

// System info (admin only)
$router->get('/system/info', 'SystemController@info', ['Admin']);

// Settings endpoints (admin only)
$router->get('/settings', 'SystemController@getSettings', ['Admin']);
$router->put('/settings', 'SystemController@updateSettings', ['Admin']);

// Backup & Export endpoints (admin only)
$router->post('/settings/backup', 'SystemController@createBackup', ['Admin']);
$router->get('/settings/export/{type}', 'SystemController@exportData', ['Admin']);
$router->get('/settings/export-logs', 'SystemController@exportLogs', ['Admin']);
$router->delete('/settings/clear-logs', 'SystemController@clearLogs', ['Admin']);

// Reset & Cache endpoints (admin only)
$router->post('/settings/reset', 'SystemController@resetSettings', ['Admin']);
$router->post('/system/clear-cache', 'SystemController@clearCache', ['Admin']);
