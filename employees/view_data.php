<?php
// Include database connection
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Fetch total counts for students, branches, classes, and agents
$total_students = $conn->query("SELECT COUNT(*) AS total FROM students WHERE etat=0 AND is_active=0")->fetch_assoc()['total'];
$total_branches = $conn->query("SELECT COUNT(*) AS total FROM branches")->fetch_assoc()['total'];
$total_classes = $conn->query("SELECT COUNT(*) AS total FROM classes")->fetch_assoc()['total'];
$total_agents = $conn->query("SELECT COUNT(*) AS total FROM agents")->fetch_assoc()['total'];

$classes_query = $conn->query("
    SELECT c.class_id, c.class_name, COUNT(s.id) AS student_count
    FROM classes c
    LEFT JOIN students s ON c.class_id = s.class_id
    WHERE s.etat=0 AND s.is_active=0
    GROUP BY c.class_id, c.class_name
");
$classes_data = $classes_query->fetch_all(MYSQLI_ASSOC);

$branches_query = $conn->query("
    SELECT b.branch_id, b.branch_name, COUNT(s.id) AS student_count
    FROM branches b
    LEFT JOIN students s ON b.branch_id = s.branch_id
    WHERE s.etat=0 AND s.is_active=0
    GROUP BY b.branch_id, b.branch_name
");
$branches_data = $branches_query->fetch_all(MYSQLI_ASSOC);

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
        :root {
            --primary: #1BA078;
            --primary-dark: #148066;
            --secondary: #2D3748;
            --light: #F7FAFC;
            --dark: #1A202C;
            --gray: #EDF2F7;
        }
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
            margin-top: 20px;
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
        .stat-box {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
        }
        
        .modal-content {
            border-radius: 15px;
        }
        
        .class-student-count {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .class-student-count:last-child {
            border-bottom: none;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid #eee;
            background-color: var(--primary);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-title {
            font-weight: 700;
        }
        
        .count-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .count-item:last-child {
            border-bottom: none;
        }
        
        .badge-count {
            background-color: var(--primary);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .btn-close {
            filter: invert(1);
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
        <div class="stat-box" data-bs-toggle="modal" data-bs-target="#branchesModal">
            <i class="fa fa-building"></i>
            <h2><?= number_format($total_branches) ?></h2>
            <p>الفروع</p>
        </div>
        <div class="stat-box" data-bs-toggle="modal" data-bs-target="#classesModal">
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

    <div class="modal fade" id="classesModal" tabindex="-1" aria-labelledby="classesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="classesModalLabel">عدد الطلاب في كل قسم</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php foreach ($classes_data as $class): ?>
                        <div class="class-student-count">
                            <span><?= htmlspecialchars($class['class_name']) ?></span>
                            <span class="badge bg-primary"><?= $class['student_count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Branches Modal -->
    <div class="modal fade" id="branchesModal" tabindex="-1" aria-labelledby="branchesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="branchesModalLabel">عدد الطلاب في كل فرع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (count($branches_data) > 0): ?>
                        <?php foreach ($branches_data as $branch): ?>
                            <div class="count-item">
                                <span><?= htmlspecialchars($branch['branch_name']) ?></span>
                                <span class="badge-count"><?= $branch['student_count'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center py-3">لا توجد فروع مسجلة</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>

