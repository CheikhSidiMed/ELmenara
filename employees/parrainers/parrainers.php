<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../db_connection.php';

if ($conn->connect_error) {
    die("Échec de connexion : " . $conn->connect_error);
}


$message = "";
$editParrainer = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $name = trim($_POST['name'] ?? '');
    $balance = trim($_POST['balance'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $parrainer_id = $_POST['parrainer_id'] ?? null;

    if ($action == 'add') {
        if (!empty($name) && is_numeric($balance)) {
            $stmt = $conn->prepare("INSERT INTO parrainers (name, balance, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $balance, $phone);
            if ($stmt->execute()) {
                $message = "تمت إضافة الكافل(ة) بنجاح.";
            } else {
                $message = "حدث خطأ أثناء إضافة الكافل(ة).";
            }
            $stmt->close();
        } else {
            $message = "الرجاء إدخال جميع البيانات بشكل صحيح.";
        }
    } elseif ($action == 'delete') {
        if (is_numeric($parrainer_id)) {
            $stmt = $conn->prepare("DELETE FROM parrainers WHERE id=?");
            $stmt->bind_param("i", $parrainer_id);
            if ($stmt->execute()) {
                $message = "تم حذف الكافل(ة) بنجاح.";
            } else {
                $message = "حدث خطأ أثناء حذف الكافل(ة).";
            }
            $stmt->close();
        }
    } elseif ($action == 'edit') {
        // Load data for editing
        if (is_numeric($parrainer_id)) {
            $stmt = $conn->prepare("SELECT * FROM parrainers WHERE id = ?");
            $stmt->bind_param("i", $parrainer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $editParrainer = $result->fetch_assoc();
            $stmt->close();
        }
    } elseif ($action == 'update') {
        // Update record in the database
        if (is_numeric($parrainer_id) && !empty($name) && is_numeric($balance)) {
            $stmt = $conn->prepare("UPDATE parrainers SET name = ?, balance = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $balance, $phone, $parrainer_id);
            if ($stmt->execute()) {
                $message = "تم تحديث بيانات الكافل(ة) بنجاح.";
            } else {
                $message = "حدث خطأ أثناء تحديث بيانات الكافل(ة).";
            }
            $stmt->close();
        } else {
            $message = "الرجاء إدخال جميع البيانات بشكل صحيح.";
        }
    }
}

// Récupération des parrainers
$result = $conn->query("SELECT * FROM parrainers ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الرعاة</title>
    <link rel="stylesheet" href="../css/tajawal.css">

    <style>
        /* body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        } */
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
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: right;
        }
        .btn-head, .form-group button {
            padding: 15px 25px;
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
            padding: 10px 20px;
            border-radius: 10px;
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
            padding: 10px 20px;
            border-radius: 10px;
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

        @media (max-width: 768px) {
            table, table th, table td {
                font-size: 14px;
            }
            .form-group button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="head">
        <h1>إدارة الكفله</h1>
        <button class="btn-head" onclick="window.location.href='../home.php'">الصفحة الرئيسية</button>
    </div>

    <!-- Afficher les messages -->
    <?php if (!empty($message)): ?>
        <div class="message <?= strpos($message, 'خطأ') !== false ? 'error' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire pour ajouter ou modifier un parrainer -->
    <form method="POST" action="">
        <input type="hidden" name="parrainer_id" value="<?= $editParrainer['id'] ?? '' ?>">
        <div class="form-group">
            <label for="name">اسم الكافل(ة)</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($editParrainer['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="balance">رصيد حساب</label>
            <input type="number" step="0.01" id="balance" name="balance" value="<?= htmlspecialchars($editParrainer['balance'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="phone">رقم الهاتف</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($editParrainer['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <button type="submit" name="action" value="<?= $editParrainer ? 'update' : 'add' ?>">
                <?= $editParrainer ? 'تحديث الكافل(ة)' : 'إضافة الكافل(ة)' ?>
            </button>
        </div>
    </form>

    <!-- Liste des parrainers -->
    <table>
        <thead>
        <tr>
            <th>المعرف</th>
            <th>اسم الراعي</th>
            <th>رصيد حساب</th>
            <th>رقم الهاتف</th>
            <th class="cnt">الإجراءات</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
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
</body>
</html>
