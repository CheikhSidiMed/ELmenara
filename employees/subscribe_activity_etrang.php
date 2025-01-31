<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Include database connection
include 'db_connection.php';

$response = []; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['etrang_id'] ?? null; 
    $name_student = $_POST['studentName'] ?? '';
    $nni = $_POST['NNI'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $wh = $_POST['wh'] ?? '';
    $studentPhoto = $_FILES['studentPhoto'] ?? null; 
    $activity_id = $_POST['activity_id'] ?? 0;
    $subscription_date = $_POST['subscription_date'] ?? date('Y-m-d');
    $after_discount = $_POST['after_discount'] ?? 0;

    $photoPath = '';
    if (!empty($studentPhoto['name'])) {
        $targetDir = "uploads/";
        $photoPath = $targetDir . basename($studentPhoto['name']);
        
        // Move uploaded file to target directory
        if (!move_uploaded_file($studentPhoto['tmp_name'], $photoPath)) {
            die("Error uploading the photo.");
        }
    }
    if (empty($id)) {
        // Prepare the SQL query
        $sql = "INSERT INTO students_etrang (name, nni, phone, wh, img)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error preparing the statement: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param('sssss', $name_student, $nni, $phone, $wh, $photoPath);

        if ($stmt->execute()) {
            $insertedId = $conn->insert_id;

            $insert_stmt = $conn->prepare("INSERT INTO student_activities (student_id_etrang, activity_id, subscription_date, fee) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("iiss", $insertedId, $activity_id, $subscription_date, $after_discount);

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
            $stmt->close();

        } else {
            echo "Error inserting data: " . $stmt->error;
        }
    }
    else {
        $insert_stmt = $conn->prepare("INSERT INTO student_activities (student_id_etrang, activity_id, subscription_date, fee) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiss", $id, $activity_id, $subscription_date, $after_discount);

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
    }

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
