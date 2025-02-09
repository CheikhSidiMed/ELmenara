<?php
include 'db_connection.php'; // Include your database connection script

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}
$user_id = $_SESSION['userid'];
$role_id = $_SESSION['role_id'];

// Fetch branches and their associated classes
$sql = "SELECT b.branch_id, b.branch_name, GROUP_CONCAT(c.class_id, ':', c.class_name ORDER BY c.class_name SEPARATOR ', ') AS classes
        FROM branches b
        LEFT JOIN classes c ON b.branch_id = c.branch_id
        JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = '$role_id'
        GROUP BY b.branch_id, b.branch_name
        ORDER BY b.branch_id
    ";



$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لائحة الفروع و الأقسام</title>
    <link rel="stylesheet" href="css/bootstrap-4.0.0.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
         body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            direction: rtl;
            text-align: right;
        }
        h2 {
            text-align: center;
            margin-top: 20px;
            font: inherit;
            font-weight: bold;
            font-size: 42px;
            margin-bottom: 20px;
            color: #708090;
        }
        .nav-tabs .nav-link {
            color: #708090;
            font-weight: bold;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .selected-item {
            background-color: #708090 !important; /* Change this to your desired color */
            color: white !important; /* Change the text color if needed */
        }
        .nav-tabs .nav-link:hover, .nav-tabs .nav-link.active {
            background-color: #f8f9fa;
        }
        .tab-content {
            padding: 20px;
            background-color: #ffffff;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .list-group-item {
            cursor: pointer;
            transition: 0.2s ease-in-out;
        }
        .list-group-item:hover {
            background-color: #708090;
            color: white;
        }
        #students-container {
            margin-top: 20px;
            display: none;
        }
        #students-container h3 {
            color: #333;
            border-bottom: 2px solid #708090;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .table-bordered {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
            padding: 12px;
        }
        .table tbody tr:hover {
            background-color: #e9ecef;
        }
        .search-box input {
            border: 2px solid #708090;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }
        .tbl {
            overflow-x: auto;
            width: 100%;
        }
        table {
            min-width: 900px;
            border-collapse: collapse;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>لائحة الفروع و الأقسام</h2>
        <ul class="nav nav-tabs" id="branchTab" role="tablist">
            <?php
                $first = true;
                while ($branch = $result->fetch_assoc()) {
                    $active_class = $first ? ' active' : '';
                    echo "<li class='nav-item'>
                            <a class='nav-link$active_class' id='tab-{$branch['branch_id']}' data-toggle='tab' href='#' data-branch-id='{$branch['branch_id']}'>{$branch['branch_name']}</a>
                        </li>";
                    $first = false;
                }
            ?>
        </ul>

        <!-- Classes Container -->
        <div id="class-container" style="margin-top: 20px;">
            <ul class="list-group" id="classes-list">
                <p class="text-center text-muted">حدد فرعًا لعرض الفصول الدراسية.</p>
            </ul>
        </div>

        <!-- Students Container -->
        <div id="students-container" style="margin-top: 20px; display: none;">
            <h3>الطلاب</h3>
            <div class="search-box mb-4">
                <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم الطالب...">
            </div>
            <div class="table-responsive tbl">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th> الرقم</th>
                            <th>اسم الطالب</th>
                            <th>تاريخ الملاد</th>
                            <th>تاريخ التسجيل</th>
                            <th>تاريخ آخر استعادة</th>
                            <th>  هاتف الطالب </th>
                            <th>  هاتف الوكيل </th>
                        </tr>
                    </thead>
                    <tbody id="students-list">
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="JS/jquery-3.5.1.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.nav-link').on('click', function (e) {
                e.preventDefault();
                var branchId = $(this).data('branch-id');
                var branchId = $(this).data('branch-id');

                $('.nav-link').removeClass('selected-item');
                $(this).addClass('selected-item');
                $.ajax({
                    url: 'fetch_classes_.php',
                    method: 'POST',
                    data: { branch_id: branchId },
                    success: function (response) {
                        $('#classes-list').html(response);
                        $('#students-container').hide();
                    }
                });
            });

            // Load students when a class is clicked
            $('#classes-list').on('click', '.list-group-item', function () {
                var classId = $(this).data('class-id');

                $.ajax({
                    url: 'fetch_students.php',
                    method: 'POST',
                    data: { class_id: classId },
                    success: function (response) {
                        $('#students-list').html(response);
                        $('#students-container').show();
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#searchInput').on('input', function() {
                const value = $(this).val().toLowerCase();
                $('#students-list tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().includes(value));
                });
            });
        });
    </script>
</body>
</html>
