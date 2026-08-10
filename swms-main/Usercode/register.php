
<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $fullname = $_POST['fullname']; 
    $email = $_POST['email'];
    $password = $_POST['password'];

   
    $sql = "INSERT INTO users (fullname, email, password) VALUES ('$fullname', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {
        echo "success"; 
    } else {
        echo "Error: " . $conn->error;
    }
}
?>