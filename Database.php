<?php
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "mindspring_clinic";
    private $conn;

    public function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new Exception("Database connection error");
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql) {
        try {
            $result = $this->conn->query($sql);
            if ($result === false) {
                throw new Exception("Query failed: " . $this->conn->error);
            }
            return $result;
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new Exception("Database query error");
        }
    }

    public function prepare($sql) {
        try {
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            return $stmt;
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new Exception("Database prepare error");
        }
    }

    public function escapeString($string) {
        return $this->conn->real_escape_string($string);
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    public function getLastError() {
        return $this->conn->error;
    }

    public function getLastInsertId() {
        return $this->conn->insert_id;
    }

    public function beginTransaction() {
        $this->conn->begin_transaction();
    }

    public function commit() {
        $this->conn->commit();
    }

    public function rollback() {
        $this->conn->rollback();
    }
}
?>