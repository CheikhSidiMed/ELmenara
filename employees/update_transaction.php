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
    $transactionDescription = $_POST['transaction_description'];
    $amount = $_POST['amount'] ?? 0.00;
    $ancAmount = $_POST['ancAmount'] ?? 0.00;
    $fund_id = $_POST['fund_id'] ?? null;
    $bank_account_id = !empty($_POST['bank_account_id']) ? $_POST['bank_account_id'] : null;
    $employee_id = !empty($_POST['employee_id']) ? $_POST['employee_id'] : null;
    $transac_type = !empty($_POST['type']) ? $_POST['type'] : null;
    $student_id = !empty($_POST['student_id']) ? $_POST['student_id'] : null;
    $agent_id = !empty($_POST['agent_id']) ? $_POST['agent_id'] : null;
    $accountType = $_POST['account_type'];
    $t_n = $_POST['t_n'] ?? null;

    // Validate input
    if (empty($transactionId) || empty($transactionDescription) || empty($amount)) {
        $response['message'] = 'الرجاء ملء جميع الحقول المطلوبة.';
        echo json_encode($response);
        exit;
    }


    $amoun_t = (float)$ancAmount - (float)$amount;
    $amount_t = $transac_type === 'minus' ?  -floatval($amoun_t) : floatval($amoun_t);

    try {
        if ($bank_account_id) {
            $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
            $stmt_bank->bind_param("di", $amount_t, $bank_account_id);
            if (!$stmt_bank->execute()) {
                throw new Exception('فشل تحديث رصيد البنك.');
            }
            $stmt_bank->close();
        } elseif ($fund_id) {
            // Update fund balance
            $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = ?");
            $stmt_fund->bind_param("di", $amount_t, $fund_id);
            if (!$stmt_fund->execute()) {
                throw new Exception('فشل تحديث رصيد الصندوق.');
            }
            $stmt_fund->close();
        }

        // Update employee balance
        if ($employee_id) {
            $stmt_emp = $conn->prepare("UPDATE employees SET balance = balance + ? WHERE id = ?");
            $stmt_emp->bind_param("di", $amount_t, $employee_id);
            if (!$stmt_emp->execute()) {
                throw new Exception('فشل تحديث رصيد الموظف.');
            }
            $stmt_emp->close();
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

            // $stmt_agent = $conn->prepare("SELECT id, month, agent_id, student_id, due_amount FROM combined_transactions WHERE description = ?");
            // $stmt_agent->bind_param("s", $t_n);
            // $stmt_agent->execute();
            // $result = $stmt_agent->get_result();
            $agent = $result->fetch_assoc();
            $months = $agent['month'];
            $due_amount = $agent['due_amount'];
            $id = $agent['id'];
            $student_id = $agent['student_id'];
            $agent_id = $agent['agent_id'];

            $stmt_agent->close();
            if ($months) {
                $monthsArray = explode(', ', $months);
                $per_amount = (float)$amount / count($monthsArray);

                $rem_amount = count($monthsArray) * ((float)$due_amount - (float)$per_amount);

                $monthArray = explode(', ', $months);
                $per_amount = (float)$amount / count($monthArray);
                $rem_per_amount = (float)$due_amount - $per_amount;

                foreach ($monthArray as $singleMonth) {
                    $stmt_paymt = $conn->prepare("UPDATE payments 
                        SET paid_amount = ?, remaining_amount = ?
                        WHERE student_id = ? AND month = ?");
                    $stmt_paymt->bind_param("ddis", $per_amount, $rem_per_amount, $student_id, $singleMonth);
                    $stmt_paymt->execute();
                    $stmt_paymt->close();
                }

                
                $stmt_empl = $conn->prepare("UPDATE combined_transactions 
                SET description = ?, paid_amount = ?, remaining_amount =?
                WHERE id = ? ");
                $stmt_empl->bind_param("sddi", $transactionDescription, $amount, $rem_amount, $id);
            }else{
                $stmt_empl = $conn->prepare("UPDATE combined_transactions SET description = ?, paid_amount = ? WHERE id = ? ");
                $stmt_empl->bind_param("sdi", $transactionDescription, $amount, $id);
            }


            if (!$stmt_empl->execute()) {
                throw new Exception('فشل في تحديث المعاملات المشتركة.');
            }
            $stmt_empl->close();
        }


        // Update transaction
        if ($accountType === 'مصاريف') {
            $stmt = $conn->prepare("UPDATE expense_transaction SET transaction_description = ?, amount = ? WHERE id = ?");
        }elseif ($accountType === 'مداخيل') {
            $stmt = $conn->prepare("UPDATE donate_transactions SET transaction_description = ?, amount = ? WHERE id = ?");
        }
         else {
            $stmt = $conn->prepare("UPDATE transactions SET transaction_description = ?, amount = ? WHERE id = ?");
        }

        if ($stmt) {
            $stmt->bind_param("sdi", $transactionDescription, $amount, $transactionId);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'تم تحديث العملية بنجاح.';
            } else {
                throw new Exception('فشل في تنفيذ عملية التحديث.');
            }
            $stmt->close();
        } else {
            throw new Exception('فشل في إعداد استعلام التحديث.');
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    $conn->close();
    echo json_encode($response);
}
?>
