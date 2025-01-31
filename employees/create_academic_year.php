<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}

include 'db_connection.php'; 

$arabicMonths = [
    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس',
    4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
    7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر',
    10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
];

$startMonth = 10; // Octobre
$endMonth = 9; // Septembre
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');

// Générer les mois académiques
$starAcademicMonths = range($startMonth, 12);
$endaAcademicMonths = ($currentMonth <= $endMonth) ? range(1, $currentMonth) : [];

$allAcademicMonths = array_merge($starAcademicMonths, $endaAcademicMonths);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year_name = $_POST['year_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Vérification si l'année académique existe déjà
    $sqlCheck = "SELECT COUNT(*) FROM academic_years WHERE year_name = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param('s', $year_name);
    $stmtCheck->execute();
    $stmtCheck->bind_result($count);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($count > 0) {
        $_SESSION['warning_message'] = 'السنة الدراسية موجودة بالفعل! الرجاء اختيار اسم آخر.';
        header('Location: create_academic_year.php');
        exit();
    }

    // Récupérer les étudiants avec montants impayés
    $sql_new = "SELECT s.id, s.student_name, s.registration_date, s.phone, p.month, s.remaining 
                FROM students s
                LEFT JOIN payments p ON s.id = p.student_id
                WHERE s.remaining != 0.00
                GROUP BY s.id, p.month";

    $stmt_new = $conn->prepare($sql_new);
    $stmt_new->execute();
    $result = $stmt_new->get_result();
    $students = [];

    while ($row = $result->fetch_assoc()) {
        $studentName = $row['student_name'];
        if (!isset($students[$studentName])) {
            $students[$studentName] = [
                'id' => $row['id'],
                'phone' => $row['phone'],
                'registration_date' => $row['registration_date'],
                'remaining' => $row['remaining'],
                'paid_months' => [],
                'unpaid_months' => []
            ];
        }
        if ($row['month']) {
            $students[$studentName]['paid_months'][] = $row['month'];
        }
    }

    foreach ($students as &$student) {
        $registrationYear = (int)date('Y', strtotime($student['registration_date']));
        $registrationMonth = (int)date('n', strtotime($student['registration_date']));

        foreach ($allAcademicMonths as $month) {
            $academicYear = ($month >= $startMonth) ? $currentYear : $currentYear + 1;

            if ($academicYear === $registrationYear && $month <= $registrationMonth) {
                continue;
            }

            // if (!in_array($month, $student['paid_months'])) {
            //     $student['unpaid_months'][] = $arabicMonths[$month];
            // }
            if ((int)$month > 0 && isset($arabicMonths[(int)$month]) && !in_array($arabicMonths[(int)$month], $student['paid_months'])) {
                $student['unpaid_months'][] = $arabicMonths[(int)$month];
            }
        }
    }
    unset($student);

    // Insérer les mois non payés
    $insertQuery = $conn->prepare("INSERT INTO months_not_paid (student_id, student_name, month_name, remaining_amount, created_at) VALUES (?, ?, ?, ?, NOW())");

    foreach ($students as $studentName => $student) {
        foreach ($student['unpaid_months'] as $unpaidMonth) {
            $insertQuery->bind_param(
                'issd',
                $student['id'],
                $studentName,
                $unpaidMonth,
                $student['remaining']
            );
            $insertQuery->execute();
        }
    }

    $insertQuery->close();
    $stmt_new->close();

    // Archiver les données et ouvrir une nouvelle année
    $conn->begin_transaction();

    try {
        $tablesWithConditions = [
            'payments' => "remaining_amount=0.00",
            'transactions' => "1=1",
            'expense_transaction' => "1=1"
        ];

        foreach ($tablesWithConditions as $table => $condition) {
            $sql = "DELETE FROM $table WHERE $condition";
            if (!$conn->query($sql)) {
                throw new Exception("Erreur lors de la suppression dans $table: " . $conn->error);
            }
        }

        $targetDate = "$currentYear-10-01";
        $sql_q = "UPDATE student_activities SET subscription_date = ? WHERE 1=1";
        $stmt_q = $conn->prepare($sql_q);
        $stmt_q->bind_param('s', $targetDate);
        $stmt_q->execute();

        $sql = "INSERT INTO academic_years (year_name, start_date, end_date) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $year_name, $start_date, $end_date);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success_message'] = 'تم فتح السنة الدراسية بنجاح!';
        header('Location: create_academic_year.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'حدث خطأ أثناء تنفيذ العملية: ' . $e->getMessage();
        header('Location: create_academic_year.php');
        exit();
    } finally {
        $stmt->close();
    }
}
?>



<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة سنة دراسية جديدة</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <link rel="stylesheet" href="css/tajawal.css">
    <style>
        body {
            direction: rtl;
            background-color: #f8f9fa;
            font-family: 'Tajawal', sans-serif;
        }
        .container {
            margin-top: 50px;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-control, .btn {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">فتح سنة دراسية جديدة</h2>
        <form id="academicYearForm" method="POST" onsubmit="return confirmSubmission(event)">
            <div class="mb-3">
                <label for="year_name" class="form-label">اختر السنة الدراسية</label>
                <select class="form-control" id="year_name" name="year_name" required>
                    <option value="">-- اختر السنة الدراسية --</option>
                    <option value="2024-2025">2024-2025</option>
                    <option value="2025-2026">2025-2026</option>
                    <option value="2026-2027">2026-2027</option>
                    <option value="2027-2028">2027-2028</option>
                    <option value="2028-2029">2028-2029</option>
                    <option value="2029-2030">2029-2030</option>
                    <option value="2030-2031">2030-2031</option>
                    <option value="2031-2032">2031-2032</option>
                    <option value="2032-2033">2032-2033</option>
                    <option value="2033-2034">2033-2034</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="start_date" class="form-label">تاريخ بداية السنة الدراسية</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">تاريخ نهاية السنة الدراسية</label>
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <button type="submit" class="btn btn-primary">أرشيف العام الماضي وفتح السنة الجديد</button>
                <a href="home.php" class="btn btn-secondary">الرجوع إلى الرئيسية</a>
            </div>
        </form>
    </div>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: '<?= addslashes($_SESSION['success_message']) ?>',
                    confirmButtonText: 'حسناً'
                });
            });
        </script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php
        if (!empty($_SESSION['warning_message'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        icon: 'warning',
                        title: 'تحذير',
                        text: '<?= addslashes($_SESSION['warning_message']) ?>',
                        confirmButtonText: 'حسناً'
                    });
                });
            </script>
            <?php unset($_SESSION['warning_message']); // Supprimer le message après affichage ?>
        <?php endif; ?>


    <script src="js/sweetalert2.min.js"></script>

    <script>
        function confirmSubmission(event) {
            event.preventDefault();
            const form = document.getElementById('academicYearForm');

            Swal.fire({
                title: 'تأكيد فتح',
                text: 'هل أنت متأكد من أنك تريد فتح هذه السنة الدراسية؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، أفتحها',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
