<?php
/**
 * Animal Management Routes
 * 
 * @package AnimalShelter
 */

// ============================================
// PUBLIC ANIMAL ROUTES (No authentication)
// ============================================

// Get available animals for adoption (public)
$router->get('/animals/available', 'AnimalController@available');

// ============================================
// PROTECTED ANIMAL ROUTES
// ============================================

// List all animals (with filters)
$router->get('/animals', 'AnimalController@index', ['Admin', 'Staff', 'Veterinarian']);

// Get animal statistics - MUST come before /animals/{id}
$router->get('/animals/stats/summary', 'AnimalController@statistics', ['Admin', 'Staff', 'Veterinarian']);

// Export animals data (CSV, JSON, Excel) - MUST come before /animals/{id}
$router->get('/animals/export', 'AnimalController@export', ['Admin', 'Staff']);

// Bulk update animal statuses
$router->post('/animals/bulk-status', 'AnimalController@bulkUpdateStatus', ['Admin', 'Staff']);

// Create new animal record
$router->post('/animals', 'AnimalController@store', ['Admin', 'Staff', 'Veterinarian']);

// Get single animal details (public) - Parameterized route MUST come AFTER specific routes
$router->get('/animals/{id}', 'AnimalController@show');

// Update animal record
$router->put('/animals/{id}', 'AnimalController@update', ['Admin', 'Staff', 'Veterinarian']);

// Delete animal record (soft delete)
$router->delete('/animals/{id}', 'AnimalController@destroy', ['Admin']);

// Update animal status only
$router->patch('/animals/{id}/status', 'AnimalController@updateStatus', ['Admin', 'Staff', 'Veterinarian']);

// ============================================
// IMPOUND RECORD ROUTES
// ============================================

// Add impound record to animal
$router->post('/animals/{id}/impound', 'AnimalController@addImpoundRecord', ['Admin', 'Staff']);

// Get impound record for animal
$router->get('/animals/{id}/impound', 'AnimalController@getImpoundRecord', ['Admin', 'Staff']);

// Update impound record
$router->put('/animals/{id}/impound', 'AnimalController@updateImpoundRecord', ['Admin', 'Staff']);

// ============================================
// ANIMAL IMAGE UPLOAD
// ============================================

// Upload animal image
$router->post('/animals/{id}/image', 'AnimalController@uploadImage', ['Admin', 'Staff', 'Veterinarian']);