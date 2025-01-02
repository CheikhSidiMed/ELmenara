<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

$msg = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['student_ids'], $_POST['student_data'], $_POST['quarter'])) {
            $studentIds = $_POST['student_ids'];
            $studentData = $_POST['student_data'];
            $quarter = $_POST['quarter'];

            foreach ($studentIds as $studentId) {
                if (isset($studentData[$studentId])) {
                    $data = $studentData[$studentId];

                    $stmt = $conn->prepare("
                        INSERT INTO student_performance (
                            student_id, month_1_income, month_1_absence, 
                            month_2_income, month_2_absence, month_3_income, 
                            month_3_absence, total_income, total_absence, 
                            total_groups, extra, notes, quarter
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            month_1_income = VALUES(month_1_income),
                            month_1_absence = VALUES(month_1_absence),
                            month_2_income = VALUES(month_2_income),
                            month_2_absence = VALUES(month_2_absence),
                            month_3_income = VALUES(month_3_income),
                            month_3_absence = VALUES(month_3_absence),
                            total_income = VALUES(total_income),
                            total_absence = VALUES(total_absence),
                            total_groups = VALUES(total_groups),
                            extra = VALUES(extra),
                            notes = VALUES(notes)
                    ");

                    $stmt->bind_param(
                        'issssssssssss', 
                        $studentId, 
                        $data['month_1_income'], $data['month_1_absence'],
                        $data['month_2_income'], $data['month_2_absence'],
                        $data['month_3_income'], $data['month_3_absence'],
                        $data['total_income'], $data['total_absence'],
                        $data['total_groups'], $data['extra'], $data['notes'], $quarter
                    );

                    $stmt->execute();
                    if ($stmt->affected_rows > 0) {
                        $msg = 'suc';                       
                    }
                }
            }
        } else {
            echo "Missing required form data.";
        }

    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!-- Include SweetAlert2 CSS in the head -->

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>

        <link rel="stylesheet" href="css/sweetalert2.css">
    </head>
<body>

    <script src="js/sweetalert2.min.js"></script>
        <?php if( $msg === 'suc'): ?>
            <script>             
                Swal.fire({
                        icon: 'success',
                        title: 'تم الحفظ بنجاح',
                        text: 'تم تحديث البيانات بنجاح للطلاب المحددين.',
                        confirmButtonText: 'موافق'
                        }).then((result) => {                     
                            if (result.isConfirmed) {
                                location.reload();
                                window.location.href = 'quarterly_selection.php';
                            }
                        });
            </script>
        <?php endif ?>

</body>
</html>