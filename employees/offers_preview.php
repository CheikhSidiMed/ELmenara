<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$accountNumber = isset($_GET['account_number']) ? $_GET['account_number'] : null;
$fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : null;
$toDate = isset($_GET['toDate']) ? $_GET['toDate'] : null;

if ($toDate) {
    $toDate = date('Y-m-d', strtotime($toDate . ' +1 day'));
}

$total_debit = 0;
$total_credit = 0;
$balance = 0;
$accountName = '';
$transactions = [];

if ($accountNumber) {
    $stmt = $conn->prepare("SELECT account_name FROM offer_accounts WHERE id = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $accountNumber);
    $stmt->execute();
    $stmt->bind_result($accountName);
    $stmt->fetch();
    $stmt->close();

    // Fetch transactions with date filter (if provided)
    $query = "SELECT transaction_date, type, transaction_description, amount, payment_method
          FROM offer_transactions
          WHERE account_id = ?
          ORDER BY transaction_date DESC";

    if ($fromDate && $toDate) {
        $query .= " AND transaction_date BETWEEN ? AND ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $accountNumber, $fromDate, $toDate);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $accountNumber);
    }
    
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    // Process transactions
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
        if ($row['type'] === 'plus') {
            $total_debit += $row['amount'];
        } elseif ($row['type'] === 'minus') {
            $total_credit += $row['amount'];
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة الحساب</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة الحساب</title>
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f9f9f9;
        }

        .main-container {
            padding-bottom: 30px;
            background-color: #ffffff;
            border-radius: 10px;
            max-width: 1100px;
        }

        h2 {
            font-size: 26px;
            color: #1BA078;
            font-weight: bold;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .form-control {
            border: 2px solid #1BA078;
            border-radius: 8px;
            padding: 10px;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #14865b;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        table {
            width: 100%;
            margin-top: 20px;
            background-color: #ffffff;
            border-collapse: collapse;
            border: 1px solid #ddd;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        thead th {
            background-color: #1BA078;
            color: white;
            padding: 15px;
            font-size: 18px;
            text-align: center;
            border-bottom: 2px solid #14865b;
        }

        tbody td {
            padding: 12px 15px;
            font-size: 16px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        tbody tr:hover {
            background-color: #f1f1f1;
        }

        .total-summary {
            background-color: #f4f4f4;
            font-weight: bold;
            padding: 20px;
            border: 1px solid #1BA078;
            border-radius: 8px;
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .summary-label {
            color: #1BA078;
            font-size: 18px;
            margin-right: auto;
        }

        .summary-value {
            font-size: 18px;
            padding: 5px 15px;
            color: #333;
            direction: ltr;
        }

        .summary-value span {
            font-weight: bold;
        }
        .accou {
            color: #000;
            font-size: 24px;
            margin: 3px;
            text-align: center;
            display: none;
        }
        .print-date {
            display: none;
        }
        @media print {
        /* Ensure no margin at the top */
        @page {
            size: A4;
            margin: 0;
        }
        .accou {
            display: block;
            margin-bottom: -20px 
        }
        button {
            display: none !important;
        }
        /* Remove any margin or padding from the body */
        body {
            margin-top: -220px !important;
            color: black !important;
            padding: 0;
        }

        /* Make only the .sheet container and its contents visible */
        .connt {
            visibility: hidden;
        }
        .sheet, .sheet * {
            visibility: visible;
        }

        /* Ensure the .sheet container covers the full width without margins */
        .sheet {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Style the header image to start at the very top */
        .sheet img {
            display: block;
            width: 100%;
            height: auto;
            max-width: 100%;
            margin: 0;
        }
        table {
            margin-top: -350px !important;
        }
        
        /* Styling for header text immediately below the image */
        .sheet-header {
            text-align: center;
            margin-top: -180px; /* Ensure no additional spacing */
            padding: 0;
        }
        .sheet-header h3, .sheet-header h5 {
            font-weight: bold;
            font-size: 16px;
            margin: 0;
            padding: 0;
        }

        /* Table adjustments for full-page width */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px; /* Optional spacing between header and table */
        }
        .table th, .table td {
            padding: 4px;
            border: 1px solid black;
            font-size: 12px;
            white-space: nowrap;
        }

            .print-date {
                display: block;
                text-align: left;
                margin-top: 1px; /* Optional spacing */
            }
        }

    </style>
</head>
</head>
<body>

<div class="container main-container">

<div class="connt">
    <!-- Home Button at the Top -->
    <div class="row mt-4">
        <div class="col text-start">
            <a href="home.php" class="btn btn-secondary me-2">
                <span>🏠</span> الصفحة الرئيسية
            </a>
        </div>
    </div>

    <div class="header-row">
        <h2 class="d-inline-block">معاينة الحساب</h2>
    </div>

    <div class="row mt-4">
        <div class="col-md-3">
            <label for="accountName" class="form-label">اسم الحساب:</label>
            <input type="text" class="form-control" id="accountName" value="<?= htmlspecialchars($accountName) ?>" readonly>
        </div>
        <div class="col-md-3">
            <label for="fromDate" class="form-label">من:</label>
            <input type="date" class="form-control" id="fromDate" value="<?= htmlspecialchars($fromDate) ?>">
        </div>
        <div class="col-md-3">
            <label for="toDate" class="form-label">إلى:</label>
            <input type="date" class="form-control" id="toDate" value="<?= htmlspecialchars($toDate) ?>">
        </div>
        <div class="col-md-3 mt-2 update-button">
        <label class="form-label"></label>
            <button class="btn btn-primary w-100" onclick="applyDateFilter()">تحديث</button>
        </div>
    </div>

</div>
    <div class="sheet-header receipt-header">
            <img src="../images/header.png" width="100%" alt="Header Image">
            <h2 class="accou" >معاينة الحساب <string style="color: blue;"> : <?php echo $accountName ?></string></h2>
            <p class="print-date">التاريخ : <?php echo date('d/m/Y'); ?></p>
        </div>

        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>بيان العملية</th>
                    <th>مدين</th>
                    <th>دائن</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($transactions as $transaction):
                ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction['transaction_date']) ?></td>
                        <td><?= htmlspecialchars($transaction['transaction_description']) ?></td>
                        <td><?= $transaction['type'] === 'minus' ? number_format($runningBalance, 2) : '' ?></td>
                        <td><?= $transaction['type'] === 'plus' ? number_format($runningBalance, 2) : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Total Summary -->
        <div class="total-summary">
            <div class="col text-end">
                <!-- Print Button -->
                <button class="btn btn-print me-2" onclick="printTable()">
                    <span>🖨️</span> طباعة
                </button>
            </div>
            <div class="summary-value"> مجموع العمليات </div>
            <div class="summary-value">مدين: <?php echo number_format($total_credit, 2); ?></div>
            <div class="summary-value">دائن: <?php echo number_format($total_debit, 2); ?></div>
            <div class="summary-value">الرصيد: <?php echo number_format($total_credit - $total_debit, 2); ?></div>
    <!-- </div> -->
        </div>
        
    </div>


    <script>
function applyDateFilter() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const urlParams = new URLSearchParams(window.location.search);

    urlParams.set('fromDate', fromDate);
    urlParams.set('toDate', toDate);

    window.location.search = urlParams.toString();
}

function printTable() {
    window.print();
}
</script>

<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>