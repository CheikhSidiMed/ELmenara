<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$rolesQuery = "SELECT * FROM roles WHERE id NOT IN (1, 2, 3, 5)";
$rolesResult = $conn->query($rolesQuery);

$branchesQuery = "SELECT branch_id, branch_name FROM branches";
$branchesResult = $conn->query($branchesQuery);


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
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link rel="stylesheet" href="css/tajawal.css">
    <style>
        body {
            font-family: 'Tajawal', serif;
            background-color: #f8f9fa;
            direction: ltr;
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
                    <input type="number" class="form-control form-control-lg" step="0.01" id="salary" name="salary" placeholder="الراتب" min="0" required>
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


            <div class="form-row">
                <div class="col-md-6 mb-3">
                    <label for="role">الدور</label>
                    <select class="form-select text-primary form-control form-control-lg" id="role" name="role" onchange="toggleFields()">
                        <option value="">اختر الدور</option>
                        <?php
                        if ($rolesResult->num_rows > 0) {
                            while ($role = $rolesResult->fetch_assoc()) {
                                $selected = ($editing_employee && $editing_employee['role_id'] == $role['id']) ? "selected" : "";
                                echo "<option value='{$role['id']}' $selected>{$role['role_name']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="branch">الفرع</label>
                    <select class="form-select text-primary font-weight-bold form-control form-control-lg" id="branch" name="branch" required>
                        <option value="">اختر فرع</option>
                        <?php
                        if ($branchesResult->num_rows > 0) {
                            while ($row = $branchesResult->fetch_assoc()) {
                                $selected = ($editing_employee && $editing_employee['branch_id'] == $row['branch_id']) ? "selected" : "";
                                echo "<option value='{$row['branch_id']}' $selected>{$row['branch_name']}</option>";
                            }
                        } else {
                            echo "<option value=''>لا يوجد فروع</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-row w-100 <?php echo ($editing_employee['class_id'] !== 0) ? '' : 'd-none'; ?> mb-4" id="classContainer">
                <label for="class">القسم</label>
                <select class="form-select text-danger form-control form-control-lg" id="class" name="class">
                    <option value="">اختر القسم</option>
                    <?php
                    $branch_id = $editing_employee['branch_id'];
                    $q = "SELECT class_id, class_name FROM classes WHERE branch_id='$branch_id'";

                    $classesResult = $conn->query($q);

                    if (!empty($branch_id) && $classesResult->num_rows > 0) {
                        while ($classRow = $classesResult->fetch_assoc()) {
                            $selected = ($editing_employee['class_id'] == $classRow['class_id']) ? "selected" : "";
                            echo "<option value='{$classRow['class_id']}' $selected>{$classRow['class_name']}</option>";
                        }
                    } else {
                        echo "<option value=''>لا يوجد صفوف</option>";
                    }
                    ?>
                </select>
            </div>


            <div class="form-group text-center">
                <button type="submit" class="btn btn-primary btn-lg">حفظ</button>
                <a href="home.php" class="btn btn-secondary btn-lg ml-3">الصفحة الرئيسية</a>
            </div>
        </form>
    </div>


    <script>
        function toggleFields() {
            let role = document.getElementById('role').value;
            let classContainer = document.getElementById('classContainer');

            if (role == "6") { // Si le rôle est enseignant
                classContainer.classList.remove("d-none"); // Afficher la sélection de classe
            } else {
                classContainer.classList.add("d-none"); // Cacher la sélection de classe
            }
        }
    </script>
    <script>
        document.addEventListener('change', function (event) {
            if (event.target && event.target.id === 'branch') {
                let branchId = event.target.value;
                let classSelect = document.getElementById('class');

                if (branchId) {
                    fetch('get_classe_s.php?branch_id=' + branchId)
                        .then(response => response.text())
                        .then(data => {
                            console.log("Données reçues:", data);
                            classSelect.innerHTML = data;
                        })
                        .catch(error => console.error('Erreur:', error));
                } else {
                    classSelect.innerHTML = '<option value="">اختر القسم</option>';
                }
            }
        });
    </script>
</body>
</html>