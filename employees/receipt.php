<?php

// Include database connection
include 'db_connection.php';



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
    <style>
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
