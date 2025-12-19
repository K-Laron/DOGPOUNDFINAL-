<?php
/**
 * Animal Shelter Management System - API Entry Point
 */

require_once __DIR__ . '/config/config.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// API Information
$apiInfo = [
    'name' => APP_NAME,
    'version' => APP_VERSION,
    'endpoints' => [
        'auth' => [
            'POST /api/auth/login.php' => 'User login',
            'POST /api/auth/register.php' => 'User registration'
        ],
        'users' => [
            'GET /api/users/' => 'List users (Admin)',
            'GET /api/users/single.php?id=X' => 'Get user details',
            'PUT /api/users/single.php?id=X' => 'Update user',
            'DELETE /api/users/single.php?id=X' => 'Delete user (Admin)'
        ],
        'animals' => [
            'GET /api/animals/' => 'List animals',
            'POST /api/animals/' => 'Create animal (Staff/Admin)',
            'GET /api/animals/single.php?id=X' => 'Get animal details',
            'PUT /api/animals/single.php?id=X' => 'Update animal',
            'DELETE /api/animals/single.php?id=X' => 'Delete animal (Admin)',
            'GET /api/animals/available.php' => 'List available animals'
        ],
        'medical' => [
            'GET /api/medical/?animal_id=X' => 'Get medical records',
            'POST /api/medical/' => 'Create medical record (Vet)',
            'GET /api/medical/upcoming.php' => 'Get upcoming treatments'
        ],
        'adoptions' => [
            'GET /api/adoptions/' => 'List adoption requests',
            'POST /api/adoptions/' => 'Create adoption request',
            'PUT /api/adoptions/process.php?id=X' => 'Process request (Staff)'
        ],
        'inventory' => [
            'GET /api/inventory/' => 'List inventory items',
            'POST /api/inventory/' => 'Create item',
            'GET /api/inventory/alerts.php' => 'Get stock alerts'
        ],
        'billing' => [
            'GET /api/billing/invoices.php' => 'List invoices',
            'POST /api/billing/invoices.php' => 'Create invoice',
            'POST /api/billing/payments.php' => 'Record payment'
        ],
        'dashboard' => [
            'GET /api/dashboard/stats.php' => 'Get dashboard statistics'
        ]
    ],
    'documentation' => BASE_URL . '/docs'
];

echo json_encode($apiInfo, JSON_PRETTY_PRINT);