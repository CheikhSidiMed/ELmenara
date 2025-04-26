<?php
include '../db_connection.php';

$title = $_POST['title'];
$file = $_FILES['document'];

$upload_dir = 'uploads/';
$filename = time() . '_' . basename($file['name']);
$target = $upload_dir . $filename;

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (move_uploaded_file($file['tmp_name'], $target)) {
    $stmt = $conn->prepare("INSERT INTO documents (title, filename) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $filename);
    $stmt->execute();
    header("Location: ind_dm.php?success=1");
    exit();
} else {
    echo "فشل في تحميل الملف.";
}
