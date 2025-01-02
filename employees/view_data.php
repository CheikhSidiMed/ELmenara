<?php
// Include database connection
include 'db_connection.php';

// Fetch total counts for students, branches, classes, and agents
$total_students = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];
$total_branches = $conn->query("SELECT COUNT(*) AS total FROM branches")->fetch_assoc()['total'];
$total_classes = $conn->query("SELECT COUNT(*) AS total FROM classes")->fetch_assoc()['total'];
$total_agents = $conn->query("SELECT COUNT(*) AS total FROM agents")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيانات المحظرة</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: center;
            background-color: #f8f9fa;
        }

        .hero {
            position: relative;
            background-image: url('../images/menar 2.png'); /* Replace with actual image path */
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero h1 {
            font-size: 48px;
            color: white;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
        }

        .stats-section {
            margin-top: -150px;
            display: flex;
            justify-content: space-around;
            padding: 50px 0;
        }

        .stat-box {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
            width: 250px;
            transition: transform 0.3s ease;
        }

        .stat-box:hover {
            transform: scale(1.05);
        }

        .stat-box i {
            font-size: 48px;
            color: #1BA078;
        }

        .stat-box h2 {
            font-size: 36px;
            margin-top: 20px;
            color: #333;
        }

        .stat-box p {
            font-size: 18px;
            color: #888;
            margin-top: 10px;
        }

        .footer {
            margin-top: 40px;
            padding: 20px;
            background-color: #f1f1f1;
            text-align: center;
            font-size: 16px;
            color: #666;
        }

    </style>
</head>

<body>
    <!-- Hero Section -->
    <div class="hero">
       
    </div>

    <!-- Statistics Section -->
    <div class="stats-section">
        <div class="stat-box">
            <i class="fa fa-user-graduate"></i>
            <h2><?= number_format($total_students) ?></h2>
            <p>الطلاب</p>
        </div>
        <div class="stat-box">
            <i class="fa fa-building"></i>
            <h2><?= number_format($total_branches) ?></h2>
            <p>الفروع</p>
        </div>
        <div class="stat-box">
            <i class="fa fa-chalkboard-teacher"></i>
            <h2><?= number_format($total_classes) ?></h2>
            <p>الأقسام</p>
        </div>
        <div class="stat-box">
            <i class="fa fa-user-tie"></i>
            <h2><?= number_format($total_agents) ?></h2>
            <p>الوكلاء</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© 2024 بيانات المحظرة. جميع الحقوق محفوظة.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

