
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
$role_id = $_SESSION['role_id'];

include 'db_connection.php';
// Initialize variables
$last_year = "";
$branch_name = "";
$class_name = "";
$username = "";
$students = [];
$classes = [];
$month = 0;
$branch_id = null;
$class_id = null;


$arabic_months = [
    'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
    'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
];



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

        $sqlClasses = '';
        if($role_id == 6){
            $sqlClasses = "SELECT c.class_id, c.class_name
                FROM classes c
                JOIN branches AS b ON c.branch_id = b.branch_id
                JOIN user_branch AS ub ON ub.class_id = c.class_id
                WHERE c.branch_id = ?";
        }else{
            $sqlClasses = "SELECT class_id, class_name
                FROM classes WHERE branch_id = ?";
        }
        $stmt = $conn->prepare($sqlClasses);
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
    $sql = "SELECT s.id, s.student_name, count(ab.created_at) AS ABS
        FROM students AS s
        LEFT JOIN
            absences AS ab ON s.id = ab.student_id AND (ab.created_at IS NULL OR MONTH(ab.created_at) = ? )
        WHERE class_id = ?
        GROUP BY
                s.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $month, $class_id);
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
    <title>استمارة الحصيلة الشهرية</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
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
                font-size: 18px; /* Adjust font size as needed */
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
        <h2 class="text-center">استمارة الحصيلة الشهرية</h2>
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
                        <option value="1">يناير</option>
                        <option value="2">فبراير</option>
                        <option value="3">مارس</option>
                        <option value="4">أبريل</option>
                        <option value="5">مايو</option>
                        <option value="6">يونيو</option>
                        <option value="7">يوليو</option>
                        <option value="8">أغسطس</option>
                        <option value="9">سبتمبر</option>
                        <option value="10">أكتوبر</option>
                        <option value="11">نوفمبر</option>
                        <option value="12">ديسمبر</option>
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
            <h3>تقرير الحصيلة الشهرية</h3>
            <p class="p-head">الشهر: <?php echo $arabic_months[$month-1]??''; ?>،  العام الدراسي: <?php echo $last_year; ?></p>
            <p class="p-head">الفرع: <?php echo $branch_name; ?></p>
            <p class="p-head">القسم: <?php echo $class_name; ?></p>
            <p class="print-date">التاريخ : <?php echo date('d/m/Y'); ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 13%; font-size: 18px">الاسم الكامل</th>
                    <th style="width: 8%; font-size: 18px"> عدد الأحزاب</th>
                    <th style="width: 8%; font-size: 18px"> مقدار الحفظ</th>
                    <th style="width: 20%; font-size: 18px">  المستوى السابق</th>
                    <th style="width: 20%; font-size: 18px">  المستوى الحالي</th>
                    <th style="width: 19%; font-size: 18px">  الزيادة</th>
                    <th style="width: 2%; font-size: 18px">  الغياب</th>
                    <th style="width: 11%; font-size: 18px">  الملاحظات</th>
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
                    <td contenteditable="true" style="text-align: center;"><?php echo $student['ABS']; ?></td>
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
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
            </tr>
            <?php endfor; ?>
                
            </tbody>
        </table>
        <div class="signature-section">
            <div class="signature">
                <p>الأستاذ </p>
                <P style="margin-top: -20px; font-weight: bold;"><?php echo $username; ?></P>
                <p style="margin-top: -35px;">__________________</p>
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
    </div>

</body>
</html>

<?php
$conn->close();
?>


