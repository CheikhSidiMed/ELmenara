<?php
session_start();

// Vérifier si la session des étudiants traités est initialisée
if (!isset($_SESSION['dept_students'])) {
    $_SESSION['dept_students'] = [];
}

// Lire les données JSON de la requête POST
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['student_id'])) {
    $student_id = intval($data['student_id']);
    $currentTime = time();

    // Ajouter l'étudiant à la session avec le timestamp
    $_SESSION['dept_students'][$student_id] = $currentTime;

    echo json_encode(['status' => 'success', 'message' => 'Étudiant marqué comme traité']);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID étudiant manquant']);
}
?>
