<?php
/**
 * Notification Routes
 * 
 * Defines endpoints for notification management
 */

// Get all notifications
$router->get('/notifications', 'NotificationController@index');
