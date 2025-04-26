<?php
include 'db_connection.php'; // Include your database connection script


session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


$students = $conn->query("SELECT id, student_name, class_id, branch_id, level_id, phone FROM students WHERE etat=0 AND is_active=0");
$classes = $conn->query("SELECT class_id, class_name FROM classes");
$branches = $conn->query("SELECT branch_id, branch_name FROM branches");
$levels = $conn->query("SELECT id, level_name, price FROM levels");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $new_class_id = $_POST['class'];
    $new_branch_id = $_POST['branch'];
    $new_level_id = $_POST['level_id'];

    // Check if the student exists
    $check_student_query = $conn->query("SELECT * FROM students WHERE etat=0 AND is_active=0 AND id = '$student_id'");
    
    if ($check_student_query->num_rows > 0) {
        // Fetch the new level price
        $level_query = $conn->query("SELECT price FROM levels WHERE id = '$new_level_id'");
        $level = $level_query->fetch_assoc();
        $new_fees = $level['price'];
        
        $student = $check_student_query->fetch_assoc();

        $discount = $student['discount'];
        if($student['payment_nature'] === 'معفى'){
            $new_fees = 0;
            $discount = 0;
        }
        $new_remaining = $new_fees - $discount;

        // Update the student's details
        $update_query = "UPDATE students
                         SET class_id = '$new_class_id',
                             branch_id = '$new_branch_id',
                             level_id = '$new_level_id',
                             fees = '$new_fees',
                             remaining = '$new_remaining'
                         WHERE id = '$student_id'";
        if ($conn->query($update_query) === TRUE) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire('نجاح!', 'تم تحديث معلومات الطالب بنجاح!', 'success');
                });
            </script>";
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire('خطأ!', 'حدث خطأ أثناء تحديث معلومات الطالب.', 'error');
                });
            </script>";
        }
    } else {
        // Student not found
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('خطأ!', 'الطالب غير موجود.', 'error');
            });
        </script>";
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقل الطلاب</title>
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="css/jquery-1.13.2-ui.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/sweetalert2.css">
    <link rel="stylesheet" href="css/cairo.css">
    <script src="js/sweetalert2.min.js"></script>
 
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f7f7f7;
            padding: 20px;
            font-size: 20px;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-home {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="btn-home">
            <a href="home.php" class="btn btn-secondary">الصفحة الرئيسية</a>
        </div>
        <h2>نقل الطلاب بين الفصول والفروع والمستويات</h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="search_student" class="form-label">بحث عن طالب:</label>
                <input type="text" class="form-control" id="search_student" placeholder="ابحث بالاسم، الهاتف أو الرقم" required>
                <input type="hidden" name="student_id" id="student_id" required>
            </div>
            <div class="mb-3">
                <label for="branch" class="form-label">الفرع:</label>
                <select class="form-control" id="branch" name="branch" required>
                    <option value="">اختر الفرع</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="class" class="form-label">القسم:</label>
                <select class="form-control" id="class" name="class" required>
                    <option value="">اختر القسم</option>
                    <?php while ($classe = $classes->fetch_assoc()): ?>
                        <option value="<?= $classe['class_id'] ?>"><?= $classe['class_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="level_id" class="form-label">المستوى:</label>
                <select name="level_id" id="level_id" class="form-select" required>
                    <option value="">-- اختر المستوى --</option>
                    <?php while ($level = $levels->fetch_assoc()): ?>
                        <option value="<?= $level['id'] ?>"><?= $level['level_name'] ?> </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">تحديث معلومات الطالب</button>
        </form>
    </div>



    <script>
        $(document).ready(function () {
            // Autocomplete for student search
            $("#search_student").autocomplete({
                source: 'update_cls_level_autocomplete.php',
                minLength: 2,
                select: function (event, ui) {
                    $("#search_student").val(ui.item.label);
                    $("#student_id").val(ui.item.value);
                    return false;
                }
            });
        });

        function confirmUpdate() {
            return Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'هل تريد تحديث بيانات الطالب؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، تحديث',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                return result.isConfirmed;
            });
        }
    </script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        fetch('fetch_branches_classes.php')
            .then(response => response.json())
            .then(data => {
                const branches = data.branches;
                const classes = data.classes;

                const branchSelect = document.getElementById('branch');
                const classSelect = document.getElementById('class');

                branches.forEach(branch => {
                    const option = document.createElement('option');
                    option.value = branch.branch_id;
                    option.textContent = branch.branch_name;
                    branchSelect.appendChild(option);
                });

                branchSelect.addEventListener('change', function() {
                    const selectedBranchId = this.value;
                    classSelect.innerHTML = '<option value="">اختر القسم</option>';

                    classes.filter(classItem => classItem.branch_id == selectedBranchId)
                        .forEach(classItem => {
                            const option = document.createElement('option');
                            option.value = classItem.class_id;
                            option.textContent = classItem.class_name;
                            classSelect.appendChild(option);
                        });
                });

                classSelect.addEventListener('change', function() {
                    const selectedClassId = this.value;

                    if (selectedClassId) {
                        fetch(`fetch_branches_classes.php?class_id=${selectedClassId}`)
                            .then(response => response.json())
                            .then(data => {
                                studentCountDiv.textContent = `${data.student_count}`;
                                updateStudentCount(data.student_count);
                            })
                            .catch(error => console.error('Error fetching student count:', error));
                    } else {
                        studentCountDiv.textContent = '';
                    }
                });
            })
            .catch(error => console.error('Error fetching branch and class data:', error));
    });
</script>

    <script>

        $(document).ready(function () {
            // Autocomplete for students
            $("#search_student").autocomplete({
                source: 'update_cls_level_autocomplete.php',
                minLength: 2,
                select: function (event, ui) {
                    $("#search_student").val(ui.item.label);
                    $("#student_id").val(ui.item.value);

                    // Fetch current student data
                    $.ajax({
                        url: 'update_cls_level_fetch_student_details.php',
                        method: 'POST',
                        data: { student_id: ui.item.value },
                        dataType: 'json',
                        success: function (response) {
                            if (response.error) {
                                Swal.fire('خطأ', response.error, 'error');
                            } else {
                                $("#level_id").val(response.level_id); 
                                $("#branch").val(response.branch_id); 
                                $("#class").val(response.class_id); 
                                updateClassesAndBranches(response.level_id);
                            }
                        }
                    });

                    return false;
                }
            });

            // Update classes and branches on level change
            $("#level_id").change(function () {
                const level_id = $(this).val();
                updateClassesAndBranches(level_id);
            });

            function updateClassesAndBranches(level_id) {
                $.ajax({
                    url: 'update_cls_level_fetch_classes_branches.php',
                    method: 'POST',
                    data: { level_id: level_id },
                    dataType: 'json',
                    success: function (response) {
                        // Update classes
                        $("#class").empty().append('<option value="">-- اختر الفصل --</option>');
                        response.classes.forEach(function (classObj) {
                            $("#class").append(`<option value="${classObj.class_id}">${classObj.class_name}</option>`);
                        });
                        $("#class").empty().append('<option value="">-- اختر الفرع --</option>');
                        response.branches.forEach(function (branchObj) {
                            $("#class").append(`<option value="${branchObj.class_id}">${branchObj.class_name}</option>`);
                        });
                        // Update branches
                        $("#branch").empty().append('<option value="">-- اختر الفرع --</option>');
                        response.branches.forEach(function (branchObj) {
                            $("#branch").append(`<option value="${branchObj.branch_id}">${branchObj.branch_name}</option>`);
                        });
                    }
                });
            }
        });

        // function confirmUpdate() {
        //     return Swal.fire({
        //         title: 'هل أنت متأكد؟',
        //         text: 'هل تريد تحديث بيانات الطالب؟',
        //         icon: 'warning',
        //         showCancelButton: true,
        //         confirmButtonText: 'نعم، تحديث',
        //         cancelButtonText: 'إلغاء'
        //     }).then((result) => {
        //         return result.isConfirmed;
        //     });
        // }
    </script>

</body>
</html>
