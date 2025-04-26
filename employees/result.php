
<?php

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}

$user_id = $_SESSION['userid'];
$role_id = $_SESSION['role_id'];


include 'db_connection.php';
// Initialize variables
$last_year = "";
$branch_name = "";
$username = '';

$class_name = "";
$students = [];
$classes = [];
$month = "";
$branch_id = null;
$class_id = null;

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}
// Initialize arrays to hold branch and class data
$branches = [];
$classes = [];

// Fetch branches from the database

$sql = "SELECT b.branch_id, b.branch_name
    FROM branches b
    JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$resultBranches = $stmt->get_result();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id = $_POST['branch'];
    
    // Fetch classes based on the selected branch
    if (!empty($branch_id)) {
        $classesQuery = '';
        if($role_id == 6){
            $classesQuery = "SELECT c.class_id, c.class_name
                FROM classes c
                JOIN branches AS b ON c.branch_id = b.branch_id
                JOIN user_branch AS ub ON ub.class_id = c.class_id
                WHERE c.branch_id = ?";
        }else{
            $classesQuery = "SELECT class_id, class_name
                FROM classes WHERE branch_id = ?";
        }
        $stmt = $conn->prepare($classesQuery);
        $stmt->bind_param('i', $branch_id);
        $stmt->execute();
        $resultClasses = $stmt->get_result();
        
        while ($row = $resultClasses->fetch_assoc()) {
            $classes[] = $row;
        }
        $stmt->close();
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class'];
    $month = $_POST['month'];
    $year = $_POST['year'];

    $sql = "SELECT c.class_name, b.branch_name, e.full_name
            FROM classes AS c
            JOIN branches AS b ON c.branch_id = b.branch_id
            LEFT JOIN user_branch AS ub ON ub.class_id = c.class_id
            LEFT JOIN users AS u ON u.id = ub.user_id
            LEFT JOIN employees AS e ON e.id = u.employee_id
            WHERE c.class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $stmt->bind_result($class_name, $branch_name, $username);
    $stmt->fetch();
    $stmt->close();

    // Fetch students in the selected class
    $sql = "SELECT id, student_name FROM students WHERE etat=0 AND is_active=0 AND class_id = ?";
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

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>   نتائج تقييم  </title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
    <style>

        h2 {
            text-align: center;
            font-weight: bold;
            margin-bottom: 4px;
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
            max-width: 1400px; /* Adjusted width */
            height: auto;
        }
        table {
            width: 98%;
            margin-left: 0px;
            margin-right: 20px;
        }
        th, td {
            border: 1px solid black;
            padding: 3px;
        }
        th {
            background-color: #f8f9fa;
        }

        .signature-section {
            display: flex;
            justify-content: center;
            margin-top: 3px; /* Reduced margin */
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
            margin-left: 40px;
            margin-right: 40px;
            margin-top: 10px;
            
        }
        .btn i {
        margin-right: 8px; /* Adjust the value as needed */
        }
   

        @media print {
            .button-group, .form-container, .container-fluid {
                display: none;
            }

            .print-date {
                display: block;
                text-align: right;
                font-weight: bold;
                margin-top: 10px;
            }
            body {
                font-size: 12px;
            }

            .form-container, table{
                transform: scale(.9);
                text-align: center;
                margin-right: -30px !important;
            }
            table {
                margin-top: -20px;
                width: 96%;
                border-collapse: collapse;
                font-size: 12px !important;
                text-align: start;
                table-layout: fixed;
            }
            .receipt-header{
                margin-right: -180px !important;

            }

            @page {
                size: A4;
            }
            tr{
                height: 31px;
            }

            th {
                font-size: 14px;
                padding: 0px  !important;
                border: 1px solid black;
                white-space: wrap;
            }
            td {
                font-size: 15px;
                font-weight: bold;
                padding: 0px !important;
                margin: 0px  !important;
                border: 1px solid black;
                white-space: wrap;
            }
            h3 {
                font-weight: bold;
                font-size: 18px;

            }
            /* Bold styling for header sections */
            .sheet-header,
            .sheet-header p,
            .signature-section p {
                font-weight: bold;
                font-size: 14px;
            }
            .sheet-header {
                margin-top: -16px;

            }

            /* Handling for large tables, allowing page breaks within the rows
            tr {
                page-break-inside: avoid;
            } */
            table{
                margin-top: 1px !important;
            }
            .p-head{
                font-size: 15px !important;
            }
            .tbl{
                padding-left: 20px;
            }
        }
        .p-head{
            font-size: 21px !important;
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
        function printPage() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="form-container">
        <h2 class="text-center">   نتائج تقييم  </h2>
        <form action="" method="POST">
            <div class="form-row">

                <div class="form-group col-md-3">
                    <label for="branch">اختر الفرع:</label>
                    <select name="branch" id="branch" class="form-control" onchange="this.form.submit()">
                        <option value="">اختر الفرع</option>
                        <?php while ($row = $resultBranches->fetch_assoc()): ?>
                            <option value="<?php echo $row['branch_id']; ?>" <?php if (isset($branch_id) && $branch_id == $row['branch_id']) echo 'selected'; ?>><?php echo $row['branch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <label for="class">اختر الصف:</label>
                    <select name="class" id="class" class="form-control">
                        <option value="">اختر الصف</option>
                        <?php if (!empty($classes)): ?>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>"><?php echo $class['class_name']; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group col-md-2">
                    <label for="month">اختر الشهر:</label>
                    <select name="month" id="month" class="form-control">
                        <option value="يناير">يناير</option>
                        <option value="فبراير">فبراير</option>
                        <option value="مارس">مارس</option>
                        <option value="أبريل">أبريل</option>
                        <option value="مايو">مايو</option>
                        <option value="يونيو">يونيو</option>
                        <option value="يوليو">يوليو</option>
                        <option value="أغسطس">أغسطس</option>
                        <option value="سبتمبر">سبتمبر</option>
                        <option value="أكتوبر">أكتوبر</option>
                        <option value="نوفمبر">نوفمبر</option>
                        <option value="ديسمبر">ديسمبر</option>
                    </select>
                </div>

                <div class="form-group col-md-2">
                    <label for="year"> العام الدراسي:</label>
                    <select name="year" id="year" class="form-control">
                        <option><?php echo $last_year; ?></option>

                    </select>
                </div>
                <div class="form-group col-md-2 align-self-end">
                    <button type="sibmit" class="btn btn-primary btn-block" id="generate-button">عرض التقرير</button>
                </div>
            </div>
        </form>
    </div>


        <div class="button-group">
            <button type="button" class="btn btn-primary d-flex align-items-center" onclick="printPage()">
                طباعة <i class="bi bi-printer-fill" style="margin-right: 8px;"></i>
            </button>
            <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
                الصفحة الرئيسية <i class="bi bi-house-door-fill me-2"></i>
            </button>
        </div>
        <div class="sheet-header receipt-header">
            <img src="../images/header.png" width="100%" alt="Header Image">
            <p class="p-head">  نتائج تقييم </p>
            <p class="p-head">الشهر: <?php echo $month; ?>،  العام الدراسي: <?php echo $last_year; ?></p>
            <p class="p-head">الفرع: <?php echo $branch_name; ?></p>
            <p class="p-head">القسم: <?php echo $class_name; ?></p>
            <p class="print-date">التاريخ : <?php echo date('d/m/Y'); ?></p>
        </div>
        <div class="table-responsive tbl">
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%; font-size: 18px">الاسم الكامل</th>
                        <th style="width: 18%; font-size: 18px">  التقييم (الكيف) </th>
                        <th style="width: 18%; font-size: 18px">  الحصيلة ( الكم)</th>
                        <th style="width: 18%; font-size: 18px">   مستوى الأداء </th>
                        <th style="width: 18%; font-size: 18px">   النتيجة </th>
                        <th style="width: 18%; font-size: 18px">  التقدير النهائي</th>
                    
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo $student['student_name']; ?></td>
                        <td contenteditable="true"></td>
                        <td contenteditable="true"></td>
                        <td contenteditable="true"></td>
                        <td contenteditable="true"></td>
                        <td contenteditable="true"></td>
                
                    </tr>
                    <?php endforeach; ?>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <tr>
                        <td contenteditable="true"></td>
                        <?php for ($j = 0; $j < 4; $j++): ?>
                        <td contenteditable="true"></td>
                    <?php endfor; ?>
                    <td contenteditable="true"></td>
                    
                </tr>
                <?php endfor; ?>
                    
                </tbody>
            </table>
        </div>
     
        <div class="signature-section">
            <div class="signature">
                <p style="font-size: 18px;"> الأستاذ(ة)</p>
                <p style="margin-top: -20px; font-weight: bold;"><?php echo $username; ?></p>
                <p style="margin-top: -32px;">_________</p>
            </div>
            <div class="signature">
                <p style="font-size: 18px;">تاريخ التسليم</p>
                <p style="margin-top: -15px;">_________</p>
            </div>
            <div class="signature">
                <p style="font-size: 18px;">توقيع الأستاذ(ة)</p>
                <p style="margin-top: -15px;">_________</p>
            </div>
            <div class="signature">
                <p style="font-size: 18px;">توقيع الإدارة</p>
                <p style="margin-top: -15px;">_________</p>
            </div>
        </div>

    


</body>
</html>

<?php
$conn->close();
?>