<?php
session_start();
include_once '../classes/Database.php';

$database = new Database();
$db = $database->getConnection();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!empty($email) && !empty($password)) {
    $email = mysqli_real_escape_string($db, $email);
    $query = "SELECT * FROM admins WHERE email = '$email' LIMIT 1";
    $result = $db->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($password === $user['password']) {
           
            $_SESSION['admin_id'] = $user['id']; 
            echo "success";
        } else {
            echo "wrong_password";
        }
    } else {
        echo "not_found";
    }
}
?>