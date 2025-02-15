<?php
// Include database connection
include 'db_connection.php';

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
    $role_p = $_POST['role'];
    $class_id_p = $_POST['class'] ?? 0;
    $branch_id = $_POST['branch'];

    if ($subscriptionDate && $subscriptionDate !== '') {
        $subscriptionDate = date('Y-m-d', strtotime($subscriptionDate));
    } else {
        $subscriptionDate = null;
    }

    $sql = "INSERT INTO employees (employee_number, full_name, balance, phone, job_id, salary, subscription_date, id_number)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdsisss", $employee_number, $full_name, $balance, $phone, $job_id, $salary, $subscriptionDate, $id_number);

    if ($stmt->execute()) {
        $emply_id = $stmt->insert_id;

        $user_stmt = $conn->prepare("SELECT id FROM users WHERE employee_id = ?");
        $user_stmt->bind_param("i", $emply_id);
        $user_stmt->execute();
        $user_stmt->bind_result($user_id);
        $user_stmt->fetch();
        $user_stmt->close();

        if ($user_id) {
            $strQ = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $strQ->bind_param("ii", $role_p, $user_id);
            if ($strQ->execute()) {
                $br_us_stmt = $conn->prepare("INSERT INTO user_branch (branch_id, class_id, user_id) VALUES (?, ?, ?)");
                if (!$br_us_stmt) {
                    echo json_encode(['success' => false, 'message' => 'Erreur préparation user_branch: ' . $conn->error]);
                    exit;
                }
                $br_us_stmt->bind_param("iii", $branch_id, $class_id_p, $user_id);
                if (!$br_us_stmt->execute()) {
                    echo json_encode(['success' => false, 'message' => 'Erreur insertion user_branch: ' . $br_us_stmt->error]);
                    exit;
                }
                $br_us_stmt->close();
                $response = ['success' => true, 'message' => 'تمت العملية بنجاح'];
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur: Aucun utilisateur associé à cet employé']);
            exit;
        }
    
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
