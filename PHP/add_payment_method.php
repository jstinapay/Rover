<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
$rover_id = $_SESSION['rover_id'];
$message = "";

$host = "yamanote.proxy.rlwy.net";
$user = "root";
$pass = "ussforDJGtKQAqXiQTHUnStcDIwpdTja";
$dbname = "railway";
$port = "40768";

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method_id = $_POST['payment_method_id'];

    $sql = "INSERT INTO rover_payment_method (payment_method_id, rover_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $payment_method_id, $rover_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: profile.php");
        exit();
    } else {
        $message = "Error: Could not add this payment method.";
    }
    $stmt->close();
}

$sql_get = "SELECT 
                pm.payment_method_id, 
                pm.payment_method_name 
            FROM 
                payment_method pm
            WHERE 
                pm.payment_method_id NOT IN (
                    SELECT rpm.payment_method_id
                    FROM rover_payment_method rpm
                    WHERE rpm.rover_id = ?
                )
            ORDER BY
                pm.payment_method_name";

$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("i", $rover_id);
$stmt_get->execute();
$result = $stmt_get->get_result();
$payment_methods = $result->fetch_all(MYSQLI_ASSOC);
$stmt_get->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Payment Method</title>
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <link rel="stylesheet" href="../CSS/createTrip.css"> <style>
        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 1em;
            color: var(--secondary-text-clr);
        }
        /* This will hide the custom input by default */
        #custom-name-wrapper {
            display: none;
            margin-top: 15px;
        }
    </style>
</head>
<body>
<nav id="sidebar">
</nav>
<main>
    <section class="createTrip-section">
        <h2>Add Payment Method</h2>
        <p>Add a new payment method to track expenses.</p>

        <?php if (!empty($message)): ?>
            <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="add_payment_method.php" method="POST">

            <label for="payment_method_id">Payment Method</label>
            <select id="payment_method_id" name="payment_method_id">
                <option value="" selected disabled hidden>Select a payment method</option>
                <?php foreach ($payment_methods as $payment_method): ?>
                    <option value="<?php echo $payment_method['payment_method_id']; ?>"><?php echo $payment_method['payment_method_name']; ?></option>
                <?php endforeach; ?>

            </select>
            <button type="submit" class="submit-btn" style="margin-top: 15px;">Add Method</button>
            <a href="profile.php" class="cancel-link">Cancel</a>
        </form>
    </section>
</main>
</body>
</html>

