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


$message = '';

// Handle Add/Update Class
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'] ?? null;
    $branch_id = $_POST['branch_id'] ?? null;
    $class_name = $_POST['class_name'] ?? '';
    $price = 0;

    if (!empty($class_id)) {
        // Update existing class
        $sql_update = "UPDATE classes SET branch_id = ?, class_name = ? WHERE class_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('isi', $branch_id, $class_name, $class_id);
        if ($stmt_update->execute()) {
            $message = 'تم تعديل الصف بنجاح!';
        } else {
            $message = 'حدث خطأ أثناء تعديل الصف.';
        }
        $stmt_update->close();
    } else {
        // Insert new class
        $sql_insert = "INSERT INTO classes (branch_id, class_name) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param('isd', $branch_id, $class_name, $price);
        if ($stmt_insert->execute()) {
            $message = 'تمت إضافة الصف بنجاح!';
        } else {
            $message = 'حدث خطأ أثناء إضافة الصف.';
        }
        $stmt_insert->close();
    }
}

// Handle Delete Class
if (isset($_GET['delete_id'])) {
    $class_id = $_GET['delete_id'];
    $sql_delete = "DELETE FROM classes WHERE class_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('i', $class_id);
    if ($stmt_delete->execute()) {
        $message = 'تم حذف الصف بنجاح!';
    } else {
        $message = 'حدث خطأ أثناء حذف الصف.';
    }
    $stmt_delete->close();
}

// Fetch all classes
$sql_fetch = "SELECT 
    c.class_id, 
    c.branch_id, 
    c.class_name, 
    b.branch_name, 
    COUNT(s.id) AS count 
FROM 
    classes c
LEFT JOIN 
    branches b ON c.branch_id = b.branch_id
LEFT JOIN 
    students s ON c.class_id = s.class_id
GROUP BY 
    c.class_id, c.branch_id, c.class_name, b.branch_name;";
$result = $conn->query($sql_fetch);
$classes = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all branches for the dropdown
$sql_branches = "SELECT branch_id, branch_name FROM branches ORDER BY branch_name";
$branch_result = $conn->query($sql_branches);
$branches = $branch_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الصفوف</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
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
    <h1 class="text-center mb-4">إدارة الصفوف</h1>

    <?php if ($message): ?>
        <div class="alert alert-info text-right"><?= $message; ?></div>
    <?php endif; ?>

    <!-- Add/Edit Class Form -->
    <div class="card mb-4">
        <div class="modal-header d-flex justify-content-between align-items-center">
            <h3>تعديل صف</h3>
            <button class="btn btn-primary home" onclick="window.location.href='home.php'">العودة إلى الصفحة الرئيسية</button>
        </div>
        <!-- <div class="card-header bg-primary text-white text-right">إضافة / تعديل صف</div> -->
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="class_id" id="class_id">
                <div class="mb-3 text-right">
                    <label for="branch_id" class="form-label">الفرع</label>
                    <select name="branch_id" id="branch_id" class="form-select" required>
                        <option value="">اختر الفرع</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['branch_id']; ?>"><?= $branch['branch_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3 text-right">
                    <label for="class_name" class="form-label">اسم الصف</label>
                    <input type="text" name="class_name" id="class_name" class="form-control" required>
                </div>

                <div class="mb-3 text-right">
                    <label for="coun_t" class="form-label">عدد التلاميذ</label>
                    <input type="text" step="0.01" name="coun_t" id="coun_t" class="form-control" readonly disabled>
                </div>
                <button type="submit" class="btn btn-success">حفظ</button>
                <button type="reset" class="btn btn-secondary">مسح</button>
            </form>
        </div>
    </div>

    <!-- Classes Table -->
    <div class="card">
        <div class="card-header bg-dark text-white text-right">الصفوف</div>
        <div class="card-body">
            <table class="table table-striped table-bordered text-right">
                <thead>
                <tr>
                    <th>#</th>
                    <th>الفرع</th>
                    <th>اسم الصف</th>
                    <th>عدد التلاميذ</th>
                    <th>إجراءات</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?= $class['class_id']; ?></td>
                        <td><?= $class['branch_name'] ?? 'N/A'; ?></td>
                        <td><?= $class['class_name']; ?></td>
                        <td><?= $class['count']; ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="editClass(<?= $class['count']; ?>, <?= $class['class_id']; ?>, <?= $class['branch_id']; ?>, '<?= $class['class_name']; ?>')">تعديل</button>
                            <a href="?delete_id=<?= $class['class_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد أنك تريد حذف هذا الصف؟')">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function editClass(count, classId, branchId, className) {
        document.getElementById('class_id').value = classId;
        document.getElementById('branch_id').value = branchId;
        document.getElementById('class_name').value = className;
        document.getElementById('coun_t').value = count;
    }
</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
