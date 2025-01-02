<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

// Fetch available classes from the database
$sql = "SELECT class_name FROM classes";
$result = $conn->query($sql);

$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = $row['class_name'];
}

$selectedYear = isset($_GET['year']) ? $_GET['year'] : '2024-2023';
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'class';
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';


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



if ($filterType === 'all') {
    $sql_new = "SELECT s.id, s.student_name, s.registration_date, s.phone, p.month, 
               p.remaining_amount, a.whatsapp_phone 
                FROM students s
                LEFT JOIN payments p ON s.id = p.student_id
                LEFT JOIN agents a ON s.agent_id = a.agent_id
                WHERE s.remaining != 0.00
                ";
} else {
    $sql_new = "SELECT s.id, s.student_name, s.registration_date, s.phone, p.month, a.whatsapp_phone, 
                p.remaining_amount
                FROM students s
                LEFT JOIN payments p ON s.id = p.student_id
                LEFT JOIN agents a ON s.agent_id = a.agent_id 
                JOIN classes c ON s.class_id = c.class_id
                WHERE c.class_name = ? AND s.remaining != 0.00 ";
}

$stmt_new = $conn->prepare($sql_new);

if ($filterType === 'class') {
    $stmt_new->bind_param('s', $selectedClass);
}

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
            'remaining_amount' =>  0,
            'whatsapp_phone' => $row['whatsapp_phone'],
            'id' => $row['id'],
            'paid_months' => [],
            'unpaid_months' => []
        ];
    }
    $students[$studentName]['remaining_amount'] += $row['remaining_amount'];

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

$stmt_new->close();
$conn->close();

?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الديون</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        .main-contain {
            margin: 10px auto;
            padding: 30px;
            border-radius: 12px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            text-align: center; /* Center align the content */
        }
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f6;
            color: #333;
        }

        .main-contain {
            margin: 10px auto;
            padding: 10px;
            border-radius: 15px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .header-tit {
            font-size: 28px;
            font-weight: bold;
            color: #1BA078;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .form-inline {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 15px; /* space between each form group */
        }

        .form-group {
            display: flex;
            align-items: center;
        }

        .form-sele, .form-checkb {
            margin-left: 5px;
        }
        .print-button {
            margin-left: 10px;
        }


        .form-inline-container label {
            font-size: 16px;
            color: #666;
            font-weight: bold;
            margin-bottom: 0;
        }

        .form-sele {
            flex: 1; /* Allow each form group to take equal space */
            max-width: 290px; /* Uniform width for each form group */
        }

        .form-sele, .form-checkb {
            border-radius: 10px;
            padding: 10px;
            border: 2px solid #1BA078;
            font-size: 16px;

        }

        .form-checkb {
            transform: scale(1.3);
            accent-color: #1BA078;
            margin-left: 5px;
        }

        .btn-conf {
            background-color: #1BA078;
            color: white;
            border: 2px solid #1BA078;
            border-radius: 10px;
            padding: 8px 15px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            display: inline-flex;
            align-items: center;
        }

        .btn-conf i {
            font-size: 18px;
            margin-left: 5px;
            
        }

        .btn-conf:hover {
            background-color: #14865b;
        }
        .header-tit {
            font-size: 28px;
            font-weight: bold;
            margin: 12px;
            color: #1BA078;
            display: flex;
            align-items: center;
            justify-content: center; /* Center the title */
            gap: 10px;
            margin-bottom: 20px;
        }
        .main-container {
            margin: 40px auto;
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            transition: all 0.3s ease-in-out;
        }

        .header-title {
            font-size: 28px;
            font-weight: bold;
            color: #1BA078;
            text-align: center;
            margin-bottom: 30px;
        }

        .sub-header {
            font-size: 20px;
            margin-bottom: 20px;
            text-align: right;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 15px;
            font-size: 18px;
            text-align: center;
            vertical-align: middle;
            border: 2px solid #1BA078;
        }

        table th {
            background-color: #1BA078;
            color: white;
        }

        table td {
            background-color: #f9f9f9;
        }

        .footer-row {
            background-color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .footer-row td {
            border: none;
            padding-top: 30px;
        }

        .footer-total {
            border-top: 2px solid #1BA078;
            padding-top: 15px;
            font-size: 20px;
        }

        .header-image {
            width: 100%;
            max-width: 500px;
            margin-bottom: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .print-button {
            margin-top: 20px;
            padding: 3px 20px;
            font-size: 23px;
            cursor: pointer;
            background-color: #1BA078; /* Green */
            color: white; /* White text */
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .print-button:hover {
            background-color: #14865b; /* Darker Green */
        }

        /* Print Styles */
        @media print {
            
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }
            h2 {
                color: black;
            }
            table {
                box-shadow: none;
            }
            th, td {
                border: 1px solid black;
                color: black; /* Black text */
            }
            th {
                background-color: white; /* White background for header */
                color: black; /* Black text for header */
            }
            tfoot {
                background-color: white; /* White background for footer */
                color: black; /* Black text for footer */
            }
            .print-button, .cnc {
                display: none; /* Hide the button when printing */
            }
            img {
            display: block;
            margin: 0 auto; /* Center the image */
            width: 100%; /* Full width in print */
        }
        }
    </style>
    <script>
        function printTable() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="container main-contain cnc">
        <div class="form-header d-flex justify-content-between align-items-center">
            <h2 class="header-tit"><i class="bi bi-file-earmark-text-fill"></i>الطلاب المدينين</h2>

            <button class="btn btn-success home" onclick="window.location.href='months_not_paid.php'">إدارة الأشهر غير المدفوعة</hbutton1>
            <button class="btn btn-success home" onclick="window.location.href='home.php'">العودة إلى الصفحة الرئيسية</button>
        </div>

        <form action="" method="get" class="form-inline row">
    <!-- Year Selection -->
    <div class="form-group col-3">
        <label for="year-select">السنة المالية:</label>
        <select id="year-select" name="year" class="form-sele">
            <option><?php echo htmlspecialchars($last_year); ?></option>
        </select>
    </div>

    <!-- Section Select -->
    <div class="form-group col-3">
        <label for="section">حسب القسم:</label>
        <select id="section" name="class" class="form-sele">
            <?php foreach ($classes as $class): ?>
                <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Filter Checkboxes -->
    <div class="form-group col-3">
        <label for="filter1">حسب القسم</label>
        <input type="radio" id="filter1" name="filter" value="class" class="form-checkb">

        <label for="filter2">حسب الجميع</label>
        <input type="radio" id="filter2" name="filter" value="all" class="form-checkb">
    </div>

    <!-- Confirm and Print Buttons -->
    <div class="col-3">
        <button type="submit" class="btn-conf">
            <i class="bi bi-check-square"></i> تأكيد العملية
        </button>
        <button type="button" class="print-button" onclick="printTable()">طباعة</button>
    </div>
</form>

    </div>

    
    <div class="container main-contain">
        <!-- Header Image -->
        <div style="text-align: center;">
        <img src="../images/header.png" width="100%" alt="Header Image">
    
        <!-- Title -->
        <h2 class="header-title">تقرير بالحسابات المدينة</h2>

        <!-- Sub-Title -->
        <?php if ($filterType === 'class'): ?>
            <div class="header-title">
                <h2>الفصل: <?= htmlspecialchars($selectedClass) ?></h2>
            </div>
        <?php endif; ?>
</div>
       
        <table>
            <form method="post" action="debt_send_whatsapp.php" target="_blank">

                <thead>
                    <tr>
                        <th>اسم الطالب</th>
                        <th>رقم الهاتف</th>
                        <th>الأشهر غير المدفوعة</th>
                        <th>المبلغ المتبقي</th>
                        <th><input type="checkbox" id="select-all" onclick="toggleSelectAll(this)" /></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $name => $data): 
                        if (count($data['unpaid_months']) === 1 && in_array('كل الأشهر مدفوعة', $data['unpaid_months']) || (empty($data['unpaid_months']) && $data['remaining_amount'] <= 0.00)) {
                            continue;
                        } ?>    
                        
                        <tr>
                            <td><?= htmlspecialchars($name) ?></td>
                            <td><?= htmlspecialchars( $data['whatsapp_phone'] ?? $data['phone']) ?></td>
                            <td>
                                <?= !empty($data['unpaid_months']) 
                                ? implode(', ', $data['unpaid_months']) 
                                : '<span class="text-success">كل الأشهر مدفوعة</span>' 
                                ?>   
                            </td>
                            <td><?= htmlspecialchars($data['remaining_amount']) ?></td>
                            <td>
                                <input type="checkbox" name="selected_students[]" 
                                    value="<?= htmlspecialchars($data['id']) ?>|<?= htmlspecialchars($data['remaining_amount']) ?>|<?= htmlspecialchars(implode(',', $data['unpaid_months'] ?? [])) ?>" />
                            </td>                        
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <button type="submit" class="btn btn-success">إرسال رسائل واتساب</button>
                        </td>
                    </tr>
                </tfoot>
            </form>
        </table>
    </div>
    

    <script>
        function toggleSelectAll(selectAllCheckbox) {
            const checkboxes = document.querySelectorAll('input[name="selected_students[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = selectAllCheckbox.checked);
        }
    </script>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>