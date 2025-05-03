<?php
// Include database connection
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
    <title>  تسجيل عملية مقدمي الخدمات  </title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css">
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
            border: 1px solid green;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 1100px;
            margin: auto;
        }
        .ser{
            border-radius: 8px 0px 0px 8px !important;
            margin-right: -2px;
            border: 1px solid blue !important;
            text-align: center !important;
        }
        #employee_search:focus {
            outline: none !important;
            box-shadow: none !important;
            border: 2px solid blue !important; /* Optional: Keeps a normal border */
        }
        .header-title {
            font-family: 'Tajawal', serif;
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

        @media (max-width: 768px) {
            body {
            padding: 4px;
            }
            .container-main {
                width: 100%;
                padding: 15px;
            }
            
            .method-section label {
                font-size: .5rem;
            }
            .form-section label, #lbd {
                font-size: 1rem;
            }
            h4 {
                font-size: 16px !important;
            }
            .months-card, .snd{
                margin-right: -.5rem !important;
                width: 100% !important;
            }
            .wdth{
                width: 100% !important;
            }

            h1 {
                font-size: 19px !important;
            }
        }
    </style>
</head>
<body>

<div class="container-main">
    <!-- Header Section -->
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
    <h1 class="header-title"><i class="icon-left bi bi-file-earmark-text"></i>    تسجيل عملية مقدمي الخدمات </h1>
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

  <!-- Search Section -->
  <div class="row mb-4">
        <div class="col-12">
            <h2 class="header-titlee">البحث عن الحساب</h2>
            <form method="GET" action="">
                <div class="input-group">
                    <input type="text" id="account_search" name="account_search" class="form-control" placeholder="ابحث باسم الحساب أو رقم الحساب">
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
            <h2 class="section-title">معلومات الحساب:</h2>
        </div>
        <div class="col-12 d-flex justify-content-between text-center">
            <div>
                <label>رقم الحساب</label>
                <div id="account_number_display"></div>
            </div>
            <div>
                <label>إسم الحساب</label>
                <div id="account_name_display"></div>
            </div>

            <div>
                <label>الفئة</label>
                <div id="category_display"></div>
            </div>
            <div>
                <label>الرصيد</label>
                <div id="account_balance_display"></div>
            </div>
        </div>
    </div>



<!-- Start the Form -->
<form method="POST" action="process_offers.php">
    <input type="hidden" name="expense_account_id" id="expense_account_id" value="">
    <input type="hidden" name="expense_account_name" id="expense_account_name" value="">
    <input type="hidden" name="transaction_type" id="transaction_type" value="">


    <!-- Transaction Information Section -->
    <div class="row form-section">
        <div class="col-12 row-underline">
            <h2 class="section-title">معلومات العملية</h2>
        </div>
        <div class="info-container mt-2">
            <div>
                <label>نوع العملية <span style="color:red;">*</span></label>
                <div>
                    <label for="transaction_type_plus" style="cursor: pointer;">
                        <i id="plusIcon" class="bi bi-plus-circle-fill" style="color: green; font-size: 2rem;"></i>
                    </label>
                    <input type="radio" name="transaction_type_radio" id="transaction_type_plus" value="plus" style="display:none;" required>

                    <label for="transaction_type_minus" style="cursor: pointer;">
                        <i id="minusIcon" class="bi bi-dash-circle-fill" style="color: red; font-size: 2rem;"></i>
                    </label>
                    <input type="radio" name="transaction_type_radio" id="transaction_type_minus" value="minus" style="display:none;" required>
                </div>
            </div>
        </div>
        <div style="border: 1px solid #ddd;" class="payment-info d-flex flex-column flex-sm-row gap-6">
                <div class="mt-2 w-50 wdth">
                    <label>المبلغ <span style="color:red; ">*</span></label>
                    <input type="text" id="amount" name="amount" class="form-control mb-3" value="" style="width: 95%; text-align: center;" required>
                    <div style="width: 95%;">
                        <div class="d-flex flex-column flex-sm-row gap-6">
                            <select style="width: 100%;" id="method" name="payment_method" class="form-select form-select-lg mb-3" onchange="toggleBankModal(this.value)" style="border: 2px solid #1a73e8; border-radius: 5px;" required>
                                <option value="">اختر طريقة الدفع</option>
                                <option value="نقدي">نقدي</option>
                                <option value="بنكي">بنكي</option>
                            </select>
                            <input type="datetime-local" id="date_time" name="date_time" class="form-control mb-3 me-2" required/>
                            </div>
                        <div id="selected-bank-name" style="margin-top: 15px; font-weight: bold; color: #1a73e8; text-align: center; font-size: 1.2rem;"></div>
                        <input type="hidden" id="selected-bank-id" name="bank">
                    </div>
                </div>
                <div class="mt-2 w-50 wdth">
                    <label for="transaction_description">بيان العملية <span style="color:red;">*</span></label>
                    <textarea type="text" rows="3" id="transaction_description" name="transaction_description" class="form-control" value="" style="width: 98%;background-color: white; padding: 5px; border-radius: 5px;" required></textarea>
                </div>
            </div>
            <div class="text-center mt-4">
                <button class="confirm-button" type="submit">تأكيد العملية</button>
            </div>
        </div>
    </div>


</form>
<!-- End the Form -->




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
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/jquery-3.5.1.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script>
    document.getElementById('plusIcon').addEventListener('click', function() {
        toggleIconSelection('plus');
        document.getElementById('transaction_type').value = 'plus';
        document.getElementById('transaction_type_plus').checked = true;
    });

    document.getElementById('minusIcon').addEventListener('click', function() {
        toggleIconSelection('minus');
        document.getElementById('transaction_type').value = 'minus';
        document.getElementById('transaction_type_minus').checked = true;
    });

    function toggleIconSelection(type) {
        // Reset styles
        document.getElementById('plusIcon').style.transform = '';
        document.getElementById('minusIcon').style.transform = '';
        document.getElementById('plusIcon').style.opacity = '0.6';
        document.getElementById('minusIcon').style.opacity = '0.6';

        // Apply styles to selected icon
        if (type === 'plus') {
            document.getElementById('plusIcon').style.transform = 'scale(1.2)';
            document.getElementById('plusIcon').style.opacity = '1';
        } else if (type === 'minus') {
            document.getElementById('minusIcon').style.transform = 'scale(1.2)';
            document.getElementById('minusIcon').style.opacity = '1';
        }
    }

    function validateForm() {
        var transactionType = document.getElementById('transaction_type').value;
        if (!transactionType) {
            Swal.fire({
                icon: 'error',
                title: 'نوع العملية مطلوب',
                text: 'يرجى اختيار نوع العملية قبل المتابعة.'
            });
            return false;
        }
        return true;
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
   $(function() {
    $("#account_search").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'fetch_accounts_offers.php',
                dataType: 'json',
                data: { term: request.term },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 1,
        select: function(event, ui) {
            // Populate the divs with the selected account's data
            $('#account_number_display').text(ui.item.account_number);
            $('#account_name_display').text(ui.item.account_name);
            $('#expense_account_name').val(ui.item.account_name);
            $('#category_display').text(ui.item.category);
            $('#account_balance_display').text(ui.item.account_balance);
            
            // Populate the hidden input with the selected account ID
            console.log("Selected id: ", ui.item.id); // Log the account ID for debugging
            $('#expense_account_id').val(ui.item.id); // This is the key step to fix the issue
        }
    });
});



</script>




</body>
</html>
