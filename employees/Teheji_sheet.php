<?php
// Include database connection
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class'];
    $month = $_POST['month'];
    $year = $_POST['year'];

     // Fetch class name and branch name
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

// Fetch students in the selected class
$sql = "SELECT student_id, student_name FROM students WHERE class_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $class_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
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
    <title>استمارة التقويم للتهجي</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 30px;
        }
        .header-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .sheet-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .sheet-header img {
            width: 100%;
            max-width: 500px;
            height: auto;
        }
        .sheet-header p {
            display: inline-block;
            margin: 0 10px; /* Adjusts space between the items */
            font-size: 14px; /* Adjust font size as needed */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 20px; /* Reduced padding */
            text-align: center;
            font-size: 15px; /* Reduced font size */
            word-wrap: break-word;
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
            width: 45%; /* Adjusted width */
            text-align: center;
        }
        .print-button {
            margin-bottom: 10px;
            text-align: center;
        }
        /* Specific style for the column with colspan="8" */
        th[colspan="8"], td[colspan="8"] {
            width: 200px; /* Adjust width as needed */
            text-align: center;
            overflow: hidden; /* Prevents content overflow */
            white-space: nowrap; /* Prevents text wrapping */
        }
        /* Specific style for the column with colspan="6" */
        th[colspan="6"], td[colspan="6"] {
            width: 200px; /* Adjust width as needed */
            text-align: center;
            overflow: hidden; /* Prevents content overflow */
            white-space: nowrap; /* Prevents text wrapping */
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .btn i {
        margin-right: 8px; /* Adjust the value as needed */
    }
        /* Print-specific adjustments */
        @media print {
            body {
                margin: 0; /* Ensure no extra margins on print */
                font-size: 8px; /* Further reduced font size */
            }
            th, td {
                padding: 2px; /* Further reduced padding */
                font-size: 20px; /* Further reduced font size */
            }
            .sheet-header h3, .sheet-header p, .signature-section p {
                font-size: 20px; /* Adjusted font size for print */
            }
            .button-group {
                display: none;
            }
            table {
                page-break-inside: avoid; /* Prevent table break */
            }
            .signature-section {
                margin-top: 0; /* Adjusted margin for print */
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
<div class="button-group">
    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="printPage()">
    طباعة <i class="fas fa-print" style="margin-right: 8px;"></i> 
    </button>
    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
        الصفحة الرئيسية <i class="fas fa-home mr-2"></i> 
    </button>


</div>
    <div class="sheet-header">
        <img src="../images/header.png" alt="Header Image">
        <h3>استمارة التقييم لأقسام التهجي</h3>
        <p>الشهر: <?php echo $month; ?>، السنة: <?php echo $year; ?></p>
        <p>الفرع: <?php echo $branch_name; ?></p>
        <p>القسم: <?php echo $class_name; ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th colspan="3">رقم التعريف</th>
                <th colspan="6">الاسم الكامل</th>
                <th colspan="9">س1 : اللوح (الوضوح - الصحة - الحفظ)</th>
                <th colspan="8">س2 : المحفوظات التعليمية و التربوية</th>
                <th colspan="11">س3 : الكتابة و القراءة (الأحرف، الكلمات، الجمل)</th>
                <th colspan="8">التقدير</th>
                <th colspan="8">الملاحظات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td colspan="3"><?php echo htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td colspan="6"><?php echo htmlspecialchars($student['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td colspan="9" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="11" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
            </tr>
            <?php endforeach; ?>
            <!-- Additional empty rows for printing space -->
            <tr>
                <td colspan="3"></td>
                <td colspan="6"></td>
                <td colspan="9" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="11" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
            </tr>
            <!-- Additional empty rows for printing space -->
            <tr>
                <td colspan="3"></td>
                <td colspan="6"></td>
                <td colspan="9" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="11" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
            </tr>
            <!-- Additional empty rows for printing space -->
            <tr>
                <td colspan="3"></td>
                <td colspan="6"></td>
                <td colspan="9" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="11" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
            </tr>
            <!-- Additional empty rows for printing space -->
            <tr>
                <td colspan="3"></td>
                <td colspan="6"></td>
                <td colspan="9" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="11" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
                <td colspan="8" contenteditable="true"></td>
            </tr>
            
            <!-- Repeat as needed for more space -->
        </tbody>
    </table>
    <div class="signature-section">
        <div class="signature">
            <p>الأستاذ</p>
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
