<?php

// Include database connection
include 'db_connection.php';


session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}



if (isset($_GET['receipt_id'])) {
    $receipt_id = $_GET['receipt_id'];

    // Fetch the receipt details
    $sql = "SELECT p.student_id, s.student_name, p.month, p.due_amount, p.paid_amount, p.remaining_amount, p.payment_method, p.payment_date, b.bank_name
            FROM payments p
            INNER JOIN students s ON p.student_id = s.id
            LEFT JOIN bank_accounts b ON p.bank_id = b.account_id
            WHERE p.payment_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("MySQL prepare error: " . $conn->error);
    }

    $stmt->bind_param("i", $receipt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $receipt_data = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Invalid receipt ID.");
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وصل دفع</title>
    <!-- <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 0;
        }

        .header-img {
            text-align: center;
            margin-bottom: 10px;
        }

        .header-img img {
            max-width: 100%;
            height: auto;
        }

        h3 {
            text-align: center;
            margin-bottom: 10px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
            table-layout: fixed;
        }

        th, td {
            padding: 8px;
            text-align: right;
            border: 1px solid #000;
        }

        th {
            background-color: #f2f2f2;
            font-size: 14px;
        }

        .print-btn {
            display: block;
            width: 100%;
            padding: 8px;
            margin-top: 10px;
            text-align: center;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                width: 120mm;
                height: 200mm;
                /* Ensure the A5 page size */
                -webkit-print-color-adjust: exact;
            }

            .print-btn {
                display: none;
            }
        }

        @page {
            size: A5;
            margin: 10mm; /* Adjust the margins as needed */
        }
    </style> -->
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 15px;
            box-sizing: border-box;
        }

        .receipt {
            background-color: white;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 5px;
            margin: auto;
            width: 100%;
            max-width: 800px; /* Maximum width for larger screens */
            box-sizing: border-box;
        }

        .receipt-header img {
            width: 100%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 0px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .info-line {
            margin-bottom: 10px;
            font-weight: bold;
            color: #5a5a5a;
            word-break: break-word; /* Prevents text overflow */
        }

        .info-line span {
            color: #007b5e;
            font-weight: bold;
        }

        .info-container {
            display: flex;
            flex-wrap: wrap; /* Allows items to wrap on small screens */
            justify-content: space-between;
            margin-bottom: 10px;
            margin-top: 10px;
            align-items: center;
            gap: 10px; /* Adds space between items when they wrap */
        }

        .info-container div {
            flex: 1;
            min-width: 120px;
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
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            font-size: 14px;
            padding: 5px;
            border: 1px solid #000;
            border-radius: 5px;
        }

        .summary-container div {
            flex: 1;
            min-width: 100px; /* Minimum width before wrapping */
            text-align: center;
            font-weight: bold;
            color: #5a5a5a;
            padding: 2px;
            box-sizing: border-box;
        }

        .summary-container .text-primary {
            color: #17a2b8 !important;
        }

        .footer-note {
            text-align: right;
            margin-top: 20px;
            font-weight: bold;
            color: #5a5a5a;
            word-break: break-word;
        }

        table {
            border: 1px solid #000 !important;
            width: 100%;
            border-collapse: collapse;
        }

        th, td, .table-bordered {
            border: 1px solid black !important;
            padding: 8px;
            word-break: break-word; /* Prevents text overflow in cells */
        }

        @media (max-width: 600px) {
            .receipt {
                padding: 15px;
            }
            
            .info-container div, 
            .summary-container div {
                flex: 100%; /* Stack items vertically on small screens */
                margin-right: 0 !important;
                margin-bottom: 1px;
            }
            
            .info-container div:last-child,
            .summary-container div:last-child {
                margin-bottom: 0;
            }
            
            th, td {
                padding: 4px 0px !important;
                margin: 0px !important;
                font-size: 10px;
            }
        }

        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            
            .receipt {
                box-shadow: none;
                padding: 0;
                width: 100%;
            }
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
    <div class="header-img">
        <img src="../images/header.png" alt="Header Image">
    </div>
    <h3>وصل دفع</h3>
    <table>
        <tr><th>رقم الوصل:</th><td><?php echo $receipt_id; ?></td></tr>
        <tr><th>اسم الطالب:</th><td><?php echo $receipt_data['student_name']; ?></td></tr>
        <tr><th>الشهر:</th><td><?php echo $receipt_data['month']; ?></td></tr>
        <tr><th>طريقة الدفع:</th><td><?php echo $receipt_data['payment_method']; ?></td></tr>
        <?php if ($receipt_data['payment_method'] === 'بنكي'): ?>
            <tr><th>اسم البنك:</th><td><?php echo $receipt_data['bank_name']; ?></td></tr>
        <?php endif; ?>
        <tr><th>المدفوع:</th><td><?php echo $receipt_data['paid_amount']; ?></td></tr>
        <tr><th>المتبقي:</th><td><?php echo $receipt_data['remaining_amount']; ?></td></tr>
        <tr><th>تاريخ الدفع:</th><td><?php echo $receipt_data['payment_date']; ?></td></tr>
    </table>
    <button class="print-btn" onclick="window.print();">طباعة الوصل</button>
</body>
</html>
