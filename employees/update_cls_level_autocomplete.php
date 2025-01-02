<?php
// Database connection
include 'db_connection.php'; 


// Handle search query
if (isset($_GET['term'])) {
    $term = $conn->real_escape_string($_GET['term']);
    $query = "SELECT students.id, students.student_name, students.phone FROM students 
              LEFT JOIN agents ON students.agent_id = agents.agent_id
                WHERE 
                students.student_name LIKE '%$term%' 
                OR students.phone LIKE '%$term%' 
                OR students.id LIKE '%$term%'
                OR agents.phone LIKE '%$term%'
                OR agents.phone_2 LIKE '%$term%'
                OR agents.whatsapp_phone LIKE '%$term%'
                LIMIT 10";

    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'label' => $row['student_name'],
            'value' => $row['id']
        ];
    }
    echo json_encode($data);
}
?>
