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


    $selectedClass = isset($_GET['class']) ? $_GET['class'] : '';
    // $fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $semester = isset($_GET['semester']) ? $_GET['semester'] : '';




    $query = "SELECT class_id, class_name FROM classes WHERE branch_id = 22";
    $classResult = $conn->query($query);

    if ($classResult === false) {
        die("Error fetching levels: " . $conn->error);
    }


    // Fetch student data from the database, including agent phone number
    $sql = "SELECT s.id, e.date, s.student_name, e.semester, e.num_count, e.num_hivd, e.tjwid, e.houdour,
               e.moyen, e.NB, COALESCE(ag.whatsapp_phone, s.phone) AS whatsapp_phone
            FROM students s
            JOIN user_branch ub ON s.branch_id = ub.branch_id AND ub.user_id = ?
            LEFT JOIN exam e ON e.student_id = s.id
            LEFT JOIN agents ag ON ag.agent_id = s.agent_id
            WHERE s.branch_id = 22 AND s.is_active = 0 ";

    // Ajouter les conditions de filtrage
    $params = [$user_id];
    $types = "i";

    if (!empty($selectedClass)) {
        $sql .= " AND s.class_id = ?";
        $params[] = $selectedClass;
        $types .= "i";
    }

    if (!empty($semester)) {
        $sql .= " AND e.semester = ?";
        $params[] = $semester;
        $types .= "s";
    }

    // if (!empty($toDate)) {
    //     $sql .= " AND e.date <= ?";
    //     $params[] = $toDate;
    //     $types .= "s";
    // }
    $sql .= " GROUP BY s.id";


    // Exécuter la requête préparée
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حصيلة الإمتحان</title>
    <link rel="shortcut icon" type="image/png" href="../../images/menar.png">
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/sweetalert2.css">
    <script src="../js/sweetalert2.min.js"></script>
    <link href="../css/bootstrap-icons.css" rel="stylesheet">
    <link href="../fonts/bootstrap-icons.css" rel="stylesheet">
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
            <a class="navbar-brand" href="#"> حصيلة الإمتحان - مقرأة المنارة والرباط</a>
        </div>
    </nav>

    
    <div class="container-full " style="direction: rtl;">
        <h2>حصيلة الإمتحان
            <!-- <span class="is_active"> الفصلي </span> -->
        </h2>
        <div class="search-filter-container text-center mb-3 row align-items-center">
            <div class="col-md-5 mb-2 mb-md-0">
                <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم الطالب...">
            </div>
            <form method="GET" class="col-md-3" >
                <div class="col-md-12 mb-2 mb-md-0">
                    <select class="form-select" name="semester" onchange="this.form.submit()">
                        <option value="">اختر فصل</option>
                        <option value="الفصل الأول" <?php if ($semester == "الفصل الأول") echo 'selected'; ?>>الفصل الأول</option>
                        <option value="الفصل الثاني" <?php if ($semester == "الفصل الثاني") echo 'selected'; ?>>الفصل الثاني</option>
                        <option value="الفصل الثالث" <?php if ($semester == "الفصل الثالث") echo 'selected'; ?>>الفصل الثالث</option>
                        
                    </select>
                </div>
            </form>
            <div class="col-md-3">
                <form method="GET" class="d-flex">
                    <select class="form-select" name="class" onchange="this.form.submit()">
                        <option value="">اختر القسم</option>
                        <?php
                        while ($cl = $classResult->fetch_assoc()) {
                            $selected = (isset($_GET['class']) && $_GET['class'] == $cl['class_id']) ? 'selected' : '';
                            echo '<option value="' . $cl['class_id'] . '" ' . $selected . '>' . htmlspecialchars($cl['class_name']) . '</option>';
                        }
                        ?>
                    </select>
                </form>
            </div>
        </div>
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>الرقم</th>
                        <th>الإسم الكامل</th>
                        <th>عدد الحزاب </th>
                        <th>الحفظ</th>
                        <th>التجويد</th>
                        <th>الحضور</th>
                        <th>المعدل العام</th>
                        <th style="min-width: 80px;"> الفصل</th>
                        <th>الملاحظة </th>
                        <th style="width: 10%;">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="suspendedStudentsTableBody">
                <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr data-student-id='{$row['id']}'>
                                    <td>{$row['id']}</td>
                                    <td data-field='student_name'>{$row['student_name']}</td>
                                    <td data-field='num_count'>{$row['num_count']}</td>
                                    <td data-field='num_hivd'>{$row['num_hivd']}</td>
                                    <td data-field='tjwid'>{$row['tjwid']}</td>
                                    <td data-field='houdour'>{$row['houdour']}</td>
                                    <td data-field='moyen'>{$row['moyen']}</td>
                                    <td data-field='semester'>{$row['semester']}</td>
                                    <td data-field='NB'>{$row['NB']}</td>
                                    <td style='display: none;'data-field='date'>{$row['date']}</td>
                                    <td>
                                        <div class='edit-btn-group'>";
                                    
                                echo "<a class='h5 btn btn-sm btn-primary edit-btn' onclick='toggleEditMode(this)'><i class='bi bi-pencil-square'></i> تعديل</a>
                                        <a class='btn btn-sm btn-success save-btn' style='display:none;' onclick='saveStudent(this)'>
                                                <i class='bi bi-save'></i> حفظ
                                            </a>
                                            <a class='btn btn-sm btn-secondary cancel-btn' style='display:none;' onclick='cancelEdit(this)'>
                                                <i class='bi bi-x'></i> إلغاء
                                            </a>
                                            </div>
                                        </td>
                                    </tr>";
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

<script>
    function toggleEditMode(button) {
        const row = button.closest('tr');
        row.classList.add('edit-mode');
        
        row.querySelector('.edit-btn').style.display = 'none';
        // row.querySelector('.btn-action').style.display = 'none';
        row.querySelector('.save-btn').style.display = 'inline-block';
        row.querySelector('.cancel-btn').style.display = 'inline-block';

        row.querySelectorAll('td[data-field]').forEach(td => {
            const field = td.dataset.field;
            const value = td.innerText;
            
            let input;
            if(field === 'semester') {
                input = `<select class="form-control">
                            <option value="الفصل الأول" ${value === 'الفصل الأول' ? 'selected' : ''}>الفصل الأول</option>
                            <option value="الفصل الثاني" ${value === 'الفصل الثاني' ? 'selected' : ''}>الفصل الثاني</option>
                            <option value="الفصل الثالث" ${value === 'الفصل الثالث' ? 'selected' : ''}>الفصل الثالث</option>
                        </select>`;
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
        // row.querySelector('.btn-action').style.display = 'inline-block';
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
            
            data[field] = input.value;
        });

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
                    url: 'add_or_up_exem.php',
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


    function sendWhatsAppMessage(phoneNumber, name, du, au, numAbAc, numAbNo) {
    if (!phoneNumber) {
        alert("لا يوجد رقم واتساب لهذا الطالب!");
        return;
    }

    const message = `🛑 حصيلة الغياب من ${du} إلى ${au}

        اسم الطالبة: ${name}

        عدد الغياب المبرر: ${numAbAc}
        عدد الغياب غير المبرر: ${numAbNo}

        ❌ ملاحظة: الغياب ممنوع عمومًا.
        ✅ كثرة الغياب المبرر تؤثر على نتيجة الامتحان.
        ✅ 3 غيابات غير مبررة تؤدي إلى تعليق الدراسة فورًا.`;

    const encodedMessage = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;

    window.open(whatsappUrl, '_blank');
}


</script>

<script src="../js/sweetalert2.js"></script>



</body>
</html>

<?php
$conn->close();
?>


