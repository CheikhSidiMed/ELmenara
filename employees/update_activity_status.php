<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Update the status of activities based on the end_date
$update_status_query = "
    UPDATE activities
    SET status = 'Ended'
    WHERE end_date < CURDATE() AND status = 'Ongoing'
";

// Execute the update query
$conn->query($update_status_query);

// Prepare the message based on the number of affected rows
if ($conn->affected_rows > 0) {
    $message = 'تم تحديث حالة الأنشطة بنجاح.';
} else {
    $message = 'لا توجد أنشطة قيد التنفيذ تحتاج إلى تحديث.';
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث الأنشطة</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>
// Display the Swal.fire message
Swal.fire({
    icon: 'info',
    title: 'تحديث الحالة',
    text: '<?php echo $message; ?>',
    confirmButtonText: 'موافق'
}).then(() => {
    // Optional: redirect to another page or refresh the page after the message
    window.location.href = 'list_activities.php';  // Redirect to the list of activities
});
</script>

</body>
</html>
