<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $employee_number = $_POST['Nbr'];
    $full_name = $_POST['Nom'];
    $balance = $_POST['address'];
    $phone = $_POST['phone'];
    $job_id = $_POST['profession'];
    $salary = $_POST['salary'];
    $subscriptionDate = $_POST['Date'] ?? null;
    $id_number = $_POST['idNumber'];

    if ($subscriptionDate && $subscriptionDate !== '') {
        // Ensure the date is correctly formatted before inserting into the database
        $subscriptionDate = date('Y-m-d', strtotime($subscriptionDate));
    } else {
        $subscriptionDate = null;
    }

    // Insert data into the employees table
    $sql = "INSERT INTO employees (employee_number, full_name, balance, phone, job_id, salary, subscription_date, id_number)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssisss", $employee_number, $full_name, $balance, $phone, $job_id, $salary, $subscriptionDate, $id_number);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'تمت العملية بنجاح'];
    } else {
        $response = ['success' => false, 'message' => 'حدث خطأ أثناء إضافة الموظف: ' . $stmt->error];
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
    <title>Employee Registration</title>
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
                window.location.href = 'Employee_registration.php'; // Redirect to the employees list page or another relevant page
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: response.message
            }).then(function() {
                // Optionally, redirect to a different page after an error
                // window.location.href = 'add_employee.php';
            });
        }
    });
</script>
</body>
</html>
