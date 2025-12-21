<?php
/**
 * User Management Routes
 * 
 * @package AnimalShelter
 */

// ============================================
// USER ROUTES
// ============================================

// List all users (Admin only)
$router->get('/users', 'UserController@index', ['Admin', 'Staff']);

// User Statistics
$router->get('/users/stats/summary', 'UserController@stats', ['Admin', 'Staff']);

// Get single user (Admin, Staff)
$router->get('/users/{id}', 'UserController@show', ['Admin', 'Staff']);

// Create new user (Admin only)
$router->post('/users', 'UserController@store', ['Admin']);

// Update user (Admin only)
$router->put('/users/{id}', 'UserController@update', ['Admin']);

// Delete user (Admin only)
$router->delete('/users/{id}', 'UserController@destroy', ['Admin']);

// ============================================
// PROFILE ROUTES (Any authenticated user)
// ============================================

// Get current user's profile
$router->get('/profile', 'UserController@profile', ['*']);

// Update current user's profile
$router->put('/profile', 'UserController@updateProfile', ['*']);

// Change password
$router->put('/profile/password', 'UserController@changePassword', ['*']);

// Upload avatar
$router->post('/profile/avatar', 'UserController@uploadAvatar', ['*']);

// Remove avatar
$router->delete('/profile/avatar', 'UserController@removeAvatar', ['*']);

// Delete account
$router->delete('/profile', 'UserController@deleteAccount', ['*']);

// ============================================
// ROLE ROUTES (Admin only)
// ============================================

// List all roles
$router->get('/roles', 'UserController@listRoles', ['Admin']);