<?php
include 'db_connection.php'; // Include your database connection script

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $phone = $_POST['phone'];
    $name = $_POST['name'];
    $phone_2 = $_POST['phone_2'];
    $profession = $_POST['profession'];
    $whatsapp_phone = $_POST['whatsapp_phone'];
    $phone_count = -1;
    // Check if the phone numbers already exist
    $check_sql = "SELECT COUNT(*) FROM agents WHERE phone = ? OR phone_2 = ? OR whatsapp_phone = ?";
    $stmt_v = $conn->prepare($check_sql);
    $stmt_v->bind_param("sss", $phone, $phone_2, $whatsapp_phone);
    $stmt_v->execute();
    $stmt_v->bind_result($phone_count);
    $stmt_v->fetch();
    $stmt_v->close();

        if ($phone_count > 0) {
        $response = array('success' => false, 'message' => 'رقم الهاتف موجود بالفعل');
    } else {
        $sql = "INSERT INTO agents (phone, agent_name, phone_2, profession, whatsapp_phone) 
                VALUES ('$phone', '$name', '$phone_2', '$profession', '$whatsapp_phone')";

        if ($conn->query($sql) === TRUE) {
            $response = array('success' => true, 'message' => 'تمت إضافة الوكيل بنجاح');
        } else {
            $response = array('success' => false, 'message' => 'حدث خطأ: ' . $conn->error);
        }
    }

    $conn->close();

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Registration</title>
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
                title: '',
                text: response.message
            }).then(function() {
                window.location.href = 'agents.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: '',
                text: response.message
            }).then(function() {
                window.location.href = 'agents.php';
            });
        }
    });
</script>
</body>
</html>
