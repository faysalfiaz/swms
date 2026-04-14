<?php
session_start(); // Start session to verify user
include_once '../classes/Database.php';
include_once '../classes/WasteManager.php';

// 1. Basic Security: Only logged-in users should be able to submit feedback
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

// 2. Initialize Objects
$database = new Database();
$db_connection = $database->getConnection();
$manager = new WasteManager($db_connection);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 3. Validate Inputs
    // Ensure the required fields are sent and not empty
    if (!isset($_POST['report_id']) || !isset($_POST['rating'])) {
        echo "Missing required data.";
        exit;
    }

    $report_id = $_POST['report_id'];
    $rating    = $_POST['rating'];
    $feedback  = isset($_POST['feedback']) ? $_POST['feedback'] : "";

    // 4. Execute Logic
    // This calls the method we wrote that checks for duplicates automatically
    if ($manager->saveFeedback($report_id, $rating, $feedback)) {
        echo "success";
    } else {
        // If saveFeedback returns false, it means either a duplicate exists 
        // or there was a database error.
        echo "You have already rated this report or an error occurred.";
    }
} else {
    echo "Invalid request method.";
}
?>