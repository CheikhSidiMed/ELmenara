<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}
$currentMonth = date('m');
                   


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['branch'] = $_POST['branch'];
    $_SESSION['class'] = $_POST['class'];
    $_SESSION['month'] = $_POST['month'];
}

// Retrieve stored values from session
$selectedBranch = isset($_SESSION['branch']) ? $_SESSION['branch'] : '';
$selectedClass = isset($_SESSION['class']) ? $_SESSION['class'] : '';
$selectedMonth = isset($_SESSION['month']) ? $_SESSION['month'] : 0;


$monthToUse = $selectedMonth + 1;
 
// Fetch branches from the database
$sqlBranches = "SELECT branch_id, branch_name FROM branches";
$resultBranches = $conn->query($sqlBranches);

$branches = [];
if ($resultBranches->num_rows > 0) {
    while ($row = $resultBranches->fetch_assoc()) {
        $branches[] = $row;
    }
}

// Initialize variables
$classes = [];

// Initialize variables
$branch_id = $_POST['branch'] ?? '';
$students = [];
$branch_name = '';
$class_name = '';
$student_by = [];

// Fetch classes based on selected branch
if (!empty($branch_id)) {
    $sqlClasses = "SELECT class_id, class_name FROM classes WHERE branch_id = ?";
    $stmt = $conn->prepare($sqlClasses);
    $stmt->bind_param('i', $branch_id);
    $stmt->execute();
    $resultClasses = $stmt->get_result();

    while ($row = $resultClasses->fetch_assoc()) {
        $classes[] = $row;
    }
    $stmt->close();
}

$arabic_months = [
    'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
    'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class'];
    $month = $_POST['month'];

    $sql = "SELECT classes.class_name, branches.branch_name
            FROM classes
            JOIN branches ON classes.branch_id = branches.branch_id
            WHERE classes.class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $stmt->bind_result($class_name, $branch_name);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT
                s.id,
                s.student_name,
                s.phone AS p_st,
                a.phone AS p_ag,
                a.phone_2,
                ab.session_time,
                ab.created_at
            FROM
                students AS s
            LEFT JOIN
                agents AS a ON s.agent_id = a.agent_id
            LEFT JOIN
                absences AS ab ON s.id = ab.student_id
                AND (ab.created_at IS NULL OR MONTH(ab.created_at) = ? + 1)
            WHERE
                s.class_id = ?
            ORDER BY
                s.id, ab.created_at, ab.session_time;
            ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $selectedMonth, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();


    while ($row = $result->fetch_assoc()) {
        $student_id = $row['id'];

        // Initialize student data if not already set
        if (!isset($students[$student_id])) {
            $students[$student_id] = [
                'name' => $row['student_name'],
                'phone1' => $row['p_st'],
                'phone2' => $row['p_ag'],
                'phone3' => $row['phone_2'],
                'absences' => [],
                'total_absences' => 0
            ];
        }
        
        if (!empty($row['created_at'])) {
            $absence_date = date('Y-m-d', strtotime($row['created_at']));
            $session_time = $row['session_time'];
            $session_type = '';
    
            if (strpos($session_time, 'صباحًا') !== false) {
                $session_type = 'morning';
            } elseif (strpos($session_time, 'مساءً') !== false) {
                $session_type = 'evening';
            }
    
            if (!isset($students[$student_id]['absences'][$absence_date])) {
                $students[$student_id]['absences'][$absence_date] = ['morning' => false, 'evening' => false];
            }
    
            if ($session_type === 'morning') {
                $students[$student_id]['absences'][$absence_date]['morning'] = true;
            } elseif ($session_type === 'evening') {
                $students[$student_id]['absences'][$absence_date]['evening'] = true;
            }
    
            $students[$student_id]['total_absences']++;
        }

    }
    $stmt->close();
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>   استمارة الغياب</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
    <link rel="stylesheet" href="css/tajawal.css">
    <style>
            .absent {
                background-color: #ffcccc;
                color: red;
                text-align: center;
            }
            h2 {
                text-align: center;
                font-weight: bold;
                margin-top: 5px;
                margin-bottom: 3px;
            }
            .form-container {
                width: 100%;
                margin: 0 auto;
                padding: 20px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 10px;
            }

            body {
                font-family: 'Tajawal', sans-serif;
                direction: rtl;
                text-align: right;
                font-size: 15px;
                margin-left: 15px; /* Reduced margin */
                margin-right: 15px; /* Reduced margin */
                margin-bottom: 30px !important;

            }
            .header-title {
                text-align: center;
            }
            .sheet-header {
                text-align: center;
            }
            .sheet-header p {
                display: inline-block;
                margin: 0 1px; /* Adjusts space between the items */
                font-size: 14px; /* Adjust font size as needed */
            }
            .sheet-header img {
                width: 100%;
                max-width: 1400px; /* Adjusted width */
                height: 60px !important;
            }
            table {
                width: 100%;
            }
            th, td {
                border: 1px solid black;
                padding: 0px;
                font-size: 12px;
                text-align: center;
            }
            th {
                font-size: 13px;
                background-color: #f8f9fa;
            }
            .signature-section {
                display: flex;
                justify-content: space-between;
                margin-top: 10px; /* Reduced margin */
            }
            .signature {
                width: 22%; /* Adjusted width */
                text-align: center;
            }
            .print-button {
                margin-bottom: 10px; /* Reduced margin */
                text-align: center;
            }
            .total-cell {
            text-align: center;
            font-weight: bold; /* Optional: to make it bold */
            }
            .button-group {
                    display: flex;
                justify-content: space-between;
                margin-top: 10px;
            }
            .btn i {
            margin-right: 8px; /* Adjust the value as needed */
            }
            .print-date {
            display: none; /* Hide by default */
        }
        /* Print-specific adjustments */
        @media print {
            .button-group, .form-container, .container-fluid {
                display: none;
            }
            .print-date {
                display: block;
                text-align: right;
                font-weight: bold;
            }

            body {
                font-size: 10px !important;
                align-items: center;
                margin-bottom: 0px !important;

            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 12px !important;
                text-align: start;
                table-layout: fixed;
            }
            h2 {
                font-size: 14px;

            }

            .receipt-header img {
                width: 100%;
                height: auto;
                max-width: 100%;
                display: block;
            }
            .print-date {
                display: none; /* Hide by default */
            }
            @page {
                size: A4 landscape;
            }

            th, td {
                font-size: 11px !important; /* Further reduce font size within table */
                padding: 0px;
                border: 1px solid black;
                white-space: wrap;
                overflow: hidden;
            }
            th {
                text-align: center;
                font-size: 13px;
                background-color: #f8f9fa;
            }
            /* Bold styling for header sections */
            .sheet-header p,
            .signature-section p {
                font-weight: bold;
                font-size: 12px;
            }
            .sheet-header {
                margin-top: -1px;
            }
            .print-date {
                display: block; /* Show only during print */
                text-align: right;
                font-weight: bold;
                margin-top: 10px;
            }

            /* Handling for large tables, allowing page breaks within the rows */
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
    <script>
        function printPage() {
            window.print();
        }
    </script>
</head>
<body>

<div class="form-container container mt-4">
    <h3 class="text-center mb-4">استمارة الغياب</h3>
    <form action="" method="POST">
        <div class="row">
            <!-- Champ Branch -->
            <div class="form-group col-md-3">
                <label for="branch">اختر الفرع:</label>
                <select name="branch" id="branch" class="form-control" onchange="this.form.submit()" required>
                    <option value="">اختر الفرع</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['branch_id']; ?>" <?php if ($selectedBranch == $branch['branch_id']) echo 'selected'; ?>>
                            <?php echo $branch['branch_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Champ Class -->
            <div class="form-group col-md-3">
                <label for="class">اختر الصف:</label>
                <select name="class" id="class" class="form-control" required>
                    <option value="">اختر الصف</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['class_id']; ?>" <?php if ($selectedClass == $class['class_id']) echo 'selected'; ?>>
                            <?php echo $class['class_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Champ Month -->
            <div class="form-group col-md-2">
                <label for="month">اختر الشهر:</label>
                <select name="month" id="month" class="form-control" required>
                    <option value="">اختر الشهر</option>
                    <?php foreach ($arabic_months as $index => $month): ?>
                        <option value="<?php echo $index; ?>" <?php if ($selectedMonth == $index) echo 'selected'; ?>>
                            <?php echo $month; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Champ Year -->
            <div class="form-group col-md-2">
            <label class="form-select-title" for="financial-year" style="margin-left: 15px;">العام الدراسي:</label>
                <select id="financial-year" class="form-control">
                <option><?php echo $last_year; ?></option>
                </select>
                
            </div>

            <!-- Bouton Générer -->
            <div class="form-group col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-block w-100" id="generate-button">إنشاء الاستمارة</button>
            </div>
        </div>
    </form>
</div>


    <div class="button-group">
        <button type="button" class="btn btn-primary d-flex align-items-center" onclick="printPage()">
        طباعة <i class="fas fa-print" style="margin-right: 8px;"></i> 
        </button>
        <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
        الصفحة الرئيسية <i class="fas fa-home mr-2"></i>
        </button>

    </div>

    <div class="sheet-header receipt-header">
        <img src="../images/header.png" alt="Header Image">
        <h2>     استمارة الغياب الشهري</h2>
        <p>الشهر: <?php echo $arabic_months[$selectedMonth] ?? ''; ?>، العام الدراسي: <?php echo $last_year; ?></p>
        <p>الفرع: <?php echo $branch_name; ?></p>
        <p>القسم: <?php echo $class_name; ?></p>
        <p class="print-date">التاريخ : <?php echo date('d/m/Y'); ?></p>

    </div>

    <table>
        <thead>
            <tr>
                <th colspan="3" > التعريف</th>
                <th colspan="12">الاسم الكامل</th>
                <th colspan="5">1هاتف</th>
                <th colspan="5">هاتف2 </th>
                <th colspan="2" style="min-width: 30px;">01</th>
                <th colspan="2" style="min-width: 30px;">02</th>
                <th colspan="2" style="min-width: 30px;">03</th>
                <th colspan="2" style="min-width: 30px;">04</th>
                <th colspan="2" style="min-width: 30px;">05</th>
                <th colspan="2" style="min-width: 30px;">06</th>
                <th colspan="2" style="min-width: 30px;">07</th>
                <th colspan="2" style="min-width: 30px;">08</th>
                <th colspan="2" style="min-width: 30px;">09</th>
                <th colspan="2" style="min-width: 30px;">10</th>
                <th colspan="2" style="min-width: 30px;">11</th>
                <th colspan="2" style="min-width: 30px;">12</th>
                <th colspan="2" style="min-width: 30px;">13</th>
                <th colspan="2" style="min-width: 30px;">14</th>
                <th colspan="2" style="min-width: 30px;">15</th>
                <th colspan="2" style="min-width: 30px;">16</th>
                <th colspan="2" style="min-width: 30px;">17</th>
                <th colspan="2" style="min-width: 30px;">18</th>
                <th colspan="2" style="min-width: 30px;">19</th>
                <th colspan="2" style="min-width: 30px;">20</th>
                <th colspan="2" style="min-width: 30px;">21</th>
                <th colspan="2" style="min-width: 30px;">22</th>
                <th colspan="2" style="min-width: 30px;">23</th>
                <th colspan="2" style="min-width: 30px;">24</th>
                <th colspan="2" style="min-width: 30px;">25</th>
                <th colspan="2" style="min-width: 30px;">26</th>
                <th colspan="2" style="min-width: 30px;">27</th>
                <th colspan="2" style="min-width: 30px;">28</th>
                <th colspan="2" style="min-width: 30px;">29</th>
                <th colspan="2" style="min-width: 30px;">30</th>
                <th colspan="2" style="min-width: 30px;">31</th>

                <th colspan="3" style="width: 3%;">عدد الغياب</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $student_id => $student): ?>
            <tr>
                <td colspan="3"><?= $student_id ?></td>
                <td colspan="12"><?= $student['name'] ?></td>
                <td colspan="5"><?= $student['phone2'] ?? $student['phone1'] ?></td>
                <td colspan="5"><?= $student['phone3'] ?? $student['phone1'] ?></td>

                <?php
                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $monthToUse, date('Y'));

                    for ($day = 1; $day <= 31; $day++):
                        $formatted_day = sprintf('%02d', $day);
                        $formatted_month = sprintf('%02d', $monthToUse);
                        
                        $absence_found = false;
                        foreach ($student['absences'] as $date => $absence_data) {
                            // Extraire mois et jour de la clé de date
                            [$absence_year, $absence_month, $absence_day] = explode('-', $date);

                            if ($absence_month == $formatted_month && $absence_day == $formatted_day) {
                                $absence_found = true;
                                $morning_absent = !empty($absence_data['morning']);
                                $evening_absent = !empty($absence_data['evening']);
                                break; // Arrêter la recherche pour ce jour
                            }
                        }

                        if (!$absence_found) {
                            $morning_absent = false;
                            $evening_absent = false;
                        }
                    ?>
                    <td style="width: 1.1%; height: 4px;" contenteditable="true" class="<?= $morning_absent ? 'absent' : 'present' ?>">
                        <?= $morning_absent ? 'X' : '' ?>
                    </td>
                    <td style="width: 1.1%;  height: 4px;" contenteditable="true" class="<?= $evening_absent ? 'absent' : 'present' ?>">
                        <?= $evening_absent ? 'X' : '' ?>
                    </td>
                <?php endfor; ?>

                <td contenteditable="true" colspan="3"><?= $student['total_absences'] ?? 0 ?></td>
            </tr>
        <?php endforeach; ?>


        <?php for ($i = 0; $i < 3; $i++): ?>
            <tr>
                <td colspan="3"></td>
                    <td colspan="12"></td>
                    <td colspan="5" contenteditable="true"></td>
                    <td colspan="5" contenteditable="true"></td>
                    <?php for ($j = 0; $j < 62; $j++): ?>
                    <td contenteditable="true"></td>
                <?php endfor; ?>
                <td colspan ="3" contenteditable="true"></td>
            </tr>
            <?php endfor; ?>
        </tbody>

    </table>
    <div class="signature-section">
        <div class="signature">
            <p>الأستاذ</p>
            <p style="margin-top: -15px;">__________________</p>
        </div>
        <div class="signature">
            <p>تاريخ التسليم</p>
            <p style="margin-top: -15px;">__________________</p>
        </div>
        <div class="signature">
            <p>توقيع الأستاذ</p>
            <p style="margin-top: -15px;">__________________</p>
        </div>
        <div class="signature">
            <p>توقيع الإدارة</p>
            <p style="margin-top: -15px;">__________________</p>
        </div>
    </div>

</body>
</html>
