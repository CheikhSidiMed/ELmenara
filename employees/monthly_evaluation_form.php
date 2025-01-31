<?php
// Start session

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Include database connection
include 'db_connection.php';

$students_per_page = 3; // Number of students to display per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get the current page or set to 1
$offset = ($page - 1) * $students_per_page;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id = $_POST['branch'];
    $class_id = $_POST['class'];
    $month = $_POST['month'];
    $year = $_POST['year'];

    // Store these in session for subsequent page loads
    $_SESSION['branch_id'] = $branch_id;
    $_SESSION['class_id'] = $class_id;
    $_SESSION['month'] = $month;
    $_SESSION['year'] = $year;

    // Fetch branch and class names
    $sqlBranch = "SELECT branch_name FROM branches WHERE branch_id = ?";
    $stmtBranch = $conn->prepare($sqlBranch);
    $stmtBranch->bind_param('i', $branch_id);
    $stmtBranch->execute();
    $stmtBranch->bind_result($branch_name);
    $stmtBranch->fetch();
    $stmtBranch->close();

    $sqlClass = "SELECT class_name FROM classes WHERE class_id = ?";
    $stmtClass = $conn->prepare($sqlClass);
    $stmtClass->bind_param('i', $class_id);
    $stmtClass->execute();
    $stmtClass->bind_result($class_name);
    $stmtClass->fetch();
    $stmtClass->close();

    // Store branch and class names in session
    $_SESSION['branch_name'] = $branch_name;
    $_SESSION['class_name'] = $class_name;
}

// Retrieve values from session
$branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;
$class_id = isset($_SESSION['class_id']) ? $_SESSION['class_id'] : null;
$month = isset($_SESSION['month']) ? $_SESSION['month'] : null;
$year = isset($_SESSION['year']) ? $_SESSION['year'] : null;
$branch_name = isset($_SESSION['branch_name']) ? $_SESSION['branch_name'] : null;
$class_name = isset($_SESSION['class_name']) ? $_SESSION['class_name'] : null;

if ($class_id !== null) {
    // Fetch total number of students
    $sql = "SELECT COUNT(*) FROM students WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $stmt->bind_result($total_students);
    $stmt->fetch();
    $stmt->close();

    // Calculate total pages
    $total_pages = ceil($total_students / $students_per_page);

    // Fetch students for the current page
    $sql = "SELECT student_id, student_name FROM students WHERE class_id = ? LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $class_id, $offset, $students_per_page);
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استمارة التقويم الشهري</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
            width: 100%;
            max-width: 600px;
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
            padding: 25px; /* Increased padding for more writing space */
            text-align: center;
            font-size: 18px;
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

        /* Pagination Controls */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            padding: 5px 10px;
            text-decoration: none;
            border: 1px solid #007bff;
            color: #007bff;
            border-radius: 5px;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
        }
        .pagination a:hover {
            background-color: #0056b3;
            color: white;
        }
        .total-cell {
            text-align: center;
            font-weight: bold; /* Optional: to make it bold */
            padding: 50px;
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
                    .button-group, .pagination {
                display: none;
            }
                    body {
                        margin: 0; /* Ensure no extra margins on print */
                        font-size: 12px; /* Maintain font size */
                    }
                    

                    th, td {
                        padding: 30px; /* Increased padding for more writing space */
                        text-align: right;
                    font-size: 16px;
                    white-space: nowrap;
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


<button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='Monthly_rating.php'">
الصفحة السابقة <i class="bi bi-arrow-left" style="margin-right: 8px;"></i> 
</button>

</div>
    <div class="sheet-header">
        <img src="../images/header.png" alt="Header Image">
        <h3>  استمارة التقييم</h3>
        <p>الفرع: <?php echo $branch_name; ?>  <p>القسم: <?php echo $class_name; ?></p> <p>الشهر: <?php echo $month; ?> </p> <p> السنة: <?php echo $year; ?></p>
        
    </div>

    <table>
        <thead>
            <tr>
                <th colspan="4">الاسم الكامل</th>
                <th colspan="2">اللوح</th>
                <th colspan="2">حزب اللوح</th>
                <th colspan="10">العدد المقوم من الأحزاب و الملاحظات العامة و المحددة</th>
                <th colspan="3">مستوى الأداء</th>
                <th colspan="3">التقدير النهائي</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td colspan="4"><?php echo $student['student_name']; ?></td>
                <td colspan="2" contenteditable="true"></td>
                <td colspan="2" contenteditable="true"></td>
                <td   class="total-cell" colspan="10" contenteditable="true"></td>
                <td colspan="3" contenteditable="true"></td>
                <td colspan="3" contenteditable="true"></td>
            </tr>
            <?php endforeach; ?>
            <!-- Extra Fields if needed -->
            <?php for ($i = count($students); $i < $students_per_page; $i++): ?>
            <tr>
                <td colspan="4"></td>
                <td colspan="2" contenteditable="true"></td>
                <td colspan="2" contenteditable="true"></td>
                <td   class="total-cell" colspan="10" contenteditable="true"></td>
                <td colspan="3" contenteditable="true"></td>
                <td colspan="3" contenteditable="true"></td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <!-- Pagination Controls -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>">السابق</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>">التالي</a>
        <?php endif; ?>
    </div>
</body>
</html>

