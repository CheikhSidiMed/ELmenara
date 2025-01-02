<?php
// Include database connection
include 'db_connection.php';

$term = $_GET['term'] ?? ''; // Get the term sent by the autocomplete function

if (!empty($term)) {
    // Prepare a statement to search for the term in the students table
    $sql = "SELECT s.id, s.student_name 
    FROM students s 
    LEFT JOIN agents a ON s.agent_id = a.agent_id 
    WHERE s.student_name LIKE ? 
       OR s.id LIKE ? 
       OR s.phone LIKE ? 
       OR a.phone LIKE ? 
       OR a.phone_2 LIKE ? 
       OR a.whatsapp_phone LIKE ? 
    ORDER BY s.id";

$stmt = $conn->prepare($sql);

$likeTerm = '%' . $term . '%'; // Common LIKE term
$stmt->bind_param('ssssss', $likeTerm, $likeTerm, $likeTerm, $likeTerm, $likeTerm, $likeTerm);

$stmt->execute();

$result = $stmt->get_result();


    $suggestions = [];

    while ($row = $result->fetch_assoc()) {
        $suggestions[] = ['label' => $row['student_name'], 'value' => $row['id']];
    }

    // Output the suggestions in JSON format
    echo json_encode($suggestions);
}

$conn->close();
?>
