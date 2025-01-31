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
    $sql = "SELECT id, student_name FROM students WHERE class_id = ?";
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
    <title>تقرير الحصيلة الشهرية</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 20px;
        }
        .header-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .sheet-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .sheet-header img {
            width: 100%; /* Adjust width as needed */
            max-width: 600px; /* Max width of the image */
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
            table-layout: fixed;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px; /* Increased padding */
            text-align: center;
            font-size: 16px; /* Increased font size */
            word-wrap: break-word;
        }
        th {
            background-color: #f8f9fa;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .signature {
            width: 30%;
            text-align: center;
        }
        .print-button {
            margin-bottom: 20px;
            text-align: center;
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
            @page {
                size: landscape;
            }
            .sheet-header h3, .sheet-header p , .signature-section p {
                font-weight: bold;
                font-size: 20px;
            }
            .button-group {
                display: none;
            }
            body {
                margin: 0; /* Ensure no extra margins on print */
                font-size: 12px; /* Maintain font size */
            }
            th {
                font-size: 13px; /* Maintain larger font size in print */
            }

            td {
                font-size: 14px; /* Adjust the font size for table content */
            }

            th, td {
                padding: 10px;
                border: 2px solid black;
                white-space: nowrap; /* Prevents line breaks in print */
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
    طباعة <i class="bi bi-printer-fill" style="margin-right: 8px;"></i> 
    </button>
    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
    الصفحة الرئيسية <i class="bi bi-house-door-fill me-2"></i> 
</button>


</div>
    <div class="sheet-header">
        <img src="../images/header.png" alt="Header Image">
        <h3>تقرير الحصيلة الشهرية</h3>
        <p>الشهر: <?php echo $month; ?>، السنة: <?php echo $year; ?></p>
        <p>الفرع: <?php echo $branch_name; ?></p>
        <p>القسم: <?php echo $class_name; ?></p>
    </div>
    <table>
        <thead>
            <tr>
                
                <th colspan="4">الاسم الكامل</th>
                <!-- Add more headers based on the specific report -->
                <th colspan="2" > عدد الأحزاب</th>
                <th colspan="3" >  المستوى السابق</th>
                <th colspan="3">  المستوى الحالي</th>
                <th colspan="2">  الزيادة</th>
                <th colspan="4">  الملاحظات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                
            <td colspan="4" style="height: 100px;"><?php echo $student['student_name']; ?></td>



<td colspan="2" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td colspan="2" contenteditable="true"></td>
<td colspan="4" style="height: 100px;" contenteditable="true"></td>
            </tr>
            <?php endforeach; ?>
            <!-- Emergency Fields -->
            <tr>
            <td colspan="4" style="height: 100px;"></td>



<td colspan="2" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="2" contenteditable="true"></td>
<td colspan="4" style="height: 100px;" contenteditable="true"></td>
            </tr>
            <tr>
            <td colspan="4" style="height: 100px;"></td>



<td colspan="2" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="2" contenteditable="true"></td>
<td colspan="4" style="height: 100px;" contenteditable="true"></td>
            </tr>
            <tr>
            <td colspan="4" style="height: 100px;"></td>



<td  colspan="2" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="2" contenteditable="true"></td>
<td colspan="4" style="height: 100px;" contenteditable="true"></td>
            </tr>
            <tr>
            <td colspan="4" style="height: 100px;"></td>



<td colspan="2" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="2" contenteditable="true"></td>
<td colspan="4" style="height: 100px;" contenteditable="true"></td>
            </tr>
            <tr>
            <td colspan="4" style="height: 100px;"></td>



<td  colspan="2" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td  colspan="3" contenteditable="true"></td>
<td colspan="2" contenteditable="true"></td>
<td colspan="4" style="height: 100px;" contenteditable="true"></td>
            </tr>
        </tbody>
    </table>

    </div>
</body>
</html>
