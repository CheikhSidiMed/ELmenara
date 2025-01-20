<?php
include 'db_connection.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$startMonth = 10; // Mois de début de l'année académique
$endMonth = 9; // Mois de fin de l'année académique
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');

// Générer les mois académiques
$starAcademicMonths = [];
$endaAcademicMonths = [];
if ($currentMonth <= $startMonth) {
    for ($month = $startMonth; $month <= 12; $month++) {
        $starAcademicMonths[] = $month;
    }
    for ($month = 1; $month <= $currentMonth; $month++) {
        $endaAcademicMonths[] = $month;
    }
} else {
    for ($month = $startMonth; $month <= $currentMonth; $month++) {
        $starAcademicMonths[] = $month;
    }
}
$allAcademicMonths = array_merge($starAcademicMonths, $endaAcademicMonths);

// Mois en arabe
$allMonths = [
    10 => 'أكتوبر',
    11 => 'نوفمبر',
    12 => 'ديسمبر',
    1 => 'يناير',
    2 => 'فبراير',
    3 => 'مارس',
    4 => 'أبريل',
    5 => 'مايو',
    6 => 'يونيو',
    7 => 'يوليو',
    8 => 'أغسطس',
    9 => 'سبتمبر'
];

// Décoder les données JSON envoyées par la requête
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['months'])) {
    $selected_months = $input['months'];
    $agent_id = $input['agent_id'];

    if (empty($selected_months)) {
        echo json_encode(['error' => 'Aucun mois sélectionné.']);
        exit;
    }

    $selected_months_arabic = array_map(function($month) use ($allMonths) {
        return $allMonths[(int)$month];
    }, $selected_months);

    $total_due_amount = 0;

    // Récupérer les étudiants de l'agent
    $sql_students = "SELECT id, remaining, registration_date FROM students WHERE agent_id = ? AND remaining != 0.00";
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bind_param("i", $agent_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();

    if ($result_students->num_rows > 0) {
        while ($student = $result_students->fetch_assoc()) {
            $student_id = $student['id'];
            $registrationMonth = (int)date('m', strtotime($student['registration_date']));
            $registrationYear = (int)date('Y', strtotime($student['registration_date']));

            // Générer les mois académiques en fonction de la date d'inscription
            $academicMonths = ($registrationMonth <= $endMonth) ? $allAcademicMonths : $starAcademicMonths;

            $monthsBefore = [];
            foreach ($academicMonths as $month) {
                if ($month < $registrationMonth || ($month === $registrationMonth)) {
                    $monthsBefore[] = $allMonths[$month];
                }
            }

            // Trouver les mois restants
            $remaining_months = array_diff($selected_months, $monthsBefore);

            foreach ($remaining_months as $month) {
                $sql_check_payment = "SELECT 1 FROM payments WHERE student_id = ? AND month = ?";
                $stmt_check_payment = $conn->prepare($sql_check_payment);
                $stmt_check_payment->bind_param("is", $student_id, $month);
                $stmt_check_payment->execute();
                $result_check_payment = $stmt_check_payment->get_result();

                if ($result_check_payment->num_rows === 0) {
                    $total_due_amount += (float)$student['remaining'];
                }
            }
        }
    }
    error_log('Total Due: ' . $total_due_amount);
error_log('Selected Months: ' . print_r($selected_months_arabic, true));

    echo json_encode(['total_due' => $total_due_amount, 'selected_months' => $selected_months_arabic]);
} else {
    echo json_encode(['error' => 'Requête invalide ou données manquantes.']);
}
?>
