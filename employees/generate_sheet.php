<?php
// Include database connection
include 'db_connection.php';
$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

// Query to fetch branch names
$sql = "SELECT branch_id, branch_name FROM branches";
$branchesResult = $conn->query($sql);
?>

<?php
// Include database connection
include 'db_connection.php';

$branches = [];
$classes = [];
$students = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id = $_POST['branch'];
    $class_id = $_POST['class'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

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

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
} else {
    // Fetch branches
    $sql = "SELECT branch_id, branch_name FROM branches";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استمارة المتابعة الأسبوعية</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 10px; /* Reduced margin */
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
            max-width: 500px; /* Adjusted width */
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

        /* Print-specific adjustments */
        @media print {
            .button-group {
                display: none;
            }
            body {
                margin: 0;
                font-size: 20px;
            }
            .sheet-header h3,
            .sheet-header p,
            .signature-section p {
                font-weight: bold;
                font-size: 16px;
            }
            th, td {
                font-size: 19px;
                padding: 2px;
                border: 1px solid black;
                white-space: nowrap;
            }
            table {
                width: 100%;
            }
        }
    </style>
<script>
        function printPage() {
            window.print();
        }
</script>
    <script src="js/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#branch').change(function () {
                var branch_id = $(this).val();
                $.ajax({
                    url: 'get_classes.php',
                    type: 'POST',
                    data: { branch_id: branch_id },
                    success: function (response) {
                        $('#class').html(response);
                    }
                });
            });
        });
    </script>
</head>
<body>
    
<div class="button-group">
    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="printPage()">
    طباعة <i class="fas fa-print" style="margin-right: 8px;"></i> 
    </button>
    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
    الصفحة الرئيسية <i class="bi bi-printer-fill mr-2"></i> 
</button>

<button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='weekly_followup.php'">
الصفحة السابقة <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> 
</button>

</div>

    <div class="sheet-header">
        <img src="../images/header.png" alt="Header Image">
        <h3>استمارة المتابعة الأسبوعية</h3>
        <p>من تاريخ: <?php echo $start_date; ?> إلى تاريخ: <?php echo $end_date; ?></p>
        <p>الفرع: <?php echo $branch_name; ?></p>
        <p>القسم: <?php echo $class_name; ?></p>
        <p> العام الدراسي : <?php echo $last_year; ?></p>
    </div>
    <table>
        <thead>
            <tr>
                <th>رقم النداء</th>
                <th colspan="4">الاسم الكامل</th>
                <th colspan="5">البرنامج اليومي من الكتابة</th>
                <th colspan="5">البرنامج اليومي من المحفوظات</th>
                <th>الزرك</th>
                <th>الأحزاب</th>
                <th>الغياب</th>
                <th colspan="4">الملاحظات</th>
            </tr>
            <tr>
                <th colspan="5"></th>
                <th>السبت</th>
                <th>الأحد</th>
                <th>الاثنين</th>
                <th>الثلاثاء</th>
                <th>الأربعاء</th>
                <th>السبت</th>
                <th>الأحد</th>
                <th>الاثنين</th>
                <th>الثلاثاء</th>
                <th>الأربعاء</th>
                <th colspan="6"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo $student['student_id']; ?></td>
                
                <td colspan="4" style="height: 30px;"><?php echo $student['student_name']; ?></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td colspan="4" style="height: 30px;" contenteditable="true"></td>
            </tr>
            <?php endforeach; ?>

            <!-- Emergency Fields -->
            <tr>
                <td contenteditable="true"></td>
                <td colspan="4" contenteditable="true" style="height: 30px;"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td  contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td colspan="4" style="height: 30px;" contenteditable="true"></td>
            </tr>
            <!-- Emergency Fields -->
            <tr>
                <td contenteditable="true"></td>
                <td colspan="4" contenteditable="true" style="height: 30px;"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td  contenteditable="true"></td>
                <td colspan="4" style="height: 30px;" contenteditable="true"></td>
            </tr>
            <!-- Emergency Fields -->
            <tr>
                <td contenteditable="true"></td>
                <td colspan="4" contenteditable="true" style="height: 30px;"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td  contenteditable="true"></td>
                <td colspan="4" style="height: 30px;" contenteditable="true"></td>
            </tr>
            <!-- Emergency Fields -->
            <tr>
                <td contenteditable="true"></td>
                <td colspan="4" contenteditable="true" style="height: 30px;"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td  contenteditable="true"></td>
                <td colspan="4" style="height: 30px;" contenteditable="true"></td>
            </tr>
            <!-- Emergency Fields -->
            <tr>
                <td contenteditable="true"></td>
                <td colspan="4" contenteditable="true" style="height: 30px;"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td  contenteditable="true"></td>
                <td colspan="4" style="height: 30px;" contenteditable="true"></td>
            </tr>

            <!-- New Total Row with colspan="10" -->
            <tr>
            <td colspan="10" class="total-cell">المجموع :</td>
                <td colspan="1" contenteditable="true"></td>
                <td colspan="1" contenteditable="true"></td>
                <td colspan="1" contenteditable="true"></td>
                <td colspan="1" contenteditable="true"></td>
                <td colspan="1" contenteditable="true"></td>
                <td colspan="1" contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td colspan="1" contenteditable="true"></td>
                <td colspan="4" style="height: 30px;" contenteditable="true"></td>
            </tr>

            <!-- Continue the rest of the rows if necessary -->
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature">
            <p>الأستاذ</p>
            <p>__________________</p>
        </div>
        <div class="signature">
            <p>تاريخ التسليم</p>
            <p>__________________</p>
        </div>
        <div class="signature">
            <p>توقيع الأستاذ</p>
            <p>__________________</p>
        </div>
        <div class="signature">
            <p>توقيع الإدارة</p>
            <p>__________________</p>
        </div>
    </div>
</body>
</html>