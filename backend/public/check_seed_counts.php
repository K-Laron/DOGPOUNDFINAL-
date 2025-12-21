<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $tables = [
        'Roles' => 4,
        'Users' => 4,
        'Veterinarians' => 1,
        'Animals' => 10,
        'Impound_Records' => 6,
        'Medical_Records' => 10,
        'Inventory' => 22,
        'Adoption_Requests' => 4,
        'Invoices' => 4,
        'Payments' => 3,
        'Feeding_Records' => 16,
        'Activity_Logs' => 9
    ];
    
    echo "--- DATABASE SEED VERIFICATION ---\n";
    printf("%-20s | %-10s | %-10s | %-10s\n", 'Table', 'DB Count', 'Seed Count', 'Status');
    echo str_repeat('-', 60) . "\n";
    
    foreach ($tables as $table => $seedCount) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $dbCount = (int)$stmt->fetch()['count'];
        
        $status = ($dbCount === $seedCount) ? 'MATCH' : 'DIFF';
        if ($dbCount > $seedCount) $status = 'EXTRA DATA';
        if ($dbCount < $seedCount) $status = 'MISSING';
        
        printf("%-20s | %-10d | %-10d | %-10s\n", $table, $dbCount, $seedCount, $status);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
