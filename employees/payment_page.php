<?php
// Include database connection
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}


$response = []; // Initialize response array
$student_data = null;
$paidMonths = [];
$allMonths = [
    'January' => 'يناير',
    'February' => 'فبراير',
    'March' => 'مارس',
    'April' => 'أبريل',
    'May' => 'مايو',
    'June' => 'يونيو',
    'July' => 'يوليو',
    'August' => 'أغسطس',
    'September' => 'سبتمبر',
    'October' => 'أكتوبر',
    'November' => 'نوفمبر',
    'December' => 'ديسمبر'
];

// Handle GET request (fetch student information)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['student_name'])) {
    $student_name = $_GET['student_name'];

    $sql = "SELECT students.id, students.student_name, classes.class_name, branches.branch_name, students.remaining
            FROM students
            INNER JOIN classes ON students.class_id = classes.class_id
            INNER JOIN branches ON students.branch_id = branches.branch_id
            WHERE students.student_name LIKE ?";
    
    $stmt = $conn->prepare($sql);
    $likeName = "%" . $student_name . "%";
    $stmt->bind_param('s', $likeName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student_data = $result->fetch_assoc();

        $paidMonthsQuery = "SELECT month FROM payments WHERE student_id = ?";
        $stmt2 = $conn->prepare($paidMonthsQuery);
        $stmt2->bind_param('i', $student_data['id']);
        $stmt2->execute();
        $paidMonthsResult = $stmt2->get_result();

        while ($row = $paidMonthsResult->fetch_assoc()) {
            $paidMonths[] = $row['month'];
        }

        $stmt2->close();
    }
    $stmt->close();
}

// Handle POST request (process payment)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['id'];
    $month = $_POST['month'];
    $due_amount = $_POST['due_amount'];
    $paid_amount = $_POST['paid_amount'];
    $remaining_amount = $_POST['remaining_amount'];
    $payment_method = $_POST['payment_method'];
    $bank_id = ($payment_method === "بنكي") ? $_POST['bank'] : null;

    if (!empty($student_id) && !empty($month) && !empty($due_amount) && !empty($paid_amount)) {
        $stmt = $conn->prepare("
            INSERT INTO payments (student_id, month, due_amount, paid_amount, remaining_amount, payment_method, bank_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssss", $student_id, $month, $due_amount, $paid_amount, $remaining_amount, $payment_method, $bank_id);

        if ($stmt->execute()) {
            $receipt_id = $conn->insert_id;  
            header("Location: receipt.php?receipt_id=$receipt_id");
            exit();
        } else {
            $response = ['success' => false, 'message' => 'حدث خطأ أثناء معالجة الدفع: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $response = ['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة.'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Fee Payment</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/jquery-base-ui.css">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }
        .container-main {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 1100px;
            margin: auto;
        }
        .header-title {
            font-family: 'Amiri', serif;
            font-size: 1.8rem;
            font-weight: bold;
        }
        .header-titlee {
            font-family: 'Amiri', serif;
            font-size: 1rem;
            font-weight: bold;
        }
        .form-select {
            font-size: 1rem;
            font-weight: bold;
            color: #1a73e8;
        }
        .input-group input {
            border-radius: 5px 0 0 5px;
            padding: 10px;
            border: 2px solid #1a73e8;
        }
        .input-group button {
            border-radius: 0 5px 5px 0;
            background-color: #1a73e8;
            color: #fff;
            padding: 0 20px;
            border: 2px solid #1a73e8;
        }
        .form-section {
            margin-bottom: 20px;
        }
        .form-section .bg-light {
            padding: 10px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            color: #333;
        }
        .form-section label {
            font-weight: bold;
            color: #1a73e8;
            display: block;
            margin-bottom: 5px;
        }
        .form-section div {
            font-size: 1.1rem;
            color: #555;
        }
        .payment-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .payment-info div {
            text-align: center;
            flex: 1;
        }
        .payment-info input,
        .payment-info select {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-top: 5px;
        }
        .months-card {
            background-color: #ffffff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .months-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .months-card .month-option {
            display: flex;
            align-items: center;
        }
        .months-card .month-option label {
            margin-left: 5px;
            font-weight: bold;
            color: #1a73e8;
            font-size: 0.8rem;
        }
        .confirm-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .confirm-button:hover {
            background-color: #218838;
        }
        .section-title {
            font-family: 'Amiri', serif;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .row-underline {
            border-bottom: 2px solid #1a73e8;
            margin-bottom: 20px;
        }
        .icon-left {
            float: left;
            font-size: 1.2rem;
            margin-right: 10px;
            color: #1a73e8;
        }
        .method-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .method-section label {
            font-size: 1.1rem;
            color: #333;
        }
        .method-section select {
            padding: 8px;
            border-radius: 5px;
            border: 2px solid #1a73e8;
            width: 100%;
        }
        #bank_list {
            margin-top: 20px;
            display: none; /* Initially hidden */
        }
    </style>
</head>
<body>

<div class="container-main">
    <!-- Header Section -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="header-title"><i class="icon-left bi bi-file-earmark-text"></i>تجديد رسوم الطلاب عن طريق الطالب</h1>
            <div>
                <label class="form-select-title" for="financial-year">السنة المالية</label>
                <select id="financial-year" class="form-select w-100">
                    <option><?php echo $last_year; ?></option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Search Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="header-titlee">البحث عن الطالب</h2>
            <form method="GET" action="">
                <div class="input-group">
                    <input type="text" name="student_name" class="form-control" id="student_name" placeholder="رقم الهاتف (الرقم الشخصي)">
                    <button class="btn btn-outline-secondary border-2" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Info Section -->
    <?php if (isset($student_data)): ?>
    <div class="row form-section">
        <div class="col-12 row-underline">
            <h2 class="section-title"> بيانات الطالب (ة):</h2>
        </div>
        <div class="col-12 d-flex justify-content-between text-center">
            <div>
                <label> رقم التعريف</label>
                <div><?php echo $student_data['id']; ?></div>
            </div>
            <div>
                <label>الاسم الكامل</label>
                <div><?php echo $student_data['student_name']; ?></div>
            </div>
            <div>
                <label>الفصل</label>
                <div><?php echo $student_data['class_name']; ?></div>
            </div>
            <div>
                <label>الرسوم</label>
                <div><?php echo $student_data['remaining']; ?> أوقية جديدة</div>
            </div>
            <div>
                <label>المتأخرات</label>
                <div>0.00 أوقية جديدة</div>
            </div>
        </div>
    </div>

    <!-- Payment Information Section -->
    <form method="POST" action="">
        <div class="row form-section">
            <div class="col-12 row-underline">
                <h2 class="section-title">معلومات الدفع:</h2>
            </div>

            <div class="col-6">
                <div class="payment-info">
                    <div>
                        <label for="arrears-paid">المبلغ المسدد</label>
                        <input type="text" name="paid_amount" id="arrears-paid" placeholder="0.00" oninput="calculateRemaining()">
                    </div>
                    <div>
                        <label for="arrears-remaining">الباقي</label>
                        <input type="text" name="remaining_amount" id="arrears-remaining" placeholder="0.00" readonly>
                    </div>
                </div>

                <div class="method-section mt-3">
                    <div>
                        <label for="method">طريقة الدفع</label>
                        <select id="method" name="payment_method" onchange="toggleBankList(this.value)">
                            <option value="نقدي">نقدي</option>
                            <option value="بنكي">بنكي</option>
                        </select>
                    </div>
                    <button type="submit" class="confirm-button">تأكيد العملية</button>
                </div>

                <!-- Bank List Section -->
                <div id="bank_list" class="form-group">
                    <label for="bank">اختر البنك:</label>
                    <select id="bank" name="bank" class="form-control">
                        <?php
                        $sql = "SELECT account_id, bank_name FROM bank_accounts";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['account_id'] . "'>" . $row['bank_name'] . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="col-6">
                <div class="months-card">
                    <div class="section-title">الأشهر</div>
                    <div class="months-grid">
                        <?php foreach ($allMonths as $monthKey => $monthName): ?>
                        <div class="month-option">
                            <input type="checkbox" name="month[]" value="<?php echo $monthName; ?>"
                            <?php if (in_array($monthName, $paidMonths)) echo 'checked disabled'; ?>>
                            <label><?php echo $monthName; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script src="js/jquery-3.5.1.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script>
    $(function() {
        $("#student_name").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "autocomplete.php",
                    dataType: "json",
                    data: {
                        term: request.term
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 1
        });
    });

    function calculateRemaining() {
        var dueAmount = parseFloat(document.getElementById('arrears-paid').value) || 0;
        var paidAmount = parseFloat(document.getElementById('arrears-remaining').value) || 0;
        var remainingAmount = dueAmount - paidAmount;
        document.getElementById('arrears-remaining').value = remainingAmount.toFixed(2);
    }

    function toggleBankList(paymentMethod) {
        var bankList = document.getElementById('bank_list');
        if (paymentMethod === 'بنكي') {
            bankList.style.display = 'block';
        } else {
            bankList.style.display = 'none';
        }
    }
</script>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
