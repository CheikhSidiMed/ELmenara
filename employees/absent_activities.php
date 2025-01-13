<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}
$currentMonth = date('m');
                   


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['activity'] = $_POST['activity'];
}

// Retrieve stored values from session
$selectedActivity= isset($_SESSION['activity']) ? $_SESSION['activity'] : '';


 
// Fetch branches from the database
$sqlAct = "SELECT id, activity_name, session, start_date, end_date FROM activities";
$resultAct_s = $conn->query($sqlAct);

$activitys = [];
if ($resultAct_s->num_rows > 0) {
    while ($row = $resultAct_s->fetch_assoc()) {
        $activitys[] = $row;
    }
}

// Initialize variables

// Initialize variables
$activity_id = $_POST['activity'] ?? '';
$students = [];
$activity_name = '';
$activity_sart = '';
$activity_to = '';
$session = '';
$student_by = [];
$rows = [];

// Fetch classes based on selected branch
if (!empty($activity_id)) {
    $sql1 = "SELECT
                s.id,
                s.student_name,
                s.phone AS p_st,
                a.phone AS p_ag,
                sa.student_id,
                ab.is_absent,
                a.phone_2,
                ab.session_number AS session_time,
                ab.absence_date AS created_at
            FROM
                student_activities AS sa
            LEFT JOIN
                students AS s ON s.id = sa.student_id
            LEFT JOIN
                agents AS a ON s.agent_id = a.agent_id
            LEFT JOIN
                absences_activity AS ab ON ab.student_id = s.id AND ab.activity_id = ?
            WHERE
                s.id IS NOT NULL AND sa.activity_id = ?
            UNION ALL
            SELECT
                s.id,
                s.name AS student_name,
                '' AS p_st,
                s.phone AS p_ag,
                sa.student_id,
                ab.is_absent,
                s.wh AS phone_2,
                ab.session_number AS session_time,
                ab.absence_date AS created_at
            FROM
                student_activities AS sa
            LEFT JOIN
                students_etrang AS s ON s.id = sa.student_id_etrang
            LEFT JOIN
                absences_activity AS ab ON ab.student_ert_id = s.id AND ab.activity_id = ?
            WHERE
                s.id IS NOT NULL AND sa.activity_id = ?
            ORDER BY
                id;";
    
    $stmt = $conn->prepare($sql1);
    $stmt->bind_param('iiii', $activity_id, $activity_id, $activity_id, $activity_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $student_id = $row['id'];
        
        // Initialize student record if not already set
        if (!isset($students[$student_id])) {
            $students[$student_id] = [
                'name' => $row['student_name'],
                'phone1' => $row['p_st'],
                'phone2' => $row['p_ag'],
                'phone3' => $row['phone_2'],
                'status' => $row['student_id'] ? 'الحظرت ' : 'خارج',
                'absences' => [],
                'total_absences' => 0
            ];
        }

        // Handle absence data
        if (!empty($row['created_at'])) {
            $absence_date = date('Y-m-d', strtotime($row['created_at']));
            $session_time = $row['session_time'];

            // Record session absence status
            $students[$student_id]['absences'][$session_time] = !empty($row['is_absent']);

            // Increment total absences count
            $students[$student_id]['total_absences']++;
        }
    }

    $stmt->close();

    // Debug: Output the students array
    // echo '<pre>';
    // print_r($students);
    // echo '</pre>';
}





$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>   استمارة الغياب</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
    <link rel="stylesheet" href="css/tajawal.css">
    <style>
            .absent {
                background-color: #ffcccc;
                color: red;

            }
            h2 {
                text-align: center;
                font-weight: bold;
                margin-top: 5px;
                margin-bottom: 3px;
            }
            .form-container {
                width: 100%;
                margin: 0 auto;
                padding: 20px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 10px;
            }

            body {
                font-family: 'Tajawal', sans-serif;
                direction: rtl;
                text-align: right;
                font-size: 18px;
                margin-left: 15px; /* Reduced margin */
                margin-right: 15px; /* Reduced margin */
                margin-bottom: 30px !important;

            }
            .header-title {
                text-align: center;
            }
            .sheet-header {
                text-align: center;
            }
            .sheet-header p {
                display: inline-block;
                margin: 0 1px; /* Adjusts space between the items */
                font-size: 14px; /* Adjust font size as needed */
            }
            .sheet-header img {
                width: 100%;
                max-width: 1400px; /* Adjusted width */
                height: 60px !important;
            }
            table {
                width: 100%;
            }
            th, td {
                border: 1px solid black;
                padding: 1px;
                font-size: 19px;
                text-align: center;
            }
            th {
                font-size: 20px;
                background-color: #f8f9fa;
            }
            .signature-section {
                display: flex;
                justify-content: space-between;
                margin-top: 10px; /* Reduced margin */
            }
            .signature {
                width: 22%; /* Adjusted width */
                text-align: center;
            }
            .print-button {
                margin-bottom: 10px; /* Reduced margin */
                text-align: center;
            }
            .total-cell {
            text-align: center;
            font-weight: bold; /* Optional: to make it bold */
            }
            .button-group {
                    display: flex;
                justify-content: space-between;
                margin-top: 10px;
            }
            .btn i {
            margin-right: 8px; /* Adjust the value as needed */
            }
            .print-date {
            display: none; /* Hide by default */
        }
        /* Print-specific adjustments */
        @media print {
            .button-group, .form-container, .container-fluid {
                display: none;
            }
            .print-date {
                display: block; 
                text-align: right;
                font-weight: bold;
            }

            body {
                font-size: 10px !important; 
                align-items: center;
                margin-bottom: 0px !important;

            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 12px !important;
                text-align: start;
                table-layout: fixed;
            }
            h2 {
                font-size: 14px;

            }

            .receipt-header img {
                width: 100%;
                height: auto;
                max-width: 100%;
                display: block;
            }
            .print-date {
                display: none; /* Hide by default */
            }
            @page {
                size: A4 landscape;
            }

            th, td {
                font-size: 11px !important; /* Further reduce font size within table */
                padding: 0px;
                border: 1px solid black;
                white-space: wrap;
                overflow: hidden;
            }
            th {
                text-align: center;
                font-size: 13px;
                background-color: #f8f9fa;
            }
            /* Bold styling for header sections */
            .sheet-header p,
            .signature-section p {
                font-weight: bold;
                font-size: 12px;
            }
            .sheet-header {
                margin-top: -1px;
            }
            .print-date {
                display: block; /* Show only during print */
                text-align: right;
                font-weight: bold;
                margin-top: 10px;
            }

            /* Handling for large tables, allowing page breaks within the rows */
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
    <script>
        function printPage() {
            window.print();
        }
    </script>
</head>
<body>

<div class="form-container container mt-4">
    <h3 class="text-center mb-4">استمارة الغياب</h3>
    <form action="" method="POST">
        <div class="d-flex justify-content-evenly">
            <!-- Champ activity -->
            <div class="form-group col-md-6">
                <label for="activity">اختر الفرع:</label>
                <select name="activity" id="activity" class="form-control" onchange="this.form.submit()" required>
                    <option value="">اختر الفرع</option>
                    <?php foreach ($activitys as $activity): ?>
                        <option value="<?php echo $activity['id']; ?>" <?php if ($selectedActivity == $activity['id']){
                            $activity_name = $activity['activity_name'];
                            $activity_sart = $activity['start_date'];
                            $activity_end = $activity['end_date'];

                            $session = $activity['session'];
                            echo 'selected'; } ?>>
                            <?php echo $activity['activity_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <!-- Champ Year -->
            <div class="form-group col-md-2">
            <label class="form-select-title" for="financial-year" style="margin-left: 15px;">العام الدراسي:</label>
                <select id="financial-year" class="form-control">
                <option><?php echo $last_year; ?></option>
                </select>
                
            </div>

        </div>
    </form>
</div>


    <div class="button-group">
        <button type="button" class="btn btn-primary d-flex align-items-center" onclick="printPage()">
        طباعة <i class="fas fa-print" style="margin-right: 8px;"></i>
        </button>
        <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
        الصفحة الرئيسية <i class="fas fa-home mr-2"></i>
        </button>

    </div>

    <div class="sheet-header receipt-header">
        <img src="../images/header.png" alt="Header Image">
        <h2><span style="color: #3C3C3C; "><?php echo $activity_name; ?></span> متابعة الحضور للدورة </h2>
        <div class="d-flex justify-content-center align-items-center">
        <p style="font-size: 17px;"> <strong>من: </strong> <?php echo $activity_sart??''; ?></p>
        <p style="font-size: 17px;"> <strong>الى: </strong>  <?php echo $activity_end??''; ?></p>


        </div>
        <p> العام الدراسي: <?php echo $last_year; ?></p>
        <p class="print-date">التاريخ : <?php echo date('d/m/Y'); ?></p>

    </div>

    <table>

        <thead>
            <tr>
                <th colspan="1" > المقعد</th>
                <th colspan="12">الاسم الكامل</th>
                <th colspan="1" > الحساب </th>
                <th colspan="5">1هاتف</th>
                <th colspan="5">هاتف2 </th>
                <?php
                    for ($day = 1; $day <= $session; $day++): ?>
                        <th style="min-width: 30px;"><?= $day ?></th>
                    <?php endfor; ?>

                <th colspan="1" style="width: 6%;">عدد الغياب</th>
            </tr>
        </thead>
        <tbody>
            <?php $index = 1;
                foreach ($students as $student_id => $student): ?>
                    <tr>
                        <td colspan="1" style="width: 3%;"><?= htmlspecialchars($index) ?></td>
                        <td colspan="12" style="text-align: right;"><?= htmlspecialchars($student['name']) ?></td>
                        <td colspan="1" style="width: 4%; font-weigth: bold; color: <?= $student['status'] === 'خارج' ? 'red' : 'inherit'; ?>;">
                            <strong> <?= htmlspecialchars($student['status']) ?> </strong>
                        </td>
                        <td colspan="5" style="width: 6%;"><?= htmlspecialchars($student['phone2'] ?? $student['phone1']) ?></td>
                        <td colspan="5" style="width: 6%;"><?= htmlspecialchars($student['phone3'] ?? $student['phone1']) ?></td>

                        <?php for ($day = 1; $day <= $session; $day++): ?>
                            <td
                                contenteditable="true"
                                class="<?= isset($student['absences'][$day]) && $student['absences'][$day] ? 'absent' : 'present' ?>">
                                <?= isset($student['absences'][$day]) && $student['absences'][$day] ? 'X' : '' ?>
                            </td>
                        <?php endfor; ?>

                        <td contenteditable="true" colspan="1"><?= htmlspecialchars($student['total_absences'] ?? 0) ?></td>
                    </tr>
                <?php $index++; endforeach; ?>

            <?php for ($i = 0; $i < 3; $i++): ?>
            <tr>
                <td colspan="1"></td>
                    <td colspan="12"></td>
                    <td colspan="1" contenteditable="true"></td>

                    <td colspan="5" contenteditable="true"></td>

                    <td colspan="5" contenteditable="true"></td>
                    <?php for ($j = 0; $j < $session; $j++): ?>
                    <td contenteditable="true"></td>
                <?php endfor; ?>
                <td colspan ="1" contenteditable="true"></td>
            </tr>
            <?php endfor; ?>
        </tbody>

    </table>
    <div class="signature-section">
        <div class="signature">
            <p>الأستاذ</p>
            <p style="margin-top: -15px;">__________________</p>
        </div>
        <div class="signature">
            <p>تاريخ التسليم</p>
            <p style="margin-top: -15px;">__________________</p>
        </div>
        <div class="signature">
            <p>توقيع الأستاذ</p>
            <p style="margin-top: -15px;">__________________</p>
        </div>
        <div class="signature">
            <p>توقيع الإدارة</p>
            <p style="margin-top: -15px;">__________________</p>
        </div>
    </div>

</body>
</html>
