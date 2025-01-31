<?php
include('db_connection.php');

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_POST['phone'])) {
    $searsh = $_POST['phone'];

    // Query to search agents with a phone number that starts with the input
    $query = "SELECT id, student_name FROM students 
              WHERE phone LIKE '$searsh%' 
              OR id LIKE '$searsh%' 
              OR student_name LIKE '$searsh%'
              LIMIT 5";
    $result = $conn->query($query);

    $matches = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $matches[] = [
                'id' => $row['id'],
                'name' => $row['student_name']
            ];
        }
    }

    echo json_encode(['matches' => $matches]);
} else {
    echo json_encode(['matches' => []]);
}
?>
