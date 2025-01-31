<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


$bankList = [];
$sql = "SELECT account_id, bank_name FROM bank_accounts";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $bankList[] = $row;
}

$sql = "SELECT m.id, m.student_id, a.agent_name, m.student_name, s.phone, a.phone AS a_a, a.whatsapp_phone,    
    GROUP_CONCAT(m.month_name ORDER BY m.created_at ASC SEPARATOR ', ') AS months_not_paid, 
    SUM(m.remaining_amount) AS total_remaining_amount, m.created_at FROM months_not_paid  m
    LEFT JOIN students s ON s.id = m.student_id
    LEFT JOIN agents a ON s.agent_id = a.agent_id
    WHERE remaining_amount >= 0.00 GROUP BY student_id
ORDER BY created_at DESC";
$result = $conn->query($sql);

$monthsNotPaid = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $monthsNotPaid[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأشهر غير المدفوعة</title>
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/tajawal.css">
    <link rel="stylesheet" href="css/sweetalert2.css"> 

    <link rel="stylesheet" href="css/jquery-ui.min.css">    
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        .styled-button {
            background-color: #2980b9; /* Blue background */
            color: white; /* White text */
            padding: 5px 10px; /* Padding for size */
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
        .container {
            margin-top: 20px;
        }
        .table-header {
            background-color: #007bff;
            color: #fff;
        }
        .table-header th {
            text-align: center;
        }
        .table-row td {
            text-align: center;
        }
        .highlight {
            background-color: #fff3cd !important;
        }
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
            font-weight: bold;
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent background */
            display: hone;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Ensures modal appears above other content */
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent background */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Ensures modal appears above other content */
        }

        .modal-content {
            background-color: #fff; /* White background for the modal */
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 600px; /* Wider modal width */
            max-width: 95%; /* Responsive max width */
        }

        .modal-content h2 {
            margin: 0 0 15px;
            text-align: center;
        }

        .modal-content label {
            display: block;
            margin-bottom: 5px;
        }

        .modal-content input[type="number"],
        .modal-content select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .modal-content .confirm-button {
            background-color: #4CAF50; /* Green background */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%; /* Full width */
            font-size: 1.1rem;
        }

        .modal-content .confirm-button:hover {
            background-color: #45a049; /* Darker green on hover */
        }
        .styled-button {
            background-color: #2980b9; /* Blue background */
            color: white; /* White text */
            padding: 3px 4px; /* Padding for size */
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
        #selected-bank-name {
            margin-top: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            color: #1a73e8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header d-flex justify-content-between align-items-center">
            <h1 class="page-title">إدارة الأشهر غير المدفوعة</h1>
            <button class="btn btn-primary home" onclick="window.location.href='home.php'">العودة إلى الصفحة الرئيسية</button>
        </div>
        <?php if (!empty($monthsNotPaid)): ?>
            <div class="table-responsive">
            <div class="search-box mb-4">
            <input type="text" id="searchInput" class="form-control" placeholder="البحث عن الطالب...">
        </div>
                <table class="table table-bordered table-hover">
                    <thead class="table-header">
                        <tr>
                            <th>#</th>
                            <th>اسم الطالب</th>
                            <th>رقم الطالب</th>
                            <th>اسم الوكيل</th>
                            <th>رقم الوكيل</th>
                            <th>رقم الوكيل WH</th>
                            <th>الشهر</th>
                            <th>السنة</th>
                            <th>المبلغ المتبقي</th>
                            <th>حالة الدفع</th>
                            <th>خيارات</th>
                        </tr>
                    </thead>
                    <tbody id="suspendedStudentsTableBody">
                        <?php foreach ($monthsNotPaid as $index => $record): ?>
                            <tr class="table-row highlight">
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $record['student_name']; ?></td>
                                <td><?php echo $record['phone'] ?? 'N/A'; ?></td>
                                <td><?php echo $record['agent_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $record['a_a'] ?? 'N/A'; ?></td>
                                <td><?php echo $record['whatsapp_phone'] ?? 'N/A'; ?></td>
                                <td><?php echo $record['months_not_paid']; ?></td>
                                <td><?php echo $record['created_at']; ?></td>
                                <td><?php echo $record['total_remaining_amount']; ?></td>
                                <td>
                                    <span class="badge bg-warning text-dark">غير مدفوع</span>
                                </td>
                                <td>
                                    <button id="pay-arrears-button-<?php echo $index; ?>" 
                                        onclick="openArrearsModal(
                                            document.getElementById('student_id_<?php echo $index; ?>').value, 
                                            document.getElementById('total_amount_<?php echo $index; ?>').value,
                                            document.getElementById('months_<?php echo $index; ?>').value
                                        )" 
                                        class="styled-button"
                                    >تسديد المتأخرات</button>
                                    <input type="hidden" name="student_id" id="student_id_<?php echo $index; ?>" value="<?php echo $record['student_id']; ?>">
                                    <input type="hidden" name="total_remaining_amount" id="total_amount_<?php echo $index; ?>" value="<?php echo $record['total_remaining_amount']; ?>">
                                    <input type="hidden" name="months_" id="months_<?php echo $index; ?>" value="<?php echo $record['months_not_paid']; ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                لا توجد أشهر غير مدفوعة في قاعدة البيانات.
            </div>
        <?php endif; ?>
    </div>


    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/sweetalert2.min.js"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>


    <div id="arrears-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="max-width: 700px; margin: auto; background-color: #f9f9f9; border-radius: 10px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);">
            <h2 style="text-align: center; color: #333;">تسديد المتأخرات</h2>
            
            <form id="arrears-form" method="POST" action="process_unpaid.php">
                <input type="hidden" id="id_us" name="id" value="">
                <input type="hidden" id="stu_months" name="stu_months" value="">
                <input type="hidden" id="amo_unt_paid" name="amount_paid" value="">
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold;">المتأخرات</label>
                    <div id="total-arrears" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;"> أوقية جديدة</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label for="amount_paid" style="font-weight: bold;">المبلغ المدفوع</label>
                    <input type="text" id="amount_paid" name="amount_paid" required onchange="calculer()" style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label for="remaining_amount" style="font-weight: bold;">الباقي</label>
                    <div id="remaining_amount" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;">0.00 أوقية جديدة</div>
                </div>

                <!-- Payment method -->
                <div class="method-section mt-3" style="margin-bottom: 20px;">
                    <label for="methodd" style="font-weight: bold;">طريقة الدفع</label>
                    <select id="methodd" name="payment_method" onchange="toggleBankModalInArrears(this.value)" style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
                        <option value="نقدي">نقدي</option>
                        <option value="بنكي">بنكي</option>
                    </select>
                    <div id="selected-bank-name-arrears" style="margin-top: 15px; font-size: 1.1rem;"></div>
                    <input type="hidden" id="selected-bank-id-arrears" name="bank">
                </div>

                <!-- Confirm and close buttons with added space -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                    <button type="submit" class="confirm-button" style="padding: 12px 25px; background-color: #28a745; color: white; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">تأكيد العملية</button>
                    <button type="button" onclick="closeArrearsModal()" style="padding: 12px 25px; background-color: #dc3545; color: white; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">إغلاق</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="bankModall" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="bankModalLabel">اختر البنك</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <select id="bankk" name="bankk" class="form-control" onchange="updateSelectedBankName()">
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

    <script>
        function openArrearsModal(id, tot_am, months) {
            document.getElementById('arrears-modal').style.display = 'flex';
            document.getElementById('total-arrears').innerText = tot_am;
            document.getElementById('amo_unt_paid').value = tot_am;
            document.getElementById('id_us').value = id;
            document.getElementById('stu_months').value = months;


            // // document.getElementById('total-arrears').innerText = t_a;
            // <input type="hidden" name="student_id" id="student_id" value="">
            // <input type="hidden" name="moths_list" id="moths-list" value="">
            // document.getElementById('student_id').value = student_id;
        }

        function closeArrearsModal() {
            document.getElementById('arrears-modal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('pay-arrears-button').addEventListener('click', openArrearsModal);
        });

        document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('amount_paid').addEventListener('input', calculer);
            });
        

        function calculer() {
            const totalArrears = parseFloat(document.getElementById('amo_unt_paid').value);
            const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
            const remaining = totalArrears - amountPaid;

            document.getElementById('remaining_amount').innerText = remaining >= 0 
                ? remaining.toFixed(2) + ' أوقية جديدة' 
                : '0.00 أوقية جديدة';
        }


        // Function to toggle bank modal inside the arrears modal 
        function toggleBankModalInArrears(paymentMethod) {
        if (paymentMethod === 'بنكي') {
            // Show the bank modal using Bootstrap's Modal component
            const bankModal = new bootstrap.Modal(document.getElementById('bankModall'), {
                keyboard: false
            });
            bankModal.show();

            // Add an event listener for bank selection, if not already added
            const bankSelectElement = document.getElementById('bankk');
            if (!bankSelectElement.hasAttribute('data-listener-added')) {
                bankSelectElement.addEventListener('change', selectBankInArrears);
                bankSelectElement.setAttribute('data-listener-added', 'true'); // Mark listener as added
            }
        } else if (paymentMethod === 'نقدي') {
            // Clear selected bank details if the payment method is 'نقدي'
            clearSelectedBankNameInArrears();
        }
        }
        // Function to select the bank in arrears modal
        function selectBankInArrears() {
            const bankSelect = document.getElementById('bankk');
            const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
            const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
            
            // Set the hidden input with the selected bank ID in arrears modal
            document.getElementById('selected-bank-id-arrears').value = selectedBankId;
            
            // Update the display with the selected bank name in the arrears modal
            document.getElementById('selected-bank-name-arrears').innerText = 'البنك المحدد: ' + selectedBankName;
        }

        // Function to clear bank name in arrears modal
        function clearSelectedBankNameInArrears() {
            document.getElementById('selected-bank-name-arrears').innerText = '';
        }


        // General function for other part of the page (outside the modal)
        function toggleBankModal(paymentMethod) {
            if (paymentMethod === 'بنكي') {
                var bankModal = new bootstrap.Modal(document.getElementById('bankModal'), {
                    keyboard: false
                });
                bankModal.show();

                // Add an event listener to ensure the bank selection updates properly
                document.getElementById('bank').addEventListener('change', selectBank);
            } else if (paymentMethod === 'نقدي') {
                clearSelectedBankName();
            }
        }

        function selectBank() {
            const bankSelect = document.getElementById('bank');
            const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
            const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
            
            // Set the hidden input with the selected bank ID in the general section
            document.getElementById('selected-bank-id').value = selectedBankId;
            
            // Update the display with the selected bank name
            document.getElementById('selected-bank-name').innerText = 'البنك المحدد: ' + selectedBankName;
        }

        function clearSelectedBankName() {
            document.getElementById('selected-bank-name').innerText = '';
        }



    </script>

    <script src="JS/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#searchInput').on('input', function() {
                const value = $(this).val().toLowerCase();
                $('#suspendedStudentsTableBody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().includes(value));
                });
            });
        });
    </script>

</body>
</html>
