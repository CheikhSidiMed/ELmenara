<?php
include 'db_connection.php';

if (isset($_POST['branch_id'])) {
    $branch_id = $_POST['branch_id'];
    
    $sql = "SELECT class_id, class_name FROM classes WHERE branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $classes = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($classes); // Ensure data is in JSON format

    $stmt->close();
    $conn->close();
}
?>
