
<?php
include 'db_connection.php';

// if (isset($_POST['agent_id']) && isset($_POST['student_id'])) {
//     $agent_id = $_POST['agent_id'];
//     $student_id = $_POST['student_id'];
    

//     // Prepare and execute the SQL query
//     $sql_paid_months = "SELECT month FROM payments WHERE student_id = ?";
//     $stmt_paid_months = $conn->prepare($sql_paid_months);
//     $stmt_paid_months->bind_param('i', $student_id);
//     $stmt_paid_months->execute();
//     $paidMonthsResult = $stmt_paid_months->get_result();

//     $paidMonths = [];
//     while ($row = $paidMonthsResult->fetch_assoc()) {
//         $paidMonths[] = $row['month'];
//     }

//     // Close the statement and connection
//     $stmt_paid_months->close();
//     $conn->close();

//     // Return JSON-encoded data
//     echo json_encode($paidMonths);
// }
















// include 'db_connection.php';

// Step 1: Extract year and month using substr()
$currentDate = date('Y-m-d'); // Today's date in 'YYYY-MM-DD' format
$currentMonth = date('m'); // Current month (01-12)
$currentDay = date('d'); // Current day (01-31)
$allMonths = [
    'January' => 'يناير',
    'February' => 'فبراير',
    'March' => 'مارس',
    'April' => 'أبريل',
    'May' => 'مايو',
    'June' => 'يونيو',
    'July' => 'يوليو',
    'August' => 'أغسطس',
    'September' => 'سبتمبر',
    'October' => 'أكتوبر',
    'November' => 'نوفمبر',
    'December' => 'ديسمبر'
];
$paidMonths_with = [];


if (isset($_POST['agent_id']) && isset($_POST['student_id'])) {
    $agent_id = $_POST['agent_id'];
    $student_id = $_POST['student_id'];
    
        // Prepare and execute the SQL query
    $sql_std = "SELECT registration_date FROM students WHERE id = ?";
    $stmt_std = $conn->prepare($sql_std);
    $stmt_std->bind_param('i', $student_id);
    $stmt_std->execute();
    $paidStdResult = $stmt_std->get_result();
    
    $student_data = $paidStdResult->fetch_assoc();
    $registrationYear = date('Y', strtotime($student_data['registration_date']));
    $registrationMonth = date('m', strtotime($student_data['registration_date']));
        
    $monthsBefore = [];
    $monthNames = array_keys($allMonths); // Get the English month names (keys)
        
    if ($currentMonth == $registrationMonth && $currentDay >= (int)substr($registration_date, 8, 2)) {
            // If it's the same month and the day has passed, include the registration month
        $monthsBefore[] = $allMonths[$monthNames[(int)$registrationMonth - 1]];
    }
        
        // Add all months before the registration month
    for ($i = 0; $i < (int)$registrationMonth - 1; $i++) {
        $monthsBefore[] = $allMonths[$monthNames[$i]]; // Use the Arabic name
    }

    // Prepare and execute the SQL query
    $sql_paid_months = "SELECT month FROM payments WHERE student_id = ?";
    $stmt_paid_months = $conn->prepare($sql_paid_months);
    $stmt_paid_months->bind_param('i', $student_id);
    $stmt_paid_months->execute();
    $paidMonthsResult = $stmt_paid_months->get_result();

    $paidMonths = [];
    while ($row = $paidMonthsResult->fetch_assoc()) {
        $paidMonths_with[] = $row['month'];
    }

    // Step 6: Merge paidMonths with monthsBefore
    $combinedMonths = array_merge($paidMonths_with, $monthsBefore);
    // Optional: Remove duplicates if needed
    $paidMonths = array_unique($combinedMonths);
    // Close the statement and connection
    $stmt_paid_months->close();
    $stmt_std->close();
    $conn->close();

    // Return JSON-encoded data
    echo json_encode($paidMonths);
}







?>
