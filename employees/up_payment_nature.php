<?php
// Database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}

$user_id = $_SESSION['userid'];

// Database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment_nature'])) {
    $student_id = $_POST['student_id'];
    $level_id = $_POST['level_id'];

    $payment_nature = $_POST['payment_nature'];
    $discount = $_POST['discount'] ?? null;

        $level_query = $conn->query("SELECT price FROM levels WHERE id = '$level_id'");
        $level = $level_query->fetch_assoc();
        $fees = (float)$level['price'];
        $remaining = $fees - $discount;

        if ($payment_nature == 'معفى' && $discount !== null) {
            $stmt = $conn->prepare("UPDATE students SET payment_nature = ?, discount = 0, fees =0, remaining = 0 WHERE id = ?");
            $stmt->bind_param("si", $payment_nature, $student_id);
        } else {
                $stmt = $conn->prepare("UPDATE students SET payment_nature = ?, discount = ?, fees =?, remaining = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $payment_nature, $discount, $fees, $remaining, $student_id);
    }

    if ($stmt->execute()) {
        $message = "تم تحديث طبيعة الدفع بنجاح!";
    } else {
        $message = "حدث خطأ أثناء التحديث.";
    }
    $stmt->close();
}

// Fetch all students
$sql = "SELECT s.id, s.student_name, s.payment_nature, s.discount, s.level_id
    FROM students s
    JOIN branches b ON s.branch_id = b.branch_id
    JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث طبيعة الدفع</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/cairo.css"> 
    <style>
         body {
            font-family: 'Cairo', sans-serif;
            padding: 20px;
            font-size: 20px;
            direction: rtl;
        }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between; 
            margin-bottom: 1rem; 
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="header-row">
            <h1 class="text-center mb-4">إدارة طبيعة الدفع</h1>
            <div class="m-0">
                    <a href="home.php" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-house-gear-fill ms-1"></i> الرئيسية
                    </a>
            </div>
        </div>
        <!-- Display success or error message -->
        <?php if (isset($message)): ?>
            <div class="alert alert-info text-center">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Students Table -->
        <div class="table-responsive">
            <div class="search-box mb-4">
                <input type="text" id="searchInput" class="form-control border-dark" placeholder="البحث عن طريق اسم الطالب...">
            </div>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>الرقم</th>
                        <th>اسم الطالب</th>
                        <th>طبيعة الدفع</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="suspendedStudentsTableBody">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['student_name'] ?></td>
                            <td style="color: <?= $row['payment_nature'] == 'طبيعي' ? 'green' : 'red' ?>;">
                                <?= $row['payment_nature'] ?>
                            </td>
                            
                            <td class="d-flex justify-content-between align-items-center text-center">
                                
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="student_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="level_id" value="<?= $row['level_id'] ?>">

                                    <select name="payment_nature" class="form-select form-select-sm d-inline w-auto" onchange="toggleDiscountInput(this, <?= $row['id'] ?>)">
                                        <option value="طبيعي" <?= $row['payment_nature'] == 'طبيعي' ? 'selected' : '' ?>>طبيعي</option>
                                        <option value="معفى" <?= $row['payment_nature'] == 'معفى' ? 'selected' : '' ?>>معفى</option>
                                    </select>
                                    
                                    <!-- Discount input visible only if 'معفى' is selected -->
                                    <div id="discount-input-<?= $row['id'] ?>" class="ms-2 d-inline" style="display: <?= $row['payment_nature'] == 'معفى' ? 'block' : 'none' ?>;">
                                        <label class="form-label d-inline me-2" for="discount">الخصم:</label>
                                        <input
                                        type="text"
                                        min="0"
                                        step="0"
                                        style="max-width: 70px;"
                                        name="discount" id="discount"
                                        class="form-control form-control-sm d-inline w-auto discount-field"
                                        value="<?= $row['discount'] ?? '' ?>">
                                    </div>
                                    
                                    <button type="submit" name="update_payment_nature" class="btn btn-sm btn-primary px-3 py-1">
                                        تحديث
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript to toggle the discount input based on payment_nature selection
        function toggleDiscountInput(selectElement, studentId) {
            const discountInput = document.getElementById('discount-input-' + studentId);
            if (selectElement.value === 'معفى') {
                discountInput.style.display = 'block';
            } else {
                discountInput.style.display = 'none';
            }
        }
    </script>
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
