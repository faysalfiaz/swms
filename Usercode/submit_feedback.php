<?php
// Include OOP classes
include '../classes/Database.php';
include '../classes/WasteManager.php';

// Create instances of the classes
$database = new Database();
$manager = new WasteManager($database->getConnection());

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_id = $_POST['report_id'];
    $rating = $_POST['rating'];
    $feedback = $_POST['feedback'];

    // Execute the feedback logic hidden inside the class method
    if ($manager->saveFeedback($report_id, $rating, $feedback)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>