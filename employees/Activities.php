<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status_filter = $_POST['status'];

    $sql = "SELECT a.id, 
            a.activity_name, 
            a.start_date, a.price, 
            a.status, COUNT(sa.student_id) AS student_count, 
            -- COUNT(sa.student_id) * a.price AS total_revenue,
            SUM(ap.paid_amount) AS total_revenue
            FROM activities a
            LEFT JOIN student_activities sa ON a.id = sa.activity_id OR a.id = sa.student_id_etrang            
            LEFT JOIN activities_payments ap ON ap.student_activity_id = sa.id OR  ap.student_ac_id_etrang = sa.id
            ";

    if ($status_filter == 'ongoing') {
        $sql .= " WHERE a.status = 'Ongoing'";
    } elseif ($status_filter == 'ended') {
        $sql .= " WHERE a.status = 'Ended'";
    }

    $sql .= " GROUP BY a.id";

    $result = $conn->query($sql);

    $activities = [];

    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            'id' => $row['id'], // Add activity ID to be used for the redirect
            'activity_name' => $row['activity_name'],
            'start_date' => date('d-m-Y', strtotime($row['start_date'])),
            'student_count' => $row['student_count'],
            'total_revenue' => $row['total_revenue'], // Number without formatting commas
            'status' => ($row['status'] === 'Ended') ? 'منتهي' : 'جاري'
        ];
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($activities);

    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأنشطة</title>
    <link rel="stylesheet" href="css/amiri.css">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f1f5f4;
            color: #333;
        }

        .main-container {
            margin: 40px auto;
            padding: 30px;
            border-radius: 15px;
            background-color: #ffffff;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            max-width: 1100px;
            transition: all 0.3s ease-in-out;
        }

        .main-container:hover {
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .header-title {
            font-family: 'Amiri', serif;
            font-size: 2rem;
            font-weight: bold;
            color: #1BA078;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-control,
        .form-select {
            border-radius: 20px;
            border: 2px solid #1BA078;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #14865b;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            outline: none;
        }

        .table-container .table {
            margin-top: 20px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }

        .table-container .table thead th {
            background-color: #1BA078;
            color: white;
            text-align: center;
            vertical-align: middle;
            font-size: 18px;
            padding: 15px;
            border-bottom: none;
        }

        .table-container .table tbody td {
            text-align: center;
            vertical-align: middle;
            font-size: 16px;
            padding: 15px;
            border-color: #ddd;
        }

        .table-container .table tbody tr:hover {
            background-color: #f0f5f5;
        }

        .btn-custom {
            border-radius: 25px;
            background-color: #1BA078;
            color: white;
            border: 1px solid #1BA078;
            padding: 10px 30px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease-in-out;
            display: inline-block;
        }

        .btn-custom:hover {
            background-color: #14865b;
            border-color: #14865b;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .view-icon {
            background-color: #1BA078;
            color: white;
            border-radius: 50%;
            padding: 8px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .view-icon:hover {
            background-color: #14865b;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
        }

        .view-icon:active {
            background-color: #106548;
        }
    </style>
</head>

<body>

    <div class="container main-container">
        <h2 class="header-title">الأنشطة</h2>
         <!-- Home Button -->
         <div class="row justify-content-center mb-3">
            <div class="col-auto">
                <a href="home.php" class="btn btn-primary" style="border-radius: 30px; background-color: #1BA078; border: 2px solid #1BA078;">
                    <i class="bi bi-house-fill"></i> الصفحة الرئيسية
                </a>
            </div>
        </div>

        <div class="row filter-container">
    <div class="col d-flex align-items-center">
        <input type="radio" name="activity_status" id="ongoing" onclick="filterActivities()">
        <label for="ongoing">جاري</label>
    </div>
    <div class="col d-flex align-items-center">
        <input type="radio" name="activity_status" id="ended" onclick="filterActivities()">
        <label for="ended">منتهي</label>
    </div>
</div>

        

        <div class="row table-container">
            <div class="col-12">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>معاينة</th>
                            <th>تاريخ فتح التسجيل</th>
                            <th>اسم النشاط</th>
                            <th>عدد المسجلين</th>
                            <th>الإيرادات</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody id="activitiesTableBody">
                        <!-- Activities will be loaded here dynamically -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="js/jquery.min.js"></script>
    <script>
        // Load all activities when the page loads
        $(document).ready(function() {
            filterActivities();
        });

        function filterActivities() {
            var ongoingChecked = $('#ongoing').is(':checked');
            var endedChecked = $('#ended').is(':checked');
            var status = 'all';

            if (ongoingChecked && !endedChecked) {
                status = 'ongoing';
            } else if (!ongoingChecked && endedChecked) {
                status = 'ended';
            }

            $.ajax({
                url: '', 
                type: 'POST',
                dataType: 'json',
                data: { status: status }, 
                success: function(response) {
                    var tableBody = $('#activitiesTableBody');
                    tableBody.empty(); 
                    if (response.length > 0) {
                        $.each(response, function(index, activity) {
                            var row = `<tr>
                                <td><span class="view-icon" onclick="redirectToPreview(${activity.id})">&#128065;</span></td>
                                <td>${activity.start_date}</td>
                                <td>${activity.activity_name}</td>
                                <td>${activity.student_count}</td>
                                <td>${activity.total_revenue} MRU</td>
                                <td>${activity.status}</td>
                            </tr>`;
                            tableBody.append(row);
                        });
                    } else {
                        var noData = `<tr><td colspan="6">لا توجد أنشطة مسجلة حالياً</td></tr>`;
                        tableBody.append(noData);
                    }
                },
                error: function() {
                    alert('حدث خطأ أثناء جلب البيانات.');
                }
            });
        }

        function redirectToPreview(activityId) {
            window.location.href = 'Activitie_preview.php?activity_id=' + activityId;
        }
    </script>

</body>

</html>
