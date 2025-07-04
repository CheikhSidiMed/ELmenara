<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

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


$sql = "SELECT s.id, s.start, s.balance, s.suspension_reason, s.elmoutoune, s.phone, s.rewaya, s.days, s.tdate, s.student_name, s.part_count, s.gender, s.birth_date, s.birth_place, s.current_city,
           s.registration_date, s.regstration_date_count, b.branch_name AS branch_name, l.level_name AS level_name, c.class_name AS class_name,
           s.student_photo, a.phone AS agent_phone, s.payment_nature, s.fees, s.discount, s.remaining
    FROM students s
    LEFT JOIN branches b ON s.branch_id = b.branch_id
    JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ?
    LEFT JOIN levels l ON s.level_id = l.id
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN agents a ON s.agent_id = a.agent_id
    WHERE s.branch_id = '22' AND s.etat = 0 AND s.is_active = ?
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
    <link href="css/style1.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap">

</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container pb-2">
            <div class="how">
                <a class="nav-link" href="../home.php"><i class="bi bi-house-fill"></i>  الرئيسية</a>
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
                        <th>مكان الإقامة</th>
                        <th>الجنس</th>
                        <th>الرواية </th>
                        <th>البداية </th>
                        <th>المتون التجويدية</th>
                        <th>الأيام المناسبة</th>
                        <th>القسم</th>
                        <th>التوقيت المناسب </th>
                        <th>الرسوم</th>
                        <th>رقم الوكيل(ة)</th>
                        <?php
                            if ($selectedStatus == 1) {
                                echo "<th>المبلغ المتبقي</th>";
                                echo "<th>السبب</th>";
                            }
                        ?>
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
                            echo "<tr data-student-id='{$row['id']}'>
                                    <td>{$row['id']}</td>
                                    <td data-field='student_name'>{$row['student_name']}</td>
                                    <td data-field='phone'>{$row['phone']}</td>
                                    <td data-field='birth_date'>{$row['birth_date']}</td>
                                    <td data-field='regstration_date_count'>" . date("Y-m-d", strtotime($row['regstration_date_count'])) . "</td>
                                    <td data-field='birth_place'>{$row['birth_place']}</td>
                                    <td data-field='current_city'>{$row['current_city']}</td>
                                    <td data-field='gender'>{$row['gender']}</td>
                                    <td data-field='rewaya'>{$row['rewaya']}</td>
                                    <td data-field='start'>{$row['start']}</td>
                                    <td data-field='elmoutoune'>{$row['elmoutoune']}</td>
                                    <td data-field='days'>{$row['days']}</td>
                                    <td data-field='class_name'>{$row['class_name']}</td>
                                    <td data-field='tdate'>{$row['tdate']}</td>
                                    <td data-field='remaining'>{$row['remaining']}</td>
                                    <td data-field='agent_phone'>{$row['agent_phone']}</td>";
                                    if ($selectedStatus == 1) {
                                        echo "<td data-field='agent_phone'>{$row['balance']}</td>";
                                        echo "<td data-field='agent_phone'>{$row['suspension_reason']}</td>";
                                    }
                                    
                                echo "
                                    <td>
                                        <div class='edit-btn-group'>";
        
                                    if($selectedStatus == 0) {
                                        echo "<a class='h5 btn btn-sm btn-primary edit-btn' onclick='toggleEditMode(this)'><i class='bi bi-pencil-square'></i> تعديل</a>
                                        <a class='btn btn-sm btn-success save-btn' style='display:none;' onclick='saveStudent(this)'>
                                                <i class='bi bi-save'></i> حفظ
                                            </a>
                                            <a class='btn btn-sm btn-secondary cancel-btn' style='display:none;' onclick='cancelEdit(this)'>
                                                <i class='bi bi-x'></i> إلغاء
                                            </a>
                                        
                                        ";
                                    }

                                    $btnClass = ($selectedStatus == 0) ? 'danger' : 'primary';
                                    $btnText = ($selectedStatus == 0) ? 'تعليق' : 'تنشيط';

                                    echo "<a class='h5 btn btn-{$btnClass} btn-action'
                                            onclick='confirmSuspend(event)'
                                            data-student-id='{$row['id']}'
                                            data-is-active='{$selectedStatus}'>
                                            <i class='bi bi-ban-fill'></i> {$btnText}
                                        </a>
                                        <a class='h5 btn btn-action btn-ivd' href='javascript:void(0);'
                                                onclick='createIvada(event)'
                                                data-student-id='{$row['id']}'>
                                                إفادة
                                            </a>
                                        </div>

                                        </td>
                                        </tr>";
                                        // <button class='btn suspend-btn' onclick='suspendStudent({$row['id']})'>
                                        //     <i style='font-size: 18px; margin: 0px; paddin: 0px' class='bi bi-person-fill-slash'></i> تعليق
                                        // </button>
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

        
    function createIvada(event) {
        event.preventDefault();
        const btn = event.currentTarget;
        const studentId = btn.dataset.studentId;

        Swal.fire({
            title: 'إفادة تحديد المستوى عند خروج الطالب',
            html: `
                <input id="swal-status" class="swal2-input" placeholder="أدخل الحالة العامة للمحفوظات">
                <input id="swal-mustewa" class="swal2-input" placeholder="أدخل المستوى">
                <textarea id="swal-note" class="swal2-textarea" placeholder="أدخل الملحوظات"></textarea>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'إنشاء',
            cancelButtonText: 'إلغاء',
            preConfirm: () => {
                const status = document.getElementById('swal-status').value.trim();
                const note = document.getElementById('swal-note').value.trim();
                const mustewa = document.getElementById('swal-mustewa').value.trim();
                if (!status || !note || !mustewa) {
                    Swal.showValidationMessage('يجب ملء الحقول: الحالة العامة والملحوظات');
                    return false;
                }
                return { status, note, mustewa};
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const status = result.value.status;
                const note = result.value.note;
                const mustewa = result.value.mustewa;

                // Create a hidden form and submit it to a new tab
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../document-management/pdf_exit.php';
                form.target = '_blank';

                const inputs = [
                    { name: 'student_id', value: studentId },
                    { name: 'status', value: status },
                    { name: 'mustewa', value: mustewa },
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
</script>

<script>
    function toggleEditMode(button) {
        const row = button.closest('tr');
        row.classList.add('edit-mode');
        
        row.querySelector('.edit-btn').style.display = 'none';
        row.querySelector('.btn-action').style.display = 'none';
        row.querySelector('.save-btn').style.display = 'inline-block';
        row.querySelector('.cancel-btn').style.display = 'inline-block';

        row.querySelectorAll('td[data-field]').forEach(td => {
            const field = td.dataset.field;
            const value = td.innerText;
            
            let input;
            if(field === 'gender') {
                input = `<select class="form-control">
                            <option value="ذكر" ${value === 'ذكر' ? 'selected' : ''}>ذكر</option>
                            <option value="أنثى" ${value === 'أنثى' ? 'selected' : ''}>أنثى</option>
                        </select>`;
            } else if (field === 'days') {
                const daysList = ["الأحد", "الإثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"];
                const selectedDays = value ? value.split(',').map(day => day.trim()) : [];

                input = `<select class="form-control" name="days[]" multiple>`;
                daysList.forEach(day => {
                    const selected = selectedDays.includes(day) ? 'selected' : '';
                    input += `<option value="${day}" ${selected}>${day}</option>`;
                });
                input += `</select>`;
            } else if(field === 'birth_date' || field === 'regstration_date_count') {
                input = `<input type="date" class="form-control" value="${value}">`;
            } else {
                input = `<input type="text" class="form-control" value="${value}">`;
            }
            
            td.innerHTML = input;
        });
    }

    function cancelEdit(button) {
        const row = button.closest('tr');
        row.classList.remove('edit-mode');
        
        row.querySelector('.edit-btn').style.display = 'inline-block';
        row.querySelector('.btn-action').style.display = 'inline-block';
        row.querySelector('.save-btn').style.display = 'none';
        row.querySelector('.cancel-btn').style.display = 'none';
        location.reload();
    }

    function saveStudent(button) {
        const row = button.closest('tr');
        const studentId = row.dataset.studentId;
        const data = {};

        row.querySelectorAll('td[data-field]').forEach(td => {
            const field = td.dataset.field;
            const input = td.querySelector('input, select');
            if (input.multiple) {
                data[field] = Array.from(input.selectedOptions).map(option => option.value);
            } else {
                data[field] = input.value;
            }
        });
        console.log(data);


        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'هل تريد حفظ التعديلات؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، احفظ التغييرات',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'update_student.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        student_id: studentId,
                        ...data
                    },
                    success: function(response) {
                        console.log(response);
                        if(response.success) {
                            Swal.fire('تم الحفظ!', 'تم تحديث بيانات الطالب بنجاح', 'success');
                            row.querySelectorAll('td[data-field]').forEach(td => {
                                const field = td.dataset.field;
                                td.innerHTML = data[field];
                            });
                            cancelEdit(button);
                        } else {
                            Swal.fire('خطأ!', response.error || 'فشل في تحديث البيانات', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('خطأ!', 'حدث خطأ في الاتصال بالخادم', 'error');
                    }
                });
            }
        });
    }
</script>

<script src="../js/sweetalert2.min.js"></script>

<script>

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

    // AJAX to process suspension
    function processSuspendStudent(student_id, suspension_reason, targetStatus, msg1, msg2) {
        $.ajax({
            url: 'up_student_active.php',
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
                        location.reload(); // Refresh page after success
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


