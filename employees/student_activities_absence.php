<?php
// Connexion à la base de données
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


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
            <h1 class="text-center mb-4">إدارة غياب الطلاب في الدورات والأنشطة</h1>
            <div class="m-0">
                    <a href="home.php" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-house-gear-fill ms-1"></i> الرئيسية
                    </a>
            </div>
        </div>

        <!-- Formulaire -->
        <form id="absenceForm" method="POST" action="ab_envoyer_whatsapp_activity.php" target="_blank">

            <div class="mb-3">
                <label for="activity" class="form-label">  اختر دورة ونشاط:</label>
                <select id="activity" name="activity_id" class="form-select" required>
                    <option value="">-- اختر دورة ونشاط --</option>
                    <?php
                    // Récupération des activités depuis la base de données
                    $activities = $conn->query("SELECT id, activity_name FROM activities");
                    while ($activity = $activities->fetch_assoc()) {
                        echo "<option value='{$activity['id']}'>{$activity['activity_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div id="seansContainer" class="hidden mb-3">
                <label for="seans" class="form-label">اختر الحصة:</label>
                <select id="seans" name="session_time" class="form-select" required>
                    <option value="">-- اختر الحصة --</option>
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
const activitySelect = document.getElementById('activity');
const studentContainer = document.getElementById('studentContainer');
const studentList = document.getElementById('studentList');
const seansContainer = document.getElementById('seansContainer');

    activitySelect.addEventListener('change', () => {
        const activityId = activitySelect.value;
        if (!activityId) {
            studentContainer.classList.add('hidden');
            studentList.innerHTML = ''; // Effacer la liste précédente
            return;
        }

        // Appeler le script PHP pour récupérer les étudiants
        fetch(`students_activitie_ab_get.php?activity_id=${activityId}`)
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
                // Afficher la liste des étudiants
                studentContainer.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des étudiants:', error);
                studentContainer.classList.add('hidden');
            });
    });

    const classSelect = document.getElementById('seans');

    activitySelect.addEventListener('change', () => {
        const activityId = activitySelect.value;
        if (!activityId) {
            studentContainer.classList.add('hidden');
            seansContainer.classList.add('hidden');
            return;
        }

        fetch(`abs_get_activitie.php?activity_id=${activityId}`)
            .then(response => response.json())
            .then(data => {
                    classSelect.innerHTML = '<option value="">-- اختر الحصة --</option>';
                    data.forEach(ses => {
                        classSelect.innerHTML += `<option value="${ses}">الحصة ${ses}</option>`;
                    });
                    seansContainer.classList.remove('hidden');
            });
    });




    </script>
</body>
</html>
