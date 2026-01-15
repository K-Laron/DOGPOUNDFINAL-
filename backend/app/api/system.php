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
