<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}



$user_id = $_SESSION['userid'];
$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $year = $row['year_name'];
}

$data = json_decode(file_get_contents('php://input'), true);

$months = $data['months'];
$response = ['success' => false];

// Check if any of the months has already been processed
foreach ($months as $month) {
    $stmt = $conn->prepare("SELECT * FROM processed_salaries WHERE month = ? AND year = ?");
    $stmt->bind_param("ss", $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['message'] = "تمت معالجة الرواتب لشهر $month بالفعل";
        echo json_encode($response);
        exit;
    }
}

// Update employee salaries and record transactions
$conn->begin_transaction();
try {
    foreach ($months as $month) {
        // Update employee salaries
        $salary_update = "UPDATE employees SET balance = balance - salary";
        if (!$conn->query($salary_update)) {
            throw new Exception("فشل تحديث الرواتب: " . $conn->error);
        }

        // Record the processed month
        $stmt = $conn->prepare("INSERT INTO processed_salaries (month, year) VALUES (?, ?)");
        $stmt->bind_param("ss", $month, $year);
        $stmt->execute();
    }

    // Get employee data and record transactions
    $m_hs = implode(', ', $months);
    $result = $conn->query("SELECT id, full_name, salary FROM employees");
    while ($row = $result->fetch_assoc()) {
        $t_d = "إدخال الرواتب: { $m_hs } للموظف(ة) {$row['full_name']}";

        $stmt = $conn->prepare("
            INSERT INTO transactions (transaction_description, amount, transaction_type, employee_id, user_id)
            VALUES (?, ?, 'plus', ?, ?)
        ");
        $stmt->bind_param("ssii", $t_d, $row['salary'],  $row['id'], $user_id); 
        $stmt->execute();
    }

    // Commit the transaction
    $conn->commit();
    $response['success'] = true;
    $response['message'] = "تمت معالجة الرواتب بنجاح";

} catch (Exception $e) {
    // Rollback on failure
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

// Output the response
echo json_encode($response);
$conn->close();
?>
