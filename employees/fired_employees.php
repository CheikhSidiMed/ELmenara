<?php
// Include database connection
include 'db_connection.php';

// Fetch all fired employees data
$query = "SELECT * FROM fire_employee ORDER BY fire_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الموظفون المفصولون</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            background-color: #f4f7f6;
        }

        .container {
            margin-top: 50px;
        }

        .header-title {
            font-size: 28px;
            font-weight: bold;
            color: #1BA078;
            margin-bottom: 30px;
            text-align: center;
        }

        table {
            width: 100%;
            margin-bottom: 20px;
        }

        table th, table td {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
            direction : ltr;
        }

        table th {
            background-color: #1BA078;
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="header-title">قائمة الموظفين المفصولين</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>اسم الموظف</th>
                <th>تاريخ التسجيل</th>
                <th>سبب الفصل</th>
                <th>المستحقات المالية</th>
                <th>تاريخ الفصل</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['employee_name']; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['subscription_date'])); ?></td>
                        <td><?php echo $row['reason']; ?></td>
                        <td>  <?php echo $row['financial_receivables']; ?> MRU </td>
                        <td><?php echo date('d-m-Y', strtotime($row['fire_date'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">لا توجد بيانات حالياً.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="js/bootstrap.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
