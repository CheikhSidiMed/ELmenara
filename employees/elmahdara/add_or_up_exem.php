<?php
include '../db_connection.php';

$conn->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $num_count = $_POST['num_count'];
    $num_hivd = $_POST['num_hivd'];
    $tjwid = $_POST['tjwid'];
    $houdour = $_POST['houdour'];
    $moyen = $_POST['moyen'];
    $NB = $_POST['NB'];
    $date = $_POST['date'];

    try {
        // Vérifier si l'entrée existe déjà
        $checkSql = "SELECT id FROM exam WHERE student_id = ? AND date = ? ";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param('is', $studentId, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        if ($exists) {
            // Mise à jour de l'existant
            $updateSql = "UPDATE exam SET num_count = ?, num_hivd = ?, tjwid = ?, houdour = ?, moyen = ?, NB = ? WHERE student_id = ? AND date = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param('iissssis', $num_count, $num_hivd, $tjwid, $houdour, $moyen, $NB, $studentId, $date);
        } else {
            // Insertion d'une nouvelle ligne
            $insertSql = "INSERT INTO exam (student_id, num_count, num_hivd, tjwid, houdour, moyen, NB) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param('iiissss', $studentId, $num_count, $num_hivd, $tjwid, $houdour, $moyen, $NB);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur SQL: ' . $stmt->error]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
