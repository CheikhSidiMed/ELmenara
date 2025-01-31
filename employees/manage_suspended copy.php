<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);

    if (isset($_POST['return_employee'])) {
        // Fetch the employee data from suspended_employees
        $query = "SELECT * FROM suspended_employees WHERE suspension_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $employee = $result->fetch_assoc();

            $insertSQL = "INSERT INTO employees 
            (employee_number, full_name, balance, phone, job_id, salary, subscription_date, id_number)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)";
        
        $stmtInsert = $conn->prepare($insertSQL);
        $stmtInsert->bind_param(
            'ssdisss',
            $employee['employee_number'],
            $employee['full_name'],
            $employee['balance'],
            $employee['phone'],
            $employee['job_id'],
            $employee['salary'],
            $employee['id_number']
        );
        $stmtInsert->execute();
        

            // Remove from suspended_employees
            $deleteQuery = "DELETE FROM suspended_employees WHERE suspension_id = ?";
            $stmtDelete = $conn->prepare($deleteQuery);
            $stmtDelete->bind_param('i', $id);
            $stmtDelete->execute();
        }
    } elseif (isset($_POST['delete_employee'])) {
        // Permanently delete the employee
        $query = "DELETE FROM suspended_employees WHERE suspension_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
}

header('Location: suspended_employees.php');
exit();
