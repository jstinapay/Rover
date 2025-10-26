<?php
// Database connection settings
$host = "localhost";
$user = "root";
$pass = "12345678";
$dbname = "travelbudgetingapp";

// connect to mysql
$conn = new mysqli($host, $user, $pass, $dbname);

//check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
