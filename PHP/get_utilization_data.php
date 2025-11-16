<?php
session_start();

if (!isset($_SESSION['rover_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

require_once 'connect.php';
$rover_id = $_SESSION['rover_id'];


$sql_budget_utilization = "
    SELECT c.category_name, 
           SUM(cb.allocated_budget) AS total_budget, 
           COALESCE(SUM(e.expense_amount), 0) AS total_expense
    FROM category_budget cb
    JOIN category c ON cb.category_id = c.category_id
    LEFT JOIN expense e ON cb.category_budget_id = e.category_budget_id
    JOIN trip t ON cb.trip_id = t.trip_id
    WHERE t.rover_id = ?
";

if (isset($_GET['trip_id']) && $_GET['trip_id'] !== 'all' && is_numeric($_GET['trip_id'])) {
    $trip_id = (int)$_GET['trip_id'];
    $sql_budget_utilization .= " AND t.trip_id = ? ";
    $sql_budget_utilization .= "
        GROUP BY c.category_name
        ORDER BY total_budget DESC, total_expense DESC;
    ";

    $stmt_budget_utilization = $conn->prepare($sql_budget_utilization);
    $stmt_budget_utilization->bind_param("ii", $rover_id, $trip_id);

} else {

    $sql_budget_utilization .= "
        GROUP BY c.category_name
        ORDER BY total_budget DESC, total_expense DESC;
    ";

    $stmt_budget_utilization = $conn->prepare($sql_budget_utilization);
    $stmt_budget_utilization->bind_param("i", $rover_id);
}

$stmt_budget_utilization->execute();
$budget_utilization = $stmt_budget_utilization->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();


$response_data = [
    'labels'    => array_column($budget_utilization, 'category_name'),
    'allocated' => array_column($budget_utilization, 'total_budget'),
    'spent'     => array_column($budget_utilization, 'total_expense')
];


header('Content-Type: application/json');
echo json_encode($response_data);
exit();
?>
