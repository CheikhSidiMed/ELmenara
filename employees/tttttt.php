<?php
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

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = 'index.php'; </script>";
    exit();
}
    $role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;
    $con_user_id = isset($_SESSION['UR_id']) ? $_SESSION['UR_id'] : null;
    $username_con = isset($_SESSION['username']) ? $_SESSION['username'] : null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['user_id'] = $_POST['user_id'] ?? '';
    $_SESSION['start_date'] = $_POST['start_date'];
    $_SESSION['end_date'] = $_POST['end_date'];
}

// Retrieve previous values or set default values
$selectedUser = $_SESSION['user_id'] ?? '';
$selectedStartDate = $_SESSION['start_date'] ?? '';
$selectedEndDate = $_SESSION['end_date'] ?? '';

// Fetch all users for the dropdown
$query = "SELECT id, username FROM users";
$result = $conn->query($query);


$user_id = $role_id == 1 ? $_POST['user_id'] ?? $con_user_id : $con_user_id;

$currentDate = date('Y-m-d');


$username = $role_id == 1 ? $_POST['username'] ?? $username_con : $username_con;
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] . " 00:00:00" : $currentDate . " 00:00:00";
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] . " 23:59:59" : $currentDate . " 23:59:59";


if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = 'index.php'; </script>";
    exit();
}

$transactions = [];
$payments = [];
$transactions = [];



if (!empty($_POST['user_id']) && $_POST['user_id'] !== 'all') {
    $transactions_query = "SELECT
        r.receipt_id,
        r.receipt_description AS transaction_description,
        sum(ct.paid_amount) AS amount,
        ct.type AS transaction_type,
        r.receipt_date AS transaction_date,
        ct.bank_id AS bank_account_id,
        b.bank_name
    FROM
        receipts r
    JOIN receipt_payments AS rp ON rp.receipt_id = r.receipt_id
    LEFT JOIN combined_transactions AS ct ON ct.id = rp.transaction_id
    LEFT JOIN bank_accounts b ON ct.bank_id = b.account_id
    WHERE
        ct.user_id = ?
        AND r.receipt_date BETWEEN ? AND ?
        GROUP BY r.receipt_id, ct.type
    ORDER BY
        r.receipt_date DESC";

    $stmt = $conn->prepare($transactions_query);
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
} elseif(!empty($_POST['user_id']) && $_POST['user_id'] === 'all'){
    $t_query = "SELECT
        r.receipt_id,
        r.receipt_description AS transaction_description,
        sum(ct.paid_amount) AS amount,
        ct.type AS transaction_type,
        r.receipt_date AS transaction_date,
        ct.bank_id AS bank_account_id,
        b.bank_name
    FROM
        receipts r
    JOIN receipt_payments AS rp ON rp.receipt_id = r.receipt_id
    LEFT JOIN combined_transactions AS ct ON ct.id = rp.transaction_id
    LEFT JOIN bank_accounts b ON ct.bank_id = b.account_id
    WHERE
        r.receipt_date BETWEEN ? AND ?
        GROUP BY r.receipt_id, ct.type
    ORDER BY
        r.receipt_date DESC;";


    $stmt = $conn->prepare($t_query);
    $stmt->bind_param("ss", $start_date, $end_date);
} else {
    $t_query = "SELECT
        r.receipt_id,
        r.receipt_description AS transaction_description,
        sum(ct.paid_amount) AS amount,
        ct.type AS transaction_type,
        r.receipt_date AS transaction_date,
        ct.bank_id AS bank_account_id,
        b.bank_name
    FROM
        receipts r
    JOIN receipt_payments AS rp ON rp.receipt_id = r.receipt_id
    LEFT JOIN combined_transactions AS ct ON ct.id = rp.transaction_id
    LEFT JOIN bank_accounts b ON ct.bank_id = b.account_id
    WHERE ct.user_id = ?
        AND
            r.receipt_date BETWEEN ? AND ?
        GROUP BY r.receipt_id, ct.type
    ORDER BY
        r.receipt_date DESC;";


    $stmt = $conn->prepare($t_query);
    $stmt->bind_param("iss", $con_user_id, $start_date, $end_date);
}

$stmt->execute();
$transactions_result = $stmt->get_result();
$transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اليومية</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f6;
            padding: 20px;
        }

        .main-container {
            margin: 10px auto;
            padding: 30px;
            border-radius: 12px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            text-align: center; /* Center align the content */
        }

        .header-title {
            font-size: 28px;
            font-weight: bold;
            color: #1BA078;
            display: flex;
            align-items: start;
            justify-content: start; /* Center the title */
            gap: 10px;
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }

        .form-group {
            flex: 1; /* Allow each form group to take equal space */
            max-width: 230px; /* Uniform width for each form group */
        }

        .form-group label {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            display: inline-block;
            width: 100%; /* Ensure label aligns properly */
        }

        .form-select, .form-date, button {
            width: 100%; /* Full width for inputs and button */
            border-radius: 10px;
            padding: 8px;
            border: 2px solid #1BA078;
            font-family: 'Amiri', serif;
            transition: all 0.3s ease-in-out;
            box-sizing: border-box;
        }

        .form-select:focus, .form-date:focus, button:focus {
            border-color: #14865b;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            outline: none;
        }

        button {
            background-color: #1BA078;
            color: white;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 26px;
            transition: all 0.3s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        button:hover {
            background-color: #14865b;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        .user-info {
            background-color: #e9f5f0;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 10px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 1px;
            box-shadow: 0 2px 1 rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th {
            background-color: #1BA078; /* Green */
            color: white; /* White text */
            font-size: 17px;
            padding: 4px;
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
            background-color: #14865b;
        }
        .table-footer {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: right;
        }

        img{
            border-radius: 10px;
            border: 1px solid #14865b;
        }
        .signatures {
        margin-top: 10px;
        text-align: right;
            }
            .signature-row {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 200px;
            }
            td:first-child {
                text-align: right;
                padding-right: 5px; /* Add padding for a slight indent */
            }
            td:not(:first-child) {
                text-align: center;
            }
                /* Print Styles */
        @media print {
            @page {
                size: A4;
            }
            body {
                background-color: white;
                margin: 0;
                padding: 0;
                font-size: 15px; /* Minimize font size */
            }
            h2 {
                color: black;
                font-size: 15px; /* Smaller font size for headings */
            }
            table {
                box-shadow: none;
                font-size: 12px; /* Minimize font size in tables */
            }
            .table-footer {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: right;
            }
            th, td {
                border: 1px solid black;
                color: black;
                padding: 2px;
            }
            td:first-child {
                text-align: right;
                padding-right: 3px;
            }
            
            td:not(:first-child) {
                text-align: center;
            }
            th {
                background-color: white;
                color: black;
                font-size: 14px;
            }
            tfoot {
                background-color: white;
                color: black;
            }
            .print-button, .main-container {
                display: none;
            }
            img {
                border: none;
                display: block;
                margin: 0 auto;
                width: 100%;
            }
            .tbl{
                padding-left: 50px;
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
        .tbl {
            overflow-x: auto;
            padding: 5px;
            width: 100%;
        }
        table {
            min-width: 700px;
            border-collapse: collapse;
        }
        .main-container {
            padding: 15px;
        }

        .header-title {
            font-size: 2.3rem;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap; /* Permet aux éléments de passer en colonne sur petits écrans */
            gap: 10px;
            justify-content: center;
        }

        .form-group {
            flex: 1; /* Permet d'étirer chaque champ */
            min-width: 200px; /* Assure une bonne largeur minimale */
        }

        button {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            cursor: pointer;
        }

        button i {
            margin-left: 5px;
        }

        /* Rendre l'image responsive */
        .header-image {
            max-width: 100%;
            height: auto;
        }

        /* Centrer les textes */
        .use-info {
            text-align: center;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .header-title {
                font-size: 1.2rem;
            }

            .d-flex {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        :root {
            --primary-color: #1BA078;
            --primary-light: #E8F5F0;
            --dark-color: #2D3748;
            --light-color: #F8FAFC;
            --border-color: #E2E8F0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius-md: 12px;
            --radius-sm: 8px;
        }

        .card {
            border: none;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            padding: 16px 24px;
            border-radius: var(--radius-md) var(--radius-md) 0 0 !important;
        }

        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(27, 160, 120, 0.2);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            font-weight: bold;
            border-radius: var(--radius-sm);
        }

        .btn-primary:hover {
            background-color: #168a6d;
            border-color: #168a6d;
        }

        .report-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .report-header img {
            max-width: 100%;
            height: auto;
            margin-bottom: 15px;
        }

        .report-title {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 10px;
        }

        .report-meta {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .report-meta-item {
            background-color: var(--primary-light);
            padding: 8px 15px;
            border-radius: var(--radius-sm);
            font-weight: bold;
        }

    </style>
    <script>
        function printTable() {
            window.print();
        }
    </script>
</head>

<body>
<div class="container">
        <!-- Filter Card -->
        <div class="card no-print">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-funnel-fill me-2"></i>
                    <span>فلترة البيانات</span>
                </div>
                <a href="home.php" class="btn btn-sm btn-light">
                    <i class="bi bi-house-door-fill me-1"></i> الرئيسية
                </a>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row g-3">
                        <!-- Year Selection -->
                        <div class="col-md-3">
                            <label for="year-select" class="form-label">السنة المالية</label>
                            <select id="year-select" class="form-select" name="year">
                                <option><?php echo htmlspecialchars($last_year); ?></option>
                            </select>
                        </div>

                        <!-- User Selection -->
                        <?php if ($role_id == 1) { ?>
                            <div class="col-md-3">
                                <label for="user-select" class="form-label">المستخدم</label>
                                <select id="user-select" class="form-select" name="user_id" onchange="updateUsername()">
                                    <option value="all">جميع المستخدمين</option>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $optionValue = $role_id != 1 ? $role_id : $row['id'];
                                            $selected = $selectedUser == $row['id'] ? 'selected' : '';
                                            echo "<option value='" . $optionValue . "' $selected>" . $row['username'] . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>لا يوجد مستخدمون</option>";
                                    }
                                    ?>
                                </select>
                                <input type="hidden" id="username" name="username" value="">
                            </div>
                        <?php } ?>

                        <!-- Date Range -->
                        <div class="col-md-3">
                            <label for="start-date" class="form-label">من تاريخ</label>
                            <input id="start-date" type="date" class="form-control" name="start_date" value="<?= $selectedStartDate ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label for="end-date" class="form-label">إلى تاريخ</label>
                            <input id="end-date" type="date" class="form-control" name="end_date" value="<?= $selectedEndDate ?>" required>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-md-12 text-center mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter-circle-fill me-2"></i>تصفية البيانات
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Header -->
        <div class="report-header">
            <img src="../images/header.png" alt="Company Logo" style="max-height: 120px;">
            <h2 class="report-title">التقرير اليومي</h2>
            <div class="report-meta">
                <div class="report-meta-item">
                    <i class="bi bi-calendar me-2"></i>
                    <span>بتاريخ: <?php echo date('d/m/Y | H:i'); ?></span>
                </div>
                <div class="report-meta-item">
                    <i class="bi bi-person me-2"></i>
                    <span>المستخدم: <?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
            <div class="report-meta">
                <div class="report-meta-item">
                    <i class="bi bi-calendar-range me-2"></i>
                    <span>من: <?php echo (new DateTime($start_date))->format('d/m/Y'); ?></span>
                </div>
                <div class="report-meta-item">
                    <i class="bi bi-calendar-range me-2"></i>
                    <span>إلى: <?php echo (new DateTime($end_date))->format('d/m/Y'); ?></span>
                </div>
            </div>
        </div>
</div>


    <div class="table-responsive tbl">
        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 12%;">بتاريخ</th>
                    <th rowspan="2">التفاصــــــــــــــــــــــــــــيل</th>
                    <th colspan="2">الصنــــــــــــــدوق</th>
                    <th colspan="3">البنــــــــــــــوك</th>
                </tr>
                <tr>
                    <th>الداخل</th>
                    <th>الخارج</th>
                    <th>البنوك</th>
                    <th>الداخل</th>
                    <th>الخارج</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <?php if (!empty($transaction['transaction_description'])): ?>
                        <tr>
                            <td> <?php echo htmlspecialchars($transaction['transaction_date']) ?></td>
                            <td style="text-align: start;">
                                <?php 
                                    echo htmlspecialchars($transaction['transaction_description']) . 
                                        ' <strong>/ رقم الوصل: </strong>' . 
                                        htmlspecialchars($transaction['receipt_id']); 
                                    ?>
                            </td>
                            <td><?php echo ($transaction['transaction_type'] == 'plus' && !$transaction['bank_account_id']) ? htmlspecialchars($transaction['amount']) : ''; ?></td>
                            <td><?php echo ($transaction['transaction_type'] == 'minus' && !$transaction['bank_account_id']) ? htmlspecialchars(abs($transaction['amount'])) : ''; ?></td>
                            <td><?php echo htmlspecialchars($transaction['bank_name']); ?></td>
                            <td><?php echo ($transaction['transaction_type'] == 'plus' && $transaction['bank_account_id']) ? htmlspecialchars($transaction['amount']) : ''; ?></td>
                            <td><?php echo ($transaction['transaction_type'] == 'minus' && $transaction['bank_account_id']) ? htmlspecialchars(abs($transaction['amount'])) : ''; ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        
                <tr class="table-footer">
                    <td colspan="2"><strong>المجاميع:</strong></td>
                    <td><?php echo htmlspecialchars($total_addition_amount); ?></td>
                    <td><?php echo htmlspecialchars($total_subtraction_amount); ?></td>
                    <td><strong>المجاميع:</strong></td>
                    <td><?php echo htmlspecialchars($total_addition_bank); ?></td>
                    <td><?php echo htmlspecialchars($total_subtraction_bank); ?></td>
                </tr>
                <tr class="table-footer">
                    <td  colspan="2"><strong>الرصيد الكلي:</strong></td>
                    <td colspan="2" ><?php echo htmlspecialchars($total_addition_amount - $total_subtraction_amount); ?></td>

                    <td><strong>الرصيد الكلي:</strong></td>
                    <td colspan="2"><?php echo htmlspecialchars($total_addition_bank - $total_subtraction_bank); ?></td>
                </tr>
            
        </table>
    </div>
<!-- Signatures Section -->
<div class="signatures" style="margin-top: 20px; display: flex; justify-content: space-between;">
    <div class="signature-row">
        <span>التوقيع: _______________</span> <!-- Signature line for Director -->
        <span>المدير</span> <!-- Director title -->
    </div>
    <div class="signature-row">
        <span>التوقيع: _______________</span> <!-- Signature line for Counter -->
        <span>المحاسب</span> <!-- Counter title -->
    </div>
</div>

    <button class="print-button" onclick="printTable()">طباعة</button>



    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        function updateUsername() {
            const userSelect = document.getElementById('user-select');
            const usernameInput = document.getElementById('username');
            usernameInput.value = userSelect.options[userSelect.selectedIndex].text;
        }

        document.addEventListener('DOMContentLoaded', updateUsername);
    </script>
</body>
</html>
