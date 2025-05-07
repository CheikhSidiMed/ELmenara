<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$branches = [];
$classes = [];

if (isset($_GET['class_id'])) {
    $class_id = $_GET['class_id'];

    // Query to count students in the selected class
    $stmt = $conn->prepare("SELECT COUNT(*) as student_count FROM students WHERE etat=0 AND is_active=0 AND class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    echo json_encode(['student_count' => $data['student_count']]);
    exit;
}



// Fetch branches
$branchResult = $conn->query("SELECT branch_id, branch_name FROM branches");
while ($row = $branchResult->fetch_assoc()) {
    $branches[] = $row;
}

// Fetch classes with prices
$classResult = $conn->query("SELECT class_id, branch_id, class_name, Price FROM classes");
while ($row = $classResult->fetch_assoc()) {
    $classes[] = $row;
}

// Return JSON response
echo json_encode(['branches' => $branches, 'classes' => $classes]);

$conn->close();
?>
