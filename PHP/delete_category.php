<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}

if (!isset($_GET['category_id']) || !isset($_GET['trip_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'connect.php';
$rover_id = $_SESSION['rover_id'];
$trip_id = $_GET['trip_id'];

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$sql = "DELETE FROM category WHERE category_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);

if ($stmt->execute()) {
    header("Location: view_trip.php?trip_id=" . $_GET['trip_id']);
    exit();
}
$stmt->close();
$conn->close();
?>