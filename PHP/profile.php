<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    header("Location: ../login.html");
    exit();
}
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

$sql_user = "SELECT first_name, last_name, email, phone_number, currency_code 
             FROM rover 
             WHERE rover_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $rover_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$sql_methods = "SELECT payment_method_id, payment_method_name 
                FROM payment_method 
                WHERE rover_id = ?";
$stmt_methods = $conn->prepare($sql_methods);
$stmt_methods->bind_param("i", $rover_id);
$stmt_methods->execute();
$payment_methods = $stmt_methods->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_methods->close();

$conn->close();
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>

    <link rel="stylesheet" href="../CSS/profile.css">
    <script type="text/javascript" src="../JS/app.js" defer></script>

    <style>
            :root {
                --currency-symbol: "<?php echo $symbol; ?>";
            }
        </style>


</head>

<body>
    <nav id="sidebar">
        <ul>

            <li>
                <span class="logo">Rover</span>
                <button onclick=toggleSidebar() id="toggle-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path
                            d="M440-240 200-480l240-240 56 56-183 184 183 184-56 56Zm264 0L464-480l240-240 56 56-183 184 183 184-56 56Z" />
                    </svg>
                </button>
            </li>
            <li>
                <a href="../index.html">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path
                            d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z" />
                    </svg>
                    <span>Home</span>
                </a>
            </li>
            <li class="active">
                <a href="dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path
                            d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z" />
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="createTrip.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path
                            d="M280-80v-100l120-84v-144L80-280v-120l320-224v-176q0-33 23.5-56.5T480-880q33 0 56.5 23.5T560-800v176l320 224v120L560-408v144l120 84v100l-200-60-200 60Z" />
                    </svg>
                    <span>Create Trip</span>
                </a>
            </li>
            <li>
                <button onclick="toggleSubMenu(this)" class="dropdown-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path
                            d="M240-160q-66 0-113-47T80-320v-320q0-66 47-113t113-47h480q66 0 113 47t47 113v320q0 66-47 113t-113 47H240Zm0-480h480q22 0 42 5t38 16v-21q0-33-23.5-56.5T720-720H240q-33 0-56.5 23.5T160-640v21q18-11 38-16t42-5Zm-74 130 445 108q9 2 18 0t17-8l139-116q-11-15-28-24.5t-37-9.5H240q-26 0-45.5 13.5T166-510Z" />
                    </svg>
                    <span>Budget</span>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z" />
                    </svg>
                </button>
                <ul class="sub-menu">
                    <div>
                        <li><a href="#">Allocate Budget</a></li>
                        <li><a href="#">Custom Category</a></li>
                    </div>
                </ul>
            <li>
                <button onclick="toggleSubMenu(this)" class="dropdown-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path
                            d="M441-120v-86q-53-12-91.5-46T293-348l74-30q15 48 44.5 73t77.5 25q41 0 69.5-18.5T587-356q0-35-22-55.5T463-458q-86-27-118-64.5T313-614q0-65 42-101t86-41v-84h80v84q50 8 82.5 36.5T651-650l-74 32q-12-32-34-48t-60-16q-44 0-67 19.5T393-614q0 33 30 52t104 40q69 20 104.5 63.5T667-358q0 71-42 108t-104 46v84h-80Z" />
                    </svg>
                    <span>Expenses</span>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z" />
                    </svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path
                            d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z" />
                    </svg>
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
                    <p><?php echo $user['phone_number']; ?></p>
                </div>
                <div class="info-item">
                    <span>Currency</span>
                    <p><?php echo $user['currency_code']; ?></p>
                </div>
            </div>
        </div>
        <div class="profile-actions">
            <a href="edit_rover.php" class="btn-edit">Edit Profile</a>
        </div>
    </div>

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
                        <a href="delete_payment_method.php?id=<?php echo $method['payment_method_id']; ?>" 
                           class="btn-delete" 
                           onclick="return confirm('Are you sure?');">
                           Delete
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</main>

</body>

</html>