<?php
// Include database connection http://localhost/ELmenara/employees/Family_payment.php  http://localhost/ELmenara/employees/student_payment.php?student_name=Med+tihiya
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

$agent_data = [];
$students_data = [];
$paidMonths = [];
$monthly_fee = 0;
$allMonths = [
    
    'October' => 'أكتوبر',
    'November' => 'نوفمبر',
    'December' => 'ديسمبر',
    'January' => 'يناير',
    'February' => 'فبراير',
    'March' => 'مارس',
    'April' => 'أبريل',
    'May' => 'مايو',
    'June' => 'يونيو',
    'July' => 'يوليو',
    'August' => 'أغسطس',
    'September' => 'سبتمبر'
];
$total_remaining = count($allMonths) * $monthly_fee; 
$remaining = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['phone'];

     $sql = "SELECT * FROM agents WHERE phone = ?";
     $stmt = $conn->prepare($sql);
     $stmt->bind_param('s', $phone);
     $stmt->execute();
     $result = $stmt->get_result();
 
     if ($result->num_rows > 0) {
         $agent_data = $result->fetch_assoc();
 
         // Fetch the months that have been paid by the agent
         $sql_paid_months = "SELECT DISTINCT month FROM payments WHERE agent_id = ?";
         $stmt_paid_months = $conn->prepare($sql_paid_months);
         $stmt_paid_months->bind_param('i', $agent_data['agent_id']);
         $stmt_paid_months->execute();
         $paidMonthsResult = $stmt_paid_months->get_result();
 
         while ($row = $paidMonthsResult->fetch_assoc()) {
             $monthName = $allMonths[$row['month']] ?? $row['month'];
             $paidMonths[] = $monthName;
         }
 
         $stmt_paid_months->close();
 
         $sql_students = "SELECT * FROM students WHERE agent_id = ?";
         $stmt_students = $conn->prepare($sql_students);
         $stmt_students->bind_param('i', $agent_data['agent_id']);
         $stmt_students->execute();
         $students_result = $stmt_students->get_result();
 
         while ($row = $students_result->fetch_assoc()) {
            $students_data[] = [
                'id' => $row['id'],
                'student_name' => $row['student_name'],
                'fees' => $row['fees'],
                'remaining' => $row['remaining']
            ];
             
             $total_remaining += $row['remaining'];
             $remaining = $row['remaining'];
         }
 
         $stmt_students->close();
     } else {
         echo "Agent not found.";
     }
 
     $stmt->close();
 }
$bankList = [];
$sql = "SELECT account_id, bank_name FROM bank_accounts";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $bankList[] = $row;
}

$conn->close();

 ?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسديد رسوم الطالب عن طريق الوكيل</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/jquery-base-ui.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tajawal.css">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">

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
            font-family: 'Tajawal', serif;
            font-size: 1.8rem;
            font-weight: bold;
        }
        .header-titlee {
            font-family: 'Tajawal', serif;
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
            font-family: 'Tajawal', serif;
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
        #selected-bank-name {
            margin-top: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            color: #1a73e8;
        }
        /* Linked Students and Agent Info */
        .linked-students, .agent-info {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .linked-students .btn {
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            width: 100%;
            font-weight: bold;
            color: #1a73e8;
        }
        .linked-students .btn:hover {
            background-color: #e0e0e0;
        }
        .agent-info p {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        .students-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        }

        .student-option {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .styled-button {
            background-color: #2980b9; /* Blue background */
            color: white; /* White text */
            padding: 12px 24px; /* Padding for size */
            border: none; /* Remove default border */
            border-radius: 8px; /* Rounded corners */
            font-size: 16px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth hover transition */
        }

        .styled-button:hover {
            background-color: #1a6fa5; /* Darker blue on hover */
            transform: translateY(-2px); /* Slight lift on hover */
        }

        .styled-button:active {
            background-color: #145a85; /* Even darker blue on click */
            transform: translateY(1px); /* Slight push down on click */
        }

    
    </style>
</head>
<body>

<div class="container-main">
 <!-- Header Section -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="header-title">
                <i class="icon-left bi bi-file-earmark-text"></i> تسديد رسوم الطالب عن طريق الوكيل
            </h1>
            <div class="d-flex align-items-center">
                <!-- Home Button with Icon -->
                <a href="home.php" class="btn btn-primary d-flex align-items-center" style="margin-left: 15px;">
                    <i class="bi bi-house-door-fill" style="margin-left: 5px;"></i> 
                    الرئيسية
                </a>
                <label class="form-select-title" for="financial-year" style="margin-left: 15px;">السنة المالية</label>
                <select id="financial-year" class="form-select w-100">
                    <option><?php echo $last_year; ?></option>
                </select>
            </div>
        </div>
    </div>


    
 <!-- Search Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="header-titlee">البحث عن الوكيل</h2>
            <form method="POST" action="">
                <div class="input-group">
                    <input type="text" name="phone" class="form-control" id="phone" placeholder="رقم الهاتف (الرقم الشخصي أو الاسم)">
                    <button class="btn btn-outline-secondary border-2" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>



    <?php if (!empty($agent_data)): ?>
    <!-- Linked Students and Agent Info Section -->
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="linked-students">
                <div class="section-title">الطلبة المرتبطين:</div>
                <div class="students-grid">
                    <?php foreach ($students_data as $student): ?>
                        <div class="student-option">
                            <button 
                                class="btn student-btn" 
                                data-remaining="<?php echo $student['remaining']; ?>"
                                data-student-id="<?php echo $student['id']; ?>" 
                                data-fee="<?php echo $student['fees']; ?>" 
                                onclick="fetchPaidMonths(<?php echo $agent_data['agent_id']; ?>, <?php echo $student['id']; ?>, <?php echo $student['remaining']; ?>)">
                                <?php echo $student['student_name']; ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="agent-info">
                <div class="section-title">معلومات الوكيل:</div>
                <p>رقم التعريف: <?php echo $agent_data['agent_id']; ?></p>
                <p>الاسم: <?php echo $agent_data['agent_name']; ?></p>
                <p>رقم الهاتف: <?php echo $agent_data['phone']; ?></p>
            </div>
        </div>
    </div>
    <script>
        function clearFormFields() {
            // Get the form element
            const form = document.querySelector('form');

            form.querySelectorAll('input[type="text"], input[type="hidden"]').forEach(input => input.value = '');

            form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);
            window.location.reload(true);

        }
    </script>
    <!-- Payment Information Section -->
<form method="POST" action="process_agent_payment.php">

    <input type="hidden" name="id" value="<?php echo $agent_data['agent_id']; ?>">
    <input type="hidden" id="selected-student-id" name="student_id">
    <label for="remaining"></label>
    <input type="hidden" id="remaining" name="remaining" readonly>
    <div class="row form-section">
        <div class="col-6">
            <div class="payment-info">
                <div>
                    <label for="due-amount">المستحقات</label>
                    <input type="text" name="due_amount" id="due-amount" value="<?php echo $total_remaining; ?>" readonly>
                </div>
                <div>
                    <label for="paid-amount">المبلغ المسدد</label>
                    <input type="text" name="paid_amount" id="arrears-paid" placeholder="0.00" oninput="calculateRemaining()">
                </div>
                <div>
                    <label for="remaining-amount">الباقي</label>
                    <input type="text" name="remaining_amount" id="arrears-remaining" placeholder="0.00" readonly>
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <div id="total-remaining-container" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;">
                    <label for="total-remaining">  المتأخرات </label>
                    <input type="text" id="total-remaining" name="due_amount" value="0.00" disabled style="border: none; background-color: transparent; outline: none; color: #555; font-size: 1.1rem;">
                </div>
            </div>
                    <!-- Payment Info Sections -->
            <div class="payment-info" >
                <label><input type="checkbox" id="toggleRegFee" onclick="toggleVisibility('reg-fee')">  رسوم الاتسجيل</label>
                <label><input type="checkbox" id="togglePFee" onclick="toggleVisibility('p-fee')">رسوم اخرى</label>
            </div>
                    <!-- Payment Info Sections -->
            <div class="payment-info" id="reg-fee" style="display: none;">
                <div>
                    <label for="registuration-fee">المبلغ المسدد</label>
                    <input type="text" name="registuration_fee" id="registuration-fee" placeholder="0٫00">
                </div>
            </div>
            <div class="payment-info" id="p-fee" style="display: none;">
                <div>
                    <label for="description-p">الوصف</label>
                    <input type="text" name="description_p" id="description-p" placeholder="الوصف">
                </div>
                <div>
                    <label for="p-paid">المبلغ المسدد</label>
                    <input type="text" name="p_paid" id="p-paid" placeholder="0,00">
                </div>
                </div>

            <div class="method-section mt-3">
                <div>
                    <label for="method">طريقة الدفع</label>
                    <select id="method" name="payment_method" onchange="toggleBankModal(this.value)">
                        <option value="نقدي">نقدي</option>
                        <option value="بنكي">بنكي</option>
                    </select>
                </div>

                <div id="selected-bank-name"></div>
                <input type="hidden" id="selected-bank-id" name="bank">
                <button type="submit" class="confirm-button">تأكيد العملية</button>
            </div>
        </div>

        <!-- HTML to Display Checkboxes and Total Amount Due -->
        <div class="col-6">
            <div class="months-card">
                <div class="section-title">الأشهر</div>
                <div class="months-grid" id="months-grid">
                    <?php foreach ($allMonths as $monthKey => $monthName): ?>
                        <div class="month-option">
                            <input type="checkbox" name="months[]" value="<?php echo $monthName; ?>" id="month-<?php echo $monthKey; ?>" 
                                data-month-fee="<?php echo $monthly_fee; ?>" 
                                onclick="updateDueAmount()" disabled>
                            <label for="month-<?php echo $monthKey; ?>"><?php echo $monthName; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</form>
    <div class="row">
        <div class="col-3">
            <button id="pay-arrears-button" onclick="openArrearsModal()" class="styled-button">تسديد المتأخرات</button>

        </div>
        <div class="col-3">
            
            <form action="agent_receipt_all_pay.php" method="POST">
                <input type="hidden" name="id_agent" value="<?php echo $agent_data['agent_id']; ?>">
                <button type="sabmit" id="pay-arrears-button" class="styled-button">وصل</button>
    
            </form>
        </div>
    

    </div>

    <?php endif; ?>
</div>

<!-- Bank Modal -->
<div class="modal fade" id="bankModall" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bankModalLabel">اختر البنك</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <select id="bankk" name="bankk" class="form-control" onchange="updateSelectedBankName()">
            <option >البنك</option>
            <?php foreach ($bankList as $bank): ?>

                <option value="<?php echo $bank['account_id']; ?>"><?php echo $bank['bank_name']; ?></option>
            <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="toggleBankModal()">تأكيد</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Structure -->
<div id="arrears-modal" class="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); align-items: center; justify-content: center;">
    <div class="modal-content" style="max-width: 700px; background-color: #f9f9f9; border-radius: 10px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);">
        <h2 style="text-align: center; color: #333;">تسديد المتأخرات</h2>

        <form id="arrears-form" method="POST" action="process_family.php">
            <input type="hidden" id="student-id-id" name="student_d">
            <input type="hidden" value="<?php echo $agent_data['agent_name']; ?>" name="agent_name">
            <input type="hidden" value="<?php echo $agent_data['agent_id']; ?>" name="agent_id">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold;">المتأخرات</label>
                <div id="total-remaining-container" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;">
                    <label for="total-remaining-input"> أوقية جديدة </label>
                    <input type="text" id="total-remaining-input" name="due_amount" disabled style="border: none; background-color: transparent; outline: none; color: #555; font-size: 1.1rem;">
                </div>

            </div>

            <div style="margin-bottom: 20px;">
                <label for="amount-paidd" style="font-weight: bold;">المبلغ المدفوع</label>
                <input type="text" id="amount-paidd" name="amount_paidd" oninput="calculer()" required style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="remaining_amountt" style="font-weight: bold;">الباقي</label>
                <div id="remaining_amountt" name="remaining_amou_ntt"  style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;">0.00 أوقية جديدة</div>
            </div>

            <div class="method-section mt-3" style="margin-bottom: 20px;">
                <label for="methodd" style="font-weight: bold;">طريقة الدفع</label>
                <select id="methodd" name="payment_methodd" onchange="toggleBankModalInArrears(this.value)" style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
                    <option value="نقدي">طريقة الدفع</option>
                    <option value="نقدي">نقدي</option>
                    <option value="بنكي">بنكي</option>
                </select>
                <div id="selected-bank-name-arrears" style="margin-top: 15px; font-size: 1.1rem;"></div>
                <input type="hidden" id="selected-bank-id-arrears" name="bank">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                <button type="submit" class="confirm-button" style="padding: 12px 25px; background-color: #28a745; color: white; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">تأكيد العملية</button>
                <button type="button" onclick="closeArrearsModal()" style="padding: 12px 25px; background-color: #dc3545; color: white; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">إغلاق</button>
            </div>
        </form>
    </div>
</div>

<!-- Bank Modal -->
<div class="modal fade" id="bankModal" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bankModalLabel">اختر البنك</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <select id="bank" name="bank" class="form-control" onchange="updateSelectedBankName()">
            <?php foreach ($bankList as $bank): ?>
                <option value="<?php echo $bank['account_id']; ?>"><?php echo $bank['bank_name']; ?></option>
            <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="selectBank()">تأكيد</button>
      </div>
    </div>
  </div>
</div>

<script src="js/jquery-3.5.1.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
    $(function() {
        $("#phone").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "autocompleteee.php",
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
</script>
<script>
    function calculateRemaining() {
    // Extract the due amount from the input field with id "due-"
    var dueAmount = parseFloat(document.getElementById('due-amount').value) || 0;
    
    // Get the paid amount from the input field
    var paidAmount = parseFloat(document.getElementById('arrears-paid').value) || 0;
    
    // Calculate the remaining amount
    var remainingAmount = dueAmount - paidAmount;
    
    // Update the remaining amount field
    document.getElementById('arrears-remaining').value = remainingAmount.toFixed(2);
    }

</script>

<script>
    let selectedStudentFee = 0; // Variable to store the selected student's fee
    const dueAmountField = document.getElementById('due-amount');
    const checkboxes = document.querySelectorAll('#months-grid input[type="checkbox"]');
function fetchPaidMonths(agentId, studentId, studentFee) {
    selectedStudentFee = studentFee;
    console.log("Remaining:", studentFee);

    document.getElementById('remaining').value = studentFee;
    document.getElementById('student-id-id').value = studentId;
    
    fetch('fetch_total_remaining.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ student_id: studentId })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('total-remaining').value = data.total_remaining;
        document.getElementById('total-remaining-input').value = data.total_remaining;
    })
    .catch(error => console.error('Error fetching total remaining:', error));

    // Fetch the paid months from the server
    fetch('get_paid_months.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `agent_id=${agentId}&student_id=${studentId}`
    })
    .then(response => response.json())
    .then(paidMonths => {
        console.log("Paid months response:", paidMonths);

        // Ensure the response is an array
        if (!Array.isArray(paidMonths)) {
            console.error('Paid months is not an array:', paidMonths);
            return;
        }

        // Reset all checkboxes and enable them
        const checkboxes = document.querySelectorAll('#months-grid input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            checkbox.disabled = false;
        });

        // Disable checkboxes for paid months
        paidMonths.forEach(month => {
            const checkbox = document.querySelector(`#months-grid input[value="${month}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.disabled = true;
            }
        });

        // Update the due amount based on the new student's fee
        updateDueAmount();
    })
    .catch(error => console.error('Error fetching paid months:', error));
}


    // Function to update the due amount based on selected months and selected student's fee
    function updateDueAmount() {
        let selectedMonths = 0;

        // Count only selected months that are enabled and unchecked
        checkboxes.forEach(checkbox => {
            if (checkbox.checked && !checkbox.disabled) {
                selectedMonths++;
            }
        });

        // Calculate the total due based on selected months and the selected student's fee
        const totalDue = selectedStudentFee * selectedMonths;

        // Update the due amount field
        dueAmountField.value = totalDue.toFixed(2);
    }

    // Add event listeners to the checkboxes to update due amount on change
    checkboxes.forEach(checkbox => {
        if (!checkbox.disabled) {
            checkbox.addEventListener('change', updateDueAmount);
        }
    });
</script>

<script>

        // Function to toggle bank modal inside the arrears modal
        function toggleBankModalInArrears(paymentMethod) {
        if (paymentMethod === 'بنكي') {
            var bankModal = new bootstrap.Modal(document.getElementById('bankModall'), {
                keyboard: false
            });
            bankModal.show();

            // Add an event listener to ensure that when the user selects a bank, it updates the arrears modal
            document.getElementById('bankk').addEventListener('change', selectBankInArrears);
        } else if (paymentMethod === 'نقدي') {
            clearSelectedBankNameInArrears();
        }
    }

    function selectBankInArrears() {
        const bankSelect = document.getElementById('bankk');
        const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
        const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
        
        // Set the hidden input with the selected bank ID in arrears modal
        document.getElementById('selected-bank-id-arrears').value = selectedBankId;
        
        // Update the display with the selected bank name in the arrears modal
        document.getElementById('selected-bank-name-arrears').innerText = 'البنك المحدد: ' + selectedBankName;
    }

    function calculer() {
        

            const totalArrears = parseFloat(document.getElementById('total-remaining').value)  || 0;
            const amountPaid = parseFloat(document.getElementById('amount-paidd').value) || 0;
            const remaining = totalArrears - amountPaid;

            document.getElementById('remaining_amountt').innerText = remaining >= 0 
                ? remaining.toFixed(2) + ' أوقية جديدة' 
                : '0.00 أوقية جديدة';
        }

        function openArrearsModal() {
        const modal = document.getElementById('arrears-modal');
        modal.style.display = 'flex'; // Set display to flex to enable centering
    }

    function closeArrearsModal() {
        const modal = document.getElementById('arrears-modal');
        modal.style.display = 'none';
    }

</script>

<script>
    function toggleVisibility(divId) {
        const element = document.getElementById(divId);
        element.style.display = element.style.display === 'none' ? 'block' : 'none';
    }
</script>



<!-- JavaScript to handle the student selection and update months -->
<script>
        // Sample data that could be returned from an AJAX call (replace with real data)
        const studentsData = {
            <?php foreach ($students_data as $student): ?>
            "<?php echo $student['id']; ?>": {
                "paidMonths": <?php echo json_encode($paidMonths); ?>,  // Replace with actual paid months per student
                "remainingAmount": <?php echo $student['remaining']; ?>
            },
            <?php endforeach; ?>
        };
        

        // Function to handle student selection
        document.querySelectorAll('.student-btn').forEach(button => {
        button.addEventListener('click', function() {
            // If the same student is clicked, do nothing
            if (this.classList.contains('selected')) return;

            // Remove 'selected' class from all buttons
            document.querySelectorAll('.student-btn').forEach(btn => btn.classList.remove('selected'));

            // Add 'selected' class to the clicked button
            this.classList.add('selected');
            // Get the student ID from the data attribute
            const studentI = this.getAttribute('data-student-id');

            // Set the selected student ID in the input field to display
            // Set the selected student ID in both display and hidden input fields
            document.getElementById('selected-student-id').value = studentI;


            // Clear previously selected months
            document.querySelectorAll('.month-option input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.disabled = false;
            });

            // Get the student ID
            const studentId = this.getAttribute('data-student-id');

            // Get the student data (e.g., paid months)
            const studentData = studentsData[studentId];

            // Disable and check the months that have been paid
            studentData.paidMonths.forEach(month => {
                const checkbox = document.querySelector(`input[value="${month}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.disabled = true;
                }
            });

            // Optional: Update any other student-specific info like remaining amount
            console.log("Remaining amount for student", studentData.remainingAmount, studentId);
        });
    });
</script>

<!-- Add some styles for better UX -->
<style>
    .student-btn {
        margin: 5px;
        padding: 10px;
        background-color: lightgray;
        border: 1px solid #ccc;
        cursor: pointer;
    }

    .student-btn.selected {
        background-color: #007bff;
        color: white;
    }
</style>


<script>
   

    function toggleBankModal(paymentMethod) {
        if (paymentMethod === 'بنكي') {
            var bankModal = new bootstrap.Modal(document.getElementById('bankModal'), {
                keyboard: false
            });
            bankModal.show();
        } else if (paymentMethod === 'نقدي') {
            clearSelectedBankName();
        }
    }

    function selectBank() {
    const bankSelect = document.getElementById('bank');
    const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
    const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
    
    // Set the hidden input with the selected bank ID
    document.getElementById('selected-bank-id').value = selectedBankId;
    
    // Update the display with the selected bank name
    document.getElementById('selected-bank-name').innerText = 'البنك المحدد: ' + selectedBankName;
}


    function clearSelectedBankName() {
        document.getElementById('selected-bank-name').innerText = '';
    }
</script>


<script>
    const initialTotalRemaining = <?php echo $total_remaining; ?>; // Initial total amount for all unpaid months
    const initialRemaining = <?php echo $remaining; ?>; // Initial total amount for all unpaid months
    const monthlyFee = 100; // Define the monthly fee here
    const dueAmountField = document.getElementById('due-amount');
    const duAmountField = document.getElementById('du-amount');
    const checkboxes = document.querySelectorAll('.months-card input[type="checkbox"]');

    // Function to update the due amount based on selected months
    function updateDueAmount() {
        let selectedMonths = 0;

        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked && !checkbox.disabled) {
                selectedMonths++;
            }
        });

        // Calculate the new total due based on selected months
        const totalDue = initialTotalRemaining - (selectedMonths * monthlyFee);
        const newRemaining = initialRemaining - selectedMonthFee;

        // Update the du-amount field with the calculated value
        duAmountField.value = newRemaining.toFixed(2);
        // Update the due amount field
        dueAmountField.value = totalDue.toFixed(2);
    }

    // Add event listeners to the checkboxes
    checkboxes.forEach(function(checkbox) {
        if (!checkbox.disabled) {
            checkbox.addEventListener('change', updateDueAmount);
        }
    });

    // Initial call to set the amount correctly
    updateDueAmount();
</script>




</body>
</html>
