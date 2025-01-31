<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['student_ids'])) {
    $student_ids = $_POST['student_ids'];
    $months = $_POST['months'];
    $due_amounts = $_POST['due_amounts'];
    $paid_amounts = $_POST['paid_amounts'];
    $remaining_amounts = $_POST['remaining_amounts'];
    $payment_methods = $_POST['payment_methods'];
    $banks = isset($_POST['banks']) ? $_POST['banks'] : [];

    foreach ($student_ids as $student_id) {
        $month = $months[$student_id];
        $due_amount = $due_amounts[$student_id];
        $paid_amount = $paid_amounts[$student_id];
        $remaining_amount = $remaining_amounts[$student_id];
        $payment_method = $payment_methods[$student_id];
        $bank_id = $payment_method === "بنكي" ? $banks[$student_id] : null;

        // Insert payment record
        $stmt = $conn->prepare("
            INSERT INTO payments (student_id, month, due_amount, paid_amount, remaining_amount, payment_method, bank_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssss", $student_id, $month, $due_amount, $paid_amount, $remaining_amount, $payment_method, $bank_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: payment_success.php");
    exit();
}

// Close the database connection
$conn->close();
?>
