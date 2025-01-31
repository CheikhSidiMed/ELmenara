<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


if (isset($_GET['term'])) {
    $term = $conn->real_escape_string($_GET['term']);

    // Fetch matching agents
    $query = "SELECT agent_id, agent_name, phone, whatsapp_phone 
              FROM agents 
              WHERE agent_name LIKE '%$term%' 
                 OR phone LIKE '%$term%' 
                 OR whatsapp_phone LIKE '%$term%'
              LIMIT 10";

    $result = $conn->query($query);

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'label' => $row['agent_name'] . " - " . $row['phone'],
            'value' => $row['agent_name'], // Value to display in the input field
            'agent_id' => $row['agent_id'], // Additional data
            'phone' => $row['phone'],
            'whatsapp' => $row['whatsapp_phone'],
        ];
    }

    echo json_encode($data);
}
?>
