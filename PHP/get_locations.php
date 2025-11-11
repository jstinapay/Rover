<?php
require_once 'connect.php';

header('Content-Type: application/json');

$data = [];

if (isset($_GET['type']) && $_GET['type'] == 'countries' && isset($_GET['continent_id'])) {

    $continent_id = (int)$_GET['continent_id'];

    $sql = "SELECT country_id, country_name FROM country 
            WHERE continent_id = ? ORDER BY country_name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $continent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} elseif (isset($_GET['type']) && $_GET['type'] == 'cities' && isset($_GET['country_id'])) {

    $country_id = (int)$_GET['country_id'];

    $sql = "SELECT city_id, city_name FROM city 
            WHERE country_id = ? ORDER BY city_name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $country_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

echo json_encode($data);
exit();
?>
