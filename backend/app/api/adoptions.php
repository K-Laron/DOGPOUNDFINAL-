<?php
/**
 * Adoption Management Routes
 * 
 * @package AnimalShelter
 */

// ============================================
// ADOPTION REQUEST ROUTES
// ============================================

// List adoption requests
// - Admin/Staff see all requests
// - Any user sees only their own requests (unless Admin/Staff)
$router->get('/adoptions', 'AdoptionController@index', ['*']);

// Get single adoption request
$router->get('/adoptions/{id}', 'AdoptionController@show', ['*']);

// Create adoption request (Any authenticated user)
$router->post('/adoptions', 'AdoptionController@store', ['*']);

// Process adoption request (Admin/Staff)
// Status: Interview Scheduled, Approved, Rejected, Completed
$router->put('/adoptions/{id}/process', 'AdoptionController@process', ['Admin', 'Staff', 'Veterinarian']);

// Cancel adoption request (Any user - only their pending requests)
$router->put('/adoptions/{id}/cancel', 'AdoptionController@cancel', ['*']);

// ============================================
// ADOPTION STATISTICS
// ============================================

// Get adoption statistics
$router->get('/adoptions/stats/summary', 'AdoptionController@statistics', ['Admin', 'Staff', 'Veterinarian']);

// Get adoption history for an animal
$router->get('/adoptions/animal/{animalId}', 'AdoptionController@animalHistory', ['Admin', 'Staff', 'Veterinarian']);

// Get adoption history for a user
$router->get('/adoptions/user/{userId}', 'AdoptionController@userHistory', ['Admin', 'Staff', 'Veterinarian']);