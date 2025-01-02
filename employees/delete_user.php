<?php
// Include the database connection
include 'db_connection.php';

// Check if the user ID is provided in the URL
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Prepare the DELETE statement
    $deleteQuery = $conn->prepare("DELETE FROM users WHERE id = ?");
    
    if ($deleteQuery === false) {
        // If the prepare() failed, display the error
        die('Error in prepare statement for delete: ' . $conn->error);
    }

    // Bind the user ID to the query
    $deleteQuery->bind_param("i", $user_id);

    // Execute the deletion
    if ($deleteQuery->execute()) {
        // Redirect to users.php after successful deletion
        header('Location: users.php');
        exit();
    } else {
        // If there's an error during deletion
        echo "<script>alert('حدث خطأ أثناء محاولة حذف المستخدم.');</script>";
        echo "<script type='text/javascript'> document.location = 'users.php'; </script>";
    }

    // Close the prepared statement
    $deleteQuery->close();
} else {
    // If no user ID is provided
    echo "<script>alert('User ID not provided!');</script>";
    echo "<script type='text/javascript'> document.location = 'users.php'; </script>";
}
?>
