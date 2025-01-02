<?php
// Include database connection
include 'db_connection.php';

// Fetch all levels
$sql = "SELECT id, level_name, price FROM levels";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لائحة المستويات</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>
    
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }
        .container-main {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            max-width: 1100px;
            margin: auto;
        }
        .header-title {
            font-family: 'Amiri', serif;
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #1a73e8;
            padding-bottom: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .btn-home {
            background-color: #1a73e8;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
            display: inline-block;
            font-size: 1.1rem;
            text-decoration: none;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .btn-home i {
            margin-left: 8px; /* Space between icon and text */
        }
        .btn-home:hover {
            background-color: #155bb5;
        }
        .center-button {
            display: flex;
            justify-content: center;
            margin-bottom: 20px; /* Add space between button and content */
        }
        .table {
            border-collapse: separate;
            border-spacing: 0 15px;
        }
        .table thead th {
            background-color: #1a73e8;
            color: #ffffff;
            border: none;
            padding: 10px 15px;
            border-radius: 10px 10px 0 0;
            font-size: 1.2rem;
        }
        .table tbody tr {
            background-color: #f9f9f9;
            border: 2px solid #1a73e8;
            border-radius: 10px;
        }
        .table tbody td {
            border: none;
            padding: 15px;
            font-size: 1rem;
            color: #333;
        }
        .table tbody tr td:first-child {
            border-radius: 10px 0 0 10px;
        }
        .table tbody tr td:last-child {
            border-radius: 0 10px 10px 0;
        }
        .btn-edit {
            background-color: #ff9800;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-edit:hover {
            background-color: #e68900;
        }
        .no-levels {
            text-align: center;
            padding: 50px;
            font-size: 1.5rem;
            color: #888;
        }
    </style>
</head>
<body>

<div class="container-main">

    <!-- Centered Home Button with Icon -->
    <div class="center-button">
        <a href="home.php" class="btn-home">
            <i class="bi bi-house-door-fill"></i> الصفحة الرئيسية
        </a>
    </div>

    <h1 class="header-title">لائحة المستويات</h1>
    
    <?php if ($result->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>اسم المستوى</th>
                    <th>السعر</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['level_name']; ?></td>
                        <td><?php echo str_replace(',', '', number_format($row['price'], 0)); ?> MRU</td>
                        <td>
                            <button class="btn-edit" data-id="<?php echo $row['id']; ?>" data-name="<?php echo $row['level_name']; ?>" data-price="<?php echo $row['price']; ?>" data-bs-toggle="modal" data-bs-target="#editModal">تعديل</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-levels">لا توجد مستويات مسجلة حاليا.</div>
    <?php endif; ?>

    <?php $conn->close(); ?>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">تعديل المستوى</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST" action="update_level.php">
                    <input type="hidden" name="level_id" id="level_id">
                    <div class="mb-3">
                        <label for="level_name" class="form-label">اسم المستوى</label>
                        <input type="text" class="form-control" id="level_name" name="level_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">السعر</label>
                        <input type="number" class="form-control" id="price" name="price" required>
                    </div>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
    // Populate modal with data when edit button is clicked
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function () {
            const levelId = this.getAttribute('data-id');
            const levelName = this.getAttribute('data-name');
            const price = this.getAttribute('data-price');

            // Set form values in the modal
            document.getElementById('level_id').value = levelId;
            document.getElementById('level_name').value = levelName;
            document.getElementById('price').value = price;
        });
    });
</script>

<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure SweetAlert2 is loaded and ready before running
        Swal.fire({
            icon: 'success',
            title: 'تم الحفظ',
            text: '   تم تعديل المستوى بنجاح!',
            confirmButtonText: 'موافق'
        }).then((result) => {
            // After the alert is closed, remove the query parameters
            if (result.isConfirmed) {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                window.history.replaceState(null, '', url); // Replace the current URL without reloading the page
            }
        });
    });
</script>
<?php endif; ?>

</body>
</html>

