<?php
// Include database connection
include 'db_connection.php'; // Adjust the path as needed

$response = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve data from POST request
    $id = $_POST['id'];
    $discount = $_POST['discount'];

    // Fetch the original fees for the student
    $sql = "SELECT fees FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($fees);
    $stmt->fetch();
    $stmt->close();

    // Calculate the new remaining balance
    $remaining = $fees - $discount;

    // Update the student's discount and remaining balance in the database
    $update_sql = "UPDATE students SET discount = ?, remaining = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ddi', $discount, $remaining, $id);

    if ($update_stmt->execute()) {
        $response = array('success' => true, 'message' => 'تم تحديث البيانات بنجاح');
    } else {
        $response = array('success' => false, 'message' => 'حدث خطأ أثناء تحديث البيانات: ' . $conn->error);
    }

    $update_stmt->close();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث الخصم</title>
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>
</head>
<body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var response = <?php echo json_encode($response); ?>;
        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'نجاح',
                text: response.message
            }).then(function() {
                // Redirect to the discounts list page after success
                window.location.href = 'discounts_list.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: response.message
            }).then(function() {
                // Optionally handle the error, e.g., redirect or reload
                window.history.back();
            });
        }
    });
</script>
</body>
</html>
