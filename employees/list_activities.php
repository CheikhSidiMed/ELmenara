<?php
// Include database connection
include 'db_connection.php';

// Fetch all activities, including the status
$sql = "SELECT activity_name, start_date, end_date, price, status FROM activities";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لائحة دورات و الأنشطة</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }
        .container-main {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            max-width: 1100px;
            margin: auto;
        }
        .header-title {
            font-family: 'Amiri', serif;
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #1a73e8;
            padding-bottom: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .table {
            border-collapse: separate;
            border-spacing: 0 15px;
        }
        .table thead th {
            background-color: #1a73e8;
            color: #ffffff;
            border: none;
            padding: 10px 15px;
            border-radius: 10px 10px 0 0;
            font-size: 1.2rem;
        }
        .table tbody tr {
            background-color: #f9f9f9;
            border: 2px solid #1a73e8;
            border-radius: 10px;
        }
        .table tbody td {
            border: none;
            padding: 15px;
            font-size: 1rem;
            color: #333;
        }
        .table tbody tr td:first-child {
            border-radius: 10px 0 0 10px;
        }
        .table tbody tr td:last-child {
            border-radius: 0 10px 10px 0;
        }
        .no-activities {
            text-align: center;
            padding: 50px;
            font-size: 1.5rem;
            color: #888;
        }
        .btn-update-status {
            background-color: #1a73e8;
            color: #fff;
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: block;
            margin: 20px auto 0;
        }
        .btn-update-status:hover {
            background-color: #125cb2;
        }
        .status-message {
            text-align: center;
            margin-top: 20px;
            font-size: 1.2rem;
            color: #1a73e8;
        }
    </style>
</head>
<body>

<div class="container-main">
    <h1 class="header-title">لائحة دورات و الأنشطة</h1>
    
    <?php if ($result->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>اسم الدورة أو النشاط</th>
                    <th>تاريخ البدء</th>
                    <th>تاريخ الانتهاء</th>
                    <th>السعر</th>
                    <th>الحالة</th> <!-- New column for activity status -->
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['activity_name']; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['start_date'])); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['end_date'])); ?></td>
                        <td><?php echo str_replace(',', '', number_format($row['price'], 0)); ?> MRU </td>
                        <td><?php echo ($row['status'] === 'Ended') ? 'منتهية' : 'قيد التنفيذ'; ?></td> <!-- Status Display -->
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-activities">لا توجد دورات أو أنشطة مسجلة حاليا.</div>
    <?php endif; ?>

    <button class="btn-update-status" id="updateStatusButton">تحديث حالة الأنشطة</button>
    <div class="status-message" id="statusMessage"></div>

    <?php $conn->close(); ?>
</div>

<script src="js/jquery-3.5.1.min.js"></script>
<script>
    $('#updateStatusButton').click(function() {
        $.ajax({
            url: 'update_activity_status.php', // The PHP script to update the status
            method: 'POST',
            success: function(response) {
                $('#statusMessage').text(response.message);
                location.reload(); // Reload the page to reflect the updated statuses
            },
            error: function() {
                $('#statusMessage').text('حدث خطأ أثناء تحديث الحالة.');
            }
        });
    });
</script>

</body>
</html>
