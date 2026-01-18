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

// Export invoices data (CSV, JSON, Excel)
$router->get('/invoices/export', 'BillingController@exportInvoices', ['Admin', 'Staff']);

// Get customers with unpaid invoices (for dropdown) - MUST be before /invoices/{id}
$router->get('/invoices/customers-with-bills', 'BillingController@customersWithUnpaidInvoices', ['Admin', 'Staff']);

// Get customer's unpaid invoices - MUST be before /invoices/{id}
$router->get('/invoices/customer/{userId}', 'BillingController@customerUnpaidInvoices', ['Admin', 'Staff']);

// Get single invoice with payments - MUST be after specific routes
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

// Export payments data (CSV, JSON, Excel)
$router->get('/payments/export', 'BillingController@exportPayments', ['Admin', 'Staff']);

// Get single payment
$router->get('/payments/{id}', 'BillingController@showPayment', ['Admin', 'Staff']);

// Record payment for invoice
$router->post('/payments', 'BillingController@recordPayment', ['Admin', 'Staff']);

// ============================================
// FEE CALCULATION
// ============================================

// Calculate fee for an animal (adoption or reclaim)
$router->get('/billing/calculate-fee', 'BillingController@calculateFee', ['Admin', 'Staff']);

// ============================================
// REPORTS
// ============================================

// Get financial summary
$router->get('/billing/summary', 'BillingController@financialSummary', ['Admin']);

// Get payments by date range
$router->get('/billing/report', 'BillingController@financialReport', ['Admin']);
