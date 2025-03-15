<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: ../home.php");
    exit;
}
$user_id = $_SESSION['userid'];

$selectedStatus = isset($_GET['status']) ? intval($_GET['status']) : 0;
$statusText = $selectedStatus ? 'المعلقين' : 'النشطين';


// Fetch student data from the database, including agent phone number
$sql = "SELECT s.id, s.start, s.phone, s.rewaya, s.days, s.tdate, s.student_name, s.part_count, s.gender, s.birth_date, s.birth_place,
           s.registration_date, s.regstration_date_count, b.branch_name AS branch_name, l.level_name AS level_name, c.class_name AS class_name,
           s.student_photo, a.phone AS agent_phone, s.payment_nature, s.fees, s.discount, s.remaining
    FROM students s
    LEFT JOIN branches b ON s.branch_id = b.branch_id
    JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ?
    LEFT JOIN levels l ON s.level_id = l.id
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN agents a ON s.agent_id = a.agent_id
    WHERE s.branch_id = '22' AND s.is_active = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $user_id, $selectedStatus);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيانات الطلاب</title>
    <link rel="shortcut icon" type="image/png" href="../../images/menar.png">
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/sweetalert2.css">
    <script src="../js/sweetalert2.min.js"></script>
    <link href="../css/bootstrap-icons.css" rel="stylesheet">
    <link href="../fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 5px;
        }
        
        h2 {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.5rem;
            color: #017B6A;
        }
        .search-box input {
            border: 2px solid #AB8568;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }
        .is_active {
            color:  #BD9237;
        }
        .container {
            margin-top: 10px;
        }
        .navbar {
            margin-bottom: 20px;
            background-color: #017B6A;
            padding-bottom: 10px;
        }
        .navbar a {
            color: white;
        }
        .table-container {
            position: relative;
            overflow-y: auto;
            max-height: 600px;
        }
        .table {
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
            border: 1px solid #ddd;
            padding: 10px; /* Add padding for better spacing */
        }
        .table th {
            background-color: #017B6A;
            color: #ffffff;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9; /* Add alternating row colors */
        }
        .table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .btn-primary {
            background-color: #017B6A;
            border-color: #017B6A;
        }
        .btn-danger {
            color: #fff !important;
        }

        .btn-primary:hover {
            background-color: #AB8568;
            border-color: #AB8568;
        }
        .btn-back {
            margin-bottom: 20px;
        }
        .home-btn .btn {
            font-size: 1.2rem;
            padding: 10px 24px;
            background-color: #017B6A;
            color: white;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        a {
            font-size: 1.6rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 5px 20px !important;
        }
        th, td{
            text-wrap: nowrap;
        }
        .navbar-brand{
            font-size: 1.8rem;
            cursor: default;
            border: none;
        }

        .home-btn .btn:hover {
            background-color: #BD9237;
        }
        .how .nav-link:hover {
            background-color: #BD9237;
        }
        .header-row .home-btn .btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }
        .home-btn .btn i {
            margin-left: 8px;
        }
        .filter-box .form-select {
            border: 2px solid #AB8568;
            padding: 10px;
            border-radius: 5px;
            width: 200px;
            text-align: right;
        }
        .search-filter-container {
            gap: 10px;
        }

        .form-select {
            border: 2px solid #AB8568;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }


        @media (max-width: 768px) {
           
            .search-filter-container {
                flex-direction: column;
            }
            
            .col-md-8, .col-md-4 {
                width: 100%;
            }
            h2, .navbar-brand {
                font-size: 1.5rem;
            }
            .nav-link {
                font-size: 1.1rem;
            }
        }

    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container pb-2">
            <div class="how">
                <a class="nav-link" href="home.php"><i class="bi bi-house-fill"></i>  الرئيسية</a>
            </div>
            <a class="navbar-brand" href="#">سجل طلاب - مقرأة المنارة والرباط</a>
        </div>
    </nav>

    
    <div class="container-full " style="direction: rtl;">
        <h2>بيانات الطلاب <span class="is_active">(<?= $statusText ?>)</span></h2>
        <div class="search-filter-container mb-3 row align-items-center">
            <div class="col-md-9 mb-2 mb-md-0">
                <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم الطالب...">
            </div>
            
            <div class="col-md-2">
                <form method="GET" id="statusFilter" class="d-flex">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="0" <?= ($selectedStatus === 0) ? 'selected' : '' ?>>الطلاب النشطين</option>
                        <option value="1" <?= ($selectedStatus === 1) ? 'selected' : '' ?>>الطلاب المعلقين</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>رقم الطالب(ة)</th>
                        <th>الاسم</th>
                        <th>الرقم</th>
                        <th>تاريخ الميلاد</th>
                        <th>تاريخ الإلتحاق</th>
                        <th>مكان الميلاد</th>
                        <th>الجنس</th>
                        <th>الرواية </th>
                        <th>البداية </th>
                        <th>الأيام المناسبة</th>
                        <th>القسم</th>
                        <th>التوقيت المناسب </th>
                        <th>الرسوم</th>
                        <th>رقم الوكيل(ة)</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody id="suspendedStudentsTableBody">
                <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $photoSrc = $row['student_photo'] !== '' ?
                                        $row['student_photo'] :
                                        '../uploads/avatar.png';
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['student_name']}</td>
                                    <td>{$row['phone']}</td>
                                    <td>{$row['birth_date']}</td>
                                    <td>" . date("Y-m-d", strtotime($row['regstration_date_count'])) . "</td>
                                    <td>{$row['birth_place']}</td>
                                    <td>{$row['gender']}</td>
                                    <td>{$row['rewaya']}</td>
                                    <td>{$row['start']}</td>
                                    <td>{$row['days']}</td>
                                    <td>{$row['class_name']}</td>
                                    <td>{$row['tdate']}</td>
                                    <td>{$row['remaining']}</td>
                                    <td>{$row['agent_phone']}</td>
                                    <td>";
        
                                    if($selectedStatus == 0) {
                                        echo "<a href='../modify_student.php?id={$row['id']}' class='h5 btn btn-primary'><i class='bi bi-pencil-square'></i> تعديل</a> ";
                                    }

                                    $btnClass = ($selectedStatus == 0) ? 'danger' : 'primary';
                                    $btnText = ($selectedStatus == 0) ? 'تعليق' : 'تنشيط';

                                    echo "<a class='h5 btn btn-{$btnClass}'
                                            onclick='confirmSuspend(event)'
                                            data-student-id='{$row['id']}'
                                            data-is-active='{$selectedStatus}'>
                                            <i class='bi bi-ban-fill'></i> {$btnText}
                                        </a>
                                        </td>
                                    </tr>";
                                    // <td><img src='{$photoSrc}' alt='student_photo' width='50' height='50' style='border-radius: 50%'></td>
                        }
                    } else {
                        echo "<tr><td colspan='16'>لا يوجد طلاب</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

<script src="../js/jquery-3.5.1.min.js"></script>
<script>
    $(document).ready(function() {
        $('#searchInput').on('input', function() {
            const value = $(this).val().toLowerCase();
            $('#suspendedStudentsTableBody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().includes(value));
            });
        });
    });
</script>


<script src="../js/sweetalert2.js"></script>

<script>
    function confirmSuspend(event) {
        event.preventDefault();
        const btn = event.currentTarget;  // Stocker le bouton avant Swal.fire
        const studentId = btn.dataset.studentId;
        const currentIsActive = <?= json_encode($selectedStatus) ?>;
        const targetStatus = currentIsActive == 1 ? 0 : 1;

        const actionMessages = {
            actionTitle: targetStatus === 1 ? 'هل أنت متأكد من التعليق؟' : 'هل أنت متأكد من التنشيط؟',
            actionText: targetStatus === 1 ? 'هل تريد فعلاً تعليق هذا الطالب؟' : 'هل تريد فعلاً تنشيط هذا الطالب؟',
            confirmText: targetStatus === 1 ? 'نعم، قم بالتعليق' : 'نعم، قم بالتنشيط',
            successTitle: targetStatus === 1 ? 'تم التعليق!' : 'تم التنشيط!',
            successText: targetStatus === 1 ? 'تم تعليق الطالب بنجاح' : 'تم تنشيط الطالب بنجاح'
        };

        Swal.fire({
            title: actionMessages.actionTitle,
            text: actionMessages.actionText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: actionMessages.confirmText,
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'up_student_active.php',
                    method: 'POST',
                    data: {
                        student_id: studentId,
                        is_active: targetStatus
                    },
                    success: function(response) {
                        Swal.fire({
                            title: actionMessages.successTitle,
                            text: actionMessages.successText,
                            icon: 'success',
                            confirmButtonText: 'حسناً'
                        });
                        window.location.reload();
                        
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'خطأ!',
                            text: `حدث خطأ أثناء ${targetStatus === 0 ? 'التعليق' : 'التنشيط'}`,
                            icon: 'error',
                            confirmButtonText: 'حسناً'
                        });
                    }
                });
            }
        });
    }

</script>


</body>
</html>

<?php
$conn->close();
?>


