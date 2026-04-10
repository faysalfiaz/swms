<?php
class WasteManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Method to update report status
    public function updateStatus($id, $status) {
        $id = intval($id);
        return $this->db->query("UPDATE reports SET status='$status' WHERE id=$id");
    }

    // Method to fetch all reports
    public function getAllReports() {
        return $this->db->query("SELECT * FROM reports ORDER BY id DESC");
    }

    // Method to save user feedback and rating
    public function saveFeedback($id, $rating, $feedback) {
        $id = intval($id);
        $feedback = $this->db->real_escape_string($feedback);
        return $this->db->query("UPDATE reports SET rating = '$rating', feedback = '$feedback' WHERE id = $id");
    }
}
?>