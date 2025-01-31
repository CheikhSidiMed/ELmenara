<?php

    header('Content-Type: application/json');

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    include 'db_connection.php';

    session_start();

    if (!isset($_SESSION['userid'])) {
        echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
        exit();
    }

  
    $conn->set_charset('utf8mb4');

try {
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
        $student_phone = $_POST['studentphone'] ?? NULL;
        // Check if an image file was uploaded
        $photoContent = NULL;
        $photoUrl = '';
        $uploadDir = 'uploads/';


        if (isset($_FILES['studentPhoto']) && $_FILES['studentPhoto']['error'] === UPLOAD_ERR_OK) {
            // Récupérer les informations sur le fichier téléchargé
            $fileTmpPath = $_FILES['studentPhoto']['tmp_name'];
            $fileName = $_FILES['studentPhoto']['name'];
            $fileSize = $_FILES['studentPhoto']['size'];
            $fileType = $_FILES['studentPhoto']['type'];
            
            // Vérifier l'extension du fichier
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        
            // Définir le répertoire de téléchargement
            $uploadDir = 'uploads/';
            $uniqueFileName = uniqid('photo_', true) . '.' . $fileExtension;
            $photoUrl = $uploadDir . $uniqueFileName;
            
            if (move_uploaded_file($fileTmpPath, $photoUrl)) {
                echo "Fichier téléchargé avec succès : " . $photoUrl;
            } else {
                echo "Erreur lors du téléchargement du fichier.";
            }
        } else {
            echo "Erreur dans le téléchargement ou aucun fichier sélectionné.";
        }
        
        $payment_nature = $_POST['paymentNature'];

        if ($payment_nature == 'معفى') {
            $fees = 0;
            $discount = 0;
            $remaining = 0;
        } else {
            $fees = $_POST['fees'];
            $discount = $_POST['discount'];
            $remaining = $_POST['remaining'];
        }

        $sql = "INSERT INTO students (registration_date, student_name, level_id, part_count, gender, birth_date, birth_place, branch_id, class_id, agent_id, payment_nature, fees, discount, remaining, student_photo, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die(json_encode(array('success' => false, 'message' => 'Error preparing the statement: ' . $conn->error)));
        }
        $stmt->bind_param('ssissssiiisiiisi',
            $date_din, $student_name, $level_id, $part_count, $gender, $birth_date, $birth_place,
            $branch_id, $class_id, $agent_id, $payment_nature, $fees, $discount, $remaining, $photoUrl, $student_phone
        );

        if ($stmt->execute()) {
            $last_id = $conn->insert_id;
            echo json_encode(array('success' => true, 'message' => 'تمت العملية بنجاح', 'student_id' => $last_id));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Oops! Something went wrong: ' . $stmt->error));
        }

        $stmt->close();
        $conn->close();
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur du serveur : ' . $e->getMessage(),
    ]);
}
exit;
?>
