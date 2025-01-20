<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include 'db_connection.php';
$conn->set_charset("utf8mb4");
$response = array('success' => false, 'message' => ''); // Initialize response array
session_start();  // Ensure the session is started

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    die("Error: User is not logged in.");
}

// Retrieve the connected user ID from the session
$user_id = $_SESSION['userid'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    // Retrieve form data
    $agent_id = $_POST['id'];
    $student_id = $_POST['student_id'];
    $paid_amount = $_POST['paid_amount'];
    $remaining_amount = $_POST['remaining_amount'];
    $payment_method = $_POST['payment_method'];
    $bank_id = isset($_POST['bank']) && !empty($_POST['bank']) ? $_POST['bank'] : null;
    $months = $_POST['months'];
    $due_amount = $_POST['due_amount'];
    $payment_date = date("Y-m-d H:i:s");

    $registuration_fee = $_POST['registuration_fee'] ?? null;
    $description_p = $_POST['description_p'] ?? null;
    $p_paid = $_POST['p_paid'] ?? null;
    $des_ins = '';
    $type = 'plus';

    $fund_d = null;
    $bank_accoun_id = null;
    if ($payment_method === "نقدي") {
        $fund_d = 1;
    } elseif ($payment_method === "بنكي" && $bank_id) {
        $bank_accoun_id = $bank_id;
    }

    $stmt_student = $conn->prepare("SELECT student_name FROM students WHERE id = ?");
    $stmt_student->bind_param("i", $student_id);
    $stmt_student->execute();
    $student_name = $stmt_student->get_result()->fetch_assoc()['student_name'];
    $stmt_student->close();

    $stmt_agent = $conn->prepare("SELECT agent_name, phone FROM agents WHERE agent_id = ?");
    $stmt_agent->bind_param("i", $agent_id);
    $stmt_agent->execute();
    $result = $stmt_agent->get_result();
    $agent = $result->fetch_assoc();
    $agent_name = $agent['agent_name'];
    $agent_phone = $agent['phone'];
    $stmt_agent->close();

    if (empty($months) || empty($due_amount) || empty($paid_amount)) {
        
        if ($registuration_fee || $description_p) {
            
            $to_t_paid = ( (float)$p_paid ?? 0) + ( (float)$registuration_fee ?? 0);

            $des_1 = $registuration_fee ? 'رسوم تسجيل لطالبة ' : '';
            $des_2 = $p_paid ?  ", { " . $description_p . " } " : '';
            $descr_ion = "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) " . "{ " . $des_1 . $des_2 . " }" . ' لطالب(ة) ' . ': ' . ($student_name);
            

            $receipts_id = insertReceipts($conn, $student_id, $to_t_paid, $descr_ion, $user_id, $agent_id);

            if ($registuration_fee) {
                $des_ins =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) " . " رسوم تسجيل لطالب(ة)  " . ': ' . ($student_name) ;
                processTransaction($conn, $type, $student_id, $des_ins, $registuration_fee, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d, $receipts_id);
            }

            if ($p_paid) {
                $description =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . "دفع(ت) " . "{ " . $description_p . " }" . 'لطالب(ة): ' . ($student_name);
                processTransaction($conn, $type, $student_id, $description, $p_paid, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d, $receipts_id);
            }
            header("Location: agent_receipt.php?payment_ids=" . $receipts_id);

            exit();
        } else {
            $response = ['success' => false, 'message' => 'يرجى ملء الحقول المطلوبة للدفع أو إدخال رسوم تسجيل أو وصف للدفع.'];
        }
    } else {

        $tot_paid = (float)$paid_amount + ( (float)$p_paid ?? 0) + ( (float)$registuration_fee ?? 0);

        $m_h = implode(', ', $months);
        $description =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) الأشهر " . "{ " . $m_h . " }" . 'لطالب(ة): ' . ($student_name) ;

        $to_t_paid = (float)$paid_amount + ( (float)$p_paid ?? 0) + ( (float)$registuration_fee ?? 0);

        $m_h = implode(', ', $months);
        $des_1 = $registuration_fee ? 'ورسوم تسجيل ' : '';
        $des_2 = $p_paid ? 'و ' . "{ " . $description_p . " } " : '';
        $descr_ion = "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') '
            . " دفع(ت) الأشهر " . "{ " . $m_h . " }, "
            . $des_1 . $des_2 . ' للطلب(ة) ' . ': ' . ($student_name);

        $receipts_id = insertReceipts($conn, $student_id, $to_t_paid, $descr_ion, $user_id, $agent_id);
    
        if ($registuration_fee) {
            $des_ins =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) " . " رسوم تسجيل لطالب(ة)  " . ': ' . ($student_name);
            processTransaction($conn, $type, $student_id, $des_ins, $registuration_fee, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d, $receipts_id);
        }

        if ($p_paid) {
            $description =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') '. " دفع(ت)  " . "{ " . $description_p . " }" . 'لطالب(ة): ' . ($student_name);
            processTransaction($conn, $type, $student_id, $description, $p_paid, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d, $receipts_id);
        }

        $des_tion =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) الأشهر " . "{ " . $m_h . " }" . 'لطالب(ة): ' . ($student_name) ;


        $transaction_id = insertComTransaction($conn, $m_h, $type, $student_id, $due_amount, $paid_amount, $remaining_amount, $des_tion, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
        insertReceiptPay($conn, $receipts_id, $transaction_id);
        insertTransaction($conn, $type, $student_id, $des_tion, $paid_amount, $bank_accoun_id, $user_id, $agent_id, $fund_d);
        updateBalance($conn, $payment_method, $paid_amount, $bank_id);

        $conn->begin_transaction();
        $success = true;
        $payment_ids = [];

        // Loop through months to insert separate entries
        foreach ($months as $month) {
            $amount_per_month = $due_amount / count($months);
            $paid_amount_per_month = $paid_amount / count($months);
            $remaining_amount_per_month = $remaining_amount / count($months);

            $stmt = $conn->prepare("INSERT INTO payments (agent_id, student_id, month, due_amount, paid_amount, remaining_amount, bank_id, payment_date, payment_method, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param(
                "iisdddissi",
                $agent_id,
                $student_id,
                $month,
                $amount_per_month,
                $paid_amount_per_month,
                $remaining_amount_per_month,
                $bank_id,
                $payment_date,
                $payment_method,
                $user_id
            );
            

            if ($stmt->execute()) {
                $payment_ids[] = $stmt->insert_id;
            } else {
                $response['message'] = 'Error: ' . $stmt->error;
                $success = false;
                break;
            }

            $stmt->close();
        }

        if ($success) {
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Payment inserted successfully.';
            
            header("Location: agent_receipt.php?payment_ids=" . $receipts_id);
            exit();
        } else {
            $conn->rollback(); // Rollback on failure
            $response['message'] = 'Transaction failed, rolled back.';
        }
    }

}

$conn->close();


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

function insertComTransaction($conn, $months, $type, $student_id, $due_amount, $paid_amount, $remaining_amount, $description, $payment_method, $bank_id, $user_id, $agent_id, $fund_id) {
    $stmt = $conn->prepare("INSERT INTO combined_transactions (month, type, student_id, due_amount, paid_amount, remaining_amount, description, payment_method, bank_id, user_id, agent_id, fund_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssss", $months, $type, $student_id, $due_amount, $paid_amount, $remaining_amount, $description, $payment_method, $bank_id, $user_id, $agent_id, $fund_id);
    if (!$stmt) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt->bind_param("ssssssssssss", $months, $type, $student_id, $due_amount, $paid_amount, $remaining_amount, $description, $payment_method, $bank_id, $user_id, $agent_id, $fund_id);
    if (!$stmt->execute()) { throw new Exception("Execute failed: " . $stmt->error); }
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

function insertReceipts($conn, $student_id, $tot_paid, $description, $user_id, $agent_id) {
    $recp = $conn->prepare("INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by, agent_id) VALUES (?, NOW(), ?, ?, ?, ?)");
    if (!$recp) { throw new Exception("Prepare failed: " . $conn->error);}
    $recp->bind_param("sssss", $student_id,  $tot_paid, $description, $user_id, $agent_id);
    if (!$recp->execute()) { throw new Exception("Execute failed: " . $recp->error);}
    $insert_id = $recp->insert_id;
    $recp->close();

    return $insert_id;
}

function processTransaction($conn, $type, $student_id, $description, $amount, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d, $receipts_id) {
    $transaction_id = insertComTransaction($conn, '', $type, $student_id, 0.00, $amount, 0.00, $description, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
    insertReceiptPay($conn, $receipts_id, $transaction_id);
    insertTransaction($conn, $type, $student_id, $description, $amount, $bank_accoun_id, $user_id, $agent_id, $fund_d);
    updateBalance($conn, $payment_method, $amount, $bank_id);
}



?>
