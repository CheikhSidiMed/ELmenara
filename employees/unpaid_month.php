<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}


$arabicMonths = [
    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس',
    4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
    7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر',
    10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
];

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

    $sql_new = "SELECT s.id, s.student_name, s.registration_date, s.phone, p.month, 
                s.remaining
                FROM students s
                LEFT JOIN payments p ON s.id = p.student_id
                WHERE s.remaining != 0.00
                GROUP BY s.id";


    $stmt_new = $conn->prepare($sql_new);
    $stmt_new->execute();
    $result = $stmt_new->get_result();



$students = [];
while ($row = $result->fetch_assoc()) {
    $registrationDate = $row['registration_date'];
    $registrationYear = (int)date('Y', strtotime($registrationDate));
    $registrationMonth = (int)date('n', strtotime($registrationDate)); 

    $studentName = $row['student_name'];
    if (!isset($students[$studentName])) {
        $students[$studentName] = [
            'phone' => $row['phone'],
            'registration_date' => $registrationDate,
            'registration_month' => $registrationMonth,
            'student_name' => $studentName,
            'remaining' => $row['remaining'],
            'id' => $row['id'],
            'paid_months' => [],
            'unpaid_months' => []
        ];
    }

    if ($row['month']) {
        $students[$studentName]['paid_months'][] = $row['month'];
    }
    
}

foreach ($students as $studentName => &$student) {
    $registrationYear = (int)date('Y', strtotime($student['registration_date']));
    $registrationMonth = (int)$student['registration_month'];
    $academicMonths = ($registrationMonth <= $endMonth) ? $endaAcademicMonths : $allAcademicMonths;

    foreach ($academicMonths as $month) {
        $academicYear = ($month >= $startMonth) ? $currentYear : $currentYear + 1;

        if ($academicYear === $registrationYear && $month <= $registrationMonth) {
            continue;
        }

        if ((int)$month > 0 && isset($arabicMonths[(int)$month]) && !in_array($arabicMonths[(int)$month], $student['paid_months'])) {
            $student['unpaid_months'][] = $arabicMonths[(int)$month];
        }   
        
    }
}
unset($student);

$insertQuery = $conn->prepare("INSERT INTO months_not_paid (student_id, student_name, month_name, remaining_amount, created_at) VALUES (?, ?, ?, ?, NOW())");

foreach ($students as $studentName => $student) {    
    foreach ($student['unpaid_months'] as $unpaidMonth) {
        $insertQuery->bind_param(
            'issd',
            $student['id'],   
            $studentName, 
            $unpaidMonth, 
            $student['remaining'] 
        );
        $insertQuery->execute();
    }
}

$insertQuery->close();


echo "Unpaid months successfully stored in the database.";

$stmt_new->close();
$conn->close();

?>