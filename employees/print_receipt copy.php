<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

$receipt_id = $_GET['receipt_id'] ?? null;
$receipt = [];
$payments = [];

if ($receipt_id) {
    $stmt = $conn->prepare("SELECT 
            r.receipt_id,
            u.username,
            r.total_amount, 
            r.receipt_date,
            r.receipt_description, 
            COALESCE(a.agent_name, 'بدون وكيل') AS agent_name,           
            s.student_name, 
            SUM(c.paid_amount) AS TOT, 
            SUM(COALESCE(c.remaining_amount, 0)) AS remaining_amounts,
            GROUP_CONCAT(c.month SEPARATOR ', ') AS months, 
            c.date AS transaction_date, 
            COALESCE(c.description, 'دفع رسوم اشهر ') AS transaction_descriptions
        FROM receipts AS r
        LEFT JOIN students AS s ON s.id = r.student_id
        LEFT JOIN agents AS a ON a.agent_id = s.agent_id
        LEFT JOIN receipt_payments AS rp ON r.receipt_id = rp.receipt_id
        LEFT JOIN combined_transactions AS c ON rp.transaction_id = c.id
        LEFT JOIN users AS u ON u.id = c.user_id
        WHERE r.receipt_id = ? 
        GROUP BY r.receipt_id, c.description;

    ");
    $stmt->bind_param('i', $receipt_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Separate receipt data from payments to avoid overwriting
    $firstRow = $result->fetch_assoc();
    if ($firstRow) {
        $receipt = $firstRow; // Only the first row for receipt details
        $payments[] = $firstRow; // Add first row as payment if exists
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الإيصال</title>
    <style>
        .receipt-container {
            width: 21cm;
            padding: 20px;
            border: 1px solid #ccc;
            margin: auto;
        }
        h2, h3 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .receipt-header img {
            width: 100%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 0px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .no-print {
            margin-top: 20px;
            text-align: center;
        }
        .print-button button {
            padding: 10px 20px;
            font-size: 16px;
        }
        @media print {
            @page {
                size: A5 landscape;
                margin: 0;
            }
            .no-print {
                display: none;
            }
            table {
            width: 50%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .receipt-header img {
            width: 50%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 0px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 2px;
            text-align: center;
        }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <img src="../images/header.png" alt="Header Image" title="تفاصيل الإيصال">
        </div>

        <h2>تفاصيل الإيصال</h2>
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 80px;">
            <p><strong>رقم الإيصال:</strong> <?php echo htmlspecialchars('00000' . $receipt['receipt_id']); ?></p>
            <p><strong> الطالب:</strong> <?php echo htmlspecialchars($receipt['student_name']?? ''); ?></p>
            <?php echo isset($receipt['agent_name']) ? "<p><strong> الوكيل:</strong> " . htmlspecialchars($receipt['agent_name']) . "</p>" : ''; ?>

        </div>

        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 80px;">
            <p><strong>المبلغ الكلي:</strong> <?php echo htmlspecialchars($receipt['total_amount']); ?></p>
            <p><strong>تاريخ الإيصال:</strong> <?php echo htmlspecialchars($receipt['receipt_date'] ?? 'غير متوفر'); ?></p>
            <p><strong>المستخدم :</strong> <?php echo htmlspecialchars($receipt['username'] ?? ''); ?></p>
        </div>

        <h3>المدفوعات</h3>
        <table>
            <tr>
                <th>المبلغ المتبقي</th>
                <th>المبلغ المدفوع</th>
                <th>الشهر</th>
                <th>الوصف</th>
            </tr>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($payment['remaining_amounts'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($payment['TOT'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($payment['months'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($payment['transaction_descriptions'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="print-button no-print">
            <button onclick="window.print()">طباعة</button>
        </div>
    </div>
</body>
</html>
