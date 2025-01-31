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
    <title>تعديل بيانات حساب</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f6;
            color: #333;
        }

        .main-container {
            margin: 40px auto;
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            transition: all 0.3s ease-in-out;
        }

        .header-title {
            font-size: 32px;
            font-weight: bold;
            color: #1BA078;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .form-container {
            margin-top: 20px;
        }

        label {
            font-weight: bold;
            color: #1BA078;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 10px;
            border: 2px solid #1BA078;
            margin-top: 10px;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #14865b;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            margin-top: 30px;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 15px;
            font-size: 18px;
            text-align: center;
            vertical-align: middle;
            border: 2px solid #1BA078;
        }

        table th {
            background-color: #1BA078;
            color: white;
            font-weight: bold;
        }

        table td {
            background-color: #f9f9f9;
        }

        .info-container {
            margin-top: 30px;
        }

        .arrow-info {
            color: #1BA078;
            font-weight: bold;
            font-size: 18px;
            position: relative;
            margin-bottom: 20px;
        }

        .arrow-info:before {
            content: '⬇';
            position: absolute;
            right: -30px;
            font-size: 24px;
        }

        .textarea-info {
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #1BA078;
            width: 100%;
            height: 150px;
            background-color: #f9f9f9;
            font-size: 16px;
        }

        .confirm-button {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .btn-confirm {
            background-color: white;
            color: #1BA078;
            border: 2px solid #1BA078;
            border-radius: 10px;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }

        .btn-confirm i {
            font-size: 32px;
            margin-left: 10px;
        }

        .btn-confirm:hover {
            background-color: #f9f9f9;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-confirm:focus {
            outline: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
        }

    </style>
</head>

<body>

    <div class="container main-container">
        <h2 class="header-title">تعديل بيانات حساب</h2>

        <!-- Search and Select Section -->
        <div class="form-container">
            <div class="row">
                <div class="col-md-6">
                    <label for="search">البحث عن الحساب :</label>
                    <input type="text" id="search" class="form-control" placeholder="أدخل رقم الحساب أو الاسم">
                </div>
                <div class="col-md-6">
                    <label for="account-type">نوعية الحساب :</label>
                    <select id="account-type" class="form-select">
                        <option value="موظف">موظف</option>
                        <option value="طالب">طالب</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container mt-4">
            <table>
                <thead>
                    <tr>
                        <th>رقم الحساب</th>
                        <th>اسم الحساب</th>
                        <th>نوع الحساب</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>123456</td>
                        <td>أحمد محمد</td>
                        <td>موظف</td>
                    </tr>
                    <tr>
                        <td>654321</td>
                        <td>علي أحمد</td>
                        <td>طالب</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Info Section -->
        <div class="info-container">
            <div class="arrow-info">
                حسب اختيار المستخدم للحساب تظهر البيانات الخاصة به هنا
            </div>
            <textarea class="textarea-info" placeholder="بيانات الحساب:"></textarea>
        </div>

        <!-- Confirmation Button -->
        <div class="confirm-button">
            <button class="btn-confirm"><i class="bi bi-check"></i> تحديث التعديل</button>
        </div>
    </div>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>
