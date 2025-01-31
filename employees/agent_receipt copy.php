<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$payment_ids = isset($_GET['payment_ids']) ? explode(',', $_GET['payment_ids']) : [];

$receipt_data = [];
$agent_name = '';
$student_name = '';
$agent_phone = '';
$agent_id = '';
$payment_method = '';
$paid_amount = 0;
$remaining_amount = 0;
$bank_name = ''; // Variable to hold the bank name

if (!empty($payment_ids)) {
    // Prepare the placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($payment_ids), '?'));
    $sql = "SELECT 
    p.payment_id,
    a.agent_id,             
    a.agent_name AS agent_name,
    a.phone AS agent_phone,
    s.student_name AS student_name,
    b.bank_name AS bank_name,
    SUM(p.remaining_amount) AS remaining_amount,
    c.class_name AS student_class,
        SUM(s.remaining) AS student_remaining,

    GROUP_CONCAT(p.month ORDER BY p.month SEPARATOR ', ') AS months_paid,
    SUM(p.paid_amount) AS total_paid
    FROM payments p
    JOIN agents a ON p.agent_id = a.agent_id
    JOIN students s ON p.student_id = s.id
    JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN bank_accounts b ON p.bank_id = b.account_id
    WHERE p.payment_id IN ($placeholders)
    GROUP BY s.id, c.class_id;
    ";  

    $stmt = $conn->prepare($sql);
    
    // Check if the statement was prepared correctly
    if ($stmt === false) {
        die('Error preparing the statement: ' . $conn->error);
    }
    
    // Bind the parameters dynamically based on the number of payment IDs
    $types = str_repeat('i', count($payment_ids)); // 'i' for integer since payment_id is an integer
    $stmt->bind_param($types, ...$payment_ids);
    
    // Execute the statement
    $stmt->execute();
    
    // Get the result and fetch the data
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $receipt_data[] = $row;
        $agent_name = $row['agent_name'];     // Assign agent name
        $student_name = $row['student_name'];      // Assign agent name
        $agent_phone = $row['agent_phone'];    // Assign agent phone
        $agent_id = $row['agent_id'];  
        $bank_name = $row['bank_name'];
        $remaining_amount = $row['remaining_amount'];

        // Store other agent and payment details if necessary
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
    <title>Payment Receipt</title>
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
            margin: auto;
            width: 100%; /* Full width on screen */
        }

        .receipt-header img {
            width: 100%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 20px;
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
                margin: 0mm;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .receipt {
                width: 100%; /* Scale to 50% of the A5 page */
                margin: auto;
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
                    <span>وصل رقم</span> : <?php echo sprintf("%010d", $payment_ids[0]); ?>
                
                </div>
                <div>
                    <span>التاريخ</span> : <?php echo date('d/m/Y | H:i'); ?>
                </div>
                <div>
                    <span>السنة الدراسية</span> : 2023/2024
                </div>
            </div>
            <br>
            <div class="info-container">
                <div>
                    <span>اسم الوكيل</span> : <?php echo $agent_name; ?>
                </div>
                <div>
                    <span>رقم الهاتف</span> : <?php echo $agent_phone; ?>
                </div>
                <div>
                    <span>رقم التعريف</span> : <?php echo $agent_id; ?>
                </div>
            </div>

<!-- Table for student data -->
<table class="table table-bordered mt-4 text-center">
    <thead>
        <tr>
            <th>اسم الطالب</th>
            <th>القسم</th>
            <th>الأشهر المدفوعة</th>
            <th>المبلغ الإجمالي</th> <!-- Total amount paid -->
        </tr>
    </thead>
    <tbody>
        <?php foreach ($receipt_data as $data): ?>
        <tr>
            <td><?php echo $data['student_name']; ?></td>
            <td><?php echo $data['student_class']; ?></td>
            <td><?php echo $data['months_paid']; ?></td> <!-- Display months paid -->
            <td><?php echo $data['total_paid']; ?></td> <!-- Display total paid -->
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            <!-- Summary section -->
            <div class="summary-container">
            <div>
        <span>حساب الدفع</span> : 
        <span class="text-primary">
        <?php echo $bank_name == ''  ? 'نقدي'  : $bank_name; ?>

        </span>
    </div>
    <div>
        <span>مجموع الرسوم</span> : <?php echo $data['student_remaining']; ?>
    </div>
    <div>
        <span>المبلغ المدفوع</span> : <?php echo $data['total_paid']; ?>
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




















<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Include database connection
include 'db_connection.php';

$payment_ids = isset($_GET['payment_ids']) ? explode(',', $_GET['payment_ids']) : [];

$receipt_data = [];
$agent_name = '';
$student_name = '';
$agent_phone = '';
$agent_id = ''; 
$payment_method = '';
$paid_amount = 0;
$remaining_amount = 0;
$bank_name = ''; // Variable to hold the bank name

if (!empty($payment_ids)) {
    // Prepare the placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($payment_ids), '?'));
    $sql = "SELECT 
    p.payment_id,
    a.agent_id,             
    a.agent_name AS agent_name,
    a.phone AS agent_phone,
    s.student_name AS student_name,
    b.bank_name AS bank_name,
    SUM(p.remaining_amount) AS remaining_amount,
    c.class_name AS student_class,
        SUM(s.remaining) AS student_remaining,

    GROUP_CONCAT(p.month ORDER BY p.month SEPARATOR ', ') AS months_paid,
    SUM(p.paid_amount) AS total_paid
    FROM payments p
    JOIN agents a ON p.agent_id = a.agent_id
    JOIN students s ON p.student_id = s.id
    JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN bank_accounts b ON p.bank_id = b.account_id
    WHERE p.payment_id IN ($placeholders)
    GROUP BY s.id, c.class_id;
    ";  

    $stmt = $conn->prepare($sql);
    
    // Check if the statement was prepared correctly
    if ($stmt === false) {
        die('Error preparing the statement: ' . $conn->error);
    }
    
    // Bind the parameters dynamically based on the number of payment IDs
    $types = str_repeat('i', count($payment_ids)); // 'i' for integer since payment_id is an integer
    $stmt->bind_param($types, ...$payment_ids);
    
    // Execute the statement
    $stmt->execute();
    
    // Get the result and fetch the data
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $receipt_data[] = $row;
        $agent_name = $row['agent_name'];     // Assign agent name
        $student_name = $row['student_name'];      // Assign agent name
        $agent_phone = $row['agent_phone'];    // Assign agent phone
        $agent_id = $row['agent_id'];  
        $bank_name = $row['bank_name'];
        $remaining_amount = $row['remaining_amount'];

        // Store other agent and payment details if necessary
    }
    $total_paid_sum = 0;
    $student_remaining_sum = 0;
    
    foreach ($receipt_data as $data) {
        $total_paid_sum += $data['total_paid'];
        $student_remaining_sum += $data['student_remaining'];
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
    <title>Payment Receipt</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
            margin-bottom: 20px;
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
                margin: 0mm;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .receipt {
                width: 100%; /* Scale to 50% of the A5 page */
                margin: auto;
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
                    <span>وصل رقم</span> : <?php echo sprintf("%010d", $payment_ids[0]); ?>
                
                </div>
                <div>
                    <span>التاريخ</span> : <?php echo date('d/m/Y | H:i'); ?>
                </div>
                <div>
                    <span>السنة الدراسية</span> : 2023/2024
                </div>
            </div>
            <br>
            <div class="info-container">
                <div>
                    <span>اسم الوكيل</span> : <?php echo $agent_name; ?>
                </div>
                <div>
                    <span>رقم الهاتف</span> : <?php echo $agent_phone; ?>
                </div>
                <div>
                    <span>رقم التعريف</span> : <?php echo $agent_id; ?>
                </div>
            </div>

<!-- Table for student data -->
<table class="table table-bordered mt-4 text-center">
    <thead>
        <tr>
            <th>اسم الطالب</th>
            <th>القسم</th>
            <th>الأشهر المدفوعة</th>
            <th>المبلغ الإجمالي</th> <!-- Total amount paid -->
        </tr>
    </thead>
    <tbody>
        <?php foreach ($receipt_data as $data): ?>
        <tr>
            <td><?php echo $data['student_name']; ?></td>
            <td><?php echo $data['student_class']; ?></td>
            <td><?php echo $data['months_paid']; ?></td> <!-- Display months paid -->
            <td><?php echo $data['total_paid']; ?></td> <!-- Display total paid -->
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            <!-- Summary section -->
            <div class="summary-container">
            <div>
    <span>حساب الدفع</span> : 
    <span class="text-primary">
        <?php echo $bank_name == '' ? 'نقدي' : $bank_name; ?>
    </span>
</div>
<div>
    <span>مجموع الرسوم</span> : <?php echo $student_remaining_sum; ?>
</div>
<div>
    <span>المبلغ المدفوع</span> : <?php echo $total_paid_sum; ?>
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


