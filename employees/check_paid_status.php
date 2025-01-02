<?php
include 'db_connection.php';

$agent_id = $_POST['agent_id'];
$selected_months = $_POST['selected_months'];

$students = [];
$sql_students = "SELECT * FROM students WHERE agent_id = ?";
$stmt_students = $conn->prepare($sql_students);
$stmt_students->bind_param('i', $agent_id);
$stmt_students->execute();
$students_result = $stmt_students->get_result();

while ($row = $students_result->fetch_assoc()) {
    foreach ($selected_months as $month) {
        $sql_check_payment = "SELECT * FROM payments WHERE student_id = ? AND month = ?";
        $stmt_check_payment = $conn->prepare($sql_check_payment);
        $stmt_check_payment->bind_param('is', $row['student_id'], $month);
        $stmt_check_payment->execute();
        $payment_result = $stmt_check_payment->get_result();

        $alreadyPaid = ($payment_result->num_rows > 0);
        $students[] = [
            'name' => $row['student_name'],
            'remaining' => $row['remaining'],
            'already_paid' => $alreadyPaid
        ];

        $stmt_check_payment->close();
    }
}

echo json_encode(['students' => $students]);
?>
