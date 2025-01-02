<?php
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$bankList = [];
$sql = "SELECT account_id, bank_name FROM bank_accounts";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $bankList[] = $row;
}



$accountType = isset($_POST['account_type']) ? $_POST['account_type'] : 'موظف';
$transactions = [];

$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : null;
if ($endDate) {
    $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
}

if ($startDate && $endDate) {
    $dateFilter = " AND t.transaction_date BETWEEN '$startDate' AND '$endDate'";
    $dateTran = " AND et.transaction_date BETWEEN '$startDate' AND '$endDate'";
    $dateTranDon = " AND dt.transaction_description BETWEEN '$startDate' AND '$endDate'";

} else {
    $dateFilter = "";
    $dateTran = "";
    $dateTranDon = "";
}

switch ($accountType) {
    case 'موظف':
        $query = "SELECT t.id, e.full_name AS account_name, t.transaction_date AS tran_date, t.transaction_description, t.amount, t.bank_account_id, t.fund_id, t.employee_id, t.student_id, t.agent_id, t.transaction_type
                  FROM transactions t
                  JOIN employees e ON t.employee_id = e.id WHERE 1=1 $dateFilter ORDER BY t.id DESC";
        break;
    case 'صندوق':
        $query = "SELECT t.id, f.fund_name AS account_name, t.transaction_date AS tran_date, t.transaction_description, t.amount, t.bank_account_id, t.fund_id, t.employee_id, t.student_id, t.agent_id, t.transaction_type 
                  FROM transactions t
                  JOIN funds f ON t.fund_id = f.id WHERE 1=1 $dateFilter ORDER BY t.id DESC";
        break;
    case 'البنك':
        $query = "SELECT t.id, b.bank_name AS account_name, t.transaction_date AS tran_date, t.transaction_description, t.amount, t.bank_account_id, t.fund_id, t.employee_id, t.student_id, t.agent_id, t.transaction_type 
                  FROM transactions t
                  JOIN bank_accounts b ON t.bank_account_id = b.account_id WHERE 1=1 $dateFilter ORDER BY t.id DESC";
        break;
    case 'تلميذ':
        $query = "SELECT t.id, s.student_name AS account_name,t.transaction_date AS tran_date,  t.transaction_description, t.amount, t.bank_account_id, t.fund_id, t.employee_id, t.student_id, t.agent_id, t.transaction_type 
                  FROM transactions t
                  JOIN students s ON t.student_id = s.id WHERE 1=1 $dateFilter ORDER BY t.id DESC";
        break;
    case 'وكيل':
        $query = "SELECT t.id, a.agent_name AS account_name, t.transaction_date AS tran_date, t.transaction_description, t.amount, t.bank_account_id, t.fund_id, t.employee_id, t.student_id, t.agent_id, t.transaction_type 
                  FROM transactions t
                  JOIN agents a ON t.agent_id = a.agent_id WHERE 1=1 $dateFilter ORDER BY t.id DESC";
        break;
    case 'مصاريف': 
        $query = "SELECT et.id, ea.account_name AS account_name, et.transaction_date AS tran_date, et.transaction_description, et.amount, et.bank_id AS bank_account_id, NULL AS fund_id, et.bank_id AS employee_id, NULL AS student_id, NULL AS agent_id, 'minus' AS transaction_type
                  FROM expense_transaction et
                  JOIN expense_accounts ea ON et.expense_account_id = ea.id WHERE 1=1 $dateTran ORDER BY et.id DESC";
        break;
    case 'مداخيل': 
        $query = "SELECT dt.id, da.account_name AS account_name, dt.transaction_date AS tran_date, dt.transaction_description, dt.amount, dt.bank_id AS bank_account_id, NULL AS fund_id, NULL AS employee_id, NULL AS student_id, NULL AS agent_id, 'plus' AS transaction_type
                  FROM donate_transactions dt
                  JOIN donate_accounts da ON dt.donate_account_id = da.id WHERE 1=1 $dateTranDon ORDER BY dt.id DESC";
        break;
    default:
        $query = "SELECT t.id, e.full_name AS account_name, t.transaction_date AS tran_date, t.transaction_description, t.amount, t.bank_account_id, t.fund_id, t.employee_id, NULL AS student_id, NULL AS agent_id, '' AS transaction_type 
                  FROM transactions t
                  JOIN employees e ON t.employee_id = e.id WHERE 1=1 $dateFilter ORDER BY t.id DESC";
}

// Execute the query
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$months = [];


$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات حساب</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f9f9f9;
            color: #333;
            transition: all 0.3s ease-in-out;
        }

        .main-container {
            margin: 30px auto;
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-width: 1400px;
            transition: all 0.3s ease-in-out;
        }

        .header-title {
            font-size: 32px;
            font-weight: bold;
            color: #1BA078;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }

        .form-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        label {
            font-weight: bold;
            color: #1BA078;
            letter-spacing: 1px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 10px;
            border: 2px solid #1BA078;
            margin-top: 10px;
            transition: all 0.3s ease-in-out;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #14865b;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-select {
            background-color: #f9f9f9;
        }

        .table-container {
            margin-top: 30px;
            width: 100%;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 10px;
        }

        table th,
        table td {
            padding: 15px;
            font-size: 18px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #1BA078;
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        table td {
            background-color: #fff;
            color: #333;
        }

        table td.editable {
            cursor: pointer;
            transition: background-color 0.3s ease-in-out;
        }

        table td.editable:hover {
            background-color: #f1f1f1;
        }

        .edit-btn,
        .save-btn {
            background-color: #1BA078;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease-in-out;
        }

        .save-btn {
            background-color: #4CAF50;
            display: none;
        }

        .edit-btn:hover,
        .save-btn:hover {
            background-color: #14865b;
        }

        /* Custom Scrollbar Styling */
        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background-color: #1BA078;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-track {
            background-color: #f1f1f1;
        }

        /* New Home Button Styling */
        .home-button-container {
            margin-bottom: 20px;
        }

        .home-button-container .btn {
            background-color: #14865b;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 18px;
            transition: background-color 0.3s ease-in-out;
        }

        .home-button-container .btn:hover {
            background-color: #1BA078;
        }
    </style>
    <style>
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

        .months-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px; /* Space between items */
            justify-content: center; /* Center items horizontally */
        }

        .month-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .month-option input[type="checkbox"] {
            margin: 0;
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
            background-color: #dc3545; /* Darker green on hover */
        }
        .styled-button {
            background-color: #dc3545; /* Blue background */
            color: white; /* White text */
            padding: 8px 16px; /* Padding for size */
            border: none; /* Remove default border */
            border-radius: 8px; /* Rounded corners */
            font-size: 17px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth hover transition */
        }

        .styled-button:hover {
            transform: translateY(-3px); /* Slight lift on hover */
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
    <div class="container main-container">
        <!-- Home Button -->
        <div class="home-button-container text-start">
            <a href="home.php" class="btn">
                <i class="bi bi-house-fill"></i> الصفحة الرئيسية
            </a>
        </div>

        <h2 class="header-title"><i class="bi bi-pencil-square"></i> تعديل عملية حسابية</h2>
        <form method="POST" action="" class="row g-3 align-items-center">
            <!-- Start Date -->
            <div class="col-12 col-md-3">
                <label for="start_date" class="form-label">من تاريخ:</label>
                <input type="date" id="start_date" name="start_date" class="form-control">
            </div>

            <!-- End Date -->
            <div class="col-12 col-md-3">
                <label for="end_date" class="form-label">إلى تاريخ:</label>
                <input type="date" id="end_date" name="end_date" class="form-control">
            </div>

            <!-- Account Type -->
            <div class="col-12 col-md-3">
                <label for="account-type" class="form-label">نوعية الحساب:</label>
                <select id="account-type" name="account_type" class="form-select" onchange="this.form.submit();">
                    <option value="موظف" <?= (isset($_POST['account_type']) && $_POST['account_type'] == 'موظف') ? 'selected' : '' ?>>موظف</option>
                    <option value="صندوق" <?= (isset($_POST['account_type']) && $_POST['account_type'] == 'صندوق') ? 'selected' : '' ?>>صندوق</option>
                    <option value="البنك" <?= (isset($_POST['account_type']) && $_POST['account_type'] == 'البنك') ? 'selected' : '' ?>>البنك</option>
                    <option value="تلميذ" <?= (isset($_POST['account_type']) && $_POST['account_type'] == 'تلميذ') ? 'selected' : '' ?>>تلميذ</option>
                    <option value="وكيل" <?= (isset($_POST['account_type']) && $_POST['account_type'] == 'وكيل') ? 'selected' : '' ?>>وكيل</option>
                    <option value="مصاريف" <?= (isset($_POST['account_type']) && $_POST['account_type'] == 'مصاريف') ? 'selected' : '' ?>>مصاريف</option>
                    <option value="مداخيل" <?= (isset($_POST['account_type']) && $_POST['account_type'] == 'مداخيل') ? 'selected' : '' ?>>مداخيل</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="col-12 col-md-3 mt-4 text-md-end">
                <button type="submit" class="btn btn-success mt-4 w-100">تصفية</button>
            </div>
        </form>



        <!-- Table Section with Vertical Scroll -->
        <div class="search-box mb-4">
            <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم .....">
        </div>
        <div class="table-container mt-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>بتاريخ</th>
                        <th>اسم الحساب</th>
                        <th style="width: 60%">بيان العملية</th>
                        <th>المبلغ</th>
                        <th>تحرير</th>
                        <th>إلغاء وصل</th>
                    </tr>
                </thead>
                <tbody id="suspendedStudentsTableBody">
                    <?php foreach ($transactions as $index => $transaction) : 
                            if(preg_match('/الأشهر:\s*\{([^}]*)\}/', $transaction['transaction_description'], $matches)){
                                
                                $months = explode(', ', $matches[1]);
                            }elseif(preg_match('/الأشهر\s*\{\s*([^}]*)\s*\}/u', $transaction['transaction_description'], $matches)){
                                $months = explode(', ', $matches[1]);
                            }else {
                                $months = [];
                            }
                        ?>
                        <tr>
                            <td><?=  $index + 1 ?></td>
                            <td><?= $transaction['tran_date'] ?></td>
                            <td><?= $transaction['account_name'] ?></td>

                            <td class="editable">
                            <span class="transaction-description"><?= isset($transaction['transaction_description']) ? htmlspecialchars($transaction['transaction_description'], ENT_QUOTES, 'UTF-8') : 'وصف غير متوفر' ?></span>
                            <input type="text" class="transaction-input form-control" value="<?= $transaction['transaction_description'] ?>" style="display: none;">
                            </td>

                            <td class="editable">
                                <span class="transaction-amount"><?= number_format($transaction['amount'], 2) ?></span>
                                <input type="number" class="amount-input form-control" value="<?= $transaction['amount'] ?>" style="display: none;">
                                <input type="number" class="anc-amount-input form-control" value="<?= $transaction['amount'] ?>" style="display: none;">
                                <input type="text" class="employee_id form-control" value="<?= $transaction['employee_id'] ?>" style="display: none;">
                                <input type="text" class="st_id form-control" value="<?= $transaction['student_id'] ?>" style="display: none;">
                                <input type="text" class="ag_id form-control" value="<?= $transaction['agent_id'] ?>" style="display: none;">

                                <input type="number" class="type form-control" value="<?= $transaction['transaction_type'] ?>" style="display: none;">
                                <input type="number" class="fund_id form-control" value="<?= $transaction['fund_id'] ?>" style="display: none;">
                                <input type="number" class="bank_account_id form-control" value="<?= $transaction['bank_account_id'] ?>" style="display: none;">
                                <input type="text" class="transaction_description form-control" value="<?= $transaction['transaction_description'] ?>" style="display: none;">

                            </td>

                            <td>
                                <button class="btn btn-primary edit-btn" onclick="editTransaction(this, <?= $transaction['id'] ?>)">تعديل</button>
                                <button class="btn btn-success save-btn" style="display: none;" onclick="saveTransaction(this, <?= $transaction['id'] ?>)">حفظ</button>
                            </td>  
                            <td>
                                <button id="pay-arrears-button-<?php echo $index; ?>" 
                                        onclick="openArrearsModal(
                                            document.getElementById('student_id_<?php echo $index; ?>').value, 
                                            document.getElementById('total_amount_<?php echo $index; ?>').value,
                                            document.getElementById('tran_des_<?php echo $index; ?>').value,
                                            document.getElementById('employe_id_<?php echo $index; ?>').value,
                                            document.getElementById('agen_id_<?php echo $index; ?>').value,
                                            document.getElementById('studen_id_<?php echo $index; ?>').value,
                                            document.getElementById('mn_th_<?php echo $index; ?>').value,
                                            document.getElementById('trans_type_<?php echo $index; ?>').value
                                        )" 
                                        class="styled-button">إلغاء </button>
                                <input type="hidden" id="student_id_<?php echo $index; ?>" value="<?php echo $transaction['id']; ?>">
                                <input type="hidden" id="mn_th_<?php echo $index; ?>" value="<?php echo htmlspecialchars(implode(', ', $months), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" id="total_amount_<?php echo $index; ?>" value="<?php echo $transaction['amount']; ?>">
                                <input type="hidden" id="tran_des_<?php echo $index; ?>" value="<?php echo $transaction['transaction_description']; ?>">
                                <input type="hidden" id="employe_id_<?php echo $index; ?>" value="<?php echo $transaction['employee_id']; ?>">
                                <input type="hidden" id="agen_id_<?php echo $index; ?>" value="<?php echo $transaction['agent_id']; ?>">
                                <input type="hidden" id="studen_id_<?php echo $index; ?>" value="<?php echo $transaction['student_id']; ?>">
                                <input type="hidden" id="trans_type_<?php echo $index; ?>" value="<?php echo $transaction['transaction_type']; ?>">
    
                                <div id="arrears-modal" class="modal-overlay" style="display:none;">
                                    <div class="modal-content">
                                        <h2 style="text-align: center; color: #333;">إلغاء وصل</h2>
                                        <div class="months-card">
                                    <div class="section-title">الأشهر</div>
                                    <div class="months-grid" id="months-grid">
                                    </div>
                                        <input type="hidden" id="id_us" name="id">
                                        <input type="hidden" id="amo_unt_paid" name="amount_paid" value="">

                                        <div style="margin-bottom: 20px;">
                                            <label style="font-weight: bold;">---|---</label>
                                            <div id="total-arrears" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;"> أوقية جديدة</div>
                                        </div>
                                        
                                        <div style="margin-bottom: 20px;">
                                            <label for="amount_paid" style="font-weight: bold;">المبلغ المدفوع</label>
                                            <input type="text" id="amount_paid" name="amount_paid" required onchange="calculer()"       
                                                style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
                                        </div>
                                        
                                        <div style="margin-bottom: 20px;">
                                            <label for="remaining_amount" style="font-weight: bold;">الباقي</label>
                                            <div id="remaining_amount" style="background-color: #f1f1f1; padding: 12px; border-radius: 5px; color: #555; font-size: 1.1rem;">0.00 أوقية جديدة</div>
                                        </div>

                                        <div style="margin-bottom: 20px;">
                                            <label for="methodd" style="font-weight: bold;">طريقة الدفع</label>
                                            <select id="methodd" name="payment_method" onchange="toggleBankModalInArrears(this.value)" 
                                                    style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.1rem;">
                                                <option value="نقدي">نقدي</option>
                                                <option value="بنكي">بنكي</option>
                                            </select>
                                            <div id="selected-bank-name-arrears" style="margin-top: 15px; font-size: 1.1rem;"></div>
                                            <input type="hidden" id="selected-bank-id-arrears" name="bank">
                                            <input type="hidden" id="tran_des" value="">
                                            <input type="hidden" id="employe_id" value="">
                                            <input type="hidden" id="studen_id" value="">
                                            <input type="hidden" id="agen_id" value="">
                                            <input type="hidden" id="mnt_hs_ch" value="">
                                            <input type="hidden" id="transa_type" value="">

                                            <input type="hidden" id="accoun_type" value="<?php echo htmlspecialchars($accountType); ?>">

                                        </div>

                                        <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                                            <button class="confirm-button" 
                                                    onclick="deleteTransaction(
                                                        this,
                                                        document.getElementById('id_us').value,
                                                        document.getElementById('amount_paid').value,
                                                        document.getElementById('methodd').value,
                                                        document.getElementById('selected-bank-id-arrears').value,
                                                        document.getElementById('tran_des').value,
                                                        document.getElementById('employe_id').value,
                                                        document.getElementById('studen_id').value,
                                                        document.getElementById('agen_id').value,
                                                        document.getElementById('accoun_type').value,
                                                        document.getElementById('mnt_hs_ch').value,
                                                        document.getElementById('transa_type').value

                                                    )" 
                                                    style="padding: 12px 25px; background-color: #28a745; color: white; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">تأكيد العملية</button>
                                            <button type="button" onclick="closeArrearsModal()" 
                                                    style="padding: 12px 25px; background-color: #dc3545; color: white; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">إغلاق</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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


    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        // Function to enable editing of a row
        function editTransaction(button, id) {
            const row = button.closest('tr');
            row.querySelector('.transaction-input').style.display = 'block';
            row.querySelector('.amount-input').style.display = 'block';
            row.querySelector('.transaction-description').style.display = 'none';
            row.querySelector('.transaction-amount').style.display = 'none';
            row.querySelector('.save-btn').style.display = 'inline-block';
            row.querySelector('.edit-btn').style.display = 'none';
        }

        function saveTransaction(button, id) {
            const row = button.closest('tr');
            const newDescription = row.querySelector('.transaction-input').value;
            const newAmount = row.querySelector('.amount-input').value;
            const ancAmount = row.querySelector('.anc-amount-input').value;

            const employee_id = row.querySelector('.employee_id').value;
            const ty_pe = row.querySelector('.type').value;
            const bank_account_id = row.querySelector('.bank_account_id').value;
            const fund_id = row.querySelector('.fund_id').value;
            const t_n = row.querySelector('.transaction_description').value;
            const agent_id = row.querySelector('.ag_id').value;
            const student_id = row.querySelector('.st_id').value;



            if (newDescription === '' || newAmount === '') {
                Swal.fire({ icon: 'error', title: 'خطأ', text: 'الرجاء إدخال جميع البيانات المطلوبة.' });
                return;
            }

            const formData = new FormData();
            formData.append('id', id);
            formData.append('transaction_description', newDescription);
            formData.append('t_n', t_n);
            formData.append('amount', newAmount);
            formData.append('ancAmount', ancAmount);
            formData.append('employee_id', employee_id);
            formData.append('type', ty_pe);
            formData.append('student_id', student_id);
            formData.append('agent_id', agent_id);
            formData.append('bank_account_id', bank_account_id);
            formData.append('fund_id', fund_id);
            formData.append('account_type', document.getElementById('account-type').value);

            fetch('update_transaction.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.querySelector('.transaction-description').textContent = newDescription;
                        row.querySelector('.transaction-amount').textContent = parseFloat(newAmount).toFixed(2);
                        row.querySelector('.transaction-input').style.display = 'none';
                        row.querySelector('.amount-input').style.display = 'none';
                        row.querySelector('.transaction-description').style.display = 'block';
                        row.querySelector('.transaction-amount').style.display = 'block';
                        row.querySelector('.save-btn').style.display = 'none';
                        row.querySelector('.edit-btn').style.display = 'inline-block';

                        Swal.fire({ icon: 'success', title: 'تم تحديث العملية بنجاح', timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'فشل في التحديث', text: 'حدث خطأ أثناء محاولة تحديث العملية.' });
                    }
                })
                .catch(error => Swal.fire({ icon: 'error', title: 'فشل في التحديث', text: 'حدث خطأ أثناء محاولة تحديث العملية.' }));
        }
    </script>

    <script>
        
            function deleteTransaction(button, id, amount, payment_method, bank_account_id, tran_des, employee_id, student_id, agent_id, account_type, months, transac_type) {
            if (confirm('هل أنت متأكد أنك تريد إلغاء هذه العملية؟')) {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('amount', amount);
                formData.append('payment_method', payment_method);
                formData.append('bank_account_id', bank_account_id);
                formData.append('t_n', tran_des);
                formData.append('employee_id', employee_id);
                formData.append('student_id', student_id);
                formData.append('agent_id', agent_id);
                formData.append('account_type', account_type);
                formData.append('months', months);
                formData.append('transac_type', transac_type);
                


                fetch('delete_transaction.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم إلغاء العملية بنجاح',
                                timer: 1500,
                                showConfirmButton: false,
                            }).then(() => {
                                if (data.receipts_id) {
                                    window.location.href = `print_receipt_return.php?receipt_id=${data.receipts_id}`;
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'فشل في إلغاء',
                                text: data.message || 'حدث خطأ أثناء محاولة إلغاء العملية.',
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'فشل في إلغاء',
                            text: 'حدث خطأ أثناء محاولة إلغاء العملية.',
                        });
                    });
            }
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





<script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/sweetalert2.min.js"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>


    <script>
        function openArrearsModal(id, tot_am, tran_des, employe_id, agen_id, studen_id, mnths, tr_type) {
                document.getElementById('arrears-modal').style.display = 'flex';
                document.getElementById('total-arrears').innerText = tot_am + ' أوقية جديدة';
                document.getElementById('id_us').value = id;
                document.getElementById('amo_unt_paid').value = tot_am;
                document.getElementById('mnt_hs_ch').value = mnths;

                document.getElementById('tran_des').value = tran_des;
                document.getElementById('employe_id').value = employe_id;
                document.getElementById('agen_id').value = agen_id;
                document.getElementById('studen_id').value = studen_id;
                document.getElementById('transa_type').value = tr_type;

                const monthsArray = mnths.split(', ');

                const monthsGrid = document.getElementById('months-grid');
                monthsGrid.innerHTML = '';

                const updateCheckedMonths = () => {
                    const checkedMonths = Array.from(monthsGrid.querySelectorAll('input[type="checkbox"]:checked'))
                        .map(checkbox => checkbox.value);
                    document.getElementById('mnt_hs_ch').value = checkedMonths.join(', ');
                };

    monthsArray.forEach((monthName) => {
        // Debug: Check each month being processed
        console.log('Processing month:', monthName);

        // Create a checkbox input
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'month[]';
        checkbox.value = monthName;
        checkbox.checked = true;
        checkbox.addEventListener('change', updateCheckedMonths);

        const label = document.createElement('label');
        label.textContent = monthName;

        // Create a container div for the checkbox and label
        const monthOption = document.createElement('div');
        monthOption.className = 'month-option';
        monthOption.appendChild(checkbox);
        monthOption.appendChild(label);

        // Append the container div to the months-grid
        monthsGrid.appendChild(monthOption);
    });


    document.getElementById('mnt_hs').value = monthsArray.join(', ');

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
