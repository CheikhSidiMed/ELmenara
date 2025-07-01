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
    
    

    $stmt = $conn->prepare("INSERT INTO receipts (receipt_date, total_amount, receipt_description, created_by)
            VALUES (NOW(), ?, ?, ?)
        ");
        $stmt->bind_param("sss", $amount, $all_des, $user_id);
        $stmt->execute();

        $receipts_id = $conn->insert_id;


        $stmt = $conn->prepare("
            INSERT INTO combined_transactions (type, description, paid_amount, payment_method, bank_id, user_id, fund_id)
            VALUES ('plus', ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("ssssss", $all_des, $amount, $payment_method, $bankId, $user_id, $fund_id);
        
        $stmt->execute();

        // Retrieve the last inserted transaction ID
        $transaction_id = $conn->insert_id;

        // Insert into `receipt_payments`
        $stmt = $conn->prepare("INSERT INTO receipt_payments (receipt_id, transaction_id)
        VALUES (?, ?)
        ");
        $stmt->bind_param("ss", $receipts_id, $transaction_id);
        $stmt->execute();

        $conn->commit();




        if ($bankId !== null) {
            $stmt_expense_bank = $conn->prepare("INSERT INTO donate_transactions (donate_account_id, transaction_description, amount, payment_method, bank_id) VALUES (?, ?, ?, ?, ?)");
            $stmt_expense_bank->bind_param('isdsd', $expenseAccountId, $all_des, $amount, $paymentMethod, $bankId);
            $stmt_expense_bank->execute();
            $stmt_expense_bank->close();

            $stmt_transaction_bank = $conn->prepare("INSERT INTO transactions (transaction_description, amount, transaction_type, bank_account_id, user_id) VALUES (?, ?, 'plus', ?, ?)");
            $stmt_transaction_bank->bind_param("sdii", $all_des, $amount, $bankId, $user_id);
            $stmt_transaction_bank->execute();
            $payment_ids = $stmt_transaction_bank->insert_id;
            $stmt_transaction_bank->close();
        } else {
            $stmt_expense_cash = $conn->prepare("INSERT INTO donate_transactions (donate_account_id, transaction_description, amount, payment_method) VALUES (?, ?, ?, ?)");
            $stmt_expense_cash->bind_param('isds', $expenseAccountId, $all_des, $amount, $paymentMethod);
            $stmt_expense_cash->execute();
            $stmt_expense_cash->close();

            $stmt_transaction_cash = $conn->prepare("INSERT INTO transactions (transaction_description, amount, transaction_type, fund_id, user_id) VALUES (?, ?, 'plus', '1', ?)");
            $stmt_transaction_cash->bind_param("sdi", $all_des, $amount, $user_id);
            $stmt_transaction_cash->execute();
            $stmt_transaction_cash->close();
        }

        $stmt_update_account = $conn->prepare("UPDATE donate_accounts SET account_balance = account_balance + ? WHERE id = ?");
        $stmt_update_account->bind_param('di', $amount, $expenseAccountId);
        $stmt_update_account->execute();
        $stmt_update_account->close();

        if ($paymentMethod === 'نقدي') {
            $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
            $stmt_fund->bind_param('d', $amount);
            $stmt_fund->execute();
            $stmt_fund->close();
        } elseif ($paymentMethod === 'بنكي' && $bankId !== null) {
            $stmt_bank_balance = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
            $stmt_bank_balance->bind_param('di', $amount, $bankId);
            $stmt_bank_balance->execute();
            $stmt_bank_balance->close();
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'تم تسجيل العملية بنجاح';

        // $payment_ids_str = implode(',', $payment_ids);
        header("Location: donation_receipt.php?receipt_id=" . $receipts_id);
        exit();


    $conn->close();
}
?>
