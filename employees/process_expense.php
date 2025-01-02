<?php
include 'db_connection.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = [];
session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    exit;
}

$user_id = $_SESSION['userid'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $expenseAccountId = $_POST['expense_account_id'];
    $transactionDescription = $_POST['transaction_description'];
    $amount = $_POST['amount'];
    $paymentMethod = $_POST['payment_method'];
    $expense_account_name = $_POST['expense_account_name'];
    $all_des = "حساب : " . $expense_account_name . " -{ " . $transactionDescription . ' }-';

    // Handle bank ID, which should be null if the payment is "نقدي"
    $bankId = ($paymentMethod == 'بنكي' && !empty($_POST['bank'])) ? $_POST['bank'] : null;

    $bank_id = isset($_POST['bank']) && $_POST['bank'] !== '' ? $_POST['bank'] : null;

    // If no bank is provided, set a default fund_id value
    $fund_id = isset($_POST['bank']) && $_POST['bank'] !== '' ? null : 1;

    // Log received data for debugging
    error_log("Received Data: " . print_r($_POST, true));

    // Check if required fields are missing
    if (empty($expenseAccountId) || empty($transactionDescription) || empty($amount) || empty($paymentMethod)) {
        $response['success'] = false;
        $response['message'] = 'يرجى ملء جميع الحقول المطلوبة.';
        echo json_encode($response);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();






        $stmt = $conn->prepare("INSERT INTO receipts (receipt_date, total_amount, receipt_description, created_by)
        VALUES (NOW(), ?, ?, ?)
    ");
    $stmt->bind_param("sss", $amount, $all_des, $user_id);
    $stmt->execute();

    $receipts_id = $conn->insert_id;


            $stmt = $conn->prepare("
                INSERT INTO combined_transactions (type, description, paid_amount, payment_method, bank_id, user_id, fund_id)
                VALUES ('minus', ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("ssssss", $all_des, $amount, $payment_method, $bank_id, $user_id, $fund_id);
            
            $stmt->execute();

            // Retrieve the last inserted transaction ID
            $transaction_id = $conn->insert_id;

            // Insert into `receipt_payments`
            $stmt = $conn->prepare("
            INSERT INTO receipt_payments (receipt_id, transaction_id)
            VALUES (?, ?)
            ");
            $stmt->bind_param("ss", $receipts_id, $transaction_id);
            $stmt->execute();

            $conn->commit();








        if ($bankId !== null) {
            // Query for bank transactions (with bank_id)
            $stmt = $conn->prepare("INSERT INTO Expense_transaction (expense_account_id, transaction_description, amount, payment_method, bank_id) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                // Bind 5 parameters
                $stmt->bind_param('isdsd', $expenseAccountId, $all_des, $amount, $paymentMethod, $bankId);
                $stmt->execute();
                $stmt->close();
            } else {
                throw new Exception("Failed to prepare statement for bank transaction.");
            }

            // Insert into the transactions table with bank_id
            $stmt_reg_fee = $conn->prepare("INSERT INTO transactions (transaction_description, amount, transaction_type, bank_account_id, user_id) VALUES (?, ?, 'minus', ?, ?)");
            $stmt_reg_fee->bind_param("sdii", $all_des, $amount, $bankId, $user_id);
            $stmt_reg_fee->execute();
            $stmt_reg_fee->close();

        } else {
            // Query for cash transactions (without bank_id)
            $stmt = $conn->prepare("INSERT INTO Expense_transaction (expense_account_id, transaction_description, amount, payment_method) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                // Bind 4 parameters
                $stmt->bind_param('isds', $expenseAccountId, $all_des, $amount, $paymentMethod);
                $stmt->execute();
                $stmt->close();
            } else {
                throw new Exception("Failed to prepare statement for cash transaction.");
            }

            // Insert into the transactions table with fund_id set to '1'
            $stmt_reg_fee = $conn->prepare("INSERT INTO transactions (transaction_description, amount, transaction_type, fund_id, user_id) VALUES (?, ?, 'minus', '1', ?)");
            $stmt_reg_fee->bind_param("sdi", $all_des, $amount, $user_id);
            $stmt_reg_fee->execute();
            $stmt_reg_fee->close();
        }

        // Update the account balance in expense_accounts table
        $updateStmt = $conn->prepare("UPDATE expense_accounts SET account_balance = account_balance - ? WHERE id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param('di', $amount, $expenseAccountId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            throw new Exception("Failed to prepare statement for updating account balance.");
        }

        // Update fund or bank balance based on payment method
        if ($paymentMethod === 'نقدي') {
            $fundStmt = $conn->prepare("UPDATE funds SET balance = balance - ? WHERE id = 1");
            if ($fundStmt) {
                $fundStmt->bind_param('d', $amount);
                $fundStmt->execute();
                $fundStmt->close();
            } else {
                throw new Exception("Failed to update fund balance.");
            }
        } elseif ($paymentMethod === 'بنكي' && $bankId !== null) {
            $bankStmt = $conn->prepare("UPDATE bank_accounts SET balance = balance - ? WHERE account_id = ?");
            if ($bankStmt) {
                $bankStmt->bind_param('di', $amount, $bankId);
                $bankStmt->execute();
                $bankStmt->close();
            } else {
                throw new Exception("Failed to update bank balance.");
            }
        }

        // Commit transaction only if all operations are successful
        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'تم تسجيل العملية بنجاح';

    
        // Rollback transaction if something went wrong
        $conn->rollback();

    $conn->close();

    // Return the response as JSON
    echo json_encode($response);
}
?>
