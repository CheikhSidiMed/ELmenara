<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}

$user_id = $_SESSION['userid'];



// Include database connection
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['branch'] = $_POST['branch'];
    $_SESSION['class'] = $_POST['class'];
    $_SESSION['month'] = $_POST['month'];
}

// Retrieve stored values from session
$selectedBranch = $_SESSION['branch'] ?? '';
$selectedClass = $_SESSION['class'] ?? '';
$selectedMonth = $_SESSION['month'] ?? '';

// Fetch branches from the database

$sql = "SELECT b.branch_id, b.branch_name
FROM branches b
JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$resultBranches = $stmt->get_result();

$branches = [];
if ($resultBranches->num_rows > 0) {
    while ($row = $resultBranches->fetch_assoc()) {
        $branches[] = $row;
    }
}

// Initialize variables
$classes = [];
$branch_id = $_POST['branch'] ?? '';
$students = [];
$branch_name = '';
$class_name = '';
$username = '';

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

// Arabic months
$arabic_months = [
    'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
    'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class'];
    $month = $_POST['month'];

    // Fetch class name and branch name
    $sql = "SELECT c.class_name, b.branch_name, u.username
            FROM classes AS c
            JOIN branches AS b ON c.branch_id = b.branch_id
            LEFT JOIN user_branch AS ub ON ub.class_id = c.class_id
            LEFT JOIN users AS u ON u.id = ub.user_id
            WHERE c.class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $stmt->bind_result($class_name, $branch_name, $username);
    $stmt->fetch();
    $stmt->close();

    // Fetch students in the selected class
    $sql = "SELECT id, student_name FROM students WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
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
    <title>استمارة تقييم أقسام التهجي</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>
    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
    <style>

            h2 {
                text-align: center;
                font-weight: bold;
                margin-bottom: 20px;
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
                font-family: 'Arial', sans-serif;
                direction: rtl;
                text-align: right;
                margin-left: 20px; /* Reduced margin */
                margin-right: 20px; /* Reduced margin */
                margin-bottom: 20px; /* Reduced margin */
            }
            .header-title {
                text-align: center;
                margin-bottom: 20px; /* Reduced margin */
            }
            .sheet-header {
                text-align: center;
                margin-bottom: 15px; /* Reduced margin */
            }
            .sheet-header p {
                display: inline-block;
                margin: 0 10px; /* Adjusts space between the items */
                font-size: 14px; /* Adjust font size as needed */
            }
            .sheet-header img {
                width: 100%;
                max-width: 1400px; /* Adjusted width */
                height: auto;
            }
            table {
                width: 100%;
            }
            th, td {
                border: 1px solid black;
                padding: 1px;
            }
            th {
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
            .receipt-header img {
                width: 100%;
                height: auto; /* Maintain aspect ratio */
                max-width: 100%; /* Ensure it doesn’t exceed container size */
                display: block; /* Prevent any extra margins around the image */
            }
            .print-date {
                display: none; /* Hide by default */
            }

        /* Print-specific adjustments */
        @media print {
            /* Hide unnecessary elements */
            .button-group, .form-container, .container-fluid {
                display: none;
            }

            .print-date {
                display: block; /* Show only during print */
                text-align: right;
                font-weight: bold;
                margin-top: 10px;
            }
       
            /* General body adjustments for print */
            body {
                font-size: 14px; /* Smaller font to fit more content */
            }

            /* Ensure table width fits the page */
            table {
                width: 100%;
                table-layout: fixed; /* Forces table to fit the page width */
            }

            /* Landscape orientation to fit more columns */
            @page {
                size: landscape;
            }

            /* Column and table styling */
            th {
                font-size: 14px;
                padding: 2px;
                border: 1px solid black;
                white-space: nowrap;
            }
            td {
                font-size: 14px;
                padding: 2px;
                border: 1px solid black;
                white-space: wrap;
            }

            /* Bold styling for header sections */
            .sheet-header h3,
            .sheet-header p,
            .signature-section p {
                font-weight: bold;
                font-size: 16px;
            }
            .sheet-header {
                margin-top: -4px;
            }

            /* Handling for large tables, allowing page breaks within the rows */
            tr {
                page-break-inside: avoid;
            }
            .p-head{
                font-size: 15px !important;
            }
        }
        .p-head{
            font-size: 21px !important;
        }

    </style>
    <script>
        function printPage() {
            window.print();
        }
    </script>
</head>
<body>

    <div class="form-container">
        <h3 class="text-center">استمارة تقييم أقسام التهجي</h3>
        <form action="" method="POST">
            <div class="form-row">

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

            <div class="form-group col-md-2">
            <label class="form-select-title" for="financial-year" style="margin-left: 15px;"> العام الدراسي</label>
                <select id="financial-year" class="form-control">
                <option><?php echo $last_year; ?></option>
                </select>
                
            </div>

                <div class="form-group col-md-2 align-self-end">
                        <button type="sibmit" class="btn btn-primary btn-block" id="generate-button">إنشاء الاستمارة</button>
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
        <h3>استمارة تقييم أقسام التهجي</h3>
        <p class="p-head">الشهر: <?php echo $arabic_months[$selectedMonth] ?? ''; ?>،  العام الدراسي : <?php echo $last_year; ?></p>
        <p class="p-head">الفرع: <?php echo $branch_name; ?></p>
        <p class="p-head">القسم: <?php echo $class_name; ?></p>
        <p class="print-date">التاريخ : <?php echo date('d/m/Y'); ?></p>

    </div>

    <table>
        <thead>
            <tr>
                <th colspan="3">رقم التعريف</th>
                <th colspan="6">الاسم الكامل</th>
                <th colspan="10">س1 : اللوح (الوضوح - الصحة - الحفظ)</th>
                <th colspan="10">س2 : المحفوظات التعليمية و التربوية</th>
                <th colspan="10">س3 : الكتابة و القراءة (الأحرف، الكلمات، الجمل)</th>
                <th colspan="6">التقدير</th>
                <th colspan="6">الملاحظات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td colspan="3"><?php echo htmlspecialchars($student['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td colspan="6"><?php echo htmlspecialchars($student['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td colspan="10" contenteditable="true"></td>
                <td colspan="10" contenteditable="true"></td>
                <td colspan="10" contenteditable="true"></td>
                <td colspan="6" contenteditable="true"></td>
                <td colspan="6" contenteditable="true"></td>
            </tr>
            <?php endforeach; ?>
            <?php for ($i = 0; $i < 4; $i++): ?>

            <tr>
                <td colspan="3"></td>
                <td colspan="6"></td>
                <td colspan="10" contenteditable="true"></td>
                <td colspan="10" contenteditable="true"></td>
                <td colspan="10" contenteditable="true"></td>
                <td colspan="6" contenteditable="true"></td>
                <td colspan="6" contenteditable="true"></td>
            </tr>
            <?php endfor; ?>

        </tbody>
    </table>
    
    <div class="signature-section">
        <div class="signature">
            <p>توقيع الأستاذ: <strong><?php echo $username; ?></strong></p>
            <p>__________________</p>
        </div>
        <div class="signature">
            <p>الملاحظات العامة</p>
            <p>__________________</p>
        </div>
        <div class="signature">
            <p> الإدارة</p>
            <p>__________________</p>
        </div>
    </div>

</body>
</html>
