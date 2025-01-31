<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Retrieve data from the POST request
$user_id = $_POST['user_id'];
$username = $_POST['username'];
$start_date = $_POST['start_date'] . " 00:00:00";
$end_date = $_POST['end_date'] . " 23:59:59";

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = 'index.php'; </script>";
    exit();
}

// Fetch Transactions
$transactions = [];
$payments = [];

$transactions_query = "
    SELECT 
        t.transaction_description, 
        t.amount, 
        t.transaction_type, 
        t.transaction_date, 
        t.bank_account_id,
        b.bank_name
    FROM 
        transactions t
    LEFT JOIN 
        bank_accounts b ON t.bank_account_id = b.account_id
    WHERE 
        t.user_id = ? 
        AND t.transaction_date BETWEEN ? AND ?
";

$stmt = $conn->prepare($transactions_query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$transactions_result = $stmt->get_result();
$transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch Payments
// $payments_query = "
//     SELECT paid_amount, payment_date 
//     FROM payments 
//     WHERE user_id = ? AND payment_date BETWEEN ? AND ?
// ";
// $stmt = $conn->prepare($payments_query);
// $stmt->bind_param("iss", $user_id, $start_date, $end_date);
// $stmt->execute();
// $payments_result = $stmt->get_result();
// $payments = $payments_result->fetch_all(MYSQLI_ASSOC);
// $stmt->close();
// Initialize totals
$total_addition_bank = 0;
$total_addition_amount = 0;
$total_subtraction_bank = 0;
$total_subtraction_amount = 0;

// Calculate totals
foreach ($transactions as $transaction) {
    if ($transaction['bank_account_id'] && $transaction['transaction_type'] == 'plus') {
        $total_addition_bank += $transaction['amount'];
    } elseif ($transaction['transaction_type'] == 'plus' && !$transaction['bank_account_id']) {
        $total_addition_amount += $transaction['amount'];
    } elseif ($transaction['transaction_type'] == 'minus' && $transaction['bank_account_id']) {
        $total_subtraction_bank += abs($transaction['amount']);
    } elseif ($transaction['transaction_type'] == 'minus' && !$transaction['bank_account_id']) {
        $total_subtraction_amount += abs($transaction['amount']);
    }
}

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نتائج اليومية</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            font-size: 30px;
            color: #333;
            letter-spacing: 2px;
        }
        .user-info {
            background-color: #e9f5f0;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th {
            background-color: #1BA078; /* Green */
            color: white; /* White text */
            font-size: 17px;
            padding: 12px;
        }
        td {
            padding: 10px;
            text-align: center;
        }
        tfoot {
            background-color: #f1f1f1; /* Light gray */
            color: black;
        }
        .print-button {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 23px;
            cursor: pointer;
            background-color: #1BA078; /* Green */
            color: white; /* White text */
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .print-button:hover {
            background-color: #14865b; /* Darker Green */
        }

        /* Print Styles */
        @media print {
            
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }
            h2 {
                color: black;
            }
            table {
                box-shadow: none;
            }
            th, td {
                border: 1px solid black;
                color: black; /* Black text */
            }
            th {
                background-color: white; /* White background for header */
                color: black; /* Black text for header */
            }
            tfoot {
                background-color: white; /* White background for footer */
                color: black; /* Black text for footer */
            }
            .print-button {
                display: none; /* Hide the button when printing */
            }
            img {
            display: block;
            margin: 0 auto; /* Center the image */
            width: 100%; /* Full width in print */
        }
        }
 
        .use-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        padding: 10px;
        }

        .use-info .date {
            letter-spacing: 2px; /* Adjust the spacing between letters */
        }

        .use-info span {
            margin: 0 10px;
        }



    </style>
    <script>
        function printTable() {
            window.print();
        }
    </script>
</head>
<body>
    <!-- Display User Information -->
    <div style="text-align: center;">
        <img src="../images/header.png" width="100%" alt="Header Image">
    </div>
    <h2>التقرير اليومي</h2>
    <div class="use-info">
    <span class="date">
        <strong>مـــــــن: </strong> <?php echo (new DateTime($start_date))->format('d/m/Y'); ?>
        <strong>إلــــــى:</strong> <?php echo (new DateTime($end_date))->format('d/m/Y'); ?>
    </span>

    <span class="date"><strong>اسم المستخدم:</strong> <?php echo htmlspecialchars($username); ?></span>
</div>





    <!-- Display Transactions -->
    <table>
        <thead>
            <tr>
                <th colspan="3">البنوك</th>
                <th colspan="2">الصندوق</th>
                <th rowspan="2">البيـــــــان</th>
            </tr>
            <tr>
                <th>سحب</th>
                <th>إيداع</th>
                <th>إسم البنك</th>
                <th>الصرف</th>
                <th>الدخل</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <?php if (!empty($transaction['transaction_description'])): ?>
                    <tr>
                        <td><?php echo ($transaction['transaction_type'] == 'minus' && $transaction['bank_account_id']) ? htmlspecialchars(abs($transaction['amount'])) : ''; ?></td>
                        <td><?php echo ($transaction['transaction_type'] == 'plus' && $transaction['bank_account_id']) ? htmlspecialchars($transaction['amount']) : ''; ?></td>
                        <td><?php echo htmlspecialchars($transaction['bank_name']); ?></td>
                        <td><?php echo ($transaction['transaction_type'] == 'minus' && !$transaction['bank_account_id']) ? htmlspecialchars(abs($transaction['amount'])) : ''; ?></td>
                        <td><?php echo ($transaction['transaction_type'] == 'plus' && !$transaction['bank_account_id']) ? htmlspecialchars($transaction['amount']) : ''; ?></td>
                        <td><?php echo htmlspecialchars($transaction['transaction_description']); ?></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td><?php echo htmlspecialchars($total_addition_bank); ?></td>
                <td></td>
                <td></td>
                <td><?php echo htmlspecialchars($total_addition_amount); ?></td>
                <td><strong>إجمالي إيداع</strong></td>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($total_subtraction_bank); ?></td>
                <td></td>
                <td></td>
                <td><?php echo htmlspecialchars($total_subtraction_amount); ?></td>
                <td></td>
                <td><strong>إجمالي الصرف</strong></td>
            </tr>
        </tfoot>
    </table>

    <button class="print-button" onclick="printTable()">طباعة</button>
</body>
</html>
