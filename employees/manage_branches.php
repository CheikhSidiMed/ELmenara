<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


$message = '';

// Handle Add/Update Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch_id = $_POST['branch_id'] ?? null;
    $branch_name = $_POST['branch_name'] ?? '';

    if (!empty($branch_id)) {
        // Update existing branch
        $sql_update = "UPDATE branches SET branch_name = ? WHERE branch_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('si', $branch_name, $branch_id);
        if ($stmt_update->execute()) {
            $message = 'تم تعديل الفرع بنجاح!';
        } else {
            $message = 'حدث خطأ أثناء تعديل الفرع.';
        }
        $stmt_update->close();
    } else {
        // Insert new branch
        $sql_insert = "INSERT INTO branches (branch_name) VALUES (?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param('s', $branch_name);
        if ($stmt_insert->execute()) {
            $message = 'تمت إضافة الفرع بنجاح!';
        } else {
            $message = 'حدث خطأ أثناء إضافة الفرع.';
        }
        $stmt_insert->close();
    }
}

// Handle Delete Branch
if (isset($_GET['delete_id'])) {
    $branch_id = $_GET['delete_id'];
    $sql_delete = "DELETE FROM branches WHERE branch_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('i', $branch_id);
    if ($stmt_delete->execute()) {
        $message = 'تم حذف الفرع بنجاح!';
    } else {
        $message = 'حدث خطأ أثناء حذف الفرع.';
    }
    $stmt_delete->close();
}

// Fetch all branches
$sql_fetch = "SELECT branch_id, branch_name FROM branches ORDER BY branch_id";
$result = $conn->query($sql_fetch);
$branches = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الفروع</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tajawal.css">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            direction: rtl;
            text-align: right;
        }
        .modal-header {
            background-color: #0056b3;
            border-radius: 1px;
            color: white;
            padding: 1rem;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4">إدارة الفروع</h1>

    <?php if ($message): ?>
        <div class="alert alert-info text-right"><?= $message; ?></div>
    <?php endif; ?>

    <!-- Add/Edit Branch Form -->
    <div class="card mb-4">
        <div class="modal-header d-flex justify-content-between align-items-center">
            <h3>تعديل فرع</h3>
            <button class="btn btn-primary home" onclick="window.location.href='home.php'">العودة إلى الصفحة الرئيسية</button>
        </div>
        <!-- <div class="card-header bg-primary text-white text-right">إضافة / تعديل فرع</div> -->
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="branch_id" id="branch_id">
                <div class="mb-3 text-right">
                    <label for="branch_name" class="form-label">اسم الفرع</label>
                    <input type="text" name="branch_name" id="branch_name" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success">حفظ</button>
                <button type="reset" class="btn btn-secondary">مسح</button>
            </form>
        </div>
    </div>

    <!-- Branches Table -->
    <div class="card">
        <div class="card-header bg-dark text-white text-right">الفروع</div>
        <div class="card-body">
            <table class="table table-striped table-bordered text-right">
                <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الفرع</th>
                    <th>إجراءات</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($branches as $branch): ?>
                    <tr>
                        <td><?= $branch['branch_id']; ?></td>
                        <td><?= $branch['branch_name']; ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="editBranch(<?= $branch['branch_id']; ?>, '<?= $branch['branch_name']; ?>')">تعديل</button>
                            <a href="?delete_id=<?= $branch['branch_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد أنك تريد حذف هذا الفرع؟')">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function editBranch(branchId, branchName) {
        document.getElementById('branch_id').value = branchId;
        document.getElementById('branch_name').value = branchName;
    }
</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
