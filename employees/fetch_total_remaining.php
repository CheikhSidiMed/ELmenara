<?php
include 'db_connection.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);
$student_id = $data['student_id'];

$response = ['total_remaining' => 0.00];

// Prepare SQL to fetch the total remaining for the selected student
if ($student_id) {
    $query = "SELECT SUM(remaining_amount) AS total_remaining FROM payments WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $row = $result->fetch_assoc();
        $response['total_remaining'] = $row['total_remaining'] ?? 0.00;
    }
    $stmt->close();
}

$conn->close();

echo json_encode($response);
?>
