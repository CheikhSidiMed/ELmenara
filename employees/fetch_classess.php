<?php
include 'database.php';

$branch_id = $_GET['branch_id'];
$classes = getClasses($branch_id);

echo '<option value="">اختر الصف</option>';
foreach ($classes as $class) {
    echo '<option value="' . htmlspecialchars($class['id']) . '">' . htmlspecialchars($class['name']) . '</option>';
}
?>
