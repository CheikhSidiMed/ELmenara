<?php
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

$receipts_id = '';
$type = 'plus';
$amount_paid = $_POST['amount_paidd'] ?? null;
$agent_id = $_POST['agent_id'] ?? null;
$payment_method = $_POST['payment_methodd'] ?? null;
$bank_id = ($payment_method === "بنكي") ? $_POST['bank'] : null;
$fund_id = ($payment_method === "نقدي") ? 1 : null;

if (empty($agent_id) || empty($amount_paid) || empty($payment_method)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data. Please fill all fields.']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    // Get agent details
    $agent = selectAgent($conn, $agent_id);
    $agent_name = $agent['agent_name'] ?? 'Unknown';
    $agent_phone = $agent['phone'] ?? 'Unknown';

    // Query to select students with remaining balance
    $query_count = "SELECT s.id, s.student_name
                    FROM students
                    WHERE s.agent_id = ?
                      AND s.remaining > 0.00";
    $stmt_count = $conn->prepare($query_count);
    $stmt_count->bind_param("i", $agent_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();

    if ($result_count->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'No arrears found for this agent.']);
        mysqli_rollback($conn);
        exit();
    }

    $result_array = $result_count->fetch_all(MYSQLI_ASSOC);
    $rem_pay = $amount_paid;

    // Insert initial receipt
    $description = "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " سدد(ت) متأخرات ";
    $receipts_id = insertReceipts($conn, $rem_pay, $description, $user_id, $agent_id);

    foreach ($result_array as $row) {
        $student_id = $row['id'];
        $student_name = $row['student_name'];

        // Calculate total remaining and count unpaid months
        $check_stmt = $conn->prepare("SELECT SUM(remaining_amount) AS total_remaining, COUNT(*) AS count
                                      FROM payments
                                      WHERE student_id = ? AND remaining_amount > 0.00");
        $check_stmt->bind_param("i", $student_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        $total_rem = $result['total_remaining'] ?? 0;
        $count = $result['count'] ?? 0;
        $check_stmt->close();

        if ($count === 0 || $total_rem <= 0) {
            continue;
        }

        // Calculate amounts to pay
        $am_t = min($rem_pay, $total_rem);
        $amount_per_row = $am_t / $count;

        // Update payments and perform transactions
        if (updatePayment($conn, $amount_per_row, $student_id)) {
            $description_student = "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " سدد(ت) متأخرات " . " لطالب(ة): " . $student_name;
            $transaction_id = insertComTransaction($conn, $type, $student_id, 0.00, $am_t, 0.00, $description_student, $payment_method, $bank_id, $user_id, $agent_id, $fund_id);

            // Link receipt and transaction
            insertReceiptPay($conn, $receipts_id, $transaction_id);
            insertTransaction($conn, $type, $student_id, $description_student, $am_t, $bank_id, $user_id, $agent_id, $fund_id);

            // Update balance for the bank or fund
            updateBalance($conn, $payment_method, $am_t, $bank_id);

            // Deduct the remaining amount
            $rem_pay -= $am_t;

            if ($rem_pay <= 0) {
                break;
            }
        } else {
            throw new Exception("No rows were updated during payment processing for student ID: $student_id.");
        }
    }

    mysqli_commit($conn);
    header("Location: agent_receipt_re.php?payment_id=" . urlencode($receipts_id) . "&agent_name=" . urlencode($agent_name));
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $stmt_count->close();
    $conn->close();
}


function insertTransaction($conn, $type, $student_id, $description, $amount, $bank_id, $user_id, $agent_id, $fund_id) {
    $tran = $conn->prepare("INSERT INTO transactions (transaction_description, amount, transaction_type, fund_id, bank_account_id, student_id, agent_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $tran->bind_param("ssssssss", $description, $amount, $type, $fund_id, $bank_id, $student_id, $agent_id, $user_id);
    $tran->execute();
}

function insertReceiptPay($conn, $receipts_id, $transaction_id){
    $stmt = $conn->prepare("INSERT INTO receipt_payments (receipt_id, transaction_id) VALUES (?, ?)");
    $stmt->bind_param("ss", $receipts_id, $transaction_id);
    $stmt->execute();
}

function insertComTransaction($conn, $type, $student_id, $due_amount, $paid_amount, $remaining_amount, $description, $payment_method, $bank_id, $user_id, $agent_id, $fund_id) {
    $stmt = $conn->prepare("INSERT INTO combined_transactions (type, student_id, due_amount, paid_amount, remaining_amount, description, payment_method, bank_id, user_id, agent_id, fund_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $type, $student_id, $due_amount, $paid_amount, $remaining_amount, $description, $payment_method, $bank_id, $user_id, $agent_id, $fund_id);
    if (!$stmt) { throw new Exception("Prepare failed: " . $conn->error);}
    $stmt->execute();
    if (!$stmt->execute()) { throw new Exception("Execute failed: " . $stmt->error);}
    $insert_id = $stmt->insert_id;
    $stmt->close();

    return $insert_id;
}

function updateBalance($conn, $payment_method, $amount, $bank_id) {
    if ($payment_method === "نقدي") {
        $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
        $stmt_fund->bind_param("d", $amount);
        $stmt_fund->execute();
    } elseif ($payment_method === "بنكي" && $bank_id) {
        $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
        $stmt_bank->bind_param("di", $amount, $bank_id);
        $stmt_bank->execute();
    }
}

function insertReceipts($conn, $tot_paid, $description, $user_id, $agent_id) {
    $recp = $conn->prepare("INSERT INTO receipts (receipt_date, total_amount, receipt_description, created_by, agent_id) VALUES (NOW(), ?, ?, ?, ?)");
    $recp->bind_param("ssss", $tot_paid, $description, $user_id, $agent_id);
    if (!$recp) { throw new Exception("Prepare failed: " . $conn->error);}
    $recp->execute();
    if (!$recp->execute()) { throw new Exception("Execute failed: " . $recp->error);}
    $insert_id = $recp->insert_id;
    $recp->close();

    return $insert_id;
}

function selectAgent($conn, $agent_id){
    $stmt_agent = $conn->prepare("SELECT agent_name, phone FROM agents WHERE agent_id = ?");
    $stmt_agent->bind_param("i", $agent_id);
    $stmt_agent->execute();
    $result = $stmt_agent->get_result();
    $agent = $result->fetch_assoc();
    $stmt_agent->close();

    return $agent;
}

function updatePayment($conn, $amount_per_row, $student_id){
    $query_update = "UPDATE payments SET remaining_amount = remaining_amount - ? WHERE student_id = ? AND remaining_amount != 0.00";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param("di", $amount_per_row, $student_id);
    $stmt_update->execute();
    return $stmt_update->affected_rows > 0;
}

function updateComnedTran($conn, $amount_per_row_ct, $student_id){
    $query = "UPDATE combined_transactions SET remaining_amount = remaining_amount - ? WHERE student_id = ? AND remaining_amount != 0.00";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("di", $amount_per_row_ct, $student_id);
    $stmt->execute();
    return $stmt->affected_rows;
}

?>
