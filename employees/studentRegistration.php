<?php 

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل التلاميذ</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .card {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #4caf50, #009688);
            color: white;
            padding: 1.5rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 2rem;
        }

        h5 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #4caf50;
        }

        .form-group label {
            color: #333;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #4caf50;
        }

        .form-check {
            display: flex;
            align-items: center;
        }

        .form-check-label {
            margin-left: 8px;
            color: #333;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border: 1px solid #ddd;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }

        .form-check-input:checked {
            background-color: #4caf50;
            border-color: #4caf50;
        }

        .btn {
            background: #4caf50;
            color: white;
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background: #388e3c;
        }

        .btn:focus {
            outline: none;
        }

        .section-separator {
            border-top: 1px solid #e0e0e0;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .form-group {
            flex: 0 0 48%;
        }

        /* Smaller form elements for gender and others */
        .form-group.select {
            flex: 0 0 30%;
        }

        .form-group.file {
            flex: 0 0 100%;
        }

        /* Custom checkbox styling */
        .form-check-input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }

    </style>
</head>
<body>

<div class="container">
    <!-- Card containing the registration form -->
    <div class="card">
        <div class="card-header text-center">
            تسجيل التلاميذ
        </div>

        <div class="card-body">
            <!-- Registration form -->
            <form id="studentRegistrationForm" enctype="multipart/form-data" method="POST" action="student.php" accept-charset="UTF-8">

                <!-- Section 1: Agent Information -->
                <div id="agentSection" class="form-row">
                    <h5>تعريف الوكيل</h5>
                    <div class="form-group">
                        <label for="agentPhone">رقم الهاتف</label>
                        <input type="number" class="form-control" id="agentPhone" name="agentPhone" placeholder="أدخل رقم الهاتف" required>
                    </div>
                    <div class="form-group d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="noAgentCheckbox" name="noAgentCheckbox">
                            <label class="form-check-label" for="noAgentCheckbox">بدون وكيل</label>
                        </div>
                    </div>
                </div>

                <div class="section-separator"></div>

                <!-- Section 2: Student Information -->
                <div id="studentSection" class="form-row">
                    <h5>تعريف الطالب</h5>
                    <div class="form-group">
                        <label for="studentId">رقم تعريف طالب</label>
                        <input type="number" class="form-control" id="studentId" name="studentId" placeholder="أدخل رقم تعريف طالب" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="studentName">الإسم الكامل</label>
                        <input type="text" class="form-control" id="studentName" name="studentName" placeholder="أدخل الإسم الكامل" required>
                    </div>
                    <div class="form-group">
                        <label for="partCount">عدد الأحزاب</label>
                        <input type="number" class="form-control" id="partCount" name="partCount" placeholder="أدخل عدد الأحزاب" min="0" required>
                    </div>
                    <div class="form-group select">
                        <label for="gender">الجنس</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="">اختر الجنس</option>
                            <option value="ذكر">ذكر</option>
                            <option value="أنثى">أنثى</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="birthDate">تاريخ الميلاد</label>
                        <input type="date" class="form-control" id="birthDate" name="birthDate" required>
                    </div>
                    <div class="form-group">
                        <label for="birthPlace">مكان الميلاد</label>
                        <input type="text" class="form-control" id="birthPlace" name="birthPlace" placeholder="أدخل مكان الميلاد" required>
                    </div>
                    <div class="form-group">
                        <label for="registrationDate">تاريخ التسجيل</label>
                        <input type="date" class="form-control" id="registrationDate" name="registrationDate" required>
                    </div>
                    <div class="form-group select">
                        <label for="branch">الفرع</label>
                        <select class="form-control" id="branch" name="branch" required>
                            <option value="">اختر الفرع</option>
                        </select>
                    </div>
                    <div class="form-group select">
                        <label for="class">القسم</label>
                        <select class="form-control" id="class" name="class" required>
                            <option value="">اختر القسم</option>
                        </select>
                    </div>
                    <div class="form-group file">
                        <label for="studentPhoto">الصورة</label>
                        <input type="file" class="form-control" id="studentPhoto" name="studentPhoto">
                    </div>
                    <div class="form-group select">
                        <label for="level">المستوى</label>
                        <select class="form-control" id="level" name="level" required>
                            <option value="">اختر المستوى</option>
                        </select>
                    </div>
                </div>

                <div class="section-separator"></div>

                <!-- Section 3: Payment Nature -->
                <div id="paymentNatureSection" class="form-row">
                    <h5>طبيعة التسديد</h5>
                    <div class="form-group d-flex justify-content-between">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentNature" id="naturalPayment" value="طبيعي" required>
                            <label class="form-check-label" for="naturalPayment">طبيعي</label>
                        </div>
                        <?php if ($role_id == 1): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentNature" id="exemptPayment" value="معفى" required>
                            <label class="form-check-label" for="exemptPayment">معفى</label>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="section-separator"></div>

                <!-- Section 4: Monthly Fees -->
                <div id="monthlyFeesSection" class="form-row">
                    <h5>الرسوم الشهرية</h5>
                    <div class="form-group">
                        <label for="fees">الرسوم</label>
                        <input type="number" class="form-control" id="fees" name="fees" placeholder="أدخل الرسوم" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="discount">الخصم</label>
                        <input type="number" class="form-control" id="discount" name="discount" placeholder="أدخل الخصم" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="remaining">المتبقى</label>
                        <input type="number" class="form-control" id="remaining" name="remaining" placeholder="أدخل المتبقى" min="0" readonly>
                    </div>
                </div>

                <div class="form-group text-center">
                    <button type="submit" class="btn">حفظ</button>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>
