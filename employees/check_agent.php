<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_POST['phone'])) {
    $phone = $_POST['phone'];

    $sql = "
        SELECT * FROM agents 
        WHERE phone LIKE '$phone%' 
        OR phone_2 LIKE '$phone%' 
        OR whatsapp_phone LIKE '$phone%'
    ";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode(array('exists' => true));
    } else {
        echo json_encode(array('exists' => false));
    }
}
?>
