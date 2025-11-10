<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}

if (!isset($_GET['trip_id'])) {
    header("Location: dashboard.php");
    exit();
}

$trip_id = $_GET['trip_id'];
$rover_id = $_SESSION['rover_id'];

$host = "yamanote.proxy.rlwy.net";
$user = "root";
$pass = "ussforDJGtKQAqXiQTHUnStcDIwpdTja";
$dbname = "railway";
$port = "40768";

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trip_name = $_POST['trip_name'];
    $region = $_POST['region'];
    $country = $_POST['country'];
    $city = $_POST['city'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_budget = $_POST['total_budget'];
    $destination_id = $_POST['destination_id'];

    $sql_dest = "UPDATE destination 
                 SET 
                     region = ?, 
                     country = ?, 
                     city = ? 
                 WHERE destination_id = ?";
    $stmt_dest = $conn->prepare($sql_dest);
    $stmt_dest->bind_param("sssi", $region, $country, $city, $destination_id);
    $stmt_dest->execute();
    $stmt_dest->close();

    // check date for status
    $today = new DateTime('today'); // Gets today's date at 00:00:00
    $status = "planned"; // Default status

    if (!empty($start_date)) {
        $start_date_obj = new DateTime($start_date);
        // If start date is today or in the past, it's 'active'
        if ($start_date_obj <= $today) {
            $status = "active";
        }
    }

    if (!empty($end_date)) {
        $end_date_obj = new DateTime($end_date);
        // If end date is in the past, it's 'completed' (this overrides 'active' or 'planned')
        if ($end_date_obj < $today) {
            $status = "completed";
        }
    }

    $sql_trip = "UPDATE trip 
                 SET 
                     trip_name = ?, 
                     start_date = ?,
                     end_date = ?, 
                     total_budget = ?,
                     status = ?
                 WHERE trip_id = ?
                 AND rover_id = ?  ";
    $stmt_trip = $conn->prepare($sql_trip);
    $stmt_trip->bind_param("sssdsii", $trip_name, $start_date, $end_date, $total_budget, $status, $trip_id, $rover_id);

    if ($stmt_trip->execute()) {
        // Success! Redirect back to the dashboard.
        header("Location: dashboard.php");
        exit();
    } else {
        $error_message = "Error updating trip: " . $stmt_trip->error;
    }
    $stmt_trip->close();

} else {
    // 5. (IF THIS IS A GET REQUEST) FETCH CURRENT DATA TO FILL THE FORM

    // This query joins trip and destination to get all data
    $sql_get = "SELECT t.*, d.region, d.country, d.city 
                FROM trip t
                JOIN destination d ON t.destination_id = d.destination_id
                WHERE t.trip_id = ? AND t.rover_id = ?";

    $stmt_get = $conn->prepare($sql_get);
    $stmt_get->bind_param("ii", $trip_id, $rover_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();

    if ($result->num_rows == 0) {
        echo "Trip not found or you do not have permission.";
        exit();
    }
    $trip = $result->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Trip</title> <link rel="stylesheet" href="../CSS/createTrip.css">

        <style>
            :root {
                --currency-symbol: "<?php echo $symbol; ?>";
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
        <section class="createTrip-section">
            <h1>Edit Trip</h1>
            <p>Update the details for your trip</p>

            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="edit_trip.php?trip_id=<?php echo $trip_id; ?>" method="POST">

                <input type="hidden" name="destination_id" value="<?php echo $trip['destination_id']; ?>">

                <label for="trip_name">Trip Name</label>
                <input type="text" id="trip_name" name="trip_name"
                       value="<?php echo htmlspecialchars($trip['trip_name']); ?>" required>

                <label for="region">Region</label>
                <select id="region" name="region" required>
                    <option value="Asia" <?php if ($trip['region'] == 'Asia') echo 'selected'; ?>>Asia</option>
                    <option value="Europe" <?php if ($trip['region'] == 'Europe') echo 'selected'; ?>>Europe</option>
                    <option value="North America" <?php if ($trip['region'] == 'North America') echo 'selected'; ?>>North America</option>
                    <option value="South America" <?php if ($trip['region'] == 'South America') echo 'selected'; ?>>South America</option>
                    <option value="Africa" <?php if ($trip['region'] == 'Africa') echo 'selected'; ?>>Africa</option>
                    <option value="Australia" <?php if ($trip['region'] == 'Australia') echo 'selected'; ?>>Australia</option>
                    <option value="Antarctica" <?php if ($trip['region'] == 'Antarctica') echo 'selected'; ?>>Antarctica</option>
                </select>

                <label for="country">Country</label>
                <input type="text" id="country" name="country"
                       value="<?php echo htmlspecialchars($trip['country']); ?>" required>

                <label for="city">City</label>
                <input type="text" id="city" name="city"
                       value="<?php echo htmlspecialchars($trip['city']); ?>" required>

                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date"
                       value="<?php echo htmlspecialchars($trip['start_date']); ?>" required>

                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date"
                       value="<?php echo htmlspecialchars($trip['end_date']); ?>" required>

                <label for="total_budget">Total Budget</label>
                <div class="input-wrapper">
                    <input type="number" id="total_budget" name="total_budget" step="0.01" min="0"
                       value="<?php echo htmlspecialchars($trip['total_budget']); ?>" required>
                </div>
                
                <button type="submit" class="submit-btn">Save Changes</button>
                <a href="dashboard.php" class="cancel-link">Cancel</a>
            </form>
        </section>
    </main>
    </body>
    </html>
