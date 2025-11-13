<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}

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

$rover_id = $_SESSION['rover_id'];
$sql = "SELECT 
            t.trip_id,
            t.trip_name,
            t.start_date,
            t.end_date,
            t.trip_budget,
            t.status,
            ci.city_name,
            co.country_name
            FROM
                trip t
            JOIN
                city ci ON t.city_id = ci.city_id
            JOIN 
                country co ON ci.country_id = co.country_id        
            WHERE 
                t.rover_id = ?
            ORDER BY 
                t.start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $rover_id);
$stmt->execute();
$result = $stmt->get_result();
$trips = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="../CSS/dashboard.css">
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
  <section class='dashboard'>
    <h1>My Trips</h1>
    <a href="createTrip.php" class="btn-add" aria-label="Add new trip">
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M440-280h80v-160h160v-80H520v-160h-80v160H280v80h160v160Zm40 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
    </a>
  </section>

    <div class="class">
        <?php if (empty($trips)): ?>
        <div class="no_trips">
            <p>You haven't created any trips yet. <a href="createTrip.php">Create your first trip!</a></p>
        </div>
        <?php else: ?>
        <?php foreach ($trips as $trip): ?>
        <div class="trip_card">
            <div class="trip_header">
                <h3><?php echo htmlspecialchars($trip['trip_name']) ?></h3>
                <span class="trip_status"><?php echo ucfirst(htmlspecialchars($trip['status']))?></span>
            </div>
            <div class="trip_details">
                <p><strong>Destination: </strong><?php echo htmlspecialchars($trip['city_name']) . ', ' . htmlspecialchars($trip['country_name']);?></p>
                <p><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($trip['start_date'])); ?></p>
                <p><strong>End Date:</strong> <?php echo date('M d, Y', strtotime($trip['end_date'])); ?></p>
                <?php if (isset($trip['trip_budget'])): ?>
                    <p><strong>Budget:</strong> <?php echo $symbol; ?><?php echo number_format($trip['trip_budget'], 2) ?> </p>
                <?php endif; ?>
            </div>
            <div class="trip_actions">
                <?php if ($trip['status'] === 'completed'): ?>
                    <a href="trip_summary.php?trip_id=<?php echo $trip['trip_id']; ?>" class="btn_view">View Summary</a>
                    <a href="delete_trip.php?trip_id=<?php echo $trip['trip_id']; ?>" class="btn_delete" onclick="return confirm('Are you sure you want to delete this trip? This action cannot be undone.');">Delete</a>
                <?php else: ?>
                    <a href="view_trip.php?trip_id=<?php echo $trip['trip_id']; ?>" class="btn_view">View</a>
                    <a href="edit_trip.php?trip_id=<?php echo $trip['trip_id']; ?>" class="btn_edit">Edit</a>
                    <a href="delete_trip.php?trip_id=<?php echo $trip['trip_id']; ?>" class="btn_delete" onclick="return confirm('Are you sure you want to delete this trip? This action cannot be undone.');">Delete</a>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>



</main>
</body>
</html>
