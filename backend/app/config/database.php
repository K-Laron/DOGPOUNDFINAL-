<?php
/**
 * Database Configuration and Connection Class
 * * @package AnimalShelter
 */

class Database {
    // ============================================
    // DATABASE CREDENTIALS
    // ============================================
    // Update these values for your environment
    
    private $host = "127.0.0.1"; // CHANGED: Use IP instead of 'localhost' for custom ports
    private $port = "3307";      // ADDED: Port 3307 to match your XAMPP MySQL setting
    private $database_name = "catarman_dog_pound_db";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";
    
    // ============================================
    // CONNECTION PROPERTIES
    // ============================================
    
    private $conn = null;
    private static $instance = null;

    /**
     * Get database connection
     * Uses lazy loading - connection is only created when needed
     * * @return PDO Database connection object
     * @throws Exception If connection fails
     */
    public function getConnection() {
        if ($this->conn === null) {
            try {
                // CHANGED: Added ";port={$this->port}" to the DSN string below
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database_name};charset={$this->charset}";
                
                $options = [
                    // Throw exceptions on errors
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    
                    // Return associative arrays by default
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    
                    // Use native prepared statements (Changed to true to support reused named parameters)
                    PDO::ATTR_EMULATE_PREPARES => true,
                    
                    // Set character encoding
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE utf8mb4_unicode_ci",
                    
                    // Don't use persistent connections
                    PDO::ATTR_PERSISTENT => false
                ];
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch (PDOException $e) {
                // It is good practice not to echo the full error to the user in production,
                // but for debugging, this log is fine.
                error_log("Database Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check configuration.");
            }
        }
        
        return $this->conn;
    }

    /**
     * Get singleton instance of Database class
     * * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback() {
        return $this->getConnection()->rollBack();
    }

    /**
     * Check if inside a transaction
     */
    public function inTransaction() {
        return $this->getConnection()->inTransaction();
    }
}