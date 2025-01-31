<?php
// Include database connection
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


$name_connect = $_SESSION['username'];


$student_activity_id = $_GET['student_activity_id'];
$payment_id = $_GET['payment_id'];

if (isset($student_activity_id) && isset($payment_id)) {
    // Prepare the SQL query to fetch the receipt data
    $sql = "
    SELECT s.student_name, s.phone, c.class_name, a.activity_name, a.price, ap.paid_amount, ap.payment_date, ap.payment_method, b.bank_name
    FROM activities_payments ap
    INNER JOIN student_activities sa ON ap.student_activity_id = sa.id
    INNER JOIN students s ON sa.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.class_id
    INNER JOIN activities a ON sa.activity_id = a.id
    LEFT JOIN bank_accounts b ON ap.bank_id = b.account_id
    WHERE ap.id = ? AND sa.id = ?
";


    $stmt = $conn->prepare($sql);

    // Check if the statement was prepared correctly
    if ($stmt === false) {
        die('Error preparing the statement: ' . $conn->error);
    }

    $stmt->bind_param('ii', $payment_id, $student_activity_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $price = $data['price'];  // Fetch the price from the data
    } else {
        echo "No data found.";
        exit();
    }

    $stmt->close();
} else {
    echo "Invalid access.";
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include necessary head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Payment Receipt</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/amiri.css">

    <link rel="stylesheet" href="css/tajawal.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            direction: rtl;
            text-align: right;
        }

        .receipt {
            background-color: white;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 5px;
        }

        .receipt-header img {
            width: 100%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 20px;
        }

        .info-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            align-items: center;
        }

        .info-container div {
            flex: 1;
            text-align: center;
        }

        .info-container div:not(:last-child) {
            margin-right: 10px;
        }

        .info-container .highlight {
            color: #007b5e;
            font-weight: bold;
        }

        .months-container {
            border: 1px solid #007b5e;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            margin-top: 20px;
            font-weight: bold;
        }

        .summary-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .summary-container div {
            flex: 1;
            text-align: center;
            font-weight: bold;
            color: #5a5a5a;
        }

        .summary-container .text-primary {
            color: #17a2b8 !important;
        }

        .footer-note {
            text-align: right;
            margin-top: 20px;
            font-weight: bold;
            color: #5a5a5a;
        }

        /* Print styles */
        @media print {
            @page {
                size: A5;
                margin: 10mm;
            }

            body {
                transform: scale(1.0); /* Scale down to fit A5 size */
                transform-origin: center;
            }

            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="receipt">
            <div class="receipt-header">
                <img src="../images/header.png" alt="Header Image">
            </div>
            <!-- Information row -->
            <div class="summary-container">
                <div>
                    <span>وصل رقم</span> : <?php echo sprintf("%05d", $payment_id); ?>
                </div>
                <div>
                    <span>المستخدم</span> : <?php echo $name_connect; ?>
                </div>
                <div>
                    <span>التاريخ</span> : <?php echo date('d/m/Y', strtotime($data['payment_date'])); ?>
                </div>
                <div>
                    <label class="form-select-title" for="financial-year" style="margin-left: 15px;">السنة المالية</label>
                    <select id="financial-year" class="form-select w-100">
                        <option><?php echo $last_year; ?></option>
                    </select>               
                </div>
            </div>
            <br>
            <div class="info-container">
                <div>
                    <span class="highlight">اسم الطالب</span> : <?php echo $data['student_name']; ?>
                </div>
                <div>
                    <span class="highlight">القسم</span> : <?php echo $data['class_name']; ?>
                </div>
                <div>
                    <span class="highlight">رقم الهاتف</span> : <?php echo $data['phone']; ?>
                </div>
            </div>
            <!-- Paid months section -->
            <div class="months-container">
                <span class="highlight">النشاط أو دورة تكوينية</span> : <?php echo $data['activity_name']; ?>
            </div>
            <!-- Summary section -->
            <div class="summary-container">
    <div>
        <span>حساب الدفع</span> : 
        <span class="text-primary">
            <?php 
                if ($data['payment_method'] === 'بنكي') {
                    echo 'بنكي - ' . $data['bank_name'];
                } else {
                    echo $data['payment_method'];
                }
            ?>
        </span>
    </div>
    <div>
        <span>مجموع الرسوم</span> : <?php echo isset($price) ? $price : 'N/A'; ?> أوقية جديدة
    </div>
    <div>
        <span>المبلغ المدفوع</span> : <?php echo number_format($data['paid_amount'], 2, '.', ''); ?> أوقية جديدة
    </div>
</div>

            <!-- Print Button -->
            <div class="text-center mt-4">
                <button class="btn btn-primary print-button" onclick="window.print()">طباعة</button>
            </div>
        </div>
    </div>
</body>
</html>
