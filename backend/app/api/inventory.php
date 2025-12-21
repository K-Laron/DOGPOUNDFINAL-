<?php
/**
 * Inventory Management Routes
 * 
 * @package AnimalShelter
 */

// ============================================
// INVENTORY ITEM ROUTES
// ============================================

// List all inventory items
$router->get('/inventory', 'InventoryController@index', ['Admin', 'Staff']);

// Get inventory alerts (low stock & expiring)
$router->get('/inventory/alerts', 'InventoryController@alerts', ['Admin', 'Staff']);

// Get low stock items only
$router->get('/inventory/low-stock', 'InventoryController@lowStock', ['Admin', 'Staff']);

// Get expiring items only
$router->get('/inventory/expiring', 'InventoryController@expiring', ['Admin', 'Staff']);

// Get inventory statistics
$router->get('/inventory/stats/summary', 'InventoryController@statistics', ['Admin', 'Staff']);

// Get single inventory item
$router->get('/inventory/{id}', 'InventoryController@show', ['Admin', 'Staff']);

// Create inventory item
$router->post('/inventory', 'InventoryController@store', ['Admin', 'Staff']);

// Update inventory item
$router->put('/inventory/{id}', 'InventoryController@update', ['Admin', 'Staff']);

// Adjust stock quantity (add/subtract)
$router->patch('/inventory/{id}/adjust', 'InventoryController@adjustStock', ['Admin', 'Staff']);

// Delete inventory item
$router->delete('/inventory/{id}', 'InventoryController@destroy', ['Admin']);

// ============================================
// INVENTORY CATEGORIES
// ============================================

// Get items by category
$router->get('/inventory/category/{category}', 'InventoryController@byCategory', ['Admin', 'Staff']);