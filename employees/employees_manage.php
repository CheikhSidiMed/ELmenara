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

$rolesQuery = "SELECT * FROM roles WHERE id != 1";
$rolesResult = $conn->query($rolesQuery);

$branchesQuery = "SELECT branch_id, branch_name FROM branches";
$branchesResult = $conn->query($branchesQuery);

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
    $role_p = $_POST['role'];
    $class_id_p = $_POST['class'] ?? 0;
    $branch_id = $_POST['branch'];

    // Insert into employees
    $stmt = $conn->prepare("INSERT INTO employees (employee_number, full_name, balance, phone, job_id, salary, subscription_date, id_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $employee_number, $full_name, $balance, $phone, $job_id, $salary, $subscription_date, $id_number);
    
    if ($stmt->execute()) {
        $emply_id = $stmt->insert_id;

        // Check if a user exists for this employee
        $user_stmt = $conn->prepare("SELECT id FROM users WHERE employee_id = ?");
        $user_stmt->bind_param("i", $emply_id);
        $user_stmt->execute();
        $user_stmt->bind_result($user_id);
        $user_stmt->fetch();
        $user_stmt->close();

        if ($user_id) {
            // Update the user role
            $strQ = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $strQ->bind_param("ii", $role_p, $user_id);
            if ($strQ->execute()) {
                // Insert into user_branch with the correct user_id
                $br_us_stmt = $conn->prepare("INSERT INTO user_branch (branch_id, class_id, user_id) VALUES (?, ?, ?)");
                if (!$br_us_stmt) {
                    echo json_encode(['success' => false, 'message' => 'Erreur préparation user_branch: ' . $conn->error]);
                    exit;
                }
                $br_us_stmt->bind_param("iii", $branch_id, $class_id_p, $user_id);
                if (!$br_us_stmt->execute()) {
                    echo json_encode(['success' => false, 'message' => 'Erreur insertion user_branch: ' . $br_us_stmt->error]);
                    exit;
                }
                $br_us_stmt->close();
                $success_message = "تمت إضافة الموظف بنجاح!";
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur: Aucun utilisateur associé à cet employé']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $conn->error]);
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
$employees =
    $conn->query("SELECT e.*, j.job_name FROM employees e
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
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" id="employee_number" class="form-control" name="employee_number"
                        placeholder="رقم الموظف" value="<?php echo $editing_employee['employee_number'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <input type="text" id="full_name" class="form-control" name="full_name"
                        placeholder="الاسم الكامل" value="<?php echo $editing_employee['full_name'] ?? ''; ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" id="balance" class="form-control" name="balance"
                        placeholder="الرصيد" value="<?php echo $editing_employee['balance'] ?? ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <input type="text" id="phone" class="form-control" name="phone"
                        placeholder="رقم الهاتف" value="<?php echo $editing_employee['phone'] ?? ''; ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <select id="job_id" class="form-select" name="job_id" required>
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
                <div class="col-md-6 mb-3">
                    <input type="number" id="salary" class="form-control" name="salary"
                        placeholder="الراتب" value="<?php echo $editing_employee['salary'] ?? ''; ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="date" id="subscription_date" class="form-control" name="subscription_date"
                        value="<?php echo $editing_employee['subscription_date'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <input type="text" id="id_number" class="form-control" name="id_number"
                        placeholder="رقم الهوية" value="<?php echo $editing_employee['id_number'] ?? ''; ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <select class="form-select text-primary " id="role" name="role" onchange="toggleFields()">
                        <option value="">اختر الدور</option>
                        <?php
                        if ($rolesResult->num_rows > 0) {
                            while ($role = $rolesResult->fetch_assoc()) {
                                echo "<option value='" . $role['id'] . "'>" . $role['role_name'] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <select class="form-select text-primary font-weight-bold" id="branch" name="branch" required>
                        <option value="">اختر فرع</option>
                        <?php
                            if ($branchesResult->num_rows > 0) {
                                while ($row = $branchesResult->fetch_assoc()) {
                                    echo "<option value='{$row['branch_id']}'" . ($selectedBranch == $row['branch_id'] ? " selected" : "") . ">{$row['branch_name']}</option>";
                                }
                            } else {
                                echo "<option value=''>لا يوجد فروع</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group w-100 d-none mb-4" id="classContainer">
                    <select class="form-select text-danger " id="class" name="class">
                        <option value="">اختر القسم</option>
                        <?php
                        if ($selectedBranch && $classesResult->num_rows > 0) {
                            echo "<!-- Debug: classes trouvées -->";
                            while ($classRow = $classesResult->fetch_assoc()) {
                                var_dump($classRow); // Vérifier chaque ligne retournée
                                echo "<option value='{$classRow['class_id']}'>{$classRow['class_name']}</option>";
                            }
                        } else {
                            echo "<option value=''>لا يوجد صفوف</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row">
            <div class="col-md-6 mb-3">
                <button type="submit" name="update_employee" class="btn btn-primary w-100">
                تحديث بيانات الموظف(ة)
                </button>
            </div>

            
            <div class="col-md-6 mb-3">
                <button type="submit" name="add_employee" class="btn btn-primary w-100">
                     بيانات الموظف(ة)
                </button>
            </div>
            
            </div>

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
