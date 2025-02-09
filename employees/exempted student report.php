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


$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

$sql = "SELECT class_name FROM classes";
$result = $conn->query($sql);

$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = $row['class_name'];
}

// Define filter parameters
$selectedYear = isset($_GET['year']) ? $_GET['year'] : '2024-2023';
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'class';
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';

// SQL query depending on the filter type
if ($filterType === 'all') {
    // Fetch all students where payment_nature is 'معفى'
    $sql_new = "SELECT s.student_name, s.regstration_date_count, c.class_name 
                FROM students s 
                JOIN classes c ON s.class_id = c.class_id
                WHERE s.payment_nature = 'معفى'";
} else {
    // Fetch students in the selected class where payment_nature is 'معفى'
    $sql_new = "SELECT s.student_name, s.regstration_date_count 
                FROM students s
                JOIN classes c ON s.class_id = c.class_id
                WHERE s.payment_nature = 'معفى' AND c.class_name = ?";
}

// Prepare the SQL statement
$stmt_new = $conn->prepare($sql_new);

// Bind the parameter if filter type is 'class'
if ($filterType === 'class') {
    $stmt_new->bind_param('s', $selectedClass);
}

// Execute the query
$stmt_new->execute();
$result_new = $stmt_new->get_result();

$students = [];
while ($row = $result_new->fetch_assoc()) {
    $students[] = $row;
}

// Close the statement and the connection
$stmt_new->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الديون</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">

<style>
        .main-contain {
            margin: 10px auto;
            padding: 30px;
            border-radius: 12px;
            background-color: white;
            border: 1px solid #ddd;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            text-align: center; /* Center align the content */
        }
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f6;
            color: #333;
        }

        .header-tit {
            font-size: 28px;
            font-weight: bold;
            color: #1BA078;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }



        .form-inline-container label {
            font-size: 16px;
            color: #666;
            font-weight: bold;
            margin-bottom: 0;
        }

        .form-sele {
            flex: 1; /* Allow each form group to take equal space */
            max-width: 290px; /* Uniform width for each form group */
        }

        .form-sele, .form-checkb {
            border-radius: 10px;
            padding: 10px;
            border: 2px solid #1BA078;
            font-size: 16px;

        }

        .form-checkb {
            transform: scale(1.3);
            accent-color: #1BA078;
            margin-left: 5px;
        }

        .btn-conf {
            background-color: #1BA078;
            color: white;
            border: 2px solid #1BA078;
            border-radius: 10px;
            padding: 8px 15px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            display: inline-flex;
            align-items: center;
        }

        .btn-conf i {
            font-size: 18px;
            margin-left: 5px;
            
        }

        .btn-conf:hover {
            background-color: #14865b;
        }
        .header-tit {
            font-size: 28px;
            font-weight: bold;
            margin: 12px;
            color: #1BA078;
            display: flex;
            align-items: center;
            justify-content: center; /* Center the title */
            gap: 10px;
            margin-bottom: 20px;
        }
        .main-container {
            margin: 40px auto;
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            transition: all 0.3s ease-in-out;
        }

        .header-title {
            font-size: 28px;
            font-weight: bold;
            color: #1BA078;
            text-align: center;
            margin-bottom: 30px;
        }

        .sub-header {
            font-size: 20px;
            margin-bottom: 20px;
            text-align: right;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 15px;
            font-size: 18px;
            text-align: center;
            vertical-align: middle;
            border: 2px solid #1BA078;
        }

        table th {
            background-color: #1BA078;
            color: white;
        }

        table td {
            background-color: #f9f9f9;
        }

        .footer-row {
            background-color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .footer-row td {
            border: none;
            padding-top: 30px;
        }

        .footer-total {
            border-top: 2px solid #1BA078;
            padding-top: 15px;
            font-size: 20px;
        }

        .header-image {
            width: 100%;
            max-width: 500px;
            margin-bottom: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .print-button {
            margin-top: 20px;
            padding: 3px 20px;
            font-size: 23px;
            cursor: pointer;
            background-color: #1BA078; /* Green */
            color: white; /* White text */
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .print-button:hover {
            background-color: #14865b; /* Darker Green */
        }

        /* Print Styles */
        @media print {
            
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }
            h2 {
                color: black;
            }
            table {
                box-shadow: none;
            }
            th, td {
                border: 1px solid black;
                color: black; /* Black text */
            }
            th {
                background-color: white; /* White background for header */
                color: black; /* Black text for header */
            }
            tfoot {
                background-color: white; /* White background for footer */
                color: black; /* Black text for footer */
            }
            .print-button, .cnc {
                display: none; /* Hide the button when printing */
            }
            img {
            display: block;
            margin: 0 auto; /* Center the image */
            width: 100%; /* Full width in print */
        }
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
        function printTable() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="container main-contain cnc">
        <h2 class="header-tit text-center"><i class="bi bi-file-earmark-text-fill"></i> الطلاب المعفيين</h2>

        <form action="" method="get" class="row g-3">
            <!-- Year Selection -->
            <div class="col-12 col-md-6 col-lg-3">
                <label for="year-select" class="form-label">السنة المالية:</label>
                <select id="year-select" name="year" class="form-select">
                    <option><?php echo htmlspecialchars($last_year); ?></option>
                </select>
            </div>

            <!-- Section Select -->
            <div class="col-12 col-md-6 col-lg-3">
                <label for="section" class="form-label">حسب القسم:</label>
                <select id="section" name="class" class="form-select">
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Selection -->
            <div class="col-12 col-md-6 col-lg-3">
                <label for="filter-select" class="form-label">تصفية حسب:</label>
                <select id="filter-select" name="filter" class="form-select">
                    <option value="class">حسب القسم</option>
                    <option value="all">حسب الجميع</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-12 col-md-6 col-lg-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-check"></i> تأكيد العملية
                </button>
                <button type="button" class="btn btn-primary w-100" onclick="printTable()">
                    <i class="bi bi-printer"></i> طباعة
                </button>
            </div>
        </form>
    </div>


    
    <div class="container main-contain">
        <div style="text-align: center;">
            <img src="../images/header.png" width="100%" alt="Header Image">
        </div>

        <h2 class="header-tit">تقرير بحسابات الطلاب المعفيين</h2>
        <?php if ($filterType === 'class'): ?>
            <div class="header-tit">
                <span>الفصل: <?= htmlspecialchars($selectedClass) ?></span>
            </div>
        <?php endif; ?>
        <div class="table-responsive tbl">
        <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>تاريخ التسجيل</th>
                        <?php if ($filterType === 'all'): ?>
                            <th>القسم</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_name']) ?></td>
                                <td><?= htmlspecialchars($student['regstration_date_count']) ?></td>
                                <?php if ($filterType === 'all'): ?>
                                    <td><?= htmlspecialchars($student['class_name']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= ($filterType === 'all') ? 3 : 2 ?>">لا توجد بيانات للطلاب المعفيين.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="footer-row">
                        <td colspan="<?= ($filterType === 'all') ? 3 : 2 ?>" class="footer-total">الجميع: <?= count($students) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>