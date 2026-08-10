<?php
$conn = new mysqli("localhost", "root", "", "swms");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>