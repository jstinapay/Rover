<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
$rover_id = $_SESSION['rover_id'];

require_once 'connect.php';

$currency_code = $_SESSION['currency_code'] ?? 'USD';
$currency_symbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'GBP' => '£', 'CNY' => '¥'];
$symbol = $currency_symbols[$currency_code] ?? '$';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_name = $_POST['trip_name'];
    $city_id = $_POST['city_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $trip_budget = $_POST['total_budget'];

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

    $conn->begin_transaction();
    try {
        $sql_trip = "INSERT INTO trip (rover_id, city_id, trip_name, start_date, end_date, trip_budget, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_trip = $conn->prepare($sql_trip);
        $stmt_trip->bind_param("iisssds", $rover_id, $city_id, $trip_name, $start_date, $end_date, $trip_budget, $status);
        $stmt_trip->execute();

        $new_trip_id = $conn->insert_id;
        $stmt_trip->close();

        $conn->commit();
        header("Location: dashboard.php");
        exit();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $error_message = "Error creating trip: " . $exception->getMessage();
    }
}

$sql_continents = "SELECT continent_id, continent_name FROM continent ORDER BY continent_name";
$continents_result = $conn->query($sql_continents);
$continents = $continents_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Trip</title>
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
        <li>
            <a href="dashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/></svg>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="active">
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
    <section class="createTrip-section">
        <h1>Create a New Trip</h1>
        <p>Start your next adventure by planning your budget.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="createTrip.php" method="POST">
            <label for="trip_name">Trip Name</label>
            <input type="text" id="trip_name" name="trip_name" placeholder="e.g., Tokyo Adventure" required>

            <label for="continent_id">Continent</label>
            <select id="continent_id" name="continent_id" required>
                <option value="">Select a continent...</option>
                <?php foreach ($continents as $continent): ?>
                    <option value="<?php echo $continent['continent_id']; ?>">
                        <?php echo htmlspecialchars($continent['continent_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="country_id">Country</label>
            <select id="country_id" name="country_id" required disabled>
                <option value="">Select a country...</option>
            </select>

            <label for="city_id">City</label>
            <select id="city_id" name="city_id" required disabled>
                <option value="">Select a city...</option>
            </select>

            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" required>

            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" required>

            <label for="total_budget">Total budget</label>
            <div class="input-wrapper">
                <input type="number" min="0" step=".01" id="total_budget" name="total_budget" placeholder="0.00" required>
            </div>

            <button type="submit" class="submit-btn">Create Trip</button>
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