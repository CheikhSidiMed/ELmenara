<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$branches = $conn->query("SELECT branch_id, branch_name FROM branches")->fetch_all(MYSQLI_ASSOC);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

// Fetch available classes from the database
// $sql = "SELECT class_name FROM classes";
// $result = $conn->query($sql);

// $classes = [];
// while ($row = $result->fetch_assoc()) {
//     $classes[] = $row['class_name'];
// }

$classes = [];




$selectedYear = isset($_GET['year']) ? $_GET['year'] : '2024-2023';
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'class';
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';
$selectedBranch = $_GET['branch'] ?? '';


if (!empty($selectedBranch)) {
    $stmt = $conn->prepare("SELECT class_name FROM classes WHERE branch_id = ?");
    $stmt->bind_param("i", $selectedBranch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT class_name FROM classes");
}

while ($row = $result->fetch_assoc()) {
    $classes[] = $row['class_name'];
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


if ($filterType === 'all') {
    $sql_new = "SELECT s.id, s.student_name, s.registration_date, s.phone, p.month,
               p.remaining_amount, a.whatsapp_phone, s.remaining as price
                FROM students s
                LEFT JOIN payments p ON s.id = p.student_id
                LEFT JOIN agents a ON s.agent_id = a.agent_id
                JOIN levels l ON s.level_id = l.id
                WHERE s.etat = 0 AND s.is_active = 0 AND s.remaining != 0.00";

    if (!empty($selectedBranch)) {
        $sql_new .= " AND s.branch_id = ?";
        $stmt = $conn->prepare($sql_new);
        $stmt->bind_param("i", $selectedBranch);
    } else {
        $stmt = $conn->prepare($sql_new);
    }

} else {
    $sql_new = "SELECT s.id, s.student_name, s.registration_date, s.phone, p.month,
                a.whatsapp_phone, p.remaining_amount, s.remaining as price
                FROM students s
                LEFT JOIN payments p ON s.id = p.student_id
                LEFT JOIN agents a ON s.agent_id = a.agent_id
                JOIN classes c ON s.class_id = c.class_id
                JOIN levels l ON s.level_id = l.id
                WHERE s.etat = 0 AND s.is_active = 0 AND s.remaining != 0.00 AND c.class_name = ?";

    if (!empty($selectedBranch)) {
        $sql_new .= " AND s.branch_id = ?";
        $stmt = $conn->prepare($sql_new);
        $stmt->bind_param("si", $selectedClass, $selectedBranch);
    } else {
        $stmt = $conn->prepare($sql_new);
        $stmt->bind_param("s", $selectedClass);
    }
}

// No need for $stmt_new
$stmt->execute();
$result = $stmt->get_result();



$students = [];
$rem_tot = 0;
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
            'rem_tot' => 0,
            'price' => $row['price'],
            'paid_months' => [],
            'unpaid_months' => []
        ];
    }
    $students[$studentName]['remaining_amount'] += $row['remaining_amount'];
    $students[$studentName]['rem_tot'] += $row['remaining_amount'];
    $rem_tot += $row['remaining_amount'];

    if ($row['month']) {
        $students[$studentName]['paid_months'][] = $row['month'];
    }
    
}


foreach ($students as $studentName => &$student) {
    $registrationYear = (int)date('Y', strtotime($student['registration_date']));
    $registrationMonth = (int)$student['registration_month'];
    $academicMonths = ($registrationMonth <= $endMonth) ? $endaAcademicMonths : $allAcademicMonths;

    foreach ($academicMonths as $month) {
        $academicYear = ($month >= $startMonth) ? $registrationYear : $currentYear;

        if ($academicYear === $registrationYear && $month <= $registrationMonth) {
            continue;
        }

        if ((int)$month > 0 && isset($arabicMonths[(int)$month]) && !in_array($arabicMonths[(int)$month], $student['paid_months'])) {
            $student['unpaid_months'][] = $arabicMonths[(int)$month];
            $student['rem_tot'] += $student['price'];
            $rem_tot += $student['price'];

        }
        
    }
}

unset($student);

$stmt->close();
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
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        .main-contain {
            margin: 10px auto;
            padding: 30px;
            border-radius: 12px;
            background-color: white;
            border: 1px solid #ddd;
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

        .tbl {
            overflow-x: auto;
            width: 100%;
        }
        table {
            min-width: 900px;
            border-collapse: collapse;
        }
    </style>
    <script>
        function printTable() {
            window.print();
        }
    </script>
</head>
<body>

<div class="container main-contain">
    <!-- Header Section -->
    <div class="form-header d-flex flex-column mb-3 flex-md-row justify-content-between align-items-center">
        <h2 class="header-tit">
            <i class="bi bi-file-earmark-text-fill"></i> الطلاب المدينين
        </h2>
        <div class="d-flex flex-column flex-md-row gap-2 mt-2 mt-md-0">
            <button class="btn btn-success h-25" onclick="window.location.href='home.php'">
                الصفحة الرئيسية <i class="fas fa-home ms-2"></i>
            </button>
            

        <!-- Sélection de l'année -->
        <div class="col-12 col-md-6 ">
            <!-- <label for="year-select" class="form-label">السنة المالية:</label> -->
            <select id="year-select" name="year" class="form-select">
                <option><?php echo htmlspecialchars($last_year); ?></option>
            </select>
        </div>
        </div>
    </div>


    <form action="" method="get" class="row g-3 align-items-end">
        
        <div class="col-12 col-md-6 col-lg-3">
            <a class="btn btn-primary w-100" onclick="window.location.href='months_not_paid.php'">
            إدارة الأشهر غير المدفوعة
            </a>
        </div>

        <!-- Branch selection -->
        <div class="col-12 col-md-6 col-lg-2">
            <label for="branch" class="form-label">الفرع:</label>
            <select id="branch" name="branch" class="form-select">
                <option value="">-- اختر الفرع --</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= $branch['branch_id'] ?>"><?= htmlspecialchars($branch['branch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Class selection -->
        <div class="col-12 col-md-6 col-lg-2">
            <label for="section" class="form-label">حسب القسم:</label>
            <select id="section" name="class" class="form-select">
                <option value="">-- اختر القسم --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
                <?php endforeach; ?>
            </select>
        </div>


        <!-- Sélection du filtre -->
        <div class="col-12 col-md-6 col-lg-2">
            <label for="filter-select" class="form-label">تصفية حسب:</label>
            <select id="filter-select" name="filter" class="form-select">
                <option value="class">حسب القسم</option>
                <option value="all">حسب الجميع</option>
            </select>
        </div>

        <!-- Boutons de validation et d'impression -->
        <div class="col-12 col-md-6 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-square"></i> تأكيد العملية
            </button>
            <button type="button" class="btn btn-secondary w-100" onclick="printTable()">
                <i class="bi bi-printer"></i> طباعة
            </button>
        </div>
    </form>

</div>


<!-- Report Section -->
<div class="container main-contain mt-4">
    <div class="text-center">
        <img src="../images/header.png" class="img-fluid" alt="Header Image">
        <h2 class="header-title mt-3">تقرير بالحسابات المدينة</h2>
        
        <?php if ($filterType === 'class'): ?>
            <h3 class="header-title">الفصل: <?= htmlspecialchars($selectedClass) ?></h3>
        <?php endif; ?>
    </div>


    <div class="table-responsive tbl">
        <table>
            <form method="post" action="debt_send_whatsapp.php" target="_blank">
                <thead>
                    <tr>
                        <th>اسم الطالب</th>
                        <th>رقم الهاتف</th>
                        <th>الأشهر غير المدفوعة</th>
                        <th>المبلغ المتبقي</th>
                        <th> الدبن</th>
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
                            <td><?= htmlspecialchars($data['rem_tot']) ?></td>
                            <td>
                                <input type="checkbox" name="selected_students[]"
                                    value="<?= htmlspecialchars($data['rem_tot']) ?>|<?= htmlspecialchars($data['id']) ?>|<?= htmlspecialchars($data['remaining_amount']) ?>|<?= htmlspecialchars(implode(',', $data['unpaid_months'] ?? [])) ?>" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">
                            <button type="submit" class="btn btn-success">إرسال رسائل واتساب</button>
                        </td>
                        <td colspan="2" style="text-align: right; font-size: 20px; font-weight: bold;">
                            <span style=""><?= str_replace('.', '.', sprintf("%0.2f", floatval($rem_tot))) ?></span>
                        </td>
                    </tr>
                </tfoot>
            </form>
        </table>
    </div>
</div>

    

    <script>
        function toggleSelectAll(selectAllCheckbox) {
            const checkboxes = document.querySelectorAll('input[name="selected_students[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = selectAllCheckbox.checked);
        }
    </script>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        document.getElementById('branch').addEventListener('change', function () {
            const branchId = this.value;

            fetch('fetch_classe_s.php?branch_id=' + branchId)
                .then(response => response.json())
                .then(data => {
                    const classSelect = document.getElementById('section');
                    classSelect.innerHTML = '<option value="">-- اختر القسم --</option>';

                    data.forEach(className => {
                        const option = document.createElement('option');
                        option.value = className;
                        option.textContent = className;
                        classSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching classes:', error);
                });
        });
    </script>

</body>
</html>