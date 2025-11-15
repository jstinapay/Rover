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
$rover_payment_method_id = $_GET['id'];

$host = "yamanote.proxy.rlwy.net";
$user = "root";
$pass = "ussforDJGtKQAqXiQTHUnStcDIwpdTja";
$dbname = "railway";
$port = "40768";
$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

//checks logged expenses if there are records that uses the payment method the rover wants to delete.
$sql_check = "SELECT COUNT(*) AS expense_count FROM expense WHERE rover_payment_method_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $rover_payment_method_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result()->fetch_assoc();
$expense_count = (int)$result_check['expense_count'];
$stmt_check->close();



//Dont delete if yes
if ($expense_count > 0) {
    $conn->close();
    header("Location: profile.php?error=in_use&failed_id=" . $rover_payment_method_id);
    exit();

   // delete if none
} else {
    $sql_delete = "DELETE FROM rover_payment_method WHERE rover_payment_method_id = ? AND rover_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $rover_payment_method_id, $rover_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    $conn->close();

    header("Location: profile.php?success=deleted");
    exit();
}
?>
