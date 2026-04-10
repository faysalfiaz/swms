<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. DATABASE & CLASS INCLUSION
include '../classes/Database.php';
include '../classes/WasteManager.php';

$database = new Database();
$db_connection = $database->getConnection();
$manager = new WasteManager($db_connection);

// 2. OPERATIONAL COMMAND HANDLER (Assign/Clean)
if (isset($_GET['action']) && isset($_GET['id'])) {
    // Security Check: Only logged-in admins can trigger actions
    if (!isset($_SESSION['admin_verified'])) {
        header("Location: login.php");
        exit();
    }
    
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $new_status = "";

    if ($action == 'assign') {
        $new_status = 'Assigned';
    } elseif ($action == 'clean') {
        $new_status = 'Cleaned';
    }

    if ($new_status != "") {
        if ($manager->updateStatus($id, $new_status)) {
            // Redirect back to dashboard after successful update
            header("Location: admin_dashboard.php?success=active");
            exit();
        }
    }
}

// 3. SESSION TERMINATION (Logout)
if (isset($_GET['logout'])) { 
    session_destroy(); 
    header("Location: login.php?status=terminated"); 
    exit(); 
}
?>