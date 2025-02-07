<?php
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}

$user_id = $_SESSION['userid'];
$role_id = $_SESSION['role_id'];

// Initialize variables
$transactions = [];
$account_name = '';
$balance = 0;
$total_debit = 0;
$total_credit = 0;
$running_balance = 0;

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = $result && $result->num_rows > 0 ? $result->fetch_assoc()['year_name'] : "";

$fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : null;
$toDate = isset($_GET['toDate']) ? $_GET['toDate'] : null;

// Modify the $toDate to include the entire day by adding one day to the end date
if ($toDate) {
    $toDate = date('Y-m-d', strtotime($toDate . ' +1 day'));
}

$cond = ($role_id === 1) ? "1=1" : "user_id = $user_id";

// Déterminer si la requête concerne un fond ou une banque
if (isset($_GET['fund_id']) || isset($_GET['bank_id'])) {
    $isFund = isset($_GET['fund_id']);
    $account_id = $isFund ? $_GET['fund_id'] : $_GET['bank_id'];
    $account_table = $isFund ? "funds" : "bank_accounts";
    $account_column = $isFund ? "fund_name" : "bank_name";
    $account_id_column = $isFund ? "id" : "account_id";
    $payment_method = $isFund ? "نقدي" : "بنكي";
    $transaction_account_column = $isFund ? "fund_id" : "bank_id";

    // Récupérer le nom du compte et le solde
    $stmt = $conn->prepare("SELECT $account_column, balance FROM $account_table WHERE $account_id_column = ?");
    $stmt->bind_param('i', $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    $account_name = $account[$account_column];
    $balance = $account['balance'];

    // Déterminer si les filtres de date sont définis
    $dateFilter = ($fromDate && $toDate) ? "AND (date BETWEEN '$fromDate' AND '$toDate')" : "";

    // Requête pour récupérer les transactions
    $query = "
        SELECT description AS transaction_description, paid_amount AS amount, type AS transaction_type, DATE_FORMAT(date, '%Y-%m-%d') as transaction_date
        FROM combined_transactions
        WHERE $transaction_account_column = ? AND ($cond) $dateFilter
        ORDER BY date DESC
    ";

    // Préparer la requête SQL
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $account_id);

    // $dateFilter = ($fromDate && $toDate) ? "AND (transaction_date BETWEEN '$fromDate' AND '$toDate')" : "";

    // // Requête pour récupérer les transactions
    // $query = "
    //     SELECT transaction_description, amount, transaction_type, DATE_FORMAT(transaction_date, '%Y-%m-%d') as transaction_date
    //     FROM transactions
    //     WHERE $transaction_account_column = ? AND ($cond) $dateFilter
    //     UNION
    //     SELECT transaction_description, amount, 'minus' as transaction_type, DATE_FORMAT(transaction_date, '%Y-%m-%d') as transaction_date
    //     FROM expense_transaction
    //     WHERE payment_method = ? $dateFilter
    //     ORDER BY transaction_date DESC
    // ";

    // // Préparer la requête SQL
    // $stmt = $conn->prepare($query);
    // $stmt->bind_param('is', $account_id, $payment_method);
}

// Execute the statement and fetch the transactions
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Calculate running balance for each row
    if ($row['transaction_type'] === 'plus') {
        $running_balance += $row['amount'];
        $total_debit += $row['amount'];
    } elseif ($row['transaction_type'] === 'minus') {
        $running_balance -= $row['amount'];
        $total_credit += $row['amount'];
    }

    $row['running_balance'] = $running_balance; // Store the calculated running balance in the row
    $transactions[] = $row;
}

// Calculate the balance as total debit minus total credit
$calculated_balance = $total_debit - $total_credit;

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة الحساب</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/amiri.css">

    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            background-color: #f9f9f9;
            display: flex; /* Make the body a flex container */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            margin: 10px; /* Remove default margins */
            text-align: center; /* Center text inside inline elements */
        }

        .sheet-header {
                text-align: center;
                margin-bottom: 15px; /* Reduced margin */
            }
            .sheet-header p {
                display: inline-block;
                margin: 0 10px; /* Adjusts space between the items */
                font-size: 14px; /* Adjust font size as needed */
            }
            .sheet-header img {
                width: 100%;
                max-width: 500px; /* Adjusted width */
                height: auto;
            }
        .main-container {
            margin: 10px;
            border: 1px solid #1BA078;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            background-color: white;
        }
        .form-control, .form-select {
            border: 1px solid #1BA078;
            border-radius: 5px;
            padding: 8px 12px;
            color: #333;
        }
        .btn-primary {
            background-color: #1BA078;
            border-color: #1BA078;
            color: white;
        }
        .table thead th {
            background-color: #1BA078;
            color: white;
            text-align: center;
        }
        .table tbody td {
            text-align: center;
            direction: ltr;
        }
        .total-summary {
            background-color: #f1f1f1;
            font-weight: bold;
            padding: 15px;
            border: 1px solid #1BA078;
            border-radius: 10px;
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        .total-summary div {
            text-align: center;
            padding: 0 10px;
            direction: ltr;
        }
        .summary-label {
            flex: 4;
            direction: rtl;
        }
        .summary-value {
            flex: 1;
        }
        @media print {
            /* Définir les marges de la page */
            @page {
                size: A4;
                margin: 0;
            }

            /* Remettre à zéro les marges et paddings du body */
            body {
                margin: 20px;
                padding: 0;
                color: black !important;
                background: none !important;
            }
            .container {
                margin-top: -15px;
                color: black !important;
                width: 100%;
                max-width: 100% !important; 

            }
            /* Masquer tout sauf le contenu spécifique */
            .conta, .brb{
                display: none;
            }

            .sheet, .sheet * {
                visibility: visible;
            }

            .sheet {
                width: 100%;
                margin: 0;
                padding: 0;
                position: relative;
                top: 0; /* S'assurer que la feuille commence au sommet */
            }

            /* En-tête de la feuille */
            .sheet-header {
                text-align: center;
                margin: 0;
                padding: 0;
            }

            .sheet-header h3, .sheet-header h5 {
                font-weight: bold;
                font-size: 16px;
                margin: 0;
                padding: 0;
            }

            /* Images dans l'en-tête */
            .sheet img {
                display: block;
                width: 100%;
                height: auto;
                margin: 0;
            }

            /* Table d'impression */
            .table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            .table th, .table td {
                color: black !important;
                padding: 4px;
                border: 1px solid black;
                font-size: 12px;
                text-align: center;
                white-space: nowrap;
            }

            /* Date d'impression */
            .print-date {
                text-align: right;
                margin-right: 10px;
                font-size: 12px;
                margin-top: 5px;
            }
        }


    </style>
</head>
<body>

<div>
    <div class="container conta">
    <div class="row align-items-center justify-content-between mt-4">
    <!-- Left-aligned content -->
        <div class="col-auto">
            <a href="home.php" class="btn btn-secondary me-2">
                <span>🏠</span> الصفحة الرئيسية
            </a>
        </div>

        <!-- Right-aligned content -->
        <div class="col-auto text-end">
            <h5>السنة المالية</h5>
            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo htmlspecialchars($last_year); ?>
            </button>
        </div>
    </div>


        <div class="header-row">
            <div>
                <h2 class="d-inline-block">معاينة <?php echo $account_name; ?></h2>
            </div>
            
        </div>

        <div class="row mt-4">
            <div class="col-md-3">
                <label for="accountName" class="form-label">اسم الحساب:</label>
                <input type="text" class="form-control" id="accountName" value="<?php echo $account_name; ?>" readonly>
            </div>
            <div class="col-md-3">
                <label for="fromDate" class="form-label">من:</label>
                <input type="date" class="form-control" id="fromDate" value="<?php echo isset($fromDate) ? date('Y-m-d', strtotime($fromDate . ' -1 day')) : ''; ?>">
            </div>
            <div class="col-md-3">
                <label for="toDate" class="form-label">إلى:</label>
                <input type="date" class="form-control" id="toDate" value="<?php echo isset($toDate) ? date('Y-m-d', strtotime($toDate . ' -1 day')) : ''; ?>">
            </div>
            <div class="col-md-3 update-button">
            <label class="form-label">.</label>

                <button class="btn btn-primary w-100" onclick="applyDateFilter()">تحديث</button>
            </div>
        </div>
    </div>   

    <div class="container main-container">
        <div class="sheet">
            <img src="../images/header.png" width="100%" alt="Header Image">
            <div class="sheet-header">
                <h3>الحساب: <?php echo $account_name ?? ''; ?> </h3>
            <h5>من: <?php echo $fromDate ?? ''; ?> / إلى: <?php echo $toDate ?? ''; ?> </h5>
            <h6  class="print-date">بتاريخ : <?php echo date('Y-m-d'); ?></h6> <!-- Print date only visible during print -->

        </div>
        <table class="table mt-4">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>بيان العملية</th>
                    <th>مدين</th>
                    <th>دائن</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo $transaction['transaction_date']; ?></td>
                    <td><?php echo $transaction['transaction_description']; ?></td>
                    <td><?php echo $transaction['transaction_type'] === 'plus' ? number_format($transaction['amount']) : '0'; ?></td>
                    <td><?php echo $transaction['transaction_type'] === 'minus' ? number_format($transaction['amount']) : '0'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Total Summary -->
        <div class="total-summary">
            <div class="summary-value"> مجموع العمليات </div>
            <div class="summary-value">مدين: <?php echo number_format($total_debit); ?></div>
            <div class="summary-value">دائن: <?php echo number_format($total_credit); ?></div>
            <div class="summary-value">الرصيد: <?php echo number_format($calculated_balance); ?></div>
        </div>

        <div class="row mt-4 button brb">
            <div class="col  text-end button">
                <!-- Print Button -->
                <button class="btn btn-print me-2" onclick="printPage()">
                    <span>🖨️</span> طباعة الصفحة
                </button>
            </div>
        </div>
    </div>
</div>


    <script>
    function printPage() {
        window.print();
    }
    </script>

    <script>
    function applyDateFilter() {
        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        const urlParams = new URLSearchParams(window.location.search);

        urlParams.set('fromDate', fromDate);
        urlParams.set('toDate', toDate);

        window.location.search = urlParams.toString();
    }
    </script>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>


</body>
</html>
