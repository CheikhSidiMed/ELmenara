<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_POST['class_id'])) {
    $class_id = $_POST['class_id'];

    $sql = "SELECT Price FROM classes WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->bind_result($price);
    $stmt->fetch();

    if ($price !== null) {
        $response = array('success' => true, 'price' => $price);
    } else {
        $response = array('success' => false, 'message' => 'No price found for the selected class.');
    }

    $stmt->close();
    $conn->close();

    echo json_encode($response);
}
?>
