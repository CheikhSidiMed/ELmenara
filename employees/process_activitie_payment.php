<?php
// Include database connection
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); 

if (!isset($_SESSION['userid'])) {
    die("Error: User is not logged in.");
}

$user_id = $_SESSION['userid'];

$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_activity_id = $_POST['student_activity_id'];
    $act_id = $_POST['student_activitie_id'];
    $paid_amount = $_POST['paid_amount'];
    $payment_method = $_POST['payment_method'];
    $payment_date = date('Y-m-d'); // Store the current date as the payment date
    $bank_id = ($payment_method === "بنكي") ? $_POST['bank'] : null;
    $fund_id =  ($payment_method === "بنكي") ? null : 1;

    if (!empty($student_activity_id) && !empty($paid_amount) && !empty($payment_method)) {
        // Fetch the student and activity details for the transaction description
        $stmt = $conn->prepare("
            SELECT sa.id, a.id AS activitie_id, a.activity_name AS activitie_name, s.student_name 
            FROM student_activities sa
            JOIN activities a ON sa.activity_id = a.id
            JOIN students s ON sa.student_id = s.id
            WHERE s.id = ? and a.id = ?
        ");
        
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("ii", $student_activity_id, $act_id);
        $stmt->execute();
        $stmt->bind_result($student_id, $activitie_id, $activitie_name, $student_name);
        $stmt->fetch();
        $stmt->close();

        // Prepare transaction description
        $transaction_description = "  رسوم الإشتراك في نشاط أو دورة تكوينية " . ' { ' . $activitie_name . ' } ' . " لتلميذ(ة) " . $student_name;

        $stmt = $conn->prepare("
            INSERT INTO activities_payments (student_activity_id, payment_date, paid_amount, payment_method, bank_id, activitie_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("isssss", $student_id, $payment_date, $paid_amount, $payment_method, $bank_id, $act_id);

        if ($stmt->execute()) {
            $payment_id = $stmt->insert_id;


            $stmt = $conn->prepare("
                INSERT INTO receipts (student_id, receipt_date, total_amount, receipt_description, created_by)
                VALUES (?, NOW(), ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $student_id, $paid_amount, $transaction_description, $user_id);
            $stmt->execute();
            $receipts_id = $conn->insert_id;


                // Insert into `combined_transactions`
                $stmt = $conn->prepare("
                INSERT INTO combined_transactions (type, student_id, description, paid_amount, payment_method, bank_id, user_id, fund_id)
                VALUES ('plus', ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param("sssssss", $student_id, $transaction_description, $paid_amount, $payment_method, $bank_id, $user_id, $fund_id);
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


            $stmt = $conn->prepare("
                INSERT INTO transactions (transaction_description, amount, transaction_type, student_id, activitie_id, fund_id, bank_account_id, user_id)
                VALUES (?, ?, 'plus', ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                die("Error preparing statement: " . $conn->error);
            }

            // Determine if the payment method is نقدي or بنكي and set the respective foreign key
            $fund_id = $payment_method === "نقدي" ? 1 : null;
            $bank_account_id = $payment_method === "بنكي" ? $bank_id : null;

            $stmt->bind_param("siisiii", $transaction_description, $paid_amount, $student_id, $activitie_id, $fund_id, $bank_account_id, $user_id);

            if ($stmt->execute()) {
                // Update balance in the respective fund or bank account
                if ($payment_method === "نقدي") {
                    $stmt = $conn->prepare("UPDATE funds SET balance = balance + ? WHERE id = 1");
                    $stmt->bind_param("d", $paid_amount);
                } elseif ($payment_method === "بنكي" && $bank_id) {
                    $stmt = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_id = ?");
                    $stmt->bind_param("di", $paid_amount, $bank_id);
                }

                if (!$stmt) {
                    die("Error preparing balance update statement: " . $conn->error);
                }

                if ($stmt->execute()) {
                    // Redirect to the receipt page
                    header("Location: print_receipt.php?receipt_id=$receipts_id");
                    exit();
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'حدث خطأ أثناء تحديث الرصيد: ' . $stmt->error
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تسجيل المعاملة: ' . $stmt->error
                ];
            }

            $stmt->close();
        } else {
            $response = [
                'success' => false,
                'message' => 'حدث خطأ أثناء تسديد رسوم النشاط: ' . $stmt->error
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'يرجى ملء جميع الحقول المطلوبة.'
        ];
    }

    $conn->close();
}
?>
