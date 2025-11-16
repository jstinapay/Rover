<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
$rover_id = $_SESSION['rover_id'];

require_once 'connect.php';

$sql_payment_methods = "SELECT rpm.rover_payment_method_id, pm.payment_method_name 
                        FROM rover_payment_method rpm
                        JOIN payment_method pm ON rpm.payment_method_id = pm.payment_method_id
                        WHERE rover_id = ?";
$stmt_payment_methods = $conn->prepare($sql_payment_methods);
$stmt_payment_methods->bind_param("i", $rover_id);
$stmt_payment_methods->execute();
$payment_methods = $stmt_payment_methods->get_result()->fetch_all(MYSQLI_ASSOC);

$sql_user = "SELECT first_name, last_name, email, phone_number, currency_code 
             FROM rover 
             WHERE rover_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $rover_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$failed_delete_id = null;
if (isset($_GET['error']) && $_GET['error'] === 'in_use' && isset($_GET['failed_id'])) {
    $failed_delete_id = (int)$_GET['failed_id'];
}

$phone = $user['phone_number'] ? htmlspecialchars($user['phone_number']) : 'N/A';
$currency = $user['currency_code'] ? htmlspecialchars($user['currency_code']) : 'N/A';

$sql_top_categories = "
    SELECT c.category_name, SUM(cb.allocated_budget) AS total_budget
    FROM category_budget cb
    JOIN category c ON cb.category_id = c.category_id
    JOIN trip t ON cb.trip_id = t.trip_id
    WHERE t.rover_id = ?
    GROUP BY c.category_name
    ORDER BY total_budget DESC
    LIMIT 5;
";

$stmt_top_categories = $conn->prepare($sql_top_categories);
$stmt_top_categories->bind_param("i", $rover_id);
$stmt_top_categories->execute();
$top_categories = $stmt_top_categories->get_result()->fetch_all(MYSQLI_ASSOC);

$category_names = array_column($top_categories, 'category_name');
$category_amounts = array_column($top_categories, 'total_budget');

$sql_trips = "SELECT trip_id, trip_name FROM trip WHERE rover_id = ? ORDER BY start_date DESC";
$stmt_trips = $conn->prepare($sql_trips);
$stmt_trips->bind_param("i", $rover_id);
$stmt_trips->execute();
$trips = $stmt_trips->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_trips->close();

$sql_budget_utilization = "
    SELECT c.category_name, 
           SUM(cb.allocated_budget) AS total_budget, 
           COALESCE(SUM(e.expense_amount), 0) AS total_expense
    FROM category_budget cb
    JOIN category c ON cb.category_id = c.category_id
    LEFT JOIN expense e ON cb.category_budget_id = e.category_budget_id
    JOIN trip t ON cb.trip_id = t.trip_id
    WHERE t.rover_id = ?
    GROUP BY c.category_name
    ORDER BY total_budget DESC, total_expense DESC;
";

$stmt_budget_utilization = $conn->prepare($sql_budget_utilization);
$stmt_budget_utilization->bind_param("i", $rover_id);
$stmt_budget_utilization->execute();
$budget_utilization = $stmt_budget_utilization->get_result()->fetch_all(MYSQLI_ASSOC);

$budget_utilization_names = array_column($budget_utilization, 'category_name');
$budget_utilization_amounts = array_column($budget_utilization, 'total_budget');
$budget_utilization_expenses = array_column($budget_utilization, 'total_expense');

$sql_top_payment_methods = "
    SELECT pm.payment_method_name,
           COUNT(e.expense_id) AS usage_count
    FROM expense e
    JOIN rover_payment_method rpm 
        ON e.rover_payment_method_id = rpm.rover_payment_method_id
    JOIN payment_method pm 
        ON rpm.payment_method_id = pm.payment_method_id
    JOIN category_budget cb
        ON e.category_budget_id = cb.category_budget_id
    JOIN trip t
        ON cb.trip_id = t.trip_id
    WHERE t.rover_id = ?
    GROUP BY pm.payment_method_name
    ORDER BY usage_count DESC
    LIMIT 5;
";

$stmt_top_payment_methods = $conn->prepare($sql_top_payment_methods);
$stmt_top_payment_methods->bind_param("i", $rover_id);
$stmt_top_payment_methods->execute();
$top_payment_methods = $stmt_top_payment_methods->get_result()->fetch_all(MYSQLI_ASSOC);

$payment_method_names = array_column($top_payment_methods, 'payment_method_name');
$payment_method_usage = array_column($top_payment_methods, 'usage_count');

// DAILY expenses
$sql_daily_expenses = "
    SELECT DATE(e.expense_date) AS date, SUM(e.expense_amount) AS total
    FROM expense e
    JOIN category_budget cb ON e.category_budget_id = cb.category_budget_id
    JOIN trip t ON cb.trip_id = t.trip_id
    WHERE t.rover_id = ?
    GROUP BY DATE(e.expense_date)
    ORDER BY DATE(e.expense_date);
";

$stmt_daily = $conn->prepare($sql_daily_expenses);
$stmt_daily->bind_param("i", $rover_id);
$stmt_daily->execute();
$daily_expenses = $stmt_daily->get_result()->fetch_all(MYSQLI_ASSOC);

// MONTHLY expenses
$sql_monthly_expenses = "
    SELECT DATE_FORMAT(e.expense_date, '%Y-%m') AS month, SUM(e.expense_amount) AS total
    FROM expense e
    JOIN category_budget cb ON e.category_budget_id = cb.category_budget_id
    JOIN trip t ON cb.trip_id = t.trip_id
    WHERE t.rover_id = ?
    GROUP BY DATE_FORMAT(e.expense_date, '%Y-%m')
    ORDER BY month;
";

$stmt_monthly = $conn->prepare($sql_monthly_expenses);
$stmt_monthly->bind_param("i", $rover_id);
$stmt_monthly->execute();
$monthly_expenses = $stmt_monthly->get_result()->fetch_all(MYSQLI_ASSOC);

// Arrays for JS
$daily_labels = array_column($daily_expenses, 'date');
$daily_values = array_column($daily_expenses, 'total');

$monthly_labels = array_column($monthly_expenses, 'month');
$monthly_values = array_column($monthly_expenses, 'total');

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Rover</title>
    <link rel="stylesheet" href="../CSS/profile.css">
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
        <li>
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
        <li class="active">
            <a href="profile.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg>
                <span>Profile</span>
            </a>
        </li>

    </ul>
</nav>

<main>
    <h1>My Profile</h1>

    <div class="profile-card">
        <div class="profile-info">
            <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
            <div class="info-grid">
                <div class="info-item">
                    <span>Email</span>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="info-item">
                    <span>Phone</span>
                    <p><?php echo $phone; ?></p>
                </div>
                <div class="info-item">
                    <span>Currency</span>
                    <p><?php echo $currency; ?></p>
                </div>
            </div>
        </div>
        <div class="profile-actions">
            <a href="edit_profile.php" class="btn-edit">Edit Profile</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <?php
    $error_message = "";
    if (isset($_GET['error']) && $_GET['error'] === 'in_use') {
        $error_message = "Cannot delete: This payment method is already linked to one or more expenses.";
    }
    ?>


    <div class="payment-methods-panel">
        <div class="payment-header">
            <h2>Payment Methods</h2>
            <a href="add_payment_method.php" class="btn-add">+ Add New</a>
        </div>

        <ul class="payment-list">
            <?php if (empty($payment_methods)): ?>
                <li class="payment-item-empty">No payment methods added yet.</li>
            <?php else: ?>
                <?php foreach ($payment_methods as $method): ?>

                    <li class="payment-item">
                        <span><?php echo htmlspecialchars($method['payment_method_name']); ?></span>
                        <a href="delete_payment_method.php?id=<?php echo $method['rover_payment_method_id']; ?>"
                           class="btn-delete"
                           onclick="return confirm('Are you sure?');">
                            Delete
                        </a>
                    </li>

                    <?php if ($failed_delete_id === $method['rover_payment_method_id']): ?>
                        <li class="error-message-li">
                            Cannot delete: This method is in use by one or more expenses.
                        </li>
                    <?php endif; ?>

                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="top-categories-panel">
        <div class="top-categories-header">
            <h2>Highest Budget Categories [TOP 5]</h2>
        </div>

    <canvas id="categoriesChart"></canvas>
    </div>

    <div class="top-categories-panel">
        <div class="top-categories-header">
            <h2>Budget Utilization Report</h2>

            <select id="tripFilter" class="trip-filter-dropdown">
                <option value="all">All Trips</option>
                <?php foreach ($trips as $trip): ?>
                    <option value="<?php echo $trip['trip_id']; ?>">
                        <?php echo htmlspecialchars($trip['trip_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <canvas id="UtilizationChart"></canvas>
    </div>

    <div class="top-categories-panel">
        <div class="top-categories-header">
            <h2>Top Payment Methods Used</h2>
        </div>
        <div class="payment-method-chart-container">           
            <canvas id="paymentMethodsChart"></canvas>
        </div>
    </div>

    <div class="top-categories-panel">
        <div class="expense-linegraph-panel">

            <div class="top-categories-header">
                <h2>Overall Expenses Over Time</h2>
            </div>

            <select id="expenseViewMode" class="dropdown-mode">
                <option value="daily">Daily</option>
                <option value="monthly">Monthly</option>
            </select>

            <div class="expense-line-chart-container">
                <canvas id="expensesLineChart"></canvas>
            </div>

        </div>
    </div>

</main>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    const ctx = document.getElementById('categoriesChart');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($category_names); ?>,
            datasets: [{
                label: 'Amount Allocated',
                data: <?php echo json_encode($category_amounts); ?>,
                borderWidth: 2,
                backgroundColor: '#5e63ff'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#e6e6ef'
                    }
                },
                x: {
                    ticks: {
                        color: '#e6e6ef'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#e6e6ef'
                    }
                }
            }
        }
    });
    </script>

<script>
    const ctx2 = document.getElementById('UtilizationChart');
    let utilizationChart;

    const initialLabels = <?php echo json_encode($budget_utilization_names); ?>;
    const initialAllocated = <?php echo json_encode($budget_utilization_amounts); ?>;
    const initialSpent = <?php echo json_encode($budget_utilization_expenses); ?>;

    function createOrUpdateChart(labels, allocatedData, spentData) {
        if (utilizationChart) {
            utilizationChart.data.labels = labels;
            utilizationChart.data.datasets[0].data = allocatedData;
            utilizationChart.data.datasets[1].data = spentData;
            utilizationChart.update();
        } else {
            utilizationChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Amount Allocated',
                            data: allocatedData,
                            borderWidth: 2,
                            backgroundColor: '#5e63ff'
                        },
                        {
                            label: 'Amount Spent',
                            data: spentData,
                            borderWidth: 2,
                            backgroundColor: '#b0b3c1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#e6e6ef' }
                        },
                        x: {
                            ticks: { color: '#e6e6ef' }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: { color: '#e6e6ef' }
                        }
                    }
                }
            });
        }
    }

    async function fetchChartData(tripId) {
        let url = 'get_utilization_data.php?trip_id=' + tripId;

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();

            createOrUpdateChart(data.labels, data.allocated, data.spent);

        } catch (error) {
            console.error('Error fetching chart data:', error);
        }
    }

    createOrUpdateChart(initialLabels, initialAllocated, initialSpent);

    document.getElementById('tripFilter').addEventListener('change', function() {
        const selectedTripId = this.value;
        fetchChartData(selectedTripId);
    });
</script>

<script>
    const ctx3 = document.getElementById('paymentMethodsChart');

    new Chart(ctx3, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($payment_method_names); ?>,
            datasets: [{
                label: 'Usage Count',
                data: <?php echo json_encode($payment_method_usage); ?>,
                borderWidth: 1,
                backgroundColor: [
                    '#5e63ff',
                    '#b0b3c1',
                    '#4cc9f0',
                    '#f72585',
                    '#7209b7'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#e6e6ef' }
                }
            }
        }
    });
</script>

<script>
    const dailyLabels = <?php echo json_encode($daily_labels); ?>;
    const dailyValues = <?php echo json_encode($daily_values); ?>;

    const monthlyLabels = <?php echo json_encode($monthly_labels); ?>;
    const monthlyValues = <?php echo json_encode($monthly_values); ?>;

    const ctxLine = document.getElementById('expensesLineChart').getContext('2d');

    let expenseChart = new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Expenses (₱)',
                data: dailyValues,
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    ticks: { color: '#e6e6ef' },
                    beginAtZero: true
                },
                x: {
                    ticks: { color: '#e6e6ef' }
                }
            },
            plugins: {
                legend: {
                    labels: { color: '#e6e6ef' }
                }
            }
        }
    });

    document.getElementById('expenseViewMode').addEventListener('change', function () {
        const mode = this.value;

        if (mode === 'daily') {
            expenseChart.data.labels = dailyLabels;
            expenseChart.data.datasets[0].data = dailyValues;
        } else {
            expenseChart.data.labels = monthlyLabels;
            expenseChart.data.datasets[0].data = monthlyValues;
        }

        expenseChart.update();
    });
</script>

</body>
</html>