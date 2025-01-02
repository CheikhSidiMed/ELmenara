<?php
include 'db_connection.php';

$response = array('success' => false, 'message' => ''); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['phone'];
    $name = $_POST['name'];
    $phone_2 = $_POST['phone_2'];
    $profession = $_POST['profession'];
    $whatsapp_phone = $_POST['whatsapp_phone'];

    $sql = "INSERT INTO agents (phone, agent_name, phone_2, profession, whatsapp_phone) 
            VALUES ('$phone', '$name', '$phone_2', '$profession', '$whatsapp_phone')";

    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'تمت إضافة الوكيل بنجاح';
    } else {
        $response['message'] = 'حدث خطأ: ' . $conn->error;
    }

    $conn->close();
}

header('Content-Type: application/json');
echo json_encode($response);
