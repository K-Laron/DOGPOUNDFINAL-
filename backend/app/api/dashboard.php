<?php
/**
 * Dashboard & System Routes
 * 
 * @package AnimalShelter
 */

// ============================================
// DASHBOARD ROUTES
// ============================================

// Get dashboard statistics (overview)
$router->get('/dashboard/stats', 'DashboardController@statistics', ['Admin', 'Staff', 'Veterinarian']);

// Get recent activity
$router->get('/dashboard/activity', 'DashboardController@recentActivity', ['Admin', 'Staff', 'Veterinarian']);

// Get quick stats (for widgets)
$router->get('/dashboard/quick-stats', 'DashboardController@quickStats', ['Admin', 'Staff', 'Veterinarian']);

// Get intake statistics for charts
$router->get('/dashboard/intake', 'DashboardController@intakeStats', ['Admin', 'Staff', 'Veterinarian']);

// ============================================
// ACTIVITY LOG ROUTES (Admin only)
// ============================================

// Get activity logs with filters
$router->get('/logs', 'DashboardController@activityLogs', ['Admin']);

// Get logs for specific user
$router->get('/logs/user/{userId}', 'DashboardController@userLogs', ['Admin', 'Staff', 'Veterinarian']);

// Get logs by action type
$router->get('/logs/action/{actionType}', 'DashboardController@actionLogs', ['Admin']);

// ============================================
// SYSTEM INFO
// ============================================

// Get system info / API health check
$router->get('/system/health', 'DashboardController@healthCheck');

// Get API version and info
$router->get('/system/info', 'DashboardController@systemInfo');