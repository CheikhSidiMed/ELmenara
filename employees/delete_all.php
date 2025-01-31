<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tablesWithConditions = [
        'payments' => "remaining_amount=0.00",
        'transactions' => "1=1",
    ];

    // Begin transaction
    $conn->begin_transaction();

    try {
        foreach ($tablesWithConditions as $table => $condition) {
            $sql = "DELETE FROM $table WHERE $condition";
            
            if (!$conn->query($sql)) {
                throw new Exception("Error deleting rows in table $table: " . $conn->error);
            }
        }
        $conn->commit();
        $message = "تم مسح كافة الجداول بنجاح.";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>مسح جداول قاعدة البيانات</title>
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>
    <style>
        .container { text-align: center; margin-top: 50px; }
        .btn-delete { background-color: red; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; }
        .message { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>مسح جميع البيانات</h2>
    <p>تحذير: هذه العملية ستحذف جميع البيانات في الجداول المحددة.</p>
    <form id="deleteForm" method="post">
        <button type="button" class="btn-delete" onclick="confirmDelete()">حذف جميع السجلات</button>
    </form>

    <?php if (!empty($message)): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
</div>

<script>
function confirmDelete() {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "سيؤدي ذلك إلى حذف جميع البيانات في الجداول بشكل دائم!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذفها!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteForm').submit();
        }
    });
}

// Display success message if deletion was successful
<?php if (!empty($message) && strpos($message, 'بنجاح') !== false): ?>
    Swal.fire({
        icon: 'success',
        title: 'نجاح!',
        text: '<?php echo $message; ?>',
    });
<?php elseif (!empty($message)): ?>
    Swal.fire({
        icon: 'error',
        title: 'خطأ',
        text: '<?php echo $message; ?>',
    });
<?php endif; ?>
</script>


</body>
</html>
