<?php
include('db_connection.php');

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_POST['phone'])) {
    $phone = $_POST['phone'];

    // Query to search agents with a phone number that starts with the input
    $query = "SELECT agent_id, agent_name FROM agents 
              WHERE phone LIKE '$phone%' 
              OR phone_2 LIKE '$phone%' 
              OR whatsapp_phone LIKE '$phone%' 
              OR agent_name LIKE '$phone%' 
              LIMIT 5";
    $result = $conn->query($query);

    $matches = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $matches[] = [
                'id' => $row['agent_id'],
                'name' => $row['agent_name']
            ];
        }
    }

    echo json_encode(['matches' => $matches]);
} else {
    echo json_encode(['matches' => []]);
}
?>
