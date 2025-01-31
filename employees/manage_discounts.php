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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $student_id = $_POST['student_id'];
    $fees = $_POST['fees'];
    $discount = $_POST['discount'];
    $remaining = (float)$fees - (float)$discount;

   
    $query = "UPDATE students 
                  SET discount = '$discount', remaining = '$remaining' 
                  WHERE id = '$student_id'";
    

    if ($conn->query($query) === TRUE) {
        $message =  "تم تحديث معلومات بنجاح!";
    } else {
        $message = "حدث خطأ أثناء العملية: " . $conn->error;
    }
}

// Handle search query
$search_query = "SELECT * FROM students";
$students_result = $conn->query($search_query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الوكلاء</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>

    <style>
        .search-box input {
            border: 2px solid #000;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h1>إدارة الخصمات</h1>
            <a href="home.php" class="btn btn-secondary"><span>🏠</span> الصفحة الرئيسية</a>
        </div>

        <!-- Display success or error messages -->
        <?php if (isset($message)): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: '<?php echo $message; ?>',
                    showConfirmButton: false,
                    timer: 2000
                });
            </script>
        <?php endif; ?>


        <form method="POST" class="mt-2">
            <input type="hidden" name="student_id" id="student_id">
            <input type="hidden" name="fees" id="fees">
            
            <div class="row mb-5 ">
                <div class="col-md-6 mt-3">
                    <h4 id="form-title">تعديل خصم </h4>
                    <input type="text" class="form-control" id="discount" name="discount">
                </div>
                <div class="col-md-6 mt-5">
                    <button type="submit" name="action" value="update" class="btn btn-primary mt-1">تحديث</button>
                </div>

            </div>
        </form>

        <div class="search-box mb-1 mT-5">
            <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم الطالب...">
        </div>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>رقم</th>
                    <th>الاسم</th>
                    <th>الخصم</th>
                    <th>خيارات</th>
                </tr>

            </thead>
            <tbody id="suspendedStudentsTableBody">
                <?php if ($students_result->num_rows > 0): ?>
                    <?php while ($agent = $students_result->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo htmlspecialchars($agent['id']); ?></td>
                            <td><?php echo htmlspecialchars($agent['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($agent['discount'] ?? 0); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-btn" 
                                        data-id="<?php echo $agent['id']; ?>"
                                        data-fees="<?php echo $agent['fees']; ?>"
                                        data-discount="<?php echo $agent['discount']; ?>">
                                    تعديل
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">لا يوجد مطابقة للبحث.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Edit Form -->
        
    </div>

    <script>
        // Handle Edit Button Click
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById('form-title').textContent = 'تعديل معلومات';
                document.getElementById('student_id').value = button.getAttribute('data-id');
                document.getElementById('discount').value = button.getAttribute('data-discount');
                document.getElementById('fees').value = button.getAttribute('data-fees');
            });
        });

    </script>
    <script src="js/jquery-3.5.1.min.js"></script>

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
