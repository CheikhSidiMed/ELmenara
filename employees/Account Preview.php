<?php
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: home.php");
    exit;
}

$user_id = $_SESSION['userid'];
$role_id = $_SESSION['role_id'];

// Initialize variables
$transactions    = [];
$account_name    = '';
$balance         = 0;
$total_debit     = 0;
$total_credit    = 0;
$running_balance = 0;

// Récupérer la dernière année académique
$sql  = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);
$last_year = $result && $result->num_rows > 0
    ? $result->fetch_assoc()['year_name']
    : "";
// Dates SQL (format pour requête)
$sqlFromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : null;
$sqlToDate = isset($_GET['toDate']) ? $_GET['toDate'] : null;
$sqlFromD = $sqlFromDate . " 00:00:00";
$sqlToD = $sqlToDate . " 23:59:59";

// Préparer le filtre de date
$dateFilter = '';
if ($sqlFromDate && $sqlToDate) {
    $dateFilter = "AND r.receipt_date BETWEEN ? AND ?";
}
$cond = ($role_id === 1) ? "1=1" : "ct.user_id = $user_id";

// Déterminer si on filtre par fund ou banque
if (isset($_GET['fund_id']) || isset($_GET['bank_id'])) {
    $isFund = isset($_GET['fund_id']);
    $account_id = $isFund ? $_GET['fund_id'] : $_GET['bank_id'];
    $account_table = $isFund ? "funds" : "bank_accounts";
    $account_column = $isFund ? "fund_name" : "bank_name";
    $account_id_column = $isFund ? "id" : "account_id";
    $transaction_account_column = $isFund ? "ct.payment_method='نقدي' AND ct.fund_id " : "ct.payment_method='بنكي' AND ct.bank_id";

    // Récupération du nom de compte et solde
    $stmt = $conn->prepare("SELECT $account_column, balance FROM $account_table WHERE $account_id_column = ?");
    $stmt->bind_param('i', $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    $account_name = $account[$account_column];
    $balance = $account['balance'];

    // Requête SQL optimisée
    $query = "
        SELECT
            r.receipt_description AS transaction_description,
            SUM(ct.paid_amount) AS amount,
            ct.type AS transaction_type,
            r.receipt_date AS transaction_date
        FROM
            receipts r
        LEFT JOIN
            receipt_payments rp ON rp.receipt_id = r.receipt_id
        LEFT JOIN
            combined_transactions ct ON ct.id = rp.transaction_id
            AND $transaction_account_column = ?
        WHERE
            $cond
            $dateFilter
        GROUP BY
            r.receipt_id, ct.type, r.receipt_description, r.receipt_date
        ORDER BY
            r.receipt_date DESC
    ";

    // Préparation
    $stmt = $conn->prepare($query);

    // Bind dynamique selon filtre date
    if ($sqlFromDate && $sqlToDate) {
        $stmt->bind_param('iss', $account_id, $sqlFromD, $sqlToD);
    } else {
        $stmt->bind_param('i', $account_id);
    }

    $stmt->execute();
    // $transactions = $stmt->get_result();
}

// Exécuter et récupérer les transactions
// $stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['transaction_type'] === 'plus') {
        $running_balance += $row['amount'];
        $total_debit    += $row['amount'];
    } elseif ($row['transaction_type'] === 'minus') {
        $running_balance -= $row['amount'];
        $total_credit   += $row['amount'];
    }
    $row['running_balance'] = $running_balance;
    $transactions[] = $row;
}

// Calcul du solde final
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
        :root {
            --primary-color: #1BA078;
            --primary-light: #E8F5F0;
            --dark-color: #2D3748;
            --light-color: #F8FAFC;
            --border-color: #E2E8F0;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            background-color: var(--light-color);
            margin: 0;
            padding: 20px;
            color: var(--dark-color);
        }

        .container-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Navigation */
        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .btn-nav {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-nav:hover {
            background: #168a6d;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-print {
            background: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: var(--dark-color);
        }

        .form-control, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: border 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(27, 160, 120, 0.2);
        }

        /* Main Content */
        .document-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .document-header {
            text-align: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .document-header img {
            max-width: 100%;
            height: auto;
            margin-bottom: 1rem;
        }

        .document-title {
            color: var(--primary-color);
            margin: 0.5rem 0;
        }

        .document-subtitle {
            color: var(--dark-color);
            margin: 0.3rem 0;
        }

        /* Table Styles */
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transaction-table thead th {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
        }

        .transaction-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }

        .transaction-table tbody tr:last-child td {
            border-bottom: none;
        }

        .transaction-table tbody tr:hover {
            background: var(--primary-light);
        }

        /* Summary Section */
        .summary-section {
            display: flex;
            justify-content: space-between;
            background: var(--primary-light);
            padding: 1.5rem;
            border-radius: 0 0 12px 12px;
            margin-top: -1px;
        }

        .summary-item {
            text-align: center;
            padding: 0 1rem;
        }

        .summary-label {
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .summary-value {
            font-size: 1.1rem;
            color: var(--primary-color);
            font-weight: bold;
        }

        /* Print Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: none;
            }

            .nav-header, .filter-section, .print-button, h1 {
                display: none;
            }

            .document-container {
                box-shadow: none;
                border-radius: 0;
            }

            .transaction-table th, .transaction-table td {
                padding: 0.5rem;
                font-size: 0.9rem;
            }

            @page {
                size: A4;
                margin: 1cm;
            }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .summary-section {
                flex-direction: column;
                gap: 1rem;
            }

            .transaction-table thead th, 
            .transaction-table tbody td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <!-- Navigation Header -->
        <div class="nav-header conta">
            <div>
                <a href="home.php" class="btn-nav">
                    <i class="fas fa-home"></i> الصفحة الرئيسية
                </a>
            </div>
            <div>
                <h5>السنة المالية</h5>
                <button class="btn-nav">
                    <?php echo htmlspecialchars($last_year); ?>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>

        <!-- Page Title -->
        <h1 class="mb-4" style="color: var(--primary-color);">معاينة <?php echo htmlspecialchars($account_name); ?></h1>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-grid">
                <div>
                    <label for="accountName" class="form-label">اسم الحساب:</label>
                    <input type="text" class="form-control" id="accountName" value="<?php echo htmlspecialchars($account_name); ?>" readonly>
                </div>
                <div>
                    <label for="fromDate" class="form-label">من:</label>
                    <input type="date" class="form-control" id="fromDate" value="<?php echo htmlspecialchars($sqlFromDate); ?>">
                </div>
                <div>
                    <label for="toDate" class="form-label">إلى:</label>
                    <input type="date" class="form-control" id="toDate" value="<?php echo htmlspecialchars($sqlToDate); ?>">
                </div>
                <div class="d-flex align-items-end">
                    <button class="btn-nav w-100" onclick="applyDateFilter()">
                        <i class="fas fa-sync-alt"></i> تحديث البيانات
                    </button>
                </div>
            </div>
        </div>

        <!-- Document Container -->
        <div class="document-container sheet">
            <!-- Document Header -->
            <div class="document-header">
                <img src="../images/header.png" alt="Company Logo">
                <h3 class="document-title">الحساب: <?php echo htmlspecialchars($account_name); ?></h3>
                <h5 class="document-subtitle">
                    من: <?php echo htmlspecialchars($sqlFromDate ?? ''); ?> /
                    إلى: <?php echo htmlspecialchars($sqlToDate ?? ''); ?>
                </h5>
                <p class="print-date">بتاريخ: <?php echo date('Y-m-d'); ?></p>
            </div>

            <!-- Transactions Table -->
            <div style="overflow-x: auto;">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">التاريخ</th>
                            <th>بيان العملية</th>
                            <th>مدين</th>
                            <th>دائن</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo $transaction['transaction_date']; ?></td>
                            <td><?php echo htmlspecialchars($transaction['transaction_description']); ?></td>
                            <td><?php echo $transaction['transaction_type'] === 'plus' ? number_format($transaction['amount']) : '0'; ?></td>
                            <td><?php echo $transaction['transaction_type'] === 'minus' ? number_format($transaction['amount']) : '0'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
                <div class="summary-item">
                    <div class="summary-label">الرصيد</div>
                    <div class="summary-value"><?php echo number_format($calculated_balance); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">دائن</div>
                    <div class="summary-value"><?php echo number_format($total_credit); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">مدين</div>
                    <div class="summary-value"><?php echo number_format($total_debit); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">مجموع العمليات</div>
                    <div class="summary-value"><?php echo count($transactions); ?></div>
                </div>
            </div>
        </div>

        <!-- Print Button -->
        <div class="text-end mt-4 print-button brb">
            <button class="btn-print" onclick="printPage()">
                <i class="fas fa-print"></i> طباعة الصفحة
            </button>
        </div>
    </div>
<script>
function printPage() {
    window.print();
}

function applyDateFilter() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate   = document.getElementById('toDate').value;
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
