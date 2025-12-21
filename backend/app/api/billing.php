<?php
/**
 * Billing & Payment Routes
 * 
 * @package AnimalShelter
 */

// ============================================
// INVOICE ROUTES
// ============================================

// List invoices
// - Admin/Staff see all invoices
// - Adopter sees only their own invoices
$router->get('/invoices', 'BillingController@indexInvoices', ['Admin', 'Staff', 'Adopter']);

// Get invoice statistics
$router->get('/invoices/stats/summary', 'BillingController@invoiceStatistics', ['Admin', 'Staff']);

// Get single invoice with payments
$router->get('/invoices/{id}', 'BillingController@showInvoice', ['Admin', 'Staff', 'Adopter']);

// Create invoice
$router->post('/invoices', 'BillingController@createInvoice', ['Admin', 'Staff']);

// Cancel invoice
$router->put('/invoices/{id}/cancel', 'BillingController@cancelInvoice', ['Admin']);

// ============================================
// PAYMENT ROUTES
// ============================================

// List all payments
$router->get('/payments', 'BillingController@indexPayments', ['Admin', 'Staff']);

// Get single payment
$router->get('/payments/{id}', 'BillingController@showPayment', ['Admin', 'Staff']);

// Record payment for invoice
$router->post('/payments', 'BillingController@recordPayment', ['Admin', 'Staff']);

// ============================================
// REPORTS
// ============================================

// Get financial summary
$router->get('/billing/summary', 'BillingController@financialSummary', ['Admin']);

// Get payments by date range
$router->get('/billing/report', 'BillingController@financialReport', ['Admin']);