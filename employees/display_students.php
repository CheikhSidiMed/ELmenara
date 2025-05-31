<?php
    
    include 'db_connection.php';
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}
$user_id = $_SESSION['userid'];

if (isset($_POST['delete_student_id'])) {
    $student_id = $_POST['delete_student_id'];

    $sql = "UPDATE students SET is_active = 2 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'تم حذف الطالب بنجاح']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل في حذف الطالب أو الطالب غير موجود']);
    }

    $stmt->close();
    exit();
}


$selectedStatus = isset($_GET['status']) ? intval($_GET['status']) : 0;
$statusText = $selectedStatus ? 'المعلقين' : 'النشطين';

// Fetch student data from the database, including agent phone number
$sql = "SELECT s.id, s.student_name, s.part_count, s.gender, s.birth_date, s.birth_place,
           s.registration_date, s.regstration_date_count, b.branch_name AS branch_name, l.level_name AS level_name, c.class_name AS class_name, 
           s.student_photo, a.phone AS agent_phone, s.suspension_reason, s.balance, s.payment_nature, s.fees, s.discount, s.remaining
    FROM students s
    LEFT JOIN branches b ON s.branch_id = b.branch_id
    JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ?
    LEFT JOIN levels l ON s.level_id = l.id
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN agents a ON s.agent_id = a.agent_id
    WHERE s.branch_id != '22' AND s.etat = 0  AND s.is_active = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $user_id, $selectedStatus);
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
            --secondary2: #c62828;
            --secondary1: #ffc107;
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
        
        .btn-edit, .btn-disb, .btn-del, .btn-act , .btn-ivd {
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-edit{
            background-color: var(--secondary);
        }
        .btn-disb {
            background-color: var(--secondary1);
        }
        .btn-del {
            background-color: var(--secondary2);
        }
        .btn-ivd {
            background-color: #380;
        }
        .btn-act {
            background-color: var(--primary);
        }
        
        .btn-edit:hover {
            background-color: #a87d2a;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px #30021c;
        }
        
        .btn-idv:hover {
            background-color: #3856;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px #380;
        }
        .btn-disb:hover {
            background-color: #e0a800;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px #30021c;
        }
        .btn-act:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px #30021c;
        }
        
        .btn-del:hover {
            background-color: #c82333;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px #30021c;
        }
        
        .btn-edit i {
            margin-left: 8px;
        }
        .btn-disb i {
            margin-left: 8px;
        }
        .btn-del i {
            margin-left: 8px;
        }
        
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: var(--accent);
            font-size: 1.2rem;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray);
        }

        .status-filter {
            position: relative;
            margin-bottom: 2rem;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }

        .status-filter select {
            border: 2px solid var(--primary);
            padding: 12px 20px;
            border-radius: 50px;
            width: 100%;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            appearance: none;
            background: url('data:image/svg+xml;utf8,<svg fill="%23333" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 20px center;
            background-color: #fff;
            background-size: 16px;
        }

        .status-filter select:focus {
            border-color: var(--secondary);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            outline: none;
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
        <h1 class="page-title">بيانات الطلاب</h1>
        
        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="searchInput" class="form-control" placeholder="ابحث عن طريق اسم الطالب...">
        </div>
        <div class="status-filter">
            <form method="GET" id="statusFilter" class="d-flex" dir="ltr">
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="0" <?= ($selectedStatus === 0) ? 'selected' : '' ?>>الطلاب النشطين</option>
                    <option value="1" <?= ($selectedStatus === 1) ? 'selected' : '' ?>>الطلاب المعلقين</option>
                </select>
            </form>
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
                            <?php
                                if ($selectedStatus == 1) {
                                    echo "<th>المبلغ المتبقي</th>";
                                    echo "<th>السبب</th>";
                                }
                            ?>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="suspendedStudentsTableBody">
                        <?php if ($result->num_rows > 0):
                                $btnClass = ($selectedStatus == 0) ? 'btn-disb' : 'btn-act';
                                $btnText = ($selectedStatus == 0) ? 'تعليق' : 'تنشيط';
                            ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    $photoSrc = $row['student_photo'] !== '' || $row['student_photo'] !== null ?
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
                                    <?php
                                        if ($selectedStatus == 1) {
                                            echo "<td data-field='agent_phone'>{$row['balance']}</td>";
                                            echo "<td data-field='agent_phone'>{$row['suspension_reason']}</td>";
                                        }
                                    ?>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="modify_student.php?id=<?= $row['id'] ?>" class="btn-edit">
                                                <i class="fas fa-edit"></i> تعديل
                                            </a>
                                            <a href="javascript:void(0);"
                                                class="<?= $btnClass ?>"
                                                onclick="confirmSuspend(event)"
                                                data-student-id="<?= $row['id']; ?>"
                                                data-is-active="<?= $selectedStatus ?>">
                                                <i class="fas fa-user-slash"></i> <?= $btnText ?>
                                            </a>

                                            <a href="javascript:void(0);" onclick="confirmDelete(<?= $row['id']; ?>)" class="btn-del">
                                                <i class="fas fa-trash"></i>فصل
                                            </a>

                                            <a href="javascript:void(0);"
                                                onclick="createIvada(event)"
                                                data-student-id="<?= $row['id']; ?>"
                                                class="btn-ivd">
                                                إفادة
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="14">
                                    <div class="empty-state">
                                        <i class="fas fa-user-graduate"></i>
                                        <p>لا يوجد طلاب مسجلين</p>
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

        function confirmDelete(student_id) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من التراجع عن هذا!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، احذفه!',
                cancelButtonText: 'لا، إلغاء!',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteStudent(student_id);
                }
            });
        }

        function deleteStudent(student_id) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من التراجع عن هذا الإجراء!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، حذف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'display_students.php',
                        type: 'POST',
                        data: { delete_student_id: student_id },
                        success: function(response) {
                            let result = JSON.parse(response);
                            if (result.status === 'success') {
                                Swal.fire({
                                    title: 'تم الحذف!',
                                    text: 'تم حذف الطالب بنجاح.',
                                    icon: 'success',
                                    confirmButtonText: 'حسنًا'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('خطأ!', result.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('خطأ!', 'فشل في الاتصال بالخادم.', 'error');
                        }
                    });
                }
            });
        }


    
    function confirmSuspend(event) {
        event.preventDefault();
        const btn = event.currentTarget;
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
                if(targetStatus == 1){
                    suspendStudent(studentId, targetStatus, actionMessages.successTitle, actionMessages.successText);
                }else{
                    processSuspendStudent(studentId, '', targetStatus, actionMessages.successTitle, actionMessages.successText);
                }
            }
        });
    }

            // Function to suspend student
    function suspendStudent(student_id, targetStatus, msg1, msg2) {
        Swal.fire({
            title: 'سبب التعليق',
            input: 'text',
            inputPlaceholder: 'أدخل سبب تعليق التلميذ',
            showCancelButton: true,
            confirmButtonText: 'تعليق',
            cancelButtonText: 'إلغاء',
            inputValidator: (value) => {
                if (!value) {
                    return 'يجب عليك إدخال سبب التعليق!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let suspension_reason = result.value;
                processSuspendStudent(student_id, suspension_reason, targetStatus, msg1, msg2);
            }
        });
    }
    
    function createIvada(event) {
    event.preventDefault();
    const btn = event.currentTarget;
    const studentId = btn.dataset.studentId;

    Swal.fire({
        title: 'إفادة تحديد المستوى عند خروج الطالب',
        html: `
            <input id="swal-status" class="swal2-input" placeholder="أدخل الحالة العامة للمحفوظات">
            <textarea id="swal-note" class="swal2-textarea" placeholder="أدخل الملحوظات"></textarea>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'إنشاء',
        cancelButtonText: 'إلغاء',
        preConfirm: () => {
            const status = document.getElementById('swal-status').value.trim();
            const note = document.getElementById('swal-note').value.trim();
            if (!status || !note) {
                Swal.showValidationMessage('يجب ملء الحقول: الحالة العامة والملحوظات');
                return false;
            }
            return { status, note };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const status = result.value.status;
            const note = result.value.note;

            // Create a hidden form and submit it to a new tab
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'document-management/pdf_exit.php';
            form.target = '_blank';

            const inputs = [
                { name: 'student_id', value: studentId },
                { name: 'status', value: status },
                { name: 'note', value: note }
            ];

            inputs.forEach(inputData => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = inputData.name;
                input.value = inputData.value;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    });
}


    // AJAX to process suspension
    function processSuspendStudent(student_id, suspension_reason, targetStatus, msg1, msg2) {
        $.ajax({
            url: 'up_del_student.php',
            type: 'POST',
            data: {
                suspend_student_id: student_id,
                targetStatus: targetStatus,
                suspension_reason: suspension_reason
            },
            success: function(response) {
                let result = JSON.parse(response);
                if (result.status === 'success') {
                    Swal.fire(msg1, msg2, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('خطأ!', 'حدثت مشكلة .', 'error');
                }
            },
            error: function() {
                Swal.fire('خطأ!', 'فشل  .', 'error');
            }
        });
    }

    </script>

</body>
</html>

<?php
$conn->close();
?>


