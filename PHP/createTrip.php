<?php
session_start();

// if the user is not logged in, redirect
if (!isset($_SESSION['traveller_id'])) {
    echo "<script type='text/javascript'>alert('You must be logged in to create a trip!');</script>";
    header("Location: ../login.html");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "12345678";
$dbname = "travelbudgetingapp";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $traveller_id = $_SESSION['traveller_id']; // 🔹 THIS IS THE LOGGED-IN TRAVELER’S ID
    $trip_name = $_POST['trip_name'];
    $region = $_POST['region'];
    $country = $_POST['country'];
    $city = $_POST['city'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_budget = $_POST['total_budget'];

    $sql = "INSERT INTO trip_table 
            (traveller_id, trip_name, region, country, city, start_date, end_date, total_budget) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssd", $traveller_id, $trip_name, $region, $country, $city, $start_date, $end_date, $total_budget);

    if ($stmt->execute()) {
        header("Location: dashboard.php");;
    } else {
        echo "<script type='text/javascript'>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

$conn->close();
?>
