<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php';

header('Content-Type: application/json'); // Set header to return JSON

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}



$levels = [];

// Fetch levels with level_id, level_name, and price
$levelResult = $conn->query("SELECT id, level_name, price FROM levels");

if ($levelResult->num_rows > 0) {
    while ($row = $levelResult->fetch_assoc()) {
        $levels[] = $row;
    }
    echo json_encode(['levels' => $levels]); // Return data as JSON
} else {
    echo json_encode(['levels' => []]); // Return empty array if no data
}

$conn->close();
?>
