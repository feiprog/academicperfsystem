<?php
require_once __DIR__ . '/../config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset to handle special characters
            $this->connection->set_charset("utf8mb4");
            
            // Set timezone
            $this->connection->query("SET time_zone = '" . date('P') . "'");
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }
    
    public function commit() {
        $this->connection->commit();
    }
    
    public function rollback() {
        $this->connection->rollback();
    }
    
    public function prepare($query) {
        $stmt = $this->connection->prepare($query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $this->connection->error);
        }
        return $stmt;
    }
    
    public function query($query) {
        $result = $this->connection->query($query);
        if ($result === false) {
            throw new Exception("Query failed: " . $this->connection->error);
        }
        return $result;
    }
    
    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }
    
    public function getAffectedRows() {
        return $this->connection->affected_rows;
    }
    
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Prevent cloning of the instance
    private function __clone() {}
    
    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    public function __destruct() {
        $this->close();
    }
} 