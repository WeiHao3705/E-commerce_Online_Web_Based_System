<?php
class Database {
    private $host = "localhost";
    private $db_name = "ecommerce_db";
    private $username = "root";
    private $password = "";
    public $conn;

    // Get the database connection
    public function getConnection() {
        if ($this->conn === null) { // only create connection if it doesn't exist
            try {
                $this->conn = new PDO(
                    "mysql:host=".$this->host.";dbname=".$this->db_name,
                    $this->username,
                    $this->password
                );
                $this->conn->exec("set names utf8");
            } catch(PDOException $exception) {
                echo "Connection error: " . $exception->getMessage();
                exit;
            }
        }
        return $this->conn;
    }
}
