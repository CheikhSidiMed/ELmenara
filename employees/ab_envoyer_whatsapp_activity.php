<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Initialiser la session pour les étudiants traités
if (!isset($_SESSION['processed_students'])) {
    $_SESSION['processed_students'] = [];
}

$currentTime = time();

// Nettoyer les étudiants traités après 10 minutes (600 secondes)
foreach ($_SESSION['processed_students'] as $id => $timestamp) {
    if ($currentTime - $timestamp > 6000) {
        unset($_SESSION['processed_students'][$id]);
    }
}

// Vérifier si des étudiants absents sont soumis
if (!empty($_POST['absent_students'])) {
    foreach ($_POST['absent_students'] as $student_id) {
        $student_id = intval($student_id);

        if (isset($_SESSION['processed_students'][$student_id])) {
            echo "
            <div style='margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #e6f7e6;'>
                <span style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>
                    ✅ تم إرسال الرسالة إلى الطالب : <strong style='color: #007bff;'>$student_id</strong>
                </span>
            </div>";
            continue; // Passer à l'étudiant suivant
        }

        // Récupérer les informations de l'étudiant
        $query = $conn->prepare("
            SELECT s.student_name, s.phone, a.whatsapp_phone
            FROM students s
            LEFT JOIN agents a ON s.agent_id = a.agent_id
            WHERE s.id = ?
        ");
        $query->bind_param("i", $student_id);
        $query->execute();
        $result = $query->get_result();

        $query2 = $conn->prepare("
            SELECT s.name AS student_name, s.phone, s.wh AS whatsapp_phone
            FROM students_etrang s
            WHERE s.id = ?
        ");
        $query2->bind_param("i", $student_id);
        $query2->execute();
        $result2 = $query2->get_result();

        $student = null;

        if ($result && $student1 = $result->fetch_assoc()) {
            $student = $student1;

        }elseif ($result2 && $student2 = $result2->fetch_assoc()) {
            $student = $student2;
        }


        if ($student !== null) {

            // Récupération des données de session
            $session_time = htmlspecialchars($_POST['session_time'] ?? '', ENT_QUOTES, 'UTF-8');
            $activity_id = htmlspecialchars($_POST['activity_id'] ?? '', ENT_QUOTES, 'UTF-8');
            $student_name = htmlspecialchars($student['student_name'], ENT_QUOTES, 'UTF-8');

            $activity_name = '';
            $stmt = $conn->prepare("SELECT id, activity_name FROM activities WHERE id = ?");
            $stmt->bind_param("i", $activity_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $activity_name = $result->fetch_assoc()['activity_name'];
            }


            $phone = !empty($student['phone']) && $student['phone'] != '0'
                ? $student['phone']
                : $student['whatsapp_phone'];

            if (!empty($phone)) {
               // Créer le message WhatsApp
                $message = "السلام عليكم ورحمة الله تعالى وبركاته\n";
                $message .= "تحية طيبة وبعد،\n\n";
                $message .= "الطالب(ة): $student_name\n";
                $message .= "غاب/ت اليوم : عن الحصة $session_time.\n";
                $message .= "  من : $activity_name.\n";
                $message .= "عساه خيرا.\n\n";
                
                $encodedMessage = urlencode($message);
                $whatsappUrl = "https://wa.me/222$phone?text=$encodedMessage";

                // Afficher le bouton pour envoyer le message
                echo "
                <div style='margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; display: flex; justify-content: space-between; align-items: center;'>
                    <span style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>
                        📩 رسالة جاهزة للطالب : <strong style='color: #007bff;'>$student_name</strong>
                    </span>
                    <a href=\"$whatsappUrl\" target=\"_blank\"
                        onclick=\"markAsProcessed($student_id, '$session_time', '$activity_id')\"
                        style='display: inline-block; padding: 8px 12px; background-color: #25D366; color: #fff; text-decoration: none; border-radius: 5px; font-family: Arial, sans-serif; font-size: 14px;'>
                        📤 إرسال إلى $phone
                    </a>
                </div>";
                
            } else {
                // Numéro de téléphone manquant
                echo "
                <div style='margin-bottom: 15px; padding: 15px; border: 1px solid red; border-radius: 8px; background-color: #FFCCCC;'>
                    <span style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>
                        ⚠️ لا يوجد رقم هاتف متاح للطالب : <strong style='color: #007bff;'>$student_name</strong>
                    </span>
                </div>";
            }
        } else {
            // Étudiant introuvable
            echo "<div style='margin-bottom: 15px; padding: 15px; border: 1px solid orange; border-radius: 8px; background-color: #FFF4E5;'>
                ⚠️ الطالب غير موجود برقم ID : $student_id
            </div>";
        }
    }
}
?>

<script>
function markAsProcessed(studentId, session_time, activity_id) {

    fetch('ab_mark_processed_activity.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: studentId, session_time: session_time,  activity_id: activity_id})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('خطأ أثناء معالجة الطلب.');
        }
        location.reload();
    })
    .catch(error => {
        console.error('Erreur réseau :', error);
        alert('خطأ أثناء الاتصال بالخادم.');
    });
}

</script>


