<?php
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$conn->set_charset('utf8mb4');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $agent_phone = isset($_POST['noAgentCheckbox']) ? NULL : $_POST['agentPhone'];
    $agent_id = $_POST['agentId'] ?? NULL;
    $student_name = $_POST['studentName'];
    $part_count = $_POST['partCount'];
    $gender = $_POST['gender'];
    $birth_date = empty($_POST['birthDate']) ? NULL : $_POST['birthDate'];
    $birth_place = $_POST['birthPlace']  ?? NULL;
    $branch_id = $_POST['branch'];
    $class_id = $_POST['class'];
    $level_id = $_POST['level'];
    $date_din = $_POST['date_din'];
    $student_phone = $_POST['studentphone'] ?? NULL; // New field for student's phone

    // Check if an image file was uploaded
    $photoContent = NULL;
    if (!empty($_FILES['studentPhoto']['tmp_name'])) {
        $photo = $_FILES['studentPhoto']['tmp_name'];
        // Read the image file content as binary if it was uploaded
        $photoContent = file_get_contents($photo);
    }

    $payment_nature = $_POST['paymentNature'];

    // If payment nature is exempt, set fees and discount to 0
    if ($payment_nature == 'معفى') {
        $fees = 0;
        $discount = 0;
        $remaining = 0;
    } else {
        $fees = $_POST['fees'];
        $discount = $_POST['discount'];
        $remaining = $_POST['remaining'];
    }
    

    // Prepare SQL query for inserting student data, including the student's phone
    $sql = "INSERT INTO students (registration_date, student_name, level_id, part_count, gender, birth_date, birth_place, branch_id, class_id, agent_id, payment_nature, fees, discount, remaining, student_photo, phone) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die(json_encode(array('success' => false, 'message' => 'Error preparing the statement: ' . $conn->error)));
    }

    // Variable for photo binding
    $photoParam = NULL;

    // If a photo was uploaded, bind it as BLOB, otherwise bind NULL
    if ($photoContent !== NULL) {
        $photoParam = $photoContent; // Assign photo content to the variable
        $stmt->bind_param(
            'ssissssiiisiiibi', 
            $date_din, $student_name, $level_id, $part_count, $gender, $birth_date, $birth_place,
            $branch_id, $class_id, $agent_id, $payment_nature, $fees, $discount, $remaining, $photoParam, $student_phone
        );
        $stmt->send_long_data(14, $photoParam);  // Send the BLOB data
    } else {
        // Pass NULL for the `student_photo` column if no image is uploaded
        $stmt->bind_param(
            'ssissssiiisiiibi', 
            $date_din, $student_name, $level_id, $part_count, $gender, $birth_date, $birth_place,
            $branch_id, $class_id, $agent_id, $payment_nature, $fees, $discount, $remaining, $photoParam, $student_phone
        );
    }

    // Execute the statement
    if ($stmt->execute()) {
        $last_id = $conn->insert_id; // Get the last inserted ID
        echo json_encode(array('success' => true, 'message' => 'تمت العملية بنجاح', 'student_id' => $last_id));    } else {
        echo json_encode(array('success' => false, 'message' => 'Oops! Something went wrong: ' . $stmt->error));
    }

    $stmt->close();
    $conn->close(); 
}
?>
