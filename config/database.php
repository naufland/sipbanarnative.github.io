<?php
/**
 * Database Configuration File
 * File: config/database.php
 */

// Database connection variables
$host = "localhost";
$dbname = "sipbanar"; // Sesuaikan dengan nama database Anda
$username = "root";
$password = ""; // Kosongkan jika tidak ada password

// Alternative database connection using class (optional)
class Database {
    private $host = "localhost";
    private $db_name = "sipbanar";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Test connection function (optional)
function testDatabaseConnection() {
    global $host, $dbname, $username, $password;
    
    try {
        $testConn = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
            $username, 
            $password
        );
        $testConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Database connection successful!";
        return true;
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        return false;
    }
}

// Uncomment the line below to test connection
// testDatabaseConnection();
?>