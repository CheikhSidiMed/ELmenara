<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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

include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

$months1 = [
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
    12 => 'ديسمبر',
];


session_start(); 

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    die("Error: User is not logged in.");
}

// Retrieve the connected user ID from the session
$user_id = $_SESSION['userid'];


$registrationYear = null;
$registrationMonth = null;
$response = []; // Initialize response array
$student_data = null;
$monthsBefore = [];

$paidMonths_with = [];
$paidMonths = [];
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

// Handle GET request (fetch student information)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];

    $sql = "SELECT students.id, students.student_name, classes.class_name, branches.branch_name, students.remaining, students.registration_date
        FROM students
        INNER JOIN classes ON students.class_id = classes.class_id
        INNER JOIN branches ON students.branch_id = branches.branch_id
        WHERE students.id =  ?";

    $stmt = $conn->prepare($sql);
    $likePattern = $student_id;  // Match entries that start with the search term
    $stmt->bind_param('s', $likePattern);  // Bind four parameters
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student_data = $result->fetch_assoc();
        $registrationYear = (int)date('Y', strtotime($student_data['registration_date']));
        $registrationMonth = (int)date('m', strtotime($student_data['registration_date']));
        
        // Déterminer les mois académiques appropriés
        $academicMonths = ($registrationMonth <= $endMonth) ? $endaAcademicMonths : $starAcademicMonths;
        // $academicMonths = ($registrationMonth <= $endMonth) ?  $allAcademicMonths : $endaAcademicMonths;

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

    
        
        // $monthsBefore[] = $monthsArabic[$registrationMonth];

       
        $paidMonthsQuery = "SELECT month FROM payments WHERE student_id = ? ";
        $stmt2 = $conn->prepare($paidMonthsQuery);
        $stmt2->bind_param('i', $student_data['id']);
        $stmt2->execute();
        $paidMonthsResult = $stmt2->get_result();

        // Loop through the result and collect the paid months
        while ($row = $paidMonthsResult->fetch_assoc()) {
            $paidMonths_with[] = $row['month'];
            
        }

        // $paidMonths_with[] = $months1[$registrationMonth];
    $combinedMonths = array_merge($paidMonths_with, $monthsBefore);
    
    $paidMonths = array_unique($combinedMonths);
    // foreach ($academicMonths as $combined){

    //     echo $combined. '<br>';
    // }

        $stmt2->close();
    } else {
        $response = ['success' => false, 'message' => 'No student found with that name.'];
    }
    $stmt->close();
}



// Fetch the bank list for the modal
$bankList = [];
$sql = "SELECT account_id, bank_name FROM bank_accounts";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $bankList[] = $row;
}

// Check if student data is available before fetching the student ID
if ($student_data) {
    $student_id = $student_data['id']; // Ensure this only runs if student data is found

    // Prepare SQL query to sum remaining_amount for the given student_id
    $query = "SELECT SUM(remaining_amount) AS total_remaining FROM payments WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // If no payments are found, default the value to 0.00
    $total_remaining = $row['total_remaining'] ?? 0.00;

    // Close the statement
    $stmt->close();
} else {
    // Handle the case where no student data is found
    $total_remaining = 0.00;
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>محظرة المنارة و الرباط</title>
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/tajawal.css">
    <link rel="stylesheet" href="css/jquery-ui.min.css">

    <style>
       body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }

        .container-main {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 1100px;
            margin: auto;
        }

        .header-title {
            font-family: 'Tajawal', serif;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .header-titlee {
            font-family: 'Tajawal', serif;
            font-size: 1rem;
            font-weight: bold;
        }

        .form-select {
            font-size: 1rem;
            font-weight: bold;
            color: #1a73e8;
        }

        .input-group input {
            border-radius: 5px 0 0 5px;
            padding: 10px;
            border: 2px solid #1a73e8;
        }

        .input-group button {
            border-radius: 0 5px 5px 0;
            background-color: #1a73e8;
            color: #fff;
            padding: 0 20px;
            border: 2px solid #1a73e8;
        }

        .form-section {
            margin-bottom: 20px;
        }

        .form-section .bg-light {
            padding: 10px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            color: #333;
        }

        .form-section label {
            font-weight: bold;
            color: #1a73e8;
            display: block;
            margin-bottom: 5px;
        }

        .form-section div {
            font-size: 1.1rem;
            color: #555;
        }

        .payment-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .payment-info div {
            text-align: center;
            flex: 1;
        }

        .payment-info input,
        .payment-info select {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-top: 5px;
        }

        .months-card {
            background-color: #ffffff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .months-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .months-card .month-option {
            display: flex;
            align-items: center;
        }

        .months-card .month-option label {
            margin-left: 5px;
            font-weight: bold;
            color: #1a73e8;
            font-size: 1rem;
        }

        .confirm-button {
            background-color: #1a73e8;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .confirm-button:hover {
            background-color: #218838;
        }

        .section-title {
            font-family: 'Tajawal', serif;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        .row-underline {
            border-bottom: 2px solid #1a73e8;
            margin-bottom: 20px;
        }

        .icon-left {
            float: left;
            font-size: 1.2rem;
            margin-right: 10px;
            color: #1a73e8;
        }

        .method-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .method-section label {
            font-size: 1.1rem;
            color: #333;
        }

        .method-section select {
            padding: 8px;
            border-radius: 5px;
            border: 2px solid #1a73e8;
            width: 100%;
        }

        #selected-bank-name {
            margin-top: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            color: #1a73e8;
        }

        /* Updated modal styles for better visibility */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent background */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Ensures modal appears above other content */
        }

        .modal-content {
            background-color: #fff; /* White background for the modal */
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 600px; /* Wider modal width */
            max-width: 95%; /* Responsive max width */
        }

        .modal-content h2 {
            margin: 0 0 15px;
            text-align: center;
        }

        .modal-content label {
            display: block;
            margin-bottom: 5px;
        }

        .modal-content input[type="number"],
        .modal-content select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .modal-content .confirm-button {
            background-color: #4CAF50; /* Green background */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%; /* Full width */
            font-size: 1.1rem;
        }

        .modal-content .confirm-button:hover {
            background-color: #45a049; /* Darker green on hover */
        }

        .styled-button {
            background-color: #2980b9; /* Blue background */
            color: white; /* White text */
            padding: 12px 24px; /* Padding for size */
            border: none; /* Remove default border */
            border-radius: 8px; /* Rounded corners */
            font-size: 16px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth hover transition */
        }

        .styled-button:hover {
            background-color: #1a6fa5; /* Darker blue on hover */
            transform: translateY(-2px); /* Slight lift on hover */
        }

        .styled-button:active {
            background-color: #145a85; /* Even darker blue on click */
            transform: translateY(1px); /* Slight push down on click */
        }

    </style>
</head>
<body>

<div class="container-main">
     <!-- Header Section -->
    <div class="row mb-3">
        <!-- <div class="col-12 d-flex justify-content-between align-items-center"> -->
            <div class="col-12 col-lg-6 d-flex align-items-center">
                <h1 class="header-title"><i class="icon-left bi bi-file-earmark-text"></i>تسديد رسوم الطلاب عن طريق الطالب</h1>
            </div>
            <div class="col-12 col-lg-6 d-flex flex-wrap justify-content-lg-end align-items-center mt-3 mt-lg-0">
                <!-- Home Button with Icon -->
                <a href="home.php" class="btn btn-primary d-flex align-items-center" style="margin-left: 15px;">
                    <i class="bi bi-house-door-fill" style="margin-left: 5px;"></i> 
                    الرئيسية
                </a>
                <div class="d-flex align-items-center">
                    <label class="form-label mb-0 me-2 w-25" for="financial-year">السنة المالية</label>
                    <select id="financial-year" class="form-select w-auto">
                        <option><?php echo $last_year; ?></option>
                    </select>
                </div>
            </div>
        <!-- </div> -->
    </div>
    
    <!-- Search Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="header-titlee">البحث عن الطالب</h2>
            <form method="GET" action="">
            <input type="hidden" name="student_id" class="form-control" id="student_id" placeholder="  الإسم الكامل لطالب ">

                <div class="input-group">
                    <input type="text" name="student_name" class="form-control" id="student_name" placeholder="  الإسم الكامل لطالب ">
                    <button class="btn btn-outline-secondary border-2" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Info Section -->
    <?php if (isset($student_data)): ?>
        <div class="container">
    <div class="row form-section">
        <!-- Section Title -->
        <div class="col-12 row-underline">
            <h2 class="section-title">بيانات الطالب (ة):</h2>
        </div>

        <!-- Student Data -->
            <div class="col-12">
                <div class="row gy-3 text-center">
                    <!-- Student ID -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label>رقم التعريف</label>
                        <div><?php echo $student_data['id']; ?></div>
                    </div>

                    <!-- Student Full Name -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label>الاسم الكامل</label>
                        <div><?php echo $student_data['student_name']; ?></div>
                    </div>

                    <!-- Class Name -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label>القسم</label>
                        <div><?php echo $student_data['class_name']; ?></div>
                    </div>

                    <!-- Monthly Fees -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label>الرسوم الشهرية</label>
                        <div id="due-amount"><?php echo $student_data['remaining']; ?> أوقية جديدة</div>
                    </div>

                    <!-- Dues -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label>المستحقات</label>
                        <div id="due-amounte">0 أوقية جديدة</div>
                        <input type="hidden" name="due_amounte" id="due-amount-hidden" value="0">
                    </div>

                    <!-- Arrears -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label>المتأخرات</label>
                        <div><?php echo number_format($total_remaining, 2); ?> أوقية جديدة</div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Payment Information Section -->
    <form method="POST" action="process_payment.php">
        <input type="hidden" name="id" value="<?php echo $student_data['id']; ?>">
        <input type="hidden" name="due_amount" id="due-amount-hidden" value="<?php echo $student_data['remaining']; ?>">
        <input type="hidden" name="due_amounte" id="due-amounte-hidden" value="0">

        <div class="row form-section">
            <div class="col-12 col-lg-6">
                <div class="payment-info" style="border: 1px solid #ddd;" >
                    <div>
                        <label for="arrears-paid">المبلغ المسدد</label>
                        <input type="text" name="paid_amount" id="arrears-paid" placeholder="0.00" oninput="calculateRemaining()">
                    </div>
                    <div>
                        <label for="arrears-remaining">الباقي</label>
                        <input type="text" name="remaining_amount" id="arrears-remaining" placeholder="0.00" readonly>
                    </div>
                </div>

                <div style="border: 1px solid #ddd;" class="payment-info" >
                    <label><input type="checkbox" id="toggleRegFee" onclick="toggleVisibility('reg-fee')">  رسوم الاتسجيل</label>
                    <label><input type="checkbox" id="togglePFee" onclick="toggleVisibility('p-fee')">رسوم اخرى</label>
                </div>

                <div class="payment-info" id="reg-fee" style="display: none; border: 1px solid #ddd;" >
                    <div>
                        <label for="registuration-fee">المبلغ المسدد</label>
                        <input type="text" name="registuration_fee" id="registuration-fee" placeholder="0٫00">
                    </div>
                </div>

                <div class="payment-info" id="p-fee" style="display: none; border: 1px solid #ddd;" >
                    <div>
                        <label for="description-p">الوصف</label>
                        <input type="text" name="description_p" id="description-p" placeholder="الوصف">
                    </div>
                    <div>
                        <label for="p-paid">المبلغ المسدد</label>
                        <input type="text" name="p_paid" id="p-paid" placeholder="0,00">
                    </div>
                </div>


                </div>

                <div style="border: 1px solid #ddd;" class="col-12 col-lg-6 border border-light bg-light months-card">
                    <div class="section-title">الأشهر</div>
                    <div class="months-grid">
                        <?php foreach ($allMonths as $monthKey => $monthName): ?>
                        <div class="month-option">
                            <input type="checkbox" name="month[]" value="<?php echo $monthName; ?>"
                            <?php if (in_array($monthName, $paidMonths)) echo 'checked disabled'; ?>>
                            <label><?php echo $monthName; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="border: 1px solid blue;"  class="method-section mt-3">
                    <div>
                        <label for="method">طريقة الدفع</label>
                        <select id="method" name="payment_method" onchange="toggleBankModal(this.value)">
                            <option value="نقدي">نقدي</option>
                            <option value="بنكي">بنكي</option>
                        </select>
                    </div>
                    <div id="selected-bank-name"></div>
                    <input type="hidden" id="selected-bank-id" name="bank">
                    <button type="submit" class="confirm-button">تأكيد العملية</button>
            </div>
        </div>
    </form>

    <div class="d-flex d-inline justify-content-between">
        <div class="">
            <button id="pay-arrears-button" onclick="openArrearsModal()" class="btn btn-secondary py-2">تسديد المتأخرات</button>
        </div>
        <div class="">
            <form action="student_receipt_all_pay.php" method="POST">
                <input type="hidden" name="student_id" value="<?php echo $student_data['id']; ?>">
                <button type="sabmit" id="pay-arrears-button" class="styled-button">وصل</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>



<!-- Modal Structure -->
<div id="arrears-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content" style="max-width: 700px; margin: auto; background-color: #f9f9f9; border-radius: 10px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);">
        <h2 style="text-align: center; color: #333;">تسديد المتأخرات</h2>
        
        <form id="arrears-form" method="POST" action="process.php">
            <input type="hidden" name="id" value="<?php echo $student_data['id']; ?>">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold;">المتأخرات</label>
                <div id="total-arrears" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;"><?php echo number_format($total_remaining, 2); ?> أوقية جديدة</div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="amount_paid" style="font-weight: bold;">المبلغ المدفوع</label>
                <input type="text" id="amount_paid" name="amount_paid" required onchange="calculer()" style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="remaining_amount" style="font-weight: bold;">الباقي</label>
                <div id="remaining_amount" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;">0.00 أوقية جديدة</div>
            </div>

            <!-- Payment method -->
            <div class="method-section mt-3" style="margin-bottom: 20px;">
                <label for="methodd" style="font-weight: bold;">طريقة الدفع</label>
                <select id="methodd" name="payment_methodd" onchange="toggleBankModalInArrears(this.value)" style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
                    <option value="نقدي">نقدي</option>
                    <option value="بنكي">بنكي</option>
                </select>
                <div id="selected-bank-name-arrears" style="margin-top: 15px; font-size: 1.1rem;"></div>
                <input type="hidden" id="selected-bank-id-arrears" name="bank">
            </div>

            <!-- Confirm and close buttons with added space -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                <button type="submit" class="confirm-button" style="padding: 12px 25px; background-color: #28a745; color: white; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">تأكيد العملية</button>
                <button type="button" onclick="closeArrearsModal()" style="padding: 12px 25px; background-color: #dc3545; color: white; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">إغلاق</button>
            </div>
        </form>
    </div>
</div>

</div>

<!-- Bank Modal -->
<div class="modal fade" id="bankModal" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bankModalLabel">اختر البنك</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <select id="bank" name="bank" class="form-control" onchange="updateSelectedBankName()">
            <?php foreach ($bankList as $bank): ?>
                <option value="<?php echo $bank['account_id']; ?>"><?php echo $bank['bank_name']; ?></option>
            <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="selectBank()">تأكيد</button>
      </div>
    </div>
  </div>
</div>

<!-- Bank Modal -->
<div class="modal fade" id="bankModall" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bankModalLabel">اختر البنك</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <select id="bankk" name="bankk" class="form-control" onchange="updateSelectedBankName()">
            <?php foreach ($bankList as $bank): ?>
                <option value="<?php echo $bank['account_id']; ?>"><?php echo $bank['bank_name']; ?></option>
            <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="toggleBankModal()">تأكيد</button>
      </div>
    </div>
  </div>
</div>


<script src="js/jquery.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/sweetalert2.min.js"></script>


<script>
   $(function() {
    $("#student_name").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "autocomplete.php", // Ensure this path is correct
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function(data) {
                    // Prepare the response data to include names and phones
                    response($.map(data, function(item) {
                        return {
                            label: item.student_name,
                            id: item.student_id,
                            value: item.student_name
                        };
                    }));
                },
                error: function() {
                    console.error('Error fetching autocomplete suggestions.');
                }
            });
        },
        minLength: 1,
        select: function(event, ui) {
            $('#student_name').val(ui.item.value);
            $('#student_id').val(ui.item.id);
            console.log('Selected: ', ui.item.label);
        }
    });
    });



    function calculateRemaining() {
    // Extract the due amount from the div with id "due-amount"
    var dueAmount = parseFloat(document.getElementById('due-amounte').innerText) || 0;
    
    // Get the paid amount from the input field
    var paidAmount = parseFloat(document.getElementById('arrears-paid').value) || 0;
    
    // Calculate the remaining amount
    var remainingAmount = dueAmount - paidAmount;
    
    // Update the remaining amount field
    document.getElementById('arrears-remaining').value = remainingAmount.toFixed(2);
    }


</script>

<script>
    const remainingFee = <?php echo $student_data['remaining']; ?>;
    const dueAmountDisplay = document.getElementById('due-amounte');
    const dueAmountHidden = document.getElementById('due-amounte-hidden');
    const checkboxes = document.querySelectorAll('.months-card input[type="checkbox"]');

    function calculateTotalDue() {
        let selectedMonths = 0;
        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked && !checkbox.disabled) {
                selectedMonths++;
            }
        });

        const totalDue = remainingFee * selectedMonths;
        dueAmountDisplay.innerText = totalDue + ' أوقية جديدة';
        dueAmountHidden.value = totalDue;
    }

    checkboxes.forEach(function(checkbox) {
        if (!checkbox.disabled) {
            checkbox.addEventListener('click', calculateTotalDue);
        }
    });
</script>
<script>
    function toggleVisibility(divId) {
        const element = document.getElementById(divId);
        element.style.display = element.style.display === 'none' ? 'block' : 'none';
    }
</script>

<script>
    function openArrearsModal() {
        document.getElementById('arrears-modal').style.display = 'flex'; // Change to 'flex' for center alignment
    }

    function closeArrearsModal() {
        document.getElementById('arrears-modal').style.display = 'none';
    }


    document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('amount_paid').addEventListener('input', calculer);
        });

    function calculer() {
        const totalArrears = parseFloat(<?php echo $total_remaining; ?>);
        const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
        const remaining = totalArrears - amountPaid;

        document.getElementById('remaining_amount').innerText = remaining >= 0 
            ? remaining.toFixed(2) + ' أوقية جديدة' 
            : '0.00 أوقية جديدة';
    }


   // Function to toggle bank modal inside the arrears modal 
    function toggleBankModalInArrears(paymentMethod) {
    if (paymentMethod === 'بنكي') {
        // Show the bank modal using Bootstrap's Modal component
        const bankModal = new bootstrap.Modal(document.getElementById('bankModall'), {
            keyboard: false
        });
        bankModal.show();

        // Add an event listener for bank selection, if not already added
        const bankSelectElement = document.getElementById('bankk');
        if (!bankSelectElement.hasAttribute('data-listener-added')) {
            bankSelectElement.addEventListener('change', selectBankInArrears);
            bankSelectElement.setAttribute('data-listener-added', 'true'); // Mark listener as added
        }
    } else if (paymentMethod === 'نقدي') {
        // Clear selected bank details if the payment method is 'نقدي'
        clearSelectedBankNameInArrears();
    }
    }
    // Function to select the bank in arrears modal
    function selectBankInArrears() {
        const bankSelect = document.getElementById('bankk');
        const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
        const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
        
        // Set the hidden input with the selected bank ID in arrears modal
        document.getElementById('selected-bank-id-arrears').value = selectedBankId;
        
        // Update the display with the selected bank name in the arrears modal
        document.getElementById('selected-bank-name-arrears').innerText = 'البنك المحدد: ' + selectedBankName;
    }

    // Function to clear bank name in arrears modal
    function clearSelectedBankNameInArrears() {
        document.getElementById('selected-bank-name-arrears').innerText = '';
    }


    // General function for other part of the page (outside the modal)
    function toggleBankModal(paymentMethod) {
        if (paymentMethod === 'بنكي') {
            var bankModal = new bootstrap.Modal(document.getElementById('bankModal'), {
                keyboard: false
            });
            bankModal.show();

            // Add an event listener to ensure the bank selection updates properly
            document.getElementById('bank').addEventListener('change', selectBank);
        } else if (paymentMethod === 'نقدي') {
            clearSelectedBankName();
        }
    }

    function selectBank() {
        const bankSelect = document.getElementById('bank');
        const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
        const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
        
        // Set the hidden input with the selected bank ID in the general section
        document.getElementById('selected-bank-id').value = selectedBankId;
        
        // Update the display with the selected bank name
        document.getElementById('selected-bank-name').innerText = 'البنك المحدد: ' + selectedBankName;
    }

    function clearSelectedBankName() {
        document.getElementById('selected-bank-name').innerText = '';
    }


</script>










</body>
</html>
