<?php
include 'database.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$branch_id = $_GET['branch_id'];
$classes = getClasses($branch_id);

echo '<option value="">اختر الصف</option>';
foreach ($classes as $class) {
    echo '<option value="' . htmlspecialchars($class['id']) . '">' . htmlspecialchars($class['name']) . '</option>';
}
?>
