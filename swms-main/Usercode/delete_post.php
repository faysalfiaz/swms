<?php
session_start();
include_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo "Database connection failed";
        exit();
    }

    $report_id = intval($_POST['report_id']);
    
    // Soft Delete Query: ডাটাবেস থেকে রিমুভ হবে না, শুধু ইউজারের জন্য 1 সেট হবে
    $stmt = $db->prepare("UPDATE reports SET is_deleted_by_user = 1 WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "failed: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "invalid_request";
}
?>