<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_POST['branch_id'])) {
    $branch_id = $_POST['branch_id'];
    
    $sql = "SELECT class_id, class_name FROM classes WHERE branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<li class='list-group-item' data-class-id='{$row['class_id']}'>{$row['class_name']}</li>";
        }
    } else {
        echo "<p class='text-center text-muted'>لم يتم العثور على فئات لهذا الفرع.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>
