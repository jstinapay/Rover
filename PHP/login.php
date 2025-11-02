<?php
session_start();
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

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT rover_id, first_name, email, password, currency_code
        FROM rover
        WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if ($user['password'] == $password) {
        $_SESSION['rover_id'] = $user['rover_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['currency_code'] = $user['currency_code'];
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<p style = 'color: red'> Incorrect password>";
    }
} else {
    echo "<p style = 'color: red'> Incorrect email>";
}
?>
