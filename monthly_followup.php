
<?php
// Include database connection
include 'db_connection.php';
// Initialize variables
$last_year = "";
$branch_name = "";
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
$sqlBranches = "SELECT branch_id, branch_name FROM branches";
$resultBranches = $conn->query($sqlBranches);

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id = $_POST['branch'];
    
    // Fetch classes based on the selected branch
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
            <h3>تقرير الحصيلة الشهرية</h3>
            <p>الشهر: <?php echo $month; ?>،  العام الدراسي: <?php echo $last_year; ?></p>
            <p>الفرع: <?php echo $branch_name; ?></p>
            <p>القسم: <?php echo $class_name; ?></p>
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
                    <th style="width: 4%; font-size: 18px">  الغياب</th>
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
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
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
    </div>

</body>
</html>

<?php
$conn->close();
?>


