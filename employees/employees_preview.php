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


// Fetch the latest academic year
$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

$balance = 0;
$total_debit = 0;
$total_credit = 0;
$running_balance = 0;
// Fetch employee ID from the URL
$employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : null;

// Initialize date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Fetch employee details
if ($employee_id) {
    $stmt = $conn->prepare("SELECT e.full_name, j.job_name, e.subscription_date, balance
        FROM employees e
        JOIN jobs j ON e.job_id = j.id
        WHERE e.id = ?
    ");
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $stmt->bind_result($full_name, $job_name, $registration_date, $balance);
    $stmt->fetch();
    $stmt->close();

    // Fetch transactions with optional date filters
    $transactions = [];
    $query = "SELECT DATE_FORMAT(transaction_date, '%d-%m-%Y') as transaction_date,
                transaction_description, amount, transaction_type, sold_emp
            FROM transactions
            WHERE employee_id = ?";

    

    // Prepare the statement
    $stmt = '';

    // Bind parameters based on the condition
    if (!empty($start_date) && !empty($end_date)) {
        $query .= " AND transaction_date BETWEEN ? AND ? ORDER BY id DESC";
        $stmt = $conn->prepare($query);

        $end_date_plus_one = (new DateTime($end_date))->modify('+1 day')->format('Y-m-d');
        $stmt->bind_param('sss', $employee_id, $start_date, $end_date_plus_one);
    } else {
        $query .= " ORDER BY id DESC";
        $stmt = $conn->prepare($query);

        $stmt->bind_param('s', $employee_id);
    }

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
        if ($row['transaction_type'] === 'plus') {
            $running_balance += $row['amount'];
            $total_debit += $row['amount'];
        } elseif ($row['transaction_type'] === 'minus') {
            $running_balance -= $row['amount'];
            $total_credit += $row['amount'];
        }
    
        $row['running_balance'] = $running_balance; // Store the calculated running balance in the row
    }
    $stmt->close();
}
$calculated_balance = $total_debit - $total_credit;

$conn->close();
?>



<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة الحساب</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/cairo.css" rel="stylesheet">
    <style>
            body {
                font-family: 'Cairo', sans-serif;
                direction: rtl;
                background-color: #f4f7f6;
                margin: 0;
                padding: 0;
            }

            .main-container {
                max-width: 1200px;
                margin: 40px auto;
                background: linear-gradient(145deg, #ffffff, #e6e6e6);
                box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.1);
                border-radius: 15px;
                padding: 20px;
            }

            .header {
                text-align: center;
                padding: 20px;
                background-color: #1BA078;
                color: white;
                border-radius: 15px 15px 0 0;
            }

            .header h2 {
                margin: 0;
                font-size: 28px;
            }

            .header img {
                max-height: 80px;
                margin-top: 10px;
            }

            .row {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin: 20px 0;
            }

            .col {
                flex: 1;
                min-width: 250px;
                background: white;
                box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.1);
                border-radius: 10px;
                padding: 15px;
            }

            .col h4 {
                font-size: 16px;
                color: #333;
                margin-bottom: 10px;
            }

            .form-control-plaintext {
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 10px;
                font-size: 17px;
                font-weight: bold;
                color: #333;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }

            .table th {
                background-color: #1BA078;
                color: white;
                font-size: 16px;
                padding: 10px;
                text-align: center;
            }

            .table td {
                padding: 10px;
                text-align: center;
                font-size: 14px;
                border: 1px solid #ddd;
            }

            .table tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            .table tr:nth-child(odd) {
                background-color: #ffffff;
            }

            .btn {
                padding: 10px 20px;
                font-size: 14px;
                color: white;
                background-color: #1BA078;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .main-container {
                padding: 30px;
                border-radius: 15px;
                background-color: white;
                border: 1px solid #1BA078;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                max-width: 1400px;
            }
            .header-image {
                width: 100%;
                height: auto;
                object-fit: cover;
                margin-bottom: 20px;
                border-radius: 10px;
            }

            .btn:hover {
                background-color: #14865b;
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

            @media screen and (max-width: 768px) {
                .row {
                    flex-direction: column;
                }
            }
            @media print {
                body {
                    background-color: white;
                }

                .btn-custom {
                    display: none !important;
                }

                .header-image {
                    display: block;
                    width: 100%;
                    height: auto;
                    margin-bottom: 20px;
                }

                table {
                    border-collapse: collapse;
                    width: 100%;
                    font-size: 12px;
                }

                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    color: black !important;
                }
                .main-container {
                padding: 30px;
                border-radius: 15px;
                background-color: white;
                max-width: 1400px;
            }

                .summary-container {
                    display: none !important;
                }
            }
    </style>
</head>
<body>
    

<div class="container main-container">
    <div class="row align-items-center">
        <div class="col-12">
            <img src="../images/header.png" alt="Header Image" class="header-image">
        </div>
        <div class="col-12">
            <h2 class="header-title">معاينة الحساب</h2>
        </div>
    </div>
    <form method="GET" action="">
        <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
        <div class="row">
            <div class="col">
                <label for="start_date">تاريخ البداية:</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col">
                <label for="end_date">تاريخ النهاية:</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col d-flex align-items-end summary-container">
                <button type="submit" class="btn">تصفية</button>
                <div class="home-button-container text-start me-5">
                    <a href="home.php" class="btn">
                        <i ></i> الصفحة الرئيسية
                    </a>
                </div>
            </div>
        </div>
    </form>
    <div class="row">
        <div class="col">
            <h4>اسم الموظف</h4>
            <div class="form-control-plaintext"><?php echo $full_name; ?></div>
        </div>
        <div class="col">
            <h4>الوظيفة</h4>
            <div class="form-control-plaintext"><?php echo $job_name; ?></div>
        </div>
        <div class="col">
            <h4>تاريخ التسجيل</h4>
            <div class="form-control-plaintext"><?php echo $registration_date; ?></div>
        </div>
        <div class="col">
            <h4>الرصيد</h4>
            <div class="form-control-plaintext"><?php echo $balance; ?></div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>بيان العملية</th>
                <th>مدين</th>
                <th>دائن</th>
                <th>الرصيد</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?php echo $transaction['transaction_date']; ?></td>
                <td><?php echo htmlspecialchars($transaction['transaction_description']); ?></td>
                <td><?php echo $transaction['transaction_type'] === 'minus' ? number_format($transaction['amount'], 2) : ''; ?></td>
                <td><?php echo $transaction['transaction_type'] === 'plus' ? number_format($transaction['amount'], 2) : ''; ?></td>
                <td><?php echo number_format($transaction['sold_emp']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="total-summary">
            <div class="summary-value"> مجموع العمليات </div>
            <div class="summary-value">مدين: <?php echo number_format($total_credit); ?></div>
            <div class="summary-value">دائن: <?php echo number_format($total_debit); ?></div>
            <div class="summary-value">الرصيد: <?php echo number_format($calculated_balance); ?></div>
    </div>

    <div class="row summary-container">
        <div class="col">
            <button class="btn" onclick="window.print()">طباعة</button>
        </div>
        <div class="col">
            <form action="generate_pdf_f.php" method="POST">
                <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                <input type="hidden" name="end_date" value="<?php echo $end_date; ?>">
                <input type="hidden" name="total_credit" value="<?php echo $total_credit; ?>">
                <input type="hidden" name="total_debit" value="<?php echo $total_debit; ?>">
                <input type="hidden" name="calculated_balance" value="<?php echo $calculated_balance; ?>">
                <button type="submit" class="btn">تحضير PDF</button>
            </form>
        </div>
    </div>
</div>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
