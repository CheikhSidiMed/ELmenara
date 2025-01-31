<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // SQL to insert the new user
    $stmt = $conn->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $username, $password, $role);
    
    if ($stmt->execute()) {
        // Log the activity of adding a user
        if (isset($_SESSION['userid'])) {
            $logged_in_user_id = $_SESSION['userid']; // Get the logged-in user's ID
            $activity_description = "Added a new user: $username";
            $page_visited = 'add_user_process.php';
            
            // Insert the activity log
            $logStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_description, page_visited) VALUES (?, ?, ?)");
            $logStmt->bind_param("iss", $logged_in_user_id, $activity_description, $page_visited);
            $logStmt->execute();
            $logStmt->close();
        }

        // Respond with success
        echo json_encode(['success' => true]);
    } else {
        // Respond with failure
        echo json_encode(['success' => false]);
    }

    $stmt->close();
}
?>
