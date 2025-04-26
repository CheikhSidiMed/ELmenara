<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


include 'db_connection.php';
    
$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}


$name_connect = '';

// Fetch payment IDs from URL parameters
$student_id = isset($_POST['student_id']) ? $_POST['student_id']: '';


$receipt_data = [];
$receipt_data_rest = [];
$student_name = '';
$agent_phone = '';
$bank_name = '';
$resptId = '';
$payment_id = '';
$remaining_amount = 0;
$total_paid_sum = 0;
$total_paid_sum_rest = 0;
$total_paid_rest = 0;
$student_remaining_sum = 0;

if (!empty($student_id)) {
    $sql = "SELECT
            r.receipt_id AS payment_id,
            s.phone,
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
            c.description AS transaction_descriptions
        FROM
            receipts r
        LEFT JOIN
            receipt_payments AS rp ON r.receipt_id = rp.receipt_id
        LEFT JOIN
            combined_transactions AS c ON rp.transaction_id = c.id
        LEFT JOIN
            students s ON c.student_id = s.id
        LEFT JOIN
            classes cl ON s.class_id = cl.class_id
        LEFT JOIN
            users u ON u.id = r.created_by
        LEFT JOIN
            bank_accounts b ON c.bank_id = b.account_id
                INNER JOIN (
        SELECT
            MAX(r2.receipt_id) AS max_receipt_id,
            c2.student_id
        FROM
            receipts r2
        LEFT JOIN
            receipt_payments rp2 ON r2.receipt_id = rp2.receipt_id
        LEFT JOIN
            combined_transactions c2 ON rp2.transaction_id = c2.id
        WHERE
            c2.description NOT LIKE 'تم إلغاء%'
            AND r2.student_id LIKE ?
        GROUP BY
            c2.student_id
    ) latest ON latest.max_receipt_id = r.receipt_id
        WHERE
            c.description NOT LIKE 'تم إلغاء%' AND r.student_id LIKE ?
        GROUP BY
            r.receipt_id, c.description;";

    $stmt = $conn->prepare($sql);

    // Check for statement errors
    if ($stmt === false) {
        die('Error preparing the statement: ' . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param('ii', $student_id, $student_id);

    // Execute and fetch results
    $stmt->execute();
    $result = $stmt->get_result();

    
    while ($row = $result->fetch_assoc()) {
        $receipt_data[] = $row;
        $resptId = $row['payment_id'];
        $student_name = $row['student_name'];
        $receipt_date = $row['receipt_date'];
        $name_connect = $row['created_by'];
        $agent_phone = $row['phone'];
        $bank_name = $row['bank_name'];
        $total_paid_sum += $row['total_paid'];
    }
    foreach ($receipt_data as $row) {
        if (!empty($row['months_paid']) && is_string($row['months_paid']) && !empty($row['student_remaining'])) {
            $monthsArray = explode(', ', $row['months_paid']);
            $student_remaining = (float)$row['student_remaining'];
            $remaining_amount += (float)$row['remaining_amount'];
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
    <link rel="stylesheet" href="css/receipt.css">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">

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
                <strong>وصل رقم:</strong> <?php echo sprintf("%010d", $resptId); ?>
            </div>
            <div>
                <strong>بتاريخ:</strong> <?php
                    $formatted_date = date('Y-m-d', strtotime($receipt_date));
                    $formatted_time = date('H:i:s', strtotime($receipt_date));
                    echo $formatted_date . ' | ' . $formatted_time;
                    ?>
            </div>
            <div>
                <strong>المستخدم:</strong> <?php echo $name_connect; ?>
            </div>
            <div>
                <strong>السنة الدراسية:</strong> <?php echo $last_year; ?>
            </div>
        </div>
        
        <div class="info-container">
            <div><strong>اسم الطالب(ة):</strong> <?php echo $student_name; ?></div>
            <div><strong>رقم الهاتف:</strong> <?php echo $agent_phone; ?></div>
            <div><strong>رقم التعريف:</strong> <?php echo $student_id; ?></div>
        </div>

        <!-- Table for student data -->
        <table class="table text-center">
            <thead>
                <tr>
                    <th>القسم</th>
                    <th>المدفوعات </th>
                    <th>المبلغ </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receipt_data as $data): ?>
                <tr>
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
                <span>حساب الدفع</span> : 
                <span class="text-primary">
                    <?php echo empty($bank_name) ? 'نقدي' : $bank_name; ?>
                </span>
            </div>
            <?php if (!empty($student_remaining_sum)) : ?>
            <div>
                <span>مجموع الرسوم</span> : <?php echo $student_remaining_sum; ?>
            </div>
            <?php endif; ?>
            <div>
                <span>المبلغ الإجمالي المدفوع</span> : <?php echo $total_paid_sum; ?>
            </div>
            <?php if (!empty($remaining_amount)) : ?>
            <div>
                <span>المبلغ المتبقي</span> : <?php echo $remaining_amount; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Print Button -->
        <div class="text-center mt-4">
            <button class="btn btn-success print-button" onclick="window.print()">طباعة</button>
        </div>
    </div>
</div>
</body>
</html>



