<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$class_id = intval($_GET['class_id']);
$result = $conn->query("SELECT s.id, s.student_name, s.phone, a.whatsapp_phone
    FROM students s
    LEFT JOIN agents a ON s.agent_id = a.agent_id
    WHERE s.etat=0 AND s.is_active=0 AND s.class_id = $class_id
");
echo json_encode($result->fetch_all(MYSQLI_ASSOC));
?>
