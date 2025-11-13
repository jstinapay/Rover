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

$sql_update = "UPDATE trip SET status = 'completed' WHERE trip_id = ? AND rover_id = ?";
$stmt = $conn->prepare($sql_update);
$stmt->bind_param("ii", $trip_id, $rover_id);

if ($stmt->execute()) {
    $stmt->close();

    $sql_summary = "SELECT c.category_name, 
                           cb.allocated_budget, 
                           COALESCE(SUM(e.expense_amount),0) AS total_spent
                    FROM category_budget cb
                    JOIN category c ON cb.category_id = c.category_id
                    LEFT JOIN expense e ON cb.category_budget_id = e.category_budget_id
                    WHERE cb.trip_id = ?
                    GROUP BY cb.category_budget_id";

    $stmt_sum = $conn->prepare($sql_summary);
    $stmt_sum->bind_param("i", $trip_id);
    $stmt_sum->execute();
    $result = $stmt_sum->get_result();
    
    $final_summary = [];
    while ($row = $result->fetch_assoc()) {
        $final_summary[] = [
            'category_name' => $row['category_name'],
            'allocated_budget' => $row['allocated_budget'],
            'total_spent' => $row['total_spent'],
            'remaining_budget' => $row['allocated_budget'] - $row['total_spent']
        ];
    }
    $stmt_sum->close();
    $conn->close();

    $_SESSION['final_summary'] = $final_summary;

    header("Location: dashboard.php?trip_id=" . $trip_id . "&status=completed");
    exit();
} else {
    echo "Error finishing trip: " . $stmt->error;
    $stmt->close();
    $conn->close();
}
?>
