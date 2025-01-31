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


// Handle deletion of student
if (isset($_POST['delete_student_id'])) {
    $student_id = $_POST['delete_student_id'];

    // Delete the student from the table
    $delete_query = "DELETE FROM students WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'تم حذف الطالب بنجاح']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل في حذف الطالب']);
    }
    $stmt->close();
    exit();
}

// Fetch all students and their foreign key data
$students_query = "
    SELECT 
        students.id,
        students.student_name,
        students.gender,
        students.phone,
        classes.class_name,
        branches.branch_name,
        agents.agent_name,
        agents.phone AS agent_phone,
        levels.level_name,
        COALESCE(SUM(payments.remaining_amount), 0) AS total_remaining_amount
    FROM students
    LEFT JOIN classes ON students.class_id = classes.class_id
    LEFT JOIN branches ON students.branch_id = branches.branch_id
    LEFT JOIN agents ON students.agent_id = agents.agent_id
    LEFT JOIN levels ON students.level_id = levels.id
    LEFT JOIN payments ON students.id = payments.student_id
    GROUP BY students.id, students.student_name, students.gender, students.phone, classes.class_name, branches.branch_name, agents.agent_name, agents.phone, levels.level_name
";

$result = $conn->query($students_query);

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
    <title>عرض الطلاب</title>

    <!-- Google Font (Custom) -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

    <!-- Include SweetAlert CSS and JS -->
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
            background-color: #f9f9f9;
            color: #333;
            direction: rtl;
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
            font-weight: bold;
            margin-bottom: 30px;
            color: #007bff;
        }
        .search-box {
            margin-bottom: 20px;
            text-align: right;
        }
        .search-box input {
            border: 2px solid #007bff;
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
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .student-table td {
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        .delete-btn {
            color: white;
            background-color: #dc3545;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .icon {
            margin-left: 5px;
        }
        .header-row {
            display: flex;
            align-items: center; /* Aligns items vertically in the center */
            justify-content: space-between; /* Distributes space between items */
            margin-bottom: 1rem; /* Optional spacing below the row */
        }
        .home-btn .btn {
            font-size: 1.2rem;
            padding: 10px 24px;
            background-color: #1a73e8;
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
        <h2>عرض جميع الطلاب</h2>
        <div class="home-btn">
            <a href="home.php" class="btn">
                <i class="bi bi-house-door-fill"></i> الرئيسية
            </a>
        </div>
    </div>


    <!-- Search Box -->
    <div class="search-box">
        <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طالب...">
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
                    <button class="btn delete-btn" onclick="confirmDelete(<?php echo $student['id']; ?>)">
                        <i style="font-size: 18px; margin: 0px;  paddin: 0px" class="bi bi-person-x-fill"></i>حذف
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

    // Confirm delete action
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

   // Delete student via AJAX
function deleteStudent(student_id) {
    $.ajax({
        url: 'display_studentss.php',
        type: 'POST',
        data: { delete_student_id: student_id },
        success: function(response) {
            let result = JSON.parse(response);
            if (result.status === 'success') {
                Swal.fire({
                    title: 'تم الحذف!',
                    text: 'تم حذف الطالب بنجاح.',
                    icon: 'success',
                    confirmButtonText: 'حسنا'
                }).then(() => {
                    location.reload(); // Refresh the page after showing the success message
                });
            } else {
                Swal.fire('خطأ!', 'حدثت مشكلة أثناء حذف الطالب.', 'error');
            }
        },
        error: function() {
            Swal.fire('خطأ!', 'فشل في حذف الطالب.', 'error');
        }
    });
}

</script>

</body>
</html>
