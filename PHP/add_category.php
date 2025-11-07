</php
session_start();

if(!isset($_SESSION['rover_id'])) {
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
        die("Connection failed: " . $conn->connect_error);
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
        $category_name = $_POST['category_name'];
        $allocation_amount = $_POST['allocation_amount']

        $sql = "INSERT INTO category (category_id, trip_id, category_name, allocation_amount)
                VALUES (?, ?, ?, ?)"
        $stmt = $conn->prepare(sql);
        $stmt->bind_param("iisd", $category_id, $trip_id, $category_name, $allocation_amount);
        $stmt->execute();
        

    }
?>