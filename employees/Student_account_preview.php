<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
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
    <title>معاينة الحساب</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f9f9f9;
        }

        .main-container {
            margin: 20px;
            border: 1px solid #1BA078;
            padding: 20px;
            border-radius: 10px;
            background-color: white;
        }

        .form-control,
        .form-select {
            border: 1px solid #1BA078;
            border-radius: 5px;
            padding: 8px 12px;
            color: #333;
        }

        .btn-primary {
            background-color: #1BA078;
            border-color: #1BA078;
            color: white;
        }

        .table thead th {
            background-color: #1BA078;
            color: white;
            text-align: center;
        }

        .table tbody td {
            text-align: center;
            direction: ltr;
        }

        .btn-download,
        .btn-withdraw {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            border: 1px solid #1BA078;
            color: #1BA078;
            border-radius: 50px;
            padding: 5px 15px;
            font-weight: bold;
            font-size: 14px;
        }

        .btn-download span,
        .btn-withdraw span {
            margin-right: 5px;
            font-size: 18px;
        }

        .btn-download:hover,
        .btn-withdraw:hover {
            background-color: #1BA078;
            color: white;
        }

        .form-label {
            margin-bottom: 0;
        }

        .update-button {
            margin-top: 10px;
        }

        .total-summary {
            background-color: #f1f1f1;
            font-weight: bold;
            padding: 15px;
            border: 1px solid #1BA078;
            border-radius: 10px;
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .total-summary div {
            text-align: center;
            padding: 0 10px;
            direction: ltr;
        }

        .summary-label {
            flex: 4;
            direction: rtl;
        }

        .summary-value {
            flex: 1;
        }
    </style>
</head>
<body>

    <div class="container main-container">
        <div class="header-row">
            <div>
                <h2 class="d-inline-block">معاينة الحساب</h2>
            </div>
            <div>
                <h5>السنة المالية</h5>
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo $last_year; ?>
                </button>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-3">
                <label for="accountName" class="form-label">اسم الحساب:</label>
                <input type="text" class="form-control" id="accountName"  readonly>
            </div>
            <div class="col-md-3">
                <label for="fromDate" class="form-label">من:</label>
                <input type="date" class="form-control" id="fromDate">
            </div>
            <div class="col-md-3">
                <label for="toDate" class="form-label">إلى:</label>
                <input type="date" class="form-control" id="toDate">
            </div>
            <div class="col-md-3 update-button">
            <label for="toDate" class="form-label">-</label>
                <button class="btn btn-primary w-100">تحديث</button>
            </div>
        </div>

        <table class="table mt-4">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>بيان العملية</th>
                    <th>مدين</th>
                    <th>دائن</th>
                    <th>الرصيد</th>
                </tr>
            </thead>
            <tbody>
               
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                
            </tbody>
        </table>

        <!-- Total Summary -->
        <div class="total-summary">
            <div class="summary-label"> مجموع العمليات </div>
            <div class="summary-value"></div>
            <div class="summary-value"></div>
            <div class="summary-value"></div>
        </div>

        <div class="row mt-4">
            <div class="col text-end">
                <button class="btn btn-download me-2">
                    <span>✔</span> تحضير PDF
                </button>
                <button class="btn btn-withdraw">
                    <span>✔</span> سحب الكشوف
                </button>
            </div>
        </div>

    </div>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
