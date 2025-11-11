<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$rover_id = $_SESSION['rover_id'];
$expense_id = $_GET['id'];
$error_message = "";

require_once 'connect.php';

$currency_code = isset($_SESSION['currency_code']) ? $_SESSION['currency_code'] : 'USD';
$currency_symbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'GBP' => '£', 'CNY' => '¥'];
$symbol = isset($currency_symbols[$currency_code]) ? $currency_symbols[$currency_code] : '$';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_name = $_POST['expense_name'];
    $category_budget_id = $_POST['category_budget_id'];
    $expense_date = $_POST['expense_date'];
    $new_expense_amount = (float)$_POST['expense_amount'];
    $payment_method_id = $_POST['payment_method_id'];

    $old_expense_amount = (float)$_POST['old_expense_amount'];
    $trip_id = $_POST['trip_id'];

    $sql_budget = "SELECT allocated_budget, 
                          COALESCE(SUM(e.expense_amount), 0) AS total_spent
                   FROM category_budget cb
                   LEFT JOIN expense e ON cb.category_budget_id = e.category_budget_id
                   WHERE cb.category_budget_id = ?
                   GROUP BY cb.category_budget_id";

    $stmt_budget = $conn->prepare($sql_budget);
    $stmt_budget->bind_param("i", $category_budget_id);
    $stmt_budget->execute();
    $budget_data = $stmt_budget->get_result()->fetch_assoc();
    $stmt_budget->close();

    $allocation = (float) $budget_data['allocated_budget'];
    $total_spent = (float) $budget_data['total_spent'];

    $new_total_spent = ($total_spent - $old_expense_amount) + $new_expense_amount;

    if ($new_total_spent > $allocation) {
        $remaining_budget = $allocation - ($total_spent - $old_expense_amount);
        $error_message = "Expense exceeds budget. You only have " . $symbol . number_format($remaining_budget, 2) . " left in this category.";
    } else {
        $sql_update = "UPDATE expense SET
                           category_budget_id = ?,
                           payment_method_id = ?,
                           expense_name = ?,
                           expense_amount = ?,
                           expense_date = ?
                       WHERE expense_id = ?";

        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iisdsi", $category_budget_id, $payment_method_id, $expense_name, $new_expense_amount, $expense_date, $expense_id);

        if ($stmt_update->execute()) {
            header("Location: view_trip.php?trip_id=" . $trip_id);
            exit();
        } else {
            $error_message = "Error logging expense: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

$sql_exp = "SELECT 
                e.expense_name, e.expense_amount, e.expense_date, 
                e.payment_method_id, e.category_budget_id,
                cb.trip_id
            FROM expense e
            JOIN category_budget cb ON e.category_budget_id = cb.category_budget_id
            JOIN trip t ON cb.trip_id = t.trip_id
            WHERE e.expense_id = ? AND t.rover_id = ?";
$stmt_exp = $conn->prepare($sql_exp);
$stmt_exp->bind_param("ii", $expense_id, $rover_id);
$stmt_exp->execute();
$expense = $stmt_exp->get_result()->fetch_assoc();
$stmt_exp->close();

if (!$expense) {
    echo "Expense not found or you do not have permission.";
    $conn->close();
    exit();
}

$trip_id = $expense['trip_id'];

$sql_cat = "SELECT cb.category_budget_id, c.category_name
            FROM category_budget cb
            JOIN category c ON cb.category_id = c.category_id
            WHERE cb.trip_id = ?";
$stmt_cat = $conn->prepare($sql_cat);
$stmt_cat->bind_param("i", $trip_id);
$stmt_cat->execute();
$categories = $stmt_cat->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cat->close();

$sql_pm = "SELECT payment_method_id, payment_method_name 
           FROM payment_method 
           ORDER BY payment_method_name";
$payment_methods = $conn->query($sql_pm)->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expense</title>
    <link rel="stylesheet" href="../CSS/createTrip.css">
    <style>
        select {
            width: 100%; padding: 0.8em; font-size: 1.1em;
            border-radius: 0.5em; border: 1px solid var(--line-clr);
            background-color: #2b2e40; color: var(--text-clr);
            margin-bottom: 1.2em; font-family: 'Poppins', sans-serif;
        }
        .input-wrapper { position: relative; }
        .input-wrapper::before {
            content: "<?php echo $symbol; ?>";
            position: absolute; left: 15px; top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-text-clr); font-size: 1.1em;
        }
        .input-wrapper input { padding-left: 35px; }
    </style>
    <script type="text/javascript" src="../JS/app.js" defer></script>
</head>
<body>
<nav id="sidebar">
    <ul>

        <li>
            <span class = "logo">Rover</span>
            <button onclick=toggleSidebar() id="toggle-btn">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M440-240 200-480l240-240 56 56-183 184 183 184-56 56Zm264 0L464-480l240-240 56 56-183 184 183 184-56 56Z"/></svg>
            </button>
        </li>
        <li>
            <a href="../index.html">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                <span>Home</span>
            </a>
        </li>
        <li class="active">
            <a href="dashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/></svg>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="createTrip.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M280-80v-100l120-84v-144L80-280v-120l320-224v-176q0-33 23.5-56.5T480-880q33 0 56.5 23.5T560-800v176l320 224v120L560-408v144l120 84v100l-200-60-200 60Z"/></svg>
                <span>Create Trip</span>
            </a>
        </li>
        <li>
            <a href="profile.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg>
                <span>Profile</span>
            </a>
        </li>

    </ul>
</nav>
<main>
    <section class="createTrip-section">
        <h1>Edit Expense</h1>
        <p>Update your logged expense.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="edit_expense.php?id=<?php echo htmlspecialchars($expense_id); ?>" method="POST">

            <input type="hidden" name="trip_id" value="<?php echo $trip_id; ?>">
            <input type="hidden" name="old_expense_amount" value="<?php echo $expense['expense_amount']; ?>">

            <label for="expense_name">Expense Name</label>
            <input type="text" id="expense_name" name="expense_name"
                   value="<?php echo htmlspecialchars($expense['expense_name']); ?>" required>

            <label for="category_budget_id">Category</label>
            <select id="category_budget_id" name="category_budget_id" required>
                <option value="">Select a category...</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_budget_id']; ?>"
                        <?php if ($cat['category_budget_id'] == $expense['category_budget_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="expense_date">Date</label>
            <input type="date" id="expense_date" name="expense_date"
                   value="<?php echo htmlspecialchars($expense['expense_date']); ?>" required>

            <label for="expense_amount">Amount</label>
            <div class="input-wrapper">
                <input type="number" min="0" step=".01" id="expense_amount" name="expense_amount"
                       value="<?php echo htmlspecialchars($expense['expense_amount']); ?>" required>
            </div>

            <label for="payment_method_id">Payment Method</label>
            <select id="payment_method_id" name="payment_method_id" required>
                <option value="">Select a payment method...</option>
                <?php foreach ($payment_methods as $pm): ?>
                    <option value="<?php echo $pm['payment_method_id']; ?>"
                        <?php if ($pm['payment_method_id'] == $expense['payment_method_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($pm['payment_method_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="submit-btn">Save Changes</button>
            <a href="view_trip.php?trip_id=<?php echo htmlspecialchars($trip_id); ?>" class="cancel-link">Cancel</a>
        </form>
    </section>
</main>
</body>
</html>