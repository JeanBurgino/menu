<?php
/**
 * Database Connection Handler
 * 
 * Singleton-Pattern für DB-Verbindungen mit Error-Handling
 */

class Database {
    private static $instance = null;
    private $pdo = null;
    
    /**
     * Private Constructor (Singleton)
     */
    private function __construct() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            $this->logError('DB Connection failed: ' . $e->getMessage());
            throw new Exception('Datenbankverbindung fehlgeschlagen', 500);
        }
    }
    
    /**
     * Get Singleton Instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO Connection
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Execute Query with Error Handling
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new Exception('Datenbankabfrage fehlgeschlagen', 500);
        }
    }
    
    /**
     * Fetch All Rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch Single Row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Execute Insert/Update/Delete
     */
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Get Last Insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Begin Transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit Transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback Transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }
    
    /**
     * Log Error
     */
    private function logError($message) {
        $logFile = LOGS_PATH . '/database.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Prevent Cloning
     */
    private function __clone() {}
    
    /**
     * Prevent Unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
