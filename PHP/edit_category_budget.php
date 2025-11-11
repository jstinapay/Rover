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

require_once 'connect.php';
$rover_id = $_SESSION['rover_id'];
$category_budget_id = $_GET['id'];

$currency_code = isset($_SESSION['currency_code']) ? $_SESSION['currency_code'] : 'USD';
$currency_symbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'GBP' => '£', 'CNY' => '¥'];
$symbol = isset($currency_symbols[$currency_code]) ? $currency_symbols[$currency_code] : '$';

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_allocated_budget = (float)$_POST['allocated_budget'];
    $trip_id = $_POST['trip_id'];
    $old_allocated_budget = (float)$_POST['old_allocated_budget'];

    $sql_budget = "SELECT 
                     t.trip_budget,
                     COALESCE(SUM(cb.allocated_budget), 0) AS current_allocated
                   FROM trip t
                   LEFT JOIN category_budget cb ON t.trip_id = cb.trip_id
                   WHERE t.trip_id = ? AND t.rover_id = ?
                   GROUP BY t.trip_id";

    $stmt_budget = $conn->prepare($sql_budget);
    $stmt_budget->bind_param("ii", $trip_id, $rover_id);
    $stmt_budget->execute();
    $budget_data = $stmt_budget->get_result()->fetch_assoc();
    $stmt_budget->close();

    if (!$budget_data) {
        $error_message = "Error: Trip not found or you do not have permission.";
    } else {
        $total_budget = (float) $budget_data['trip_budget'];
        $current_allocated = (float) $budget_data['current_allocated'];

        $new_total_allocated = ($current_allocated - $old_allocated_budget) + $new_allocated_budget;

        if ($new_total_allocated > $total_budget) {
            $remaining_budget = $total_budget - ($current_allocated - $old_allocated_budget);
            $error_message = "Allocation exceeds total trip budget. You only have " . $symbol . number_format($remaining_budget, 2) . " left to allocate.";
        } else {
            $sql_update = "UPDATE category_budget SET allocated_budget = ? 
                           WHERE category_budget_id = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("di", $new_allocated_budget, $category_budget_id);

            if ($stmt->execute()) {
                header("Location: view_trip.php?trip_id=" . $trip_id);
                exit();
            } else {
                $error_message = "Update Failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$sql_get = "SELECT 
                cb.allocated_budget, cb.trip_id,
                c.category_name
            FROM category_budget cb
            JOIN category c ON cb.category_id = c.category_id
            JOIN trip t ON cb.trip_id = t.trip_id
            WHERE cb.category_budget_id = ? AND t.rover_id = ?";

$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("ii", $category_budget_id, $rover_id);
$stmt_get->execute();
$budget = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$budget) {
    echo "Budget not found or you do not have permission.";
    $conn->close();
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Budget</title>
    <link rel="stylesheet" href="../CSS/createTrip.css">
    <style>
        :root { --currency-symbol: "<?php echo $symbol; ?>"; }
        .category-name-display {
            background: var(--base-clr);
            padding: 0.8em;
            font-size: 1.1em;
            border-radius: 0.5em;
            border: 1px dashed var(--line-clr);
            color: var(--secondary-text-clr);
            margin-bottom: 1.2em;
        }
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
            <a href="profile.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg>
                <span>Profile</span>
            </a>
        </li>

    </ul>
</nav>
<main>
    <section class="createTrip-section">
        <h1>Edit Budget</h1>
        <p>Update the allocated budget for this category.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="edit_category_budget.php?id=<?php echo $category_budget_id; ?>" method="POST">

            <input type="hidden" name="trip_id" value="<?php echo $budget['trip_id']; ?>">
            <input type="hidden" name="old_allocated_budget" value="<?php echo $budget['allocated_budget']; ?>">

            <label for="category_name">Category</label>
            <div class="category-name-display">
                <?php echo htmlspecialchars($budget['category_name']); ?>
            </div>

            <label for="allocated_budget">Allocated Budget</label>
            <div class="input-wrapper">
                <input type="number" id="allocated_budget" name="allocated_budget" step="0.01" min="0"
                       value="<?php echo htmlspecialchars($budget['allocated_budget']); ?>" required>
            </div>

            <button type="submit" class="submit-btn">Save Changes</button>
            <a href="view_trip.php?trip_id=<?php echo $budget['trip_id']; ?>" class="cancel-link">Cancel</a>
        </form>
    </section>
</main>
</body>
</html>