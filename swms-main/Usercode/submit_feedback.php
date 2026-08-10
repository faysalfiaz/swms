<?php
session_start();
include_once '../classes/Database.php';
include_once '../classes/WasteManager.php';

if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access.";
    exit;
}

$database = new Database();
$db_connection = $database->getConnection();
$manager = new WasteManager($db_connection);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['report_id']) || !isset($_POST['rating'])) {
        echo "Missing required data.";
        exit;
    }

    $report_id = intval($_POST['report_id']);
    $rating    = intval($_POST['rating']);
    $feedback  = isset($_POST['feedback']) ? trim($_POST['feedback']) : "";

    if ($rating < 1 || $rating > 5) {
        echo "Invalid rating value.";
        exit;
    }

    if ($manager->saveFeedback($report_id, $rating, $feedback)) {
        echo "success";
    } else {
        echo "You have already rated this report or an error occurred.";
    }
} else {
    echo "Invalid request method.";
}
?>