<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

// Fetch absences and student data
$sql = "
    SELECT 
        s.id AS student_id,
        s.student_name,
        s.phone AS phone1,
        s.phone AS phone2,
        a.session_time,
        a.created_at
    FROM 
        students s
    LEFT JOIN 
        absences a ON s.id = a.student_id
    WHERE 
        a.created_at IS NULL OR MONTH(a.created_at) = MONTH(CURDATE())
    ORDER BY 
        s.id, a.created_at, a.session_time;
";

$result = $conn->query($sql);

// Initialize data array
$students = [];

while ($row = $result->fetch_assoc()) {
    $student_id = $row['student_id'];

    // Initialize student data if not already set
    if (!isset($students[$student_id])) {
        $students[$student_id] = [
            'name' => $row['student_name'],
            'phone1' => $row['phone1'],
            'phone2' => $row['phone2'],
            'absences' => [],
            'total_absences' => 0
        ];
    }

    // Map absences by date and session
    if (!empty($row['created_at'])) {
        $absence_date = date('Y-m-d', strtotime($row['created_at']));

        // Determine session type based on time string
        $session_time = $row['session_time'];
        $session_type = '';

        if (strpos($session_time, 'صباحًا') !== false) {
            $session_type = 'morning';
        } elseif (strpos($session_time, 'مساءً') !== false) {
            $session_type = 'evening';
        }

        if (!isset($students[$student_id]['absences'][$absence_date])) {
            $students[$student_id]['absences'][$absence_date] = ['morning' => false, 'evening' => false];
        }

        if ($session_type === 'morning') {
            $students[$student_id]['absences'][$absence_date]['morning'] = true;
        } elseif ($session_type === 'evening') {
            $students[$student_id]['absences'][$absence_date]['evening'] = true;
        }

        // Increment total absences for the student
        $students[$student_id]['total_absences']++;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل الغياب</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #ddd;
            text-align: center;
            padding: 1px;
        }
        th {
            background-color: #bdc1c5;
            color: white;
        }
        .absent {
            background-color: #ffcccc;
            color: red;
        }
        .present {
            background-color: inherit;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">سجل الغياب الشهري</h1>

    <table>
        <thead>
            <tr>
                <th colspan="3">التعريف</th>
                <th colspan="12">الاسم الكامل</th>
                <th colspan="5">هاتف 1</th>
                <th colspan="5">هاتف 2</th>
                <?php for ($day = 1; $day <= 30; $day++): ?>
                    <th colspan="2" style="min-width: 60px;"><?= str_pad($day, 2, '0', STR_PAD_LEFT) ?></th>
                <?php endfor; ?>
                <th colspan="2">المجموع</th>
            </tr>
            <tr>
                <th colspan="3"></th>
                <th colspan="12"></th>
                <th colspan="5"></th>
                <th colspan="5"></th>
                <?php for ($day = 1; $day <= 31; $day++): ?>
                    <th style="min-width: 30px;">ص</th>
                    <th style="min-width: 30px;">م</th>
                <?php endfor; ?>
                <th colspan="2">غيابات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student_id => $student): ?>
                <tr>
                    <td colspan="3"><?= $student_id ?></td>
                    <td colspan="12"><?= $student['name'] ?></td>
                    <td colspan="5"><?= $student['phone1'] ?></td>
                    <td colspan="5"><?= $student['phone2'] ?></td>

                    <?php for ($day = 1; $day <= 31; $day++): ?>
                        <?php
                        $date = date('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                        $morning_absent = isset($student['absences'][$date]['morning']) && $student['absences'][$date]['morning'];
                        $evening_absent = isset($student['absences'][$date]['evening']) && $student['absences'][$date]['evening'];
                        ?>
                        <td class="<?= $morning_absent ? 'absent' : 'present' ?>"><?= $morning_absent ? 'X' : '' ?></td>
                        <td class="<?= $evening_absent ? 'absent' : 'present' ?>"><?= $evening_absent ? 'X' : '' ?></td>
                    <?php endfor; ?>

                    <td colspan="2"><?= $student['total_absences'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
