<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php'; // Include the database connection

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}
$user_id = $_SESSION['userid'];


// Handle suspension of student
if (isset($_POST['suspend_student_id'])) {
    $student_id = $_POST['suspend_student_id'];
    $suspension_reason = $_POST['suspension_reason'];

    // First, fetch the student's data from the students table
    $student_query = "SELECT students.id, students.student_name, students.gender, students.phone,
                 students.regstration_date_count, students.part_count, students.payment_nature, students.fees, students.discount, students.remaining,
               classes.class_name, branches.branch_name, agents.agent_name, agents.phone AS agent_phone, levels.level_name
        FROM students
        LEFT JOIN classes ON students.class_id = classes.class_id
        LEFT JOIN branches ON students.branch_id = branches.branch_id
        LEFT JOIN agents ON students.agent_id = agents.agent_id
        LEFT JOIN levels ON students.level_id = levels.id
        WHERE students.id = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($student) {
        // Insert the student's data into the suspended_students table with the suspension reason
        $sql = "INSERT INTO suspended_students (student_id, student_name, gender, phone, class_name, branch_name,
                            agent_name, agent_phone, level_name, suspension_reason,
                            reg_date, part_count, payment_nature, fees, discount, remaining)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssssisiii",
        $student['id'], $student['student_name'], $student['gender'], $student['phone'],
        $student['class_name'], $student['branch_name'], $student['agent_name'], $student['agent_phone'],
        $student['level_name'], $suspension_reason,
        $student['regstration_date_count'], $student['part_count'],
        $student['payment_nature'], $student['fees'], $student['discount'], $student['remaining']);
    $stmt->execute();
    $stmt->close();

        // Delete the student from the students table
        $delete_query = "DELETE FROM students WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'success', 'message' => 'تم تعليق الطالب بنجاح']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الطالب']);
    }
    exit();
}

// Fetch all students and their foreign key data
$students_query = "SELECT students.id, students.student_name, students.gender, students.phone,
           classes.class_name, branches.branch_name, agents.agent_name, agents.phone AS agent_phone, levels.level_name, COALESCE(SUM(payments.remaining_amount), 0) AS total_remaining_amount
    FROM students
    LEFT JOIN classes ON students.class_id = classes.class_id
    LEFT JOIN branches ON students.branch_id = branches.branch_id
    JOIN user_branch ub ON branches.branch_id = ub.branch_id AND ub.user_id = ?
    LEFT JOIN agents ON students.agent_id = agents.agent_id
    LEFT JOIN payments ON students.id = payments.student_id
    LEFT JOIN levels ON students.level_id = levels.id
    GROUP BY students.id, students.student_name, students.gender, students.phone, classes.class_name, branches.branch_name, agents.agent_name, agents.phone, levels.level_name";

$stmt = $conn->prepare($students_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعليق تلميذ</title>

    <!-- Include Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/sweetalert2.css">
    <script src="js/sweetalert2.min.js"></script>
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="css/cairo.css">

    <!-- Include Bootstrap for styles -->
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background-color: #f9f9f9;
            color: #333;
            margin: 20px;

        }
        .container {
            margin-top: 50px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
            color: #dc3545;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            border: 2px solid #dc3545;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }
        .student-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .student-table th, .student-table td {
            padding: 15px;
            background-color: #f8f9fa;
            border: none;
        }
        .student-table th {
            background-color: #dc3545;
            color: white;
            font-weight: bold;
        }
        .student-table td {
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        .suspend-btn {
            color: white;
            background-color: #ff8800;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .suspend-btn:hover {
            background-color: #e87d00;
        }
        .icon {
            margin-left: 5px;
        }
        .home-btn .btn {
            font-size: 1.2rem;
            padding: 10px 24px;
            background-color: #ff8800;
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

    </style>
</head>
<body>

<div class="container-full">
    <div class="header-row">
        <h2>تعليق تلميذ</h2>
        <div class="home-btn">
            <a href="home.php" class="btn">
                <i class="bi bi-house-door-fill"></i> الرئيسية
            </a>
        </div>
    </div>
    <!-- Search Box -->
    <div class="search-box">
        <input type="text" id="searchInput" class="form-control" placeholder="البحث عن الطالب...">
    </div>

    <!-- Display Students Table -->
    <table class="student-table table table-striped">
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
                <th>المبلغ المتبقي</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody id="studentsTableBody">
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo $student['id']; ?></td>
                <td><?php echo $student['student_name']; ?></td>
                <td><?php echo $student['gender']; ?></td>
                <td><?php echo $student['phone']; ?></td>
                <td><?php echo $student['class_name']; ?></td>
                <td><?php echo $student['branch_name']; ?></td>
                <td><?php echo $student['agent_name']; ?></td>
                <td><?php echo $student['agent_phone']; ?></td>
                <td><?php echo $student['level_name']; ?></td>
                <td><?php echo $student['total_remaining_amount']; ?></td>

                <td>
                    <button class="btn suspend-btn" onclick="suspendStudent(<?php echo $student['id']; ?>)">
                        <i style="font-size: 18px; margin: 0px; paddin: 0px" class="bi bi-person-fill-slash"></i> تعليق
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Include jQuery (required for AJAX) -->
<script src="js/jquery-3.5.1.min.js"></script>

<script>
    // Filter students by name
    $('#searchInput').on('input', function() {
        let value = $(this).val().toLowerCase();
        $('#studentsTableBody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Function to suspend student
    function suspendStudent(student_id) {
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
                processSuspendStudent(student_id, suspension_reason);
            }
        });
    }

    // AJAX to process suspension
    function processSuspendStudent(student_id, suspension_reason) {
        $.ajax({
            url: 'display_studentsss.php',
            type: 'POST',
            data: {
                suspend_student_id: student_id,
                suspension_reason: suspension_reason
            },
            success: function(response) {
                let result = JSON.parse(response);
                if (result.status === 'success') {
                    Swal.fire('تم التعليق!', 'تم تعليق الطالب بنجاح.', 'success').then(() => {
                        location.reload(); // Refresh page after success
                    });
                } else {
                    Swal.fire('خطأ!', 'حدثت مشكلة أثناء تعليق الطالب.', 'error');
                }
            },
            error: function() {
                Swal.fire('خطأ!', 'فشل في تعليق الطالب.', 'error');
            }
        });
    }
</script>

</body>
</html>
