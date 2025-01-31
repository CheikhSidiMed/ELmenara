<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Response array
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $transactionId = $_POST['id'];
    $newBank = $_POST['newBank'];
    $ancienBanque = $_POST['ancienBanque'];

    $t_n = $_POST['description'];
    $amount = $_POST['amount'] ?? 0.00;
    $fund_id = $_POST['fund_id'] ?? null;
    $bank_account_id = !empty($_POST['bank_account_id']) ? $_POST['bank_account_id'] : null;
    $employee_id = !empty($_POST['employee_id']) ? $_POST['employee_id'] : null;
    $student_id = !empty($_POST['student_id']) ? $_POST['student_id'] : null;
    $agent_id = !empty($_POST['agent_id']) ? $_POST['agent_id'] : null;
    $accountType = $_POST['account_type'];

    // Validate input
    if (empty($transactionId) || empty($amount)) {
        $response['message'] = 'الرجاء ملء جميع الحقول المطلوبة.';
        echo json_encode($response);
        exit;
    }

    $id_newBnk = null;
    if($newBank !== 'الصندوق'){
        $stmt = $conn->prepare("SELECT account_id FROM bank_accounts WHERE bank_name = ?");
        $stmt->bind_param("s", $newBank);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $id_newBnk = $result->fetch_assoc()['account_id'];
        }
        $stmt->close();
    }

    $newFund = null;

    $amount_t = (float)$amount;

    try {

        if ($bank_account_id) {
            $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance - ? WHERE account_id = ?");
            $stmt_bank->bind_param("di", $amount_t, $bank_account_id);
            if (!$stmt_bank->execute()) {
                throw new Exception('فشل تحديث رصيد البنك.');
            }
            $stmt_bank->close();
        } else{
            $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance - ? WHERE id = 1");
            $stmt_fund->bind_param("d", $amount_t);
            if (!$stmt_fund->execute()) {
                throw new Exception('فشل تحديث رصيد الصندوق.');
            }
            $stmt_fund->close();
        }

        $payment_method = '';
        if ($id_newBnk) {
            $payment_method = 'بنكي';
            $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
            $stmt_bank->bind_param("di", $amount_t, $id_newBnk);
            if (!$stmt_bank->execute()) {
                throw new Exception('فشل تحديث رصيد البنك.');
            }
            $stmt_bank->close();
        } else {
            $newFund = 1;
            $payment_method = 'نقدي';
            $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
            $stmt_fund->bind_param("d", $amount_t);
            if (!$stmt_fund->execute()) {
                throw new Exception('فشل تحديث رصيد الصندوق.');
            }
            $stmt_fund->close();
        }


        // Update combined transactions
        if (!empty($t_n)) {

            $stmt_agent = null;

            // اختيار الاستعلام المناسب
            if (!empty($student_id) && !empty($agent_id)) {
                $stmt_agent = $conn->prepare("
                    SELECT id, agent_id, month, student_id, due_amount
                    FROM combined_transactions
                    WHERE description = ? AND agent_id = ? AND student_id = ?
                ");
                $stmt_agent->bind_param("sss", $t_n, $agent_id, $student_id);
            } elseif (!empty($student_id)) {
                $stmt_agent = $conn->prepare("
                    SELECT id, student_id, month, agent_id, due_amount
                    FROM combined_transactions
                    WHERE description = ? AND student_id = ?
                ");
                $stmt_agent->bind_param("ss", $t_n, $student_id);
            } elseif (!empty($employee_id)) {
                $stmt_agent = $conn->prepare("
                    SELECT id, student_id, month, agent_id, due_amount
                    FROM combined_transactions
                    WHERE description = ? AND employee_id = ?
                ");
                $stmt_agent->bind_param("ss", $t_n, $employee_id);
            } else {
                $stmt_agent = $conn->prepare("
                    SELECT id, agent_id, month, student_id, due_amount
                    FROM combined_transactions
                    WHERE description = ?
                ");
                $stmt_agent->bind_param("s", $t_n);
            }
            $stmt_agent->execute();
            $result = $stmt_agent->get_result();


            $agent = $result->fetch_assoc();
            $months = $agent['month'];
            $due_amount = $agent['due_amount'];
            $id = $agent['id'];
            $student_id = $agent['student_id'];
            $agent_id = $agent['agent_id'];

            $stmt_agent->close();
            if (!empty($months)) {
                // Convert to array if comma-separated
                $monthArray = explode(',', $months);
            
                if (!empty($monthArray)) {
                    foreach ($monthArray as $singleMonth) {
                        $stmt_paymt = $conn->prepare("UPDATE payments
                            SET payment_method = ?, bank_id = ?
                            WHERE student_id = ? AND month = ?");
                        $stmt_paymt->bind_param("ddis", $payment_method, $id_newBnk, $student_id, $singleMonth);
                        $stmt_paymt->execute();
                        $stmt_paymt->close();
                    }


                } else {
                    throw new Exception('Month array is empty after processing.');
                }
                $stmt_e = $conn->prepare("UPDATE combined_transactions SET payment_method = ?, bank_id = ?, fund_id = ? WHERE id = ? ");
                $stmt_e->bind_param("sssi", $payment_method, $id_newBnk, $newFund, $id);
                if (!$stmt_e->execute()) {
                    throw new Exception('فشل في تحديث المعاملات المشتركة.');
                }
                $stmt_e->close();
            } else {
                $stmt_empl = $conn->prepare("UPDATE combined_transactions SET payment_method = ?, bank_id = ?, fund_id = ? WHERE id = ? ");
                $stmt_empl->bind_param("sssi", $payment_method, $id_newBnk, $newFund, $id);
            
                // Execute and close only when the statement is prepared
                if (!$stmt_empl->execute()) {
                    throw new Exception('فشل في تحديث المعاملات المشتركة.');
                }
                $stmt_empl->close();
            }
            
        }


        // Update transaction
        if ($accountType === 'مصاريف') {
            $stmt = $conn->prepare("UPDATE expense_transaction SET payment_method = ?, bank_id = ? WHERE id = ?");
            $stmt->bind_param("ssi", $payment_method, $id_newBnk, $transactionId);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'تم تحديث العملية بنجاح.';
            } else {
                throw new Exception('فشل في تنفيذ عملية تحديث المصاريف.');
            }
            $stmt->close();
        } elseif ($accountType === 'مداخيل') {
            $stmt = $conn->prepare("UPDATE donate_transactions SET payment_method = ?, bank_id = ? WHERE id = ?");
            $stmt->bind_param("ssi", $payment_method, $id_newBnk, $transactionId);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'تم تحديث العملية بنجاح.';
            } else {
                throw new Exception('فشل في تنفيذ عملية تحديث المداخيل.');
            }
            $stmt->close();
        } else {
            $stmt1 = $conn->prepare("UPDATE transactions SET bank_account_id = ?, fund_id = ? WHERE id = ?");
            $stmt1->bind_param("ssi", $id_newBnk, $newFund, $transactionId);
            if ($stmt1->execute()) {
                $response['success'] = true;
                $response['message'] = 'تم تحديث العملية بنجاح.';
            } else {
                throw new Exception('فشل في تنفيذ عملية التحديث.');
            }
            $stmt1->close();
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    $conn->close();
    echo json_encode($response);
}
?>
