<?php
session_start();
require_once 'connect.php';

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
    $hashed_password = $user['password'];

    if (password_verify($password, $hashed_password)) {
        $_SESSION['rover_id'] = $user['rover_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['currency_code'] = $user['currency_code'];
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Incorrect email or password');</script>";;
        echo "<script>window.location.href = '../login.html';</script>";
        exit();
    }
} else {
    echo "<script>alert('Incorrect email or password');</script>";
    echo "<script>window.location.href = '../login.html';</script>";
    exit();
}
?>
