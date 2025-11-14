<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
$rover_id = $_SESSION['rover_id'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php");
    exit();
}

if (!isset($_POST['method_name']) || empty(trim($_POST['method_name']))) {
    header("Location: profile.php?error=empty");
    exit();
}

$method_name = trim($_POST['method_name']);

$host = "yamanote.proxy.rlwy.net";
$user = "root";
$pass = "ussforDJGtKQAqXiQTHUnStcDIwpdTja";
$dbname = "railway";
$port = "40768";

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$sql = "INSERT INTO payment_method (payment_method_name, rover_id) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $method_name, $rover_id);

$stmt->execute();
$stmt->close();
$conn->close();

header("Location: profile.php?added=1");
exit();
?>

