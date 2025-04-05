<?php

    header('Content-Type: application/json');

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    include '../db_connection.php';

    session_start();

    if (!isset($_SESSION['userid'])) {
        echo "<script type='text/javascript'> document.location = '../../index.php'; </script>";
        exit();
    }

  
    $conn->set_charset('utf8mb4');

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $agent_phone = (isset($_POST['agentP']) && $_POST['agentP'] !== '') ? $_POST['agentP'] : null;
        $student_phone = (isset($_POST['studentphone']) && $_POST['studentphone'] !== '') ? $_POST['studentphone'] : 0;
        $agent_id = $_POST['agentId'] ?? null;
        $student_name = $_POST['studentName'];
        $rewaya = $_POST['rewaya'];
        $gender = $_POST['gender'];
        $birth_date = empty($_POST['birthDate']) ? null : $_POST['birthDate'];
        $birth_place = $_POST['birthPlace']  ?? null;
        $branch_id = 22;
        $days_s = $_POST['days'] ?? [];
        $days = implode(', ', $days_s);
        $start = $_POST['start'];
        $elmoutoune = $_POST['elmoutoune'];
        $tdate = $_POST['tdate'];
        $class = $_POST['class'];
        $date_din = $_POST['date_din'];
        $current_city = $_POST['current_city'];
        // Check if an image file was uploaded
        $photoContent = null;
        $photoUrl = '';
        $uploadDir = '../uploads/';
        

        if (isset($_FILES['studentPhoto']) && $_FILES['studentPhoto']['error'] === UPLOAD_ERR_OK) {
            // Récupérer les informations sur le fichier téléchargé
            $fileTmpPath = $_FILES['studentPhoto']['tmp_name'];
            $fileName = $_FILES['studentPhoto']['name'];
            $fileSize = $_FILES['studentPhoto']['size'];
            $fileType = $_FILES['studentPhoto']['type'];
            
            // Vérifier l'extension du fichier
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        
            // Définir le répertoire de téléchargement
            $uploadDir = '../uploads/';
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
        
        $sql = "INSERT INTO students (current_city, elmoutoune, start, class_id, regstration_date_count, student_name, rewaya, days, gender, birth_date, birth_place, branch_id, tdate, agent_id, payment_nature, fees, discount, remaining, student_photo, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die(json_encode(array('success' => false, 'message' => 'Error preparing the statement: ' . $conn->error)));
        }
        $stmt->bind_param('sssssssssssisisiiisi',
            $elmoutoune, $current_city, $start, $class, $date_din, $student_name, $rewaya, $days, $gender, $birth_date, $birth_place,
            $branch_id, $tdate, $agent_id, $payment_nature, $fees, $discount, $remaining, $photoUrl, $student_phone
        );

        if ($stmt->execute()) {
            $msg = "أهلاً وسهلاً بكم في مقرأة المنارة والرباط للتعليم عن بُعد\n\n" .
                    "📝 استمارة التسجيل:\n\n" .
                    "اسم الطالب(ة): $student_name\n" .
                    "تاريخ الميلاد: $birth_date\n" .
                    "مكان الميلاد: $birth_place\n" .
                    "مكان الإقامة: $current_city\n" .
                    "الرواية: $rewaya\n" .
                    "البداية: $start\n" .
                    "الأيام المناسبة: $days\n" .
                    "التوقيت: $tdate\n" .
                    "تاريخ الالتحاق: $date_din\n" .
                    "الرسوم: $remaining\n";


            $phone = $student_phone !== 0 ? $student_phone : $agent_phone;
            $enMSG = urlencode($msg);
            $whatsappUrl = "https://wa.me/222$phone?text=$enMSG";

            $last_id = $conn->insert_id;
            echo json_encode(
                array('success' => true,
                    'message' => 'تمت العملية بنجاح',
                    'student_id' => $last_id,
                    'Url' => $whatsappUrl
                ));
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
