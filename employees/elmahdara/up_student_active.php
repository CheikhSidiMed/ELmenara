<?php
include '../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $is_active = $_POST['is_active'];
    
    $stmt = $conn->prepare("UPDATE students SET is_active = ? WHERE id = ?");
    // Liez les paramètres avec bind_param. Ici, on suppose que les deux sont des entiers (ii)
    $stmt->bind_param('ii', $is_active, $studentId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    $stmt->close();
    exit;
}
?>
