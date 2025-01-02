<?php
// Include the database connection file
include 'db_connection.php';

// Retrieve the selected year and filter type from the URL parameters
$selectedYear = isset($_GET['year']) ? $_GET['year'] : '2024-2023';
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'class';
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';

// SQL query depending on the filter type
if ($filterType === 'all') {
    // Fetch all students where payment_nature is 'معفى'
    $sql = "SELECT s.student_name, s.registration_date, c.class_name 
            FROM students s 
            JOIN classes c ON s.class_id = c.class_id
            WHERE s.payment_nature = 'معفى'";
} else {
    // Fetch students in the selected class where payment_nature is 'معفى'
    $sql = "SELECT s.student_name, s.registration_date 
            FROM students s
            JOIN classes c ON s.class_id = c.class_id
            WHERE s.payment_nature = 'معفى' AND c.class_name = ?";
}

// Prepare the SQL statement
$stmt = $conn->prepare($sql);

// Bind the parameter if filter type is 'class'
if ($filterType === 'class') {
    $stmt->bind_param('s', $selectedClass);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Close the connection
$stmt->close();
$conn->close();
?>



<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير بحسابات الطلاب المعفيين</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f6;
            color: #333;
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
    </style>
</head>

<body>

    <div class="container main-container">
        <!-- Header Image -->
        <img src="../images/header.png" alt="Header Image" class="header-image">

        <!-- Title -->
        <h2 class="header-title">تقرير بحسابات الطلاب المعفيين</h2>

        <!-- Sub-Title -->
        <?php if ($filterType === 'class'): ?>
            <div class="sub-header">
                <span>الفصل: <?= htmlspecialchars($selectedClass) ?></span>
            </div>
        <?php endif; ?>

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
                            <td><?= htmlspecialchars($student['registration_date']) ?></td>
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

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>