<?php
include_once 'Database.php';

interface SystemOperations {
    public function updateStatus($id, $status, $remark);
    public function getAllReports();
}

class WasteManager extends Database implements SystemOperations {

    public function __construct() {
        parent::__construct();
    }

    public function updateStatus($id, $status, $remark = "") {
        $safe_id = mysqli_real_escape_string($this->conn, $id);
        $safe_status = mysqli_real_escape_string($this->conn, $status);
        $safe_remark = mysqli_real_escape_string($this->conn, $remark);

        $sql = "UPDATE reports SET status = '$safe_status', admin_remark = '$safe_remark' WHERE id = '$safe_id'";
        return $this->conn->query($sql);
    }

    public function getAllReports() {
        $sql = "SELECT * FROM reports ORDER BY id DESC";
        return $this->conn->query($sql);
    }

    /**
     * Logic to save feedback with a duplicate check.
     */
    public function saveFeedback($report_id, $rating, $feedback) {
        $safe_report_id = intval($report_id);

        // Check if already exists
        $check = $this->conn->query("SELECT id FROM ratings WHERE report_id = $safe_report_id");
        if ($check && $check->num_rows > 0) return false;

        $safe_rating = intval($rating);
        $safe_feedback = mysqli_real_escape_string($this->conn, $feedback);
        
        $sql = "INSERT INTO ratings (report_id, rating, feedback) VALUES ($safe_report_id, $safe_rating, '$safe_feedback')";
        return $this->conn->query($sql);
    }

    /**
     * Helper to fetch rating for the UI
     */
    public function getRating($report_id) {
        $safe_id = intval($report_id);
        $sql = "SELECT * FROM ratings WHERE report_id = $safe_id";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }

    public function saveContact($name, $email, $subject, $message) {
        $sql = "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        return $stmt->execute();
    }
}
?>