<?php
class Database {
    // Encapsulation: Database credentials are kept private
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "swms";
    protected $conn; // Protected so child classes can use it

    public function __construct() {
        // Automatically connects when an object is created
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        if ($this->conn->connect_error) {
            die("Database Connection Error: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>