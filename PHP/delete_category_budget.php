<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
if (!isset($_GET['category_budget_id'])) {
    echo "<script>alert('No category budget selected.')</script>";
    header("Location: view_trip.php");
    exit();
}

if (!isset($_GET["trip_id"])) {
    echo "<script>alert('No trip selected.')</script>";
    header("Location: dashboard.php");
    exit();
}

$trip_id = $_GET["trip_id"];
$rover_id = $_SESSION['rover_id'];
$category_budget_id = $_GET['category_budget_id'];

require_once 'connect.php';

$sql = 'DELETE category_budget 
        FROM category_budget
        JOIN trip ON category_budget.trip_id = trip.trip_id
        WHERE category_budget.category_budget_id = ? AND trip.rover_id = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $category_budget_id, $rover_id);
if ($stmt->execute()) {
    header("Location: view_trip.php?trip_id=" . $trip_id);
    exit();
} else {
    echo "Error deleting category budget: " . $stmt->error;
}
$stmt->close();
$conn->close();
