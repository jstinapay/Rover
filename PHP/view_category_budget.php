<?php
session_start();
if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
if (!isset($_GET['category_budget_id'])) {
    header("Location: dashboard.php");
    exit();
}

$rover_id = $_SESSION['rover_id'];
$category_budget_id = $_GET['category_budget_id'];

require_once 'connect.php';


$currency_code = $_SESSION['currency_code'] ?? 'USD';
$currency_symbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'GBP' => '£', 'CNY' => 'CN¥'];
$symbol = $currency_symbols[$currency_code] ?? '$';


$sql_budget = "SELECT 
                    cb.allocated_budget, 
                    cb.trip_id,
                    c.category_name
                FROM 
                    category_budget cb
                JOIN 
                    category c ON cb.category_id = c.category_id
                JOIN
                    trip t ON cb.trip_id = t.trip_id
                WHERE 
                    cb.category_budget_id = ? AND t.rover_id = ?";
                    
$stmt_budget = $conn->prepare($sql_budget);
$stmt_budget->bind_param("ii", $category_budget_id, $rover_id);
$stmt_budget->execute();
$result_budget = $stmt_budget->get_result();

if ($result_budget->num_rows == 0) {
    echo "Error: Category not found or you do not have permission.";
    $stmt_budget->close();
    $conn->close();
    exit();
}
$category = $result_budget->fetch_assoc();
$trip_id = $category['trip_id'];
$stmt_budget->close();


$sql_trip = "SELECT trip_name, status FROM trip WHERE trip_id = ? AND rover_id = ?";
$stmt_trip = $conn->prepare($sql_trip);
$stmt_trip->bind_param("ii", $trip_id, $rover_id);
$stmt_trip->execute();
$trip = $stmt_trip->get_result()->fetch_assoc();
$stmt_trip->close();

$sql_expenses = "SELECT 
                    e.expense_id, e.expense_name, e.expense_amount, e.expense_date,
                    c.category_name,
                    pm.payment_method_name
                 FROM expense e
                 JOIN category_budget cb ON e.category_budget_id = cb.category_budget_id
                 JOIN category c ON cb.category_id = c.category_id
                 JOIN rover_payment_method rpm ON e.rover_payment_method_id = rpm.rover_payment_method_id
                 JOIN payment_method pm ON rpm.payment_method_id = pm.payment_method_id
                 WHERE cb.category_budget_id = ?
                 ORDER BY e.expense_date DESC";
$stmt_expenses = $conn->prepare($sql_expenses);
$stmt_expenses->bind_param("i", $category_budget_id);
$stmt_expenses->execute();
$expenses = $stmt_expenses->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_expenses->close();


$total_spent = array_sum(array_column($expenses, 'expense_amount'));
$allocated = (float) $category['allocated_budget'];
$remaining = $allocated - $total_spent;


$percentage = ($allocated > 0) ? ($total_spent / $allocated) * 100 : 0;

$bar_percentage = $percentage;
if ($bar_percentage > 100) {
    $bar_percentage = 100;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['category_name']); ?> - Budget</title>
    <link rel="stylesheet" href="../CSS/view_trip.css">
    
    
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
    <a href="view_trip.php?trip_id=<?php echo $trip_id; ?>" class="back-link">&larr; Back to <?php echo htmlspecialchars($trip['trip_name']); ?></a>
    <div class="budget-h1">
        <h1><?php echo htmlspecialchars($category['category_name']); ?></h1>
        <h3>Allocated Budget: <?php echo $symbol . htmlspecialchars($category['allocated_budget']) ?></h3>
    </div>
    

    <div class="budget-tracker">
        <div class="budget-header">
            <span>Spent</span>
            <span>Remaining</span>
        </div>
        
        <div class="progress-bar-container">
            <div class="progress-bar-spent <?php echo $is_over_budget ? 'danger' : ''; ?>" 
                 style="width: <?php echo $bar_percentage; ?>%;">
            </div>
        </div>
        
        <div class="budget-footer">
            <span class="spent-total">
                <?php echo $symbol . number_format($total_spent, 2); ?>
            </span>
            
            <span class="<?php echo $is_over_budget ? 'danger-text' : ''; ?>">
                <?php echo $symbol . number_format($remaining, 2) . " left";?>
            </span>
        </div>
    </div>

    <div class="expenses-container">
        <div class="expenses-header">
            <h2><?php echo htmlspecialchars($category['category_name']); ?> Expenses</h2>
        </div>

        <div class="expenses-table-wrapper">
            <table class="expenses-table">
                <thead>
                <tr>
                    <th>Category</th>
                    <th>Expense</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Date</th>
                     <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($expenses)): ?>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($expense['expense_name']); ?></td>
                            <td><?php echo htmlspecialchars($expense['payment_method_name']); ?></td>
                            <td><?php echo $symbol .  number_format($expense['expense_amount'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                             <td>
                                <a href="edit_expense.php?id=<?php echo $expense['expense_id']; ?>" class="edit-expense">Edit</a>
                                <a href="delete_expense.php?expense_id=<?php echo $expense['expense_id']; ?>&trip_id=<?php echo $trip_id; ?>" class="delete-expense" onclick="return confirm('Are you sure you want to delete this expense?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No expenses logged yet.</td> </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
