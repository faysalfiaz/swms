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
 * We added 'admin_remark' to the SELECT statement so the 
 * frontend JavaScript can access the rejection reason.
 */
$sql = "SELECT id, description, location, image, status, rating, feedback, admin_remark 
        FROM reports 
        WHERE user_id='$user_id' 
        ORDER BY id DESC";

$result = $db_conn->query($sql);

$complaints = [];

if ($result) {
    while($row = $result->fetch_assoc()){
        $complaints[] = $row;
    }
}

// Send the data back to index.php
echo json_encode($complaints);
?>