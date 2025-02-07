<?php
// Start the session and include the database connection
session_start();
include 'db_connection.php';


if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = 'index.php'; </script>";
    exit();
}


$logged_in_role_id = $_SESSION['role_id'];

$selectedClass = $_POST['class'] ?? null;
$selectedBranch = isset($_POST['branch']) ? (is_array($_POST['branch']) ? $_POST['branch'][0] : $_POST['branch']) : null;

$query = "SELECT
            u.id,
            u.username,
            r.role_name,
            c.class_name,
            GROUP_CONCAT(b.branch_name ORDER BY b.branch_name SEPARATOR ', ') AS branch_names
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN user_branch ub ON ub.user_id = u.id
        LEFT JOIN branches b ON b.branch_id = ub.branch_id
        LEFT JOIN classes c ON c.class_id = ub.class_id
        GROUP BY u.id, u.username, r.role_name;";
$result = $conn->query($query);


$rolesQuery = "SELECT * FROM roles";
$rolesResult = $conn->query($rolesQuery);


$editSuccess = false;
if (isset($_SESSION['edit_success'])) {
    $editSuccess = true;
    unset($_SESSION['edit_success']);
}


$deleteSuccess = false;
if (isset($_SESSION['delete_success'])) {
    $deleteSuccess = true;
    unset($_SESSION['delete_success']);
}

$branchesQuery = "SELECT branch_id, branch_name FROM branches";
$branchesResult = $conn->query($branchesQuery);



$selectedTeacher = ($_POST['role'] &&$_POST['role'] === 20) ?? null; // Récupérer le teacher depuis le formulaire
$classesResult = [];

if ($selectedBranch && $selectedTeacher) { // Vérifier que les deux sont sélectionnés
    $classesQuery = "SELECT class_id, class_name FROM classes WHERE branch_id = ?";
    $stmt = $conn->prepare($classesQuery);
    $stmt->bind_param("i", $selectedBranch);
    $stmt->execute();
    $classesResult = $stmt->get_result();
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض المستخدمين</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css">
    <script src="js/sweetalert2.min.js"></script>

    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f7f8fc;
            color: #333;
        }

        .container {
            margin-top: 50px;
        }
        .bny{
            padding: 4px 15px !important;
            font-size:  22px !important;
            color: #fff;
        }

        table {
            margin-top: 20px;
            border: 2px solid #1BA078;
            border-radius: 15px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table th, .table td {
            text-align: center;
            vertical-align: middle;
            font-size: 18px;
            padding: 15px;
        }

        .table th {
            background-color: #1BA078;
            color: white;
            font-size: 20px;
        }

        .table td {
            background-color: #f9f9f9;
            color: #555;
        }

        h2 {
            color: #1BA078;
            font-weight: bold;
            font-size: 36px;
            text-align: center;
            margin-bottom: 40px;
        }

        /* Hover effect for table rows */
        .table tbody tr:hover {
            background-color: #f0f5f5;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        /* Add some extra styling for the table */
        .table-bordered {
            border: none;
        }

        .table th:first-child,
        .table td:first-child {
            border-top-left-radius: 1px;
            border-bottom-left-radius: 1px;
        }

        .table th:last-child,
        .table td:last-child {
        }

        /* Scrollbar styling */
        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }

        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background-color: #1BA078;
        }

        .table-container::-webkit-scrollbar-track {
            background-color: #f1f1f1;
        }

        .add-user-btn, .home-btn {
            display: inline-block;
            background-color: #1BA078;
            color: white;
            font-size: 18px;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .add-user-btn:hover, .home-btn:hover {
            background-color: #14865b;
            text-decoration: none;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        @media screen and (max-width: 768px) {
            .bny{
                font-size: 15px !important;
            }
            th{
                font-size: 15px !important;

            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>قائمة المستخدمين و الأدوار</h2>
        <a href="home.php" class="home-btn"><i class="bi bi-house-fill"></i> الصفحة الرئيسية</a>
        <button class="add-user-btn" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-fill-add ms-1"></i> إضافة مستخدم جديد</button>

        <!-- User Table -->
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>رقم المستخدم</th>
                        <th>اسم المستخدم</th>
                        <th>الدور</th>
                        <th>الفرع</th>
                        <th>القسم</th>
                        <?php if ($logged_in_role_id == 1): ?>
                        <th>الإجراءات</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['branch_names']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['class_name']??'الجيمع') . "</td>";
                            if ($logged_in_role_id == 1) {
                                echo "<td class='action-buttons'>";
                                echo "<a href='edit_user.php?id=" . $row['id'] . "' class='btn btn-success bny btn-sm'><i class='bi bi-pencil-square'></i> تعديل</a>";
                                echo "<a href='delete_user.php?id=" . $row['id'] . "' class='btn btn-danger bny btn-sm' onclick=\"return confirm('هل أنت متأكد من أنك تريد حذف هذا المستخدم؟');\"><i class='bi bi-trash'></i> حذف</a>";
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>لا يوجد مستخدمون</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Success message after redirect -->
    <?php if ($editSuccess) { ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'تم تحديث المستخدم بنجاح',
                showConfirmButton: false,
                timer: 1500
            });
        </script>
    <?php } ?>

    <?php if ($deleteSuccess) { ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'تم حذف المستخدم بنجاح',
                showConfirmButton: false,
                timer: 1500
            });
        </script>
    <?php } ?>

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #1BA078;">
                    <h5 class="modal-title text-white" id="addUserModalLabel">إضافة مستخدم جديد</h5>
                    <button type="button" class="btn-close text-white ms-1" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">اسم المستخدم</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">الدور</label>
                            <select class="form-select" id="role" name="role" onchange="toggleFields()">
                                <?php
                                if ($rolesResult->num_rows > 0) {
                                    while ($role = $rolesResult->fetch_assoc()) {
                                        echo "<option value='" . $role['id'] . "'>" . $role['role_name'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group w-100" id="branchContainer">
                            <label for="branch">الفروع</label>
                            <select class="form-control" id="branch" name="branch[]" multiple required>
                            <option value="">اختر فرع</option>

                                <?php
                                if ($branchesResult->num_rows > 0) {
                                    while ($row = $branchesResult->fetch_assoc()) {
                                        // echo "<option value='{$row['branch_id']}'>{$row['branch_name']}</option>";
                                        echo "<option value='{$row['branch_id']}'" . ($selectedBranch == $row['branch_id'] ? " selected" : "") . ">{$row['branch_name']}</option>";

                                    }
                                } else {
                                    echo "<option value=''>لا يوجد فروع</option>";
                                }
                                ?>
                            </select>
                            <small class="text-muted text-success">يمكنك اختيار عدة فروع باستخدام Ctrl (Windows) أو Cmd (Mac)</small>
                        </div>

                        <div class="form-group w-100 d-none" id="classContainer">
                            <label for="class">القسم</label>
                            <select class="form-control" id="class" name="class">
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary bny" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success bny" onclick="addUser()">إضافة</button>
                </div>
            </div>
        </div>
    </div>





    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
    function addUser() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const role = document.getElementById('role').value;
        let class_id = document.getElementById('class').value;

        const branchSelect = document.getElementById('branch');
        const branches = Array.from(branchSelect.selectedOptions).map(option => option.value);

        if (username && password && role && branches.length > 0) {
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            formData.append('role', role);
            formData.append('class', class_id);

            branches.forEach(branch => {
                formData.append('branches[]', branch);
            });

            fetch('add_user_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تمت إضافة المستخدم بنجاح',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'حدث خطأ أثناء الإضافة',
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'حدث خطأ أثناء الإضافة',
                });
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'الرجاء ملء جميع الحقول',
                text: 'يجب إدخال جميع البيانات المطلوبة.'
            });
        }
    }
</script>
</body> <!-- Assurez-vous que ceci est la fin du document -->



<script>
    function toggleFields() {
        let role = document.getElementById('role').value;
        let branchSelect = document.getElementById('branch');
        let branchContainer = document.getElementById('branchContainer');
        let classContainer = document.getElementById('classContainer');

        if (role == "6") { // Si le rôle est enseignant
            branchSelect.removeAttribute("multiple"); // Désactiver multiple
            branchContainer.innerHTML = branchContainer.innerHTML.replace("multiple", ""); // Supprimer multiple si existant
            classContainer.classList.remove("d-none"); // Afficher la sélection de classe
        } else {
            branchSelect.setAttribute("multiple", "multiple"); // Activer multiple
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
