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

// Fetch branches from the database
$sqlBranches = "SELECT branch_id, branch_name FROM branches";
$resultBranches = $conn->query($sqlBranches);

$branches = [];
if ($resultBranches->num_rows > 0) {
    while ($row = $resultBranches->fetch_assoc()) {
        $branches[] = $row;
    }
}

// Define quarterly months
$quarterlyMonths = [
    'الفصل الأول' => ['ديسمبر', 'نوفمبر', 'أكتوبر'],
    'الفصل الثاني' => ['مارس', 'فبراير', 'يناير'],
    'الفصل الثالث' => ['يونيو', 'مايو', 'أبريل'],
    'الفصل الرابع' => ['سبتمبر', 'أغسطس', 'يوليو'],
];

$selectedBranch = $_POST['branch'] ?? '';
$selectedClass = $_POST['class'] ?? '';
$selectedQuarterly = $_POST['quarterly'] ?? '';
$selectedYear = $_POST['year'] ?? '';

// Fetch the selected class name
$className = '';
if (!empty($selectedClass)) {
    $stmt = $conn->prepare("SELECT class_name FROM classes WHERE class_id = ?");
    $stmt->bind_param("i", $selectedClass);
    $stmt->execute();
    $stmt->bind_result($className);
    $stmt->fetch();
    $stmt->close();
}

if (!empty($selectedClass)) {
    // Fetch total number of students
    $sql = "SELECT COUNT(*) FROM students WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $selectedClass);
    $stmt->execute();
    $stmt->bind_result($total_students);
    $stmt->fetch();
    $stmt->close();

    // Initialize variables for pagination
    $students_per_page = 10; // Example, can be set dynamically
    $page = $_GET['page'] ?? 1; // Current page number
    $offset = ($page - 1) * $students_per_page;

    // Calculate total pages
    $total_pages = ceil($total_students / $students_per_page);

    // Fetch students for the current page
    $sql = "SELECT student_id, student_name FROM students WHERE class_id = ? LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $selectedClass, $offset, $students_per_page);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
} else {
    $total_students = 0;
    $total_pages = 0;
    $students = [];
}
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">

    <style>
       body {
            font-family: 'Arial', sans-serif;
            direction: ltr;
            text-align: right;
            margin: 20px;
        }
        .sheet-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .sheet-header img {
            width: 100%;
            max-width: 600px;
            height: auto;
        }
        .sheet-header p {
            display: inline-block;
            margin: 0 10px; /* Adjusts space between the items */
            font-size: 14px; /* Adjust font size as needed */
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 18px;
            background-color: #ffffff;
        }

        th, td {
            border: 3px solid black;
            padding: 20px; /* Increased padding for larger cells */
            text-align: center;
            vertical-align: middle;
        }

        th {
            
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #ffffff;
        }

        tr:nth-child(odd) {
            background-color: #ffffff;
        }

        /* Specific column styling */
        td[colspan="6"] {
            background-color: #ffffff;
        }

        /* Row and column spanning */
        td[rowspan="2"] {
            vertical-align: middle;
            font-weight: bold;
        }

        /* Additional styling for the entire page */
        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
       /* Print-specific adjustments */
        @media print {
            @page {
                size: A5 landscape; /* Set page size to A5 in landscape orientation */
                margin: 0.5cm; /* Adjust margin to fit more content */
            }
            .button-group {
                display: none; /* Hide button group in print */
            }
            th, td {
                white-space: nowrap; /* Prevent line breaks in print */
            }
            .sheet-header h3,
            .sheet-header p,
            .signature-section p {
                font-weight: bold;
                font-size: 16px;
            }
            }
            table {
                width: 100%;
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
        الصفحة الرئيسية <i class="bi bi-house-fill mx-2"></i> 
        </button>
    </div>
    
    <div class="sheet-header">
        <img src="../images/header.png" alt="Header Image">
    </div>
    <div class="form-container">
    <?php if ($selectedBranch && $selectedClass && $selectedQuarterly && $selectedYear): ?>
        <?php
        // Calculate the Arabic year (Hijri year)
        $arabicYear = date('Y', strtotime($selectedYear . '-01-01')) + 1445 - 2024;
        ?>

        <h1> إدارة الدروس - العام الدراسي: {<?php echo $last_year; ?>} - <?php echo $arabicYear; ?> | الفصل: <?php echo $selectedQuarterly; ?> </h1>
        <h1>القسم: <?php echo $className; ?></h1>
        <table>
    <tr>
    <td contenteditable="true">الملاحظات</td>
        <td contenteditable="true">زيادة المتون و التربية</td>
        <td contenteditable="true">عدد الأحزاب إجمالا</td>
        <td contenteditable="true">مجموع الغياب</td>
        <td contenteditable="true">مجموع الحصيلة</td>
        
        <?php if (isset($quarterlyMonths[$selectedQuarterly])): ?>
            <?php foreach ($quarterlyMonths[$selectedQuarterly] as $month): ?>
                <td colspan="2" contenteditable="true">حصيلة الشهر (<?php echo $month . " " . $selectedYear; ?>)</td>
            <?php endforeach; ?>
        <?php endif; ?>

        <td colspan="" rowspan="" contenteditable="true" style="vertical-align: top; padding-top: 10px;">اسم الطالب (ة)</td>
    </tr>
    
    <tr rowspan="2">
        <td rowspan="2" contenteditable="true">&nbsp;</td>
        <td rowspan="2" contenteditable="true">&nbsp;</td>
        <td rowspan="2" contenteditable="true">&nbsp;</td>
        <td rowspan="2" contenteditable="true">&nbsp;</td>
        <td rowspan="2" contenteditable="true">&nbsp;</td>

        <!-- Generate headers for 'الغياب' and 'الحصيلة' based on selected months -->
        <?php if (isset($quarterlyMonths[$selectedQuarterly])): ?>
            <?php foreach ($quarterlyMonths[$selectedQuarterly] as $month): ?>
                <td rowspan="2" >الغياب</td>
                <td rowspan="2" >الحصيلة</td>
            <?php endforeach; ?>
        <?php endif; ?>
    </tr>

    <!-- Loop through students -->
    <tr>
    <td rowspan="" colspan="" contenteditable="true">&nbsp;</td>
            </th>
    <tr>
    <?php foreach ($students as $student): ?>
        
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
        <td><?php echo $student['student_name']; ?></td>
        
    </tr>
    <?php endforeach; ?>


    <!-- <tr>
        <td colspan="10" contenteditable="true">&nbsp;</td>
        <td contenteditable="true">الملاحظات</td>
    </tr> -->
</table>


    <?php else: ?>
        <p class="text-center">يرجى اختيار الفرع والصف والفصل والسنة لعرض البيانات.</p>
    <?php endif; ?>
</div>


<div class="signature-section">
        <div class="signature">
            <p>الإدارة</p>
            <p>__________________</p>
        </div>
        
    </div>

</body>
</html>
