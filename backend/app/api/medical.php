<?php
/**
 * Medical Records Routes
 * 
 * @package AnimalShelter
 */

// ============================================
// MEDICAL RECORD ROUTES
// ============================================

// Get all medical records (with filters)
$router->get('/medical', 'MedicalController@index', ['Admin', 'Staff', 'Veterinarian']);

// Get medical records for specific animal
$router->get('/medical/animal/{animalId}', 'MedicalController@byAnimal', ['Admin', 'Staff', 'Veterinarian']);

// Get medical stats summary
$router->get('/medical/stats/summary', 'MedicalController@stats', ['Admin', 'Staff', 'Veterinarian']);

// Get upcoming treatments/vaccinations
$router->get('/medical/upcoming', 'MedicalController@upcoming', ['Admin', 'Staff', 'Veterinarian']);

// Get overdue treatments
$router->get('/medical/overdue', 'MedicalController@overdue', ['Admin', 'Staff', 'Veterinarian']);

// Get single medical record
$router->get('/medical/{id}', 'MedicalController@show', ['Admin', 'Staff', 'Veterinarian']);

// Create medical record
$router->post('/medical', 'MedicalController@store', ['Admin', 'Veterinarian']);

// Update medical record
$router->put('/medical/{id}', 'MedicalController@update', ['Admin', 'Veterinarian']);

// Delete medical record
$router->delete('/medical/{id}', 'MedicalController@destroy', ['Admin', 'Veterinarian']);

// ============================================
// FEEDING RECORD ROUTES
// ============================================

// Get feeding records for animal
$router->get('/feeding/animal/{animalId}', 'MedicalController@feedingByAnimal', ['Admin', 'Staff', 'Veterinarian']);

// Get today's feeding summary
$router->get('/feeding/today', 'MedicalController@feedingToday', ['Admin', 'Staff', 'Veterinarian']);

// Record feeding
$router->post('/feeding', 'MedicalController@recordFeeding', ['Admin', 'Staff', 'Veterinarian']);

// ============================================
// VETERINARIAN ROUTES
// ============================================

// Get all veterinarians
$router->get('/veterinarians', 'MedicalController@listVeterinarians', ['Admin', 'Staff', 'Veterinarian']);

// Get veterinarian details
$router->get('/veterinarians/{id}', 'MedicalController@showVeterinarian', ['Admin', 'Staff', 'Veterinarian']);