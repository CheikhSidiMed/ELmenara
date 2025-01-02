<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include 'db_connection.php';

$response = []; // Initialize response array

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_student = $_POST['student_name'];
    $activity_id = $_POST['activity_id'];
    $subscription_date = $_POST['subscription_date'];

    // Fetch the student ID based on the entered name
    $stmt = $conn->prepare("SELECT id FROM students WHERE student_name = ?");
    $stmt->bind_param("s", $name_student);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $student_id = $student['id'];

        // Insert the subscription into the student_activities table
        $insert_stmt = $conn->prepare("INSERT INTO student_activities (student_id, activity_id, subscription_date) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iis", $student_id, $activity_id, $subscription_date);

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
    } else {
        $response = [
            'success' => false,
            'message' => 'لم يتم العثور على التلميذ!'
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
