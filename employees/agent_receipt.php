<?php
// Enable error reporting
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

$name_connect = $_SESSION['username'];

$receiptId = isset($_GET['payment_ids']) ? $_GET['payment_ids'] : '';

$receipt_data = [];
$agent_name = '';
$student_name = '';
$agent_phone = '';
$agent_id = '';
$bank_name = '';
$remaining_amount = 0;
$total_paid_sum = 0;
$student_remaining_sum = 0;
$receipt_date = '';
$created_by = '';

if (!empty($receiptId)) {
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
            users u ON u.id = r.created_by
        LEFT JOIN
            agents a ON a.agent_id = r.agent_id
        LEFT JOIN
            classes cl ON s.class_id = cl.class_id
        LEFT JOIN
            bank_accounts b ON c.bank_id = b.account_id
        WHERE
            r.receipt_id = ?
        GROUP BY
            s.id, c.description;";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $receiptId);
    $stmt->execute();
    $result = $stmt->get_result();

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
            $monthsArray = explode(',', $row['months_paid']);
            $student_remaining = (float)$row['student_remaining'];
            $remaining_amount += (float)$row['remaining_amount'];
            // Calculate for the current student
            $value = count($monthsArray) * $student_remaining;
            $student_remaining_sum += floor($value / 100) * 100;
        }
    }

    
    $grouped_students = [];
    foreach ($receipt_data as $data) {
        $studentName = $data['student_name'];
        $studentClass = $data['student_class'];
        $transactionDesc = $data['transaction_descriptions'];
        $monthsPaid = $data['months_paid'];
        $totalPaid = $data['total_paid'];
        
        $key = $studentName . '_' . $studentClass;
        
        // إذا كان هناك أشهر مدفوعة، اجمعها في سطر واحد
        if (!empty($monthsPaid)) {
            if (!isset($grouped_students[$key])) {
                $grouped_students[$key] = [
                    'student_name' => $studentName,
                    'student_class' => $studentClass,
                    'months' => [],
                    'total_paid' => 0,
                ];
            }
            // إضافة الأشهر إلى المصفوفة وجمع المبالغ
            $monthsArray = explode(', ', $monthsPaid);
            foreach ($monthsArray as $month) {
                $grouped_students[$key]['months'][] = trim($month);
            }
            $grouped_students[$key]['total_paid'] += $totalPaid;
        } else {
            // إذا لم تكن دفعة شهرية، أضفها كسطر منفصل
            $uniqueKey = $key . '_' . uniqid();
            $grouped_students[$uniqueKey] = [
                'student_name' => $studentName,
                'student_class' => $studentClass,
                'description' => $transactionDesc,
                'total_paid' => $totalPaid,
            ];
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
    <link rel="stylesheet" href="css/amiri.css">
    <link rel="stylesheet" href="css/tajawal.css">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/receipt.css">

    <!-- <style>
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
        th, td{
            text-align: center !important;
            vertical-align: middle!important;
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

    </style> -->
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
                <strong>وصل رقم: </strong>  <?php echo sprintf("%010d", $resptId); ?>
            </div>
                <div><strong>بتاريخ: </strong>
                    <?php
                        $formatted_date = date('Y-m-d', strtotime($receipt_date));
                        $formatted_time = date('H:i:s', strtotime($receipt_date));
                        echo $formatted_date . ' | ' . $formatted_time;  ?>
                </div>
            <div>
                    <strong>المستخدم: </strong>  <?php echo $created_by; ?>
            </div>
            <div>
                <strong>السنة الدراسية: </strong>  <?php echo $last_year; ?>
            </div>
        </div>
        <div class="info-container">
            <div><strong>اسم الوكيل(ة): </strong>  <?php echo $agent_name; ?></div>
            <div><strong>رقم الهاتف: </strong>  <?php echo $agent_phone; ?></div>
            <div><strong> رقم تعريف الوكيل(ة): </strong><?php echo $agent_id; ?></div>
        </div>

        <!-- Table for student data -->
        <table class="table text-center">
            <thead>
                <tr>
                    <th>اسم الطالب(ة)</th>
                    <th>القسم</th>
                    <th>الأشهر المدفوعة</th>
                    <th>المبلغ الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grouped_students as $entry): ?>
                <tr>
                    <td><?php echo htmlspecialchars($entry['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($entry['student_class']); ?></td>
                    <td>
                        <?php
                            echo isset($entry['months']) ? implode(', ', $entry['months']) : htmlspecialchars($entry['description']);
                        ?>
                    </td>
                    <td><?php echo number_format($entry['total_paid'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary section -->
        <div class="summary-container">
            <div>
                <span>حساب الدفع</span>:
                <span class="text-primary">
                    <?php echo empty($bank_name) ? 'نقدي' : $bank_name; ?>
                </span>
            </div>
            <div>
                <strong>مجموع الرسوم: </strong><?php echo $student_remaining_sum; ?>
            </div>
            <div>
                <strong>المبلغ المدفوع: </strong><?php echo $total_paid_sum; ?>
            </div>
            <div>
                <strong>المبلغ المتبقي: </strong><?php echo $remaining_amount; ?>
            </div>
        </div>

        <!-- Print Button -->
        <div class="text-center">
            <button class="btn btn-success print-button mt-3" onclick="window.print()">طباعة</button>
        </div>
    </div>
</div>
</body>
</html>



