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
// - Adopter sees only their own requests
$router->get('/adoptions', 'AdoptionController@index', ['Admin', 'Staff', 'Adopter']);

// Get single adoption request
$router->get('/adoptions/{id}', 'AdoptionController@show', ['Admin', 'Staff', 'Adopter']);

// Create adoption request (Adopter only)
$router->post('/adoptions', 'AdoptionController@store', ['Adopter']);

// Process adoption request (Admin/Staff)
// Status: Interview Scheduled, Approved, Rejected, Completed
$router->put('/adoptions/{id}/process', 'AdoptionController@process', ['Admin', 'Staff']);

// Cancel adoption request (Adopter - only their pending requests)
$router->put('/adoptions/{id}/cancel', 'AdoptionController@cancel', ['Adopter']);

// ============================================
// ADOPTION STATISTICS
// ============================================

// Get adoption statistics
$router->get('/adoptions/stats/summary', 'AdoptionController@statistics', ['Admin', 'Staff']);

// Get adoption history for an animal
$router->get('/adoptions/animal/{animalId}', 'AdoptionController@animalHistory', ['Admin', 'Staff']);

// Get adoption history for a user
$router->get('/adoptions/user/{userId}', 'AdoptionController@userHistory', ['Admin', 'Staff']);