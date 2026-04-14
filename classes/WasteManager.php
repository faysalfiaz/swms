<?php
include_once 'Database.php';

/**
 * 1. Abstraction: Defining a blueprint using an Interface.
 * Any class implementing this must have these methods.
 */
interface SystemOperations {
    public function updateStatus($id, $status, $remark);
    public function getAllReports();
}

/**
 * 2. Inheritance: WasteManager inherits connection from Database class.
 * 3. Polymorphism: It implements the 'SystemOperations' interface.
 */
class WasteManager extends Database implements SystemOperations {

    public function __construct() {
        // Calling the parent class (Database) constructor
        parent::__construct();
    }

    // 4. Polymorphism (Method Implementation): Providing specific logic for the interface method
    public function updateStatus($id, $status, $remark = "") {
        $safe_id = mysqli_real_escape_string($this->conn, $id);
        $safe_status = mysqli_real_escape_string($this->conn, $status);
        $safe_remark = mysqli_real_escape_string($this->conn, $remark);

        $sql = "UPDATE reports 
                SET status = '$safe_status', 
                    admin_remark = '$safe_remark' 
                WHERE id = '$safe_id'";

        return $this->conn->query($sql);
    }

    // Fetch all waste reports from the database
    public function getAllReports() {
        $sql = "SELECT * FROM reports ORDER BY id DESC";
        return $this->conn->query($sql);
    }

    // Save user feedback and rating
    public function saveFeedback($id, $rating, $feedback) {
        $safe_id = intval($id);
        $safe_rating = mysqli_real_escape_string($this->conn, $rating);
        $safe_feedback = mysqli_real_escape_string($this->conn, $feedback);
        
        $sql = "UPDATE reports SET rating = '$safe_rating', feedback = '$safe_feedback' WHERE id = $safe_id";
        return $this->conn->query($sql);
    }

    // New: Save Contact Us messages using Prepared Statements for security
    public function saveContact($name, $email, $subject, $message) {
        $sql = "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        return $stmt->execute();
    }
}
?>