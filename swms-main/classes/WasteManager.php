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
        $stmt = $this->conn->prepare("UPDATE reports SET status = ?, admin_remark = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $remark, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getAllReports() {
        $sql = "SELECT r.*, ct.team_name, GROUP_CONCAT(wc.category_name SEPARATOR ', ') AS categories 
                FROM reports r 
                LEFT JOIN cleaning_teams ct ON r.team_id = ct.id 
                LEFT JOIN report_categories rc ON r.id = rc.report_id 
                LEFT JOIN waste_categories wc ON rc.category_id = wc.id 
                GROUP BY r.id 
                ORDER BY r.id DESC";
        return $this->conn->query($sql);
    }

    public function createReport($userId, $description, $location, $image, $categoryIds = []) {
        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare("INSERT INTO reports (user_id, description, location, image, status, created_at) VALUES (?, ?, ?, ?, 'Pending', NOW())");
            $stmt->bind_param("isss", $userId, $description, $location, $image);
            $stmt->execute();
            
            $reportId = $stmt->insert_id;
            $stmt->close();

            if (!empty($categoryIds) && is_array($categoryIds)) {
                $catStmt = $this->conn->prepare("INSERT INTO report_categories (report_id, category_id) VALUES (?, ?)");
                foreach ($categoryIds as $catId) {
                    $cId = intval($catId);
                    $catStmt->bind_param("ii", $reportId, $cId);
                    $catStmt->execute();
                }
                $catStmt->close();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function getUserReports($userId) {
        $sql = "SELECT 
                    r.id, 
                    r.description, 
                    r.location, 
                    r.image, 
                    r.status, 
                    r.created_at,
                    ct.team_name,
                    ct.leader_phone,
                    f.rating,
                    f.comments AS feedback,
                    GROUP_CONCAT(wc.category_name SEPARATOR ', ') AS categories
                FROM reports r
                LEFT JOIN cleaning_teams ct ON r.team_id = ct.id
                LEFT JOIN feedback f ON r.id = f.report_id
                LEFT JOIN report_categories rc ON r.id = rc.report_id
                LEFT JOIN waste_categories wc ON rc.category_id = wc.id
                WHERE r.user_id = ?
                GROUP BY r.id
                ORDER BY r.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        $stmt->close();

        return $reports;
    }

    public function saveFeedback($report_id, $rating, $feedback) {
        $safe_report_id = intval($report_id);

        $checkStmt = $this->conn->prepare("SELECT id FROM feedback WHERE report_id = ?");
        $checkStmt->bind_param("i", $safe_report_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $checkResult->num_rows > 0) {
            $checkStmt->close();
            return false; 
        }
        $checkStmt->close();

        $safe_rating = intval($rating);

        $stmt = $this->conn->prepare("INSERT INTO feedback (report_id, rating, comments, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $safe_report_id, $safe_rating, $feedback);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function getRating($report_id) {
        $safe_id = intval($report_id);
        $stmt = $this->conn->prepare("SELECT * FROM feedback WHERE report_id = ?");
        $stmt->bind_param("i", $safe_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
        $stmt->close();
        return $data;
    }

    public function assignTeamAndStatus($reportId, $teamId, $status) {
        $stmt = $this->conn->prepare("UPDATE reports SET team_id = ?, status = ? WHERE id = ?");
        $stmt->bind_param("isi", $teamId, $status, $reportId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function saveContact($name, $email, $subject, $message) {
        $sql = "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
?>