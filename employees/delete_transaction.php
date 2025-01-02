<?php
include 'db_connection.php';

// Response array
$response = ['success' => false, 'message' => ''];
session_start(); 

if (!isset($_SESSION['userid'])) {
    die(json_encode(['success' => false, 'message' => 'Error: User is not logged in.']));
}
$user_id = $_SESSION['userid'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $transactionId = $_POST['id'] ?? null;
    $amount = $_POST['amount'] ?? 0.00;
    $bank_account_id = !empty($_POST['bank_account_id']) ? $_POST['bank_account_id'] : null;
    $employee_id = !empty($_POST['employee_id']) ? $_POST['employee_id'] : null;
    $student_id = !empty($_POST['student_id']) ? $_POST['student_id'] : null;
    $agent_id = !empty($_POST['agent_id']) ? $_POST['agent_id'] : null;
    $payment_method = $_POST['payment_method'] ?? null;
    $months = !empty($_POST['months']) ? $_POST['months'] : null;
    $accountType = $_POST['account_type'] ?? null;
    $transac_type = $_POST['transac_type'] ?? null;

    $t_n = $_POST['t_n'] ?? null;

    if (empty($transactionId)) {
        echo json_encode(['success' => false, 'message' => 'معرف العملية مفقود.']);
        exit;
    }

    $fund_id = !empty($bank_account_id) ? null : 1;
    $amount_t = $transac_type === 'minus' ?  -floatval($amount) : floatval($amount);
    $receipts_id = '';
    try {
        // تحديث رصيد البنك
        if (!empty($bank_account_id)) {
            $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
            $stmt_bank->bind_param("di", $amount_t, $bank_account_id);
            if (!$stmt_bank->execute()) {
                throw new Exception('فشل إلغاء رصيد البنك.');
            }
            $stmt_bank->close();
        }

        // تحديث رصيد الصندوق
        if (!empty($fund_id)) {
            $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = ?");
            $stmt_fund->bind_param("di", $amount_t, $fund_id);
            if (!$stmt_fund->execute()) {
                throw new Exception('فشل إلغاء رصيد الصندوق.');
            }
            $stmt_fund->close();
        }

        // تحديث رصيد الموظف
        if (!empty($employee_id)) {
            $stmt_emp = $conn->prepare("UPDATE employees SET balance = balance + ? WHERE id = ?");
            $stmt_emp->bind_param("di", $amount_t, $employee_id);
            if (!$stmt_emp->execute()) {
                throw new Exception('فشل إلغاء رصيد الموظف.');
            }
            $stmt_emp->close();
        }

        // التعامل مع العمليات المشتركة
        if (!empty($t_n)) {
            $stmt_agent = null;

            // اختيار الاستعلام المناسب
            if (!empty($student_id) && !empty($agent_id)) {
                $stmt_agent = $conn->prepare("
                    SELECT id, agent_id, month, student_id 
                    FROM combined_transactions 
                    WHERE description = ? AND agent_id = ? AND student_id = ?
                ");
                $stmt_agent->bind_param("sss", $t_n, $agent_id, $student_id);
            } elseif (!empty($student_id)) {
                $stmt_agent = $conn->prepare("
                    SELECT id, agent_id, month, student_id
                    FROM combined_transactions 
                    WHERE description = ? AND student_id = ?
                ");
                $stmt_agent->bind_param("ss", $t_n, $student_id);
            } elseif (!empty($employee_id)) {
                $stmt_agent = $conn->prepare("
                    SELECT id, student_id, month, agent_id
                    FROM combined_transactions 
                    WHERE description = ? AND employee_id = ?
                ");
                $stmt_agent->bind_param("ss", $t_n, $employee_id);
            } else {
                $stmt_agent = $conn->prepare("
                    SELECT id, agent_id, month, student_id 
                    FROM combined_transactions 
                    WHERE description = ?
                ");
                $stmt_agent->bind_param("s", $t_n);
            }

            $stmt_agent->execute();
            $result = $stmt_agent->get_result();
            
            function find($t_n, $months, $placeholder = '') {
                $primary_pattern = '/الأشهر\s*\{\s*([^}]*)\s*\}/u';
                $fallback_pattern = '/الأشهر\s*:\s*\{([^}]*)\}/u';
                
                if (preg_match($primary_pattern, $t_n, $matches) || preg_match($fallback_pattern, $t_n, $matches)) {
                    $existing_months = explode(', ', $matches[1]); 
                    $updated_months = array_filter($existing_months, function($month) use ($months) {
                        return trim($month) !== trim($months); 
                    });
                    
                    if (!empty($updated_months)) {
                        $new_months_string = implode(', ', $updated_months);
                        $t_n = preg_replace($primary_pattern, "الأشهر { $new_months_string }", $t_n);
                        $t_n = preg_replace($fallback_pattern, "الأشهر { $new_months_string }", $t_n);
                    } else {
                        $t_n = preg_replace($primary_pattern, '', $t_n);
                        $t_n = preg_replace($fallback_pattern, '', $t_n);
                    }
                }
                
                return trim($t_n);
            }
            $ds = find($t_n, $months, '');

            function getRemainingMonths($month_s, $month) {
                $all_months = array_map('trim', explode(', ', $month_s));
                $selected_months = array_map('trim', explode(', ', $month));
                $remaining_months = array_diff($all_months, $selected_months);                
                return implode(', ', $remaining_months);
            }
            if ($result && $agent = $result->fetch_assoc()) {
                $student_d = $agent['student_id'] ?? null;
                $month_s = $agent['month'] ?? null;
                $transaction_id = $agent['id'];

                $mont_hs = [];
                if (preg_match('/الأشهر:\s*\{([^}]*)\}/', $t_n, $matches)) {         
                    $mont_hs = explode(', ', $matches[1]);
                } elseif (preg_match('/الأشهر\s*\{\s*([^}]*)\s*\}/u', $t_n, $matches)) {
                    $mont_hs = explode(', ', $matches[1]);
                } else {
                    $mont_hs = [];
                }

                $month_s_array = explode(', ', $month_s);
                $mont_hs_array = $mont_hs;

                $rult = getRemainingMonths($month_s, $months);
                $d_rest_s = find($t_n, $rult, '');
                
                $dess = 'تم إلغاء الوصل ' . $d_rest_s;

                if (count($mont_hs_array) > 1) {
                    
                    if (count($month_s_array) === count($mont_hs_array) && !array_diff($month_s_array, $mont_hs_array)) {
                        // $smt = $conn->prepare("DELETE FROM combined_transactions WHERE id = ?");
                        // $smt->bind_param("s", $transaction_id);
                        // $smt->execute();
                    } else {
                        $smt = $conn->prepare("UPDATE combined_transactions SET description = ?, month = ?, paid_amount = paid_amount - ? WHERE id = ?");
                        $smt->bind_param("ssss", $ds, $months, $amount, $transaction_id);
                        $smt->execute();

                        $trns = $conn->prepare("INSERT INTO transactions (transaction_type, student_id, transaction_description, amount, bank_account_id, user_id, agent_id, fund_id) VALUES ('plus', ?, ?, ?, ?, ?, ?, ?)");
                        $trns->bind_param("sssssss", $student_id, $ds, $amount, $bank_account_id, $user_id, $agent_id, $fund_id);
                        $trns->execute();
                    }
                } else {
                    // $smt = $conn->prepare("DELETE FROM combined_transactions WHERE id = ?");
                    // $smt->bind_param("s", $transaction_id);
                    // $smt->execute();
                }

                // إضافة إيصال جديد
                $stmt = $conn->prepare("INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by, agent_id)
                    VALUES (?, NOW(), ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $student_d, $amount_t, $dess, $user_id, $agent_id);
                $stmt->execute();
                $receipts_id = $conn->insert_id;

                // إدخال عملية جديدة
                $stmt = $conn->prepare("INSERT INTO combined_transactions (type, employee_id, month, student_id, description, paid_amount, payment_method, bank_id, user_id, agent_id, fund_id) VALUES ('minus', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", $employee_id, $months, $student_id, $dess, $amount_t, $payment_method, $bank_account_id, $user_id, $agent_id, $fund_id);
                $stmt->execute();
                $combined_id = $conn->insert_id;

                $stmt = $conn->prepare("INSERT INTO receipt_payments (receipt_id, transaction_id) VALUES ( ?, ?)");
                $stmt->bind_param("ss", $receipts_id, $combined_id);
                $stmt->execute();

                // حذف المدفوعات المرتبطة
                if (!empty($months)) {
                    $mon_ths = trim($months, " \t\n\r\0\x0B");
                    $monthsArray = explode(',', $mon_ths);
                    foreach ($monthsArray as $singleMonth) {
                        $trm = trim($singleMonth);
                        $stmt_payment = $conn->prepare("DELETE FROM payments WHERE student_id = ? AND month = ?");
                        $stmt_payment->bind_param("ss", $student_id, $trm);
                        $stmt_payment->execute();
                        $stmt_payment->close();
                    }
                }
            }
            $stmt_agent->close();
        }

        // حذف العملية الأصلية
        $stmt = null;
        if ($accountType === 'مصاريف') {
            $stmt = $conn->prepare("DELETE FROM expense_transaction WHERE id = ?");
        } elseif ($accountType === 'مداخيل') {
            $stmt = $conn->prepare("DELETE FROM donate_transactions WHERE id = ?");
        } else {
            $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
        }

        if ($stmt) {
            $stmt->bind_param("i", $transactionId);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['receipts_id'] = $receipts_id;
                $response['message'] = 'تم إلغاء العملية بنجاح.';
            } else {
                throw new Exception('فشل في تنفيذ عملية إلغاء.');
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    $conn->close();
    echo json_encode($response);
}
?>
