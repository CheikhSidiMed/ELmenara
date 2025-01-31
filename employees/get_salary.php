<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_POST['employeePhone'])) {
    $employeePhone = $_POST['employeePhone'];

    // Fetch salary from employees table
    $stmt = $conn->prepare("SELECT salary FROM employees WHERE phone = ?");
    $stmt->bind_param('s', $employeePhone);
    $stmt->execute();
    $stmt->bind_result($salary);
    $stmt->fetch();
    $stmt->close();

    // Return salary as JSON
    echo json_encode(['salary' => $salary]);
}

$conn->close();
?>
