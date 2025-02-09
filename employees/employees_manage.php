<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';


session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}



// Handle Insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $employee_number = $_POST['employee_number'];
    $full_name = $_POST['full_name'];
    $balance = $_POST['balance'];
    $phone = $_POST['phone'];
    $job_id = $_POST['job_id'];
    $salary = $_POST['salary'];
    $subscription_date = $_POST['subscription_date'];
    $id_number = $_POST['id_number'];

    $sql = "INSERT INTO employees (employee_number, full_name, balance, phone, job_id, salary, subscription_date, id_number)
            VALUES ('$employee_number', '$full_name', '$balance', '$phone', '$job_id', '$salary', '$subscription_date', '$id_number')";

    if ($conn->query($sql) === TRUE) {
        $success_message = "تمت إضافة الموظف بنجاح!";
    } else {
        $error_message = "خطأ: " . $conn->error;
    }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employee'])) {
    $id = $_POST['id'];
    $employee_number = $_POST['employee_number'];
    $full_name = $_POST['full_name'];
    $balance = $_POST['balance'];
    $phone = $_POST['phone'];
    $job_id = $_POST['job_id'];
    $salary = $_POST['salary'];
    $subscription_date = $_POST['subscription_date'];
    $id_number = $_POST['id_number'];

    $sql = "UPDATE employees
            SET employee_number='$employee_number', full_name='$full_name', balance='$balance',
                phone='$phone', job_id='$job_id', salary='$salary', subscription_date='$subscription_date', id_number='$id_number'
            WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        $success_message = "تم تعديل بيانات الموظف بنجاح!";
    } else {
        $error_message = "خطأ: " . $conn->error;
    }
}

// Fetch Employees
$employees = $conn->query("SELECT e.*, j.job_name
                           FROM employees e
                           LEFT JOIN jobs j ON e.job_id = j.id");

// Handle Edit
$editing_employee = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM employees WHERE id='$id'");
    $editing_employee = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الموظفين</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/cairo.css" rel="stylesheet">
    <style>
    body {
        font-family: 'Cairo', sans-serif;
        direction: rtl;
        margin: 0;
        padding: 0;
        padding-bottom: 30px;
    }

    </style>
</head>

<body>
<div class="container mt-5">
    <h1 class="mb-4">إدارة الموظفين</h1>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Employee Form -->
    <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>تعديل بيانات الموظف(ة)</h4>
        <a href="home.php" class="btn btn-secondary btn-sm">الرئيسية</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if ($editing_employee): ?>
                <input type="hidden" name="id" value="<?php echo $editing_employee['id']; ?>">
            <?php endif; ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="employee_number">رقم الموظف(ة)</label>
                    <input type="text" id="employee_number" class="form-control" name="employee_number"
                        placeholder="رقم الموظف" value="<?php echo $editing_employee['employee_number'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="full_name">الاسم الكامل</label>
                    <input type="text" id="full_name" class="form-control" name="full_name"
                        placeholder="الاسم الكامل" value="<?php echo $editing_employee['full_name'] ?? ''; ?>" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="balance">الرصيد</label>
                    <input type="text" id="balance" class="form-control" name="balance"
                        placeholder="الرصيد" value="<?php echo $editing_employee['balance'] ?? ''; ?>">
                </div>
                <div class="col-md-6">
                    <label for="phone">رقم الهاتف</label>
                    <input type="text" id="phone" class="form-control" name="phone"
                        placeholder="رقم الهاتف" value="<?php echo $editing_employee['phone'] ?? ''; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="job_id">الوظيفة</label>
                    <select id="job_id" class="form-control" name="job_id" required>
                        <option value="">اختر الوظيفة</option>
                        <?php
                        $jobs = $conn->query("SELECT * FROM jobs");
                        while ($job = $jobs->fetch_assoc()) {
                            $selected = ($editing_employee && $editing_employee['job_id'] == $job['id']) ? "selected" : "";
                            echo "<option value='{$job['id']}' $selected>{$job['job_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="salary">الراتب</label>
                    <input type="number" id="salary" class="form-control" name="salary"
                        placeholder="الراتب" value="<?php echo $editing_employee['salary'] ?? ''; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="subscription_date">تاريخ الاشتراك</label>
                    <input type="date" id="subscription_date" class="form-control" name="subscription_date"
                        value="<?php echo $editing_employee['subscription_date'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="id_number">رقم الهوية</label>
                    <input type="text" id="id_number" class="form-control" name="id_number"
                        placeholder="رقم الهوية" value="<?php echo $editing_employee['id_number'] ?? ''; ?>">
                </div>
            </div>
            <button type="submit" name="update_employee" class="btn btn-primary w-100">
                تحديث بيانات الموظف(ة)
            </button>
        </form>
    </div>
</div>

<!-- Search Box -->
<div class="search-box mb-4">
    <input type="text" id="searchInput" class="form-control" placeholder="البحث عن الموظف(ة)...">
</div>

<!-- Employee List (Responsive) -->
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="text-center">
            <tr>
                <th>رقم</th>
                <th>رقم الموظف(ة)</th>
                <th>الاسم الكامل</th>
                <th>الرصيد</th>
                <th>الهاتف</th>
                <th>الوظيفة</th>
                <th>الراتب</th>
                <th>تاريخ الاشتراك</th>
                <th>رقم الهوية</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody id="suspendedStudentsTableBody">
            <?php while ($employee = $employees->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $employee['id']; ?></td>
                    <td><?php echo $employee['employee_number']; ?></td>
                    <td><?php echo $employee['full_name']; ?></td>
                    <td><?php echo $employee['balance']; ?></td>
                    <td><?php echo $employee['phone']; ?></td>
                    <td><?php echo $employee['job_name']; ?></td>
                    <td><?php echo $employee['salary']; ?></td>
                    <td><?php echo $employee['subscription_date']; ?></td>
                    <td><?php echo $employee['id_number']; ?></td>
                    <td>
                        <a href="?id=<?php echo $employee['id']; ?>" class="btn btn-warning btn-sm">تعديل</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
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
