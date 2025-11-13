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

require_once 'connect.php';
$rover_id = $_SESSION['rover_id'];
$trip_id = $_GET['trip_id'];

$currency_code = isset($_SESSION['currency_code']) ? $_SESSION['currency_code'] : 'USD';
$currency_symbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'GBP' => '£', 'CNY' => '¥'];
$symbol = isset($currency_symbols[$currency_code]) ? $currency_symbols[$currency_code] : '$';

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trip_name = $_POST['trip_name'];
    $city_id = $_POST['city_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $trip_budget = $_POST['total_budget'];

    $today = new DateTime('today');
    $status = "planned";
    if (!empty($start_date)) {
        $start_date_obj = new DateTime($start_date);
        if ($start_date_obj <= $today) {
            $status = "active";
        }
    }
    if (!empty($end_date)) {
        $end_date_obj = new DateTime($end_date);
        if ($end_date_obj < $today) {
            $status = "completed";
        }
    }

    $sql_update = "UPDATE trip SET 
                        trip_name = ?,
                        city_id = ?,
                        start_date = ?,
                        end_date = ?,
                        trip_budget = ?,
                        status = ?
                   WHERE trip_id = ? AND rover_id = ?";

    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("sisssssi", $trip_name, $city_id, $start_date, $end_date, $trip_budget, $status, $trip_id, $rover_id);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error_message = "Update Failed: " . $stmt->error;
    }
    $stmt->close();
}

$sql_trip = "SELECT 
                t.trip_name, t.start_date, t.end_date, t.trip_budget, t.status,
                ci.city_id,
                co.country_id,
                con.continent_id
             FROM trip t
             JOIN city ci ON t.city_id = ci.city_id
             JOIN country co ON ci.country_id = co.country_id
             JOIN continent con ON co.continent_id = con.continent_id
             WHERE t.trip_id = ? AND t.rover_id = ?";

$stmt_trip = $conn->prepare($sql_trip);
$stmt_trip->bind_param("ii", $trip_id, $rover_id);
$stmt_trip->execute();
$trip = $stmt_trip->get_result()->fetch_assoc();
$stmt_trip->close();

if (!$trip) {
    echo "Trip not found or you do not have permission.";
    exit();
}

$sql_continents = "SELECT continent_id, continent_name FROM continent ORDER BY continent_name";
$continents = $conn->query($sql_continents)->fetch_all(MYSQLI_ASSOC);

$sql_countries = "SELECT country_id, country_name FROM country WHERE continent_id = ? ORDER BY country_name";
$stmt_countries = $conn->prepare($sql_countries);
$stmt_countries->bind_param("i", $trip['continent_id']);
$stmt_countries->execute();
$countries = $stmt_countries->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_countries->close();

$sql_cities = "SELECT city_id, city_name FROM city WHERE country_id = ? ORDER BY city_name";
$stmt_cities = $conn->prepare($sql_cities);
$stmt_cities->bind_param("i", $trip['country_id']);
$stmt_cities->execute();
$cities = $stmt_cities->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cities->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trip</title>
    <link rel="stylesheet" href="../CSS/createTrip.css">
    <style>
        :root { --currency-symbol: "<?php echo $symbol; ?>"; }
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
            <a href="profile.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg>
                <span>Profile</span>
            </a>
        </li>

    </ul>
</nav>
<main>
    <section class="createTrip-section">
        <h1>Edit Trip</h1>
        <p>Update your trip details.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="edit_trip.php?trip_id=<?php echo $trip_id; ?>" method="POST">

            <label for="trip_name">Trip Name</label>
            <input type="text" id="trip_name" name="trip_name"
                   value="<?php echo htmlspecialchars($trip['trip_name']); ?>" required>

            <label for="continent_id">Continent</label>
            <select id="continent_id" name="continent_id" required>
                <option value="">Select a continent...</option>
                <?php foreach ($continents as $continent): ?>
                    <option value="<?php echo $continent['continent_id']; ?>"
                        <?php if ($continent['continent_id'] == $trip['continent_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($continent['continent_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="country_id">Country</label>
            <select id="country_id" name="country_id" required>
                <option value="">Select a country...</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo $country['country_id']; ?>"
                        <?php if ($country['country_id'] == $trip['country_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($country['country_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="city_id">City</label>
            <select id="city_id" name="city_id" required>
                <option value="">Select a city...</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?php echo $city['city_id']; ?>"
                        <?php if ($city['city_id'] == $trip['city_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($city['city_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date"
                   value="<?php echo htmlspecialchars($trip['start_date']); ?>" required>

            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date"
                   value="<?php echo htmlspecialchars($trip['end_date']); ?>" required>

            <label for="total_budget">Total budget</label>
            <div class="input-wrapper">
                <input type="number" min="0" step=".01" id="total_budget" name="total_budget"
                       value="<?php echo htmlspecialchars($trip['trip_budget']); ?>" required>
            </div>

            <button type="submit" class="submit-btn">Save Changes</button>
            <a href="dashboard.php" class="cancel-link">Cancel</a>
        </form>
    </section>
</main>

<script>
    const continentSelect = document.getElementById('continent_id');
    const countrySelect = document.getElementById('country_id');
    const citySelect = document.getElementById('city_id');

    continentSelect.addEventListener('change', function() {
        const continentId = this.value;

        countrySelect.innerHTML = '<option value="">Loading...</option>';
        countrySelect.disabled = true;
        citySelect.innerHTML = '<option value="">Select a city...</option>';
        citySelect.disabled = true;

        if (continentId) {
            fetch(`get_locations.php?type=countries&continent_id=${continentId}`)
                .then(response => response.json())
                .then(data => {
                    populateDropdown(countrySelect, data, 'country');
                    countrySelect.disabled = false;
                })
                .catch(error => console.error('Error fetching countries:', error));
        }
    });

    countrySelect.addEventListener('change', function() {
        const countryId = this.value;

        citySelect.innerHTML = '<option value="">Loading...</option>';
        citySelect.disabled = true;

        if (countryId) {
            fetch(`get_locations.php?type=cities&country_id=${countryId}`)
                .then(response => response.json())
                .then(data => {
                    populateDropdown(citySelect, data, 'city');
                    citySelect.disabled = false;
                })
                .catch(error => console.error('Error fetching cities:', error));
        }
    });

    function populateDropdown(selectElement, data, type) {
        selectElement.innerHTML = `<option value="">Select a ${type}...</option>`;
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[type + '_id'];
            option.textContent = item[type + '_name'];
            selectElement.appendChild(option);
        });
    }
</script>
</body>
</html>