<?php
include '../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $is_active = $_POST['is_active'];
    
    $stmt = $conn->prepare("UPDATE students SET is_active = ? WHERE id = ?");
    if ($stmt->execute([$is_active,$studentId])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
?>