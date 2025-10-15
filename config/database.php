<?php
// Database Configuration for XAMPP (MySQL)
// Uses PDO for secure connection and error handling

class Database {
    private $host = "localhost";   // XAMPP default host
    private $db_name = "eternal_legacy"; // <-- change to your actual database name
    private $username = "root";    // default MySQL user in XAMPP
    private $password = "";        // default is empty (no password)
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // DSN = Data Source Name
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Recommended PDO attributes
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // Log and show a friendly message
            error_log("❌ Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please check XAMPP and credentials.");
        }

        return $this->conn;
    }
}
?>
