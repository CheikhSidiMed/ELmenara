<?php
// insert_level.php
include 'db_connection.php';  // Include your database connection file

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $level_name = $_POST['level_name'];
    $price = $_POST['price'];

    // Prepare an SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO levels (level_name, price) VALUES (?, ?)");
    $stmt->bind_param("sd", $level_name, $price);

    // Execute the statement
    if ($stmt->execute()) {
        // If the insertion was successful, redirect back with success flag
        header('Location: home.php?success=1');  // Redirect with success flag
        exit();
    } else {
        // If there was an error inserting, handle it
        echo "Error: " . $stmt->error;
    }

    // Close the statement and the connection
    $stmt->close();
    $conn->close();
}
?>
