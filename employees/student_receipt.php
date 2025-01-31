<?php
// Include database connection
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    exit;
}
$name_connect = $_SESSION['username'];


$receipt_id = isset($_GET['receipt_id']) ? $_GET['receipt_id'] : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$paid_amount = isset($_GET['paid_amount']) ? $_GET['paid_amount'] : '';
$remaining_amount = isset($_GET['remaining_amount']) ? $_GET['remaining_amount'] : '';
$months_paid = isset($_GET['months_paid']) ? $_GET['months_paid'] : '';
$due_amounte = isset($_GET['due_amounte']) ? $_GET['due_amounte'] : '';
$bank_name = ''; // Initialize bank_name variable
$receipt_data_rest = [];

$sql = "SELECT students.student_name, students.phone, students.remaining, classes.class_name, branches.branch_name, b.bank_name
        FROM students
        JOIN classes ON students.class_id = classes.class_id
        JOIN branches ON students.branch_id = branches.branch_id
        LEFT JOIN payments p ON students.id = p.student_id AND p.payment_id = ?
        LEFT JOIN bank_accounts b ON p.bank_id = b.account_id
        WHERE students.id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Error preparing the statement: ' . $conn->error);
}

// Bind the receipt_id and student_id
$stmt->bind_param("ii", $receipt_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student_data = $result->fetch_assoc();
    $bank_name = $student_data['bank_name'] ? $student_data['bank_name'] : ''; // Assign bank_name if exists
} else {
    die("Student or payment not found.");
}
$stmt->close();


// Initialize variables
$receipt_data_rest = [];
$total_paid_sum_rest = 0;

// SQL query
$sql = "SELECT 
        t.id, 
        s.student_name AS student_name, 
        t.transaction_description AS transaction_description, 
        IFNULL(b.bank_name, 'نقدي') AS bank_name,  
        SUM(t.amount) AS total_paid
    FROM 
        transactions t
    LEFT JOIN 
        students s ON t.student_id = s.id
    LEFT JOIN
        bank_accounts b ON t.bank_account_id = b.account_id
    WHERE 
        t.student_id = ?  
        AND t.transaction_date >= NOW() - INTERVAL 24 HOUR
        AND t.transaction_description LIKE '%سدد متأخرات%'
    GROUP BY 
        s.student_name";

// Prepare and check the statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Error preparing the statement: ' . $conn->error);
}

// Bind parameters and execute
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch results and calculate totals
while ($row = $result->fetch_assoc()) {
    $receipt_data_rest[] = $row;
    $total_paid_sum_rest += $row['total_paid'];
}

$stmt->close();


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include necessary head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Payment Receipt</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
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

        @media print {
            @page {
                size: A5 landscape;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0px;
                font-size: 10pt;
                color: #000; /* Force text to black */
            }

            .container {
                max-width: 1200px !important;
                margin: 0 auto;
                margin-right: -67px !important;

                padding: 0 2px;
                text-align: center;
            }

            .receipt {
                max-width: 80% !important;
                margin: 0 auto;
                padding: 0px;
                border: none;
                color: #000;
                /* page-break-inside: avoid; */
            }

            table {
                width: 100% !important;
                border-collapse: collapse;
            }

            th, td {
                padding: 0px;
                border: 1px solid #000;
                text-align: center;
                font-size: 9pt !important;
            }

            /* Styling for emphasis */
            .highlight {
                font-weight: bold;
                font-size: 9.5pt;
                color: #000;
            }

            /* Hide unnecessary print elements */
            .no-print, .print-button {
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
                    <span>وصل رقم</span> : <?php echo sprintf("%010d", $receipt_id); ?>
                </div>
                <div><span>بتاريخ</span> : <?php echo date('d/m/Y | H:i'); ?></div>
                <div>
                    <span>المستخدم</span> : <?php echo $name_connect; ?>
                </div>
                <div>
                    <span>السنة الدراسية</span> : <?php echo $last_year; ?>
                </div>
            </div>
            <br>
            <div class="info-container">
                <div>
                    <span class="highlight">اسم الطالب</span> : <?php echo $student_data['student_name']; ?>
                </div>
                <div>
                    <span class="highlight">الفرع</span> : <?php echo $student_data['branch_name']; ?>
                </div>
                <div>
                    <span class="highlight">القسم</span> : <?php echo $student_data['class_name']; ?>
                </div>
                <div>
                    <span class="highlight">رقم الهاتف</span> : <?php echo $student_data['phone']; ?>
                </div>
            </div>
            <!-- Paid months section -->
            <div class="months-container">
                <span class="highlight">الأشهر المسددة</span> : <?php echo $months_paid; ?>
            </div>
            <?php foreach ($receipt_data_rest as $data_rest): ?>
    <div class="months-container">
        <span class="highlight"><?php echo $student_data['student_name']. '  :          '; ?><?php echo htmlspecialchars('سدد متأخرات: '); ?></span>
        <?php echo htmlspecialchars($data_rest['total_paid']); ?>
    </div>
<?php endforeach; ?>


            <!-- Summary section -->
            <div class="summary-container">
                <div>
                    <span>حساب الدفع</span> : 
                    <span class="text-primary">
                        <?php echo $payment_method == 'بنكي' ? 'بنكي - ' . ($bank_name ?: 'غير متوفر') : 'نقدي'; ?>
                    </span>
                </div>
                <div>
                    <span>مجموع الرسوم</span> : <?php echo $due_amounte; ?> <!-- Displaying due amount -->
                </div>
                <div>
                    <span>المبلغ المدفوع</span> : <?php echo $paid_amount; ?>
                </div>
                <div>
                    <span>المبلغ المتبقي</span> : <?php echo $remaining_amount; ?>
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
