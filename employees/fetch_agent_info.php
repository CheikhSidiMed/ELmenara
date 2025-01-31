<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_POST['phone'])) {
    $agent_phone = $_POST['phone'];
    
    error_log("Received phone number: " . $agent_phone);  // Debugging: Log the received phone number

    // Query to find agent and related students
    $agentQuery = "SELECT agent_id, agent_name FROM agents WHERE phone LIKE ? OR phone_2 LIKE ? OR whatsapp_phone LIKE ?";
    $stmt = $conn->prepare($agentQuery);
    $likePhone = $phone . '%';
    $stmt->bind_param("sss", $likePhone, $likePhone, $likePhone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $agent = $result->fetch_assoc();
        $agent_name = $agent['agent_id'];

        // Now fetch related students
        $stmt2 = $conn->prepare("SELECT student_name FROM students WHERE agent_id = ?");
        $stmt2->bind_param("s", $agent_phone);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        $related_students = [];
        while ($student = $result2->fetch_assoc()) {
            $related_students[] = $student['student_name'];
        }

        echo json_encode([
            'success' => true,
            'agent_name' => $agent_name,
            'related_students' => $related_students
        ]);
        
    } else {
        error_log("No agent found for phone number: " . $agent_phone);  // Debugging: log when no agent is found
        // No agent found
        echo json_encode(['success' => false]);
    }

    $stmt->close();
    $conn->close();
} else {
    error_log("No phone number provided.");  // Debugging: log if no phone number is provided
}
?>
