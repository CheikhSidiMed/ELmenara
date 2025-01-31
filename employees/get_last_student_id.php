<?php
// Include the database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


try {
    // Query to get the last student ID
    $query = "SELECT id FROM students ORDER BY id DESC LIMIT 1";
    
    // Execute the query
    $result = $conn->query($query);

    // Check if a row was returned
    if ($result && $result->num_rows > 0) {
        // Fetch the result
        $row = $result->fetch_assoc();
        $lastStudentId = $row['id']; // Get the last student ID directly
        $nextStudentId = $lastStudentId + 1; // Increment to get the next ID
    } else {
        // Default to 1 if no students found (assuming 1 is the starting ID)
        $nextStudentId = 1;
    }

    // Return the JSON response
    echo json_encode(['last_student_id' => $nextStudentId]);

} catch (Exception $e) {
    // Return an error response in case of failure
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

// Close the database connection
$conn->close();
?>
