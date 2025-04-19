<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}
$user_id = $_SESSION['userid'];

if (isset($_GET['valider_etudiant'])) {
    $studentId = intval($_GET['valider_etudiant']);
    
    // Update student etat to 0
    $stmt = $conn->prepare("UPDATE students SET etat = 0 WHERE id = ?");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();

    header("Location: ".$_SERVER['PHP_SELF']."?success=1");
    exit;
}

// Fetch student data from the database, including agent phone number
$sql = "SELECT s.id, s.student_name, s.part_count, s.gender, s.birth_date, s.birth_place,
           s.registration_date, s.regstration_date_count, b.branch_name AS branch_name, l.level_name AS level_name, c.class_name AS class_name, 
           s.student_photo, a.phone AS agent_phone, s.payment_nature, s.fees, s.discount, s.remaining
    FROM students s
    LEFT JOIN branches b ON s.branch_id = b.branch_id
    JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ?
    LEFT JOIN levels l ON s.level_id = l.id
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN agents a ON s.agent_id = a.agent_id
    WHERE s.etat=1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيانات الطلاب</title>
    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
    <link href="css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sweetalert2.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #017B6A;
            --primary-light: #019984;
            --secondary: #BD9237;
            --accent: #AB8568;
            --light: #F8F9FA;
            --dark: #212529;
            --gray: #E9ECEF;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            margin: 0;
            padding: 0;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .page-title {
            color: var(--primary);
            font-weight: 700;
            margin: 2rem 0;
            text-align: center;
            font-size: 2.5rem;
            position: relative;
        }
        
        .page-title::after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: var(--secondary);
            margin: 0.5rem auto;
            border-radius: 2px;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .search-box input {
            border: 2px solid var(--primary);
            padding: 12px 20px;
            border-radius: 50px;
            width: 100%;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .search-box input:focus {
            border-color: var(--secondary);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            outline: none;
        }
        
        .search-box::before {
            content: '\f002';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 3rem;
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            padding: 15px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table td {
            padding: 12px 15px;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray);
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tbody tr:hover {
            background-color: rgba(1, 123, 106, 0.05);
        }
        
        .table tbody tr:nth-child(even) {
            background-color: rgba(1, 123, 106, 0.02);
        }
        
        .student-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gray);
            transition: all 0.3s ease;
        }
        
        .student-photo:hover {
            transform: scale(1.1);
            border-color: var(--secondary);
        }
        
        .btn-success {
            background-color: var(--secondary);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-success:hover {
            background-color: #a87d2a;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(189, 146, 55, 0.3);
        }
        
        .btn-success i {
            margin-left: 8px;
        }
        
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: var(--accent);
            font-size: 1.2rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.4rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .table th, .table td {
                padding: 10px 8px;
                font-size: 0.9rem;
            }
            
            .student-photo {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap me-2"></i>منصة البيانات الطلابية
            </a>
            <div class="d-flex">
                <a class="nav-link" href="home.php">
                    <i class="fas fa-home me-1"></i> الصفحة الرئيسية
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <h1 class="page-title"> قائمة إنتظار الطلاب</h1>
        
        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="searchInput" class="form-control" placeholder="ابحث عن طريق اسم الطالب...">
        </div>
        
        <!-- Table Container -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>رقم الطالب</th>
                            <th>الاسم</th>
                            <th>عدد الأحزاب</th>
                            <th>الجنس</th>
                            <th>تاريخ الميلاد</th>
                            <th>مكان الميلاد</th>
                            <th>تاريخ التسجيل</th>
                            <th>تاريخ التسدد</th>
                            <th>الفرع</th>
                            <th>القسم</th>
                            <th>المستوى</th>
                            <th>الصورة</th>
                            <th>هاتف الوكيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="suspendedStudentsTableBody">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    $photoSrc = $row['student_photo'] !== '' ?
                                        $row['student_photo'] :
                                        'uploads/avatar.png';
                                ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= $row['student_name'] ?></td>
                                    <td><?= $row['part_count'] ?></td>
                                    <td><?= $row['gender'] ?></td>
                                    <td><?= $row['birth_date'] ?></td>
                                    <td><?= $row['birth_place'] ?></td>
                                    <td><?= $row['regstration_date_count'] ?></td>
                                    <td><?= $row['registration_date'] ?></td>
                                    <td><?= $row['branch_name'] ?></td>
                                    <td><?= $row['class_name'] ?></td>
                                    <td><?= $row['level_name'] ?></td>
                                    <td>
                                        <img src="<?= $photoSrc ?>" alt="صورة الطالب" class="student-photo">
                                    </td>
                                    <td><?= $row['agent_phone'] ?></td>
                                    <td>
                                        <a href="?valider_etudiant=<?= $row['id'] ?>" class="btn btn-success btn-validate">
                                            <i class="fas fa-check-circle"></i> تسجيل
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="14">
                                    <div class="empty-state">
                                        <i class="fas fa-user-graduate"></i>
                                        <p>لا يوجد طلاب </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/sweetalert2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Search functionality
            $('#searchInput').on('input', function() {
                const value = $(this).val().toLowerCase();
                $('#suspendedStudentsTableBody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().includes(value));
                });
            });
            
            // Add animation to table rows
            $('#suspendedStudentsTableBody tr').each(function(i) {
                $(this).delay(i * 50).animate({ opacity: 1 }, 200);
            });
        });
    </script>

    <script>
        // Intercept disable button clicks for confirmation
        document.querySelectorAll('.btn-disable').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');

            Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "سيتم تحديث حساب الطالب!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، تحديث',
            cancelButtonText: 'إلغاء',
            reverseButtons: true
            }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
            });
        });
        });

        // Show success message after redirect
        <?php if (isset($_GET['success'])): ?>
        Swal.fire({
        title: 'تم بنجاح',
        text: 'تم تحديث حساب الطالب.',
        icon: 'success',
        confirmButtonText: 'موافق'
        }).then(() => {
            if (window.history.replaceState) {
                const cleanUrl = window.location.origin + window.location.pathname;
                window.history.replaceState(null, '', cleanUrl);
            };});
        <?php endif; ?>
    </script>

</body>
</html>

<?php
$conn->close();
?>


