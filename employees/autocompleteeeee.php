<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_GET['term'])) {
    $term = $_GET['term'] . '%'; // Search for names or phone numbers starting with the typed term

    $sql = "SELECT CONCAT(full_name) AS employee_info 
            FROM employees 
            WHERE full_name LIKE ? OR phone LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $term, $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['employee_info'];
    }

    echo json_encode($suggestions);
}

$conn->close();
?>
