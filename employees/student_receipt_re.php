<?php
// Include database connection
include 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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


$id = isset($_GET['receipt_id']) ? $_GET['receipt_id'] : '';



$receipt_data = [];
$payment_method = '';
$receipt_date = '';
$created_by = '';

$remaining_amount = 0;
$student_name = '';
$agent_phone = '';
$agent_id = '';
$paid_amount = 0;
$student_class = ''; 
$student_remaining_sum = 0;
$total_paid_sum = 0;
$bank_name = '';

if (!empty($id)) {
    // Prepare the SQL query
    $sql = "SELECT 
        r.receipt_id AS receipt_id, 
        r.total_amount,
        r.receipt_date,
        u.username AS created_by,
        c.payment_method,
        s.phone,
        br.branch_name,
        s.student_name AS student_name, 
        IFNULL(b.bank_name, 'نقدي') AS bank_name,  
        IFNULL(cl.class_name, 'N/A') AS student_class,
        SUM(c.remaining_amount) AS remaining_amount, 
        s.remaining AS student_remaining, 
        GROUP_CONCAT(c.month ORDER BY c.month SEPARATOR ', ') AS months_paid, 
        SUM(c.paid_amount) AS total_paid, 
        COALESCE(c.description, 'دفع رسوم اشهر ') AS transaction_descriptions
        FROM 
            receipts r
        LEFT JOIN 
            receipt_payments AS rp ON r.receipt_id = rp.receipt_id
        LEFT JOIN         
            combined_transactions AS c ON rp.transaction_id = c.id
        LEFT JOIN 
            users u ON u.id = r.created_by
        LEFT JOIN 
            students s ON c.student_id = s.id
        LEFT JOIN 
            classes cl ON s.class_id = cl.class_id
        LEFT JOIN 
            branches br ON s.branch_id = br.branch_id
        LEFT JOIN 
            bank_accounts b ON c.bank_id = b.account_id
    WHERE 
        r.receipt_id = ?
    GROUP BY 
        s.student_name, c.description;";  

    $stmt = $conn->prepare($sql);
    
    // Bind the parameter
    $stmt->bind_param('s', $id);
    
    // Execute the statement
    $stmt->execute();
    
    // Fetch data
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $receipt_data[] = $row;
        $student_name = $row['student_name'];
        $total_paid_sum += $row['total_paid'];
        $receipt_date = $row['receipt_date'];
        $created_by = $row['created_by'];

        $agent_phone = $row['phone'];
        $student_class = $row['student_class']; 
        $branch_name = $row['branch_name']; 
        $bank_name = $row['bank_name']; 
        $paid_amount = $row['total_paid']; 
        $payment_method = $row['payment_method']; 
    }

    foreach ($receipt_data as $row) {
        if (!empty($row['months_paid']) && is_string($row['months_paid']) && !empty($row['student_remaining'])) {
            $monthsArray = explode(', ', $row['months_paid']);
            $student_remaining = (float)$row['student_remaining'];
            $remaining_amount += (float)$row['remaining_amount'];
            // Calculate for the current student
            $value = count($monthsArray) * $student_remaining;
            $student_remaining_sum += floor($value / 100) * 100;
        }
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
            margin-top: 20px;
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
            padding: 5px;
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
        table{
            border: 1px solid #000 !important;

            }

            th, td, .table-bordered {
                border: 1px solid black !important;
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
                margin-top: 20px;
                padding: 0px;
                border: none;
                color: #000;
                /* page-break-inside: avoid; */
            }

            table,  {
                width: 100% !important;
                border-collapse: collapse;
                border: 1px solid #000 !important;

            }

            th, td, .table-bordered {
                padding: 0px !important;
                margin: 0px !important;
                border: 1px solid black !important;
                text-align: center;
                color: #000;
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
            <div class="summary-container">
                <div><strong>وصل رقم: </strong><?php echo sprintf("%010d", $id); ?></div>
                <div><strong>بتاريخ: </strong><?php 
                    $formatted_date = date('Y-m-d', strtotime($receipt_date));
                    $formatted_time = date('H:i:s', strtotime($receipt_date)); 
                    echo $formatted_date . ' | ' . $formatted_time; 
                    ?></div>
                <div>
                    <strong>المستخدم: </strong><?php echo $created_by; ?>
                </div>
                <div><strong>السنة الدراسية: </strong><?php echo $last_year; ?></div>
            </div>
            <div class="info-container">
                <div><strong> الفرع: </strong><?php echo $branch_name; ?></div>
                <div><strong>رقم التعريف: </strong><?php echo $id; ?></div>
            </div>
            <table class="table text-center">
                <thead>
                <tr>
                    <th>اسم الطالب(ة)</th>
                    <th>القسم</th>
                    <th>المدفوعات </th>
                    <th>المبلغ </th>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($receipt_data as $data): ?>
                    <tr>    
                        <td><?php echo $data['student_name']; ?></td>
                        <td><?php echo $data['student_class']; ?></td>
                        <td><?php echo $data['months_paid'] ?? $data['transaction_descriptions'] ;  ?></td>
                        <td><?php echo $data['total_paid']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="summary-container">
                <div><span>حساب الدفع</span>: <span class="text-primary"><?php echo $bank_name ?? 'نقدي'; ?></span></div>

                <div><span>مجموع الرسوم</span>: <?php echo $student_remaining_sum - $remaining_amount; ?></div>

                <div>
                    <span>المبلغ الإجمالي المدفوع</span> : <?php echo $total_paid_sum; ?>
                </div>
                <div><span>المبلغ المتبقي</span>: <?php echo $remaining_amount; ?></div>

            </div>
            <div class="text-center mt-4">
                <button class="btn btn-success print-button" onclick="window.print()">طباعة</button>
            </div>
        </div>
    </div>
</body>
</html>
