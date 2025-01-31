<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';
$conn->set_charset("utf8mb4");
$response = array('success' => false, 'message' => '');
s
session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}



$user_id = $_SESSION['userid'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $agent_id = $_POST['id'];
    $paid_amount = $_POST['paid_amount'];
    $remaining_amount = $_POST['remaining_amount'];
    $payment_method = $_POST['payment_method'];
    $bank_id = isset($_POST['bank']) && !empty($_POST['bank']) ? $_POST['bank'] : null;
    $months = $_POST['monthss'];
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

    $stmt_agent = $conn->prepare("SELECT agent_name, phone FROM agents WHERE agent_id = ?");
    $stmt_agent->bind_param("i", $agent_id);
    $stmt_agent->execute();
    $result = $stmt_agent->get_result();
    $agent = $result->fetch_assoc();
    $agent_name = $agent['agent_name'];
    $agent_phone = $agent['phone'];
    $stmt_agent->close();

    if (empty($months) || empty($due_amount) || empty($paid_amount)) {
        $to_t_paid = ( (float)$p_paid ?? 0) + ( (float)$registuration_fee ?? 0);

        $des_1 = !((float)$registuration_fee) ?? 'رسوم تسجيل لطالبة ';
        $des_2 = !((float)$p_paid) ?? 'و ' . "{ " . $description_p . " } ";
        $descr_ion = "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) " . "{ " . $des_1 . $des_2 . " }" . ' لطالبة ';
        
        $receipts_id = insertReceipts($conn, $to_t_paid, $descr_ion, $user_id, $agent_id);
        
        $sql_students = "SELECT id, student_name FROM students WHERE agent_id = ?";
        $stmt_students = $conn->prepare($sql_students);
        $stmt_students->bind_param("i", $agent_id);
        $stmt_students->execute();
        $result_students = $stmt_students->get_result();
        
        if ($result_students->num_rows > 0) {
            $student_count = $result_students->num_rows;
            $tot_paid = (( (float)$p_paid ?? 0) + ( (float)$registuration_fee ?? 0)) / $student_count;
        
            // Process each student
            while ($row = $result_students->fetch_assoc()) {
                $student_name = $row['student_name'];
                $student_id = $row['id'];
        
                // Process registration fee
                if ((float)$registuration_fee) {
                    $reg_fee = (float) $registuration_fee / $student_count;
                    $des_ins = "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) " . " رسوم تسجيل لطالب(ة)  " . ': ' . ($student_name);
                    processTransaction($conn, $type, $student_id, $des_ins, $reg_fee, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d, $receipts_id);
                }
        
                // Process payment
                if ((float)$p_paid) {
                    $p_paidd = (float) $p_paid / $student_count;
                    $description = "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . "دفع(ت) " . "{ " . $description_p . " }" . 'لطالب(ة): ' . ($student_name);
                    processTransaction($conn, $type, $student_id, $description, $p_paidd, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d, $receipts_id);
                }
            }
        
            header("Location: agent_receipt.php?payment_ids=" . $receipts_id);
            exit();
        } else {
         $response = ['success' => false, 'message' => 'يرجى ملء الحقول المطلوبة للدفع أو إدخال رسوم تسجيل أو وصف للدفع.'];
        }
    } else {

        $to_t_paid = (float)$paid_amount + ( (float)$p_paid ?? 0) + ( (float)$registuration_fee ?? 0);

        $m_h = implode(', ', $months);
        $des_1 = $registuration_fee ? 'ورسوم تسجيل ' : '';
        $des_2 = $p_paid ? 'و ' . "{ " . $description_p . " } " : '';
        $descr_ion = "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') '
            . " دفع(ت) الأشهر " . "{ " . $m_h . " }, "
            . $des_1 . $des_2 . 'لطلبة ';

        $receipts_id = insertReceipts($conn, $to_t_paid, $descr_ion, $user_id, $agent_id);

        $sql_students = "SELECT id, student_name, remaining FROM students WHERE agent_id = ? AND remaining > 0.00";
        $stmt_students = $conn->prepare($sql_students);
        $stmt_students->bind_param("i", $agent_id);
        $stmt_students->execute();
        $result_students = $stmt_students->get_result();
    
        if ($result_students->num_rows > 0) {
            $student_count = $result_students->num_rows;
            $tot_paid = ((float)$paid_amount + ( (float)$p_paid ?? 0) + ( (float)$registuration_fee ?? 0)) / $student_count;

            $description =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) الأشهر " . "{ " . $m_h . " }" . ' ' ;

            while ($row = $result_students->fetch_assoc()) {
                $student_name = $row['student_name'];
                $student_id = $row['id'];

                if ($registuration_fee) {
                    $regist_fee = $registuration_fee / $student_count;
                    $des_ins =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) " . " رسوم تسجيل لطالب(ة)  " . ': ' . ($student_name) ;

                    $transaction_id = insertComTransaction($conn, '', $type, $student_id, 0.00, $regist_fee, 0.00, $des_ins, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
                    insertReceiptPay($conn, $receipts_id, $transaction_id);
                    insertTransaction($conn, $type, $student_id, $des_ins, $regist_fee, $bank_accoun_id, $user_id, $agent_id, $fund_d);
                    updateBalance($conn, $payment_method, $regist_fee, $bank_id);
                }

                if ($p_paid) {
                    $pp_paid = $p_paid / $student_count;

                    $description =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') '. " دفع(ت)  " . "{ " . $description_p . " }" . 'لطالب(ة): ' . ($student_name);
                    $transaction_id = insertComTransaction($conn, '', $type, $student_id, 0.00, $pp_paid, 0.00, $description, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
                    insertReceiptPay($conn, $receipts_id, $transaction_id);
                    insertTransaction($conn, $type, $student_id, $description, $pp_paid, $bank_accoun_id, $user_id, $agent_id, $fund_d);
                    updateBalance($conn, $payment_method, $pp_paid, $bank_id);

                }
            }
        
            $months = $_POST['monthss'] ?? [];
            $placeholders = rtrim(str_repeat('?,', count($months)), ',');
            $monthsString = implode(',', $_POST['monthss'] ?? []);
            $monthsA = explode(',', $monthsString);
            $sql_s = "SELECT s.id, s.student_name, s.remaining FROM students s
                        LEFT JOIN payments p ON s.id = p.student_id AND p.month IN ($placeholders)
                        WHERE s.agent_id = ? AND s.remaining > 0.00
                        GROUP BY s.id, s.student_name, s.remaining
                        HAVING COUNT(p.payment_id) < ?
                    ";
            $stmt_s = $conn->prepare($sql_s);
            $params = array_merge($months, [$agent_id, count($months)]);
            $bind_types = str_repeat('s', count($months)) . 'i' . 'i';
            $stmt_s->bind_param($bind_types, ...$params);
            $stmt_s->execute();
            $result_s = $stmt_s->get_result();
            $rem_paid = $paid_amount;

            if ($result_s->num_rows > 0) {
                $success = true;
                $st_ct = $result_s->num_rows;
                $result_array = $result_s->fetch_all(MYSQLI_ASSOC);
                foreach ($result_array as $row)  {
                    $student_name = $row['student_name'];
                    $student_id = $row['id'];
                    $remaining_balance = $row['remaining'];
                    
                    $unpaid_months = [];
                    foreach ($monthsA as $month) {
                        $check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM payments WHERE student_id = ? AND month = ?");
                        $check_stmt->bind_param("is", $student_id, $month);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result()->fetch_assoc();
                        $check_stmt->close();

                        if ($check_result['count'] == 0) {
                            $unpaid_months[] = $month;
                        }
                    }
                    if (empty($unpaid_months)) {
                        continue;
                    }
                    
                    $conn->begin_transaction();
                    foreach ($unpaid_months as $month) {

                        $payment_per_month = min($rem_paid, $remaining_balance);
                        $rem_amount = $remaining_balance - $payment_per_month;

                        $stmt = $conn->prepare("INSERT INTO payments (agent_id, student_id, month, due_amount, paid_amount, remaining_amount, bank_id, payment_date, payment_method, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iisdddissi", $agent_id, $student_id, $month, $remaining_balance, $payment_per_month, $rem_amount, $bank_id, $payment_date,$payment_method,$user_id);
                        if ($stmt->execute()) {
                            updateBalance($conn, $payment_method, $payment_per_month, $bank_id);
                            $des_tion =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) الأشهر " . "{ " . $month . " }" . 'لطالب(ة): ' . ($student_name) ;

                            $transaction_id = insertComTransaction($conn, $month, $type, $student_id, $remaining_balance, $payment_per_month, $rem_amount, $des_tion, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
                            insertReceiptPay($conn, $receipts_id, $transaction_id);
                            insertTransaction($conn, $type, $student_id, $des_tion, $payment_per_month, $bank_accoun_id, $user_id, $agent_id, $fund_d);
                            
                            $rem_paid -= $payment_per_month;
                            if ($rem_paid <= 0) {
                                break;
                            }
                        } else {
                            $response['message'] = 'Error: ' . $stmt->error;
                            $success = false;
                            break;
                        }
                        $stmt->close();
                    }
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

function insertReceipts($conn, $tot_paid, $description, $user_id, $agent_id) {
    $recp = $conn->prepare("INSERT INTO receipts (receipt_date, total_amount, receipt_description, created_by, agent_id) VALUES (NOW(), ?, ?, ?, ?)");
    if (!$recp) { throw new Exception("Prepare failed: " . $conn->error);}
    $recp->bind_param("ssss", $tot_paid, $description, $user_id, $agent_id);
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
