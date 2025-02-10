<?php
// Include the database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


    if (isset($_POST['agent_id'])) {
        $agent_id = $_POST['agent_id'];

        $deleteQuery = $conn->prepare("DELETE FROM agents WHERE agent_id = ?");
        if ($deleteQuery === false) {
            die('Error in prepare statement for delete: ' . $conn->error);
        }

        $deleteQuery->bind_param("i", $agent_id);

        if ($deleteQuery->execute()) {
            echo json_encode(['success' => 'تم الحذف الوكل(ة).']);
        } else {
            echo json_encode(['error' => 'حدث خطأ أثناء محاولة حذف الوكل(ة).']);
        }

        $deleteQuery->close();
    } else {
        echo json_encode(['error' => 'حدث خطأ أثناء محاولة حذف الوكل(ة).']);
    }
?>
