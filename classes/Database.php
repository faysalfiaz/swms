<?php
class Database {
    // Database connection parameters
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "swms";
    public $conn;

    // The Constructor: Automatically runs when a new Database object is created
    public function __construct() {
        // Create a new connection to the MySQL database
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        // Check if the connection was successful
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    // Getter function to provide the connection object to other parts of the app
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Update the status and feedback of a specific report.
     * $id: The ID of the report
     * $status: Can be 'approved' or 'rejected'
     * $remark: Feedback message from the Admin
     */
    public function updateReportStatus($id, $status, $remark) {
        // Sanitize input to prevent SQL Injection attacks
        $safe_id = mysqli_real_escape_string($this->conn, $id);
        $safe_status = mysqli_real_escape_string($this->conn, $status);
        $safe_remark = mysqli_real_escape_string($this->conn, $remark);

        // SQL Query to update the record in the 'reports' table
        $sql = "UPDATE reports 
                SET status = '$safe_status', 
                    admin_remark = '$safe_remark' 
                WHERE id = '$safe_id'";

        // Execute the query and return true if successful
        if ($this->conn->query($sql)) {
            return true; 
        } else {
            return false; 
        }
    }
}
?>