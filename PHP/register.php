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

// Get form data
$fullname = $_POST['fullname'];
$email = $_POST['email'];
$password = $_POST['password'];

//prepare and execute SQL
$sql = "INSERT INTO traveller_table (traveller_name, traveller_email, traveller_password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $fullname, $email, $password);

if ($stmt->execute()) {
    // On success, redirect to createTrip.html
    header("Location: ../login.html"); // Assumes createTrip.html is one level up
    exit(); // IMPORTANT: Stops the script from running further
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>