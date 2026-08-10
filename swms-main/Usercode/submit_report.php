<?php
session_start();
include_once '../classes/Database.php';
include_once '../classes/WasteManager.php';

if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $categories = isset($_POST['categories']) ? $_POST['categories'] : [];

    if (empty($location) || !isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo "Location and Photo are required.";
        exit;
    }

    $image = $_FILES['image'];
    $imageExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
    $imageName = time() . '_' . uniqid() . '.' . $imageExtension;
    
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $targetFile = $targetDir . $imageName;

    if (move_uploaded_file($image['tmp_name'], $targetFile)) {
        $manager = new WasteManager();

        if ($manager->createReport($userId, $description, $location, $imageName, $categories)) {
            echo "success";
        } else {
            echo "Failed to save report to database.";
        }
    } else {
        echo "Failed to upload image.";
    }
} else {
    echo "Invalid request method.";
}
?>