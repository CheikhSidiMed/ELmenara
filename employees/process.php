<?php
// Include database connection
require_once 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}



$user_id = intval($_SESSION['userid']);
$student_id = intval($_POST['id'] ?? 0);
$amount_paid = floatval($_POST['amount_paid'] ?? 0);
$payment_method = $_POST['payment_methodd'] ?? null;
$bank_id = ($payment_method === "بنكي" && isset($_POST['bank'])) ? intval($_POST['bank']) : null;
$fund_id = ($payment_method === "نقدي") ? 1 : null;
$agent_id = $_SESSION['agent_id'] ?? null; // Assuming `agent_id` is stored in the session.

if (empty($student_id) || $amount_paid <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data. Please fill all fields.']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Check unpaid rows for the student
    $query_count = "SELECT COUNT(*) AS row_count FROM payments WHERE student_id = ? AND remaining_amount > 0.00";
    $stmt_count = $conn->prepare($query_count);
    $stmt_count->bind_param("i", $student_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row = $result_count->fetch_assoc();
    $row_count = $row['row_count'] ?? 0;

    if ($row_count === 0) {
        throw new Exception("No arrears found for this student.");
    }

    // 2. Distribute the paid amount across rows
    $amount_per_row = $amount_paid / $row_count;

    // 3. Update payments for student
    $query_update = "UPDATE payments SET remaining_amount = GREATEST(remaining_amount - ?, 0) WHERE student_id = ? AND remaining_amount > 0.00";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param("di", $amount_per_row, $student_id);
    $stmt_update->execute();

    $query_update_ct = "UPDATE combined_transactions SET remaining_amount = GREATEST(remaining_amount - ?, 0) WHERE student_id = ? AND remaining_amount > 0.00";
    $stmt_update_ct = $conn->prepare($query_update_ct);
    $stmt_update_ct->bind_param("di", $amount_per_row, $student_id);
    $stmt_update_ct->execute();

    if ($stmt_update->affected_rows > 0) {
        // Fetch student name
        $stmt_student = $conn->prepare("SELECT student_name FROM students WHERE id = ?");
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $result_student = $stmt_student->get_result();
        $student_name = $result_student->fetch_assoc()['student_name'] ?? 'Unknown Student';
        $stmt_student->close();

        // Transaction description
        $transaction_description = ".سدد متأخرات : ($student_name)";

        // Insert receipt
        $stmt = $conn->prepare("
            INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by, agent_id)
            VALUES (?, NOW(), ?, ?, ?, ?)
        ");
        $stmt->bind_param("idssi", $student_id, $amount_paid, $transaction_description, $user_id, $agent_id);
        $stmt->execute();
        $receipts_id = $stmt->insert_id;

        // Insert into combined_transactions
        $stmt = $conn->prepare("
            INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, agent_id, fund_id)
            VALUES ('plus', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isdsiiii", $student_id, $transaction_description, $amount_paid, $payment_method, $bank_id, $user_id, $agent_id, $fund_id);
        $stmt->execute();
        $transaction_id = $stmt->insert_id;

        // Link receipt with payment
        $stmt = $conn->prepare("
            INSERT INTO receipt_payments (receipt_id, transaction_id)
            VALUES (?, ?)
        ");
        $stmt->bind_param("ii", $receipts_id, $transaction_id);
        $stmt->execute();

        // Update fund or bank balance
        if ($payment_method === "نقدي") {
            $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
            $stmt_fund->bind_param("d", $amount_paid);
            $stmt_fund->execute();
            $stmt_fund->close();
        } elseif ($payment_method === "بنكي" && $bank_id) {
            $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
            $stmt_bank->bind_param("di", $amount_paid, $bank_id);
            $stmt_bank->execute();
            $stmt_bank->close();
        }

        mysqli_commit($conn);

        // Redirect with payment ID
        header("Location: student_receipt_re.php?receipt_id=" . $receipts_id);
        exit;
    } else {
        throw new Exception("No rows were updated during payment processing.");
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $stmt_count->close();
    $stmt_update->close();
    $conn->close();
}
?>
