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
$payment_ids = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $expenseAccountId = $_POST['expense_account_id'];
    $date_time = $_POST['date_time'].':00';
    $transaction_type = $_POST['transaction_type'] ?? null;

    $transactionDescription = $_POST['transaction_description'];
    $amount = $_POST['amount'];
    $paymentMethod = $_POST['payment_method'] ?? '';
    $expense_account_name = $_POST['expense_account_name'];
    $all_des = "حساب : " . $expense_account_name . " -- " . $transactionDescription;

    $bankId = ($paymentMethod == 'بنكي' && !empty($_POST['bank'])) ? $_POST['bank'] : null;
    $fund_id = isset($_POST['bank']) && $_POST['bank'] !== '' ? null : 1;

    error_log("Received Data: " . print_r($_POST, true));

    if (empty($expenseAccountId) || empty($transactionDescription) || empty($amount) || empty($paymentMethod)) {
        $response['success'] = false;
        $response['message'] = 'يرجى ملء جميع الحقول المطلوبة.';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    
    

    $stmt = $conn->prepare("INSERT INTO receipts (receipt_date, total_amount, receipt_description, created_by) VALUES (?, ?, ?, ?) ");
        $stmt->bind_param("ssss", $date_time, $amount, $all_des, $user_id);
        $stmt->execute();

        $receipts_id = $conn->insert_id;


        $stmt = $conn->prepare("
            INSERT INTO combined_transactions (date, type, description, paid_amount, payment_method, bank_id, user_id, fund_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("ssssssss", $date_time, $transaction_type, $all_des, $amount, $payment_method, $bankId, $user_id, $fund_id);
        
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
            $stmt_expense_bank = $conn->prepare("INSERT INTO offer_transactions (transaction_date, account_id, transaction_description, amount, payment_method, bank_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_expense_bank->bind_param('sisdsd', $date_time, $expenseAccountId, $all_des, $amount, $paymentMethod, $bankId);
            $stmt_expense_bank->execute();
            $stmt_expense_bank->close();

            $stmt_transaction_bank = $conn->prepare("INSERT INTO transactions (transaction_date, transaction_description, amount, transaction_type, bank_account_id, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_transaction_bank->bind_param("ssdsii", $date_time, $all_des, $amount, $transaction_type, $bankId, $user_id);
            $stmt_transaction_bank->execute();
            $payment_ids = $stmt_transaction_bank->insert_id;
            $stmt_transaction_bank->close();
        } else {
            $stmt_expense_cash = $conn->prepare("INSERT INTO offer_transactions (transaction_date, account_id, transaction_description, amount, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt_expense_cash->bind_param('sisds', $date_time, $expenseAccountId, $all_des, $amount, $paymentMethod);
            $stmt_expense_cash->execute();
            $stmt_expense_cash->close();

            $stmt_transaction_cash = $conn->prepare("INSERT INTO transactions (transaction_date, transaction_description, amount, transaction_type, fund_id, user_id) VALUES (?, ?, ?, ?, '1', ?)");
            $stmt_transaction_cash->bind_param("ssdsi", $date_time, $all_des, $amount, $transaction_type, $user_id);
            $stmt_transaction_cash->execute();
            $stmt_transaction_cash->close();
        }

        if ($transaction_type === 'minus' && $amount > 0) {
            if ($payment_method === 'نقدي') {
                $sql = "UPDATE funds SET balance = balance - ? WHERE id = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('d', $amount);
                $stmt->execute();
                $fund_id = 1;
            } elseif ($payment_method === 'بنكي' && $bankId) {
                $sql = "UPDATE bank_accounts SET balance = balance - ? WHERE account_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('di', $amount, $bankId);
                $stmt->execute();
                $bank_account_id = $bankId;
            }
    
            // Increase employee debt
            $sql = "UPDATE offer_accounts SET account_balance = account_balance + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('di', $amount, $expenseAccountId);
            $stmt->execute();
        } elseif ($transaction_type === 'plus' && $amount > 0) {
            // Add to fund or bank balance
            if ($payment_method === 'نقدي') {
                $sql = "UPDATE funds SET balance = balance + ? WHERE id = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('d', $amount);
                $stmt->execute();
                $fund_id = 1;
            } elseif ($payment_method === 'بنكي' && $bankId) {
                $sql = "UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('di', $amount, $bankId);
                $stmt->execute();
                $bank_account_id = $bankId;
            }
    
            // Decrease employee debt or increase credit
            $sql = "UPDATE offer_accounts SET account_balance = account_balance - ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('di', $amount, $expenseAccountId);
            $stmt->execute();
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'تم تسجيل العملية بنجاح';

        header("Location: offer_receipt.php?receipt_id=" . $receipts_id);
        exit();


    $conn->close();
}
?>
