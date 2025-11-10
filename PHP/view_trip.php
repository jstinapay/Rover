<?php
session_start();

if(!isset($_SESSION['rover_id'])){
    header("Location: ../login.html");
    exit();
}

if(!isset($_GET['trip_id'])){
    echo "<script>alert('No trip selected.')</script>";
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit();
}

$trip_id = $_GET['trip_id'];
$rover_id = $_SESSION['rover_id'];


require_once 'connect.php';

$currency_code = isset($_SESSION['currency_code']) ? $_SESSION['currency_code'] : 'USD';

$currency_symbols = [
    'PHP' => '₱',
    'USD' => '$',
    'EUR' => '€',
    'JPY' => '¥',
    'GBP' => '£',
    'CNY' => '¥',
];

$symbol = isset($currency_symbols[$currency_code]) ? $currency_symbols[$currency_code] : '$';

$sql = "SELECT trip_id, trip_name, total_budget
        FROM trip
        WHERE trip_id = ?
        AND rover_id = ?;";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $trip_id, $rover_id);
$stmt->execute();
$result_trip = $stmt->get_result();

if ($result_trip->num_rows == 0) {
    echo "<script>alert('Trip not found or you do not have permission.');</script>";
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit();
}
$trip = $result_trip->fetch_assoc();

$sql_categories = "SELECT *
                   FROM category
                   WHERE trip_id = ?;";
$stmt_categories = $conn->prepare($sql_categories);
$stmt_categories->bind_param("i", $trip_id);
$stmt_categories->execute();
$result_categories = $stmt_categories->get_result();
$categories =$result_categories->fetch_all(MYSQLI_ASSOC);
$stmt_categories->close();

$sql_sum = "SELECT SUM(allocation_amount) AS total_allocated
            FROM category
            WHERE trip_id = ?;";
$stmt_sum = $conn->prepare($sql_sum);
$stmt_sum->bind_param("i", $trip_id);
$stmt_sum->execute();
$result_sum = $stmt_sum->get_result()->fetch_assoc();
$total_allocated = isset($result_sum['total_allocated']) ? $result_sum['total_allocated'] : 0.00;
$stmt_sum->close();

$sql_expenses = "SELECT * FROM expense WHERE trip_id = ? ORDER BY expense_date DESC";
$stmt_expenses = $conn->prepare($sql_expenses);
$stmt_expenses->bind_param("i", $trip_id);
$stmt_expenses->execute();
$result_expenses = $stmt_expenses->get_result();
$expenses = $result_expenses->fetch_all(MYSQLI_ASSOC);
$stmt_expenses->close();

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
            <button onclick="toggleSubMenu(this)" class="dropdown-btn">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M240-160q-66 0-113-47T80-320v-320q0-66 47-113t113-47h480q66 0 113 47t47 113v320q0 66-47 113t-113 47H240Zm0-480h480q22 0 42 5t38 16v-21q0-33-23.5-56.5T720-720H240q-33 0-56.5 23.5T160-640v21q18-11 38-16t42-5Zm-74 130 445 108q9 2 18 0t17-8l139-116q-11-15-28-24.5t-37-9.5H240q-26 0-45.5 13.5T166-510Z"/></svg>
                <span>Budget</span>
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/></svg>
            </button>
            <ul class="sub-menu">
                <div>
                    <li><a href="#">Allocate Budget</a></li>
                    <li><a href="#">Custom Category</a></li>
                </div>
            </ul>
        <li>
            <button onclick="toggleSubMenu(this)" class="dropdown-btn">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M441-120v-86q-53-12-91.5-46T293-348l74-30q15 48 44.5 73t77.5 25q41 0 69.5-18.5T587-356q0-35-22-55.5T463-458q-86-27-118-64.5T313-614q0-65 42-101t86-41v-84h80v84q50 8 82.5 36.5T651-650l-74 32q-12-32-34-48t-60-16q-44 0-67 19.5T393-614q0 33 30 52t104 40q69 20 104.5 63.5T667-358q0 71-42 108t-104 46v84h-80Z"/></svg>
                <span>Expenses</span>
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/></svg>
            </button>
            <ul class="sub-menu">
                <div>
                    <li><a href="#">Add Expense</a></li>
                    <li><a href="#">Edit Expense</a></li>
                    <li><a href="#">Payment Method</a></li>
                </div>
            </ul>
        </li>
        <li>
            <a href="../profile.html">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg>
                <span>Profile</span>
            </a>
        </li>

    </ul>
</nav>
<main>
    <section class='dashboard'>
        <h1><?php echo htmlspecialchars($trip['trip_name']); ?></h1>

    <h2>
        Allocated Budget: <?php echo $symbol; ?><?php echo number_format($total_allocated, 2); ?> /
        Total: <?php echo $symbol; ?><?php echo number_format($trip['total_budget'], 2); ?>
    </h2>
    </section>
    <?php if ($total_allocated > $trip['total_budget']): ?>
        <h3 style="color: #e8a8a8;">Warning: Your allocations exceed your total budget!</h3>
    <?php endif; ?>

    <div class="chart_container">
        <div class="chart">
            <canvas id="myChart"></canvas>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        <?php
        // Use PHP to extract the data from your $categories array
        // array_column() is a perfect tool for this
        $category_names = array_column($categories, 'category_name');
        $category_amounts = array_column($categories, 'allocation_amount');
        ?>

        // Get the canvas element from your HTML
        const ctx = document.getElementById('myChart');

        // Pass the PHP arrays to JavaScript by encoding them as JSON
        const labels = <?php echo json_encode($category_names); ?>;
        const data = <?php echo json_encode($category_amounts); ?>;

        // Create the new chart
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Allocated Budget',
                    data: data,
                    borderWidth: 1,
                    //neutral colors
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#6A8EAE',
                        '#A6B9C1',
                        '#E8D5B5',
                        '#D9B4A9',
                        '#7E8D85',
                        '#C1CCD3'

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

        <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <h4><?php echo htmlspecialchars($category['category_name']); ?></h4>
                <p>Budget: <?php echo $symbol; ?><?php echo number_format($category['allocation_amount'], 2); ?></p>  

                <div class="category_actions">
                <a href="view_category.php?category_id=<?php echo $category['category_id']; ?>" class="view-button">View</a>
                <a href="edit_category.php?category_id=<?php echo $category['category_id']; ?>" class="edit-button">Edit</a>
                <a href="delete_category.php?category_id=<?php echo $category['category_id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.');">Delete</a>
                </div>
            </div>
            
        <?php endforeach; ?>

        <a href="add_category.php?trip_id=<?php echo $trip_id; ?>" class="category-card-add">
            <span>+ Add Category</span>
        </a>
    </div>

    <div class="expenses-container">
    
    <div class="expenses-container">
    <div class="expenses-header">
        <h2>Expenses</h2>
        <div class="add-expense-button">
            <a href="add_expense.php">+ Add Expense</a>
        </div>
    </div>
    
    <div class="expenses-table-wrapper">
        <table class="expenses-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Expense Name</th>
                    <th>Price (<?php echo $symbol; ?>)</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($expenses)): ?>
                    <?php foreach ($expenses as $expense): ?>
                        <?php
                        $cat_name = '';
                        foreach ($categories as $cat) {
                            if ($cat['category_id'] == $expense['category_id']) {
                                $cat_name = $cat['category_name'];
                                break;
                            }
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cat_name); ?></td>
                            <td><?php echo htmlspecialchars($expense['expense_name']); ?></td>
                            <td><?php echo number_format($expense['expense_amount'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                            <td>
                                <a href="edit_expense.php?expense_id=<?php echo $expense['expense_id']; ?>" class="edit-expense">Edit</a>
                                <a href="delete_expense.php?expense_id=<?php echo $expense['expense_id']; ?>" class="delete-expense" onclick="return confirm('Are you sure you want to delete this expense?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No expenses logged yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
</body>
</html>
