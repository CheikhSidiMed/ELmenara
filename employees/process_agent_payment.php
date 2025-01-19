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

    $fund_d = null;
    $bank_accoun_id = null;
    if ($payment_method === "نقدي") {
        $fund_d = 1; 
    } elseif ($payment_method === "بنكي" && $bank_id) {
        $bank_accoun_id = $bank_id;
    }

    if (empty($months) || empty($due_amount) || empty($paid_amount)) {
        
        if ($registuration_fee || $description_p) {
            
            $paid_amount_T = ((float)$registuration_fee ?? 0 )+ ((float)$p_paid ?? 0);
            
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
            
            $des_ins =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) " . " رسوم تسجيل لطالب(ة)  " . ': ' . ($student_name) ;

            $stmt = $conn->prepare("
                INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by, agent_id)
                VALUES (?, NOW(), ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssss", $student_id, $paid_amount_T, $des_ins, $user_id, $agent_id);
            $stmt->execute();
            
            $receipts_id = $conn->insert_id;



            if ($registuration_fee) {
                // Insert into `combined_transactions`
                $stmt = $conn->prepare("
                INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, agent_id, fund_id)
                VALUES ('plus', ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param("ssssssss", $student_id, $des_ins, $registuration_fee, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
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




                $stmt_reg_fee = $conn->prepare("
                    INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id, agent_id)
                    VALUES ('$des_ins', ?, 'plus', ?, ?, ?, ?, ?)
                ");
                $stmt_reg_fee->bind_param("diiiii", $registuration_fee, $student_id, $fund_d, $bank_accoun_id, $user_id, $agent_id);
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

                $description =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . "دفع(ت) " . "{ " . $description_p . " }" . 'لطالب(ة): ' . ($student_name) ;

                // Insert into `combined_transactions`
                $stmt = $conn->prepare("
                INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, agent_id, fund_id)
                VALUES ('plus', ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param("ssssssss", $student_id, $description, $p_paid, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
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
                    INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id, agent_id)
                    VALUES (?, ?, 'plus', ?, ?, ?, ?, ?)
                ");
                $stmt_p_paid->bind_param("sdiiiii", $description, $p_paid, $student_id, $fund_d, $bank_accoun_id, $user_id, $agent_id);
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

            // $months_paid_str = implode(' • ', []);
            // $redirect_url = "student_receipt_ins_aut.php?receipt_id=$receipt_id&des_ins=$des_ins&student_id=$student_id&p_paid=$p_paid&description=$description&registuration_fee=$registuration_fee&payment_method=$payment_method";
            // header("Location: $redirect_url");
            header("Location: agent_receipt.php?payment_ids=" . $receipts_id);

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

        $stmt_agent = $conn->prepare("SELECT agent_name, phone FROM agents WHERE agent_id = ?");
        $stmt_agent->bind_param("i", $agent_id);
        $stmt_agent->execute();
        $result = $stmt_agent->get_result();
        $agent = $result->fetch_assoc();
        $agent_name = $agent['agent_name'];
        $agent_phone = $agent['phone'];
        $stmt_agent->close();

        $tot_paid = (float)$paid_amount + ( (float)$p_paid ?? 0) + ( (float)$registuration_fee ?? 0);

        $m_h = implode(', ', $months); 
        $description =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) الأشهر " . "{ " . $m_h . " }" . 'لطالب(ة): ' . ($student_name) ;


        $stmt = $conn->prepare("
            INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by, agent_id)
            VALUES (?, NOW(), ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $student_id, $tot_paid, $description, $user_id, $agent_id);
        $stmt->execute();
        
        $receipts_id = $conn->insert_id;
    
        if ($registuration_fee) {
            $des_ins =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) " . " رسوم تسجيل لطالب(ة)  " . ': ' . ($student_name) ;


            // Insert into `combined_transactions`
            $stmt = $conn->prepare("
            INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, agent_id, fund_id)
            VALUES ('plus', ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("ssssssss", $student_id, $des_ins, $registuration_fee, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
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
            $stmt_reg_fee = $conn->prepare("
                INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id, agent_id)
                VALUES ('$des_ins', ?, 'plus', ?, ?, ?, ?, ?)
            ");
            $stmt_reg_fee->bind_param("diiiii", $registuration_fee, $student_id, $fund_d, $bank_accoun_id, $user_id, $agent_id);
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

            $description =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') '. " دفع(ت)  " . "{ " . $description_p . " }" . 'لطالب(ة): ' . ($student_name);
            // Insert into `combined_transactions`
            $stmt = $conn->prepare("
            INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, agent_id, fund_id)
            VALUES ('plus', ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("ssssssss", $student_id, $description, $p_paid, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
            $stmt->execute();

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
                INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id, agent_id)
                VALUES (?, ?, 'plus', ?, ?, ?, ?, ?)
            ");
            $stmt_p_paid->bind_param("sdiiiii", $description, $p_paid, $student_id, $fund_d, $bank_accoun_id, $user_id, $agent_id);
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


                // Fetch the agent's name based on agent_id
                $stmt_agent = $conn->prepare("SELECT agent_name, phone FROM agents WHERE agent_id = ?");
                $stmt_agent->bind_param('i', $agent_id);
                $stmt_agent->execute();
                $result = $stmt_agent->get_result();
                $agent = $result->fetch_assoc();
                $agent_name = $agent['agent_name'];
                $agent_phone = $agent['phone'];
                $stmt_agent->close();
                $des_tion =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ') ' . " دفع(ت) الأشهر " . "{ " . $m_h . " }" . 'لطالب(ة): ' . ($student_name) ;

                // Insert into `combined_transactions`
                $stmt = $conn->prepare("
                    INSERT INTO combined_transactions
                    (description, type, student_id, month, due_amount, paid_amount, remaining_amount, payment_method, bank_id, user_id, agent_id, fund_id)
                    VALUES (?, 'plus', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssssssssss", $des_tion, $student_id, $m_h, $due_amount, $paid_amount, $remaining_amount, $payment_method, $bank_accoun_id, $user_id, $agent_id, $fund_d);
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

                $transaction_type = 'plus';

                // Insert into transactions table
                $stmt_transaction = $conn->prepare("
                    INSERT INTO transactions (transaction_description, amount, transaction_type, fund_id, bank_account_id, student_id, agent_id, user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                // Ensure that the number of types matches the number of variables
                $stmt_transaction->bind_param("sdsiissi", $des_tion, $paid_amount, $transaction_type, $fund_d, $bank_accoun_id, $student_id, $agent_id, $user_id);

                // Execute the statement
                $stmt_transaction->execute();
                $stmt_transaction->close();


        $conn->begin_transaction();
        $success = true;
        $payment_ids = [];

        // Loop through months to insert separate entries
        foreach ($months as $month) {
            // Insert into payments table
            $amount_per_month = $due_amount / count($months);
            $paid_amount_per_month = $paid_amount / count($months);
            $remaining_amount_per_month = $remaining_amount / count($months);

            // Prepare the SQL statement
            $stmt = $conn->prepare("
                INSERT INTO payments (agent_id, student_id, month, due_amount, paid_amount, remaining_amount, bank_id, payment_date, payment_method, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Bind parameters including the `user_id`
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

                $fund_id = null;
                $bank_account_id = null;

                // Update fund or bank balance based on payment method
                if ($payment_method === "نقدي") {
                    $fund_id = 1; // Assuming the fund id is 1
                    $sql = "UPDATE funds SET balance = balance + ? WHERE id = ?";
                    $stmt_fund = $conn->prepare($sql);
                    $stmt_fund->bind_param('di', $paid_amount_per_month, $fund_id);
                    $stmt_fund->execute();
                    $stmt_fund->close();
                } elseif ($payment_method === "بنكي" && $bank_id) {
                    $bank_account_id = $bank_id;
                    $sql = "UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?";
                    $stmt_bank = $conn->prepare($sql);
                    $stmt_bank->bind_param('di', $paid_amount_per_month, $bank_account_id);
                    $stmt_bank->execute();
                    $stmt_bank->close();
                }


            } else {
                // If insertion fails, rollback and exit
                $response['message'] = 'Error: ' . $stmt->error;
                $success = false;
                break;
            }

            $stmt->close(); // Close statement after each insertion
        }

        if ($success) {
            $conn->commit(); // Commit if all went well
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
?>
