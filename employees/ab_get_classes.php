<?php
include 'db_connection.php';
$branch_id = intval($_GET['branch_id']);
$result = $conn->query("SELECT class_id, class_name FROM classes WHERE branch_id = $branch_id");
echo json_encode($result->fetch_all(MYSQLI_ASSOC));
?>
