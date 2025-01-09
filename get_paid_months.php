<?php
include 'db_connection.php';



$startMonth = 10;
$endMonth = 9; 
$currentYear = (int)date('Y'); 
$currentMonth = (int)date('m');

$starAcademicMonths = [];
$endaAcademicMonths = [];
if($currentMonth <= $startMonth){
    for ($month = $startMonth; $month <= 12; $month++) {
        $starAcademicMonths[] = $month;
    }
} else{
    for ($month = $startMonth; $month <= $currentMonth; $month++) {
        $starAcademicMonths[] = $month;
    }
}

if ($currentMonth <= $startMonth) {
    for ($month = 1; $month <= $currentMonth; $month++) {
        $endaAcademicMonths[] = $month;
    }
} else {
    $endaAcademicMonths[] = [];
}
$allAcademicMonths = array_merge($starAcademicMonths, $endaAcademicMonths);


$monthsArabic = [
    1 => 'يناير',
    2 => 'فبراير',
    3 => 'مارس',
    4 => 'أبريل',
    5 => 'مايو',
    6 => 'يونيو',
    7 => 'يوليو',
    8 => 'أغسطس',
    9 => 'سبتمبر',
    10 => 'أكتوبر',
    11 => 'نوفمبر',
    12 => 'ديسمبر'
];


$allMonths = [ 
    'October' => 'أكتوبر',
    'November' => 'نوفمبر',
    'December' => 'ديسمبر',
    'January' => 'يناير',
    'February' => 'فبراير',
    'March' => 'مارس',
    'April' => 'أبريل',
    'May' => 'مايو',
    'June' => 'يونيو',
    'July' => 'يوليو',
    'August' => 'أغسطس',
    'September' => 'سبتمبر'
];

$paidMonths_with = [];
$monthsBefore = [];

if (isset($_POST['agent_id']) && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];

    // Get the student's registration date
    $sql_std = "SELECT registration_date FROM students WHERE id = ?";
    $stmt_std = $conn->prepare($sql_std);
    $stmt_std->bind_param('i', $student_id);
    $stmt_std->execute();
    $paidStdResult = $stmt_std->get_result();

    if ($student_data = $paidStdResult->fetch_assoc()) {
        $registration_date = $student_data['registration_date'];
        
        $registrationYear = (int)date('Y', strtotime($student_data['registration_date']));
        $registrationMonth = (int)date('m', strtotime($student_data['registration_date']));
        
        // Déterminer les mois académiques appropriés
        $academicMonths = ($registrationMonth <= $endMonth) ? $endaAcademicMonths : $starAcademicMonths;
        
        $monthsBefore = [];
        $monthsBefore1 = [];
        $monthsBefore2 = [];

        foreach ($academicMonths as $month) {
            $academicYear = ($month >= $startMonth) ? $currentYear : $currentYear + 1;        
            if ($month <= $registrationMonth ) {
                $monthsBefore1[] = $monthsArabic[$month];
            }
        }
        if ($registrationMonth < $startMonth) {
            foreach ($starAcademicMonths as $month) {
                $monthsBefore2[] = $monthsArabic[$month];

            }
        } 
        $monthsBefore = array_merge($monthsBefore1, $monthsBefore2);


        // Fetch paid months from the database
        $sql_paid_months = "SELECT month FROM payments WHERE student_id = ?";
        $stmt_paid_months = $conn->prepare($sql_paid_months);
        $stmt_paid_months->bind_param('i', $student_id);
        $stmt_paid_months->execute();
        $paidMonthsResult = $stmt_paid_months->get_result();

        while ($row = $paidMonthsResult->fetch_assoc()) {
            $paidMonths_with[] = $row['month'];
        }

        // Merge and remove duplicate months
        $combinedMonths = array_unique(array_merge($monthsBefore, $paidMonths_with));

    } else {
        $combinedMonths = []; // Return an empty array if no registration date found
    }

    // Close statements and connection
    $stmt_paid_months->close();
    $stmt_std->close();
    $conn->close();
    $paidMonths = array_values($combinedMonths);

    // Return JSON-encoded data
    echo json_encode($paidMonths);
}
?>
