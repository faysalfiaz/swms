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

    /**
     * Returns the database connection object
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Method to update report status and admin remarks
     */
    public function updateReportStatus($id, $status, $remark) {
        // Using Prepared Statements to prevent SQL Injection
        $sql = "UPDATE reports SET status = ?, admin_remark = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            // "ssi" stands for string, string, integer
            $stmt->bind_param("ssi", $status, $remark, $id);
            if ($stmt->execute()) {
                return true;
            }
        }
        return false;
    }
}
?>