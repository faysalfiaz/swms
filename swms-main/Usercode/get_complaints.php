<?php
session_start();
include '../classes/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]); 
    exit;
}

$database = new Database();
$db_conn = $database->getConnection();

$user_id = intval($_SESSION['user_id']);

$sql = "SELECT 
            r.id, 
            r.description, 
            r.location, 
            r.image, 
            r.status, 
            r.created_at,
            t.team_name,
            t.leader_phone,
            f.rating, 
            f.comments AS feedback,
            GROUP_CONCAT(c.category_name SEPARATOR ', ') AS categories
        FROM reports r
        LEFT JOIN cleaning_teams t ON r.team_id = t.id
        LEFT JOIN feedback f ON r.id = f.report_id 
        LEFT JOIN report_categories rc ON r.id = rc.report_id
        LEFT JOIN waste_categories c ON rc.category_id = c.id
        WHERE r.user_id = $user_id 
          AND (r.is_deleted_by_user = 0 OR r.is_deleted_by_user IS NULL)
        GROUP BY r.id
        ORDER BY r.id DESC";

$result = $db_conn->query($sql);

$complaints = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (isset($row['rating'])) {
            $row['rating'] = intval($row['rating']);
        }
        $complaints[] = $row;
    }
}

echo json_encode($complaints);
?>