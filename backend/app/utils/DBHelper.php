<?php
/**
 * Database helper utilities
 * - Validate SQL identifiers (table/column names) before interpolation
 */

class DBHelper
{
    /**
     * Validate a table or column name is a simple identifier (letters, numbers, underscore)
     * and optionally verify it exists in the current database.
     *
     * @param PDO $pdo
     * @param string $identifier
     * @return bool
     */
    public static function isValidIdentifier(PDO $pdo, string $identifier): bool
    {
        // Basic pattern check
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            return false;
        }

        // Verify it exists as a table in the current database
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl");
            $stmt->execute(['tbl' => $identifier]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int)($row['cnt'] ?? 0)) > 0;
        } catch (Exception $e) {
            // If the metadata check fails for some reason, err on the side of safety and reject
            error_log("DBHelper::isValidIdentifier error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Quote an identifier (table/column) using backticks after validation
     * @param string $identifier
     * @return string
     */
    public static function quoteIdentifier(string $identifier): string
    {
        return "`" . str_replace("`", "``", $identifier) . "`";
    }
}
