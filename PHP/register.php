<?php
// Database connection settings
$host = "yamanote.proxy.rlwy.net";
$user = "root";
$pass = "ussforDJGtKQAqXiQTHUnStcDIwpdTja";
$dbname = "railway";
$port = "40768";

$conn = new mysqli($host, $user, $pass, $dbname, $port);

//check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$phone_number = $_POST['phone_number'];
$currency_code = $_POST['currency_code'];
$email = $_POST['email'];
$password = $_POST['password'];

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// check if email is already registered, if yes dont duplicate
$sql = "SELECT * FROM rover WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<script>alert('Email already registered');</script>";
    echo "<script>window.location.href = '../register.html';</script>";
    exit();
}

//prepare and execute SQL
$sql = "INSERT INTO rover (first_name, last_name, phone_number, currency_code, email, password) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $first_name, $last_name, $phone_number, $currency_code, $email, $hashed_password);

if ($stmt->execute()) {
    // On success, redirect to login.html
    header("Location: ../login.html"); // Assumes createTrip.html is one level up
    exit(); // IMPORTANT: Stops the script from running further
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>