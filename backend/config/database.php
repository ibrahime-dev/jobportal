<?php
/**
 * Database Configuration for XAMPP
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'job_portal';
    private $username = 'root';
    private $password = '';  // Usually empty for XAMPP
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }

        return $this->conn;
    }
}

function getDatabase() {
    return new Database();
}

function executeQuery($query, $params = []) {
    $database = getDatabase();
    $conn = $database->getConnection();
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        throw new Exception("Database query failed");
    }
}

function fetchOne($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

function insertRecord($query, $params = []) {
    $database = getDatabase();
    $conn = $database->getConnection();
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $conn->lastInsertId();
    } catch(PDOException $e) {
        error_log("Insert error: " . $e->getMessage());
        throw new Exception("Database insert failed");
    }
}
?>