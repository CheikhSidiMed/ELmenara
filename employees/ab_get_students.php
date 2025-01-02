<?php
include 'db_connection.php';
$class_id = intval($_GET['class_id']);
$result = $conn->query("
    SELECT s.id, s.student_name, s.phone, a.whatsapp_phone 
    FROM students s 
    LEFT JOIN agents a ON s.agent_id = a.agent_id 
    WHERE s.class_id = $class_id
");
echo json_encode($result->fetch_all(MYSQLI_ASSOC));
?>
