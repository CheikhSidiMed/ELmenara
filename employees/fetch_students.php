
<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (isset($_POST['class_id'])) {
    $class_id = $_POST['class_id'];
    
    $sql = "SELECT s.student_name, s.regstration_date_count, s.registration_date, s.phone, s.birth_date, s.id, a.phone AS a_phone
        FROM students AS s
        LEFT JOIN agents AS a ON a.agent_id = s.agent_id
        WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['student_name']}</td><td>{$row['birth_date']}</td><td>{$row['regstration_date_count']}</td><td>{$row['registration_date']}</td><td>{$row['phone']}</td><td>{$row['a_phone']}</td></tr>";
        }
    } else {
        echo "<tr><td colspan='2' class='text-center text-muted'>لم يتم العثور على طلاب لهذا الفصل.</td></tr>";
    }

    $stmt->close();
    $conn->close();
}


// include 'db_connection.php';

// $term = $_GET['term'] ?? '';
// $class_id = $_GET['class_id'] ?? null;

// $students = [];
// $students_query = "
//     SELECT s.student_name AS full_name, s.phone, s.remaining, c.class_name 
//     FROM students s
//     JOIN classes c ON s.class_id = c.class_id
//     WHERE s.student_name LIKE ?
// ";

// $params = ["%$term%"];

// if ($class_id) {
//     $students_query .= " AND s.class_id = ?";
//     $params[] = $class_id;
// }

// $stmt = $conn->prepare($students_query);
// $stmt->bind_param(str_repeat('s', count($params)), ...$params);
// $stmt->execute();
// $result_students = $stmt->get_result();

// while ($row = $result_students->fetch_assoc()) {
//     $students[] = $row;
// }

// echo json_encode($students);

// $conn->close();

?>