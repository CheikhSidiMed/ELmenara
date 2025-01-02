<?php
include 'db_connection.php';

if (isset($_GET['agent_id'])) {
    $agent_id = $_GET['agent_id'];

    // Prepare SQL query to fetch agent details
    $sql = "SELECT * FROM agents WHERE agent_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $agent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $agent = $result->fetch_assoc();
        
        // Return agent data as JSON
        echo json_encode([
            'id' => $agent['agent_id'],
            'name' => $agent['agent_name'],
            'phone' => $agent['phone'],
            'phone2' => $agent['phone_2'],
            'job' => $agent['profession'],
            'whatsapp' => $agent['whatsapp_phone']
        ]);
    } else {
        echo json_encode(['error' => 'Agent not found']);
    }
    
    $stmt->close();
}

$conn->close();
?>
