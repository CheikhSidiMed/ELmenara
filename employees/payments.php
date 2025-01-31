<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}



// Fetch payment history when a specific student is searched
if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $sql = "SELECT p.student_id, s.student_name, p.month, p.due_amount, p.paid_amount, p.remaining_amount, p.payment_method, b.bank_name
            FROM payments p
            JOIN students s ON p.student_id = s.id
            LEFT JOIN bank_accounts b ON p.bank_id = b.account_id
            WHERE s.id = ?
            ORDER BY p.month DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = null;
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تاريخ الدفع</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link rel="stylesheet" href="css/jquery-base-ui.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 20px;
            background-color: #f4f7f9;
        }
        .container {
            max-width: 1200px;
            padding: 15px;
            margin: auto;
            background-color: #fff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .sheet-header img {
            width: 100%;
            max-width: 1400px; /* Adjusted width */
            height: auto;
        }
        h3 {
            display: none;
        }
        .print-date {
            display: none;
        }

        @media print {
            .button-group,  form {
                display: none;
            }
            h3 {
            display: block !important;
            text-align: center !important;
            margin-top: 10px;
        }
            a, i, h2, .fas, .contt  {
                display: none !important;
            }
            .print-date {
        display: block;
        text-align: right;
        font-size: 14px;
        margin-bottom: 10px;
     }
            .container-fluid {
                display: none;
            }
            body {
                margin: 40px;
                font-size: 20px;
            }
            .sheet-header 
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
        }
    </style>
        <script>
            function printPage() {
                window.print();
            }
    </script>
</head>
<body>
    <div class="container">
    <div class="print-date"></div>

        <div class="d-flex justify-content-between align-items-center cont mb-3">
            <h2>تاريخ الدفع للطلاب</h2>
            <!-- Home Button -->
            <a href="home.php" class="btn btn-primary d-flex align-items-center contt">
                <i class="bi bi-house-fill" style="margin-left: 5px;"></i> 
                الرئيسية
            </a>
        </div>
        
        <form method="GET" action="">
            <div class="form-group">
                <label for="student_name">بحث الطالب بالاسم:</label>
                <input type="text" name="student_name" class="form-control" id="student_name" placeholder="أدخل اسم الطالب" autocomplete="off">
                <input type="hidden" name="student_id" id="student_id">
            </div>
            <button type="submit" class="btn btn-primary">بحث</button>
        </form>
        <div class="sheet-header">
        <img src="../images/header.png" alt="Header Image">
        <h3>تاريخ الدفع للطلاب</h3>

    </div>
        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>رقم الطالب</th>
                        <th>اسم الطالب</th>
                        <th>الشهر</th>
                        <th>المستحقات</th>
                        <th>المدفوع</th>
                        <th>المتبقي</th>
                        <th>طريقة الدفع</th>
                        <th>اسم البنك</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['student_id']}</td>
                            <td>{$row['student_name']}</td>
                            <td>{$row['month']}</td>
                            <td>{$row['due_amount']}</td>
                            <td>{$row['paid_amount']}</td>
                            <td>{$row['remaining_amount']}</td>
                            <td>{$row['payment_method']}</td>
                            <td>{$row['bank_name']}</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
            
        <?php else: ?>
            <p>لم يتم العثور على الطالب أو لا توجد مدفوعات.</p>
        <?php endif; ?>
    </div>
    <div class="button-group">
    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="printPage()">
    طباعة <i class="fas fa-print" style="margin-right: 8px;"></i> 
    </button>


    </div>

    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script>
        $(function() {
            $("#student_name").autocomplete({
                source: "autocompletee.php",
                minLength: 1,
                select: function(event, ui) {
                    $('#student_name').val(ui.item.label);
                    $('#student_id').val(ui.item.value);
                    return false;
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
    var printDate = new Date().toLocaleDateString('ar-EG', { year: 'numeric', month: 'long', day: 'numeric' });
    document.querySelector('.print-date').textContent = "التاريخ : " + printDate;
});

    </script>

    
</body>
</html>

<?php
$conn->close();
?>
