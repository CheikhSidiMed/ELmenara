<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to store form selections
session_start();

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
    $_SESSION['branch'] = $_POST['branch'] ?? '';
    $_SESSION['class'] = $_POST['class'] ?? '';
    $_SESSION['month'] = $_POST['month'] ?? '';
}

// Retrieve stored values from session
$selectedBranch = isset($_SESSION['branch']) ? $_SESSION['branch'] : '';
$selectedClass = isset($_SESSION['class']) ? $_SESSION['class'] : '';
$selectedMonth = isset($_SESSION['month']) ? $_SESSION['month'] : '';

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
$branch_id = $selectedBranch;

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

// Arabic quarterly names
$arabic_quarterly = [
    'الفصل الأول', 'الفصل الثاني', 'الفصل الثالث', 'الفصل الرابع'
];

// Define quarterly months
$quarterlyMonths = [
    'الفصل الأول' => ['ديسمبر', 'نوفمبر', 'أكتوبر'],
    'الفصل الثاني' => ['مارس', 'فبراير', 'يناير'],
    'الفصل الثالث' => ['يونيو', 'مايو', 'أبريل'],
    'الفصل الرابع' => ['سبتمبر', 'أغسطس', 'يوليو'],
];

// Fetch the selected class name and all students in the class
$className = '';
$students = [];
if (!empty($selectedClass)) {
    $stmt = $conn->prepare("SELECT class_name FROM classes WHERE class_id = ?");
    $stmt->bind_param("i", $selectedClass);
    $stmt->execute();
    $stmt->bind_result($className);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT id, student_name FROM students WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $selectedClass);
    $stmt->execute();
    $result = $stmt->get_result();

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
    <title>    الحصيلة الفصلية </title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">

    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
    <style>
        input.no-border {
            border: none;              
            outline: none;             
            padding: 2px;              
            font-size: 14px;  
            width: 98%;       
        }




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
                font-family: 'Tajawal', sans-serif;
                direction: rtl;
                text-align: right;
                font-size: 14px;
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
                font-size: 14px !important; /* Adjust font size as needed */
            }
            .sheet-header img {
                width: 100%;
                max-width: 1400px; /* Adjusted width */
                height: 60px !important;
            }
            table {
                width: 100%;
                table-layout: fixed;
            }
            th, td {
                border: 1px solid black;
                padding: 1px;
                font-size: 14px !important;

            }
            th {
                font-size: 15px !important;
                background-color: #f8f9fa;
            }
            .signature-section {
                position: absolute;
                left: 0;
                padding-left: 20px; /* Optional: Add some padding for spacing */
            }

            .signature {
                text-align: center; /* Ensure the text aligns properly */
                direction: rtl;   /* Maintain the Arabic text alignment */
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
            /* Hide unnecessary elements */
            .button-group, .form-container, .container-fluid {
                display: none;
            }
            .print-date {
                display: block; /* Show only during print */
                text-align: right;
                font-weight: bold;
            }
            /* General body adjustments for print */
            body {
                font-size: 14px !important; /* Smaller font to fit more content */
                align-items: center;
                margin-bottom: 0px !important;

            }

            /* Ensure table width fits the page */
            table {
                width: 100%;
                border-collapse: collapse;
                text-align: start;
                table-layout: fixed; /* Forces table to fit the page width */

            }
            input.no-border {
            border: none;              
            outline: none;             
            padding: 2px;              
            font-size: 14px;  
            width: 98%;       
        }
            h3 {
                font-size: 16px;

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

            /* Landscape orientation to fit more columns */
            @page {
                size: landscape;
            }

            
            th, td {
                font-size: 13px !important; /* Further reduce font size within table */
                padding: 1px;
                border: 1px solid black;
                white-space: wrap;
                overflow: hidden;
                
            }
            th {
                text-align: center;
                font-size: 15px !important;
                background-color: #f8f9fa;
            }
            .sheet-header p,
            .signature-section p {
                font-weight: bold;
                font-size: 14px;

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
        <h3>الحصيلة الفصلية</h3>
        <form action="" method="POST">
            <div class="form-row">
                <div class="form-group col-md-2 align-self-end">
                    <button type="button"  onclick="window.location.href='quarterly_selection_student.php'" class="btn btn-primary btn-block"> الاستمارة الطالب (ة) </button>
                </div>
                <div class="form-group col-md-2">
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
                <div class="form-group col-md-2">
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
                    <label for="month">اختر الفصل:</label>
                    <select name="month" id="month" class="form-control" required>
                        <option value="">اختر الفصل</option>
                        <?php foreach ($arabic_quarterly as $quarter): ?>
                            <option value="<?php echo $quarter; ?>" <?php if ($selectedMonth == $quarter) echo 'selected'; ?>>
                                <?php echo $quarter; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group col-md-2">
                    <label class="fyear" for="financial-year" style="margin-left: 15px;"> العام الدراسي:</label>
                    <select id="financial-year" class="form-control w-100">
                        <option><?php echo $last_year; ?></option>
                    </select>
                </div>
                <div class="form-group col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">عرض الاستمارة</button>
                </div>
            </div>
        </form>
    </div>


    <div class="button-group">
        <button type="button" class="btn btn-primary d-flex align-items-center" onclick="printPage()">
            طباعة <i class="bi bi-printer-fill" style="margin-right: 8px;"></i> 
        </button>


        <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
        الصفحة الرئيسية <i class="bi bi-home" style="margin-right: 8px;"></i> 
        </button>

    </div>
    <div class="">
    <div class="sheet-header receipt-header">
        <img src="../images/header.png" width="100%" alt="Header Image">
        <h3 style="margin-top: 3px; margin-bottom: -1px;"> الحصيلة الفصلية </h3>
        <P>إدارة الدروس - العام الدراسي: <?php echo $last_year; ?> | 
        الفصل: <?php echo in_array($selectedMonth, $arabic_quarterly) ? $selectedMonth : 'غير محدد'; ?> </P>
        <P class="print-date">التاريخ : <?php echo date('Y-m-d'); ?></P> <!-- Print date only visible during print -->

    </div>
    <div class="for-container">
    <?php if ($selectedBranch && $selectedClass && $selectedMonth): ?>

        <form action="save_student_data.php" method="post">
            <table>
                <tr>
                    <td style="max-width: 10px" rowspan="2">اسم الطالب (ة)</td>
                    <?php if (!empty($selectedMonth) && in_array($selectedMonth, $arabic_quarterly)): ?>
                        <?php if (isset($quarterlyMonths[$selectedMonth])): ?>
                            <?php foreach (array_reverse($quarterlyMonths[$selectedMonth]) as $month): ?>
                                <td colspan="2">حصيلة الشهر (<?php echo $month; ?>)</td>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>فصل غير موجود.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p></p>
                    <?php endif; ?>
                    <td class="max-width" rowspan="2">مجموع الحصيلة</td>
                    <td class="max-width" rowspan="2">مجموع الغياب</td>
                    <td class="max-width" rowspan="2">عدد الأحزاب إجمالا</td>
                    <td class="max-width" rowspan="2">زيادة المتون و التربية</td>
                    <td class="max-width" rowspan="2">الملاحظات</td>
                </tr>
                <tr>
                    <td>الحصيلة</td>
                    <td>الغياب</td>
                    <td>الحصيلة</td>
                    <td>الغياب</td>
                    <td>الحصيلة</td>
                    <td>الغياب</td>
                </tr>

                <?php foreach ($students as $student) {
                    $studentId = $student['id'];

                    $stmt = $conn->prepare("
                        SELECT month_1_income, month_1_absence, month_2_income, month_2_absence, 
                            month_3_income, month_3_absence, total_income, total_absence, 
                            total_groups, extra, notes
                        FROM student_performance 
                        WHERE student_id = ? AND quarter = ?");  
                    $stmt->bind_param('is', $studentId, $selectedMonth);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows > 0) {
                        $stmt->bind_result($month1Income, $month1Absence, $month2Income, $month2Absence, 
                                        $month3Income, $month3Absence, $totalIncome, $totalAbsence, 
                                        $totalGroups, $extra, $notes);
                        $stmt->fetch();
                    } else {
                        // Set variables to empty if no data exists
                        $month1Income = $month1Absence = $month2Income = $month2Absence = 
                        $month3Income = $month3Absence = $totalIncome = $totalAbsence = 
                        $totalGroups = $extra = $notes = '';
                    }
                    $stmt->close();
                ?>
                <tr>
                    <td><?php echo $student['student_name']; ?></td>
                    <input type="hidden" name="student_ids[]" value="<?php echo $student['id']; ?>" />
                    <input type="hidden" name="quarter" value="<?php echo $selectedMonth; ?>" />
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_1_income]" value="<?php echo htmlspecialchars($month1Income); ?>" class="no-border"/></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_1_absence]" value="<?php echo htmlspecialchars($month1Absence); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_2_income]" value="<?php echo htmlspecialchars($month2Income); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_2_absence]" value="<?php echo htmlspecialchars($month2Absence); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_3_income]" value="<?php echo htmlspecialchars($month3Income); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_3_absence]" value="<?php echo htmlspecialchars($month3Absence); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][total_income]" value="<?php echo htmlspecialchars($totalIncome); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][total_absence]" value="<?php echo htmlspecialchars($totalAbsence); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][total_groups]" value="<?php echo htmlspecialchars($totalGroups); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][extra]" value="<?php echo htmlspecialchars($extra); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][notes]" value="<?php echo htmlspecialchars($notes); ?>" class="no-border" /></td>
                </tr>
                <?php } ?>  
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <tr>
                        <?php for ($j = 0; $j < 12; $j++): ?>
                            <td><input type="text" name="new_student_data[<?php echo $j; ?>]" class="no-border" /></td>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>

            </table>
            <div class="signature-section">
                <div class="signature">
                    <p>الإدارة</p>
                    <p>__________________</p>
                </div>      
            </div>
            <div class="button-group">
                <button type="submit" class="btn btn-primary d-flex align-items-center my-3" >حفظ البيانات</button>
            </div> 
        </form>

    <?php else: ?>
        <p class="text-center">يرجى اختيار الفرع والصف والفصل والسنة لعرض البيانات.</p>
    <?php endif; ?>
</div>






</body>
</html>
