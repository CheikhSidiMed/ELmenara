<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: ../home.php");
    exit;
}


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

$paidMonths = [];
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['garant_id'])) {
    $garant_id = $_GET['garant_id'];

    $sql1 = "SELECT g.id, g.name, g.phone, g.amount_sponsored, g.balance, g.donate_id, d.account_name, d.account_number
    FROM garants AS g
    LEFT JOIN donate_accounts AS d ON g.donate_id = d.id
    WHERE g.id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param('s', $garant_id);
    $stmt1->execute();

    $result1 = $stmt1->get_result()->fetch_assoc();
    $stmt1->close();

    

    $sql = "SELECT payment_id, month, des FROM stock_monthly_payments WHERE garant_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $garant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $paidMonths[] = $row['month'];
    }
    $paidMonths = array_unique($paidMonths);


    if ($result1) {
        json_encode($result1);
    }
    $stmt->close();
}




$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
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
    <title>Account Transaction</title>
    <link href="../css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link href="../css/bootstrap-icons.css" rel="stylesheet">
    <link href="../fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/jquery-base-ui.css">
    <link rel="stylesheet" href="../css/sweetalert2.css">
    <script src="../js/sweetalert2.min.js"></script>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
            direction: rtl;
            text-align: right;
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
            font-weight: bold;
            color: #1a73e8;
            font-size: 1rem;
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
            color: #1a73e8;
        }
        .form-select {
            font-size: 1rem;
            font-weight: bold;
            color: #1a73e8;
        }
        .input-group input {
            border-radius: 5px;
            padding: 10px;
            border: 2px solid #1a73e8;
        }
        .input-group button {
            border-radius: 5px;
            background-color: #1a73e8;
            color: white;
            border: 2px solid #1a73e8;
        }
        .form-section {
            margin-bottom: 20px;
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
        .amount-display {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
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
            width: 100%;
        }
        .confirm-button:hover {
            background-color: #218838;
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
        .method-section input[type="radio"] {
            display: none;
        }
        .method-section label {
            font-size: 1.1rem;
            color: #333;
            cursor: pointer;
        }
        .method-section label .fa-check-circle {
            margin-left: 10px;
            font-size: 1.5rem;
            color: #1a73e8;
            display: none;
        }
        .method-section input[type="radio"]:checked + label .fa-check-circle {
            display: block;
        }
        .method-section input[type="radio"]:checked + label {
            color: #1a73e8;
        }
        .info-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 15px;
        }
        .info-container div {
            display: flex;
            align-items: center;
        }
        .info-container label {
            font-size: 1.3rem;
            font-weight: bold;
            margin-right: 10px;
            white-space: nowrap;
        }
        .info-container i {
            font-size: 2rem;
            margin: 0 10px;
        }
    </style>
</head>
<body>

<div class="container-main">
    <!-- Header Section -->
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
    <h1 class="header-title"><i class="icon-left bi bi-file-earmark-text"></i>   تسجيل عملية حسابية للتبرعات </h1>
        <div class="d-flex align-items-center">
            <a href="home.php" class="btn btn-primary d-flex align-items-center" style="margin-left: 15px;">
                <i class="bi bi-house-door-fill" style="margin-right: 5px;"></i>
                الرئيسية
            </a>
            <label class="form-select-title" for="financial-year" style="margin-left: 15px;">السنة المالية</label>
            <select id="financial-year" class="form-select w-100">
                <option><?php echo $last_year; ?></option>
            </select>
        </div>
    </div>
</div>

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="header-titlee">البحث عن الكافل(ة)</h4>
            <form method="GET" action="">
            <input type="hidden" name="garant_id" class="form-control" id="garant-id">
                <div class="input-group">
                    <input type="text" id="account_search" name="account_search" class="form-control" placeholder="ابحث باسم أو رقم الكافل(ة)">
                    <button class="btn btn-outline-secondary border-2" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Info Section -->
    <div class="row form-section">
        <div class="col-12 row-underline">
            <h4 class="section-title">معلومات الكافل(ة):</h4>
        </div>
        <hr>
        <div class="row text-center">
            <div class="col-6 col-lg-3">
                <label>إسم الكافل(ة)</label>
                <div id=""><?php echo $result1['name']; ?></div>
            </div>
            <div class="col-6 col-lg-3">
                <label>رقم الهاتف</label>
                <div id=""><?php echo $result1['phone']; ?></div>
            </div>
            <div class="col-6 col-lg-3">
                <label>الرصيد</label>
                <div id=""><?php echo $result1['balance']; ?></div>
            </div>
            <div class="col-6 col-lg-3">
                <label>الحساب المتكفل به</label>
                <div><?php echo $result1['account_name']; ?></div>
            </div>
        </div>
        <hr class="mt-3">
    </div>


    <form method="POST" action="process_donation.php">
        <input type="hidden" name="garant_id" id="garant_id" value="<?php echo $result1['id']; ?>">
        <input type="hidden" name="account_name" id="account_name" value="<?php echo $result1['account_name']; ?>">
        <input type="hidden" name="name" id="name" value="<?php echo $result1['name']; ?>">

        <div class="row form-section">
    
            <div style="border: 1px solid #ddd;" class="col-12 col-lg-5  text-center  border border-light bg-light months-card">
                <div class="section-title" style="font-size: 22px;">الأشهر</div>
                <div class="months-grid text-center">
                    <?php foreach ($allMonths as $monthKey => $monthName): ?>
                    <div class="month-option text-center">
                        <input type="checkbox" name="month[]" style="margin-left: 5px; margin-bottom: 4px;" value="<?php echo $monthName; ?>"
                        <?php if (in_array($monthName, $paidMonths)) echo 'checked disabled'; ?>>
                        <label><?php echo $monthName; ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-12 col-lg-7">
                <div style="border: 1px solid #ddd;" class="payment-info">
                    <div class="">
                    <div class="form-group">
                        <label for="due-amount">المستحقات</label>
                        <input type="text" name="due_amount" id="due-amount" value="0.00" readonly>
                        <input type="hidden" id="remaining-fee" value="0.00">

                    </div>
                    <div class="form-group">
                        <label for="paid-amount">المبلغ المسدد</label>
                        <input type="text" name="paid_amount" id="arrears-paid" placeholder="0.00" oninput="calculateRemaining()">
                    </div>
                    <div class="form-group">
                        <label for="remaining-amount">الباقي</label>
                        <input type="text" name="remaining_amount" id="arrears-remaining" placeholder="0.00" readonly>
                    </div>
                    </div>
                    <div  class="payment-info">
                        <label for="method" class="form-label" style="font-weight: bold; color: #1a73e8;">طريقة الدفع <span style="color:red;">*</span></label>
                        <select id="method" name="payment_method" class="form-select form-select-lg mb-3" onchange="toggleBankModal(this.value)" style="border: 2px solid #1a73e8; border-radius: 5px;" required>
                            <option value="">اختر طريقة الدفع</option>
                            <option value="نقدي">نقدي</option>
                            <option value="بنكي">بنكي</option>
                        </select>
                    </div>
                </div>
            </div>

        <div class="text-center mt-4">
            <button class="btn btn-primary" type="submit">تأكيد العملية</button>
        </div>
    </form>



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

<script src="../js/jquery-3.5.1.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/jquery-ui.min.js"></script>

<script>
    function calculateRemaining() {
        var dueAmount = parseFloat(document.getElementById('due-amount').value) || 0;
        var paidAmount = parseFloat(document.getElementById('arrears-paid').value) || 0;
        var remainingAmount = dueAmount - paidAmount;
        document.getElementById('arrears-remaining').value = remainingAmount.toFixed(2);
    }
</script>

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
        
        document.getElementById('selected-bank-id').value = selectedBankId;
        document.getElementById('selected-bank-name').innerText = 'البنك المحدد: ' + selectedBankName;
    }

    function clearSelectedBankName() {
        document.getElementById('selected-bank-name').innerText = '';
    }
</script>

<script src="../js/jquery-3.5.1.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/jquery-ui.min.js"></script>

<script>
$(function() {
    $("#account_search").autocomplete({
        source: function(request, response) {
            console.log("Recherche envoyée:", request.term);
            $.ajax({
                url: 'fetch_garants.php',
                dataType: 'json',
                data: { term: request.term },
                success: function(data) {
                    response(data);
                },
                error: function(xhr, status, error) {
                    console.error("Erreur AJAX:", status, error);
                    console.log(xhr.responseText);
                }
            });
        },
        minLength: 1,
        select: function(event, ui) {
            $('#garant-id').val(ui.item.id);
            $("#account_search").closest("form").submit();
        }
    });
});


document.getElementById('due-amount').value = (parseFloat(<?php echo $result1['amount_sponsored']; ?>) || 0).toFixed(2);
document.getElementById('remaining-fee').value = (parseFloat(<?php echo $result1['amount_sponsored']; ?>) || 0).toFixed(2);
document.addEventListener('DOMContentLoaded', function () {

    const dueAmountDisplay = document.getElementById('due-amount'); // Ensure this is a span or div
    const checkboxes = document.querySelectorAll('.months-card input[type="checkbox"]');

    function calculateTotalDue() {
        let selectedMonths = 0;
        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked && !checkbox.disabled) {
                selectedMonths++;
            }
        });

        const remainingFeeElement = document.getElementById('remaining-fee');
        if (!remainingFeeElement) return; // Ensure the element exists

        const remainingFee = parseFloat(remainingFeeElement.value) || 0; // Convert to number
        const totalDue = remainingFee * selectedMonths;
console.log(totalDue);
        if (dueAmountDisplay) {
            // dueAmountDisplay.value = totalDue;
            document.getElementById('due-amount').value = (parseFloat(totalDue) || 0).toFixed(2);

        }
        // if (dueAmountHidden) {
        //     dueAmountHidden.value = totalDue;
        // }
    }

    checkboxes.forEach(function (checkbox) {
        if (!checkbox.disabled) {
            checkbox.addEventListener('change', calculateTotalDue); // Use 'change' instead of 'click'
        }
    });

    calculateTotalDue(); // Call once on page load
});


</script>




</body>
</html>
