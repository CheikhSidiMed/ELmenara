<?php
include 'db_connection.php';

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
