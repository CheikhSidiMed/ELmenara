<?php
include '../db_connection.php';

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: ../home.php");
    exit;
}

$user_id = $_SESSION['userid'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $expenseAccountId = $_POST['garant_id'] ?? '';
    $months = $_POST['month'] ?? [];
    $des = $_POST['des'] ?? '';
    $paid_amount_des = floatval($_POST['paid_amount_des'] ?? 0);
    $transaction_type = $_POST['transaction_type'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? '';
    $account_name = $_POST['account_name'] ?? '';
    $name = $_POST['name'] ?? '';
    $amount = floatval($_POST['paid_amount'] ?? 0);
    $due_amount = floatval($_POST['remaining_amount'] ?? 0);

    if (empty($expenseAccountId) || empty($account_name) || empty($paymentMethod) || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة.']);
        exit;
    }

    $m_h = implode(', ', $months);
    $all_des = " الكافل(ة) " . $name . " { " . "حساب : " . $account_name . " -- " . $m_h . " - ". $des ." - }";
    
    $bankId = ($paymentMethod === 'بنكي' && !empty($_POST['bank'])) ? intval($_POST['bank']) : null;
    $fund_id = $bankId ? null : 1;

    $tot_amount = ( (float)$amount ?? 0) + ( (float)$paid_amount_des ?? 0);
    $conn->begin_transaction();

    try {
        // **1. Enregistrer le reçu**
        $stmt = $conn->prepare("INSERT INTO receipts (receipt_date, total_amount, receipt_description, created_by) VALUES (NOW(), ?, ?, ?)");
        $stmt->bind_param("dsi", $tot_amount, $all_des, $user_id);
        $stmt->execute();
        $receipts_id = $stmt->insert_id;
        $stmt->close();

        // **2. Enregistrer la transaction combinée**
        $stmt = $conn->prepare("INSERT INTO combined_transactions (type, description, paid_amount, payment_method, bank_id, user_id, fund_id) VALUES ('plus', ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssii", $all_des, $tot_amount, $paymentMethod, $bankId, $user_id, $fund_id);
        $stmt->execute();
        $transaction_id = $stmt->insert_id;
        $stmt->close();

        // **3. Lier le reçu et la transaction**
        $stmt = $conn->prepare("INSERT INTO receipt_payments (receipt_id, transaction_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $receipts_id, $transaction_id);
        $stmt->execute();
        $stmt->close();

        // **4. Ajouter les paiements mensuels**
        if (!empty($months)) {
            $stmt = $conn->prepare("INSERT INTO stock_monthly_payments (garant_id, month, amount_paid, due_amount, des) VALUES (?, ?, ?, ?, ?)");

            foreach ($months as $month) {
                $paid = $amount / count($months);
                $due_mount = $due_amount / count($months);
                $space = '';
                $stmt->bind_param("isdds", $expenseAccountId, $month, $paid, $due_mount, $space);
                $stmt->execute();
            }
    
            $stmt = $conn->prepare("UPDATE garants  SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $due_amount, $expenseAccountId);
            $stmt->execute();
   
        }
        if (!empty($des)) {
            $space = '';
            $mnt = 0.00;
            $stmt = $conn->prepare("INSERT INTO stock_monthly_payments (garant_id, month, amount_paid, due_amount, des) VALUES (?, ?, ?, ?, ?)");

            $stmt->bind_param("isdds", $expenseAccountId, $space, $paid_amount_des, $mnt, $des);
            $stmt->execute();
            $paid_amount_d =  $transaction_type === 'minus' ? -$paid_amount_des : $paid_amount_des;
            $stmt = $conn->prepare("UPDATE garants  SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $paid_amount_d, $expenseAccountId);
            $stmt->execute();
        }
        
        
        
        $stmt->close();

        // **5. Ajouter les transactions bancaires ou en espèces**
        if ($bankId !== null) {
            $stmt = $conn->prepare("INSERT INTO donate_transactions (donate_account_id, transaction_description, amount, payment_method, bank_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsi", $expenseAccountId, $all_des, $tot_amount, $paymentMethod, $bankId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO transactions (transaction_description, amount, transaction_type, bank_account_id, user_id) VALUES (?, ?, 'plus', ?, ?)");
            $stmt->bind_param("sdii", $all_des, $tot_amount, $bankId, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO donate_transactions (donate_account_id, transaction_description, amount, payment_method) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $expenseAccountId, $all_des, $tot_amount, $paymentMethod);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO transactions (transaction_description, amount, transaction_type, fund_id, user_id) VALUES (?, ?, 'plus', ?, ?)");
            $stmt->bind_param("sdii", $all_des, $tot_amount, $fund_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // **6. Mettre à jour le solde du compte de don**
        $stmt = $conn->prepare("UPDATE donate_accounts SET account_balance = account_balance + ? WHERE id = ?");
        $stmt->bind_param("di", $tot_amount, $expenseAccountId);
        $stmt->execute();
        $stmt->close();

        // **7. Mettre à jour le solde des fonds ou des banques**
        if ($paymentMethod === 'نقدي') {
            $stmt = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
            $stmt->bind_param("d", $tot_amount);
            $stmt->execute();
            $stmt->close();
        } elseif ($paymentMethod === 'بنكي' && $bankId !== null) {
            $stmt = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
            $stmt->bind_param("di", $tot_amount, $bankId);
            $stmt->execute();
            $stmt->close();
        }

        // **Validation et redirection**
        $conn->commit();
        header("Location: garant_donation_receipt.php?receipt_id=" . $receipts_id . "&due_amount=" . $due_amount . "&garant_id=" . $expenseAccountId);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Une erreur est survenue : ' . $e->getMessage()]);
        exit();
    }
}

$conn->close();
?>
