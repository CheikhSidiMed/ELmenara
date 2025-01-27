<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

// Fetch student data from the database, including agent phone number
$sql = "SELECT s.id, s.student_name, s.part_count, s.gender, s.birth_date, s.birth_place,
           s.registration_date, s.regstration_date_count, b.branch_name AS branch_name, l.level_name AS level_name, c.class_name AS class_name, 
           s.student_photo, a.phone AS agent_phone, s.payment_nature, s.fees, s.discount, s.remaining
    FROM students s
    LEFT JOIN branches b ON s.branch_id = b.branch_id
    LEFT JOIN levels l ON s.level_id = l.id
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN agents a ON s.agent_id = a.agent_id
";

$result = $conn->query($sql);


?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيانات الطلاب</title>
    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/sweetalert2.css">
    <script src="js/sweetalert2.min.js"></script>
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
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
            color: #4e73df;
        }
        .search-box input {
            border: 2px solid #4e73df;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }
        .container {
            margin-top: 10px;
        }
        .navbar {
            margin-bottom: 20px;
            background-color: #4e73df;
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
            background-color: #4e73df;
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
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        .btn-back {
            margin-bottom: 20px;
        }
        .home-btn .btn {
            font-size: 1.2rem;
            padding: 10px 24px;
            background-color: #1a73e8;
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
        .navbar-brand{
            font-size: 1.8rem;
            cursor: default;
            border: none;
        }

        .home-btn .btn:hover {
            background-color: #155bb5;
        }
        .header-row .home-btn .btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .home-btn .btn i {
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="">
                <a class="nav-link" href="home.php"><i class="bi bi-house-up-fill"></i> الصفحة الرئيسية</a>
            </div>
            <a class="navbar-brand" href="#">منصة البيانات الطلابية</a>
        </div>
    </nav>
    
    <div class="container-full " style="direction: rtl;">
        <h2>بيانات الطلاب</h2>
        <div class="search-box mb-4">
            <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم الطالب...">
        </div>
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>رقم الطالب(ة)</th>
                        <th>الاسم</th>
                        <th>عدد الأحزاب</th>
                        <th>الجنس</th>
                        <th>تاريخ الميلاد</th>
                        <th>مكان الميلاد</th>
                        <th>تاريخ التسجيل</th>
                        <th>تاريخ التسدد </th>
                        <th>الفرع</th>
                        <th>القسم</th>
                        <th>المستوى</th>
                        <th>الصورة</th>
                        <th>رقم هاتف الوكيل(ة)</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody id="suspendedStudentsTableBody">
                <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $photoSrc = $row['student_photo'] !== '' ?
                                        $row['student_photo'] :
                                        'uploads/avatar.png';
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['student_name']}</td>
                                    <td>{$row['part_count']}</td>
                                    <td>{$row['gender']}</td>
                                    <td>{$row['birth_date']}</td>
                                    <td>{$row['birth_place']}</td>
                                    <td>{$row['regstration_date_count']}</td>
                                    <td>{$row['registration_date']}</td>
                                    <td>{$row['branch_name']}</td>
                                    <td>{$row['class_name']}</td>
                                    <td>{$row['level_name']}</td>
                                    <td><img src='{$photoSrc}' alt='student_photo' width='50' height='50' style='border-radius: 50%'></td>
                                    <td>{$row['agent_phone']}</td>
                                    <td><a href='modify_student.php?id={$row['id']}' class='btn btn-primary'><i class='bi bi-pencil-square'></i> تعديل</a></td>
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

<script src="JS/jquery-3.5.1.min.js"></script>
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

</body>
</html>

<?php
$conn->close();
?>


