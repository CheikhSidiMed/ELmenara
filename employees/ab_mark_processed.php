<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}
// Initialize processed students array in the session if not set
if (!isset($_SESSION['processed_students'])) {
    $_SESSION['processed_students'] = [];
}

// Decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['student_id'], $data['session_time'])) {
    $student_id = intval($data['student_id']);
    $session_time = htmlspecialchars($data['session_time'], ENT_QUOTES, 'UTF-8');
    $created_at = htmlspecialchars($data['date_time'], ENT_QUOTES, 'UTF-8');
    $currentTime = time();

    // Check if the student was already processed recently
    if (isset($_SESSION['processed_students'][$student_id]) &&
        ($currentTime - $_SESSION['processed_students'][$student_id] < 60)) { // 60 seconds cooldown
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'تم معالجة هذا الطالب مؤخرًا.']);
        exit;
    }

    // Insert into absences table
    $sql = "INSERT INTO absences (student_id, session_time, created_at) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iss', $student_id, $session_time, $created_at);

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
