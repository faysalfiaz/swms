<?php
session_start();
include 'db.php';


if(!isset($_SESSION['user_id'])){
    echo "Unauthorized"; exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id']; 
    $description = $_POST['description'];
    $location = $_POST['location'];
    
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $image = $_FILES['image']['name'];
    $target_file = $target_dir . basename($image);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
       
        $sql = "INSERT INTO reports (user_id, description, location, image, status) VALUES ('$user_id', '$description', '$location', '$image', 'Pending')";
        if ($conn->query($sql) === TRUE) {
            echo "success";
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Failed to upload image.";
    }
}
?>