<?php
include 'db_connection.php';

$branchId = $_GET['branch_id'] ?? 0;

$classes = [];

if ($branchId) {
    $stmt = $conn->prepare("SELECT class_name FROM classes WHERE branch_id = ?");
    $stmt->bind_param("i", $branchId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $classes[] = $row['class_name'];
    }
} else {
    $result = $conn->query("SELECT class_name FROM classes");
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row['class_name'];
    }
}

header('Content-Type: application/json');
echo json_encode($classes);
?>
