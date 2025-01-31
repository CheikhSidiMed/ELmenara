<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';

$students = [];
if ($searchTerm !== '') {
    $stmt = $conn->prepare("
        SELECT student_name, phone 
        FROM students 
        WHERE student_name LIKE ? OR phone LIKE ?
    ");
    $likeSearchTerm = '%' . $searchTerm . '%';
    $stmt->bind_param('ss', $likeSearchTerm, $likeSearchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row['student_name'] . ' - ' . $row['phone'];
    }

    $stmt->close();
}

echo json_encode($students);
$conn->close();
?>
