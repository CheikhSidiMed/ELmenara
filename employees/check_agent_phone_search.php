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

    // Query to fetch agent information
    $query = "SELECT * FROM agents 
              WHERE phone LIKE '$phone%' 
              OR phone_2 LIKE '$phone%' 
              OR agent_name LIKE '$phone%' 
              OR whatsapp_phone LIKE '$phone%'";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $agent = $result->fetch_assoc();

        // Fetch related students for this agent
        $studentQuery = "SELECT a.id, a.student_name, c.class_name, b.branch_name 
                         FROM students a
                         LEFT JOIN classes c ON a.class_id = c.class_id 
                         LEFT JOIN branches b ON a.branch_id = b.branch_id 
                         WHERE agent_id = " . $agent['agent_id'];
                         
        $studentResult = $conn->query($studentQuery);

        $students = [];
        while ($studentRow = $studentResult->fetch_assoc()) {
            $students[] = [
                'id' => $studentRow['id'], // Fixed to use $studentRow
                'name' => $studentRow['student_name'],
                'class' => $studentRow['class_name'],  
                'branch' => $studentRow['branch_name']  
            ];
        }

        echo json_encode([
            'exists' => true,
            'agent_name' => $agent['agent_name'],
            'agent_id' => $agent['agent_id'],
            'students' => $students
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
} else {
    echo json_encode(['exists' => false]);
}
?>
