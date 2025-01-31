<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Include database connection
include 'db_connection.php';


session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Retrieve the connected user ID from the session
$user_id = $_SESSION['userid'];

$response = []; // Initialize response array

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $due_amounte = $_POST['due_amounte'];
    $student_id = $_POST['id'];
    $due_amount = $_POST['due_amount'];
    $paid_amount = $_POST['paid_amount'];
    $remaining_amount = $_POST['remaining_amount'];
    $payment_method = $_POST['payment_method'];
    $months = $_POST['month'];
    

    
    // Debugging: Check if the bank_id is set correctly
    $bank_id = ($payment_method === "بنكي") ? $_POST['bank'] : null;
    $fund_id = ($payment_method === "نقدي") ? 1 : null; // Assume fund_id = 1 for the main fund

    if ($payment_method === "بنكي" && !$bank_id) {
        die("Bank ID not set. Please select a bank.");
    }

    // Validate inputs
    if (!empty($student_id) && !empty($_POST['month']) && !empty($due_amount) && !empty($paid_amount)) {
        $months_paid = []; 
        foreach ($months as $month) {
            $paid_amount_per_month = $paid_amount / count($months);
            // Prepare and bind the payment insert statement
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
                $stmt_student = $conn->prepare("SELECT student_name FROM students WHERE id = ?");
                $stmt_student->bind_param("i", $student_id);
                $stmt_student->execute();
                $result_student = $stmt_student->get_result();
                $student_name = $result_student->fetch_assoc()['student_name'];
                $stmt_student->close();

                // Prepare the transaction description
                $transaction_description = "تسديد رسوم : ($student_name) ل شهر $month";

                // Insert into the transactions table
                $stmt_transaction = $conn->prepare("
                    INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, fund_id, bank_account_id, user_id)
                    VALUES (?, ?, 'plus', ?, ?, ?, ?)
                ");
                $stmt_transaction->bind_param("sdiiii", $transaction_description, $paid_amount_per_month, $student_id, $fund_id, $bank_id, $user_id);

                // Execute the transaction insert
                if (!$stmt_transaction->execute()) {
                    $response = ['success' => false, 'message' => 'حدث خطأ أثناء معالجة الدفع: ' . $stmt_transaction->error];
                    break;  // Stop on the first error
                }
                $stmt_transaction->close();

                // Update the balance for the fund or bank
                if ($payment_method === "نقدي") {
                    $stmt_fund = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
                    $stmt_fund->bind_param("d", $paid_amount);
                    if (!$stmt_fund->execute()) {
                        $response = ['success' => false, 'message' => 'حدث خطأ أثناء تحديث رصيد الصندوق: ' . $stmt_fund->error];
                        break;  // Stop on the first error
                    }
                    $stmt_fund->close();
                } elseif ($payment_method === "بنكي" && $bank_id) {
                    $stmt_bank = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
                    $stmt_bank->bind_param("di", $paid_amount, $bank_id);
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

        if (empty($response)) {  // No errors
            // Redirect to student_receipt.php with necessary data
            $months_paid_str = implode(' • ', $months_paid); // Join months with ' • ' separator
            $redirect_url = "student_receipt.php?receipt_id=$receipt_id&student_id=$student_id&payment_method=$payment_method&paid_amount=$paid_amount&remaining_amount=$remaining_amount&months_paid=$months_paid_str&due_amounte=$due_amounte";
            header("Location: $redirect_url");
            exit();
        }
    } else {
        $response = ['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة.'];
    }
}

// Close the database connection
$conn->close();
?>
