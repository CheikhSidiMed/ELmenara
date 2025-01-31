<?php
// Include the database connection file
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}



$selectedMonth = $_GET['month'] ?? null;
$year = $_GET['year'] ?? null;

$totalStudents = 0;
$totalFees = 0;
$totalPaid = 0;
$totalRemaining = 0;

$sql = "
    SELECT 
        c.class_name, 
        COUNT(DISTINCT s.id) AS student_count, 
        SUM(s.remaining) AS total_fees, 
        SUM(COALESCE(p.paid_amount, 0)) AS total_paid,
        (SUM(s.remaining) - SUM(COALESCE(p.paid_amount, 0))) AS remaining
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN payments p ON s.id = p.student_id AND p.month = ?
    GROUP BY c.class_name";

$stmt = $conn->prepare($sql);


if (!$stmt) {
    die("Error preparing SQL query: " . $conn->error);
}

$stmt->bind_param('s', $selectedMonth);
$stmt->execute();


$result = $stmt->get_result();

// Initialize an empty array to store the rows
$rows = [];

while ($row = $result->fetch_assoc()) {
    $remaining = $row['total_fees'] - $row['total_paid'];

    // Add the row data to the array for rendering
    $rows[] = [
        'class_name' => $row['class_name'],
        'student_count' => $row['student_count'],
        'total_fees' => $row['total_fees'],
        'total_paid' => $row['total_paid'],
        'remaining' => $remaining
    ];

    // Sum up the totals for the footer
    $totalStudents += $row['student_count'];
    $totalFees += $row['total_fees'];
    $totalPaid += $row['total_paid'];
    $totalRemaining += $remaining;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير المالي الشهري للمحضرة</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f6;
        }

        .main-container {
            margin: 20px auto;
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .table-container {
            margin-top: 30px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            background-color: #1BA078;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 18px;
            border: 2px solid #1BA078;
        }

        .table tbody td {
            text-align: center;
            padding: 8px;
            font-size: 16px;
            border: 2px solid #1BA078;
            direction: ltr;
        }

        .table tfoot td {
            font-weight: bold;
            background-color: #f4f7f6;
            text-align: center;
            border-top: 2px solid #1BA078;
            direction: ltr;
        }

        .table tfoot .total-row {
            background-color: #f4f7f6;
            font-weight: bold;
            border-top: 2px solid #1BA078;
        }

        .header-image {
            width: 100%;
            margin-bottom: 20px;
        }
    </style>
    <style>
        @media print {
            .btn {
                display: none;
                }
        }
    </style>

</head>

<body>

    <div class="container main-container">
        <!-- Header Image -->
        <img src="../images/header.png" alt="Header Image" class="header-image">
        
        <!-- Header Title -->
        <h2 class="header-title">التقرير المالي الشهري للمحضرة</h2>

        <!-- Month Section -->
        <h3 class="text-center"><span id="selected-month"><?php echo $selectedMonth ?></span>, السنة المالية: <?php echo $year ?><span id="selected-month"></span></h3>

        <!-- Table Section -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>القسم</th>
                        <th>عدد الطلاب</th>
                        <th>مجموع الرسوم</th>
                        <th>مجموع المدفوع</th>
                        <th>المتبقي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['class_name']) ?></td>
                            <td><?= htmlspecialchars($row['student_count']) ?></td>
                            <td><?= htmlspecialchars(number_format($row['total_fees'])) ?></td>
                            <td><?= htmlspecialchars(number_format($row['total_paid'])) ?></td>
                            <td><?= htmlspecialchars(number_format($row['remaining'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td>المجموع :</td>
                        <td><?= $totalStudents ?></td>
                        <td><?= number_format($totalFees) ?></td>
                        <td><?= number_format($totalPaid) ?></td>
                        <td><?= number_format($totalRemaining) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="text-center mb-3 mt-5">
        <button class="btn btn-success" onclick="window.print()">طباعة التقرير</button>
    </div>
    
    </div>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>
