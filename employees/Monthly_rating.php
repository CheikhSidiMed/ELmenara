<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Start session to access session variables

// Include database connection
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

$branch_name = '';
$class_name = '';
$totalPages = 0;


// Form submission processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['branch'] = $_POST['branch'];
    $_SESSION['class'] = $_POST['class'];
    $_SESSION['month'] = $_POST['month'];
    $_SESSION['year'] = $_POST['year'];
}

// Retrieve stored values from session
$selectedBranch = $_SESSION['branch'] ?? '';
$selectedClass = $_SESSION['class'] ?? '';
$selectedMonth = $_SESSION['month'] ?? '';
$selectedYear = $_SESSION['year'] ?? date('Y');

// Fetch branches from the database
$sqlBranches = "SELECT branch_id, branch_name FROM branches";
$resultBranches = $conn->query($sqlBranches);

$branches = [];
if ($resultBranches->num_rows > 0) {
    while ($row = $resultBranches->fetch_assoc()) {
        $branches[] = $row;
    }
}

// Initialize classes and branch name
$classes = [];
$branch_id = $_SESSION['branch'] ?? '';

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

// Fetch branch and class names if set
if (!empty($branch_id) && !empty($selectedClass)) {
    $sqlBranch = "SELECT branch_name FROM branches WHERE branch_id = ?";
    $stmtBranch = $conn->prepare($sqlBranch);
    $stmtBranch->bind_param('i', $branch_id);
    $stmtBranch->execute();
    $stmtBranch->bind_result($branch_name);
    $stmtBranch->fetch();
    $stmtBranch->close();

    $sqlClass = "SELECT class_name FROM classes WHERE class_id = ?";
    $stmtClass = $conn->prepare($sqlClass);
    $stmtClass->bind_param('i', $selectedClass);
    $stmtClass->execute();
    $stmtClass->bind_result($class_name);
    $stmtClass->fetch();
    $stmtClass->close();
}

// Fetch students based on selected class with pagination
$page = $_GET['page'] ?? 1;
$limit = 3;
$offset = ($page - 1) * $limit;

$students = [];

if (!empty($selectedClass)) {
    $sql = "SELECT id, student_name FROM students WHERE class_id = ? LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $selectedClass, $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();

    // Get total number of students for pagination
    $sqlTotal = "SELECT COUNT(*) FROM students WHERE class_id = ?";
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->bind_param('i', $selectedClass);
    $stmtTotal->execute();
    $stmtTotal->bind_result($totalStudents);
    $stmtTotal->fetch();
    $stmtTotal->close();

    // Calculate total pages
    $totalPages = ceil($totalStudents / $limit);
}


if (isset($_GET['print_all']) && $_GET['print_all'] == 'true') {
    header('Content-Type: application/json');

    $sql = "SELECT student_name FROM students WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $selectedClass);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();

    echo json_encode($students);
    exit;
}


$conn->close();
?>



<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استمارة التقويم الشهري</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
    <style>

            h2 {
                text-align: center;
                font-weight: bold;
                margin-bottom: 5px;
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
            .pagination {
                margin: 15px;
            }
            .sheet-header {
                text-align: center;
                margin-bottom: 5px; /* Reduced margin */
            }
            .sheet-header p {
                display: inline-block;
                margin: 0 10px; /* Adjusts space between the items */
                font-size: 14px; /* Adjust font size as needed */
            }
            .sheet-header img {
                width: 100%;
                max-width: 500px; /* Adjusted width */
                height: auto;
            }
            table {
                width: 100%;
            }
            td {
                border: 1px solid black;
                padding: 1px;
                text-align: center;
                height:   174px; 
            }
            th {
                background-color: #f8f9fa;
                border: 1px solid black;
                padding: 1px;
                text-align: center;
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
            display: none;
        }

        @media print {
            @page {
        size: landscape;
        margin: 0;
        }
    body {
        margin: 0;
        padding: 0;
        font-size: 15px; /* Small base font */
    }
    .sheet-header, .sheet-header p, .signature-section p {
        font-weight: bold;
        font-size: 14px; 
    }
    .sheet {
        page-break-before: always;
    }
    h3 {
        margin-top: 13px;
        font-weight: bold;
        font-size: 20px; 
    }
    .print-date {
        display: block;
        text-align: right;
        font-weight: bold;
        margin-top: 5px;
        font-size: 10px;
    }
    .button-group, .form-container {
        display: none; /* Hide buttons and form elements */
    }

    .sheet-header {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
    }

    table {
        width: 100%;
        max-width: 92%;
        table-layout: fixed;
        border-collapse: collapse;
        margin-right: 47px;
    }
    th, td {
        font-size: 15px !important; 
        padding: 2px; 
        border: 1px solid black;
        white-space: wrap; 
        overflow: hidden; 
        text-align: center;
    }
    th {
        font-weight: bold;
    }

    .pagination {
        display: none;
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
    <div class="form-container">
        <h3 class="text-center">استمارة التقويم الشهري</h3>
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
                    <label for="year">العام الدراسي:</label>
                    <select name="year" id="year" class="form-control">
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
            طباعة <i class="bi bi-printer-fill" style="margin-right: 8px;"></i> 
        </button>
        <a href="#" class="btn btn-primary" onclick="printAllStudents(); return false;">طباعة جميع الطلاب لصفحة السابقة <i class="bi bi-printer-fill me-2" style="margin-right: 8px;"></i> </a>

        <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
            الصفحة الرئيسية <i class="fas fa-home mr-2"></i> 
        </button>

    </div>
    <div class="sheet">
        <img src="../images/header.png" width="100%" alt="Header Image">
        <div class="sheet-header">
            <h3> استمارة التقويم </h3>
            <p>الفرع: <?php echo $branch_name; ?>  <p>القسم: <?php echo $class_name; ?></p> <p>الشهر: <?php echo $arabic_months[$selectedMonth] ?? ''; ?> </p> <p> العام الدراسي: <?php echo $last_year; ?></p>
            <p class="print-date">تاريخ الطباعة: <?php echo date('Y-m-d'); ?></p> <!-- Print date only visible during print -->

        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th colspan="4">الاسم الكامل</th>
                <th colspan="2">اللوح</th>
                <th colspan="16">العدد المقوم من الأحزاب و الملاحظات العامة و المحددة</th>
                <th colspan="1" style="width: 10%">مستوى الأداء</th>
                <th colspan="1" style="width: 10%">التقدير النهائي</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td colspan="4"><?php echo $student['student_name']; ?></td>
                <td colspan="2" contenteditable="true"></td>
                <td colspan="16" contenteditable="true"></td>
                <td colspan="1" contenteditable="true"></td>
                <td colspan="1" contenteditable="true"></td>
            </tr>
            <?php endforeach; ?>
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

    <!-- Pagination Controls -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">السابق</a>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">التالي</a>
        <?php endif; ?>
    </div>


    <!-- Pagination Controls -->
    
    <script>
        function printAllStudents() {
            fetch('?print_all=true')
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data) && data.length > 0) {
                        // Clear existing table content
                        const tableBody = document.querySelector('table tbody');
                        tableBody.innerHTML = ''; // Remove existing rows

                        // Add rows for all students
                        data.forEach(student => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td colspan="4">${student.student_name}</td>
                                <td colspan="2" contenteditable="true"></td>
                                <td colspan="16" contenteditable="true"></td>
                                <td colspan="1" contenteditable="true" style="max-width: 38px"></td>
                                <td colspan="1" contenteditable="true" style="max-width: 38px"></td>
                            `;
                            tableBody.appendChild(row);
                        });

                        // Trigger print
                        window.print();
                    } else {
                        alert('لا يوجد طلاب لطباعة.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching student data:', error);
                    alert('حدث خطأ أثناء تحميل البيانات.');
                });
        }
    </script>


</body>
</html>