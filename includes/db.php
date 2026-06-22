<?php

// Database Configuration
$servername = "127.0.0.1";
$username = "root";
$password = "";
$database = "qr_attendance_v2";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8mb4");

?>
