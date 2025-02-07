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
    $term = '%' . $_GET['term'] . '%'; // Search for names or phone numbers containing the typed term

    // SQL query to search student_name, student phone, and agent phone
    $sql = "SELECT s.id, s.student_name, s.phone AS student_phone, a.phone AS agent_phone 
            FROM students s
            LEFT JOIN agents a ON s.agent_id = a.agent_id
            WHERE s.student_name LIKE ? OR s.phone LIKE ? OR a.phone LIKE ? ORDER BY s.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $term, $term, $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $phone = $row['agent_phone'] ?? $row['student_phone'];
        $suggestions[] = [
            'student_name' => $row['student_name'] . " (" . $phone . ")",
            'student_phone' => $row['student_phone'], 
            'student_id' => $row['id'], 
            'agent_phone' => $row['agent_phone']
        ];
    }

    echo json_encode($suggestions);
}

$conn->close();
?>
