<?php
session_start();

if(!isset($_SESSION['rover_id'])){
    header("Location: ../login.html");
    exit();
}

if(!isset($_GET['trip_id'])){
    echo "<script>alert('No trip selected.')</script>";
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit();
}

require_once 'connect.php';
$rover_id = $_SESSION['rover_id'];
$trip_id = $_GET['trip_id'];

// ON DELETE CASCADE: budget_category and expenses will also be deleted 
$sql = "DELETE FROM trip WHERE trip_id = ? AND rover_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $trip_id, $rover_id);

if ($stmt->execute()) {
    header("Location: dashboard.php");
    exit();
}
$stmt->close();
$conn->close();
?>