<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$response = ['success' => false, 'message' => ''];

// Get the POST data
$employeeName = $_POST['employeeName'];
$employeePhone = $_POST['employeePhone'];
$subscriptionDate = $_POST['subscriptionDate'];
$terminationReason = $_POST['terminationReason'];
$financialReceivables = isset($_POST['financialReceivables']) ? $_POST['financialReceivables'] : ''; // Financial receivables

// Validate the inputs
if (!empty($employeeName) && !empty($terminationReason)) {
    // Find the employee's ID using their phone number or name
    $query = "SELECT id FROM employees WHERE full_name = ? AND phone = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $employeeName, $employeePhone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        $employeeId = $employee['id'];

        // Insert into the fire_employee table
        $insertQuery = "INSERT INTO fire_employee (employee_id, employee_name, subscription_date, reason, financial_receivables, fire_date) 
                        VALUES (?, ?, ?, ?, ?, CURRENT_DATE)";
        $stmtInsert = $conn->prepare($insertQuery);
        $stmtInsert->bind_param('issss', $employeeId, $employeeName, $subscriptionDate, $terminationReason, $financialReceivables);
        
        if ($stmtInsert->execute()) {
            // Disable foreign key checks temporarily
            $conn->query("SET FOREIGN_KEY_CHECKS=0");

            // Delete the employee from the employees table
            $deleteQuery = "DELETE FROM employees WHERE id = ?";
            $stmtDelete = $conn->prepare($deleteQuery);
            $stmtDelete->bind_param('i', $employeeId);
            
            if ($stmtDelete->execute()) {
                // Success, turn foreign key checks back on
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $response['success'] = true;
                $response['message'] = 'تم فصل الموظف وحذف بياناته بنجاح.';
            } else {
                $response['message'] = 'فشل في حذف الموظف من قاعدة البيانات: ' . $conn->error;
            }
        } else {
            $response['message'] = 'فشل في تسجيل فصل الموظف: ' . $conn->error;
        }
    } else {
        $response['message'] = 'لم يتم العثور على الموظف.';
    }
} else {
    $response['message'] = 'يرجى تقديم جميع البيانات المطلوبة.';
}

// Return the response
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
