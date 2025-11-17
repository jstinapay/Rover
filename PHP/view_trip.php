<?php
ini_set('DISPLAY_ERRORS', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}

if (!isset($_GET['trip_id'])) {
    header('Location: dashboard.php');
    exit();
}

$trip_id = $_GET['trip_id'];
$rover_id = $_SESSION['rover_id'];


require_once 'connect.php';

$currency_code = isset($_SESSION['currency_code']) ? $_SESSION['currency_code'] : 'USD';
$currency_symbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'GBP' => '£', 'CNY' => '¥'];
$symbol = isset($currency_symbols[$currency_code]) ? $currency_symbols[$currency_code] : '$';


$sql_trip = "SELECT 
                t.trip_id, t.trip_name, t.trip_budget, t.status, t.start_date, t.end_date,
                ci.city_name, co.country_name
             FROM trip t
             JOIN city ci ON t.city_id = ci.city_id
             JOIN country co ON ci.country_id = co.country_id
             WHERE t.trip_id = ? AND t.rover_id = ?";
$stmt_trip = $conn->prepare($sql_trip);
$stmt_trip->bind_param("ii", $trip_id, $rover_id);
$stmt_trip->execute();
$result_trip = $stmt_trip->get_result();

if ($result_trip->num_rows == 0) {
    echo "Trip not found or you do not have permission.";
    exit();
}
$trip = $result_trip->fetch_assoc();
$stmt_trip->close();

$sql_budgets = "SELECT
                    cb.category_budget_id,
                    cb.allocated_budget,
                    c.category_name,
                    c.category_id,
                    COALESCE(SUM(e.expense_amount), 0) AS total_spent
                FROM category_budget cb
                JOIN category c ON cb.category_id = c.category_id
                LEFT JOIN expense e ON cb.category_budget_id = e.category_budget_id
                WHERE cb.trip_id = ?
                GROUP BY cb.category_budget_id, c.category_name, cb.allocated_budget, c.category_id
                ORDER BY c.category_name";

$stmt_budgets = $conn->prepare($sql_budgets);
$stmt_budgets->bind_param("i", $trip_id);
$stmt_budgets->execute();
$budgets = $stmt_budgets->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_budgets->close();

$total_allocated = 0;
$total_spent_overall = 0;
foreach ($budgets as $budget) {
    $total_allocated += $budget['allocated_budget'];
    $total_spent_overall += $budget['total_spent'];
}
$total_budget = $trip['trip_budget'];
$remaining_overall = $total_budget - $total_spent_overall;
$unallocated = $total_budget - $total_allocated;

$sql_expenses = "SELECT 
                    e.expense_id, e.expense_name, e.expense_amount, e.expense_date,
                    c.category_name,
                    pm.payment_method_name
                 FROM expense e
                 JOIN category_budget cb ON e.category_budget_id = cb.category_budget_id
                 JOIN category c ON cb.category_id = c.category_id
                 JOIN rover_payment_method rpm ON e.rover_payment_method_id = rpm.rover_payment_method_id
                 JOIN payment_method pm ON rpm.payment_method_id = pm.payment_method_id
                 WHERE cb.trip_id = ?
                 ORDER BY e.expense_date DESC";
$stmt_expenses = $conn->prepare($sql_expenses);
$stmt_expenses->bind_param("i", $trip_id);
$stmt_expenses->execute();
$expenses = $stmt_expenses->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_expenses->close();

$total_spent = $total_spent_overall;
$remaining = $total_budget - $total_spent;


$bar_percentage = 0;
if ($total_budget > 0) {
    $bar_percentage = ($total_spent / $total_budget) * 100;
}


$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../CSS/view_trip.css">
    <script type="text/javascript" src="../JS/app.js" defer></script>
</head>

<body>
    <nav id="sidebar">
        <ul>

            <li>
                <span class="logo">Rover</span>
                <button onclick=toggleSidebar() id="toggle-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                        <path d="M440-240 200-480l240-240 56 56-183 184 183 184-56 56Zm264 0L464-480l240-240 56 56-183 184 183 184-56 56Z" />
                    </svg>
                </button>
            </li>
            <li>
                <a href="../index.html">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                        <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z" />
                    </svg>
                    <span>Home</span>
                </a>
            </li>
            <li class="active">
                <a href="dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                        <path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z" />
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="createTrip.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                        <path d="M280-80v-100l120-84v-144L80-280v-120l320-224v-176q0-33 23.5-56.5T480-880q33 0 56.5 23.5T560-800v176l320 224v120L560-408v144l120 84v100l-200-60-200 60Z" />
                    </svg>
                    <span>Create Trip</span>
                </a>
            </li>
            </li>
            <li>
                <a href="profile.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                        <path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z" />
                    </svg>
                    <span>Profile</span>
                </a>
            </li>

        </ul>
    </nav>
    <main>
        <section class='dashboard'>
            <h1><?php echo htmlspecialchars($trip['trip_name']); ?></h1>

            <a href="edit_trip.php?trip_id=<?php echo $trip_id; ?>" class="edit-btn-icon" title="Edit Trip">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                    <path d="M560-80v-123l221-220q9-9 20-13t22-4q12 0 23 4.5t20 13.5l37 37q8 9 12.5 20t4.5 22q0 11-4 22.5T903-300L683-80H560Zm300-263-37-37 37 37ZM620-140h38l121-122-18-19-19-18-122 121v38ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v120h-80v-80H520v-200H240v640h240v80H240Zm280-400Zm241 199-19-18 37 37-18-19Z" />
                </svg>
            </a>
            <a href="finish_trip.php?trip_id=<?php echo $trip_id ?>" class="finish-btn-icon" title="Finish Trip" onclick="return confirm('Are you sure you want to finish this trip?');">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                    <path d="M500-560ZM200-120v-680h250q-5 20-8 40t-2 40H280v240h290l16 80h134v-46q20 0 40-3t40-9v138H520l-16-80H280v280h-80Zm491-516 139-138-42-42-97 95-39-39-42 43 81 81Zm29-290q83 0 141.5 58.5T920-726q0 83-58.5 141.5T720-526q-83 0-141.5-58.5T520-726q0-83 58.5-141.5T720-926Z" />
                </svg>
            </a>
            <h2>
                Allocated Budget: <?php echo $symbol; ?><?php echo number_format($total_allocated, 2); ?> /
                Total: <?php echo $symbol; ?><?php echo number_format($trip['trip_budget'], 2); ?>
            </h2>
        </section>
        <p class="trip-duration"><?php echo htmlspecialchars($trip['start_date']) . ' to ' . htmlspecialchars($trip['end_date']) ?></p>

        <div class="chart_container">
            <div class="chart">
                <canvas id="myChart"></canvas>
            </div>

        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            <?php
            // Data comes from the new $budgets array
            $category_names = array_column($budgets, 'category_name');
            $category_amounts = array_column($budgets, 'allocated_budget');
            ?>

            const ctx = document.getElementById('myChart');
            const labels = <?php echo json_encode($category_names); ?>;
            const data = <?php echo json_encode($category_amounts); ?>;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Allocated Budget',
                        data: data,
                        borderWidth: 1,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                            '#EA5545', '#F46A9B', '#EF9B20', '#50E991', '#bdcf32', '#DC0AB4', '#6aa84f'
                        ]
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>

        <div class="category-container">
            <?php foreach ($budgets as $budget): ?>
                <div class="category-card">
                    <h4><?php echo htmlspecialchars($budget['category_name']); ?></h4>
                    <p>Budget: <?php echo $symbol; ?><?php echo number_format($budget['allocated_budget'], 2); ?></p>

                    <div class="category_actions">
                        <a href="view_category_budget.php?category_budget_id=<?php echo $budget['category_budget_id']; ?>&trip_id=<?php echo $trip['trip_id']; ?>" class="view-button">View</a>
                        <a href="edit_category_budget.php?category_budget_id=<?php echo $budget['category_budget_id']; ?>&trip_id=<?php echo $trip['trip_id'] ?>" class="edit-button">Edit</a>
                        <a href="delete_category_budget.php?category_budget_id=<?php echo $budget['category_budget_id']; ?>&trip_id=<?php echo $trip['trip_id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this budget? This action cannot be undone.');">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <a href="add_category_budget.php?trip_id=<?php echo $trip_id; ?>" class="category-card-add">
                <span>+ Add Budget</span>
            </a>
        </div>



        <div class="expenses-container">
            <div class="expenses-header">
                <h2>Expenses</h2>
                <div class="budget-tracker">
                    <div class="budget-header">
                        <span>Spent</span>
                        <span>Remaining</span>
                    </div>

                    <div class="progress-bar-container">
                        <div class="progress-bar-spent"
                             style="width: <?php echo $bar_percentage; ?>%;">
                        </div>
                    </div>

                    <div class="budget-footer">
            <span class="spent-total">
                <?php echo $symbol . number_format($total_spent, 2); ?>
            </span>
                        <span class="remaining-total">
                <?php echo $symbol . number_format($remaining, 2) . " left";?>
            </span>
                    </div>
                </div>
                <div class="add-expense-button">
                    <a href="add_expense.php?trip_id=<?php echo $trip_id; ?>">+ Add Expense</a>
                </div>
            </div>

            <div class="expenses-table-wrapper">
                <table class="expenses-table">
                    <thead>
                    <tr>
                        <th>Category</th>
                        <th>Expense Name</th>
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
                                    <a href="delete_expense.php?expense_id=<?php echo $expense['expense_id']; ?>&trip_id=<?php echo $trip['trip_id']; ?>" class="delete-expense" onclick="return confirm('Are you sure you want to delete this expense?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="no-data">
                            <td colspan="6">No expenses logged yet.</td> </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>