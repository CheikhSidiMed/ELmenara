<?php
// Start the session and include the database connection
session_start();
include 'db_connection.php';


if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = 'index.php'; </script>";
    exit();
}


$logged_in_role_id = $_SESSION['role_id'];


$query = "SELECT u.id, u.username, r.role_name FROM users u JOIN roles r ON u.role_id = r.id";
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

        .table {
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
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
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
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .table th:last-child,
        .table td:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
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
            border-radius: 10px;
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
    </style>
</head>

<body>
    <div class="container">
        <h2>قائمة المستخدمين و الأدوار</h2>
        <a href="home.php" class="home-btn"><i class="bi bi-house-fill"></i> الصفحة الرئيسية</a>
        <button class="add-user-btn" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-fill-add"></i> إضافة مستخدم جديد</button>

        <!-- User Table -->
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>رقم المستخدم</th>
                        <th>اسم المستخدم</th>
                        <th>الدور</th>
                        <th>الدور</th>
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
                            if ($logged_in_role_id == 1) {
                                echo "<td class='action-buttons'>";
                                echo "<a href='edit_user.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm'><i class='bi bi-pencil-square'></i> تعديل</a>";
                                echo "<a href='delete_user.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick=\"return confirm('هل أنت متأكد من أنك تريد حذف هذا المستخدم؟');\"><i class='bi bi-trash'></i> حذف</a>";
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

    <!-- Success message after redirect from delete -->
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #1BA078;">
                    <h5 class="modal-title text-white" id="addUserModalLabel">إضافة مستخدم جديد</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <select class="form-select" id="role" name="role">
                                <?php
                                if ($rolesResult->num_rows > 0) {
                                    while ($role = $rolesResult->fetch_assoc()) {
                                        echo "<option value='" . $role['id'] . "'>" . $role['role_name'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" onclick="addUser()">إضافة</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
    // Function to handle adding a new user
    function addUser() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const role = document.getElementById('role').value;

        if (username && password && role) {
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            formData.append('role', role);

            fetch('add_user_process.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                if (data.success) {
                    // Show success Swal.fire alert
                    Swal.fire({
                        icon: 'success',
                        title: 'تمت إضافة المستخدم بنجاح',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Reload the page after the alert
                        window.location.reload();
                    });
                } else {
                    // Show error Swal.fire alert
                    Swal.fire({
                        icon: 'success',
                        title: 'تمت إضافة المستخدم بنجاح',
                       
                    });
                }
            }).catch(error => {
                console.error('Error:', error);
                // Show error Swal.fire alert for the catch block
                Swal.fire({
                    icon: 'success',
                    title: 'تمت إضافة المستخدم بنجاح',
                   
                });
            });
        } else {
            // Show validation error if fields are missing
            Swal.fire({
                icon: 'warning',
                title: 'الرجاء ملء جميع الحقول',
                text: 'يجب إدخال جميع البيانات المطلوبة.'
            });
        }
    }
</script>

</body>

</html>
