<?php
session_start();
error_reporting(0);
include('includes/dbconn.php'); // Ensure dbconn.php is correctly included

if (isset($_POST['signin'])) {
    $uname = $_POST['username'];
    $password = $_POST['password'];

    // Fetch the user by username and password
    $sql = "SELECT u.id, u.username, u.password, u.role_id, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.username = :uname AND u.password = :password"; // Direct password matching
    $query = $dbh->prepare($sql);
    $query->bindParam(':uname', $uname, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);

    // If user exists
    if ($result) {
        $_SESSION['userid'] = $result->id;
        $_SESSION['username'] = $result->username;
        $_SESSION['role_id'] = $result->role_id; // Store the role_id in the session


        // Redirect to the same page regardless of role
        echo "<script type='text/javascript'> document.location = 'employees/home.php'; </script>";
    } else {
        echo "<script>alert('Désolé, les détails ne sont pas valides.');</script>";
    }
}
?>





<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>محظرة المنارة و الرباط</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="images/menar.png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/center.css">
    <link rel="stylesheet" href="assets/css/themify-icons.css">
    <link rel="stylesheet" href="assets/css/metisMenu.css">
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/css/slicknav.min.css">
    <!-- amchart css -->
    <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" />
    <!-- others css -->
    <link rel="stylesheet" href="assets/css/typography.css">
    <link rel="stylesheet" href="assets/css/default-css.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <!-- modernizr css -->
    <script src="assets/js/vendor/modernizr-2.8.3.min.js"></script>
   
</head>

<body>
    <!-- preloader area start -->
    <div id="preloader">
        <div class="loader"></div>
    </div>
    <!-- preloader area end -->
    <!-- login area start -->
    <div class="login-area login-s2" style="margin-top: 5px;">
        <div class="container">
            <div class="login-box ptb--100">
                <form method="POST" name="signin">
                    <div class="login-form-head">
                        <center><img width="250" height="auto" src="images/menar.png"></center>
                    </div>
                    <div class="login-form-body">
                        <div class="form-gp">
                            <label for="exampleInputEmail1">  اسم المستخدم</label>
                            <input type="text" id="username" name="username" autocomplete="off" required>
                            <i class="ti-email"></i>
                            <div class="text-danger"></div>
                        </div>
                        <div class="form-gp">
                            <label for="exampleInputPassword1">كلمه مرور</label>
                            <input type="password" id="password" name="password" autocomplete="off" required>
                            <i class="ti-lock"></i>
                            <div class="text-danger"></div>
                        </div>
                        
                        <div class="submit-btn-area">
                            <button id="form_submit" type="submit" name="signin">تسجيل الدخول <i class="ti-arrow-right"></i></button>
                        </div>
                        
                        

                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- login area end -->

    <!-- jquery latest version -->
    <script src="assets/js/vendor/jquery-2.2.4.min.js"></script>
    <!-- bootstrap 4 js -->
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/jquery.slimscroll.min.js"></script>
    <script src="assets/js/jquery.slicknav.min.js"></script>
    
    <!-- others plugins -->
    <script src="assets/js/plugins.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>

</html>