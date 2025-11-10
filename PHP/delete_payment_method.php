<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
if (!isset($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$rover_id = $_SESSION['rover_id'];
$payment_method_id = $_GET['id'];

$host = "yamanote.proxy.rlwy.net";
$user = "root";
$pass = "ussforDJGtKQAqXiQTHUnStcDIwpdTja";
$dbname = "railway";
$port = "40768";
$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$sql = "DELETE FROM payment_method WHERE payment_method_id = ? AND rover_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $payment_method_id, $rover_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: profile.php");
exit();
?>
