<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}
$role_id = $_SESSION['role_id'];


if (isset($_POST['branch_id'])) {
    $branch_id = $_POST['branch_id'];
    $sql = "";
    if($role_id == 6){
        $sql = "SELECT c.class_id, c.class_name
        FROM classes c
        JOIN branches AS b ON c.branch_id = b.branch_id
        JOIN user_branch AS ub ON ub.class_id = c.class_id
        WHERE c.branch_id = ?";
    }else{
    $sql = "SELECT class_id, class_name
        FROM classes WHERE branch_id = ?";
    }
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
