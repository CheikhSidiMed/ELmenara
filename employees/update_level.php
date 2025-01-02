<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $level_id = $_POST['level_id'];
    $level_name = $_POST['level_name'];
    $price = $_POST['price'];

    // Update query
    $sql = "UPDATE levels SET level_name = ?, price = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $level_name, $price, $level_id);

    if ($stmt->execute()) {
        // Redirect or give success message
        header("Location: levels.php?success=1");
        exit();
    } else {
        echo "Error updating level: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
