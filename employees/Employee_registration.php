<?php
// Include database connection
include 'db_connection.php';
// Start the session
session_start();

// Check if the session variable 'userid' is set
if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}

// Access session variables safely
$userid = $_SESSION['userid'];
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;

// Fetch job list from the jobs table
$jobList = [];
$sql = "SELECT id, job_name FROM jobs";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $jobList[] = $row;
}

// Fetch the last employee number from the employees table
$nextEmployeeNumber = 4201; // Default starting number
$sql = "SELECT MAX(employee_number) AS last_employee_number FROM employees";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastEmployeeNumber = $row['last_employee_number'];
    if (!is_null($lastEmployeeNumber)) {
        $nextEmployeeNumber = $lastEmployeeNumber + 1;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اكتتاب الموظفين</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Google Font: Amiri -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            background-color: #f8f9fa;
        }
        .container {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 800px;
            margin: 40px auto;
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #343a40;
        }
        label {
            font-weight: 600;
        }
        button {
            font-size: 1.2rem;
        }
        .form-control-lg {
            border: 2px solid #ced4da;
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .form-text {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">اكتتاب الموظفين</h1>
        <form id="Employeform" action="add_employee.php" method="POST">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="Nbr">رقم الموظف</label>
                    <input
                        type="number"
                        class="form-control form-control-lg"
                        id="Nbr"
                        name="Nbr"
                        value="<?php echo $nextEmployeeNumber; ?>"
                        min="0"
                        placeholder="أدخل رقم الموظف"
                        required
                    >
                </div>
                <div class="form-group col-md-6">
                    <label for="Nom">الإسم الكامل</label>
                    <input type="text" class="form-control form-control-lg" id="Nom" name="Nom" placeholder="أدخل الإسم الكامل" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="address">الرصيد</label>
                    <input type="text" class="form-control form-control-lg" id="address" name="address" placeholder="أدخل الرصيد" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="phone">الهاتف</label>
                    <input type="text" class="form-control form-control-lg" id="phone" name="phone" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
                    <small class="form-text">أدخل 8 أرقام فقط.</small>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="profession">الوظيفة</label>
                    <select id="profession" name="profession" class="form-control form-control-lg" required>
                        <option value="">اختر الوظيفة</option>
                        <?php foreach ($jobList as $job): ?>
                            <option value="<?php echo $job['id']; ?>"><?php echo $job['job_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="salary">الراتب</label>
                    <input type="number" class="form-control form-control-lg" id="salary" name="salary" placeholder="الراتب" min="0" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="idNumber">رقم بطاقة التعريف</label>
                    <input type="text" class="form-control form-control-lg" id="idNumber" name="idNumber" pattern="\d{10}" maxlength="10" placeholder="أدخل رقم بطاقة التعريف" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="Date">تاريخ الاكتتاب</label>
                    <input type="date" class="form-control form-control-lg" id="Date" name="Date" required>
                </div>
            </div>
            <div class="form-group text-center">
        <button type="submit" class="btn btn-primary btn-lg">حفظ</button>
        <a href="home.php" class="btn btn-secondary btn-lg ml-3">الصفحة الرئيسية</a>
    </div>
        </form>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>