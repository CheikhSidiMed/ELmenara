<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


include 'db_connection.php';

$months = ['يونيو', 'مايو'];
$placeholders = rtrim(str_repeat('?,', count($months)), ','); // Creates the placeholders for months
$monthsA = $months; 
$agent_id = 341;
$paid_amount = 3400.00;

// SQL query to select students with unpaid months
$sql_s = "SELECT s.id, s.student_name FROM students s WHERE s.agent_id = ? AND s.remaining > 0.00";
$stmt_s = $conn->prepare($sql_s);

$stmt_s->bind_param('i', $agent_id);
$stmt_s->execute();
$result_s = $stmt_s->get_result();

if ($result_s->num_rows > 0) {
    $success = true;
    $st_ct = $result_s->num_rows;
    $result_array = $result_s->fetch_all(MYSQLI_ASSOC);

    $rem_pay = $paid_amount;

    foreach ($result_array as $row) {
        $student_name = $row['student_name'];
        $student_id = $row['id'];

        $check_stmt = $conn->prepare("SELECT SUM(remaining_amount) AS total_remaining, count(*) AS count FROM payments WHERE student_id = ? AND remaining_amount > 0.00");
        $check_stmt->bind_param("i", $student_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        $total_rem = $result['total_remaining'];
        $count = $result['count'];
        $check_stmt->close();

        if ($count == 0) {
            continue;
        }


        
        $am_t = min($rem_pay, $total_rem);
        
        $amount_per_row = $am_t / $count;

        echo $student_id . ' - '. $amount_per_row . ' _ ' . '<br>';
        $rem_pay -= $total_rem;

        if ($rem_pay <= 0) {
                break;
        }

        $conn->commit();

    }
} else {
    echo "No eligible students found.";
}
?>
