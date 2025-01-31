<?php
include 'db_connection.php'; // Include your database connection script

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_POST['class_id'])) {
    $class_id = $_POST['class_id'];

    // Fetch students based on class_id
    $sql = "SELECT student_name, registration_date FROM students WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $output = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output .= '<tr>';
            $output .= '<td>' . $row['student_name'] . '</td>';
            $output .= '<td>' . $row['registration_date'] . '</td>';
            $output .= '</tr>';
        }
    } else {
        $output .= '<tr><td colspan="2">لا يوجد طلاب مسجلين في هذا القسم</td></tr>';
    }

    echo $output;
    $stmt->close();
}

$conn->close();
?>
