<?php
session_start();

if(!isset($_SESSION['rover_id'])){
    header("Location: ../login.html");
    exit();
}

require_once 'connect.php';

if(!isset($_GET['trip_id'])){
    echo "<script>alert('No trip selected.'); window.location.href = 'dashboard.php';</script>";
    exit();
}

$trip_id = $_GET['trip_id'];
$rover_id = $_SESSION['rover_id'];

$sql = "SELECT t.trip_name, t.start_date, t.end_date, t.trip_budget, t.status, 
               c.city_name, co.country_name
        FROM trip t
        JOIN city c ON t.city_id = c.city_id
        JOIN country co ON c.country_id = co.country_id
        WHERE t.trip_id = ? AND t.rover_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $trip_id, $rover_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Trip not found.'); window.location.href = 'dashboard.php';</script>";
    exit();
}

$trip = $result->fetch_assoc();

$sql_budget = "SELECT cb.category_budget_id, c.category_name, cb.allocated_budget,
                      IFNULL(SUM(e.expense_amount), 0) AS total_spent
               FROM category_budget cb
               JOIN category c ON cb.category_id = c.category_id
               LEFT JOIN expense e ON cb.category_budget_id = e.category_budget_id
               WHERE cb.trip_id = ?
               GROUP BY cb.category_budget_id";
$stmt = $conn->prepare($sql_budget);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$budgets = $stmt->get_result();

$total_spent = 0;
foreach ($budgets as $b) {
    $total_spent += $b['total_spent'];
}
$budgets->data_seek(0);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Summary - <?php echo htmlspecialchars($trip['trip_name']); ?></title>
    <link rel="stylesheet" href="../CSS/trip_summary.css">
 
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
    <div class="summary-container">
        <div class="trip-header">
            <h2><?php echo htmlspecialchars($trip['trip_name']); ?></h2>
            <span class="trip-status"><?php echo htmlspecialchars($trip['status']); ?></span>
        </div>
        <p><strong>Destination:</strong> <?php echo htmlspecialchars($trip['city_name']) . ', ' . htmlspecialchars($trip['country_name']); ?></p>
        <p><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($trip['start_date'])); ?></p>
        <p><strong>End Date:</strong> <?php echo date('M d, Y', strtotime($trip['end_date'])); ?></p>
        <p><strong>Budget:</strong> ₱<?php echo number_format($trip['trip_budget'], 2); ?></p>

        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Allocated Budget</th>
                    <th>Total Spent</th>
                    <th>Remaining</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $budgets->fetch_assoc()): 
                    $remaining = $row['allocated_budget'] - $row['total_spent'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td>₱<?php echo number_format($row['allocated_budget'], 2); ?></td>
                        <td>₱<?php echo number_format($row['total_spent'], 2); ?></td>
                        <td>₱<?php echo number_format($remaining, 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="total-section">
            <p><strong>Total Spent:</strong> ₱<?php echo number_format($total_spent, 2); ?></p>
            <p><strong>Remaining Overall Budget:</strong> ₱<?php echo number_format($trip['trip_budget'] - $total_spent, 2); ?></p>
        </div>

        <a href="dashboard.php" class="btn_back">← Back to Dashboard</a>
    </div>
</main>
</body>
</html>
