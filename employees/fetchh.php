<?php
include 'db_connection.php'; // Include your database connection script

// Check if the 'term' parameter exists in the request
if (isset($_GET['term'])) {
    $term = $_GET['term'];

    // Prepare the SQL query to search for students by name
    $sql = "SELECT student_name FROM students WHERE student_name LIKE ? LIMIT 10"; // LIMIT added for better performance
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $term . "%";
    $stmt->bind_param("s", $searchTerm);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the results
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row['student_name'];
    }

    // Return the results as a JSON array
    echo json_encode($students);
} else {
    // If no term is provided, return an empty array
    echo json_encode([]);
}
?>
