<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
$rover_id = $_SESSION['rover_id'];
$message = "";

// Handle POST request to add method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the user selected "Custom"
    if (isset($_POST['payment_option']) && $_POST['payment_option'] === 'Custom') {
        // Use the custom text input
        $payment_name = $_POST['custom_payment_name'];
    } else {
        // Use the dropdown selection
        $payment_name = $_POST['payment_option'];
    }

    // Validate that the name isn't empty
    if (empty($payment_name)) {
        $message = "Payment method name cannot be empty.";
    } else {
        // Proceed to insert into database
        $host = "yamanote.proxy.rlwy.net";
        $user = "root";
        $pass = "ussforDJGtKQAqXiQTHUnStcDIwpdTja";
        $dbname = "railway";
        $port = "40768";
        $conn = new mysqli($host, $user, $pass, $dbname, $port);
        if ($conn->connect_error) {
            die("Connection Failed: " . $conn->connect_error);
        }

        $sql_add = "INSERT INTO payment_method (rover_id, payment_method_name) VALUES (?, ?)";
        $stmt_add = $conn->prepare($sql_add);
        $stmt_add->bind_param("is", $rover_id, $payment_name);
        
        if ($stmt_add->execute()) {
            // Success! Go back to profile.
            header("Location: profile.php");
            exit();
        } else {
            $message = "Error adding method: " . $stmt_add->error;
        }
        $stmt_add->close();
        $conn->close();
    }
}
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
                
                <label for="payment_option">Payment Method</label>
                <select id="payment_option" name="payment_option" onchange="toggleCustomInput()">
                    <option value="">Select a type...</option>
                    <option value="Cash">Cash</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Gcash">Gcash</option>
                    <option value="Custom">Custom...</option>
                </select>
                
                <div id="custom-name-wrapper">
                    <label for="custom_payment_name">Custom Name</label>
                    <input type="text" id="custom_payment_name" name="custom_payment_name" 
                           placeholder="e.g., My BPI Visa Card">
                </div>
                       
                <button type="submit" class="submit-btn" style="margin-top: 15px;">Add Method</button>
                <a href="profile.php" class="cancel-link">Cancel</a>
            </form>
        </section>
    </main>

    <script>
        function toggleCustomInput() {
            var select = document.getElementById('payment_option');
            var customInput = document.getElementById('custom-name-wrapper');
            if (select.value === 'Custom') {
                customInput.style.display = 'block';
            } else {
                customInput.style.display = 'none';
            }
        }
    </script>
</body>
</html>
