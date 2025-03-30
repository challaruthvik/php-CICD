<?php

namespace Sephp\Database;

class DatabaseConnection {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $config = require __DIR__ . '/../../config/database.php';
        
        try {
            // Include port in connection string if specified
            $dsn = "mysql:host={$config['database']['host']}";
            
            // Add port if it exists
            if (isset($config['database']['port'])) {
                $dsn .= ";port={$config['database']['port']}";
            }
            
            // Complete the connection string
            $dsn .= ";dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
            
            $this->connection = new \PDO(
                $dsn,
                $config['database']['username'],
                $config['database']['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
        } catch (\PDOException $e) {
            throw new \Exception("Connection failed: " . $e->getMessage());
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
}