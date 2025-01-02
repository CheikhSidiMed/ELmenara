<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Error</title>
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>
</head>
<body>

<?php
if (isset($_GET['student_name']) && isset($_GET['month'])) {
    $student_name = urldecode($_GET['student_name']);
    $month = urldecode($_GET['month']);
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'الطالب $student_name قد دفع بالفعل رسوم الشهر $month.'
            });
          </script>";
}
?>

</body>
</html>
