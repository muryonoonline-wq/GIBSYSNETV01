<?php
// backend/config/database.php

class Database {
    private $host = "localhost";
    private $db_name = "gibsysnet";
    private $username = "root";
    private $password = "";
    public $conn;
    public $error;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            $this->error = "Connection error: " . $exception->getMessage();
            error_log($this->error);
        }
    }

    public function getConnection() {
        if ($this->conn === null) {
            $this->connect();
        }
        return $this->conn;
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollBack() {
        return $this->conn->rollBack();
    }

    // Helper method untuk eksekusi query
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            return false;
        }
    }

    // Helper method untuk mendapatkan single row
    public function getSingle($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }

    // Helper method untuk mendapatkan multiple rows
    public function getAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    // Helper method untuk insert data
    public function insert($table, $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($data);
            return $this->conn->lastInsertId();
        } catch(PDOException $e) {
            error_log("Insert Error: " . $e->getMessage());
            return false;
        }
    }

    // Helper method untuk update data
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $setClause = implode(", ", $set);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute(array_merge($data, $whereParams));
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Update Error: " . $e->getMessage());
            return false;
        }
    }

    // Helper method untuk delete data
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Delete Error: " . $e->getMessage());
            return false;
        }
    }

    // Method untuk check koneksi database
    public function checkConnection() {
        if ($this->conn) {
            try {
                $this->conn->query("SELECT 1");
                return true;
            } catch (PDOException $e) {
                return false;
            }
        }
        return false;
    }

    // Method untuk backup database (simplified)
    public function backup($backupPath) {
        $tables = $this->getAll("SHOW TABLES");
        $output = "";
        
        foreach($tables as $table) {
            $tableName = current($table);
            
            // Drop table jika sudah ada
            $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n\n";
            
            // Create table structure
            $createTable = $this->getSingle("SHOW CREATE TABLE `{$tableName}`");
            $output .= $createTable['Create Table'] . ";\n\n";
            
            // Insert data
            $rows = $this->getAll("SELECT * FROM `{$tableName}`");
            if (count($rows) > 0) {
                $columns = array_keys($rows[0]);
                $output .= "INSERT INTO `{$tableName}` (`" . implode("`, `", $columns) . "`) VALUES\n";
                
                $values = [];
                foreach($rows as $row) {
                    $rowValues = array_map(function($value) {
                        if ($value === null) return "NULL";
                        return "'" . addslashes($value) . "'";
                    }, $row);
                    $values[] = "(" . implode(", ", $rowValues) . ")";
                }
                
                $output .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        // Save to file
        return file_put_contents($backupPath, $output) !== false;
    }
}

// Singleton instance
$database = new Database();