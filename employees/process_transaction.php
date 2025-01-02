<?php
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();  // Ensure the session is started

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    die("Error: User is not logged in.");
}

// Retrieve the connected user ID from the session
$user_id = $_SESSION['userid'];

// Check if the request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $transaction_type = $_POST['transaction_type'] ?? null;
    $employee_id = $_POST['employee_id'] ?? null; 
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';
    $bank_id = isset($_POST['bank']) && $_POST['bank'] !== '' ? $_POST['bank'] : null;

    // If no bank is provided, set a default fund_id value
    $fund_id = isset($_POST['bank']) && $_POST['bank'] !== '' ? null : 1;
    $transaction_description = $_POST['transaction_description'] ?? '';
    // Fetch the employee's full name based on employee_id
    $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $employee_name = $employee ? $employee['full_name'] : '';

    // Sanitize transaction_description to remove unwanted newlines
    $transaction_description = str_replace(["\r", "\n"], '', $_POST['transaction_description'] ?? '');

    $tr_ption =  ' الموظف(ة): ' . $employee_name . ',  ' . $transaction_description;

    // Insert data into receipts
    $stmt = $conn->prepare("INSERT INTO receipts (receipt_date, total_amount, receipt_description, created_by)
        VALUES (NOW(), ?, ?, ?)");
    $stmt->bind_param("sss", $amount, $tr_ption, $user_id);
    $stmt->execute();

    $receipts_id = $conn->insert_id;

    $stmt = $conn->prepare("
        INSERT INTO combined_transactions (employee_id, type, description, paid_amount, payment_method, bank_id, user_id, fund_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isssssss", $employee_id, $transaction_type, $tr_ption, $amount, $payment_method, $bank_id, $user_id, $fund_id);
    
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



    // Begin transaction for data integrity
    $conn->begin_transaction();
    try {
        $fund_id = null;
        $bank_account_id = null;
    
        // Adjust balances based on transaction type
        if ($transaction_type === 'minus' && $amount > 0) {
            // Deduct from fund or bank balance
            if ($payment_method === 'نقدي') {
                $sql = "UPDATE funds SET balance = balance - ? WHERE id = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('d', $amount);
                $stmt->execute();
                $fund_id = 1;
            } elseif ($payment_method === 'بنكي' && $bank_id) {
                $sql = "UPDATE bank_accounts SET balance = balance - ? WHERE account_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('di', $amount, $bank_id);
                $stmt->execute();
                $bank_account_id = $bank_id;
            }
    
            // Increase employee debt
            $sql = "UPDATE employees SET balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('di', $amount, $employee_id);
            $stmt->execute();
        } elseif ($transaction_type === 'plus' && $amount > 0) {
            // Add to fund or bank balance
            if ($payment_method === 'نقدي') {
                $sql = "UPDATE funds SET balance = balance + ? WHERE id = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('d', $amount);
                $stmt->execute();
                $fund_id = 1;
            } elseif ($payment_method === 'بنكي' && $bank_id) {
                $sql = "UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('di', $amount, $bank_id);
                $stmt->execute();
                $bank_account_id = $bank_id;
            }
    
            // Decrease employee debt or increase credit
            $sql = "UPDATE employees SET balance = balance - ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('di', $amount, $employee_id);
            $stmt->execute();
        }
    
        // Insert transaction record
        $sql = "INSERT INTO transactions (transaction_description, amount, transaction_type, fund_id, bank_account_id, employee_id, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sdsiiii', $tr_ption, $amount, $transaction_type, $fund_id, $bank_account_id, $employee_id, $user_id);
    
        $stmt->execute();
    
        // Commit transaction  
        $conn->commit();
    
        header("Location: operation.php?status=success&message=" . urlencode("تمت العملية بنجاح"));
        exit();
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
    
        header("Location: operation.php?status=error&message=" . urlencode("حدث خطأ: " . $e->getMessage()));
        exit();
    } finally {
        $conn->close();
    }
}    

