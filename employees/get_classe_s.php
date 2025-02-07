<?php
include 'db_connection.php'; // Remplacez par votre connexion à la base de données

if (isset($_GET['branch_id'])) {
    $branch_id = intval($_GET['branch_id']);
    $query = "SELECT class_id, class_name FROM classes WHERE branch_id = $branch_id";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        echo "<option value=''>اختر الصف</option>";
        while ($row = $result->fetch_assoc()) {
            echo "<option value='{$row['class_id']}'>{$row['class_name']}</option>";
        }
    } else {
        echo "<option value=''>لا يوجد صفوف</option>";
    }
}
?>
