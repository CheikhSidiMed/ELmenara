<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}


$agent_data = [];
$students_data = [];
$paidMonths = [];
$monthly_fee = 0;

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

$commonMonths = [];
$student_s = [];

$finalMonths = [];

$academicMonths = [];
$total_due_amount = 0.00;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['phone'];
    $agent_idd = null;
     $sql = "SELECT * FROM agents WHERE phone = ?";
     $stmt = $conn->prepare($sql);
     $stmt->bind_param('s', $phone);
     $stmt->execute();
     $result = $stmt->get_result();
     if ($result->num_rows > 0) {
         $agent_data = $result->fetch_assoc();
 
         $sql_students = "SELECT s.id, s.registration_date, s.student_name, b.branch_name, c.class_name, s.fees, s.remaining
            FROM students s
            LEFT JOIN branches b ON s.branch_id = b.branch_id
            LEFT JOIN levels l ON s.level_id = l.id
            LEFT JOIN classes c ON s.class_id = c.class_id
            WHERE s.agent_id = ?";
         $stmt_students = $conn->prepare($sql_students);
         $stmt_students->bind_param('i', $agent_data['agent_id']);
         $stmt_students->execute();
         $students_result = $stmt_students->get_result();
         $agent_idd = $agent_data['agent_id'];
         while ($row = $students_result->fetch_assoc()) {
            $student_s[] = $row;
            $students_data[] = [
                'id' => $row['id'],
                'student_name' => $row['student_name'],
                'fees' => $row['fees'],
                'registration_date' => $row['registration_date'],
                'branch_name' => $row['branch_name'],
                'class_name' => $row['class_name'],
                'remaining' => $row['remaining']
            ];

         }
        $stmt_students->close();

        $student_ids = array_column($student_s, 'id');
        $student_reg_dates = array_column($student_s, 'registration_date');
        $placeholders = implode(',', array_fill(0, count($student_ids), '?'));

        $sql_paid_months = "SELECT student_id, month FROM payments WHERE student_id IN ($placeholders)";
        $stmt_paid_months = $conn->prepare($sql_paid_months);
        $stmt_paid_months->bind_param(str_repeat('i', count($student_ids)), ...$student_ids);
        $stmt_paid_months->execute();
        $result_paid_months = $stmt_paid_months->get_result();

        $paidMonths_with = [];
        while ($row = $result_paid_months->fetch_assoc()) {
            $paidMonths_with[$row['student_id']][] = $row['month'];
        }
        $academicMonths = [];
        foreach ($student_s as $student) {
            $registrationYear = (int)date('Y', strtotime($student['registration_date']));
            $registrationMonth = (int)date('m', strtotime($student['registration_date']));
            $academicMonths = ($registrationMonth <= $endMonth) ? $allAcademicMonths : $starAcademicMonths;
            
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
            $academicMonths[$student['id']] = $monthsBefore;

            $student_id = $student['id'];
            $monthsBefore = $academicMonths[$student_id] ?? [];
            $paidMonths = $paidMonths_with[$student_id] ?? [];
                   
            $combinedMonths = array_unique(array_merge($monthsBefore, $paidMonths));
            $finalMonths[$student_id] = $combinedMonths;

        }
        
        $commonMonths = array_values(reset($finalMonths));
        
        foreach ($finalMonths as $studentID => $months) {
            $commonMonths = array_intersect($commonMonths, $months);
            if (empty($commonMonths)) {
                break;
            }
        }

     } else {
         echo "Agent not found.";
     }


    if ($agent_idd) {
        $sql_students = "SELECT id, remaining FROM students WHERE agent_id = ? AND remaining != 0.00";
        $stmt_students = $conn->prepare($sql_students);
        $stmt_students->bind_param("i", $agent_idd);
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
 
     $stmt->close();
 }
$bankList = [];
$sql = "SELECT account_id, bank_name FROM bank_accounts";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $bankList[] = $row;
}

$conn->close();

 ?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسديد رسوم الطالب عن طريق الوكيل</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/jquery-base-ui.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tajawal.css">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">

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
            font-weight: bold;
            color: #1a73e8;
            font-size: 0.8rem;
        }
        .confirm-button {
            background-color: #28a745;
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
        /* Linked Students and Agent Info */
        .linked-students, .agent-info {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .linked-students .btn {
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            width: 100%;
            font-weight: bold;
            color: #1a73e8;
        }
        .linked-students .btn:hover {
            background-color: #e0e0e0;
        }
        .agent-info p {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        .students-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        }

        .student-option {
            display: flex;
            align-items: center;
            justify-content: center;
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

        input[readonly] {
            pointer-events: none;
            opacity: 0.6;
            accent-color: red;        }
    </style>
</head>
<body>

<div class="container-main">
 <!-- Header Section -->
<div class="container">
    <div class="row mb-3">
        <!-- Header Title -->
        <div class="col-12 col-lg-6 d-flex align-items-center">
            <h1 class="header-title">
                <i class="icon-left bi bi-file-earmark-text"></i> تسديد رسوم الطالب عن طريق الوكيل
            </h1>
        </div>
        <!-- Actions -->
        <div class="col-12 col-lg-6 d-flex flex-wrap justify-content-lg-end align-items-center mt-3 mt-lg-0">
            <!-- Home Button with Icon -->
            <a href="home.php" class="btn btn-primary d-flex align-items-center mb-2 mb-lg-0 me-lg-3">
                <i class="bi bi-house-door-fill ms-2"></i> الرئيسية
            </a>
            <!-- Financial Year Select -->
            <div class="d-flex align-items-center">
                <label class="form-label mb-0 me-2 w-25" for="financial-year">السنة المالية</label>
                <select id="financial-year" class="form-select w-auto">
                    <option><?php echo $last_year; ?></option>
                </select>
            </div>
        </div>
    </div>
</div>



    
 <!-- Search Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="header-titlee">البحث عن الوكيل</h2>
            <form method="POST" action="">
                <div class="input-group">
                    <input type="text" name="phone" class="form-control" id="phone" placeholder="رقم الهاتف (الرقم الشخصي أو الاسم)">
                    <button class="btn btn-outline-secondary border-2" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>



    <?php if (!empty($agent_data)): ?>
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="linked-students">
                    <div class="section-title">الطلبة المرتبطين:</div>
                    <div class=" col-auto">
                        <table class="table table-bordered rounded">
                            <thead>
                                <tr class="thead-light">
                                    <th scope="col">#</th>
                                    <th scope="col">الاسم</th>
                                    <th scope="col">الفرع</th>
                                    <th scope="col">القسم</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students_data as $index => $student): ?>
                                    <tr class="text-2">
                                        <th scope="row"><?php echo $index + 1; ?></th>
                                        <td><?php echo $student['student_name']; ?></td>
                                        <td><?php echo $student['branch_name']; ?></td>
                                        <td><?php echo $student['class_name']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="agent-info">
                    <div class="section-title">معلومات الوكيل:</div>
                    <p>رقم التعريف: <?php echo $agent_data['agent_id']; ?></p>
                    <p>الاسم: <?php echo $agent_data['agent_name']; ?></p>
                    <p>رقم الهاتف: <?php echo $agent_data['phone']; ?></p>
                </div>
            </div>
        </div>
    <?php if (!empty($agent_data)): ?>
        <script>
            function fetchPaidMonths(totalRemaining) {
                document.getElementById('total-remaining').value = totalRemaining.toFixed(2);
                document.getElementById('total-remaining-input').value = totalRemaining.toFixed(2);
            }
            document.addEventListener('DOMContentLoaded', function() {
                fetchPaidMonths(<?php echo json_encode($total_due_amount); ?>);
            });
        </script>
    <?php endif ?>

    <!-- Payment Information Section -->
<form method="POST" action="process_agent_payment.php">

    <input type="hidden" name="id" value="<?php echo $agent_data['agent_id']; ?>">
    <input type="hidden" id="remaining" name="remaining" readonly>
    <input type="hidden" id="months_s" name="monthss[]" readonly>
    <div class="row form-section">
        <div class="row align-align-items-baseline">
            <div class="col-12 col-lg-6">
                <div class="payment-info">
                    <div>
                        <label for="due-amount">المستحقات</label>
                        <input type="text" name="due_amount" id="due-amount" value="0.00" readonly>
                    </div>
                    <div>
                        <label for="paid-amount">المبلغ المسدد</label>
                        <input type="text" name="paid_amount" id="arrears-paid" placeholder="0.00" oninput="calculateRemaining()">
                    </div>
                    <div>
                        <label for="remaining-amount">الباقي</label>
                        <input type="text" name="remaining_amount" id="arrears-remaining" placeholder="0.00" readonly>
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <div id="total-remaining-container" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;">
                        <label for="total-remaining">  المتأخرات </label>
                        <input type="text" id="total-remaining" name="due_amount" value="0.00" disabled style="border: none; background-color: transparent; outline: none; color: #555; font-size: 1.1rem;">
                    </div>
                </div>
            </div>

            <!-- HTML to Display Checkboxes and Total Amount Due -->
            
                <div class="col-12 col-lg-6 months-card border border-light  bg-light">
                    <div class="section-title">الأشهر</div>
                    <div class="months-grid" id="months-grid">
                    <?php foreach ($allMonths as $monthKey => $monthName):
                        $isPaid = in_array($monthName, $commonMonths);
                    ?>
                        <div class="month-option">
                            <input type="checkbox"
                                name="months[]"
                                value="<?php echo $monthName; ?>"
                                id="month-<?php echo $monthKey; ?>"
                                <?php echo $isPaid ? 'checked readonly' : ''; ?>
                                data-month-fee="<?php echo $monthly_fee; ?>"
                                onclick="updateDueAmount(<?php echo $agent_data['agent_id']; ?>)"/>
                            <label for="month-<?php echo $monthKey; ?>"><?php echo $monthName; ?></label>
                        </div>

                    <?php endforeach; ?>
                    </div>
                </div>
            
        </div>
        <div class="">
            <div class="payment-info" >
                <label><input type="checkbox" id="toggleRegFee" onclick="toggleVisibility('reg-fee')">  رسوم الاتسجيل</label>
                <label><input type="checkbox" id="togglePFee" onclick="toggleVisibility('p-fee')">رسوم اخرى</label>
            </div>
                    <!-- Payment Info Sections -->
            <div class="payment-info" id="reg-fee" style="display: none;">
                <div>
                    <label for="registuration-fee">المبلغ المسدد</label>
                    <input type="text" name="registuration_fee" id="registuration-fee" placeholder="0٫00">
                </div>
            </div>
            <div class="payment-info" id="p-fee" style="display: none;">
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
        <div class="method-section mt-3">
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
            
            <form action="agent_receipt_all_pay.php" method="POST">
                <input type="hidden" name="id_agent" value="<?php echo $agent_data['agent_id']; ?>">
                <button type="sabmit" id="pay-arrears-button" class="styled-button">وصل</button>
    
            </form>
        </div>
    

    </div>

    <?php endif; ?>
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
            <option >البنك</option>
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

<!-- Modal Structure -->
<div id="arrears-modal" class="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); align-items: center; justify-content: center;">
    <div class="modal-content" style="max-width: 700px; background-color: #f9f9f9; border-radius: 10px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);">
        <h2 style="text-align: center; color: #333;">تسديد المتأخرات</h2>

        <form id="arrears-form" method="POST" action="process_family_all.php">
            <input type="hidden" value="<?php echo $agent_data['agent_id']; ?>" name="agent_id">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold;">المتأخرات</label>
                <div id="total-remaining-container" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;">
                    <label for="total-remaining-input"> أوقية جديدة </label>
                    <input type="text" id="total-remaining-input" name="due_amount" disabled style="border: none; background-color: transparent; outline: none; color: #555; font-size: 1.1rem;">
                </div>

            </div>

            <div style="margin-bottom: 20px;">
                <label for="amount-paidd" style="font-weight: bold;">المبلغ المدفوع</label>
                <input type="text" id="amount-paidd" name="amount_paidd" oninput="calculer()" required style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="remaining_amountt" style="font-weight: bold;">الباقي</label>
                <div id="remaining_amountt" name="remaining_amou_ntt"  style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;">0.00 أوقية جديدة</div>
            </div>

            <div class="method-section mt-3" style="margin-bottom: 20px;">
                <label for="methodd" style="font-weight: bold;">طريقة الدفع</label>
                <select id="methodd" name="payment_methodd" onchange="toggleBankModalInArrears(this.value)" style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
                    <option value="نقدي">طريقة الدفع</option>
                    <option value="نقدي">نقدي</option>
                    <option value="بنكي">بنكي</option>
                </select>
                <div id="selected-bank-name-arrears" style="margin-top: 15px; font-size: 1.1rem;"></div>
                <input type="hidden" id="selected-bank-id-arrears" name="bank">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                <button type="submit" class="confirm-button" style="padding: 12px 25px; background-color: #28a745; color: white; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">تأكيد العملية</button>
                <button type="button" onclick="closeArrearsModal()" style="padding: 12px 25px; background-color: #dc3545; color: white; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">إغلاق</button>
            </div>
        </form>
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

<script src="js/jquery-3.5.1.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
    $(function() {
        $("#phone").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "autocompleteee.php",
                    dataType: "json",
                    data: {
                        term: request.term
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 1
        });
    });
</script>
<script>
    function calculateRemaining() {
        var dueAmount = parseFloat(document.getElementById('due-amount').value) || 0;
        var paidAmount = parseFloat(document.getElementById('arrears-paid').value) || 0;
        var remainingAmount = dueAmount - paidAmount;
        document.getElementById('arrears-remaining').value = remainingAmount.toFixed(2);
    }
</script>



<script>

    function toggleBankModalInArrears(paymentMethod) {
        if (paymentMethod === 'بنكي') {
            var bankModal = new bootstrap.Modal(document.getElementById('bankModall'), {
                keyboard: false
            });
            bankModal.show();

            // Add an event listener to ensure that when the user selects a bank, it updates the arrears modal
            document.getElementById('bankk').addEventListener('change', selectBankInArrears);
        } else if (paymentMethod === 'نقدي') {
            clearSelectedBankNameInArrears();
        }
    }

    function selectBankInArrears() {
        const bankSelect = document.getElementById('bankk');
        const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
        const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
        document.getElementById('selected-bank-id-arrears').value = selectedBankId;
        document.getElementById('selected-bank-name-arrears').innerText = 'البنك المحدد: ' + selectedBankName;
    }

    function calculer() {
            const totalArrears = parseFloat(document.getElementById('total-remaining').value)  || 0;
            const amountPaid = parseFloat(document.getElementById('amount-paidd').value) || 0;
            const remaining = totalArrears - amountPaid;

            document.getElementById('remaining_amountt').innerText = remaining >= 0
                ? remaining.toFixed(2) + ' أوقية جديدة'
                : '0.00 أوقية جديدة';
    }

    function openArrearsModal() {
        const modal = document.getElementById('arrears-modal');
        modal.style.display = 'flex';
    }

    function closeArrearsModal() {
        const modal = document.getElementById('arrears-modal');
        modal.style.display = 'none';
    }

</script>

<script>
    function toggleVisibility(divId) {
        const element = document.getElementById(divId);
        element.style.display = element.style.display === 'none' ? 'block' : 'none';
    }
</script>

<style>
    .student-btn {
        margin: 5px;
        padding: 10px;
        background-color: lightgray;
        border: 1px solid #ccc;
        cursor: pointer;
    }

    .student-btn.selected {
        background-color: #007bff;
        color: white;
    }
</style>


<script>
   

    function toggleBankModal(paymentMethod) {
        if (paymentMethod === 'بنكي') {
            var bankModal = new bootstrap.Modal(document.getElementById('bankModal'), {
                keyboard: false
            });
            bankModal.show();
        } else if (paymentMethod === 'نقدي') {
            clearSelectedBankName();
        }
    }

    function selectBank() {
        const bankSelect = document.getElementById('bank');
        const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
        const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
        
        document.getElementById('selected-bank-id').value = selectedBankId;
        document.getElementById('selected-bank-name').innerText = 'البنك المحدد: ' + selectedBankName;
    }


    function clearSelectedBankName() {
        document.getElementById('selected-bank-name').innerText = '';
    }
</script>


<script>

    function updateDueAmount(agent_id) {
        const checkboxes = document.querySelectorAll('input[name="months[]"]');
        const selectedMonths = Array.from(checkboxes)
            .filter(checkbox => checkbox.checked && !checkbox.hasAttribute('readonly'))
            .map(checkbox => checkbox.value);

        if (selectedMonths.length === 0) {
            newT = 0;
            document.getElementById('due-amount').value = 0.00;
            document.getElementById('arrears-paid').value = 0.00;
            calculateRemaining();
            return;
        }
        document.getElementById('months_s').value = selectedMonths.join(',');

        fetch('get_due_aount.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ months: selectedMonths, agent_id: agent_id })
})
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json().catch(() => {
            throw new Error('Invalid JSON response from server');
        });
    })
    .then(data => {
        if (data.total_due !== undefined) {
            document.getElementById('due-amount').value = data.total_due.toFixed(2);
            document.getElementById('arrears-paid').value = data.total_due.toFixed(2);
            calculateRemaining();
        } else if (data.error) {
            console.error('Server Error:', data.error);
        } else {
            console.error('Unexpected Response:', data);
        }
    })
    .catch(error => {
        console.error('Request Error:', error);
        alert('An error occurred. Check the console for details.');
    });


        // fetch('get_due_aount.php', {
        //     method: 'POST',
        //     headers: {
        //         'Content-Type': 'application/json'
        //     },
        //     body: JSON.stringify({ months: selectedMonths, agent_id: agent_id })
        // })
        //     .then(response => {
        //         if (!response.ok) {
        //             throw new Error(`Erreur HTTP ${response.status}`);
        //         }
        //         return response.json();
        //     })
        //     .then(data => {
        //         if (data.total_due !== undefined) {
        //             document.getElementById('due-amount').value = data.total_due.toFixed(2);
        //             document.getElementById('arrears-paid').value = data.total_due.toFixed(2);
        //             calculateRemaining();
        //         } else if (data.error) {
        //             console.error('Erreur serveur:', data.error);
        //         } else {
        //             console.error('Réponse inattendue:', data);
        //         }
        //     })
        //     .catch(error => {
        //         console.error('Erreur lors de la requête:', error);
        //     });
    }
</script>


</body>
</html>
