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
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <style>
        .summary-container {
            margin: 2rem auto;
            width: 80%;
            background-color: #222;
            color: #eee;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(255,255,255,0.05);
        }
        .summary-container h2 {
            margin-bottom: 1rem;
            color: #fff;
        }
        .summary-container p {
            margin: 0.5rem 0;
            font-size: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            background-color: #2a2a2a;
            border-radius: 10px;
            overflow: hidden;
        }
        table th, table td {
            padding: 0.8rem 1rem;
            text-align: left;
        }
        table th {
            background-color: #333;
            color: #fff;
            font-weight: bold;
        }
        table tr:nth-child(even) {
            background-color: #292929;
        }
        table tr:hover {
            background-color: #383838;
        }
        .total-section {
            margin-top: 2rem;
            background-color: #2b2b2b;
            padding: 1rem;
            border-radius: 10px;
        }
        .btn_back {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.6rem 1.4rem;
            background-color: #333;
            color: #e3e3e3;
            text-decoration: none;
            border-radius: 6px;
            transition: 0.3s;
        }
        .btn_back:hover {
            background-color: #555;
        }
        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .trip-header .trip-status {
            background-color: #444;
            color: #eee;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            text-transform: capitalize;
        }
    </style>
</head>
<body>
<nav id="sidebar">
  <ul>
    <li>
      <span class="logo">Rover</span>
      <button onclick="toggleSidebar()" id="toggle-btn">
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
          <path d="M440-240 200-480l240-240 56 56-183 184 183 184-56 56Zm264 0L464-480l240-240 56 56-183 184 183 184-56 56Z"/>
        </svg>
      </button>
    </li>
    <li><a href="../index.html"><span>Home</span></a></li>
    <li><a href="dashboard.php"><span>Dashboard</span></a></li>
    <li><a href="createTrip.php"><span>Create Trip</span></a></li>
    <li><a href="profile.php"><span>Profile</span></a></li>
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
