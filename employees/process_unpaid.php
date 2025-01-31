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



$user_id = $_SESSION['userid'];
$student_id = $_POST['id'] ?? null;
$months = htmlspecialchars($_POST['stu_months'] ?? '', ENT_QUOTES, 'UTF-8');
$amount_paid = floatval($_POST['amount_paid'] ?? 0);
$payment_method = $_POST['payment_method'] ?? null;
$bank_id = ($payment_method === "بنكي") ? intval($_POST['bank'] ?? 0) : null;
$fund_id = ($payment_method === "نقدي") ? 1 : null;

// Validate required fields
if (empty($student_id) || $amount_paid <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data. Please fill all fields.']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Check unpaid rows for the student
    $query_count = "
        SELECT COUNT(*) AS row_count, SUM(remaining_amount) AS total_remaining, student_name 
        FROM months_not_paid 
        WHERE student_id = ? AND remaining_amount > 0";
    $stmt_count = $conn->prepare($query_count);
    $stmt_count->bind_param("i", $student_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row = $result_count->fetch_assoc();
    $row_count = $row['row_count'] ?? 0;
    $total_remaining = $row['total_remaining'] ?? 0;
    $student_name = $row['student_name'] ?? 'Unknown Student';

    if ($row_count == 0) {
        echo json_encode(['status' => 'error', 'message' => 'No arrears found for this student.']);
        mysqli_rollback($conn);
        exit;
    }

    // 2. Distribute the paid amount across rows
    $amount_per_row = $amount_paid / $row_count;

    $query_update = "
        UPDATE months_not_paid 
        SET remaining_amount = GREATEST(remaining_amount - ?, 0.00) 
        WHERE student_id = ? AND remaining_amount > 0";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param("di", $amount_per_row, $student_id);
    $stmt_update->execute();

    // 3. Remove rows with no remaining amount
    $query_delete = "DELETE FROM months_not_paid WHERE student_id = ? AND remaining_amount = 0.00";
    $stmt_delete = $conn->prepare($query_delete);
    $stmt_delete->bind_param("i", $student_id);
    $stmt_delete->execute();

    // Transaction description
    $transaction_description = "تسديد متأخرات " . '{ ' . $months . ' } ' . $student_name;

    // 4. Insert into receipts table
    $query_receipt = "
        INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by)
        VALUES (?, NOW(), ?, ?, ?)";
    $stmt_receipt = $conn->prepare($query_receipt);
    $stmt_receipt->bind_param("idsi", $student_id, $amount_paid, $transaction_description, $user_id);
    $stmt_receipt->execute();
    $receipt_id = $conn->insert_id;

    // 5. Insert into combined_transactions table
    $query_combined = "
        INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, fund_id)
        VALUES ('plus', ?, ?, ?, ?, ?, ?, ?)";
    $stmt_combined = $conn->prepare($query_combined);
    $stmt_combined->bind_param("ssdssii", $student_id, $transaction_description, $amount_paid, $payment_method, $bank_id, $user_id, $fund_id);
    $stmt_combined->execute();
    $transaction_id = $conn->insert_id;

    // 5. Insert into combined_transactions table
    // $query_trans = "
    //     INSERT INTO transactions (transaction_type, student_id, transaction_description, amount, bank_account_id, user_id, fund_id)
    //     VALUES ('plus', ?, ?, ?, ?, ?, ?)";
    // $stmt_trans = $conn->prepare($query_trans);
    // $stmt_trans->bind_param("ssdsii", $student_id, $transaction_description, $amount_paid, $bank_id, $user_id, $fund_id);
    // $stmt_trans->execute();

    // 6. Link receipts and transactions
    $query_receipt_payment = "
        INSERT INTO receipt_payments (receipt_id, transaction_id)
        VALUES (?, ?)";
    $stmt_receipt_payment = $conn->prepare($query_receipt_payment);
    $stmt_receipt_payment->bind_param("ii", $receipt_id, $transaction_id);
    $stmt_receipt_payment->execute();

    // Update balances
    if ($payment_method === "نقدي") {
        $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
        $stmt_fund->bind_param("d", $amount_paid);
        $stmt_fund->execute();
    } elseif ($payment_method === "بنكي" && $bank_id) {
        $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
        $stmt_bank->bind_param("di", $amount_paid, $bank_id);
        $stmt_bank->execute();
    }

    $conn->commit();

    // Redirect to receipt page
    header("Location: student_receipt_re.php?receipt_id=" . urlencode($receipt_id));
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $stmt_count->close();
    $stmt_update->close();
    $stmt_delete->close();
    $stmt_receipt->close();
    $stmt_combined->close();
    $stmt_receipt_payment->close();
    $conn->close();
}
