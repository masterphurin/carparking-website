<?php
/**
 * Database Connection Class
 * คลาสสำหรับการเชื่อมต่อฐานข้อมูล
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $config = require __DIR__ . '/database.php';
            
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            throw new Exception("การเชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // ป้องกันการ clone
    private function __clone() {}
    
    // ป้องกันการ unserialize
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function สำหรับเรียกใช้ database connection
 */
function getDatabase() {
    return Database::getInstance()->getConnection();
}
