<?php
// Include the database connection file
include 'db_connection.php';


session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form inputs
    $account_number = trim($_POST['account_number']);
    $account_name = trim($_POST['account_name']);
    $category = trim($_POST['category']);
    $account_balance = trim($_POST['account_balance']);

    // Input validation
    if (empty($account_number) || empty($account_name) || empty($category) || !is_numeric($account_balance)) {
        $message = 'يرجى تعبئة جميع الحقول بشكل صحيح.';
    } else {
        // Check if the account_number already exists in the database
        $sql_check = "SELECT 1 FROM offer_accounts WHERE account_number = ?";
        $stmt_check = $conn->prepare($sql_check);
        
        if ($stmt_check) {
            $stmt_check->bind_param('s', $account_number);
            $stmt_check->execute();
            $stmt_check->store_result();

            // If account_number exists, show an error message
            if ($stmt_check->num_rows > 0) {
                $message = 'رقم الحساب موجود بالفعل.';
            } else {
                $sql = "INSERT INTO offer_accounts (account_number, account_name, category, account_balance) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    // Bind parameters to prevent SQL injection
                    $stmt->bind_param('sssd', $account_number, $account_name, $category, $account_balance);

                    if ($stmt->execute()) {
                        // On successful insertion, set success message
                        $message = 'تم إضافة الحساب بنجاح.';
                    } else {
                        $message = 'حدث خطأ أثناء إضافة الحساب.';
                    }

                    $stmt->close();
                } else {
                    $message = 'حدث خطأ في الاتصال بقاعدة البيانات.';
                    // error_log($conn->error); // Log the error for debugging
                }
            }

            $stmt_check->close();
        } else {
            $message = 'حدث خطأ في الاتصال بقاعدة البيانات.';
            // error_log($conn->error); // Log the error for debugging
        }
    }

    // Store the message in the session
    $_SESSION['message'] = $message;
    header('Location: manage_khadamat.php');
}

$conn->close();
?>
