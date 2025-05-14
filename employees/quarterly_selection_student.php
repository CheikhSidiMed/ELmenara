<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to store form selections

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Include database connection
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
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

// Store student_name in session if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id'])) {
        $_SESSION['id'] = $_POST['id'];
    }
    if (isset($_POST['month'])) {
        $_SESSION['month'] = $_POST['month'];
    }
}

$selectedMonth = isset($_SESSION['month']) ? $_SESSION['month'] : '';

$quarterlyMonths_number = [
    'الفصل الأول' => ['12', '11', '10'],
    'الفصل الثاني' => ['3', '2', '1'],
    'الفصل الثالث' => ['6', '5', '4'],
    'الفصل الرابع' => ['9', '8', '7'],
];

$months = $selectedMonth !== '' ? $quarterlyMonths_number[$selectedMonth] : '';

$selectedYear = date('Y');
$branch = '';
$class_name = '';
$branch_name = '';

$className = '';
$students = [];
$studentAbsences = [];

// Check if student_name is set and not empty
if (!empty($_POST['id'])) {
    $id = $_POST['id'];
    $sql = "SELECT s.id, s.student_name, b.branch_name, c.class_name,
                MONTH(ab.created_at) AS absence_month,
                COUNT(ab.created_at) AS absences
            FROM students AS s
            LEFT JOIN branches AS b ON s.branch_id = b.branch_id
            LEFT JOIN classes AS c ON s.class_id = c.class_id
            LEFT JOIN
                absences AS ab
                ON s.id = ab.student_id
                AND MONTH(ab.created_at) IN (?, ?, ?)
            WHERE s.id LIKE ?
            GROUP BY
                s.id, absence_month";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiii', $months[0], $months[1], $months[2], $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $month = $row['absence_month'];
        $absences = $row['absences'];
        $class_name = $row['class_name'];
        $branch_name = $row['branch_name'];
        $students[$id] = [
            'id' => $id,
            'student_name' => $row['student_name'],
            'branch_name' => $row['branch_name'],
            'class_name' => $row['class_name'],
        ];
        $studentAbsences[$id][$month] = $absences;
    }
    $stmt->close();

    $students = array_values($students);

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
        #agentDropdown {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 150px;
            overflow-y: auto;

            border: 1px solid #ddd;
            background-color: #fff;
        }

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
            input.no-border {
            border: none;
            outline: none;
            padding: 2px;
            font-size: 14px;
            width: 98%;
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
                font-size: 17px !important;
                padding: 1px;
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

            /* Print-specific adjustments */
        @media print {
            .button-group, .form-container, .container-fluid {
                display: none;
        }

        body {
            font-size: 10px; /* Smaller font to fit more content */
            margin: 0;
            margin-top: 10px;
            padding: 0;
        }

        table {
            width: 100%;
            table-layout: fixed; 
            transform: scale(0.85); /* Slight scaling to reduce overall table size */
            transform-origin: top center; /* Centers scaling from the top */
            margin: auto;
            color: black;
        }
        .sheet {
            width: 90%;
            table-layout: fixed;
            transform: scale(0.85);
            transform-origin: top right;
            margin: auto;
        }


        @page {
            size: A5;
            margin: 0; 
        }
        input.no-border {
                border: none;              
                outline: none;             
                padding: 1px;              
                font-size: 11px;  
                width: 94%;       
            }
        /* Column and table styling */
        th, td {
            font-size: 11px; /* Smaller font for table cells */
            padding: 1px; /* Minimal padding to save space */
            border: 1px solid black;
            word-wrap: break-word; /* Allows words to wrap within the cell */
            white-space: normal; /* Allows text to wrap to multiple lines */
            text-align: start; /* Center-align content for readability */
        }

        th {
            font-size: 12px; /* Smaller font for table cells */

            font-weight: bold;
            white-space: nowrap;
            text-align: center; /* Center-align content for readability */

        }

        /* Bold styling for header sections */
        .sheet-header h3,
        .sheet-header p,
        .signature-section p {
            font-weight: bold;
            font-size: 11px; 
            margin-left: 52px;
        }

        /* Handling for large tables, allowing page breaks within rows */
        tr {
            page-break-inside: avoid;
        }
        .all {
            width: 100%;
            table-layout: fixed; /* Forces table to fit the page width */
            transform: scale(0.85); 
            transform-origin: top right; 
            margin: auto; 
            margin-right: -52px;
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
                
                <div class="form-group col-md-5">
                <div class="position-relative">
                    <!-- <label for="student_name" class="form-label">اسم التلميذ</label>
                    <div id="agentDropdown" class="dropdown-menu position-absolute" style="display: none;"></div> -->
                    <input type="hidden" class="form-control" id="student_id" name="id" required>

                    <label for="student_name" class="form-label">اسم التلميذ</label>
                        <input type="text" class="form-control" id="name_student" name="student_name" placeholder="أدخل اسم التلميذ" required>
                        <div id="agentDropdown" class="dropdown-menu"></div>
                </div>
                </div>
                <div class="form-group col-md-3">
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

                <div class="form-group col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">عرض الاستمارة</button>
                </div>
                <div class="form-group col-md-2 align-self-end">
                    <button type="button"  onclick="window.location.href='quarterly_selection.php'" class="btn btn-primary btn-block"> الاستمارة الطلاب </button>
                </div>
            </div>
        </form>
    </div>


    <div class="button-group">
        <button type="button" class="btn btn-primary d-flex align-items-center" onclick="printPage()">
            طباعة <i class="bi bi-printer-fill" style="margin-left: 8px;"></i>
        </button>


        <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
        الصفحة الرئيسية <i class="bi bi-home" style="margin-right: 8px;"></i>
        </button>

    </div>
<div class="all">
    <div class="sheet">
        <img src="../images/header.png" width="100%" alt="Header Image">
        <div class="sheet-header">
        <h3 style="margin-top: 3px; margin-bottom: -1px;"> الحصيلة الفصلية </h3>

        <p style="font-size: 16px !important;">إدارة الدروس - العام الدراسي: <?php echo $last_year; ?> <span style="font-size: 22px !important; color: #109686 ; font-weight: bold;">|</span> <strong> الفرع: </strong><?php echo $branch_name; ?> <span style="font-size: 28px !important; color: #109686; font-weight: bold;">_</span> <strong> الصف: </strong><?php echo $class_name; ?> <span style="font-size: 22px !important; color: #109686; font-weight: bold;">|</span>
        <p style="font-size: 16px !important;">الفصل: <?php echo in_array($selectedMonth, $arabic_quarterly) ? $selectedMonth : ''; ?> </p>
        <p style="font-size: 16px !important;" class="print-date">التاريخ : <?php echo date('Y-m-d'); ?></p> <!-- Print date only visible during print -->

    </div>
    <div class="for-container">
        <?php if ($selectedMonth): ?>

            <table>
                <tr>
                <td rowspan="2" style="width: 17%;">اسم الطالب (ة)</td>
                <?php if (!empty($selectedMonth) && in_array($selectedMonth, $arabic_quarterly)): ?>
                    <?php if (isset($quarterlyMonths[$selectedMonth])): ?>
                        <?php foreach (array_reverse($quarterlyMonths[$selectedMonth]) as $month): ?>
                            <td colspan="2">حصيلة الشهر (<?php echo $month . " " . $last_year; ?>)</td>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>فصل غير موجود.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>يرجى اختيار فصل صحيح.</p>
                <?php endif; ?>

                </tr>
                <tr>
                    <td rowspan="1" >الحصيلة</td>
                    <td rowspan="1" >الغياب</td>
                    <td rowspan="1" >الحصيلة</td>
                    <td rowspan="1" >الغياب</td>
                    <td rowspan="1" >الحصيلة</td>
                    <td rowspan="1" >الغياب</td>

                </tr>
                
            <tr>
            <?php foreach ($students as $studentId => $student) {
                    $tot_ab =
                    ($studentAbsences[$student['id']][$months[0]] ?? 0) +
                    ($studentAbsences[$student['id']][$months[1]] ?? 0) +
                    ($studentAbsences[$student['id']][$months[2]] ?? 0);
                    $stmt = $conn->prepare("SELECT month_1_income, month_1_absence, month_2_income, month_2_absence,
                            month_3_income, month_3_absence, total_income, total_absence,
                            total_groups, extra, notes
                        FROM student_performance
                        WHERE student_id = ? AND quarter = ?");
                    $stmt->bind_param('is', $student['id'], $selectedMonth);
                    $stmt->execute();
                    $stmt->store_result();
                    $tot_abs = 0;
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
                    <!-- <td><input type="text" name="student_data[<?php //echo $student['id']; ?>][month_1_absence]" value="<?php //echo htmlspecialchars($studentAbsences[$student['id']][$months[2]]??0); ?>" class="no-border" /></td> -->
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_2_income]" value="<?php echo htmlspecialchars($month2Income); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_2_absence]" value="<?php echo htmlspecialchars($month2Absence); ?>" class="no-border" /></td>
                    <!-- <td><input type="text" name="student_data[<?php //echo $student['id']; ?>][month_2_absence]" value="<?php //echo htmlspecialchars($studentAbsences[$student['id']][$months[1]]??0); ?>" class="no-border" /></td> -->
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_3_income]" value="<?php echo htmlspecialchars($month3Income); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][month_3_absence]" value="<?php echo htmlspecialchars($studentAbsences[$student['id']][$months[0]]??0); ?>" class="no-border" /></td>

                </tr>
                <?php } ?> 
            </table>

            <table style="margin-top: 2px;">
                <tr>


                    <td rowspan="1">مجموع الحصيلة</td>
                    <td rowspan="1">مجموع الغياب</td>
                    <td rowspan="1">عدد الأحزاب إجمال</td>
                    <td rowspan="1">زيادة المتون و التربية</td>

                    <td rowspan="1">الملاحظات</td>

                </tr>
                
                
            <tr>
            <?php foreach ($students as $studentId => $student) {
                    $tot_ab =
                    ($studentAbsences[$student['id']][$months[0]] ?? 0) +
                    ($studentAbsences[$student['id']][$months[1]] ?? 0) +
                    ($studentAbsences[$student['id']][$months[2]] ?? 0);
                    $stmt = $conn->prepare("SELECT month_1_income, month_1_absence, month_2_income, month_2_absence,
                            month_3_income, month_3_absence, total_income, total_absence,
                            total_groups, extra, notes
                        FROM student_performance
                        WHERE student_id = ? AND quarter = ?");
                    $stmt->bind_param('is', $student['id'], $selectedMonth);
                    $stmt->execute();
                    $stmt->store_result();
                    $tot_abs = 0;

                    if ($stmt->num_rows > 0) {
                        $stmt->bind_result($month1Income, $month1Absence, $month2Income, $month2Absence,
                                        $month3Income, $month3Absence, $totalIncome, $totalAbsence,
                                        $totalGroups, $extra, $notes);
                        $stmt->fetch();
                        $tot_abs = (int)$month1Absence + (int)$month2Absence + (int)$month3Absence;

                    } else {
                        // Set variables to empty if no data exists
                        $month1Income = $month1Absence = $month2Income = $month2Absence =
                        $month3Income = $month3Absence = $totalIncome = $totalAbsence =
                        $totalGroups = $extra = $notes = '';
                    }
                    $stmt->close();
                ?>
                <tr>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][total_income]" value="<?php echo htmlspecialchars($totalIncome); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][total_absence]" value="<?php echo htmlspecialchars($tot_abs); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][total_groups]" value="<?php echo htmlspecialchars($totalGroups); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][extra]" value="<?php echo htmlspecialchars($extra); ?>" class="no-border" /></td>
                    <td><input type="text" name="student_data[<?php echo $student['id']; ?>][notes]" value="<?php echo htmlspecialchars($notes); ?>" class="no-border" /></td>
                </tr>
                <?php } ?> 
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
</div>
    <script src="js/popper.min.js"></script>



<!-- Include Bootstrap JS and dependencies -->
<script src="js/jquery-3.5.1.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        $('#name_student').on('input', function() {
            var agentPhone = $(this).val();

            if (agentPhone.length > 0) {
                $.ajax({
                    url: 'check_student.php',
                    type: 'POST',
                    data: { phone: agentPhone },
                    dataType: 'json',
                    success: function(response) {
                        var $dropdown = $('#agentDropdown');
                        $dropdown.empty();

                        if (response.matches && response.matches.length > 0) {
                            response.matches.forEach(function(agent) {
                                var agentItem = $('<a>', {
                                    class: 'dropdown-item agent-item',
                                    href: '#',
                                    text: agent.name,
                                    'data-agent-id': agent.id
                                });
                                $dropdown.append(agentItem);
                            });
                            $dropdown.show();
                        } else {
                            $dropdown.hide();
                        }
                    },
                    error: function() {
                        console.error('Error occurred during the AJAX call.');
                    }
                });
            } else {
                $('#agentDropdown').hide();
            }
        });

        $(document).on('click', '.agent-item', function(e) {
            e.preventDefault();
            var selectedAgentName = $(this).text();
            var selectedAgentId = $(this).data('agent-id');

            $('#name_student').val(selectedAgentName);
            $('#student_id').val(selectedAgentId);      // Set the student id

            $('#agentDropdown').hide();
        });

        $(document).click(function(event) {
            if (!$(event.target).closest('#name_student, #agentDropdown').length) {
                $('#agentDropdown').hide();
            }
        });
    });
</script>

</body>
</html>
