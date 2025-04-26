<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

if (isset($_POST['suspend_student_id'])) {
    $student_id = $_POST['suspend_student_id'];
    $suspension_reason = $_POST['suspension_reason'];
    $targetStatus = $_POST['targetStatus'];

    $student_query = "SELECT
            s.id,
            s.remaining,
            COALESCE(SUM(p.remaining_amount), 0) AS total_paid,
            (
                (
                CASE
                    WHEN MONTH(CURDATE()) >= 10
                    THEN MONTH(CURDATE()) - 9
                    ELSE MONTH(CURDATE()) + 3
                END
                - (
                    CASE
                    WHEN MONTH(s.regstration_date_count) <= 9
                        THEN MONTH(s.regstration_date_count) + 3
                    ELSE MONTH(s.regstration_date_count) - 9
                    END
                )
                - COALESCE((SELECT COUNT(DISTINCT p2.month)
                            FROM payments p2
                            WHERE p2.student_id = s.id), 0)
            ) * s.remaining
            + COALESCE(SUM(p.remaining_amount), 0)
            ) AS total_due
        FROM students s
        LEFT JOIN payments p
            ON s.id = p.student_id
        WHERE s.id = ?
        GROUP BY s.id;";

    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($student) {
        $current_date = date('Y-m-d H:i:s');
        $balance = $student['total_due'];

        if ($targetStatus == 1) {
            $sql = "UPDATE students SET balance = ?, is_active = 1, date_desectivation = ?, suspension_reason=? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $balance, $current_date, $suspension_reason, $student_id);
        } else {
            $sql = "UPDATE students SET is_active = 0, registration_date = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $current_date, $student_id);
        }

        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'success', 'message' => 'تم تعليق الطالب بنجاح']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الطالب']);
    }
    exit();
}


?>