<?php
include 'db_connection.php';
session_start();

// Initialize processed students array in the session if not set
if (!isset($_SESSION['processed_students'])) {
    $_SESSION['processed_students'] = [];
}

// Decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['student_id'], $data['session_time'])) {
    $student_id = intval($data['student_id']);
    $activity_id = intval($data['activity_id']);

    $session_time = htmlspecialchars($data['session_time'], ENT_QUOTES, 'UTF-8');
    $currentTime = time();

    // Check if the student was already processed recently
    if (isset($_SESSION['processed_students'][$student_id]) &&
        ($currentTime - $_SESSION['processed_students'][$student_id] < 60)) { // 60 seconds cooldown
        http_response_code(429); // Too many requests
        echo json_encode(['status' => 'error', 'message' => 'تم معالجة هذا الطالب مؤخرًا.']);
        exit;
    }


    $query = $conn->prepare("
    SELECT s.student_name, s.phone, a.whatsapp_phone
    FROM students s
    LEFT JOIN agents a ON s.agent_id = a.agent_id
    WHERE s.id = ?
");
$query->bind_param("i", $student_id);
$query->execute();
$result = $query->get_result();

$query2 = $conn->prepare("
    SELECT s.name AS student_name, s.phone, s.wh AS whatsapp_phone
    FROM students_etrang s
    WHERE s.id = ?
");
$query2->bind_param("i", $student_id);
$query2->execute();
$result2 = $query2->get_result();

$sql = '';

if ($result && $student1 = $result->fetch_assoc()) {
    $sql = "INSERT INTO absences_activity (activity_id, session_number, student_id) VALUES (?, ?, ?)";

}elseif ($result2 && $student2 = $result2->fetch_assoc()) {
    $sql = "INSERT INTO absences_activity (activity_id, session_number, student_ert_id) VALUES (?, ?, ?)";
}

    // Insert into absences table
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iii', $activity_id, $session_time, $student_id);

        if ($stmt->execute()) {
            $_SESSION['processed_students'][$student_id] = $currentTime;
            echo json_encode(['status' => 'success', 'message' => 'تم تسجيل غياب الطالب بنجاح.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'خطأ أثناء تسجيل الغياب: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'خطأ في إعداد الاستعلام.']);
    }
} else {
    http_response_code(400); // Bad request
    echo json_encode(['status' => 'error', 'message' => 'معرف الطالب أو وقت الجلسة مفقود.']);
}
?>
