<?php
include 'db_connection.php';

header('Content-Type: application/json');

// Décoder les données JSON envoyées par la requête Fetch
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['months'])) {
    $selected_months = $input['months'];
    $agent_id = 241;

    $total_due_amount = 0;

    if (empty($selected_months)) {
        echo json_encode(['error' => 'Aucun mois sélectionné.']);
        exit;
    }

    $sql_students = "SELECT id, remaining FROM students WHERE agent_id = ? AND remaining != 0.00";
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bind_param("i", $agent_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();

    if ($result_students->num_rows > 0) {
        while ($student = $result_students->fetch_assoc()) {
            $student_id = $student['id'];

            foreach ($selected_months as $month) {
                $sql_check_payment = "SELECT 1 FROM payments WHERE student_id = ? AND month = ?";
                $stmt_check_payment = $conn->prepare($sql_check_payment);
                $stmt_check_payment->bind_param("is", $student_id, trim($month));
                $stmt_check_payment->execute();
                $result_check_payment = $stmt_check_payment->get_result();

                if ($result_check_payment->num_rows === 0) {
                    $total_due_amount += (float)$student['remaining'];
                }
            }
        }
    }

    echo json_encode(['total_due' => $total_due_amount, 'selected_months' => $selected_months]);
} else {
    echo json_encode(['error' => 'Requête invalide ou données manquantes.']);
}
?>
