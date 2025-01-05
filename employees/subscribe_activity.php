<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

$response = []; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $activity_id = $_POST['activity_id'];
    $subscription_date = $_POST['subscription_date'];
    $after_discount = $_POST['after_discount']; // Correction ici


        // Insert the subscription into the student_activities table
    $insert_stmt = $conn->prepare("INSERT INTO student_activities (student_id, activity_id, subscription_date, fee) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("iiss", $student_id, $activity_id, $subscription_date, $after_discount);

    if ($insert_stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'تم التسجيل في الدورة أو النشاط بنجاح!'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'حدث خطأ أثناء التسجيل: ' . $insert_stmt->error
        ];
    }

    $insert_stmt->close();


    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Result</title>
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
                window.location.href = 'Iscrire_in_event.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: response.message
            }).then(function() {
                window.location.href = 'Iscrire_in_event.php';
            });
        }
    });
</script>
</body>
</html>
