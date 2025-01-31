<?php

include 'db_connection.php'; 

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}



if (isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    
    // Fetch student details
    $query = "SELECT s.level_id, l.level_name, b.branch_id, c.class_id, c.class_name, b.branch_name 
              FROM students s
              LEFT JOIN levels l ON s.level_id = l.id
              LEFT JOIN classes c ON s.class_id = c.class_id
              LEFT JOIN branches b ON s.branch_id = b.branch_id
              WHERE s.id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Student not found.']);
    }
    $stmt->close();
}
?>
