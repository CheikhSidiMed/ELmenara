<?php
// Include your database connection file
require_once 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

session_start(); // Ensure the session is started

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    exit;
}

$receipts_id = '';
$user_id = $_SESSION['userid'];

// Retrieve form data
$student_id = $_POST['student_d'] ?? null;
$amount_paid = $_POST['amount_paidd'] ?? null;
$agent_name = $_POST['agent_name'] ?? null;
$agent_id = $_POST['agent_id'] ?? null;

$payment_method = $_POST['payment_methodd'] ?? null;


$bank_id = ($payment_method === "بنكي") ? $_POST['bank'] : null;
$fund_id = ($payment_method === "نقدي") ? 1 : null;



// Check if required values are provided
if (empty($student_id) || empty($amount_paid)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data. Please fill all fields.']);
    exit;
}

// Start a transaction to ensure data consistency
mysqli_begin_transaction($conn);

try {
    // 1. Count the number of payment rows for the specific student where remaining_amount != 0.00
    $query_count = "SELECT COUNT(*) AS row_count FROM payments WHERE student_id = ? AND remaining_amount != 0.00";
    $stmt_count = $conn->prepare($query_count);
    $stmt_count->bind_param("i", $student_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row = $result_count->fetch_assoc();
    $row_count = $row['row_count'] ?? 0;

    if ($row_count == 0) {
        echo json_encode(['status' => 'error', 'message' => 'No arrears found for this student.']);
        mysqli_rollback($conn);
        exit();
    }

    // 2. Calculate the amount to subtract per row
    $amount_per_row = $amount_paid / $row_count;

    // 3. Update remaining_amount for each payment row where student_id is present and remaining_amount != 0.00
    $query_update = "UPDATE payments SET remaining_amount = remaining_amount - ? WHERE student_id = ? AND remaining_amount != 0.00";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param("di", $amount_per_row, $student_id);

    // Execute the update for all rows
    $stmt_update->execute();





    $query_count_ct = "SELECT COUNT(*) AS row_count FROM combined_transactions WHERE student_id = ? AND remaining_amount != 0.00";
    $stmt_count_ct = $conn->prepare($query_count_ct);
    $stmt_count_ct->bind_param("i", $student_id);
    $stmt_count_ct->execute();
    $result_count_ct = $stmt_count_ct->get_result();
    $row_ct = $result_count_ct->fetch_assoc();
    $row_count_ct = $row_ct['row_count'] ?? 0;

    if ($row_count_ct == 0) {
        echo json_encode(['status' => 'error', 'message' => 'No arrears found for this student.']);
        mysqli_rollback($conn);
        exit();
    }

    // 2. Calculate the amount to subtract per row
    $amount_per_row_ct = $amount_paid / $row_count;

    $query_update_ct = "UPDATE combined_transactions SET remaining_amount = remaining_amount - ? WHERE student_id = ? AND remaining_amount != 0.00";
    $stmt_update_ct = $conn->prepare($query_update_ct);
    $stmt_update_ct->bind_param("di", $amount_per_row_ct, $student_id);
    $stmt_update_ct->execute();





    if ($stmt_update->affected_rows > 0) {
        // Fetch the student name for the transaction description
        $stmt_student = $conn->prepare("SELECT student_name FROM students WHERE id = ?");
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $result_student = $stmt_student->get_result();
        $student_name = $result_student->fetch_assoc()['student_name'];
        $stmt_student->close();

        $stmt_agent = $conn->prepare("SELECT agent_name, phone FROM agents WHERE agent_id = ?");
        $stmt_agent->bind_param("i", $agent_id);
        $stmt_agent->execute();
        $result = $stmt_agent->get_result();
        $agent = $result->fetch_assoc();
        $agent_name = $agent['agent_name'];
        $agent_phone = $agent['phone'];
        $stmt_agent->close();

        // Prepare the transaction description
        $transaction_description =  "الوكيل(ة): " . $agent_name . '(' . $agent_phone . ' ) ' . " سدد(ت) متأخرات " . "  لطالب(ة)" . ': ' . ($student_name) ;


        $stmt = $conn->prepare("
        INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by, agent_id)
        VALUES (?, NOW(), ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $student_id, $amount_paid, $transaction_description, $user_id, $agent_id);
        $stmt->execute();
    
        $receipts_id = $conn->insert_id;

                // Insert into `combined_transactions`
                $stmt = $conn->prepare("
                INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, agent_id, fund_id)
                VALUES ('plus', ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param("ssssssss", $student_id, $transaction_description, $amount_paid, $payment_method, $bank_id, $user_id, $agent_id, $fund_id);
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

        // Insert into the transactions table
        $stmt_transaction = $conn->prepare("
            INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id)
            VALUES (?, ?, 'plus', ?, ?, ?, ?)
        ");
        $stmt_transaction->bind_param("sdiiii", $transaction_description, $amount_paid, $student_id, $fund_id, $bank_id, $user_id);

        // Execute the transaction insert
        if (!$stmt_transaction->execute()) {
            throw new Exception("Error processing payment: " . $stmt_transaction->error);
        }
        $payment_id = $stmt_transaction->insert_id; // Get the inserted ID for future use

        $fund_id = null;
                $bank_account_id = null;

                // Update fund or bank balance based on payment method                          
                if ($payment_method === "نقدي") {
                    $fund_id = 1; // Assuming the fund id is 1
                    $sql = "UPDATE funds SET balance = balance + ? WHERE id = ?";
                    $stmt_fund = $conn->prepare($sql);
                    $stmt_fund->bind_param('di', $amount_paid, $fund_id);
                    $stmt_fund->execute();
                    $stmt_fund->close();
                } elseif ($payment_method === "بنكي" && $bank_id) {
                    $bank_account_id = $bank_id;
                    $sql = "UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?";
                    $stmt_bank = $conn->prepare($sql);
                    $stmt_bank->bind_param('di', $amount_paid, $bank_account_id);
                    $stmt_bank->execute();
                    $stmt_bank->close();
                }
        $stmt_transaction->close();

        mysqli_commit($conn); // Commit transaction if successful
        header("Location: agent_receipt_re.php?payment_id=" . $receipts_id . "&agent_name=" . urlencode($agent_name));
                 // echo json_encode(['status' => 'success', 'message' => '.تم تسديد المتأخرات بنجاح']);
    } else {
        throw new Exception("No rows were updated during payment processing.");
    }
} catch (Exception $e) {
    // Rollback in case of an error
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    // Close the statements and connection
    $stmt_count->close();
    $stmt_update->close();
    $conn->close();
}
?>
