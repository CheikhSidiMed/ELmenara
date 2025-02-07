<?php
include 'db_connection.php'; // Include the database connection
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}

if (isset($_POST['restore_student_id'])) {
    $student_id = $_POST['restore_student_id'];

    // First, fetch the suspended student's data
    $suspended_query = "SELECT suspended_students.student_id, suspended_students.payment_nature,suspended_students.part_count, suspended_students.reg_date, suspended_students.student_name, suspended_students.gender, suspended_students.phone, 
           suspended_students.class_name, suspended_students.branch_name, suspended_students.agent_name, suspended_students.agent_phone,
           suspended_students.level_name, suspended_students.discount, suspended_students.fees, suspended_students.remaining
    FROM suspended_students
    WHERE suspended_students.student_id = ?";
$stmt = $conn->prepare($suspended_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$suspended_student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($suspended_student) {
    // Insert the student's data back into the students table
    $insert_query = "INSERT INTO students (remaining, discount, fees, part_count, payment_nature, regstration_date_count, student_name, gender, phone, class_id, branch_id, agent_id, level_id)
        SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?,
               (SELECT class_id FROM classes WHERE class_name = ? LIMIT 1),
               (SELECT branch_id FROM branches WHERE branch_name = ? LIMIT 1),
               (SELECT agent_id FROM agents WHERE agent_name = ? LIMIT 1),
               (SELECT id FROM levels WHERE level_name = ? LIMIT 1)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sssssssssssss",
    $suspended_student['remaining'], $suspended_student['discount'], $suspended_student['fees'], $suspended_student['part_count'],$suspended_student['payment_nature'], $suspended_student['reg_date'], $suspended_student['student_name'], $suspended_student['gender'], $suspended_student['phone'], 
        $suspended_student['class_name'], $suspended_student['branch_name'], $suspended_student['agent_name'],
        $suspended_student['level_name']);
    $stmt->execute();
    $stmt->close();



        // Delete the student from the suspended_students table
        $delete_query = "DELETE FROM suspended_students WHERE student_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'success', 'message' => 'تم استعادة الطالب بنجاح']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الطالب']);
    }
    exit();
}

// Fetch all suspended students
$suspended_students_query = "SELECT students.id, suspended_students.student_id, suspended_students.student_name, suspended_students.gender, suspended_students.phone, 
           suspended_students.class_name, suspended_students.branch_name, suspended_students.agent_name, suspended_students.agent_phone, 
           suspended_students.level_name, suspended_students.suspension_reason, suspended_students.suspension_date,
           COALESCE(SUM(payments.remaining_amount), 0) AS total_remaining_amount

    FROM suspended_students
    LEFT JOIN students ON students.id = suspended_students.student_id
    LEFT JOIN payments ON students.id = payments.student_id
    GROUP BY suspended_students.student_id,
             suspended_students.student_name,
             suspended_students.gender,
             suspended_students.phone,
             suspended_students.class_name,
             suspended_students.branch_name,
             suspended_students.agent_name,
             suspended_students.agent_phone,
             suspended_students.level_name";
    
$result = $conn->query($suspended_students_query);

$suspended_students = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $suspended_students[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة تلميذ</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert and Bootstrap CSS -->
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background-color: #f3f4f6;
            color: #333;
        }
        .container {
            margin-top: 50px;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-weight: 700;
            color: #28a745;
        }
        .home-btn .btn {
            font-size: 1.2rem;
            padding: 10px 24px;
            background-color: #28a745;
            color: white;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .home-btn .btn:hover {
            background-color: #155bb5;
        }
        .header-row .home-btn .btn {
            padding: 0.5rem 1rem; /* Adjusts padding for button */
            border-radius: 5px; /* Optional border radius for rounded button */
        }

        .home-btn .btn i {
            margin-left: 8px;
        }
        .header-row {
            display: flex;
            align-items: center; /* Aligns items vertically in the center */
            justify-content: space-between; /* Distributes space between items */
            margin-bottom: 1rem; /* Optional spacing below the row */
        }

        .header-row h2 {
            margin: 0; /* Removes default margin from heading */
        }

        .header-row .home-btn .btn {
            padding: 0.5rem 1rem; /* Adjusts padding for button */
            border-radius: 5px; /* Optional border radius for rounded button */
        }

        .search-box input {
            border: 2px solid #28a745;
            padding: 10px;
            border-radius: 5px;
        }
        .student-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .student-table th, .student-table td {
            padding: 12px;
            border: none;
            background-color: #f8f9fa;
            vertical-align: middle;
        }
        .student-table th {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        .student-table td {
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .restore-btn {
            color: white;
            background-color: #28a745;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .restore-btn:hover {
            background-color: #0056b3;
        }
        .icon {
            margin-left: 5px;
        }
        .alert-message {
            display: flex;
            justify-content: center;
            font-size: 14px;
            color: #dc3545;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-row">
        <h2>استعادة تلميذ</h2>
        <div class="home-btn">
            <a href="home.php" class="btn">
                <i class="bi bi-house-fill"></i> الرئيسية
            </a>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-box mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم الطالب...">
    </div>

    <!-- Display Suspended Students Table -->
    <div class="table-responsive">
        <table class="student-table table">
            <thead>
                <tr>
                    <th>رقم الطالب</th>
                    <th>اسم الطالب</th>
                    <th>الجنس</th>
                    <th>الهاتف</th>
                    <th>الفصل</th>
                    <th>الفرع</th>
                    <th>المسؤول</th>
                    <th>هاتف المسؤول</th>
                    <th>المستوى</th>
                    <th>سبب التعليق</th>
                    <th>تاريخ التعليق</th>
                    <th>المبلغ المتبقي</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody id="suspendedStudentsTableBody">
                <?php foreach ($suspended_students as $student): ?>
                <tr>
                    <td><?php echo $student['student_id']; ?></td>
                    <td><?php echo $student['student_name']; ?></td>
                    <td><?php echo $student['gender']; ?></td>
                    <td><?php echo $student['phone']; ?></td>
                    <td><?php echo $student['class_name']; ?></td>
                    <td><?php echo $student['branch_name']; ?></td>
                    <td><?php echo $student['agent_name']; ?></td>
                    <td><?php echo $student['agent_phone']; ?></td>
                    <td><?php echo $student['level_name']; ?></td>
                    <td><?php echo $student['suspension_reason']; ?></td>
                    <td><?php echo $student['suspension_date']; ?></td>
                    <td><?php echo $student['total_remaining_amount']; ?></td>
                    <td>

                            <button class="restore-btn" onclick="restoreStudent(<?php echo $student['student_id']; ?>)">
                                <i class="bi bi-person-fill-check"></i> استعادة
                            </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Include SweetAlert, jQuery, and Bootstrap JS -->
<script src="js/jquery-3.5.1.min.js"></script>

<script>
    // Filter suspended students by name
    $('#searchInput').on('input', function() {
        const value = $(this).val().toLowerCase();
        $('#suspendedStudentsTableBody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Function to restore student
    function restoreStudent(student_id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "سيتم استعادة الطالب إلى قاعدة البيانات الأساسية",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، استعد!',
            cancelButtonText: 'لا، إلغاء!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                processRestoreStudent(student_id);
            }
        });
    }

    // AJAX to process student restoration
    function processRestoreStudent(student_id) {
        $.ajax({
            url: 'restore_student.php',
            type: 'POST',
            data: { restore_student_id: student_id },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.status === 'success') {
                    Swal.fire('تم الاستعادة!', 'تم استعادة الطالب بنجاح.', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('خطأ!', 'حدثت مشكلة أثناء استعادة الطالب.', 'error');
                }
            },
            error: function() {
                Swal.fire('خطأ!', 'فشل في استعادة الطالب.', 'error');
            }
        });
    }
</script>

</body>
</html>
