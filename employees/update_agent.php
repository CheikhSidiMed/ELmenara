<?php
include 'db_connection.php';

if (isset($_POST['agent_id'])) {
    $agent_id = $_POST['agent_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $phone2 = $_POST['phone2'];
    $job = $_POST['job'];
    $whatsapp = $_POST['whatsapp'];

    // Prepare SQL query to update agent details
    $sql = "UPDATE agents SET agent_name = ?, phone = ?, phone_2 = ?, profession = ?, whatsapp_phone = ? WHERE agent_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssi', $name, $phone, $phone2, $job, $whatsapp, $agent_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update agent']);
    }

    $stmt->close();
}

$conn->close();
?>
