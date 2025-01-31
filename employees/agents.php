<?php

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الوكلاء</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <link rel="stylesheet" href="css/tajawal.css">
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            background: #f7f9fc;
            font-family: 'Tajawal', serif; /* Use Amiri font */
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            margin-top: 60px;
        }
        h2 {
            margin-bottom: 30px;
            color: #343a40;
            text-align: center; /* Center align title */
            font-size: 2.5rem; /* Increase font size for the title */
        }
        .form-control {
            border-radius: 5px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .form-group label {
            font-weight: bold;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .form-text {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        /* Custom styles for form elements */
        .form-row {
            margin-bottom: 20px; /* Add spacing between form rows */
        }
        button[type="submit"] {
            font-size: 1.2rem; /* Increase button text size */
        }
        .btn-block {
            padding: 15px; /* Increase padding for a better touch target */
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <button id="goHomeButton" class="btn btn-primary mb-3" onclick="window.location.href='home.php';">الذهاب إلى الصفحة الرئيسية</button>
        <h2>تسجيل الوكلاء</h2>

        <form id="AgnetForm" method="POST" action="insert_agent.php">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="phone">الهاتف</label>
                    <input type="text" class="form-control form-control-lg" id="phone" name="phone" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
                    <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="name">الإسم</label>
                    <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="أدخل الإسم" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="phone_2">2 الهاتف</label>
                    <input type="text" class="form-control form-control-lg" id="phone_2" name="phone_2" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
                    <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="profession">المهنة</label>
                    <input type="text" class="form-control form-control-lg" id="profession" name="profession" placeholder="المهنة" >
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-10">
                    <label for="whatsapp_phone">رقم هاتف الواتس اب</label>
                    <input type="text" class="form-control form-control-lg" id="whatsapp_phone" name="whatsapp_phone" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
                    <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">حفظ</button>
        </form>
        <div class="footer">
            <p>&copy; 2024 محظرة المنارة و الرباط - جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
