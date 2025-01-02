<?php
include 'db_connection.php';

header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data = json_decode(file_get_contents("php://input"), true);
$agent_id = $data['agent_id'];

$response = ['total_remaining' => 0.00];
$total_due_amount = 0.00;

if ($agent_id) {
    $sql_students = "SELECT id, remaining FROM students WHERE agent_id = ? AND remaining != 0.00";
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bind_param("i", $agent_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();

    if ($result_students->num_rows > 0) {
        while ($student = $result_students->fetch_assoc()) {
            $student_id = $student['id'];

            $sql_check_payment = "SELECT SUM(remaining_amount) AS total_remaining FROM payments WHERE student_id = ?";
            $stmt_check_payment = $conn->prepare($sql_check_payment);
            $stmt_check_payment->bind_param("i", $student_id);
            $stmt_check_payment->execute();
            $result = $stmt_check_payment->get_result();

            if ($result) {
                $row = $result->fetch_assoc();
                $total_due_amount += (float) ($row['total_remaining'] ?? 0.00);
            }
        }
    }
}

$conn->close();
$response['total_remaining'] = $total_due_amount;
echo json_encode($response);
?>
