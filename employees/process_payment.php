<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';


session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}



$user_id = $_SESSION['userid'];
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $due_amounte = $_POST['due_amounte'] ?? null;
    $student_id = $_POST['id'] ?? null;
    $due_amount = $_POST['due_amount'] ?? null;
    $paid_amount = $_POST['paid_amount'] ?? null;
    $remaining_amount = $_POST['remaining_amount'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $months = $_POST['month'] ?? null;

    $registuration_fee = $_POST['registuration_fee'] ?? 0;
    $description_p = $_POST['description_p'] ?? null;
    $p_paid = $_POST['p_paid'] ?? 0;
    $des_ins = '';
    $description = '';
    $bank_id = ($payment_method === "بنكي") ? $_POST['bank'] ?? null : null;
    $fund_id = ($payment_method === "نقدي") ? 1 : null;

    if ($payment_method === "بنكي" && !$bank_id) {
        die("Bank ID not set. Please select a bank.");
    }

    if (empty($student_id)) {
        $response = ['success' => false, 'message' => 'معرف الطالب مفقود.'];
    } elseif (empty($months) || empty($due_amount) || empty($paid_amount)) {
        
        $stmt_student = $conn->prepare("SELECT student_name FROM students WHERE id = ?");
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $result_student = $stmt_student->get_result();
        $student_name = $result_student->fetch_assoc()['student_name'];
        $stmt_student->close();

        // Prepare the transaction description


        if ($registuration_fee || $description_p) {
            
            $paid_amount_T = ((float)$registuration_fee  ?? 0 ) + ((float)$p_paid  ?? 0 );

            $trans_iption = "الطالب(ة): ". $student_name. ' ' . "سديد(ت) رسوم ";

            $stmt = $conn->prepare("
                INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by)
                VALUES (?, NOW(), ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $student_id, $paid_amount_T, $trans_iption, $user_id);
            $stmt->execute();
            
            $receipts_id = $conn->insert_id;

    

            if ($registuration_fee) {
                $des_ins = "الطالب(ة): ". $student_name. ' ' . "سديد(ت) رسوم تسجيل ";

                // Insert into `combined_transactions`
                $stmt = $conn->prepare("
                INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, fund_id)
                VALUES ('plus', ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param("sssssss", $student_id, $des_ins, $registuration_fee, $payment_method, $bank_id, $user_id, $fund_id);
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

                // Commit the transaction
                $conn->commit();




                $stmt_reg_fee = $conn->prepare("
                    INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id)
                    VALUES ('$des_ins', ?, 'plus', ?, ?, ?, ?)
                ");
                $stmt_reg_fee->bind_param("diiii", $registuration_fee, $student_id, $fund_id, $bank_id, $user_id);
                $stmt_reg_fee->execute();
                $stmt_reg_fee->close();
                $receipt_id = $conn->insert_id;

                if ($payment_method === "نقدي") {
                    $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
                    $stmt_fund->bind_param("d", $registuration_fee);
                    $stmt_fund->execute();
                    $stmt_fund->close();
                } elseif ($payment_method === "بنكي" && $bank_id) {
                    $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
                    $stmt_bank->bind_param("di", $registuration_fee, $bank_id);
                    $stmt_bank->execute();
                    $stmt_bank->close();
                }
            // $months_paid_str = implode(' • ', []);
            // $redirect_url = "student_receipt_ins_aut.php?receipt_id=$receipt_id&student_id=$student_id&p_paid=$p_paid&description=$description&paid_amount=$p_paid";
            // header("Location: $redirect_url");
            // exit();
            }

            if ($p_paid) {

                $description =  'الطالب (ة): ' . $student_name . ' { ' . $description_p .  ' } ';

                // Insert into `combined_transactions`
                $stmt = $conn->prepare("
                INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, fund_id)
                VALUES ('plus', ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param("sssssss", $student_id, $description, $p_paid, $payment_method, $bank_id, $user_id, $fund_id);
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

                // Commit the transaction
                $conn->commit();


                $stmt_p_paid = $conn->prepare("
                    INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id)
                    VALUES (?, ?, 'plus', ?, ?, ?, ?)
                ");
                $stmt_p_paid->bind_param("sdiiii", $description, $p_paid, $student_id, $fund_id, $bank_id, $user_id);
                $stmt_p_paid->execute();
                $stmt_p_paid->close();
                $receipt_id = $conn->insert_id;

                if ($payment_method === "نقدي") {
                    $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
                    $stmt_fund->bind_param("d", $p_paid);
                    $stmt_fund->execute();
                    $stmt_fund->close();
                } elseif ($payment_method === "بنكي" && $bank_id) {
                    $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
                    $stmt_bank->bind_param("di", $p_paid, $bank_id);
                    $stmt_bank->execute();
                    $stmt_bank->close();
                }
            }

            $months_paid_str = implode(' • ', []);
            $redirect_url = "student_receipt_re.php?receipt_id=$receipts_id&student_id=$student_id&payment_method=$payment_method&paid_amount=$paid_amount&remaining_amount=$remaining_amount&months_paid=$months_paid_str&due_amounte=$due_amounte";
            header("Location: $redirect_url");
            exit();
        } else {
            $response = ['success' => false, 'message' => 'يرجى ملء الحقول المطلوبة للدفع أو إدخال رسوم تسجيل أو وصف للدفع.'];
        }
    } else {
        $stmt_student = $conn->prepare("SELECT student_name FROM students WHERE id = ?");
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $student_name = $stmt_student->get_result()->fetch_assoc()['student_name'];
        $stmt_student->close();

        $months_paid = [];

        $tot_paid = ((float)$paid_amount  ?? 0 ) + ((float)$p_paid  ?? 0 )+ ((float)$registuration_fee  ?? 0 );
        
        $m_h = implode(', ', $months);
        $d_n =  'الطالب(ة) سدد(ت)  ' . $student_name . ' الأشهر: { ' . $m_h .  ' } ';

        $stmt = $conn->prepare("
            INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by)
            VALUES (?, NOW(), ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $student_id, $paid_amount, $d_n, $user_id);
        $stmt->execute();
        
        $receipts_id = $conn->insert_id;

        if ($registuration_fee) {
            $des_ins = "الطالب(ة): ". $student_name. ' ' . "سديد(ت) رسوم تسجيل ";

            // Insert into `combined_transactions`
            $stmt = $conn->prepare("
            INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, fund_id)
            VALUES ('plus', ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("sssssss", $student_id, $des_ins, $registuration_fee, $payment_method, $bank_id, $user_id, $fund_id);
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

            // Commit the transaction
            $conn->commit();




            $stmt_reg_fee = $conn->prepare("
                INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id)
                VALUES ('$des_ins', ?, 'plus', ?, ?, ?, ?)
            ");
            $stmt_reg_fee->bind_param("diiii", $registuration_fee, $student_id, $fund_id, $bank_id, $user_id);
            $stmt_reg_fee->execute();
            $stmt_reg_fee->close();
            $receipt_id = $conn->insert_id;

            if ($payment_method === "نقدي") {
                $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
                $stmt_fund->bind_param("d", $registuration_fee);
                $stmt_fund->execute();
                $stmt_fund->close();
            } elseif ($payment_method === "بنكي" && $bank_id) {
                $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
                $stmt_bank->bind_param("di", $registuration_fee, $bank_id);
                $stmt_bank->execute();
                $stmt_bank->close();
            }
 
        }

        if ($p_paid) {

            $description =  'الطالب(ة): ' . $student_name . ' { ' . $description_p .  ' } ';

            // Insert into `combined_transactions`
            $stmt = $conn->prepare("
            INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, fund_id)
            VALUES ('plus', ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("sssssss", $student_id, $description, $p_paid, $payment_method, $bank_id, $user_id, $fund_id);
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

            // Commit the transaction
            $conn->commit();


            $stmt_p_paid = $conn->prepare("
                INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id)
                VALUES (?, ?, 'plus', ?, ?, ?, ?)
            ");
            $stmt_p_paid->bind_param("sdiiii", $description, $p_paid, $student_id, $fund_id, $bank_id, $user_id);
            $stmt_p_paid->execute();
            $stmt_p_paid->close();
            $receipt_id = $conn->insert_id;

            if ($payment_method === "نقدي") {
                $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
                $stmt_fund->bind_param("d", $p_paid);
                $stmt_fund->execute();
                $stmt_fund->close();
            } elseif ($payment_method === "بنكي" && $bank_id) {
                $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
                $stmt_bank->bind_param("di", $p_paid, $bank_id);
                $stmt_bank->execute();
                $stmt_bank->close();
            }
        }

        $stmt = $conn->prepare("INSERT INTO combined_transactions 
        (description, type, student_id, month, due_amount, paid_amount, remaining_amount, payment_method, bank_id, user_id, agent_id, fund_id)
        VALUES (?, 'plus', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for combined_transactions: " . $conn->error);
        }
        $stmt->bind_param("sisdsssssss", $d_n, $student_id, $m_h, $due_amount, $paid_amount, $remaining_amount, $payment_method, $bank_id, $user_id, $agent_id, $fund_id);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting into combined_transactions: " . $stmt->error);
        }
        $transaction_id = $conn->insert_id; // Retrieve last inserted ID
        $stmt->close();

        // Insert into receipt_payments
        $stmt = $conn->prepare("INSERT INTO receipt_payments (receipt_id, transaction_id) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for receipt_payments: " . $conn->error);
        }
        $stmt->bind_param("ii", $receipts_id, $transaction_id);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting into receipt_payments: " . $stmt->error);
        }
        $stmt->close();

        // Insert into transactions
        $stmt_transaction = $conn->prepare("INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id)
            VALUES (?, ?, 'plus', ?, ?, ?, ?)");

        if (!$stmt_transaction) {
            throw new Exception("Failed to prepare statement for transactions: " . $conn->error);
        }
        $stmt_transaction->bind_param("sdiiii", $d_n, $paid_amount, $student_id, $fund_id, $bank_id, $user_id);
        if (!$stmt_transaction->execute()) {
            throw new Exception("Error inserting into transactions: " . $stmt_transaction->error);
        }
        $stmt_transaction->close();


        foreach ($months as $month) {
            $paid_amount_per_month = $paid_amount / count($months);

            $stmt = $conn->prepare("
                INSERT INTO payments (student_id, month, due_amount, paid_amount, remaining_amount, payment_method, bank_id, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssssss", $student_id, $month, $due_amount, $paid_amount_per_month, $remaining_amount, $payment_method, $bank_id, $user_id);

            // Execute the statement
            if ($stmt->execute()) {

                $receipt_id = $conn->insert_id;  
                $months_paid[] = $month;

                // Fetch the student name for the transaction description
                // $stmt_student = $conn->prepare("SELECT student_name FROM students WHERE id = ?");
                // $stmt_student->bind_param("i", $student_id);
                // $stmt_student->execute();
                // $result_student = $stmt_student->get_result();
                // $student_name = $result_student->fetch_assoc()['student_name'];
                // $stmt_student->close();

                // Prepare the transaction description


                // Update the balance for the fund or bank
                if ($payment_method === "نقدي") {
                    $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
                    $stmt_fund->bind_param("d", $paid_amount_per_month);
                    if (!$stmt_fund->execute()) {
                        $response = ['success' => false, 'message' => 'حدث خطأ أثناء تحديث رصيد الصندوق: ' . $stmt_fund->error];
                        break;  // Stop on the first error
                    }
                    $stmt_fund->close();
                } elseif ($payment_method === "بنكي" && $bank_id) {
                    $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
                    $stmt_bank->bind_param("di", $paid_amount_per_month, $bank_id);
                    if (!$stmt_bank->execute()) {
                        $response = ['success' => false, 'message' => 'حدث خطأ أثناء تحديث رصيد البنك: ' . $stmt_bank->error];
                        break;  // Stop on the first error
                    }
                    $stmt_bank->close();
                }

            } else {
                $response = ['success' => false, 'message' => 'حدث خطأ أثناء معالجة الدفع: ' . $stmt->error];
                break;  // Stop on the first error
            }
            $stmt->close();
        }

        if (empty($response)) {  
            $months_paid_str = implode(' • ', $months_paid); 
            $redirect_url = "student_receipt_re.php?receipt_id=$receipts_id&student_id=$student_id&payment_method=$payment_method&paid_amount=$paid_amount&remaining_amount=$remaining_amount&months_paid=$months_paid_str&due_amounte=$due_amounte";
            header("Location: $redirect_url");
            exit();
        }
    }
}

$conn->close();
?>
