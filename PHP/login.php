<?php
session_start();
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

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT traveller_id, traveller_name, traveller_email, traveller_password
        FROM traveller_table
        WHERE traveller_email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if ($user['traveller_password'] == $password) {
        $_SESSION['traveller_id'] = $user['traveller_id'];
        $_SESSION['traveller_name'] = $user['traveller_name'];
        header("Location: ../createTrip.html");
        exit();
    } else {
        echo "<p style = 'color: red'> Incorrect password>";
    }
} else {
    echo "<p style = 'color: red'> Incorrect email>";
}

