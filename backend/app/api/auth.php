<?php
/**
 * Authentication Routes
 * 
 * @package AnimalShelter
 */

// ============================================
// AUTH ROUTES (No authentication required)
// ============================================

// Login
$router->post('/auth/login', 'AuthController@login');

// Register new user (public registration as Adopter)
$router->post('/auth/register', 'AuthController@register');

// Refresh access token
$router->post('/auth/refresh', 'AuthController@refresh');

// Logout (optional - for logging purposes)
// Logout (optional - for logging purposes)
$router->post('/auth/logout', 'AuthController@logout');

// Logout from all sessions
$router->post('/auth/logout-all', 'AuthController@logoutAll');

// Forgot password - request reset
$router->post('/auth/forgot-password', 'AuthController@forgotPassword');

// Reset password with token
$router->post('/auth/reset-password', 'AuthController@resetPassword');