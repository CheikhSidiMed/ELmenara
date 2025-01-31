<?php
// insert_activity.php

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Include database connection
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $activity_name = $_POST['activity_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $price = $_POST['price'];
    $session = $_POST['session'];


    // Prepare SQL to insert data
    $sql = "INSERT INTO activities (activity_name, start_date, end_date, price, session) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die('Error preparing the statement: ' . $conn->error);
    }

    $stmt->bind_param('sssds', $activity_name, $start_date, $end_date, $price, $session);

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'تم إنشاء الدورة أو النشاط بنجاح!'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الدورة أو النشاط: ' . $stmt->error
        ];
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Creation</title>
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script></head>
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
                window.location.href = 'home.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: response.message
            }).then(function() {
                window.location.href = 'previous_page.php'; // Change this to the desired redirection page on error
            });
        }
    });
</script>
</body>
</html>
