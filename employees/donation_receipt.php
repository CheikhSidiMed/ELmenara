<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include 'db_connection.php';

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


// Fetch payment IDs from URL parameters
$receipt_id = isset($_GET['receipt_id']) ? $_GET['receipt_id'] : '';

$receipt_data = [];
$bank_name = '';
$total_paid_sum = 0;
$student_remaining_sum = 0;
$receipt_date = '';
$created_by = '';

if (!empty($receipt_id)) {
    // Prepare placeholders for SQL IN clause
    $sql = "SELECT 
        r.receipt_id AS payment_id,
        r.receipt_date,
        u.username AS created_by,
        c.description AS transaction_description, 
        IFNULL(b.bank_name, 'نقدي') AS bank_name,
        SUM(c.paid_amount) AS paid_amount
        FROM 
            receipts r
        LEFT JOIN 
            receipt_payments AS rp ON r.receipt_id = rp.receipt_id
        LEFT JOIN         
            combined_transactions AS c ON rp.transaction_id = c.id
        LEFT JOIN 
            users u ON u.id = r.created_by
        LEFT JOIN 
            bank_accounts b ON c.bank_id = b.account_id
        WHERE 
            r.receipt_id = ?
        GROUP BY 
            r.receipt_id;";

    $stmt = $conn->prepare($sql);

    // Check for statement errors
    if ($stmt === false) {
        die('Error preparing the statement: ' . $conn->error);
    }

    $stmt->bind_param('i', $receipt_id);

    // Execute and fetch results
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $receipt_data[] = $row;
        $receipt_date = $row['receipt_date'];
        $created_by = $row['created_by'];
        $bank_name = $row['bank_name'];
        $total_paid_sum += $row['paid_amount'];
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال الدفع</title>
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
            margin: auto;
            width: 100%; /* Full width on screen */
        }

        .receipt-header img {
            width: 100%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 0px;
        }

        .info-line {
            margin-bottom: 10px;
            font-weight: bold;
            color: #5a5a5a;
        }

        .info-line span {
            color: #007b5e;
            font-weight: bold;
        }

        .info-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            margin-top: 10px;
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

        .summary-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #000;
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
                size: A5;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0px;
                font-size: 10pt;
                color: #000; /* Force text to black */
            }
            .container {
                margin-top: -40px !important;
                margin-right: -39px !important;

                transform: scale(.69);
                transform-origin: right !important; /* Origine de l'échelle au centre */
                display: flex !important; /* Active le flexbox */
                justify-content: center !important; /* Centre horizontalement */
                align-items: center !important;
            }
            .info-container {
                padding-top: 4px !important;
                margin: 0px !important;
            }
            .container {
                max-width: 1200px !important;
                margin: 0 auto;
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
                padding: 0px !important;
                margin: 0px !important;
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
            <div><span>بتاريخ</span> : <?php echo $receipt_date; ?></div>
            <div>
                    <span>المستخدم</span> : <?php echo $created_by; ?>
            </div>
            <div>
                <span>السنة الدراسية</span> : <?php echo $last_year; ?>
            </div>
        </div>
        <div class="info-container">
        <div style="text-align: center; font-family: Tajawal, sans-serif; padding: 5px;">
    <h2 style="color: #4CAF50;">🌟 شكراً جزيلاً على دعمكم! 🌟</h2>
    <p style="font-size: 18px;">
         لقد ساهمتم بكرمكم في إحداث فرق كبير! <br>
         جزاكم الله خيراً وجعل ما تقدمونه في ميزان حسناتكم.
    </p>

</div>
        </div>

        <!-- Table for student data -->

                <?php foreach ($receipt_data as $data): ?>
                    <div class="summary-container" style="text-align: center;">
                        <div>

                            <?php echo $data['transaction_description']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
 

        <!-- Summary section -->
        <div class="summary-container">
            <div>
                <span>حساب الدفع</span> : 
                <span class="text-primary">
                    <?php echo $bank_name; ?>
                </span>
            </div>
            <div>
                <span>المبلغ المدفوع</span> : <?php echo $total_paid_sum; ?>
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



