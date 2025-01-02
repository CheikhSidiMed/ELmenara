<?php
// Include the database connection file
include 'db_connection.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account_id'])) {
    $delete_account_id = $_POST['delete_account_id'];

    // Delete query
    $sql = "DELETE FROM expense_accounts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $delete_account_id);

    if ($stmt->execute()) {
        $delete_message = 'تم حذف الحساب بنجاح.';
    } else {
        $delete_message = 'حدث خطأ أثناء حذف الحساب.';
    }
}

?>




<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التبرعات</title>
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tajawal.css">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
        }
        .table-container {
            margin: 30px auto;
            max-width: 80%;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .table-title {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- <h1 class="text-center">إدارة الحسابات</h1> -->

        <div class="table-container">
            <h1 class="table-title">إدارة حسابات المصاريف</h1>

            <button class="btn home bg-black text-white mb-4"  onclick="window.location.href='home.php'">العودة إلى الصفحة الرئيسية</button>

            <!-- Expense Accounts Table -->
            <table class="table table-striped table-bordered text-center">
                <thead class="table-dark">
                    <tr>
                        <th>رقم الحساب</th>
                        <th>اسم الحساب</th>
                        <th>الفئة</th>
                        <th>التاريخ</th>
                        <th>الرصيد</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody >
                    <?php
                    include 'db_connection.php';

                    // Fetch all accounts
                    $query = "SELECT * FROM expense_accounts";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "
                            <tr>
                                <td>{$row['account_number']}</td>
                                <td>{$row['account_name']}</td>
                                <td>{$row['category']}</td>
                                <td>{$row['created_at']}</td>
                                <td>{$row['account_balance']}</td>
                                <td>
                                    <button 
                                        class='btn btn-warning btn-sm edit-button' 
                                        data-id='{$row['id']}'
                                        data-name='{$row['account_name']}' 
                                        data-category='{$row['category']}' 
                                        data-balance='{$row['account_balance']}'>
                                        <i class='bi bi-pencil-square'></i> تعديل
                                    </button>
                                    
                                    <button class='btn btn-danger btn-sm' onclick='confirmDelete({$row['id']})'>
                                        <i class='bi bi-trash'></i> حذف
                                    </button>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>لا توجد حسابات</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- Edit Account Modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAccountLabel">تعديل الحساب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="edit_account.php" method="POST">
                    <div class="modal-body">
                        <!-- Fields to edit -->
                        <input type="hidden" name="edit_account_id" id="edit_account_id">
                        <div class="mb-3">
                            <label for="edit_account_name">اسم الحساب:</label>
                            <input type="text" class="form-control" id="edit_account_name" name="edit_account_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">الفئة</label>
                            <input type="text" class="form-control" id="edit_category" name="edit_category" required>
                            </div>
                        <div class="mb-3">
                            <label for="edit_account_balance" class="form-label">الرصيد</label>
                            <input type="number" class="form-control" id="edit_account_balance" name="edit_account_balance" required>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_account_id" id="delete_account_id">
    </form>

    <script>
        function confirmDelete(id) {
            if (confirm('هل أنت متأكد من حذف هذا الحساب؟')) {
                document.getElementById('delete_account_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
    <script>
    // Handle edit button click
    document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', function() {
            // Get data from the button's dataset
            const id = this.dataset.id;
            const name = this.dataset.name;
            const category = this.dataset.category;
            const balance = this.dataset.balance;

            // Populate the modal fields
            document.getElementById('edit_account_id').value = id;
            document.getElementById('edit_account_name').value = name;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_account_balance').value = balance;

            // Show the modal (Bootstrap's way)
            const editModal = new bootstrap.Modal(document.getElementById('editAccountModal'));
            editModal.show();
        });
    });
</script>

</body>
</html>
