<?php
// Include database connection
include 'db_connection.php';

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['student_ids'])) {
    $student_ids = $_POST['student_ids'];

    // Fetch information for selected students
    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
    $types = str_repeat('i', count($student_ids));
    $sql = "
        SELECT students.*, classes.class_name, branches.branch_name 
        FROM students 
        INNER JOIN classes ON students.class_id = classes.class_id 
        INNER JOIN branches ON students.branch_id = branches.branch_id 
        WHERE students.id IN ($placeholders)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$student_ids);
    $stmt->execute();
    $students_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch paid months for each student
    foreach ($students_data as &$student) {
        $sql_paid_months = "
            SELECT month 
            FROM payments 
            WHERE student_id = ?
        ";
        $stmt = $conn->prepare($sql_paid_months);
        $stmt->bind_param('i', $student['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $paidMonths = $result->fetch_all(MYSQLI_ASSOC);
        $student['paid_months'] = array_column($paidMonths, 'month');
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الدفع للطلاب</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f9;
            margin: 20px;
        }
        .container {
            max-width: 900px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 30px;
            font-weight: bold;
            text-align: center;
        }
        .card {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #fafafa;
            margin-bottom: 20px;
            box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.1);
        }
        .card h5 {
            margin-bottom: 20px;
            color: #007bff;
        }
        .form-group label {
            font-weight: bold;
            color: #555;
        }
        .form-control {
            border-radius: 5px;
            box-shadow: none;
            border: 1px solid #ced4da;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 20px;
            padding: 10px 20px;
            font-size: 16px;
            width: 100%;
        }
        .form-check-label {
            margin-left: 10px;
        }
        .form-check-input {
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>الدفع للطلاب</h2>
        <form method="POST" action="Process Family Payment.php">
            <?php foreach ($students_data as $student): ?>
                <div class="card">
                    <h5><?php echo $student['student_name']; ?> - <span class="text-secondary"><?php echo $student['class_name']; ?>, <?php echo $student['branch_name']; ?></span></h5>
                    <input type="hidden" name="student_ids[]" value="<?php echo $student['id']; ?>">

                    <div class="form-group">
                        <label for="month_<?php echo $student['id']; ?>">الشهر:</label>
                        <select id="month_<?php echo $student['id']; ?>" name="months[<?php echo $student['id']; ?>]" class="form-control">
                            <?php foreach ($allMonths as $key => $month): ?>
                                <?php if (!in_array($month, $student['paid_months'])): ?>
                                    <option value="<?php echo $month; ?>"><?php echo $month; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="due_amount_<?php echo $student['id']; ?>">المستحقات:</label>
                        <input type="text" name="due_amounts[<?php echo $student['id']; ?>]" class="form-control" id="due_amount_<?php echo $student['id']; ?>" value="<?php echo $student['remaining']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="paid_amount_<?php echo $student['id']; ?>">المدفوع:</label>
                        <input type="text" name="paid_amounts[<?php echo $student['id']; ?>]" class="form-control" id="paid_amount_<?php echo $student['id']; ?>" oninput="calculateRemaining(<?php echo $student['id']; ?>)">
                    </div>

                    <div class="form-group">
                        <label for="remaining_amount_<?php echo $student['id']; ?>">المتبقي:</label>
                        <input type="text" name="remaining_amounts[<?php echo $student['id']; ?>]" class="form-control" id="remaining_amount_<?php echo $student['id']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="payment_method_<?php echo $student['id']; ?>">طريقة الدفع:</label>
                        <select id="payment_method_<?php echo $student['id']; ?>" name="payment_methods[<?php echo $student['id']; ?>]" class="form-control" onchange="toggleBankList(<?php echo $student['id']; ?>, this.value)">
                            <option value="نقدي">نقدي</option>
                            <option value="بنكي">بنكي</option>
                        </select>
                    </div>

                    <div id="bank_list_<?php echo $student['id']; ?>" class="form-group" style="display:none;">
                        <label for="bank_<?php echo $student['id']; ?>">اختر البنك:</label>
                        <select id="bank_<?php echo $student['id']; ?>" name="banks[<?php echo $student['id']; ?>]" class="form-control">
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
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success">تأكيد الدفع</button>
        </form>
    </div>

    <script>
        function calculateRemaining(studentId) {
            var dueAmount = parseFloat(document.getElementById('due_amount_' + studentId).value) || 0;
            var paidAmount = parseFloat(document.getElementById('paid_amount_' + studentId).value) || 0;
            var remainingAmount = dueAmount - paidAmount;
            document.getElementById('remaining_amount_' + studentId).value = remainingAmount.toFixed(2);
        }

        function toggleBankList(studentId, paymentMethod) {
            var bankList = document.getElementById('bank_list_' + studentId);
            if (paymentMethod === 'بنكي') {
                bankList.style.display = 'block';
            } else {
                bankList.style.display = 'none';
            }
        }
    </script>
</body>
</html>
