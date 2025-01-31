<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    exit;
}
$name_connect = $_SESSION['username'];

// Fetch payment IDs from URL parameters
$agent_id = isset($_POST['id_agent']) ? $_POST['id_agent']: '';


$receipt_data = [];
$receipt_data_rest = [];
$agent_name = '';
$student_name = '';
$agent_phone = '';
$bank_name = '';
$payment_id = '';
$remaining_amount = 0;
$total_paid_sum = 0;
$total_paid_sum_rest = 0;
$total_paid_rest = 0;
$student_remaining_sum = 0;
$receipt_date = '';
$created_by = '';


if (!empty($agent_id)) {
    $sql = "SELECT 
        r.receipt_id AS payment_id, 
        a.agent_id, 
        COALESCE(a.agent_name, 'بدون وكيل') AS agent_name, 
        a.phone AS agent_phone,
        r.total_amount,
        r.receipt_date,
        u.username AS created_by,
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
            students s ON c.student_id = s.id
        LEFT JOIN 
            agents a ON a.agent_id = r.agent_id
        LEFT JOIN 
            users u ON u.id = r.created_by
        LEFT JOIN 
            classes cl ON s.class_id = cl.class_id
        LEFT JOIN 
            bank_accounts b ON c.bank_id = b.account_id
        WHERE 
            c.description NOT LIKE 'تم إلغاء%' AND
            r.agent_id LIKE ?  
            AND DATE(r.receipt_date) >= DATE(NOW() - INTERVAL 12 HOUR)
        GROUP BY 
            s.student_name, s.class_id;";

    $stmt = $conn->prepare($sql);

    // Check for statement errors
    if ($stmt === false) {
        die('Error preparing the statement: ' . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param('i', $agent_id);

    // Execute and fetch results
    $stmt->execute();
    $result = $stmt->get_result();

    $vr=0;
    while ($row = $result->fetch_assoc()) {
        $receipt_data[] = $row;
        $resptId = $row['payment_id'];
        $receipt_date = $row['receipt_date'];
        $created_by = $row['created_by'];
        $agent_name = $row['agent_name'];
        $student_name = $row['student_name'];
        $agent_phone = $row['agent_phone'];
        $agent_id = $row['agent_id'];
        $bank_name = $row['bank_name'];
        $total_paid_sum += $row['total_paid'];
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


//     $sql = "SELECT 
//     t.id, 
//     t.agent_id, 
//     a.agent_name AS agent_name, 
//     a.phone AS agent_phone, 
//     s.student_name AS student_name, 
//     t.transaction_description AS transaction_description, 
//     IFNULL(b.bank_name, 'نقدي') AS bank_name,  
//     IFNULL(c.class_name, 'N/A') AS student_class,
//     SUM(t.amount) AS remaining_amount, 
//     SUM(t.amount) AS total_paid
//     FROM 
//         transactions t
//     LEFT JOIN 
//         agents a ON t.agent_id = a.agent_id
//     LEFT JOIN 
//         students s ON t.student_id = s.id
//     LEFT JOIN 
//         classes c ON s.class_id = c.class_id
//     LEFT JOIN 
//         bank_accounts b ON t.bank_account_id = b.account_id
//     WHERE 
//         t.agent_id LIKE ?  
//         AND t.transaction_date >= NOW() - INTERVAL 24 HOUR
//     GROUP BY 
//         s.student_name, c.class_id;";

// $stmt = $conn->prepare($sql);

// // Check for statement errors
// if ($stmt === false) {
//     die('Error preparing the statement: ' . $conn->error);
// }

// // Bind parameters
// $stmt->bind_param('i', $agent_id);

// // Execute and fetch results
// $stmt->execute();
// $result = $stmt->get_result();

// while ($row = $result->fetch_assoc()) {
//     $receipt_data_rest[] = $row;
//     $agent_name = $row['agent_name'];
//     $student_name = $row['student_name'];
//     $agent_phone = $row['agent_phone'];
//     $bank_name = $row['bank_name'];
//     $total_paid_rest = $row['total_paid'];
//     $total_paid_sum_rest += $row['total_paid'];
// }

// $stmt->close();
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
    <link rel="stylesheet" href="css/amiri.css">
    <link rel="stylesheet" href="css/tajawal.css">
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
        <!-- Information row -->
        <div class="summary-container">
            <div>
                <span>وصل رقم: </span><?php echo sprintf("%010d", $resptId ?? 0); ?>
            </div>
            <div>
                <span>بتاريخ: </span><?php 
                    $formatted_date = date('Y-m-d', strtotime($receipt_date)); 
                    $formatted_time = date('H:i:s', strtotime($receipt_date));
                    echo $formatted_date . ' | ' . $formatted_time;  ?>
            </div>
            <div>
                <span>المستخدم: </span><?php echo $created_by; ?>
            </div>
            <div>
                <span>السنة الدراسية: </span><?php echo $last_year; ?>
            </div>
        </div>
  
        <div class="info-container">
            <div><strong>اسم الوكيل(ة): </strong><?php echo $agent_name; ?></div>
            <div><strong>رقم الهاتف: </strong><?php echo $agent_phone; ?></div>
            <div><strong> رقم تعريف الوكيل(ة): </strong><?php echo $agent_id; ?></div>
        </div>

        <!-- Table for student data -->
        <table class="table text-center">
            <thead>
                <tr>
                    <th>اسم الطالب(ة)</th>
                    <th>القسم</th>
                    <th>الأشهر </th>
                    <th>المبلغ </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receipt_data as $data): ?>
                <tr>
                    <td><?php echo $data['student_name']; ?></td>
                    <td><?php echo $data['student_class']; ?></td>
                    <td><?php echo $data['months_paid'] ?? $data['transaction_descriptions'] ; ?></td>
                    <td><?php echo $data['total_paid']; ?></td>
                </tr>
                <?php endforeach; ?>

              
            </tbody>
        </table>

        <!-- Summary section -->
        <div class="summary-container">
            <div>
                <strong>حساب الدفع:</strong> 
                <span class="text-primary">
                    <?php echo empty($bank_name) ? 'نقدي' : $bank_name; ?>
                </span>
            </div>
            <div>
                <strong>مجموع الرسوم:</strong> <?php echo $student_remaining_sum; ?>
            </div>
            <div>
                <strong>المبلغ الإجمالي المدفوع:</strong> <?php echo $total_paid_sum; ?>
            </div>
            <div>
                <strong>المبلغ المتبقي:</strong> <?php echo $remaining_amount; ?>
            </div>
        </div>

        <!-- Print Button -->
        <div class="text-center mt-4">
            <button class="btn btn-success print-button" onclick="window.print()">طباعة</button>
        </div>
    </div>
</div>
</body>
</html>



