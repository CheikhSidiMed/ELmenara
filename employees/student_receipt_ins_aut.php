<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';
// Include database connection
    
$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}


session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    exit;
}
$name_connect = $_SESSION['username'];

$receipt_id = isset($_GET['receipt_id']) ? $_GET['receipt_id'] : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';


$des_ins = isset($_GET['des_ins']) ? $_GET['des_ins'] : '';
$description = isset($_GET['description']) ? $_GET['description'] : '';
$registuration_fee = isset($_GET['registuration_fee']) ? (float)$_GET['registuration_fee'] : 0;
$p_paid = isset($_GET['p_paid']) ? (float)$_GET['p_paid'] : 0;
$due_amounte = $p_paid + $registuration_fee;


$bank_name = ''; // Initialize bank_name variable

// Fetch student details and bank name if payment method is 'بنكي'
$sql = "SELECT students.student_name, students.phone, students.remaining, classes.class_name, branches.branch_name, b.bank_name
        FROM students
        JOIN classes ON students.class_id = classes.class_id
        JOIN branches ON students.branch_id = branches.branch_id
        LEFT JOIN transactions t ON students.id = t.student_id AND t.id = ?
        LEFT JOIN bank_accounts b ON t.bank_account_id = b.account_id
        WHERE students.id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Error preparing the statement: ' . $conn->error);
}

// Bind the receipt_id and student_id
$stmt->bind_param("ii", $receipt_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student_data = $result->fetch_assoc();
    $bank_name = $student_data['bank_name'] ? $student_data['bank_name'] : ''; // Assign bank_name if exists
} else {
    die("Student or payment not found.");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include necessary head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Payment Receipt</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Tajawal', sans-serif;
        background-color: #f5f5f5;
        direction: rtl;
        text-align: right;
    }

    .receipt {
        background-color: white;
        box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
        padding: 20px;
        border-radius: 5px;
    }

    .receipt-header img {
        width: 100%;
        height: auto;
        border-bottom: 2px solid #007b5e;
        margin-bottom: 20px;
    }

    .info-container, .summary-container {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        align-items: center;
    }

    .info-container div, .summary-container div {
        flex: 1;
        text-align: center;
        font-weight: bold;
        color: #5a5a5a;
    }

    .info-container .highlight, .months-container .highlight, .summary-container .text-primary {
        color: #007b5e;
        font-weight: bold;
    }

    .months-header, .months-row {
        display: flex;
        justify-content: space-between;
        border: 1px solid #007b5e;
        border-radius: 5px;
        padding: 10px;
        margin-top: 20px;
    }

    .months-header {
        background-color: #007b5e;
        color: white;
        font-weight: bold;
    }

    .months-row {
        font-weight: bold;
        background-color: #f9f9f9;
    }

/* Print styles */
@media print {
    @page {
        size: A5;
        margin: 0;
    }

    body {
        margin: 0;
        padding: 0;
        font-size: 10pt;
        color: #000; /* Force text to black */
    }

    .container {
        width: 100%;
        margin: 0 auto;
        padding: 0 20px;
        text-align: center;
    }

    .receipt {
        width: 100%;
        margin: 0 auto;
        padding: 10px;
        border: none;
        color: #000;
        page-break-inside: avoid;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 5px;
        border: 1px solid #000;
        text-align: center;
        font-size: 9pt;
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

<body>
    <div class="container my-5">
        <div class="receipt">
            <!-- Header Image Section -->
            <div class="receipt-header">
                <img src="../images/header.png" alt="Header Image">
            </div>

            <!-- Basic Receipt Information -->
            <div class="summary-container">
                <div><span>وصل رقم</span> : <?php echo sprintf("%010d", $receipt_id); ?></div>
                <div><span>بتاريخ</span> : <?php echo date('d/m/Y | H:i'); ?></div>
                <div><span>المستخدم</span> : <?php echo $name_connect; ?></div>
                <div><span>السنة الدراسية</span> : <?php echo $last_year; ?></div>
            </div>

            <br>

            <!-- Student Information Section -->
            <div class="info-container">
                <div><span class="highlight">اسم الطالب</span> : <?php echo $student_data['student_name']; ?></div>
                <div><span class="highlight">الفرع</span> : <?php echo $student_data['branch_name']; ?></div>
                <div><span class="highlight">القسم</span> : <?php echo $student_data['class_name']; ?></div>
                <div><span class="highlight">رقم الهاتف</span> : <?php echo $student_data['phone']; ?></div>
            </div>

            <!-- Paid Months Section -->
            <div class="months-header">
                <div>الوصف</div>
                <div>المبلغ المدفوع</div>
            </div>

            <!-- Display Optional Registration Fee and Description -->
            <?php if (!empty($des_ins) && !empty($registuration_fee)): ?>
                <div class="months-row">
                    <div><?php echo $des_ins; ?></div>
                    <div><?php echo $registuration_fee; ?></div>
                </div>
            <?php endif; ?>

            <!-- Additional Paid Items -->
            <?php if (!empty($description) && !empty($p_paid)): ?>
                <div class="months-row">
                    <div><?php echo $description; ?></div>
                    <div><?php echo $p_paid; ?></div>
                </div>
            <?php endif; ?>

            <!-- Payment Summary Section -->
            <div class="months-row">
                <div>
                    <span>حساب الدفع</span> : 
                    <span class="text-primary">
                        <?php echo $payment_method == 'بنكي' ? 'بنكي - ' . ($bank_name ?: '') : 'نقدي'; ?>
                    </span>
                </div>
                <div>
                    <span>مجموع الرسوم</span> :
                    <span class="text-primary"><?php echo $due_amounte; ?></span>
                </div>
            </div>

            <!-- Print Button (Hidden for Print) -->
            <div class="text-center mt-4">
                <button class="btn btn-primary print-button" onclick="window.print()">طباعة</button>
            </div>
        </div>
    </div>
</body>
