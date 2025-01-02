<?php
// Include database connection
include 'db_connection.php';

$term = $_GET['term'] ?? ''; // Get the term sent by the autocomplete function

if (!empty($term)) {
    // Prepare a statement to search for the term in the students table
    $sql = "SELECT id, student_name FROM students WHERE student_name LIKE ?";
    $stmt = $conn->prepare($sql);
    $likeTerm = '%' . $term . '%';
    $stmt->bind_param('s', $likeTerm);
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
