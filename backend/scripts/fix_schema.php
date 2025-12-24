<?php
// Fix Schema Script
// Adds missing columns to Users and Veterinarians tables

require_once __DIR__ . '/../app/config/database.php';

echo "Starting schema update...\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Add Preferences to Users
    echo "Checking Users table...\n";
    $stmt = $db->query("SHOW COLUMNS FROM Users LIKE 'Preferences'");
    if ($stmt->rowCount() == 0) {
        echo "Adding Preferences column to Users table...\n";
        $db->exec("ALTER TABLE Users ADD COLUMN Preferences JSON DEFAULT NULL");
        echo "✅ Preferences column added.\n";
    } else {
        echo "ℹ️ Preferences column already exists.\n";
    }

    // 2. Add Clinic_Name to Veterinarians
    echo "Checking Veterinarians table...\n";
    $stmt = $db->query("SHOW COLUMNS FROM Veterinarians LIKE 'Clinic_Name'");
    if ($stmt->rowCount() == 0) {
        echo "Adding Clinic_Name column to Veterinarians table...\n";
        $db->exec("ALTER TABLE Veterinarians ADD COLUMN Clinic_Name VARCHAR(100) DEFAULT NULL");
        echo "✅ Clinic_Name column added.\n";
    } else {
        echo "ℹ️ Clinic_Name column already exists.\n";
    }

    // 3. Add Bio to Veterinarians
    $stmt = $db->query("SHOW COLUMNS FROM Veterinarians LIKE 'Bio'");
    if ($stmt->rowCount() == 0) {
        echo "Adding Bio column to Veterinarians table...\n";
        $db->exec("ALTER TABLE Veterinarians ADD COLUMN Bio TEXT DEFAULT NULL");
        echo "✅ Bio column added.\n";
    } else {
        echo "ℹ️ Bio column already exists.\n";
    }

    echo "\nSchem update completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
