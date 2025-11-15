<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
if (!isset($_GET['trip_id'])) {
    echo "No trip selected.";
    header("Location: dashboard.php");
    exit();
}

$rover_id = $_SESSION['rover_id'];
$trip_id = $_GET['trip_id'];
$error_message = "";
$trip_is_completed = false;

require_once 'connect.php';

$sql_trip_status = "SELECT status FROM trip WHERE trip_id = ? AND rover_id = ?";
$stmt_status = $conn->prepare($sql_trip_status);
$stmt_status->bind_param("ii", $trip_id, $rover_id);
$stmt_status->execute();
$result_status = $stmt_status->get_result()->fetch_assoc();
$stmt_status->close();

if (!$result_status) {
    $error_message = "Trip not found or you do not have permission.";
    $trip_is_completed = true;
} elseif ($result_status['status'] === 'completed') {
    $error_message = "This trip has been completed. No further expenses can be added.";
    $trip_is_completed = true;
}

$currency_code = isset($_SESSION['currency_code']) ? $_SESSION['currency_code'] : 'USD';
$currency_symbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'GBP' => '£', 'CNY' => 'CN¥'];
$symbol = isset($currency_symbols[$currency_code]) ? $currency_symbols[$currency_code] : '$';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$trip_is_completed) {
    $expense_name = $_POST['expense_name'];
    $category_budget_id = $_POST['category_budget_id'];
    $expense_date = $_POST['expense_date'];
    $expense_amount = (float) $_POST['expense_amount'];
    $payment_method_id = $_POST['payment_method_id'];

    $sql_check = "SELECT t.rover_id FROM category_budget cb
                  JOIN trip t ON cb.trip_id = t.trip_id
                  WHERE cb.category_budget_id = ? AND t.rover_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $category_budget_id, $rover_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        $error_message = "Security check failed. You do not own this category.";
    } else {
        $sql_budget = "SELECT 
                         cb.allocated_budget,
                         COALESCE(SUM(e.expense_amount), 0) AS total_spent
                       FROM category_budget cb
                       LEFT JOIN expense e ON cb.category_budget_id = e.category_budget_id
                       WHERE cb.category_budget_id = ?
                       GROUP BY cb.category_budget_id, cb.allocated_budget";

        $stmt_budget = $conn->prepare($sql_budget);
        $stmt_budget->bind_param("i", $category_budget_id);
        $stmt_budget->execute();
        $budget_data = $stmt_budget->get_result()->fetch_assoc();
        $stmt_budget->close();

        $allocation = (float) $budget_data['allocated_budget'];
        $total_spent = (float) $budget_data['total_spent'];

        if (($total_spent + $expense_amount) > $allocation) {
            $remaining_budget = $allocation - $total_spent;
            $error_message = "Expense exceeds budget. You only have " . $symbol . number_format($remaining_budget, 2) . " left in this category.";
        } else {
            $sql_insert = "INSERT INTO expense (category_budget_id, rover_payment_method_id, expense_name, expense_amount, expense_date)
                           VALUES (?, ?, ?, ?, ?)";

            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iisds",$category_budget_id, $payment_method_id, $expense_name, $expense_amount, $expense_date);

            if ($stmt_insert->execute()) {
                header("Location: view_trip.php?trip_id=" . $trip_id);
                exit();
            } else {
                $error_message = "Error logging expense: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
    }
    $stmt_check->close();
}

$sql_cat = "SELECT cb.category_budget_id, c.category_name 
            FROM category_budget cb
            JOIN category c ON cb.category_id = c.category_id
            WHERE cb.trip_id = ?";
$stmt_cat = $conn->prepare($sql_cat);
$stmt_cat->bind_param("i", $trip_id);
$stmt_cat->execute();
$categories = $stmt_cat->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cat->close();

$sql_pm = "SELECT rpm.rover_payment_method_id, pm.payment_method_name 
           FROM rover_payment_method rpm
           JOIN payment_method pm ON rpm.payment_method_id = pm.payment_method_id
           WHERE rpm.rover_id = ?";
$stmt_pm = $conn->prepare($sql_pm);
$stmt_pm->bind_param("i", $rover_id);
$stmt_pm->execute();
$payment_methods = $stmt_pm->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_pm->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense</title>
    <link rel="stylesheet" href="../CSS/createTrip.css">
    <style>
        :root {
            --currency-symbol: "<?php echo $symbol; ?>";
        }
    </style>
    <script type="text/javascript" src="../JS/app.js" defer></script>
</head>
<body>
<nav id="sidebar">

</nav>
<main>
    <section class="createTrip-section">
        <h1>Add Expense</h1>
        <p>Log an expense for this trip.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php if ($result_status && $result_status['status'] === 'completed') echo '<a href="view_trip.php?trip_id=' . $trip_id . '" class="cancel-link">Back to Trip</a>'; ?>
        <?php endif; ?>

        <?php if (empty($error_message) || $result_status['status'] !== 'completed'): ?>
        <form action="add_expense.php?trip_id=<?php echo htmlspecialchars($trip_id); ?>" method="POST">
            <label for="expense_name">Expense Name</label>
            <input type="text" id="expense_name" name="expense_name" placeholder="e.g., Coffee, Train ticket" required>

            <label for="category_budget_id">Category</label>
            <select id="category_budget_id" name="category_budget_id" required>
                <option value="">Select a category...</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_budget_id']; ?>">
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="expense_date">Date</label>
            <input type="date" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>

            <label for="expense_amount">Amount</label>
            <div class="input-wrapper">
                <input type="number" min="0" step=".01" id="expense_amount" name="expense_amount" placeholder="0.00" required>
            </div>

            <label for="payment_method_id">Payment Method</label>
            <select id="payment_method_id" name="payment_method_id" required>
                <option value="">Select a payment method...</option>
                <?php foreach ($payment_methods as $pm): ?>
                    <option value="<?php echo $pm['rover_payment_method_id']; ?>">
                        <?php echo htmlspecialchars($pm['payment_method_name']); ?>
                    </option>
                <?php endforeach; ?>
                <?php if (empty($payment_methods)): ?>
                    <option value="" disabled>No payment methods found. Add one in your Profile.</option>
                <?php endif; ?>
            </select>

            <button type="submit" class="submit-btn">Log Expense</button>
            <a href="view_trip.php?trip_id=<?php echo htmlspecialchars($trip_id); ?>" class="cancel-link">Cancel</a>
        </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
