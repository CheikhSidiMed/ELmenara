<?php
include 'db_connection.php'; // Include your database connection script

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $branch_name = $_POST['branchName'];

    // Corrected SQL statement
    $sql = "INSERT INTO branches (branch_name) VALUES ('$branch_name')";

    if ($conn->query($sql) === TRUE) {
        $response = array('success' => true, 'message' => 'تمت العملية بنجاح ');
    } else {
        $response = array('success' => false, 'message' => 'Oops! Something went wrong: ' . $conn->error);
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bank Account Creation</title>
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
                // Optionally redirect or do something after success message
                 window.location.href = 'home.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: response.message
            }).then(function() {
                // Optionally redirect or do something after error message
                // window.location.href = 'some_other_page.php';
            });
        }
    });
</script>
</body>
</html>

