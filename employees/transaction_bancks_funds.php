<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

session_start();

if (!isset($_SESSION['userid'])) {
    die("Error: User is not logged in.");
}

$user_id = $_SESSION['userid'];

// Traitement du formulaire
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sourceType = $_POST['source_type'];
    $sourceId = $_POST['source_id'];
    $sourceN = $_POST['source_name'] ?? '';
    $destinationN = $_POST['destination_name'] ?? '';
    $destinationType = $_POST['destination_type'];
    $destinationId = $_POST['destination_id'];
    $desc = $_POST['desc'];
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        $message = "المبلغ يجب أن يكون أكبر من الصفر.";
    } else {
        // Validation des soldes
        $sourceTable = $sourceType === 'bank_account' ? 'bank_accounts' : 'funds';
        $sourceTableId = $sourceType === 'bank_account' ? 'account_id' : 'id';

        $destinationTable = $destinationType === 'bank_account' ? 'bank_accounts' : 'funds';
        $destinationTableId = $destinationType === 'bank_account' ? 'account_id' : 'id';

        $destIdBnck = $destinationType === 'bank_account' ? $sourceId : null;
        $destIdFund = $destinationType === 'bank_account' ? null : $sourceId;

        $sourIdBnck = $sourceType === 'bank_account' ? $destinationId : null;
        $sourIdFund = $sourceType === 'bank_account' ? null : $destinationId;

        // Récupérer le solde source
        $sourceSQL = "SELECT balance FROM $sourceTable WHERE $sourceTableId = ?";
        $stmt = $conn->prepare($sourceSQL);
        $stmt->bind_param('i', $sourceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $sourceBalance = $result->fetch_assoc()['balance'];

        if ($sourceBalance < $amount) {
            $message = "الرصيد غير كافٍ لإجراء هذه المعاملة.";
        } else {
            // Mettre à jour les soldes
            $conn->begin_transaction();
            try {
                $updateSourceSQL = "UPDATE $sourceTable SET balance = balance - ? WHERE $sourceTableId = ?";
                $stmt = $conn->prepare($updateSourceSQL);
                $stmt->bind_param('di', $amount, $sourceId);
                $stmt->execute();

                $updateDestinationSQL = "UPDATE $destinationTable SET balance = balance + ? WHERE $destinationTableId = ?";
                $stmt = $conn->prepare($updateDestinationSQL);
                $stmt->bind_param('di', $amount, $destinationId);
                $stmt->execute();

                
                $des = "نقل الأموال من حساب " . $sourceN .  " الى حساب " . $destinationN .' { ' . $desc . ' }';  


                $stmt = $conn->prepare("INSERT INTO receipts (receipt_date, total_amount, receipt_description, created_by)
                    VALUES (NOW(), ?, ?, ?)");
                $stmt->bind_param("sss", $amount,  $des, $user_id);
                $stmt->execute();
                $receipts_d = $conn->insert_id;

                $stmt = $conn->prepare("INSERT INTO combined_transactions (type, description, paid_amount, payment_method, bank_id, user_id, fund_id)
                    VALUES ('minus', ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $des, $amount, $destinationType, $sourIdBnck, $user_id, $sourIdFund);
                $stmt->execute();
                $transaction_d = $conn->insert_id;

                $stmt = $conn->prepare("
                INSERT INTO receipt_payments (receipt_id, transaction_id)
                VALUES (?, ?)
                ");
                $stmt->bind_param("ss", $receipts_d, $transaction_d);
                $stmt->execute();

                $stmt_reg_fee = $conn->prepare("
                INSERT INTO transactions (transaction_description, amount, transaction_type, fund_id, bank_account_id, user_id)
                VALUES ('$des', ?, 'minus', ?, ?, ?)
                ");
                $stmt_reg_fee->bind_param("diii", $amount, $sourIdFund, $sourIdBnck, $user_id);
                $stmt_reg_fee->execute();
                $stmt_reg_fee->close();




                $stmt = $conn->prepare("
                    INSERT INTO receipts (receipt_date, total_amount, receipt_description, created_by)
                    VALUES (NOW(), ?, ?, ?)
                ");
                $stmt->bind_param("sss", $amount,  $des, $user_id);
                $stmt->execute();
                $receipts_id = $conn->insert_id;

                $stmt = $conn->prepare("
                INSERT INTO combined_transactions (type, description, paid_amount, payment_method, bank_id, user_id, fund_id)
                VALUES ('plus', ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param("ssssss", $des, $amount, $destinationType, $destIdBnck, $user_id, $destIdFund);
                $stmt->execute();
                // Retrieve the last inserted transaction ID
                $transaction_id = $conn->insert_id;

                // Insert into `receipt_payments`
                $stmt = $conn->prepare("
                INSERT INTO receipt_payments (receipt_id, transaction_id)
                VALUES (?, ?)
                ");
                $stmt->bind_param("ss", $receipts_id, $transaction_id);
                $stmt->execute();

                $stmt_reg_fee = $conn->prepare("
                    INSERT INTO transactions (transaction_description, amount, transaction_type, fund_id, bank_account_id, user_id)
                    VALUES ('$des', ?, 'plus', ?, ?, ?)
                ");
                $stmt_reg_fee->bind_param("diii", $amount, $destIdFund, $destIdBnck, $user_id);
                $stmt_reg_fee->execute();
                $stmt_reg_fee->close();

                $conn->commit();

                $message = "تمت المعاملة بنجاح!";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "فشلت المعاملة: " . $e->getMessage();
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقل الأموال</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/jquery-3.5.1.min.js"></script>
    <style>
        body {
            direction: rtl;
            background-color: #f8f9fa;
            font-family: 'Cairo', sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .btn-home {
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            padding: 10px 15px;
        }
        .btn-home:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">نقل الأموال</h2>
        <?php if (!empty($message)) : ?>
            <div class="alert alert-info text-center"><?= $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <!-- مصدر -->
            <div class="mb-3">
                <label for="source_type" class="form-label">حساب المصدر</label>
                <select id="source_type" name="source_type" class="form-select" required>
                    <option value="">-- اختر --</option>
                    <option value="bank_account">حساب بنكي</option>
                    <option value="fund">صندوق</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="source_id" class="form-label">حساب المصدر</label>
                <select id="source_id" name="source_id" class="form-select" required>
                    <option value="">-- اختر حساب المصدر أولاً --</option>
                </select>
            </div>
            <input type="hidden" id="source_name" name="source_name">
            <input type="hidden" id="destination_name" name="destination_name">

            <!-- وجهة -->
            <div class="mb-3">
                <label for="destination_type" class="form-label">حساب الوجهة</label>
                <select id="destination_type" name="destination_type" class="form-select" required>
                    <option value="">-- اختر --</option>
                    <option value="bank_account">حساب بنكي</option>
                    <option value="fund">صندوق</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="destination_id" class="form-label">رقم الوجهة</label>
                <select id="destination_id" name="destination_id" class="form-select" required>
                    <option value="">-- اختر نوع الوجهة أولاً --</option>
                </select>
            </div>

            <!-- مبلغ -->
            <div class="mb-3">
                <label for="amount" class="form-label">المبلغ</label>
                <input type="text" id="amount" name="amount" step="0.01" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="desc" class="form-label">الوصف</label>
                <input type="text" id="desc" name="desc" class="form-control" required>
            </div>
            
            <!-- Actions -->
            <div class="d-flex justify-content-between align-items-center">
                <button type="submit" class="btn btn-success">إجراء المعاملة</button>
                <a href="home.php" class="btn-home">الصفحة الرئيسية</a>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            // Mise à jour dynamique pour نوع المصدر -> رقم المصدر
            $('#source_type').change(function () {
                const sourceType = $(this).val();
                const sourceIdField = $('#source_id');
                sourceIdField.html('<option value="">-- يتم التحميل... --</option>');

                if (sourceType) {
                    $.getJSON('get_options.php', { type: sourceType }, function (data) {
                        sourceIdField.html('<option value="">-- اختر --</option>');
                        $.each(data, function (key, value) {
                            sourceIdField.append(`<option value="${value.id}">${value.name}</option>`);
                        });
                    });
                } else {
                    sourceIdField.html('<option value="">-- اختر نوع المصدر أولاً --</option>');
                }
            });

            // Mise à jour dynamique pour نوع الوجهة -> رقم الوجهة
            $('#destination_type').change(function () {
                const destinationType = $(this).val();
                const destinationIdField = $('#destination_id');
                destinationIdField.html('<option value="">-- يتم التحميل... --</option>');

                if (destinationType) {
                    $.getJSON('get_options.php', { type: destinationType }, function (data) {
                        destinationIdField.html('<option value="">-- اختر --</option>');
                        $.each(data, function (key, value) {
                            destinationIdField.append(`<option value="${value.id}">${value.name}</option>`);
                        });
                    });
                } else {
                    destinationIdField.html('<option value="">-- اختر نوع الوجهة أولاً --</option>');
                }
            });
        });
    </script>

    <script>
        $(document).ready(function () {
            $('#source_id').change(function () {
                const selectedOption = $(this).find('option:selected').text();
                $('#source_name').val(selectedOption); 
            });

            $('#destination_id').change(function () {
                const selectedOption = $(this).find('option:selected').text();
                $('#destination_name').val(selectedOption); 
            });
        });
    </script>
</body>
</html>

