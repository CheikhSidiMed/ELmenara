<?php
// Connexion à la base de données
include 'db_connection.php';

if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة غياب الطلاب</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tajawal.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/cairo.css"> 

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            padding: 20px;
            font-size: 20px;
            direction: rtl;
        }
        h1 {
            color: #007bff;
            margin-bottom: 20px;
        }
        .hidden {
            display: none;
        }
        button {
            margin-bottom: 40px;
        }
        .btn-send {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn-send:hover {
            background-color: #218838;
        }
        table {
            margin-top: 20px;
        }
        th, td {
            text-align: center;
        }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between; 
            margin-bottom: 1rem; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-row">
            <h1 class="text-center mb-4"> إدارة غياب الطلاب</h1>
            <div class="m-0">
                    <a href="home.php" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-house-gear-fill ms-1"></i> الرئيسية
                    </a>
            </div>
        </div>

        <!-- Formulaire -->
        <form id="absenceForm" method="POST" action="ab_envoyer_whatsapp.php" target="_blank">
            <div class="mb-3">
                <label for="session_time" class="form-label">اختر توقيت الحصة:</label>
                <select id="session_time" name="session_time" class="form-select" required>
                    <option value="">-- اختر التوقيت --</option>
                    <?php
                    // Options prédéfinies pour les heures des séances
                    $session_times = [
                        'صباحًا' => ' صباحًا',
                        'مساءً' => ' مساءً',
                    ];

                    // Générer les options
                    foreach ($session_times as $time => $label) {
                        echo "<option value=\"$label\">$label</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="branch" class="form-label">اختر الفرع:</label>
                <select id="branch" name="branch_id" class="form-select" required>
                    <option value="">-- اختر الفرع --</option>
                    <?php
                    $branches = $conn->query("SELECT branch_id, branch_name FROM branches");
                    while ($branch = $branches->fetch_assoc()) {
                        echo "<option value='{$branch['branch_id']}'>{$branch['branch_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div id="classContainer" class="hidden mb-3">
                <label for="class" class="form-label">اختر الفصل:</label>
                <select id="class" name="class_id" class="form-select" required>
                    <option value="">-- اختر الفصل --</option>
                </select>
            </div>

            <div id="studentContainer" class="hidden">
                <h2>قائمة الطلاب</h2>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>اسم الطالب</th>
                            <th>رقم الهاتف</th>
                            <th>غياب</th>
                        </tr>
                    </thead>
                    <tbody id="studentList"></tbody>
                </table>
                <div class="text-center">
                    <button type="submit" class="btn btn-send" >إرسال رسائل واتساب</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const branchSelect = document.getElementById('branch');
        const classSelect = document.getElementById('class');
        const classContainer = document.getElementById('classContainer');
        const studentContainer = document.getElementById('studentContainer');
        const studentList = document.getElementById('studentList');

        // تحميل الفصول حسب الفرع
        branchSelect.addEventListener('change', () => {
            const branchId = branchSelect.value;
            if (!branchId) {
                classContainer.classList.add('hidden');
                studentContainer.classList.add('hidden');
                return;
            }
            fetch(`ab_get_classes.php?branch_id=${branchId}`)
                .then(response => response.json())
                .then(data => {
                    classSelect.innerHTML = '<option value="">-- اختر الفصل --</option>';
                    data.forEach(classe => {
                        classSelect.innerHTML += `<option value="${classe.class_id}">${classe.class_name}</option>`;
                    });
                    classContainer.classList.remove('hidden');
                });
        });

        // تحميل الطلاب حسب الفصل
        classSelect.addEventListener('change', () => {
            const classId = classSelect.value;
            if (!classId) {
                studentContainer.classList.add('hidden');
                return;
            }
            fetch(`ab_get_students.php?class_id=${classId}`)
                .then(response => response.json())
                .then(data => {
                    studentList.innerHTML = '';
                    data.forEach(student => {
                        const phone = (!student.phone || student.phone === '0') ? student.whatsapp_phone : student.phone;
                        studentList.innerHTML += `
                            <tr>
                                <td>${student.student_name}</td>
                                <td>${phone}</td>
                                <td><input type="checkbox" name="absent_students[]" value="${student.id}"></td>
                            </tr>`;
                    });
                    studentContainer.classList.remove('hidden');
                });
        });
    </script>
</body>
</html>
