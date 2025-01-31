<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_GET['employee_number'])) {
    $employee_number = $_GET['employee_number'];

    $sql = "SELECT e.employee_number, e.full_name, e.balance, j.job_name 
            FROM employees e 
            JOIN jobs j ON e.job_id = j.id 
            WHERE e.employee_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $employee_number);
    $stmt->execute();
    $result = $stmt->get_result();

    $employee = [];
    if ($row = $result->fetch_assoc()) {
        $employee = [
            'employee_number' => $row['employee_number'],
            'full_name' => $row['full_name'],
            'job_name' => $row['job_name'],
            'balance' => $row['balance']
        ];
    }

    echo json_encode($employee);
}

$conn->close();
?>
