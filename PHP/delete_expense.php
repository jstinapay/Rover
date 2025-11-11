<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}

if (!isset($_GET['expense_id']) || !isset($_GET['trip_id'])) {
    header("Location: dashboard.php");
    exit();
}

$expense_id = $_GET["expense_id"];
$trip_id_redirect = $_GET['trip_id'];
$rover_id = $_SESSION['rover_id'];

require_once 'connect.php';

$sql = "DELETE e FROM expense e
        JOIN category_budget cb ON e.category_budget_id = cb.category_budget_id
        JOIN trip t ON cb.trip_id = t.trip_id
        WHERE e.expense_id = ? AND t.rover_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $expense_id, $rover_id);

if ($stmt->execute()) {
    header("Location: view_trip.php?trip_id=" . $trip_id_redirect);
    exit();
} else {
    echo "Error deleting expense: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>