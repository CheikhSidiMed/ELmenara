<?php
require_once 'db_connection.php'; // Include your database connection

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Fetch all suspended employees
$query = "SELECT * FROM suspended_employees";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الموظفون المفصولون</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            background-color: #f4f7f6;
        }

        .container {
            margin: 40px auto;
            padding: 20px;
            max-width: 1000px;
            background-color: white;
            border: 2px solid #1BA078;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .header-title {
            font-size: 30px;
            font-weight: bold;
            color: #1BA078;
            margin-bottom: 20px;
        }

        .table {
            background-color: white;
            text-align: center;
            margin-top: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }

        .btn-action {
            font-size: 16px;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn-delete {
            background-color: #e74c3c;
        }

        .btn-return {
            background-color: #1BA078;
        }

        .btn-delete:hover,
        .btn-return:hover {
            opacity: 0.8;
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
</head>

<body>
    <div class="container">
        <h2 class="header-title"><i class="bi bi-people-fill"></i> الموظفون المفصولون</h2>
        <div class="table-responsive tbl">
            <table class="table table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>رقم الموظف</th>
                        <th>اسم الموظف</th>
                        <th>رقم الهاتف</th>
                        <th>سبب الفصل</th>
                        <th>الرصيد المستحق</th>
                        <th>تاريخ الفصل</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$row['id_number']}</td>";
                            echo "<td>{$row['full_name']}</td>";
                            echo "<td>{$row['phone']}</td>";
                            echo "<td>{$row['suspension_reason']}</td>";
                            echo "<td>{$row['balance']}</td>";
                            echo "<td>{$row['subscription_date']}</td>";
                            echo "<td>
                                    <form method='post' action='manage_suspended.php' style='display: inline-block;'>
                                        <input type='hidden' name='id' value='{$row['suspension_id']}'>
                                        <button type='submit' name='return_employee' class='btn-action btn-return'>
                                            <i class='bi bi-arrow-return-left'></i> إرجاع
                                        </button>
                                    </form>
                                    <form method='post' action='manage_suspended.php' style='display: inline-block;'>
                                        <input type='hidden' name='id' value='{$row['suspension_id']}'>
                                        <button type='submit' name='delete_employee' class='btn-action btn-delete'>
                                            <i class='bi bi-trash'></i> حذف
                                        </button>
                                    </form>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>لا يوجد موظفون مفصولون حاليًا.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="actions" style="text-align: center; margin-top: 20px;">
    <a href="home.php" class="btn btn-success">الصفحة الرئيسية</a>
    <a href="fire_employee.php" class="btn btn-warning">فصل موظف</a>
</div>

    </div>
</body>

</html>
