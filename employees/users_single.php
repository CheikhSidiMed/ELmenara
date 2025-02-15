<?php
// Start the session and include the database connection
    include 'db_connection.php';


    session_start();

    if (!isset($_SESSION['userid'])) {
        echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
        header("Location: home.php");
        exit;
    }
    $user_id = $_SESSION['userid'];

    $query = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();


    // Update user information
    if (isset($_POST['edit_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];


        $updateQuery = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
        $updateQuery->bind_param("ssi", $username, $password, $user_id);

        if ($updateQuery->execute()) {

            $_SESSION['edit_success'] = true;
            header('Location: users_single.php');
            exit();
        } else {
            echo "<script>alert('حدث خطأ أثناء محاولة تحديث المستخدم.');</script>";
        }
    }

    $editSuccess = false;
    if (isset($_SESSION['edit_success'])) {
        $editSuccess = true;
        unset($_SESSION['edit_success']);
    }

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المستخدم</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css">
    <script src="js/sweetalert2.min.js"></script>
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f7f7f7;
            padding: 30px;
        }

        .container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        h2 {
            color: #1BA078;
            font-weight: bold;
            font-size: 37px;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-label {
            font-weight: bold;
            color: #1BA078;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #1BA078;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 16px;
            background-color: #f9f9f9;
        }

        .form-control:focus {
            border-color: #14865b;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary, .home-btn {
            background-color: #1BA078;
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            font-size: 18px;
            transition: background-color 0.3s ease;
            display: block;
            width: 100%;
        }
        a{
            text-align: center;
            text-decoration: none;
            color: white;
        }
        a:hover{
            text-decoration: none;
        }

        .btn-primary:hover {
            background-color: #14865b;
        }

        .btn-primary:focus {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>


    <?php if ($editSuccess) { ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'تم تحديث المستخدم بنجاح',
                showConfirmButton: false,
                timer: 1500
            });
        </script>
    <?php } ?>



    <div class="container mt-5">
        <h2>تعديل </h2>
        <a href="home.php" class="home-btn"><i class="bi bi-house-fill"></i> الصفحة الرئيسية</a>

        <form id="editUserForm" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">اسم المستخدم</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور</label>
                <input type="password" class="form-control" id="password" name="password" value="<?= htmlspecialchars($user['password']); ?>" required>
            </div>

            <button type="submit" name="edit_user" class="btn btn-primary">تحديث </button>
        </form>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>

</body>
</html>
