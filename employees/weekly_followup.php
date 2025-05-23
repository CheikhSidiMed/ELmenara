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
// Include database connection
include 'db_connection.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedBranch = $_POST['branch'] ?? null;
    $selectedClass = $_POST['class'] ?? null;
} else {
    $selectedBranch = null;
    $selectedClass = null;
}

$sql = "SELECT b.branch_id, b.branch_name
    FROM branches b
    JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$branchesResult = $stmt->get_result();

// Fetch classes based on selected branch
$classesResult = [];
if ($selectedBranch) {
    
    $classesQuery = '';
    if($role_id == 6){
        $classesQuery = "SELECT c.class_id, c.class_name
            FROM classes c
            JOIN branches AS b ON c.branch_id = b.branch_id
            JOIN user_branch AS ub ON ub.class_id = c.class_id AND ub.user_id = '$user_id'
            WHERE c.branch_id = ?";
    }else{
        $classesQuery = "SELECT class_id, class_name
            FROM classes WHERE branch_id = ?";
    }
    $stmt = $conn->prepare($classesQuery);
    $stmt->bind_param("i", $selectedBranch);
    $stmt->execute();
    $classesResult = $stmt->get_result();
    $stmt->close();
}

$branches = [];
$students = [];

$start_date = '';
$end_date = '';
$branch_name = '';
$class_name = '';
$username = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id = $_POST['branch'];
    $class_id = $_POST['class'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Fetch class name and branch name

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

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
} else {
    // Fetch branches if no form is submitted
    $sql = "SELECT b.branch_id, b.branch_name
        FROM branches b
        JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}


$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}


?>




<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استمارة المتابعة الأسبوعية</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
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
            padding: 1px;
            white-space: nowrap !important;
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
            margin-left: 40px;
            margin-right: 40px;
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
            .container-fluid {
                display: none;
            }
            body {
                margin: 40px;
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
            .tbl{
                padding-left: 20px;
            }
            .p-head{
                font-size: 15px !important;
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


    <div class="container-fluid">
        <div class="form-container">
            <h2 class="text-center">إنشاء استمارة المتابعة الأسبوعية</h2>
            <form method="POST" action="">
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="branch">الفرع</label>
                        <select class="form-control" id="branch" name="branch" required onchange="this.form.submit()">
                            <option value="">اختر الفرع</option>
                            <?php
                            if ($branchesResult->num_rows > 0) {
                                while($row = $branchesResult->fetch_assoc()) {
                                    echo "<option value='{$row['branch_id']}'" . ($selectedBranch == $row['branch_id'] ? " selected" : "") . ">{$row['branch_name']}</option>";
                                }
                            } else {
                                echo "<option value=''>لا يوجد فروع</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="class">الصف</label>
                        <select class="form-control" id="class" name="class" required>
                            <option value="">اختر الصف</option>
                            <?php
                            if ($selectedBranch && $classesResult->num_rows > 0) {
                                while($classRow = $classesResult->fetch_assoc()) {
                                    echo "<option value='{$classRow['class_id']}'" . ($selectedClass == $classRow['class_id'] ? " selected" : "") . ">{$classRow['class_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="fform-group col-md-2">
                        <label for="start_date">من تاريخ</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo isset($start_date) ? $start_date : ''; ?>" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="end_date">إلى تاريخ</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo isset($end_date) ? $end_date : ''; ?>" required>
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block w-100">إنشاء الاستمارة</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

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
        <h3>استمارة المتابعة الأسبوعية</h3>
        <p class="p-head">من تاريخ: <?php echo $start_date; ?> إلى تاريخ: <?php echo $end_date; ?></p>
        <p class="p-head">الفرع: <?php echo $branch_name; ?></p>
        <p class="p-head">القسم: <?php echo $class_name; ?></p>
        <p>العام الدراسي : <?php echo htmlspecialchars($last_year); ?></p>
    </div>
    
    <div class="table-responsive tbl">
        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;"> النداء</th>
                    <th colspan="4" rowspan="2">الاسم الكامل</th>
                    <th colspan="5">البرنامج اليومي من الكتابة</th>
                    <th colspan="5">البرنامج اليومي من المحفوظات</th>
                    <th rowspan="2">الزرك</th>
                    <th rowspan="2">الأحزاب</th>
                    <th rowspan="2">الغياب</th>
                    <th colspan="4" rowspan="2">الملاحظات</th>
                </tr>
                <tr>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td colspan="1"><?php echo $student['id']; ?></td>
                    
                    <td colspan="4"><?php echo $student['student_name']; ?></td>
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
                    <td colspan="4" contenteditable="true"></td>
                </tr>
                <?php endforeach; ?>
            <?php for ($i = 0; $i < 3; $i++): ?>
                <tr>
                    <td ></td>
                    <td colspan="4"></td>
                <?php for ($j = 0; $j < 13; $j++): ?>
                    <td contenteditable="true"></td>
                <?php endfor; ?>
                <td colspan="4" contenteditable="true"></td>

                </tr>
            <?php endfor; ?>

                <tr>
                <td colspan="5" class="total-cell">المجموع :</td>
                    <td colspan="1" contenteditable="true"></td>
                    <td colspan="1" contenteditable="true"></td>
                    <td colspan="1" contenteditable="true"></td>
                    <td colspan="1" contenteditable="true"></td>
                    <td colspan="1" contenteditable="true"></td>
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
            </tbody>
        </table>
    </div>


    <div class="signature-section">
        <div class="signature">
            <p style="font-size: 18px;">الأستاذ(ة) </p>
            <P style="margin-top: -20px; font-weight: bold;"><?php echo $username; ?></P>
            <p style="margin-top: -35px;">_________</p>
        </div>
        <div class="signature">
            <p style="font-size: 18px">تاريخ التسليم</p>
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
