<?php
include '../db_connection.php';

$conn->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $updateData = [
        'student_name'             => $_POST['student_name'],
        'phone'                    => $_POST['phone'],
        'birth_date'               => $_POST['birth_date'],
        'regstration_date_count'   => $_POST['regstration_date_count'],
        'birth_place'              => $_POST['birth_place'],
        'gender'                   => $_POST['gender'],
        'elmoutoune'                   => $_POST['elmoutoune'],
        'rewaya'                   => $_POST['rewaya'],
        'start'                    => $_POST['start'],
        'days'                     => isset($_POST['days']) ? implode(', ', $_POST['days']) : '',
        'class_name'               => $_POST['class_name'],
        'tdate'                    => $_POST['tdate'],
        'remaining'                => $_POST['remaining'],
    ];

    try {
        // Removed the extra comma before WHERE
        $sql = "UPDATE students SET
                student_name = ?,
                phone = ?,
                birth_date = ?,
                regstration_date_count = ?,
                birth_place = ?,
                gender = ?,
                elmoutoune = ?,
                rewaya = ?,
                start = ?,
                days = ?,
                tdate = ?,
                remaining = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssssssssi',
            $updateData['student_name'],
            $updateData['phone'],
            $updateData['birth_date'],
            $updateData['regstration_date_count'],
            $updateData['birth_place'],
            $updateData['gender'],
            $updateData['elmoutoune'],
            $updateData['rewaya'],
            $updateData['start'],
            $updateData['days'],
            $updateData['tdate'],
            $updateData['remaining'],
            $studentId
        );
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Oops! Something went wrong: ' . $stmt->error]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
