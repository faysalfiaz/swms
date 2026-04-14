<?php
session_start();
include '../classes/Database.php';
include '../classes/WasteManager.php';

// Set header to return JSON data
header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    echo json_encode([]); 
    exit;
}

$database = new Database();
$db_conn = $database->getConnection();

$user_id = intval($_SESSION['user_id']);

/**
 * UPDATED SQL QUERY
 * Using LEFT JOIN to combine reports and ratings.
 * This ensures that if a rating exists for a report, it is included in the JSON.
 */
$sql = "SELECT 
            r.id, 
            r.description, 
            r.location, 
            r.image, 
            r.status, 
            r.admin_remark,
            rt.rating, 
            rt.feedback 
        FROM reports r
        LEFT JOIN ratings rt ON r.id = rt.report_id 
        WHERE r.user_id = '$user_id' 
        ORDER BY r.id DESC";

$result = $db_conn->query($sql);

$complaints = [];

if ($result) {
    while($row = $result->fetch_assoc()){
        // Cast rating to int if it exists, otherwise it stays null
        if(isset($row['rating'])) {
            $row['rating'] = intval($row['rating']);
        }
        $complaints[] = $row;
    }
}

// Send the combined data back to index.php
echo json_encode($complaints);
?>