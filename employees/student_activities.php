<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Fetch all activities
$sql_activities = "SELECT id, activity_name FROM activities";
$result_activities = $conn->query($sql_activities);

$activities = [];
if ($result_activities->num_rows > 0) {
    while ($row = $result_activities->fetch_assoc()) {
        $activities[] = $row;
    }
}

// Handle form submission to fetch students
$students = [];
if (isset($_GET['activity_id']) && !empty($_GET['activity_id'])) {
    $activity_id = $_GET['activity_id'];

    // Fetch students enrolled in the selected activity
    $sql_students = "
        SELECT s.id AS student_id, s.student_name, sa.subscription_date, s.gender
        FROM student_activities sa
        JOIN students s ON sa.student_id = s.id
        WHERE sa.activity_id = ?";
    
    $stmt = $conn->prepare($sql_students);
    $stmt->bind_param('i', $activity_id);
    $stmt->execute();
    $result_students = $stmt->get_result();

    if ($result_students->num_rows > 0) {
        while ($row = $result_students->fetch_assoc()) {
            $students[] = $row;
        }
    }

    $sql = "
        SELECT s.id AS student_id, s.name AS student_name, sa.subscription_date, '_' AS gender
        FROM student_activities sa
        JOIN students_etrang s ON sa.student_id_etrang = s.id
        WHERE sa.activity_id = ?";

    $stmt_s = $conn->prepare($sql);
    $stmt_s->bind_param('i', $activity_id);
    $stmt_s->execute();
    $result = $stmt_s->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}
?>



<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أنشطة الطلاب</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tajawal.css">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }
        .activity-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .activity-card h2, h1 {
            color: #007bff;
        }
        .student-list {
            margin-top: 30px;
        }
        table th, table td {
            text-align: center;
        }
        .btn-print {
            margin-top: 20px;
        }
        .receipt-header img {
            width: 100%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 20px;
        }
        @media (max-width: 576px) {
            .activity-card {
                margin-top: 15px;
            }
        }
        @media print {
            .bb {
                display: none !important;
            }
            .btn-print {
                display: none !important;
            }
        }
    </style>
</head>
<body dir="rtl">

<div class="container-full">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-10 col-sm-12">
            <div class="activity-card">
            <div class="receipt-header">
                <img src="../images/header.png" alt="Header Image">
        </div>
                <div class="col-12 d-flex justify-content-between align-items-center">
                

                    <h2 class="text-center">الطلاب المسجلين</h2>
                    <div class="d-flex align-items-center bb">
                        <a href="home.php" class="btn btn-primary d-flex align-items-center" style="margin-left: 15px;">
                        <i class="bi bi-house-fill" style="margin-left: 5px;"></i>
                            الرئيسية
                        </a>
                    </div>
                </div>
                <form method="GET" action="" class="mb-4">
                    <div class="my-3">
                        <select name="activity_id" id="activity_id" class="form-select" required onchange="this.form.submit()">
                            <option value="" disabled selected>اختر النشاط</option>
                            <?php foreach ($activities as $activity): ?>
                                <option value="<?php echo $activity['id']; ?>" <?php echo (isset($activity_id) && $activity_id == $activity['id']) ? 'selected' : ''; ?>>
                                    <?php echo $activity['activity_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <?php if (!empty($students)): ?>
                    <div class="student-list">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>تاريخ الاشتراك </th>
                                    <th>الجنس</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['student_name']; ?></td>
                                        <td><?php echo $student['subscription_date']; ?></td>
                                        <td><?php echo $student['gender']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-primary btn-print" onclick="window.print()">طباعة الصفحة</button>
                <?php elseif (isset($activity_id)): ?>
                    <div class="alert alert-warning">لا يوجد طلاب مسجلين في هذا النشاط.</div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
