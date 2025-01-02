<?php
include 'db_connection.php';

if (isset($_POST['branch_id'])) {
    $branch_id = $_POST['branch_id'];

    $sql = "SELECT class_id, class_name FROM classes WHERE branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">اختر القسم</option>';
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . $row['class_id'] . '">' . $row['class_name'] . '</option>';
    }

    $stmt->close();
}

$conn->close();
?>
