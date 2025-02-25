<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    include '../db_connection.php';

if ($conn->connect_error) {
    die("Échec de connexion : " . $conn->connect_error);
}

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: ../home.php");
    exit;
}

$message = "";
$editParrainer = null;
$selectedDonation = 'v';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $name = trim($_POST['name'] ?? '');
    $balance = trim($_POST['balance'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $donate_id = trim($_POST['donate_id'] ?? '');
    $amount_sponsored = trim($_POST['amount_sponsored'] ?? '');
    $parrainer_id = $_POST['parrainer_id'] ?? null;

    if ($action == 'add') {
        if (!empty($name) && is_numeric($balance)) {

            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM garants WHERE phone = ?");
            $checkStmt->bind_param("s", $phone);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                $message = "رقم الهاتف موجود بالفعل. الرجاء إدخال رقم هاتف آخر.";
            } else {
                $stmt = $conn->prepare("INSERT INTO garants (name, balance, phone, amount_sponsored, donate_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $balance, $phone, $amount_sponsored, $donate_id);
                if ($stmt->execute()) {
                    $message = "تمت إضافة الكفيل(ة) بنجاح.";
                } else {
                    $message = "حدث خطأ أثناء إضافة الكفيل(ة).";
                }
                $stmt->close();
            }
        } else {
            $message = "الرجاء إدخال جميع البيانات بشكل صحيح.";
        }
    } elseif ($action == 'delete') {
        if (is_numeric($parrainer_id)) {
            $stmt = $conn->prepare("DELETE FROM garants WHERE id=?");
            $stmt->bind_param("i", $parrainer_id);
            if ($stmt->execute()) {
                $message = "تم حذف الكفيل(ة) بنجاح.";
            } else {
                $message = "حدث خطأ أثناء حذف الكفيل(ة).";
            }
            $stmt->close();
        }
    } elseif ($action == 'edit') {
        if (is_numeric($parrainer_id)) {
            $stmt = $conn->prepare("SELECT * FROM garants WHERE id = ?");
            $stmt->bind_param("i", $parrainer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $editParrainer = $result->fetch_assoc();
            $selectedDonation = $editParrainer['donate_id'];
            $stmt->close();
        }
    } elseif ($action == 'update') {
        if (is_numeric($parrainer_id) && !empty($name) && is_numeric($balance)) {
            $stmt = $conn->prepare("UPDATE garants SET donate_id = ?, name = ?, balance = ?, phone = ?, amount_sponsored = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $donate_id, $name, $balance, $phone, $amount_sponsored, $parrainer_id);
            if ($stmt->execute()) {
                $message = "تم تحديث بيانات الكفيل(ة) بنجاح.";
            } else {
                $message = "حدث خطأ أثناء تحديث بيانات الكفيل(ة).";
            }
            $stmt->close();
        } else {
            $message = "الرجاء إدخال جميع البيانات بشكل صحيح.";
        }
    }
}

$result_dons = $conn->query("SELECT * FROM donate_accounts ORDER BY created_at DESC");
$result = $conn->query("SELECT * FROM garants ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الرعاة</title>
    <link rel="stylesheet" href="../css/tajawal.css">
    <link href="../css/bootstrap-5.3.1.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
            font-size: 22px;
            direction: rtl;
            text-align: right;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: right;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: right;
        }
        #searchInput{
            border: 2px solid blue;
        }
        table th {
            background-color: #f4f4f4;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea, select option {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: right;
        }
        .btn-head, .form-group button {
            padding: 5px 18px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 22px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        .message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 4px;
            color: #fff;
            text-align: center;
        }
        .message.success {
            background-color: #28a745;
        }
        .message.error {
            background-color: #dc3545;
        }
        .btn-edit {
            background-color: orange;
            color: white;
            font-size: 22px;
            padding: 5px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-edit:hover {
            background-color: darkorange;
        }

        .btn-delete {
            background-color: red;
            color: white;
            font-size: 22px;
            padding: 5px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }
        input{
            font-size: 21px;
        }

        .btn-delete:hover {
            background-color: darkred;
        }
        .cnt{
            text-align: center;
        }
        .head{
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
        .btn-head:hover {
            background-color: #0056b3;
        }
        .form{
            margin-left: 0px;
        }
        select {
            width: 100%;
            padding: 10px;
            font-size: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: right;
            appearance: none; /* Supprime l'apparence par défaut */
            background-color: white;
        }

        select option {
            font-size: 18px;
            padding: 10px;
        }

        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 15px;
            }
            
            h1 {
                font-size: 25px;
            }

            table {
                font-size: 14px;
                overflow-x: auto;
                display: block;
                white-space: nowrap;
            }

            .btn-delete, .btn-edit, .btn-head {
                font-size: 16px;
                padding: 4px 12px;
            }

            .form-group input, .form-group select, .form-group button {
                font-size: 18px;
            }

            .head {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .btn-head {
                width: 100%;
                margin-top: 10px;
            }
        }

    </style>
</head>
<body>
<div class="container">
    <div class="head">
        <h1>إدارة الكفالات</h1>
        <button class="btn-head" onclick="window.location.href='../home.php'">الصفحة الرئيسية</button>
    </div>

    <!-- Afficher les messages -->
    <?php if (!empty($message)): ?>
        <div class="message <?= strpos($message, 'خطأ') !== false ? 'error' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire pour ajouter ou modifier un parrainer -->
    <form method="POST" action="" class="form row">
        <input type="hidden" name="parrainer_id" value="<?= $editParrainer['id'] ?? '' ?>">
        <div class="form-group col-12 col-lg-6">
            <label for="name">اسم الكفيل(ة)</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($editParrainer['name'] ?? '') ?>" required>
        </div>
        <div class="form-group col-12 col-lg-6">
            <label for="amount_sponsored"> المبلغ المتكفل به</label>
            <input type="number" step="0.01" id="amount_sponsored" name="amount_sponsored" value="<?= htmlspecialchars($editParrainer['amount_sponsored'] ?? '') ?>" required>
        </div>
        <div class="form-group col-12 col-lg-6">
            <label for="balance">رصيد حساب</label>
            <input type="number" step="0.01" id="balance" name="balance" value="<?= htmlspecialchars($editParrainer['balance'] ?? '') ?>" required>
        </div>
        <div class="form-group col-12 col-lg-6">
            <label for="phone">رقم الهاتف</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($editParrainer['phone'] ?? '') ?>" required>
        </div>
        <div class="form-group col-12">
            <label for="donate_id">إسم الحساب</label>
            <select class="form-control" name="donate_id" id="donate_id">
                <option value="">--إسم الحساب--</option>
                <?php while ($row = $result_dons->fetch_assoc()): ?>
                    <option value="<?= $row['id']; ?>" <?= $selectedDonation == $row['id'] ? 'selected' : '' ?>>
                        <?= $row['account_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <button type="submit" class="btn-head" name="action" value="<?= $editParrainer ? 'update' : 'add' ?>">
                <?= $editParrainer ? 'تحديث كفيل(ة)' : 'إضافة كفيل(ة)' ?>
            </button>
        </div>
    </form>

    <!-- Liste des garants -->
     <hr>
    <div class="search-box mb-1 mt-4">
        <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم الكفيل(ة)...">
    </div>
    <table>
        <thead>
        <tr>
            <th>المعرف</th>
            <th>اسم الكفيل(ة)</th>
            <th> المبلغ المتكفل به</th>
            <th>رصيد حساب</th>
            <th>رقم الهاتف</th>
            <th class="cnt">الإجراءات</th>
        </tr>
        </thead>
        <tbody id="garantsTableBody">
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['amount_sponsored']) ?></td>
                <td><?= htmlspecialchars($row['balance']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td class="cnt">
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="parrainer_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="action" value="edit" class="btn-edit">تعديل</button>
                        <button type="submit" name="action" value="delete" class="btn-delete">حذف</button>

                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="../js/jquery-3.5.1.min.js"></script>
<script>
    $(document).ready(function() {
        $('#searchInput').on('input', function() {
            const value = $(this).val().toLowerCase();
            $('#garantsTableBody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().includes(value));
            });
        });
    });
</script>
</body>
</html>
