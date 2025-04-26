<?php
include '../db_connection.php';

$id = $_GET['id'];
$result = $conn->query("SELECT filename FROM documents WHERE id = $id");
if ($row = $result->fetch_assoc()) {
    $file_path = 'uploads/' . $row['filename'];
    if (file_exists($file_path)) unlink($file_path);
}

$conn->query("DELETE FROM documents WHERE id = $id");
header("Location: ind_dm.php?deleted=1");
exit;
